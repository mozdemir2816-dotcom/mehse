<?php

namespace Tests\Feature;

use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\GeminiKararDanismani;
use App\Support\KurulToplantisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class KurulToplantisiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_yeni_toplanti_olusturulur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yer', 'Toplantı Salonu')
            ->set('baskan', 'Ali Veli')
            ->call('yeniToplanti');

        $this->assertDatabaseHas('kurul_toplantilari', [
            'firma_id' => $firma->id,
            'yer' => 'Toplantı Salonu',
            'baskan' => 'Ali Veli',
        ]);
        $this->assertNotNull($component->get('toplantiId'));
    }

    public function test_katilimci_eklenir_ve_katilim_durumu_degistirilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        $component = Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->set('yeniKatilimciAd', 'Ahmet Yılmaz')
            ->set('yeniKatilimciGorev', 'İş Güvenliği Uzmanı')
            ->call('katilimciEkle');

        $toplanti->refresh();
        $this->assertCount(1, $toplanti->katilimcilar);
        $this->assertTrue($toplanti->katilimcilar[0]['katildi']);

        $component->call('katilimToggle', 0);
        $toplanti->refresh();
        $this->assertFalse($toplanti->katilimcilar[0]['katildi']);
    }

    public function test_firma_calisanindan_hizli_katilimci_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Zeynep Kaya']);
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('katilimHizliEkle', $calisan->id);

        $toplanti->refresh();
        $this->assertSame('Zeynep Kaya', $toplanti->katilimcilar[0]['ad_soyad']);
    }

    public function test_gundem_manuel_ve_hazir_maddeyle_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        $hazirMadde = config('isg.kurul_toplantisi.hazir_gundem_maddeleri.Genel.0');

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->set('yeniGundemMaddesi', 'Manuel gündem maddesi')
            ->call('gundemEkle')
            ->call('hazirGundemEkle', $hazirMadde);

        $toplanti->refresh();
        $this->assertContains('Manuel gündem maddesi', $toplanti->gundem);
        $this->assertContains($hazirMadde, $toplanti->gundem);
    }

    public function test_gundem_maddesinden_karar_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'katilimcilar' => [], 'gundem' => ['Yıllık plan gözden geçirme'], 'kararlar' => [],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('kararFormuAc', 0)
            ->set('yeniKararMetni', 'Plan Mart ayında güncellenecek')
            ->set('yeniKararSorumlu', 'İGU')
            ->set('yeniKararTermin', '2026-03-01')
            ->call('kararEkle');

        $toplanti->refresh();
        $this->assertCount(1, $toplanti->kararlar);
        $this->assertSame('Yıllık plan gözden geçirme', $toplanti->kararlar[0]['gundem_maddesi']);
        $this->assertSame('beklemede', $toplanti->kararlar[0]['durum']);
    }

    public function test_yeni_toplantiya_firma_yil_bazli_no_atanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $yil = now()->year;

        Livewire::test(KurulSayfasi::class)->set('firmaId', $firma->id)->call('yeniToplanti');
        Livewire::test(KurulSayfasi::class)->set('firmaId', $firma->id)->call('yeniToplanti');

        $nolar = $firma->kurulToplantilari()->orderBy('id')->pluck('toplanti_no')->all();
        $this->assertSame(["{$yil}/1", "{$yil}/2"], $nolar);
    }

    public function test_toplanti_no_elle_duzeltilebilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['toplanti_no' => '2026/1', 'tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->set('toplantiNo', '2026/A-1')
            ->call('toplantiBilgileriniKaydet');

        $this->assertSame('2026/A-1', $toplanti->refresh()->toplanti_no);
    }

    public function test_karar_kayittan_sonra_duzenlenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'katilimcilar' => [], 'gundem' => ['Gündem X'],
            'kararlar' => [['gundem_maddesi' => 'Gündem X', 'karar_metni' => 'Eski metin', 'sorumlu' => 'A', 'termin' => null, 'durum' => 'devam_ediyor']],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('kararDuzenle', 0)
            ->assertSet('yeniKararMetni', 'Eski metin')
            ->set('yeniKararMetni', 'Yeni düzeltilmiş metin')
            ->set('yeniKararSorumlu', 'B')
            ->call('kararGuncelle');

        $karar = $toplanti->refresh()->kararlar[0];
        $this->assertSame('Yeni düzeltilmiş metin', $karar['karar_metni']);
        $this->assertSame('B', $karar['sorumlu']);
        $this->assertSame('Gündem X', $karar['gundem_maddesi']); // korunur
        $this->assertSame('devam_ediyor', $karar['durum']);      // korunur
    }

    public function test_excel_uretilir_durum_sutunu_olmadan(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'toplanti_no' => '2026/3', 'tarih' => now(),
            'katilimcilar' => [['ad_soyad' => 'Test', 'gorev' => 'İGU', 'katildi' => true]],
            'gundem' => ['Madde 1'],
            'kararlar' => [['gundem_maddesi' => 'Madde 1', 'karar_metni' => 'Karar', 'sorumlu' => 'X', 'termin' => null, 'durum' => 'beklemede']],
        ]);

        $yanit = KurulToplantisiUretici::excel($toplanti);
        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('PK', ob_get_clean());
    }

    public function test_pdf_kararlar_tablosunda_durum_sutunu_yok_toplanti_no_var(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'toplanti_no' => '2026/7', 'tarih' => now(),
            'katilimcilar' => [], 'gundem' => ['G1'],
            'kararlar' => [['gundem_maddesi' => 'G1', 'karar_metni' => 'K1', 'sorumlu' => 'S1', 'termin' => null, 'durum' => 'tamamlandi']],
        ]);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();

        $this->assertStringContainsString('Toplantı No', $html);
        $this->assertStringContainsString('2026/7', $html);
        $this->assertStringContainsString('<th>Karar Metni</th>', $html);
        // Kararlar tablosunda "Durum" başlığı/rozeti kaldırıldı (karar metni sütunu genişledi).
        $this->assertStringNotContainsString('<th>Durum</th>', $html);
        $this->assertStringNotContainsString('Tamamlandı', $html);
        $this->assertStringNotContainsString('class="durum"', $html);
    }

    public function test_karar_durumu_guncellenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create([
            'tarih' => now(), 'katilimcilar' => [], 'gundem' => [],
            'kararlar' => [['gundem_maddesi' => 'X', 'karar_metni' => 'Y', 'sorumlu' => null, 'termin' => null, 'durum' => 'beklemede']],
        ]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSec', $toplanti->id)
            ->call('kararDurumGuncelle', 0, 'tamamlandi');

        $toplanti->refresh();
        $this->assertSame('tamamlandi', $toplanti->kararlar[0]['durum']);
    }

    public function test_gemini_karar_onerisi_api_anahtari_yokken_pasif(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse(GeminiKararDanismani::aktifMi());
        $this->assertNull(GeminiKararDanismani::oner('Bir gündem maddesi'));
    }

    public function test_gemini_karar_onerisi_gecerli_yanit_doner(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Öneri: yıllık plan mart ayında güncellenecek.']]]]],
            ], 200),
        ]);

        $oneri = GeminiKararDanismani::oner('Yıllık plan gözden geçirme');

        $this->assertSame('Öneri: yıllık plan mart ayında güncellenecek.', $oneri);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id,
            'tarih' => now(),
            'katilimcilar' => [['ad_soyad' => 'Test', 'gorev' => 'İGU', 'katildi' => true]],
            'gundem' => ['Madde 1'],
            'kararlar' => [['gundem_maddesi' => 'Madde 1', 'karar_metni' => 'Karar', 'sorumlu' => null, 'termin' => null, 'durum' => 'beklemede']],
        ]);

        $yanit = KurulToplantisiUretici::pdf($toplanti);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_pdf_katilimcilar_tablosunda_katilim_yerine_imza_yeri_acilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id,
            'tarih' => now(),
            'katilimcilar' => [
                ['ad_soyad' => 'Katılan Kişi', 'gorev' => 'İGU', 'katildi' => true],
                ['ad_soyad' => 'Katılmayan Kişi', 'gorev' => 'İşveren Vekili', 'katildi' => false],
            ],
            'gundem' => [],
            'kararlar' => [],
        ]);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();

        $this->assertStringContainsString('İmza</th>', $html);
        $this->assertStringNotContainsString('Katıldı<', $html);
        $this->assertStringContainsString('Katılmayan Kişi', $html);
        $this->assertStringContainsString('Katılmadı', $html);
    }

    public function test_toplanti_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $toplanti = $firma->kurulToplantilari()->create(['tarih' => now(), 'katilimcilar' => [], 'gundem' => [], 'kararlar' => []]);

        Livewire::test(KurulSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('toplantiSil', $toplanti->id);

        $this->assertDatabaseMissing('kurul_toplantilari', ['id' => $toplanti->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(KurulSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
