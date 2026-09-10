<?php

namespace Tests\Feature;

use App\Filament\Pages\SertifikaOlustur as SertifikaSayfasi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\Sertifika;
use App\Models\User;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\SertifikaUretici;
use App\Support\SertifikaYildizGrupUretici;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class SertifikaOlusturTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_secilince_calisanlar_ve_egitici_otomatik_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu', 'ad_soyad' => 'İGU Ayşe']);
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'isyeri_hekimi', 'ad_soyad' => 'Dr. Mehmet']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id, 'isyeri_hekimi_id' => $hekim->id]);
        $c = Calisan::factory()->for($firma)->create();

        $component = Livewire::test(SertifikaSayfasi::class)->set('firmaId', $firma->id);

        $this->assertSame('İGU Ayşe', $component->get('egiticiIguAdi'));
        $this->assertSame('Dr. Mehmet', $component->get('egiticiHekimAdi'));
        $this->assertSame([$c->id], $component->get('secilenCalisanIdler'));
    }

    public function test_tekli_tip_sabit_icerik_doner(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tip', 'yukseklik');

        $icerik = $component->get('icerik');
        $this->assertSame(
            config('isg.egitim.ozel_basliklar.yuksekte_calisma.maddeler'),
            collect($icerik['maddeler'])->pluck('madde')->all(),
        );
    }

    public function test_isg_tipi_sektor_secilince_icerik_isyerine_ozgu_gelir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'tehlikeli']);

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('sektorAnahtari', 'insaat');

        $icerik = $component->get('icerik');
        $this->assertSame('İnşaat', $icerik['isyerine_ozgu']['sektor']);
    }

    public function test_gecerlilik_otomatik_hesaplanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('egitimTarihleri.0', '2026-01-10')
            ->call('gecerlilikOtomatik', 'tehlikeli');

        $this->assertSame('2028-01-10', $component->get('gecerlilikTarihi'));
    }

    public function test_gun_sayisi_degisince_tarih_dizisi_boyutlanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('gunSayisi', 3);

        $this->assertCount(3, $component->get('egitimTarihleri'));
    }

    public function test_pdf_aksiyonu_kayit_olusturur_ve_kase_snapshotlanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu', 'kase_gorseli' => 'isg-profesyonel-kase/x.png']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);
        $c = Calisan::factory()->for($firma)->create(['ad_soyad' => 'Test Çalışan']);

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tip', 'isg')
            ->callAction('pdf');

        $s = Sertifika::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('isg', $s->tip);
        $this->assertSame('isg-profesyonel-kase/x.png', $s->egitici_igu_kase);
        $this->assertCount(1, $s->katilimcilar);
        $this->assertStringStartsWith('SRT-'.now()->year.'-', $s->belge_no);
    }

    public function test_katilimci_olmadan_kaydedilemez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('tumCalisanlar', false)
            ->callAction('pdf');

        $this->assertDatabaseCount('sertifikalar', 0);
    }

    public function test_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'katilimcilar' => [['ad_soyad' => 'Test Kişi', 'tc' => null, 'gorev' => null]],
            'konu_icerigi' => ['tip' => 'genel', 'genel_konular' => [], 'saglik_konulari' => [], 'teknik_konular' => []],
        ]);

        $yanit = SertifikaUretici::pdf($s);

        $this->assertInstanceOf(StreamedResponse::class, $yanit);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $icerik);
    }

    public function test_gecmis_kayit_silinir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $s = Sertifika::create(['firma_id' => $firma->id, 'tip' => 'isg', 'katilimcilar' => []]);

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->call('gecmisSil', $s->id);

        $this->assertDatabaseMissing('sertifikalar', ['id' => $s->id]);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();

        $firmalar = Livewire::test(SertifikaSayfasi::class)->instance()->firmalar();

        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    public function test_tur_ve_sekil_kaydedilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create();

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tur', 'tekrar')
            ->set('sekil', 'uzaktan')
            ->callAction('pdf');

        $s = Sertifika::where('firma_id', $firma->id)->firstOrFail();
        $this->assertSame('tekrar', $s->tur);
        $this->assertSame('uzaktan', $s->sekil);
    }

    public function test_tur_tekrar_secilince_icerik_8_saate_gore_yeniden_olceklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tip', 'isg');

        $ilkDefaToplam = collect($component->get('icerik')['genel_konular'])->sum('dakika');

        $component->set('tur', 'tekrar');
        $tekrarToplam = collect($component->get('icerik')['genel_konular'])->sum('dakika');

        // çok tehlikeli ilk defa: blok başına 180dk hedef; tekrar: her zaman 90dk hedef.
        $this->assertGreaterThan($tekrarToplam, $ilkDefaToplam);
    }

    public function test_madde_hariç_birakilinca_pdfde_gorunmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $component = Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('icerik.genel_konular.0.dahil', false)
            ->call('tumCalisanlar', false)
            ->set('manuelKatilimcilar', [['ad_soyad' => 'Test Kişi', 'tc' => null, 'gorev' => null]])
            ->callAction('pdf');

        $s = Sertifika::where('firma_id', $firma->id)->firstOrFail();
        $ilkMadde = config('isg.egitim.genel_konular.0.madde');

        $html = view('pdf.sertifika', ['sertifika' => $s, 'firma' => $firma])->render();

        $this->assertStringNotContainsString($ilkMadde, $html);
    }

    public function test_pdf_referans_belge_bilgilerini_icerir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Test Firma A.Ş.']);
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'tur' => 'ilk_defa',
            'sekil' => 'yuz_yuze',
            'katilimcilar' => [['ad_soyad' => 'Test Kişi', 'tc' => '12345678901', 'gorev' => 'İşçi']],
            'konu_icerigi' => EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli'),
        ]);

        $html = view('pdf.sertifika', ['sertifika' => $s, 'firma' => $firma])->render();

        $this->assertStringContainsString('Katılımcının Adı Soyadı', $html);
        $this->assertStringContainsString('Eğitim Türü / Şekli', $html);
        $this->assertStringContainsString('İlk Defa Eğitim', $html);
        $this->assertStringContainsString('Yüz Yüze', $html);
        $this->assertStringContainsString('Eğitimin Konuları', $html);
        $this->assertStringContainsString('1. Genel Konular', $html);
        $this->assertStringContainsString('a)', $html);
        $this->assertStringContainsString('Düzenleme Tarihi', $html);
    }

    public function test_dort_gercek_cerceve_secenegi_mevcut(): void
    {
        $this->assertSame(
            ['sade', 'mavi_kose', 'altin_susleme', 'gri_cizgi'],
            array_keys(config('isg.sertifika.cerceveler')),
        );
    }

    #[DataProvider('cerceveSaglayici')]
    public function test_her_cerceve_ve_tip_kombinasyonu_tek_sayfaya_sigar(string $cerceve): void
    {
        $firma = Firma::factory()->create(['unvan' => 'NİL UNLU MAMULLER GIDA PASTACILIK SANAYİ VE TİCARET ANONİM ŞİRKETİ', 'tehlike_sinifi' => 'cok_tehlikeli']);

        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'kapali_alan',
            'cerceve' => $cerceve,
            'egitici_igu_dahil' => true,
            'egitici_igu_adi' => 'Mehmet Test Uzman Uzunadı',
            'katilimcilar' => [['ad_soyad' => 'Test Kişi Uzun Soyadı', 'tc' => '12345678901', 'gorev' => 'Üretim Vardiya Sorumlusu']],
            'konu_icerigi' => EgitimIcerikOlusturucu::olustur('kapali_alan', null, 'cok_tehlikeli'),
        ]);

        $pdf = Pdf::loadView('pdf.sertifika', ['sertifika' => $s, 'firma' => $firma])->setPaper('a4');
        $pdf->output();

        $this->assertSame(1, $pdf->getCanvas()->get_page_count());
    }

    public static function cerceveSaglayici(): array
    {
        return [['sade'], ['mavi_kose'], ['altin_susleme'], ['gri_cizgi']];
    }

    public function test_yildiz_grup_yalniz_isg_tipinde_uygun(): void
    {
        $isg = new Sertifika(['tip' => 'isg']);
        $yukseklik = new Sertifika(['tip' => 'yukseklik']);

        $this->assertTrue(SertifikaYildizGrupUretici::uygunMu($isg));
        $this->assertFalse(SertifikaYildizGrupUretici::uygunMu($yukseklik));
    }

    public function test_yildiz_grup_tek_katilimci_alanlari_dolduruyor(): void
    {
        $firma = Firma::factory()->create(['unvan' => 'TEST FİRMA A.Ş.']);
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'tur' => 'ilk_defa',
            'sekil' => 'yuz_yuze',
            'egitim_tarihleri' => ['2026-09-01', '2026-09-02'],
            'sure_metni' => '16 Ders Saati',
            'egitici_igu_dahil' => true,
            'egitici_igu_adi' => 'Test İGU',
            'katilimcilar' => [['ad_soyad' => 'Ahmet Yılmaz', 'tc' => '11111111111', 'gorev' => 'Operatör']],
            'konu_icerigi' => EgitimIcerikOlusturucu::olustur('genel', 'tekstil', 'az_tehlikeli'),
        ]);

        $yanit = SertifikaYildizGrupUretici::indir($s);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $gecici = tempnam(sys_get_temp_dir(), 'ygt').'.xlsx';
        file_put_contents($gecici, $icerik);
        $sheet = IOFactory::load($gecici)->getSheetByName('Çıktı Sayfası');
        unlink($gecici);

        $this->assertSame('KATILIMCININ ADI : AHMET YILMAZ', $sheet->getCell('D8')->getValue());
        $this->assertSame('GÖREVİ : OPERATÖR', $sheet->getCell('D9')->getValue());
        $this->assertSame('TEST FİRMA A.Ş.', $sheet->getCell('G29')->getValue());
        $this->assertSame('Test İGU', $sheet->getCell('G24')->getValue());
        $this->assertStringContainsString('01/09/2026 ve 02/09/2026', (string) $sheet->getCell('D10')->getValue());
        $this->assertSame('a)Çalışma mevzuatı ile ilgili bilgiler', $sheet->getCell('E44')->getValue()->getPlainText());
        $this->assertSame('a)İplik ve dokuma makine güvenliği', $sheet->getCell('E68')->getValue()->getPlainText());
        $this->assertNull($sheet->getCell('E73')->getValue());
    }

    public function test_yildiz_grup_firma_logosu_sag_uste_eklenir(): void
    {
        $logoRel = 'firma-logo/test-yildiz-'.uniqid().'.png';
        $logoTam = storage_path('app/public/'.$logoRel);
        @mkdir(dirname($logoTam), 0777, true);
        // 1x1 saydam PNG
        file_put_contents($logoTam, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ));

        $firma = Firma::factory()->create(['logo' => $logoRel]);
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'katilimcilar' => [['ad_soyad' => 'Ahmet Yılmaz', 'tc' => null, 'gorev' => null]],
            'konu_icerigi' => EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli'),
        ]);

        $yanit = SertifikaYildizGrupUretici::indir($s);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $gecici = tempnam(sys_get_temp_dir(), 'ygl').'.xlsx';
        file_put_contents($gecici, $icerik);
        $sheet = IOFactory::load($gecici)->getSheetByName('Çıktı Sayfası');
        unlink($gecici);
        @unlink($logoTam);

        $koordinatlar = collect($sheet->getDrawingCollection())->map->getCoordinates()->all();
        $this->assertContains('K4', $koordinatlar);   // firma amblemi sağ üstte
        $this->assertContains('D4', $koordinatlar);   // OSGB amblemi solda korunur
    }

    public function test_yildiz_grup_secilen_tur_ve_sekil_kalin_isaretlenir(): void
    {
        $firma = Firma::factory()->create();
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'tur' => 'tekrar',
            'sekil' => 'uzaktan',
            'katilimcilar' => [['ad_soyad' => 'Ahmet Yılmaz', 'tc' => null, 'gorev' => null]],
            'konu_icerigi' => EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli'),
        ]);

        $yanit = SertifikaYildizGrupUretici::indir($s);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $gecici = tempnam(sys_get_temp_dir(), 'ygt').'.xlsx';
        file_put_contents($gecici, $icerik);
        $sheet = IOFactory::load($gecici)->getSheetByName('Çıktı Sayfası');
        unlink($gecici);

        // tur='tekrar' -> F19 kalın + I19 çarpı, F18/I18 boş.
        $this->assertTrue($sheet->getStyle('F19')->getFont()->getBold());
        $this->assertFalse($sheet->getStyle('F18')->getFont()->getBold());
        $this->assertSame('X', $sheet->getCell('I19')->getValue());
        $this->assertEmpty($sheet->getCell('I18')->getValue());
        // sekil='uzaktan' -> F20 kalın + I20 çarpı, F21/I21 boş.
        $this->assertTrue($sheet->getStyle('F20')->getFont()->getBold());
        $this->assertFalse($sheet->getStyle('F21')->getFont()->getBold());
        $this->assertSame('X', $sheet->getCell('I20')->getValue());
        $this->assertEmpty($sheet->getCell('I21')->getValue());
        // Yanlış hizalanan eski onay işareti görselleri artık şablonda yok.
        $koordinatlar = collect($sheet->getDrawingCollection())->map->getCoordinates()->all();
        $this->assertEmpty(array_intersect(['I18', 'I19', 'I20', 'I21'], $koordinatlar));
    }

    public function test_yildiz_grup_coklu_katilimci_zip_dondurur(): void
    {
        $firma = Firma::factory()->create();
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'katilimcilar' => [
                ['ad_soyad' => 'Ahmet Yılmaz', 'tc' => '11111111111', 'gorev' => 'Operatör'],
                ['ad_soyad' => 'Ayşe Kaya', 'tc' => '22222222222', 'gorev' => 'Teknisyen'],
            ],
            'konu_icerigi' => EgitimIcerikOlusturucu::olustur('genel', null, 'az_tehlikeli'),
        ]);

        $yanit = SertifikaYildizGrupUretici::indir($s);
        ob_start();
        $yanit->sendContent();
        $icerik = ob_get_clean();

        $this->assertStringStartsWith('PK', $icerik);
        $this->assertSame('application/zip', $yanit->headers->get('Content-Type'));
    }

    public function test_yildiz_grup_diger_tiplerde_null_doner(): void
    {
        $firma = Firma::factory()->create();
        $s = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'yukseklik',
            'katilimcilar' => [['ad_soyad' => 'Test', 'tc' => null, 'gorev' => null]],
            'konu_icerigi' => EgitimIcerikOlusturucu::olustur('yuksekte_calisma', null, 'az_tehlikeli'),
        ]);

        $this->assertNull(SertifikaYildizGrupUretici::indir($s));
    }

    public function test_yildiz_grup_butonu_sadece_isg_tipinde_gorunur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create();

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tip', 'isg')
            ->assertActionVisible('yildizGrup')
            ->set('tip', 'yukseklik')
            ->assertActionHidden('yildizGrup');
    }

    public function test_yildiz_grup_aksiyonu_kayit_olusturur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create();

        Livewire::test(SertifikaSayfasi::class)
            ->set('firmaId', $firma->id)
            ->set('tip', 'isg')
            ->callAction('yildizGrup');

        $this->assertDatabaseCount('sertifikalar', 1);
    }
}
