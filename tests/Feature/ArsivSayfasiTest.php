<?php

namespace Tests\Feature;

use App\Filament\Pages\DokumanYonetimi;
use App\Models\ArsivDosya;
use App\Models\BelgeSablonu;
use App\Models\Bildirim;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\ArsivKurali;
use App\Support\BelgeSablonMotoru;
use App\Support\BildirimTarayici;
use App\Support\IsyeriDurumu;
use App\Support\KullaniciAyarlari;
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

    private function durum(string $kategori): array
    {
        $kayitlar = ArsivDosya::where('firma_id', $this->firma->id)->get()
            ->filter(fn ($d) => ArsivKurali::kategori($d->kategori)['anahtar'] === $kategori);

        return ArsivKurali::durum($this->firma, $kategori, $kayitlar, null, KullaniciAyarlari::arsivHaric());
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
        $this->assertSame('takipsiz', $this->durum('igu_sozlesmesi')['durum']);
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
            ->assertSee('Sözleşmeler')
            ->assertSee('İSG Uzmanı Sözleşmesi')
            ->assertSee('Risk ve Acil Durum')
            ->assertSee('süresi geçmiş')
            ->assertSee('1 firmada çalışan listesi eksik');

        $kartlar = $sayfa->instance()->kartlar;
        $this->assertSame(1, $kartlar['yillik_calisma_plani']['kayit']);
        $this->assertSame(1, $kartlar['tatbikat']['gecikmis']);
        $this->assertSame(1, $kartlar['igu_sozlesmesi']['eksik']);

        Calisan::factory()->for($this->firma)->create(['aktif' => true]);
        $sayfa->set('firmaId', $this->firma->id)->assertDontSee('çalışan listesi eksik');

        $sorunlar = $sayfa->instance()->sorunlar;
        $this->assertSame('gecikmis', $sorunlar->first()['durum']);
        $this->assertContains('igu_sozlesmesi', $sorunlar->pluck('kategori')->all());

        $sayfa->set('sekme', 'uyum')->assertSee('Ahmet Yapı')
            ->set('sekme', 'liste')->assertSee('2026 Çalışma Planı');

        $sayfa->call('firmaKategoriAc', $this->firma->id, 'tatbikat')
            ->assertSet('kategoriAnahtari', 'tatbikat')
            ->assertSee('Tatbikat en az yılda bir yapılır');
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
        $this->assertSame('İSG Uzmanı Sözleşmesi — Ayşe Uzman', $d->baslik);
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
        $this->kayit('tatbikat', ['baslangic_tarihi' => '2025-08-01']);                         // periyodik: gecikti
        $this->kayit('talimat', ['gecerlilik_sonu' => '2026-01-01', 'asama' => ArsivDosya::IMZA_BEKLIYOR]);   // imza bekleyen sayılmaz

        BildirimTarayici::tara($this->uzman);
        $a = Bildirim::query()->acik()->pluck('seviye', 'anahtar');
        $f = 'f'.$this->firma->id.':';

        $this->assertSame('kritik', $a[$f.'dokuman:gecti']);
        $this->assertSame('uyari', $a[$f.'dokuman:yakin']);
        $this->assertStringContainsString('1 doküman', Bildirim::where('anahtar', $f.'dokuman:gecti')->value('aciklama'));
        $this->assertSame('kritik', $a[$f.'arsiv:tatbikat:gecikmis']);
        $this->assertArrayNotHasKey($f.'arsiv:yillik_calisma_plani:eksik', $a->all());
        $this->assertArrayNotHasKey($f.'arsiv:igu_sozlesmesi:eksik', $a->all());

        $surec = collect(IsyeriDurumu::surecler($this->firma))->firstWhere('surec', 'Dokümanlar (İSG dosyası)');
        $this->assertStringContainsString('Arşiv: 2 aktif doküman, 1 süresi geçmiş', $surec['sonuc']);
        $this->assertCount(2, IsyeriDurumu::takvim($this->firma)->where('kategori', 'Doküman'));
    }
}
