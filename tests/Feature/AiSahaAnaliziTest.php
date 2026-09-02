<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSahaAnalizi as SahaSayfasi;
use App\Filament\Pages\DofOlustur;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\SahaAnalizi;
use App\Models\User;
use App\Support\GeminiSahaAnalizi;
use App\Support\SahaAnaliziUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AiSahaAnaliziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_secilince_igu_bilgileri_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe', 'sertifika_no' => 'A-99']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'isveren_ad' => 'Ali Patron']);

        $component = Livewire::test(SahaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('gozetimYapan'));
        $this->assertSame('A-99', $component->get('gozetimYapanSertifikaNo'));
        $this->assertSame('Ali Patron', $component->get('isverenVekiliAdi'));
    }

    public function test_ai_api_anahtari_yokken_pasif_ve_istek_atilmaz(): void
    {
        config(['services.gemini.key' => null]);

        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        Http::fake();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniFotograflar', [UploadedFile::fake()->image('saha.jpg')])
            ->call('fotograflariAnalizEt');

        Http::assertNothingSent();
        $this->assertFalse(GeminiSahaAnalizi::aktifMi());
    }

    public function test_fotograf_analiz_edilince_bulgu_eklenir(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Storage::fake('public');

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    [
                        'foto_index' => 1,
                        'bina_bolge' => 'Depo',
                        'kategori' => 'Düzen/Temizlik',
                        'tespit' => 'Hortumlar zeminde dağınık bırakılmış.',
                        'oneriler' => ['Hortumlar toplanmalı.', 'Kablo köprüsü kullanılmalı.'],
                        'yasal_gerekce' => '6331 sayılı Kanun m.4',
                        'risk_derecesi' => 3,
                    ],
                ])]]]]],
            ], 200),
        ]);

        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniFotograflar', [UploadedFile::fake()->image('saha.jpg')])
            ->call('fotograflariAnalizEt');

        $bulgular = $component->get('bulgular');
        $this->assertCount(1, $bulgular);
        $this->assertSame('Depo', $bulgular[0]['bina_bolge']);
        $this->assertSame(3, $bulgular[0]['risk_derecesi']);
        $this->assertTrue($bulgular[0]['secili']);
        $this->assertStringContainsString('Hortumlar toplanmalı.', $bulgular[0]['oneriler_metni']);
        $this->assertCount(0, $component->get('yeniFotograflar'));
        $this->assertCount(1, $component->get('yuklenenFotograflar'));
    }

    public function test_bulgu_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [
                ['foto_yolu' => null, 'bina_bolge' => null, 'kategori' => null, 'tespit' => 'Test', 'oneriler_metni' => '', 'yasal_gerekce' => null, 'risk_derecesi' => 3, 'secili' => true],
            ])
            ->call('bulguSil', 0);

        $this->assertCount(0, $component->get('bulgular'));
    }

    public function test_secilenleri_dofe_aktar_oturuma_kaydeder_ve_yonlendirir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [
                ['foto_yolu' => null, 'bina_bolge' => 'Depo', 'kategori' => 'Genel', 'tespit' => 'Hortumlar dağınık.', 'oneriler_metni' => "Topla.\nSabitle.", 'yasal_gerekce' => '6331 m.4', 'risk_derecesi' => 2, 'secili' => true],
                ['foto_yolu' => null, 'bina_bolge' => null, 'kategori' => null, 'tespit' => 'Seçilmeyen bulgu', 'oneriler_metni' => '', 'yasal_gerekce' => null, 'risk_derecesi' => 4, 'secili' => false],
            ])
            ->call('secilenleriDofeAktar')
            ->assertRedirect(DofOlustur::getUrl());

        $aktarim = session('dof_aktarim');
        $this->assertSame($firma->id, $aktarim['firma_id']);
        $this->assertCount(1, $aktarim['maddeler']);
        $this->assertStringContainsString('[Depo] Hortumlar dağınık.', $aktarim['maddeler'][0]['tespit']);
        $this->assertSame('yuksek', $aktarim['maddeler'][0]['oncelik']);
        $this->assertStringContainsString('Topla.', $aktarim['maddeler'][0]['oneri']);
        $this->assertStringContainsString('Yasal dayanak: 6331 m.4', $aktarim['maddeler'][0]['oneri']);
    }

    public function test_pdf_aksiyonu_secili_bulgulari_kaydeder_ve_kase_snapshotlanir(): void
    {
        Storage::fake('public');
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [
                ['foto_yolu' => null, 'bina_bolge' => 'Depo', 'kategori' => 'Genel', 'tespit' => 'Tespit 1', 'oneriler_metni' => "Öneri A\nÖneri B", 'yasal_gerekce' => 'm.4', 'risk_derecesi' => 2, 'secili' => true],
                ['foto_yolu' => null, 'bina_bolge' => 'Ofis', 'kategori' => 'Genel', 'tespit' => 'Tespit 2', 'oneriler_metni' => 'Öneri C', 'yasal_gerekce' => 'm.5', 'risk_derecesi' => 4, 'secili' => false],
            ])
            ->callAction('pdf');

        $rapor = SahaAnalizi::where('firma_id', $firma->id)->firstOrFail();
        $this->assertCount(1, $rapor->bulgular);
        $this->assertSame('Depo', $rapor->bulgular[0]['bina_bolge']);
        $this->assertSame(['Öneri A', 'Öneri B'], $rapor->bulgular[0]['oneriler']);
        $this->assertSame('isg-profesyonel-kase/x.png', $rapor->gozetim_yapan_kase);
        $this->assertStringStartsWith('SAHA-'.now()->year.'-', $rapor->belge_no);
    }

    public function test_secili_bulgu_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('bulgular', [
                ['foto_yolu' => null, 'bina_bolge' => null, 'kategori' => null, 'tespit' => 'Test', 'oneriler_metni' => '', 'yasal_gerekce' => null, 'risk_derecesi' => 3, 'secili' => false],
            ]);

        $this->assertDatabaseCount('saha_analizleri', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = SahaAnalizi::create([
            'firma_id' => $firma->id,
            'bulgular' => [['bina_bolge' => 'Depo', 'tespit' => 'Test', 'oneriler' => ['Öneri'], 'yasal_gerekce' => 'm.4', 'risk_derecesi' => 3]],
        ]);

        $yanit = SahaAnaliziUretici::pdf($rapor);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = SahaAnalizi::create(['firma_id' => $firma->id, 'bulgular' => []]);

        Livewire::test(SahaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $rapor->id);

        $this->assertDatabaseMissing('saha_analizleri', ['id' => $rapor->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SahaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
