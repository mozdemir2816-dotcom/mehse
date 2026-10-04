<?php

namespace Tests\Feature;

use App\Filament\Pages\DokumanYonetimi;
use App\Models\ArsivDosya;
use App\Models\BelgeSablonu;
use App\Models\Bildirim;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\Talimat;
use App\Models\User;
use App\Support\ArsivKurali;
use App\Support\ArsivUretici;
use App\Support\BelgeSablonMotoru;
use App\Support\BildirimTarayici;
use App\Support\EtiketliSablon;
use App\Support\IsyeriDurumu;
use App\Support\KullaniciAyarlari;
use App\Support\YillikDegerlendirmeFormUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;
use ZipArchive;

/**
 * Arşiv (eski Doküman Yönetimi) — kategori kuralları, yükleme, şablondan
 * üretim, dosyaya ekleme, ayarlar, bildirimler. Profilim > Arşiv: ArsivTest.
 */
class ArsivSayfasiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Carbon::setTestNow('2026-10-04 10:00:00');
        KullaniciAyarlari::onbellegiTemizle();
        $this->uzman = User::factory()->create(['name' => 'Mehmet Uzman']);
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Ahmet Yapı', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 20,
            'adres' => 'Atatürk Cad. 1', 'ilce' => null, 'il' => 'Bursa', 'igu_id' => null, 'isyeri_hekimi_id' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function kayit(string $kategori, array $ek = []): ArsivDosya
    {
        $yol = 'arsiv/'.uniqid().'.pdf';
        Storage::disk('public')->put($yol, '%PDF-1.4 deneme');

        return ArsivDosya::create([
            'firma_id' => $this->firma->id, 'kategori' => $kategori, 'baslik' => $kategori,
            'dosya_adi' => basename($yol), 'dosya_yolu' => $yol, 'boyut' => 15, ...$ek,
        ]);
    }

    /** Saf kural testi: varsayılan takip dışı listesi uygulanmaz ($haric = null → []). */
    private function durum(string $kategori, ?array $haric = []): array
    {
        $kayitlar = ArsivDosya::where('firma_id', $this->firma->id)->get()
            ->filter(fn ($d) => ArsivKurali::kategori($d->kategori)['anahtar'] === $kategori);

        return ArsivKurali::durum($this->firma, $kategori, $kayitlar, null, $haric ?? KullaniciAyarlari::arsivHaric());
    }

    /*
    | Kurallar
    */

    public function test_suresiz_kategori_belge_yuklenene_kadar_eksik_sonra_yururlukte(): void
    {
        $this->assertSame('eksik', $this->durum('igu_sozlesmesi')['durum']);

        $d = $this->kayit('igu_sozlesmesi', ['baslangic_tarihi' => '2020-01-01']);
        $s = $this->durum('igu_sozlesmesi');
        $this->assertSame('tamam', $s['durum']);
        $this->assertSame($d->id, $s['gecerli']->id);
        $this->assertNull($d->fresh()->gecerlilik_sonu);
    }

    public function test_yillik_plan_ait_oldugu_yilin_sonunda_biter_bu_yilinki_beklenir(): void
    {
        $eski = $this->kayit('yillik_calisma_plani', ['yil' => 2025, 'baslangic_tarihi' => '2025-01-10']);
        $this->assertSame('2025-12-31', $eski->gecerlilik_sonu->toDateString());
        $s = $this->durum('yillik_calisma_plani');
        $this->assertSame('eksik', $s['durum']);
        $this->assertSame('2026 Çalışma Planı', $s['baslik']);

        $this->kayit('yillik_calisma_plani', ['yil' => 2027]);
        $this->assertSame('eksik', $this->durum('yillik_calisma_plani')['durum']);
        $this->kayit('yillik_calisma_plani', ['yil' => 2026, 'baslangic_tarihi' => '2026-10-03']);
        $this->assertSame('tamam', $this->durum('yillik_calisma_plani')['durum']);
    }

    public function test_yillik_rapor_31_ocaktan_sonra_gecikmis(): void
    {
        Carbon::setTestNow('2026-01-20');
        $this->assertSame('yaklasan', $this->durum('yillik_degerlendirme')['durum']);

        Carbon::setTestNow('2026-10-04');
        $s = $this->durum('yillik_degerlendirme');
        $this->assertSame('gecikmis', $s['durum']);
        $this->assertSame('2025 Değerlendirme Raporu', $s['baslik']);

        $this->kayit('yillik_degerlendirme', ['yil' => 2025]);
        $this->assertSame('tamam', $this->durum('yillik_degerlendirme')['durum']);
    }

    public function test_periyodik_tatbikat_ve_tehlike_sinifina_gore_risk(): void
    {
        $this->assertSame('gecikmis', $this->durum('tatbikat')['durum']);
        $this->assertSame('eksik', $this->durum('risk_degerlendirmesi')['durum']);

        $t = $this->kayit('tatbikat', ['baslangic_tarihi' => '2025-10-20']);
        $this->assertSame('2026-10-20', $t->gecerlilik_sonu->toDateString());
        $this->assertSame('yaklasan', $this->durum('tatbikat')['durum']);

        $t->update(['baslangic_tarihi' => '2025-09-01']);
        $this->assertSame('gecikmis', $this->durum('tatbikat')['durum']);

        $r = $this->kayit('risk_degerlendirmesi', ['baslangic_tarihi' => '2025-03-01']);   // çok tehlikeli: 2 yıl
        $this->assertSame('2027-03-01', $r->gecerlilik_sonu->toDateString());
        $this->assertSame('tamam', $this->durum('risk_degerlendirmesi')['durum']);
    }

    public function test_kurul_elli_calisandan_azsa_gerekmez_imza_bekleyen_sayilmaz_haric_takipsiz(): void
    {
        $this->assertSame('muaf', $this->durum('kurul_tutanagi')['durum']);

        $this->kayit('igu_sozlesmesi', ['asama' => ArsivDosya::IMZA_BEKLIYOR]);
        $this->assertSame('eksik', $this->durum('igu_sozlesmesi')['durum']);

        KullaniciAyarlari::kaydet($this->uzman, ['arsiv' => ['yaklasan_gun' => 30, 'haric' => ['igu_sozlesmesi']]]);
        $this->assertSame('takipsiz', $this->durum('igu_sozlesmesi', null)['durum']);
    }

    public function test_kayit_kategorisinde_elle_girilen_bitis_takip_edilir(): void
    {
        $this->assertSame('takipsiz', $this->durum('periyodik_kontrol')['durum']);
        $this->kayit('periyodik_kontrol', ['baslangic_tarihi' => '2025-09-01', 'gecerlilik_sonu' => '2026-09-01']);
        $this->assertSame('gecikmis', $this->durum('periyodik_kontrol')['durum']);
        $this->assertSame('2026-09-01', ArsivDosya::sole()->gecerlilik_sonu->toDateString());
    }

    /*
    | Sayfa — genel bakış
    */

    public function test_genel_bakis_kartlar_uyarilar_ve_sekmeler(): void
    {
        $this->kayit('yillik_calisma_plani', ['yil' => 2026, 'baslik' => '2026 Çalışma Planı']);

        $sayfa = Livewire::test(DokumanYonetimi::class)
            ->assertSee('OSGB Arşiv Evrakları')
            ->assertSee('İş Güvenliği Sözleşmesi')
            ->assertSee('Diğer Belgeler')
            ->assertSee('süresi geçmiş')
            ->assertSee('1 firmada çalışan listesi eksik');

        $kartlar = $sayfa->instance()->kartlar;
        $this->assertSame(1, $kartlar['yillik_calisma_plani']['kayit']);
        $this->assertSame(1, $kartlar['yillik_degerlendirme']['gecikmis']);
        $this->assertSame(0, $kartlar['tatbikat']['gecikmis']);   // OSGB grubu dışı → varsayılan takip dışı
        $this->assertSame(1, $kartlar['igu_sozlesmesi']['eksik']);

        Calisan::factory()->for($this->firma)->create(['aktif' => true]);
        $sayfa->set('firmaId', $this->firma->id)->assertDontSee('çalışan listesi eksik');

        $sorunlar = $sayfa->instance()->sorunlar;
        $this->assertSame('gecikmis', $sorunlar->first()['durum']);
        $this->assertContains('igu_sozlesmesi', $sorunlar->pluck('kategori')->all());

        $sayfa->set('sekme', 'uyum')->assertSee('Ahmet Yapı')
            ->set('sekme', 'liste')->assertSee('2026 Çalışma Planı');

        $sayfa->call('firmaKategoriAc', $this->firma->id, 'egitim_katilim')
            ->assertSet('kategoriAnahtari', 'egitim_katilim')
            ->assertSee('Temel İSG eğitimi çok tehlikelide yılda bir');
    }

    /*
    | Yeni kayıt — yükleme
    */

    public function test_sozlesme_yukleme_kisi_adi_zorunlu_ve_yururluge_girer(): void
    {
        $sayfa = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)->call('kategoriAc', 'igu_sozlesmesi');

        $sayfa->callAction('yeniKayit', [
            'yontem' => 'yukle', 'firma_id' => $this->firma->id, 'kategori' => 'igu_sozlesmesi',
            'dosyalar' => [UploadedFile::fake()->create('sozlesme.pdf', 50, 'application/pdf')],
            'baslangic_tarihi' => '2026-10-03', 'kisi_adi' => '',
        ])->assertHasActionErrors(['kisi_adi']);
        $this->assertDatabaseCount('arsiv_dosyalari', 0);

        $sayfa = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)->call('kategoriAc', 'igu_sozlesmesi');
        $sayfa->callAction('yeniKayit', [
            'yontem' => 'yukle', 'firma_id' => $this->firma->id, 'kategori' => 'igu_sozlesmesi',
            'dosyalar' => [UploadedFile::fake()->create('sozlesme.pdf', 50, 'application/pdf')],
            'baslangic_tarihi' => '2026-10-03', 'kisi_adi' => 'Ayşe Uzman', 'aciklama' => 'Islak imzalı',
        ])->assertHasNoActionErrors();

        $d = ArsivDosya::sole();
        $this->assertSame('İş Güvenliği Sözleşmesi — Ayşe Uzman', $d->baslik);
        $this->assertSame('sozlesme.pdf', $d->dosya_adi);   // özgün ad korunur
        $this->assertSame(ArsivDosya::DOSYADA, $d->asama);
        $this->assertSame('yukleme', $d->kaynak);
        $this->assertStringStartsWith('arsiv/'.$this->firma->id.'/', $d->dosya_yolu);
        Storage::disk('public')->assertExists($d->dosya_yolu);
        $this->assertSame('tamam', $sayfa->instance()->kategoriDetay['durum']['durum']);
    }

    public function test_cok_sayfali_fotograflar_tek_pdfte_birlesir(): void
    {
        Livewire::test(DokumanYonetimi::class)->callAction('yeniKayit', [
            'yontem' => 'yukle', 'firma_id' => $this->firma->id, 'kategori' => 'yillik_egitim_plani', 'yil' => 2026,
            'dosyalar' => [UploadedFile::fake()->image('s1.jpg', 400, 560), UploadedFile::fake()->image('s2.jpg', 400, 560)],
            'baslangic_tarihi' => '2026-10-03',
        ])->assertHasNoActionErrors();

        $d = ArsivDosya::sole();
        $this->assertSame('2026-egitim-plani.pdf', $d->dosya_adi);
        $this->assertStringStartsWith('%PDF', Storage::disk('public')->get($d->dosya_yolu));
        $this->assertSame([], Storage::disk('public')->files('arsiv/gecici'));
        $this->assertSame('2026-12-31', $d->gecerlilik_sonu->toDateString());
    }

    /*
    | Şablondan üret
    */

    public function test_sistem_sablonundan_uretilen_belge_imza_bekler_dosyaya_ekle_ile_resmilesir(): void
    {
        $sayfa = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)->call('kategoriAc', 'yillik_calisma_plani');

        $sayfa->callAction('yeniKayit', [
            'yontem' => 'sablon', 'firma_id' => $this->firma->id, 'kategori' => 'yillik_calisma_plani', 'yil' => 2026,
            'baslangic_tarihi' => '2026-10-03', 'sablon' => 'sistem', 'baslik' => '',
        ])->assertHasNoActionErrors();

        $d = ArsivDosya::sole();
        $this->assertSame(ArsivDosya::IMZA_BEKLIYOR, $d->asama);
        $this->assertSame('sistem', $d->kaynak);
        $this->assertSame('2026 Çalışma Planı', $d->baslik);
        $this->assertStringEndsWith('.xlsx', $d->dosya_adi);
        $this->assertSame('eksik', $sayfa->instance()->kategoriDetay['durum']['durum']);
        $this->assertCount(1, $sayfa->instance()->kategoriDetay['uretilmis']);

        $sayfa->callAction('dosyayaEkle', ['baslangic_tarihi' => '2026-10-04'], ['id' => $d->id])->assertHasNoActionErrors();
        $this->assertSame(ArsivDosya::DOSYADA, $d->fresh()->asama);
        $this->assertSame('tamam', $sayfa->instance()->kategoriDetay['durum']['durum']);

        $d->update(['asama' => ArsivDosya::IMZA_BEKLIYOR]);
        $eski = $d->dosya_yolu;
        $sayfa->callAction('dosyayaEkle', [
            'baslangic_tarihi' => '2026-10-04', 'dosyalar' => [UploadedFile::fake()->create('imzali.pdf', 40, 'application/pdf')],
        ], ['id' => $d->id])->assertHasNoActionErrors();
        Storage::disk('public')->assertMissing($eski);
        $this->assertSame('imzali.pdf', $d->fresh()->dosya_adi);
    }

    public function test_kayda_dayali_sistem_sablonu_kayit_yoksa_uretmez(): void
    {
        Livewire::test(DokumanYonetimi::class)->callAction('yeniKayit', [
            'yontem' => 'sablon', 'firma_id' => $this->firma->id, 'kategori' => 'risk_degerlendirmesi',
            'baslangic_tarihi' => '2026-10-03', 'sablon' => 'sistem',
        ]);

        $this->assertDatabaseCount('arsiv_dosyalari', 0);
    }

    public function test_kullanici_excel_sablonu_yuklenir_alanlar_bulunur_ve_doldurulur(): void
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setCellValue('A1', '{{isyeri.unvan}} - {{kayit.plan_yili}} YILLIK ÇALIŞMA PLANI');
        $s->setCellValue('A2', '{{ hekim.ad }}');
        $s->setCellValue('A3', '{{ozel_not}}');
        $s->setCellValue('A4', '{{erkek_sayisi}}');
        $gecici = tempnam(sys_get_temp_dir(), 'tst').'.xlsx';
        IOFactory::createWriter($kitap, 'Xlsx')->save($gecici);

        $sayfa = Livewire::test(DokumanYonetimi::class)->callAction('sablonlar', [
            'ad' => 'Benim Planım', 'kategori' => 'yillik_calisma_plani',
            'dosya' => UploadedFile::fake()->createWithContent('plan-sablonu.xlsx', (string) file_get_contents($gecici)),
        ])->assertHasNoActionErrors();

        $sablon = BelgeSablonu::sole();
        $this->assertSame('xlsx', $sablon->tur);
        $this->assertSame(['isyeri.unvan', 'kayit.plan_yili', 'hekim.ad', 'ozel_not', 'erkek_sayisi'], $sablon->yer_tutucular);
        $this->assertArrayHasKey('kullanici:'.$sablon->id, $sayfa->instance()->sablonSecenekleri('yillik_calisma_plani'));
        $this->assertArrayNotHasKey('kullanici:'.$sablon->id, $sayfa->instance()->sablonSecenekleri('tatbikat'));

        Calisan::factory()->for($this->firma)->count(2)->create(['aktif' => true, 'cinsiyet' => 'erkek']);

        $sayfa->callAction('yeniKayit', [
            'yontem' => 'sablon', 'firma_id' => $this->firma->id, 'kategori' => 'yillik_calisma_plani', 'yil' => 2026,
            'baslangic_tarihi' => '2026-10-03', 'sablon' => 'kullanici:'.$sablon->id, 'baslik' => 'Plan 2026',
            'degerler' => ['ozel_not' => 'Şantiye dahil', 'hekim__ad' => 'Dr. Can'],
        ])->assertHasNoActionErrors();

        $d = ArsivDosya::sole();
        $this->assertSame('Benim Planım', $d->sablon_adi);
        $this->assertSame('Dr. Can', $d->alanlar['hekim.ad']);
        $dolu = tempnam(sys_get_temp_dir(), 'tst').'.xlsx';
        file_put_contents($dolu, Storage::disk('public')->get($d->dosya_yolu));
        $sayfa2 = IOFactory::load($dolu)->getActiveSheet();
        $this->assertSame('Ahmet Yapı - 2026 YILLIK ÇALIŞMA PLANI', $sayfa2->getCell('A1')->getValue());
        $this->assertSame('Dr. Can', $sayfa2->getCell('A2')->getValue());
        $this->assertSame('Şantiye dahil', $sayfa2->getCell('A3')->getValue());
        $this->assertSame(2, $sayfa2->getCell('A4')->getValue());
        @unlink($dolu);

        $sayfa->call('sablonSil', $sablon->id);
        $this->assertDatabaseCount('belge_sablonlari', 0);
    }

    public function test_word_sablonunda_bolunmus_yer_tutucu_doldurulur(): void
    {
        $docx = tempnam(sys_get_temp_dir(), 'tst').'.docx';
        $zip = new ZipArchive;
        $zip->open($docx, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        // Word'ün "{{isyeri." + "unvan}}" diye iki koşuya böldüğü yer tutucu.
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .'<w:p><w:r><w:t xml:space="preserve">Sayın {{isyeri.</w:t></w:r><w:r><w:rPr><w:b/></w:rPr><w:t>unvan}}</w:t></w:r><w:r><w:t xml:space="preserve">, {{kayit.tarih}} tarihli {{konu}}.</w:t></w:r></w:p>'
            .'</w:body></w:document>');
        $zip->close();

        $this->assertSame(['isyeri.unvan', 'kayit.tarih', 'konu'], BelgeSablonMotoru::yerTutucular($docx, 'docx'));

        $degerler = BelgeSablonMotoru::sistemDegerleri($this->firma, ['tarih' => '2026-10-03']) + ['konu' => 'görevlendirme'];
        $cikti = tempnam(sys_get_temp_dir(), 'tst').'.docx';
        file_put_contents($cikti, BelgeSablonMotoru::doldur($docx, 'docx', $degerler));
        $zip->open($cikti);
        $metin = strip_tags($zip->getFromName('word/document.xml'));
        $zip->close();

        $this->assertSame('Sayın Ahmet Yapı, 03.10.2026 tarihli görevlendirme.', trim($metin));
        @unlink($docx);
        @unlink($cikti);
    }

    public function test_sistem_degerleri_ve_etiketler(): void
    {
        $v = BelgeSablonMotoru::sistemDegerleri($this->firma, ['yil' => 2026]);
        $this->assertSame('Ahmet Yapı', $v['isyeri.unvan']);
        $this->assertSame('Atatürk Cad. 1 Bursa', $v['isyeri.adres']);
        $this->assertSame('Çok Tehlikeli', $v['isyeri.tehlike_sinifi']);
        $this->assertSame('Mehmet Uzman', $v['uzman.ad']);   // İGU atanmamış → hesap sahibi
        $this->assertSame('2026', $v['kayit.plan_yili']);
        $this->assertSame('04.10.2026', $v['bugun']);
        $this->assertSame('İşyeri Unvanı', BelgeSablonMotoru::etiket('isyeri.unvan'));
        $this->assertSame('İşyeri Notu', BelgeSablonMotoru::etiket('işyeri_notu'));
        $this->assertSame('kayit__yil', BelgeSablonMotoru::formAnahtari('kayit.yil'));
    }

    /*
    | Ayarlar, eski işlevler, yetki
    */

    public function test_hatirlatma_ayarlari_kaydedilir(): void
    {
        Livewire::test(DokumanYonetimi::class)->callAction('hatirlatma', [
            'yaklasan_gun' => 45,
            'takip' => ['igu_sozlesmesi', 'tatbikat'],
        ])->assertHasNoActionErrors();

        KullaniciAyarlari::onbellegiTemizle();
        $this->assertSame(45, KullaniciAyarlari::arsivYaklasanGun($this->uzman->fresh()));
        $haric = KullaniciAyarlari::arsivHaric($this->uzman->fresh());
        $this->assertContains('risk_degerlendirmesi', $haric);
        $this->assertNotContains('tatbikat', $haric);
        $this->assertNotContains('diger', $haric);
    }

    public function test_duzelt_pasife_al_filtre_excel_indir_goruntule_sil(): void
    {
        $d = $this->kayit('talimat', ['baslik' => 'Forklift Talimatı', 'gecerlilik_sonu' => '2026-09-01']);
        $eskiArsiv = ArsivDosya::create(['firma_id' => $this->firma->id, 'dosya_adi' => 'eski.pdf', 'dosya_yolu' => 'arsiv/eski.pdf', 'boyut' => 1]);   // Profilim > Arşiv
        $this->assertSame('Diğer', $eskiArsiv->kategoriEtiketi());

        $sayfa = Livewire::test(DokumanYonetimi::class)->set('sekme', 'liste')
            ->assertSee('Forklift Talimatı')->assertSee('eski.pdf')->assertSee('Süresi doldu');

        $sayfa->callAction('duzenle', [
            'firma_id' => $this->firma->id, 'kategori' => 'talimat', 'baslik' => 'Forklift Talimatı (revize)',
            'versiyon' => '1.1', 'baslangic_tarihi' => '2026-10-01', 'gecerlilik_sonu' => '2027-10-01',
        ], ['id' => $d->id])->assertHasNoActionErrors();
        $this->assertSame('1.1', $d->fresh()->versiyon);
        $this->assertSame('2027-10-01', $d->fresh()->gecerlilik_sonu->toDateString());

        $sayfa->call('durumDegistir', $eskiArsiv->id);
        $this->assertFalse($eskiArsiv->fresh()->aktif);

        $sayfa->set('durum', 'pasif');
        $this->assertSame([$eskiArsiv->id], $sayfa->instance()->dokumanlar->pluck('id')->all());
        $sayfa->set('durum', '')->set('arama', 'revize');
        $this->assertSame([$d->id], $sayfa->instance()->dokumanlar->pluck('id')->all());

        $sayfa->call('excelRapor')->assertFileDownloaded('arsiv-raporu.xlsx');
        $sayfa->call('indir', $d->id)->assertFileDownloaded($d->dosya_adi);

        $sayfa->call('goruntule', $d->id)->assertSee('Forklift Talimatı (revize)')->assertSee('Dosyayı Aç');

        $sayfa->call('sil', $d->id);
        $this->assertNull($d->fresh());
        $this->assertNull($sayfa->get('goruntulenenId'));
    }

    public function test_baskasinin_kaydi_ve_sablonu_gorulemez_degistirilemez(): void
    {
        $yabanciUzman = User::factory()->create();
        $yabanciFirma = Firma::factory()->for($yabanciUzman)->create();
        $yabanci = ArsivDosya::create(['firma_id' => $yabanciFirma->id, 'dosya_adi' => 'gizli.pdf', 'dosya_yolu' => 'a/gizli.pdf', 'boyut' => 1, 'baslik' => 'Gizli Doküman']);
        $sablon = BelgeSablonu::create(['user_id' => $yabanciUzman->id, 'ad' => 'Yabancı', 'dosya_adi' => 'a.xlsx', 'dosya_yolu' => 'a.xlsx', 'tur' => 'xlsx']);

        Livewire::test(DokumanYonetimi::class)
            ->set('sekme', 'liste')
            ->assertDontSee('Gizli Doküman')
            ->call('durumDegistir', $yabanci->id)
            ->call('sil', $yabanci->id)
            ->call('goruntule', $yabanci->id)
            ->assertSet('goruntulenenId', null)
            ->call('sablonSil', $sablon->id)
            ->call('firmaKategoriAc', $yabanciFirma->id, 'tatbikat')
            ->assertSet('firmaId', null);

        $this->assertTrue($yabanci->fresh()->aktif);
        $this->assertNotNull($sablon->fresh());
    }

    /*
    | Bildirim Merkezi / İşyeri Durum Merkezi
    */

    public function test_bildirimler_kural_ve_tarih_takibine_gore(): void
    {
        $this->kayit('talimat', ['gecerlilik_sonu' => '2026-10-01']);                           // kayıt: süresi geçti
        $this->kayit('diger', ['gecerlilik_sonu' => '2026-10-14']);                             // kayıt: yaklaşıyor
        $this->kayit('diger', ['gecerlilik_sonu' => '2025-01-01', 'aktif' => false]);          // pasif sayılmaz
        $this->kayit('yillik_calisma_plani', ['yil' => 2025]);                                  // geçen yılın planı "doldu" alarmı vermez
        $this->kayit('tespit_oneri', ['baslangic_tarihi' => '2026-06-01']);                    // periyodik (3 ay): 01.09'da gecikti
        $this->kayit('tatbikat', ['baslangic_tarihi' => '2025-08-01']);                         // OSGB grubu dışı: varsayılan takip dışı
        $this->kayit('talimat', ['gecerlilik_sonu' => '2026-01-01', 'asama' => ArsivDosya::IMZA_BEKLIYOR]);   // imza bekleyen sayılmaz

        BildirimTarayici::tara($this->uzman);
        $a = Bildirim::query()->acik()->pluck('seviye', 'anahtar');
        $f = 'f'.$this->firma->id.':';

        $this->assertSame('kritik', $a[$f.'dokuman:gecti']);
        $this->assertSame('uyari', $a[$f.'dokuman:yakin']);
        $this->assertStringContainsString('1 doküman', Bildirim::where('anahtar', $f.'dokuman:gecti')->value('aciklama'));
        $this->assertSame('kritik', $a[$f.'arsiv:tespit_oneri:gecikmis']);
        $this->assertArrayNotHasKey($f.'arsiv:tatbikat:gecikmis', $a->all());
        $this->assertArrayNotHasKey($f.'arsiv:yillik_calisma_plani:eksik', $a->all());
        $this->assertArrayNotHasKey($f.'arsiv:igu_sozlesmesi:eksik', $a->all());

        $surec = collect(IsyeriDurumu::surecler($this->firma))->firstWhere('surec', 'Dokümanlar (İSG dosyası)');
        $this->assertStringContainsString('Arşiv: 2 aktif doküman, 1 süresi geçmiş', $surec['sonuc']);
        $this->assertCount(2, IsyeriDurumu::takvim($this->firma)->where('kategori', 'Doküman'));
    }

    /*
    | İki grup: OSGB arşiv evrakları (varsayılan takip) + diğer belgeler
    */

    public function test_varsayilan_takip_yalniz_osgb_arsiv_evraklari(): void
    {
        $osgb = collect(config('arsiv.kategoriler'))->where('grup', 'osgb')->keys()->all();
        $this->assertSame([
            'igu_sozlesmesi', 'yillik_calisma_plani', 'yillik_egitim_plani', 'yillik_degerlendirme', 'egitim_katilim',
            'risk_degerlendirmesi', 'tespit_oneri', 'kurul_tutanagi', 'saha_gozlem',
        ], $osgb);

        $haric = KullaniciAyarlari::arsivHaric();
        $this->assertContains('tatbikat', $haric);
        $this->assertContains('hekim_sozlesmesi', $haric);
        $this->assertSame([], array_intersect($osgb, $haric));
        $this->assertSame('takipsiz', $this->durum('tatbikat', null)['durum']);

        // Kullanıcı takip listesini kaydedince (boş dahi olsa) varsayılan değil kayıtlı liste geçerli.
        KullaniciAyarlari::kaydet($this->uzman, ['arsiv' => ['yaklasan_gun' => 30, 'haric' => []]]);
        $this->assertSame([], KullaniciAyarlari::arsivHaric($this->uzman));
        $this->assertSame('gecikmis', $this->durum('tatbikat', null)['durum']);
    }

    public function test_firma_secilince_osgb_evraklari_kontrol_listesi_ve_yukle(): void
    {
        $this->kayit('igu_sozlesmesi', ['baslangic_tarihi' => '2026-01-05', 'baslik' => 'Sözleşme']);

        $sayfa = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)
            ->assertSee('OSGB Arşiv Evrakları')
            ->assertSee('Firmaya imzalatılmış evrakların')
            ->assertSee('Eğitim Katılım Formları')
            ->assertSee('Saha Gözlem Raporları')
            ->assertSee('1 / 8 güncel')            // 9 evrak; kurul 50'den az çalışanda gerekmez
            ->assertSee('Yükle');

        $secenekler = $sayfa->instance()->kategoriSecenekleri();
        $this->assertSame(['OSGB Arşiv Evrakları', 'Diğer Belgeler'], array_keys($secenekler));
        $this->assertSame('İş Güvenliği Sözleşmesi', $secenekler['OSGB Arşiv Evrakları']['igu_sozlesmesi']);

        $sayfa->callAction('yeniKayit', [
            'yontem' => 'yukle', 'firma_id' => $this->firma->id, 'kategori' => 'saha_gozlem',
            'dosyalar' => [UploadedFile::fake()->create('saha-imzali.pdf', 30, 'application/pdf')],
            'baslangic_tarihi' => '2026-10-02',
        ], ['kategori' => 'saha_gozlem', 'firma' => $this->firma->id, 'yontem' => 'yukle'])->assertHasNoActionErrors();

        $this->assertSame('tamam', $sayfa->instance()->matris[$this->firma->id]['saha_gozlem']['durum']);
        $sayfa->assertSee('2 / 8 güncel');
    }

    public function test_defter_ve_saha_raporu_uc_ayda_bir_beklenir(): void
    {
        $d = $this->kayit('saha_gozlem', ['baslangic_tarihi' => '2026-08-01']);   // son 01.11 → 28 gün
        $this->assertSame('2026-11-01', $d->gecerlilik_sonu->toDateString());
        $this->assertSame('yaklasan', $this->durum('saha_gozlem')['durum']);

        $d->update(['baslangic_tarihi' => '2026-09-01']);                           // son 01.12 → 58 gün
        $this->assertSame('tamam', $this->durum('saha_gozlem')['durum']);

        $d->update(['baslangic_tarihi' => '2026-07-01']);                           // son 01.10 → geçti
        $this->assertSame('gecikmis', $this->durum('saha_gozlem')['durum']);
        $this->assertSame(3, ArsivKurali::ay('tespit_oneri', $this->firma));

        $this->assertSame('muaf', $this->durum('kurul_tutanagi')['durum']);
        $this->firma->update(['calisan_sayisi' => 60]);
        $this->assertSame('eksik', $this->durum('kurul_tutanagi')['durum']);
    }

    public function test_kisa_periyotta_yaklasan_esigi_kisalir(): void
    {
        config(['arsiv.kategoriler.tespit_oneri.ay' => 1]);   // aylık periyot: eşik 30 değil 10 gün

        $d = $this->kayit('tespit_oneri', ['baslangic_tarihi' => '2026-09-20']);   // son 20.10 → 16 gün
        $this->assertSame('tamam', $this->durum('tespit_oneri')['durum']);

        $d->update(['baslangic_tarihi' => '2026-09-10']);                            // son 10.10 → 6 gün
        $this->assertSame('yaklasan', $this->durum('tespit_oneri')['durum']);
    }

    public function test_yillik_degerlendirme_formu_sablondan_doldurulur_gorevli_adi_yok(): void
    {
        $this->firma->update(['sgk_sicil_no' => '1234567', 'telefon' => '0224 000 00 00', 'nace_aciklama' => 'Bina inşaatı', 'nace_kodu' => null]);
        Calisan::factory()->for($this->firma)->count(3)->create(['aktif' => true, 'cinsiyet' => 'erkek', 'dogum_tarihi' => '1990-01-01']);
        Talimat::create(['firma_id' => $this->firma->id, 'baslik' => 'Forklift', 'created_at' => '2025-05-01 10:00:00']);

        $kitap = YillikDegerlendirmeFormUretici::doldur($this->firma->fresh(), 2025, Carbon::parse('2026-01-15'));
        $s = $kitap->getActiveSheet();

        $this->assertSame('2025 YILI İSG DEĞERLENDİRME RAPORU', $s->getCell('C1')->getValue());
        $this->assertSame('15.01.2026', $s->getCell('N1')->getValue());
        $this->assertSame('AHMET YAPI', $s->getCell('C6')->getValue());
        $this->assertSame('1234567', $s->getCell('I7')->getValue());
        $this->assertSame('ÇOK TEHLİKELİ', $s->getCell('I8')->getValue());
        $this->assertSame('BİNA İNŞAATI', $s->getCell('C8')->getValue());
        $this->assertSame(3, $s->getCell('E11')->getValue());
        $this->assertSame(3, $s->getCell('O11')->getValue());
        $this->assertSame('İSG KATİP SÖZLEŞME GİRİŞİ (UZMAN+HEKİM+DİĞER SAĞLIK PERSONELİ)', $s->getCell('B16')->getValue());
        $this->assertSame('01.05.2025', $s->getCell('D24')->getValue());
        $this->assertSame('1 TALİMAT OLUŞTURULARAK PERSONELE EĞİTİM OLARAK VERİLDİ.', $s->getCell('K24')->getValue());
        $this->assertSame('ÇALIŞAN TEMSİLCİSİ SEÇİMİ YAPILDI.', $s->getCell('K19')->getValue());   // örnek kişi adı yok
        // Görevli (İGU / hekim / işveren) adı basılmaz.
        foreach (['C7', 'E12', 'H12', 'B36', 'D36', 'J36'] as $h) {
            $this->assertEmpty($s->getCell($h)->getValue(), $h.' boş olmalı');
        }
        $this->assertStringNotContainsString('Mehmet Uzman', json_encode($s->toArray(), JSON_UNESCAPED_UNICODE));

        // Arşivde "Şablondan Üret → Sistem" de bu formu kullanır.
        Livewire::test(DokumanYonetimi::class)->callAction('yeniKayit', [
            'yontem' => 'sablon', 'firma_id' => $this->firma->id, 'kategori' => 'yillik_degerlendirme', 'yil' => 2025,
            'baslangic_tarihi' => '2026-01-15', 'sablon' => 'sistem',
        ])->assertHasNoActionErrors();
        $d = ArsivDosya::sole();
        $this->assertSame('2025-yillik-degerlendirme-raporu-ahmet-yapi.xlsx', $d->dosya_adi);
        $gecici = tempnam(sys_get_temp_dir(), 'tst').'.xlsx';
        file_put_contents($gecici, Storage::disk('public')->get($d->dosya_yolu));
        $this->assertSame('2025 YILI İSG DEĞERLENDİRME RAPORU', IOFactory::load($gecici)->getActiveSheet()->getCell('C1')->getValue());
        @unlink($gecici);
    }

    public function test_egitim_katilim_ve_saha_gozlem_sistem_sablonu_kayit_yoksa_uyarir(): void
    {
        $sayfa = Livewire::test(DokumanYonetimi::class);
        $this->assertArrayHasKey('sistem', $sayfa->instance()->sablonSecenekleri('egitim_katilim'));
        $this->assertArrayHasKey('sistem', $sayfa->instance()->sablonSecenekleri('saha_gozlem'));

        foreach (['egitim_katilim', 'saha_gozlem'] as $k) {
            $sonuc = ArsivUretici::uret($k, $this->firma, null, '2026-10-03');
            $this->assertIsString($sonuc);
        }
    }

    /*
    | Örnek form yükle → birebir aynısı üretilir / hazır Excel'i indir → düzelt → yükle
    */

    /** Yer tutucusuz, başka firmanın verisiyle dolu örnek form (FirstİSG düzeni). */
    private function ornekForm(): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setCellValue('C1', 'ESKİ FİRMA LTD.');
        $s->setCellValue('C2', '2026 - YILLIK EĞİTİM PLANI');
        $s->setCellValue('A3', 'İŞ YERİ ÜNVANI');
        $s->mergeCells('A3:B3');
        $s->setCellValue('C3', 'ESKİ FİRMA LTD.');
        $s->setCellValue('E3', 'ÇALIŞMA YILI');
        $s->setCellValue('F3', 2026);
        $s->setCellValue('A4', 'ADRESİ');
        $s->setCellValue('B4', 'Eski adres');
        $s->setCellValue('E4', 'İŞ GÜVENLİĞİ UZMANI');
        $s->setCellValue('F4', 'Eski Uzman Adı');
        $s->setCellValue('A5', 'SGK Sicil No:');
        $s->setCellValue('B5', '1111111111111');
        $s->setCellValue('A7', 'Planlanan Tarih:');
        $s->setCellValue('B7', 1);
        $s->setCellValue('C7', 8);
        $s->setCellValue('D7', 2026);
        $s->setCellValue('A8', 'Eğitim konusu');
        $s->setCellValue('B8', '01.08.2026 tarihinde KKD eğitimi');
        $s->setCellValue('A20', '=F4');            // imza altı: uzman adı (formül)
        $s->setCellValue('A21', '=A20');           // zincir
        $s->setCellValue('B20', '=E4');            // rol etiketi — kalır
        $yol = tempnam(sys_get_temp_dir(), 'orn').'.xlsx';
        IOFactory::createWriter($kitap, 'Xlsx')->save($yol);

        return $yol;
    }

    public function test_ornek_form_yuklenir_kunye_taninir_ve_birebir_uretilir(): void
    {
        $this->firma->update(['sgk_sicil_no' => '2410101099999']);
        $ornek = $this->ornekForm();
        $this->assertEqualsCanonicalizing(['isyeri.unvan', 'kayit.yil', 'isyeri.adres', 'gorevli_adi', 'isyeri.sgk_sicil'], EtiketliSablon::bul($ornek));

        $sayfa = Livewire::test(DokumanYonetimi::class)->set('firmaId', $this->firma->id)
            ->assertSee('Örnek formumu yükle')
            ->assertSee("Hazır Excel'i indir", false);

        $sayfa->mountAction('sablonlar', ['kategori' => 'yillik_egitim_plani'])
            ->assertActionDataSet(['kategori' => 'yillik_egitim_plani', 'ad' => 'Kendi Yıllık Eğitim Planı formum']);
        $sayfa->setActionData(['dosya' => UploadedFile::fake()->createWithContent('egitim-plani.xlsx', (string) file_get_contents($ornek))])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $sablon = BelgeSablonu::sole();
        $this->assertSame([], $sablon->yer_tutucular);
        $this->assertSame('kullanici:'.$sablon->id, array_key_first($sayfa->instance()->sablonSecenekleri('yillik_egitim_plani')));   // kendi formu varsayılan
        $sayfa->assertSee('Formumdan üret');

        $sayfa->callAction('yeniKayit', [
            'yontem' => 'sablon', 'firma_id' => $this->firma->id, 'kategori' => 'yillik_egitim_plani', 'yil' => 2027,
            'baslangic_tarihi' => '2026-12-20', 'sablon' => 'kullanici:'.$sablon->id,
        ])->assertHasNoActionErrors();

        $d = ArsivDosya::sole();
        $gecici = tempnam(sys_get_temp_dir(), 'tst').'.xlsx';
        file_put_contents($gecici, Storage::disk('public')->get($d->dosya_yolu));
        $s = IOFactory::load($gecici)->getActiveSheet();

        $this->assertSame('Ahmet Yapı', $s->getCell('C1')->getValue());                     // başlıktaki eski unvan
        $this->assertSame('2027 - YILLIK EĞİTİM PLANI', $s->getCell('C2')->getValue());
        $this->assertSame('Ahmet Yapı', $s->getCell('C3')->getValue());                     // birleştirilmiş etiketin sağı
        $this->assertSame(2027, $s->getCell('F3')->getValue());
        $this->assertSame('Atatürk Cad. 1 Bursa', $s->getCell('B4')->getValue());
        $this->assertSame('2410101099999', $s->getCell('B5')->getValue());                  // uzun numara metin kalır
        $this->assertNull($s->getCell('F4')->getValue());                                  // görevli adı basılmaz
        $this->assertNull($s->getCell('A20')->getValue());                                 // ada bağlı formül
        $this->assertNull($s->getCell('A21')->getValue());                                 // zincir
        $this->assertSame('=E4', $s->getCell('B20')->getValue());                          // rol etiketi formülü kalır
        $this->assertSame(2027, $s->getCell('D7')->getValue());                            // gövdedeki yıl
        $this->assertSame('01.08.2027 tarihinde KKD eğitimi', $s->getCell('B8')->getValue());
        $this->assertSame(8, $s->getCell('C7')->getValue());                               // diğer sayılar aynen
        $this->assertSame('Eğitim konusu', $s->getCell('A8')->getValue());
        @unlink($gecici);
        @unlink($ornek);
    }

    public function test_hazir_excel_indirilir_duzeltilip_dosyaya_eklenir(): void
    {
        $sayfa = Livewire::test(DokumanYonetimi::class);
        $sayfa->call('hazirIndir', 'yillik_calisma_plani');                                  // firma seçilmeden indirilmez
        $this->assertDatabaseCount('arsiv_dosyalari', 0);

        $sayfa->set('firmaId', $this->firma->id)
            ->call('hazirIndir', 'yillik_calisma_plani')
            ->assertFileDownloaded();
        $this->assertDatabaseCount('arsiv_dosyalari', 0);                                   // indirmek kayıt açmaz

        // Düzeltilmiş Excel "Yükle" ile arşive girer…
        $sayfa->callAction('yeniKayit', [
            'yontem' => 'yukle', 'firma_id' => $this->firma->id, 'kategori' => 'yillik_calisma_plani', 'yil' => 2026,
            'dosyalar' => [UploadedFile::fake()->create('calisma-plani-duzeltilmis.xlsx', 30, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')],
            'baslangic_tarihi' => '2026-10-04',
        ])->assertHasNoActionErrors();
        $this->assertSame('calisma-plani-duzeltilmis.xlsx', ArsivDosya::sole()->dosya_adi);

        // …ya da üretilen belge "Dosyaya ekle"de düzeltilmiş Excel ile değiştirilir.
        $d = $this->kayit('yillik_egitim_plani', ['yil' => 2026, 'asama' => ArsivDosya::IMZA_BEKLIYOR]);
        $sayfa->callAction('dosyayaEkle', [
            'baslangic_tarihi' => '2026-10-04',
            'dosyalar' => [UploadedFile::fake()->create('egitim-duzeltilmis.xlsx', 30, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')],
        ], ['id' => $d->id])->assertHasNoActionErrors();
        $this->assertSame('egitim-duzeltilmis.xlsx', $d->fresh()->dosya_adi);
        $this->assertSame(ArsivDosya::DOSYADA, $d->fresh()->asama);
    }
}
