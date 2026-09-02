<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimSorulari as SorularSayfasi;
use App\Models\Calisan;
use App\Models\EgitimSinavi;
use App\Models\Firma;
use App\Models\User;
use App\Support\EgitimSinaviUretici;
use App\Support\GeminiSoruUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class EgitimSorulariTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_api_anahtari_yokken_ai_pasif_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);

        Http::fake();

        $this->assertFalse(GeminiSoruUretici::aktifMi());
        $this->assertSame([], GeminiSoruUretici::uret('İnşaat', 'Orta'));
        Http::assertNothingSent();
    }

    public function test_gecerli_yanit_sorulara_donusturulur(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['soru' => 'Baret ne zaman takılır?', 'secenekler' => ['Her zaman', 'Hiçbir zaman', 'Bazen', 'Sadece yağmurda'], 'dogru_index' => 0],
                ])]]]]],
            ], 200),
        ]);

        $sorular = GeminiSoruUretici::uret('İnşaat', 'Orta');

        $this->assertCount(1, $sorular);
        $this->assertSame('Baret ne zaman takılır?', $sorular[0]['soru']);
    }

    public function test_eksik_secenekli_soru_elenir(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    ['soru' => 'Geçersiz soru', 'secenekler' => ['A', 'B'], 'dogru_index' => 0],
                ])]]]]],
            ], 200),
        ]);

        $this->assertSame([], GeminiSoruUretici::uret('İnşaat', 'Orta'));
    }

    public function test_manuel_soru_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SorularSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniSoruMetni', 'İş kazası kaç gün içinde bildirilir?')
            ->set('yeniSecenekler', ['1 gün', '3 gün', '10 gün', '30 gün'])
            ->set('yeniDogruIndex', 1)
            ->call('soruEkle');

        $this->assertCount(1, $component->get('sorular'));

        $component->call('soruSil', 0);
        $this->assertCount(0, $component->get('sorular'));
    }

    public function test_firma_calisanindan_katilimci_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Ahmet Yılmaz']);

        $component = Livewire::test(SorularSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('katilimciHizliEkle', $calisan->id);

        $this->assertSame('Ahmet Yılmaz', $component->get('katilimcilar')[0]['ad_soyad']);
    }

    public function test_pdf_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SorularSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sektorAnahtari', 'insaat')
            ->set('zorluk', 'kolay')
            ->set('yeniSoruMetni', 'Soru 1')
            ->set('yeniSecenekler', ['A', 'B', 'C', 'D'])
            ->set('yeniDogruIndex', 2)
            ->call('soruEkle')
            ->callAction('pdf');

        $sinav = EgitimSinavi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('insaat', $sinav->sektor_anahtari);
        $this->assertSame('kolay', $sinav->zorluk);
        $this->assertCount(1, $sinav->sorular);
    }

    public function test_soru_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SorularSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('egitim_sinavlari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $sinav = EgitimSinavi::create([
            'firma_id' => $firma->id,
            'sektor_anahtari' => null,
            'zorluk' => 'karisik',
            'cevap_anahtari_dahil' => true,
            'sorular' => [['soru' => 'Test sorusu', 'secenekler' => ['A', 'B', 'C', 'D'], 'dogru_index' => 0]],
            'katilimcilar' => [['ad_soyad' => 'Test Kişi', 'tc' => null, 'sinav_tarihi' => '2026-09-02']],
        ]);

        $yanit = EgitimSinaviUretici::pdf($sinav);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_sinav_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $sinav = EgitimSinavi::create([
            'firma_id' => $firma->id, 'zorluk' => 'orta', 'cevap_anahtari_dahil' => true,
            'sorular' => [], 'katilimcilar' => [],
        ]);

        Livewire::test(SorularSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $sinav->id);

        $this->assertDatabaseMissing('egitim_sinavlari', ['id' => $sinav->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SorularSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
