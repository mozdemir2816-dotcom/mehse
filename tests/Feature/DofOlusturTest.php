<?php

namespace Tests\Feature;

use App\Filament\Pages\DofOlustur as DofSayfasi;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use App\Support\DofRaporuUretici;
use App\Support\GeminiOneriDanismani;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class DofOlusturTest extends TestCase
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
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'İGU Ayşe', 'sertifika_no' => 'A-12345']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'isveren_ad' => 'Ali Patron']);

        $component = Livewire::test(DofSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('gozetimYapan'));
        $this->assertSame('A-12345', $component->get('gozetimYapanSertifikaNo'));
        $this->assertSame('Ali Patron', $component->get('isverenVekiliAdi'));
    }

    public function test_ai_saha_analizinden_aktarilan_maddeler_mount_ile_yuklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        session(['dof_aktarim' => [
            'firma_id' => $firma->id,
            'maddeler' => [
                ['tespit' => '[Depo] Hortumlar dağınık.', 'oncelik' => 'yuksek', 'oneri' => 'Topla.', 'sorumlu' => null, 'termin' => null, 'durum' => 'acik'],
            ],
        ]]);

        $component = Livewire::test(DofSayfasi::class);

        $this->assertSame($firma->id, $component->get('firmaId'));
        $this->assertCount(1, $component->get('maddeler'));
        $this->assertSame('[Depo] Hortumlar dağınık.', $component->get('maddeler')[0]['tespit']);
        $this->assertNull(session('dof_aktarim'));
    }

    public function test_madde_eklenir_ve_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Acil çıkış kapısı önü dolu')
            ->set('yeniOncelik', 'yuksek')
            ->call('maddeEkle');

        $this->assertCount(1, $component->get('maddeler'));
        $this->assertSame('acik', $component->get('maddeler')[0]['durum']);

        $component->call('maddeSil', 0);
        $this->assertCount(0, $component->get('maddeler'));
    }

    public function test_madde_eklenirken_fotograf_yuklenip_kaydedilir(): void
    {
        Storage::fake('public');
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Kaygan zemin')
            ->set('yeniFoto', UploadedFile::fake()->image('kanit.jpg'))
            ->call('maddeEkle');

        $madde = $component->get('maddeler')[0];
        $this->assertNotNull($madde['foto_yolu']);
        Storage::disk('public')->assertExists($madde['foto_yolu']);
        $this->assertNull($component->get('yeniFoto'));
    }

    public function test_fotografli_dof_pdfinde_kanit_sayfasi_olusur(): void
    {
        Storage::fake('public');
        $yol = UploadedFile::fake()->image('kanit.jpg')->store('dof-foto', 'public');

        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = DofRaporu::create([
            'firma_id' => $firma->id,
            'maddeler' => [['tespit' => 'Kaygan zemin', 'oncelik' => 'orta', 'durum' => 'acik', 'foto_yolu' => $yol]],
        ]);

        $html = view('pdf.dof-raporu', ['rapor' => $rapor, 'firma' => $firma])->render();

        $this->assertStringContainsString('FOTOĞRAF KANITI', $html);
        $this->assertStringContainsString($yol, $html);
    }

    public function test_dof_pdf_madde_tablosunda_satir_ici_foto_gosterilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = DofRaporu::create([
            'firma_id' => $firma->id,
            'maddeler' => [['tespit' => 'Kaygan zemin', 'oncelik' => 'orta', 'durum' => 'acik', 'foto_yolu' => 'dof-foto/kanit.jpg']],
        ]);

        $html = view('pdf.dof-raporu', ['rapor' => $rapor, 'firma' => $firma])->render();

        $this->assertStringContainsString('class="satir-foto"', $html);
    }

    public function test_durum_guncellenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Test tespit')
            ->call('maddeEkle')
            ->call('durumGuncelle', 0, 'tamamlandi');

        $this->assertSame('tamamlandi', $component->get('maddeler')[0]['durum']);
    }

    public function test_ai_onerisi_gecerli_yanit_maddeye_yazilir(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Kapı önü derhal boşaltılmalı.']]]]],
            ], 200),
        ]);

        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Acil çıkış kapısı önü dolu')
            ->call('aiOnerisiAl');

        $this->assertSame('Kapı önü derhal boşaltılmalı.', $component->get('yeniOneri'));
    }

    public function test_ai_api_anahtari_yokken_pasif(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse(GeminiOneriDanismani::aktifMi());
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('yeniTespit', 'Test tespit')
            ->call('maddeEkle')
            ->callAction('pdf');

        $rapor = DofRaporu::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('isg-profesyonel-kase/x.png', $rapor->gozetim_yapan_kase);
        $this->assertCount(1, $rapor->maddeler);
        $this->assertStringStartsWith('DOF-'.now()->year.'-', $rapor->belge_no);
    }

    public function test_madde_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('pdf');

        $this->assertDatabaseCount('dof_raporlari', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = DofRaporu::create([
            'firma_id' => $firma->id,
            'maddeler' => [['tespit' => 'Test', 'oncelik' => 'orta', 'oneri' => null, 'sorumlu' => null, 'termin' => null, 'durum' => 'acik']],
        ]);

        $yanit = DofRaporuUretici::pdf($rapor);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = DofRaporu::create(['firma_id' => $firma->id, 'maddeler' => []]);

        Livewire::test(DofSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $rapor->id);

        $this->assertDatabaseMissing('dof_raporlari', ['id' => $rapor->id]);
    }

    public function test_takip_ozeti_tum_firmalarin_maddelerini_sayar(): void
    {
        $firma1 = Firma::factory()->for($this->uzman)->create();
        $firma2 = Firma::factory()->for($this->uzman)->create();

        DofRaporu::create(['firma_id' => $firma1->id, 'maddeler' => [
            ['tespit' => 'A', 'oncelik' => 'kritik', 'durum' => 'acik'],
            ['tespit' => 'B', 'oncelik' => 'orta', 'durum' => 'tamamlandi'],
        ]]);
        DofRaporu::create(['firma_id' => $firma2->id, 'maddeler' => [
            ['tespit' => 'C', 'oncelik' => 'yuksek', 'durum' => 'devam_ediyor'],
        ]]);

        $ozet = Livewire::test(DofSayfasi::class)->instance()->takipOzeti();

        $this->assertSame(2, $ozet['toplam_dof']);
        $this->assertSame(2, $ozet['acik_madde']);
        $this->assertSame(1, $ozet['kapanmis_madde']);
        $this->assertEquals(33, $ozet['kapatma_orani']);
    }

    public function test_madde_kapat_durumu_tamamlandi_yapar_ve_not_ekler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rapor = DofRaporu::create(['firma_id' => $firma->id, 'maddeler' => [
            ['tespit' => 'Test tespit', 'oncelik' => 'orta', 'durum' => 'acik'],
        ]]);

        Livewire::test(DofSayfasi::class)
            ->set("kapatmaNotlari.{$rapor->id}-0", 'Düzeltildi, kontrol edildi.')
            ->call('maddeKapat', $rapor->id, 0);

        $rapor->refresh();
        $this->assertSame('tamamlandi', $rapor->maddeler[0]['durum']);
        $this->assertSame('Düzeltildi, kontrol edildi.', $rapor->maddeler[0]['kapatma_notu']);
        $this->assertNotNull($rapor->maddeler[0]['kapatma_tarihi']);
    }

    public function test_baska_uzmanin_dof_raporunu_kapatamaz(): void
    {
        $baskaFirma = Firma::factory()->create();
        $rapor = DofRaporu::create(['firma_id' => $baskaFirma->id, 'maddeler' => [
            ['tespit' => 'X', 'oncelik' => 'orta', 'durum' => 'acik'],
        ]]);

        Livewire::test(DofSayfasi::class)->call('maddeKapat', $rapor->id, 0);

        $rapor->refresh();
        $this->assertSame('acik', $rapor->maddeler[0]['durum']);
    }

    public function test_acik_maddeler_oncelik_ve_arama_ile_filtrelenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Filtre Firması']);
        DofRaporu::create(['firma_id' => $firma->id, 'maddeler' => [
            ['tespit' => 'Yangın söndürücü eksik', 'oncelik' => 'kritik', 'durum' => 'acik'],
            ['tespit' => 'Merdiven aydınlatması', 'oncelik' => 'dusuk', 'durum' => 'acik'],
        ]]);

        $component = Livewire::test(DofSayfasi::class)->set('takipOncelikFiltre', 'kritik');
        $this->assertCount(1, $component->instance()->acikMaddelerFiltreli());

        $component->set('takipOncelikFiltre', '')->set('takipArama', 'merdiven');
        $this->assertCount(1, $component->instance()->acikMaddelerFiltreli());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(DofSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
