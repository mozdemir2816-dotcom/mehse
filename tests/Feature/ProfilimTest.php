<?php

namespace Tests\Feature;

use App\Filament\Pages\Profilim;
use App\Models\Calisan;
use App\Models\EgitimKaydi;
use App\Models\EgitimTuru;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\RiskSablonu;
use App\Models\User;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProfilimTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_profil_ozeti_sayaclari_hesaplar(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 3]);
        Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli', 'calisan_sayisi' => 7]);
        Firma::factory()->create(); // başka uzman

        Calisan::create(['firma_id' => $a->id, 'ad_soyad' => 'A', 'aktif' => true]);
        Calisan::create(['firma_id' => $a->id, 'ad_soyad' => 'B', 'aktif' => false]);

        $rd = RiskDegerlendirmesi::create(['firma_id' => $a->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        // önemli risk: puan > eşik (140)
        $rd->maddeler()->create(['tehlike' => 'Ağır', 'olasilik' => 10, 'frekans' => 6, 'siddet' => 7]); // 420
        $rd->maddeler()->create(['tehlike' => 'Hafif', 'olasilik' => 1, 'frekans' => 1, 'siddet' => 1]); // 1

        RiskSablonu::olustur($this->uzman, 'S', 'ofis', null, 'matris_5x5', [['anahtar' => 'x', 'tehlike' => 't']]);

        $o = PortfoyKarne::profilOzeti($this->uzman->id);

        $this->assertSame(2, $o['firma']);
        $this->assertSame(10, $o['calisan']); // firmaların bildirdiği toplam (3+7), Çalışan kaydı sayısı değil
        $this->assertSame(1, $o['risk_degerlendirmesi']);
        $this->assertSame(1, $o['risk_sablonu']);
        $this->assertSame(1, $o['onemli_risk']);
        $this->assertSame(1, $o['tehlike_dagilimi']['Çok Tehlikeli']);
        $this->assertSame(1, $o['tehlike_dagilimi']['Az Tehlikeli']);
    }

    public function test_ilkyardimci_ihtiyaci_tehlike_sinifina_gore_hesaplanir(): void
    {
        // çok tehlikeli: her 10 çalışana 1 -> ceil(3/10)=1 ; az tehlikeli: her 20'ye 1 -> ceil(7/20)=1
        Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 3]);
        Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli', 'calisan_sayisi' => 7]);
        Firma::factory()->create(['tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 100]); // başka uzman

        $this->assertSame(2, PortfoyKarne::ilkyardimciIhtiyaci($this->uzman->id));
    }

    public function test_performans_eksenleri_yalniz_hazir_kriterleri_ortalar(): void
    {
        $a = Firma::factory()->for($this->uzman)->create();
        Firma::factory()->for($this->uzman)->create(); // risk değerlendirmesi yok

        RiskDegerlendirmesi::create(['firma_id' => $a->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $eksenler = PortfoyKarne::performansEksenleri($this->uzman->id);

        $this->assertEqualsCanonicalizing(
            ['Evrak Uyumu', 'Çalışan Kapsamı', 'Eğitim Durumu', 'Risk Yönetimi', 'Acil Durum'],
            array_keys($eksenler),
        );
        // 2 firmadan 1'inde risk değerlendirmesi var (%50), saha denetimi hiçbirinde yok (%0) -> ortalama %25
        $this->assertSame(25, $eksenler['Risk Yönetimi']);
        foreach ($eksenler as $yuzde) {
            $this->assertGreaterThanOrEqual(0, $yuzde);
            $this->assertLessThanOrEqual(100, $yuzde);
        }
    }

    public function test_firma_kriter_matrisi_risk_degerlendirmesini_isaretler(): void
    {
        // calisan_sayisi < 50 sabitlenir: isg_kurulu kriteri muaf olur (deterministik oran).
        $a = Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 10]);
        $b = Firma::factory()->for($this->uzman)->create(['calisan_sayisi' => 10]);
        RiskDegerlendirmesi::create(['firma_id' => $a->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        $matris = collect(PortfoyKarne::firmaKriterMatrisi($this->uzman->id))->keyBy(fn ($s) => $s['firma']->id);

        $this->assertTrue($matris[$a->id]['hucreler']['risk_degerlendirmesi']);
        $this->assertFalse($matris[$b->id]['hucreler']['risk_degerlendirmesi']);
        // 50 altı çalışan olduğu için isg_kurulu muaf — kalan hazır kriter sayısı 19;
        // firma A yalnız risk değerlendirmesini karşılıyor: round(1/19*100) = 5.
        $this->assertSame(5, $matris[$a->id]['oran']);
        $this->assertSame(0, $matris[$b->id]['oran']);
    }

    public function test_firma_checklist_detay_vade_tarihine_gore_durum_hesaplar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);

        \App\Models\FirmaChecklistVadesi::create([
            'firma_id' => $firma->id, 'kriter_anahtari' => 'egitim_katilim_formu', 'vade_tarihi' => now()->addDays(10),
        ]);
        \App\Models\FirmaChecklistVadesi::create([
            'firma_id' => $firma->id, 'kriter_anahtari' => 'tespit_oneri', 'vade_tarihi' => now()->subDays(5),
        ]);

        $detay = collect(PortfoyKarne::firmaChecklistDetay($firma->fresh()))->keyBy('anahtar');

        $this->assertSame('tamamlandi', $detay['risk_degerlendirmesi']['durum']);
        $this->assertSame('yakin', $detay['egitim_katilim_formu']['durum']);
        $this->assertSame('eksik', $detay['tespit_oneri']['durum']);
        $this->assertSame('eksik', $detay['saglik_raporu']['durum']); // hiç vade girilmemiş
    }

    public function test_firma_checklist_vade_guncelle_action_kaydeder_ve_kaldirir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $tarih = now()->addDays(15)->toDateString();

        Livewire::test(Profilim::class)
            ->set('firmaTakipSeciliId', $firma->id)
            ->call('firmaChecklistVadeGuncelle', 'saglik_raporu', $tarih);

        $this->assertSame(
            $tarih,
            \App\Models\FirmaChecklistVadesi::where('firma_id', $firma->id)->where('kriter_anahtari', 'saglik_raporu')->first()->vade_tarihi->toDateString(),
        );

        Livewire::test(Profilim::class)
            ->set('firmaTakipSeciliId', $firma->id)
            ->call('firmaChecklistVadeGuncelle', 'saglik_raporu', '');

        $this->assertNull(
            \App\Models\FirmaChecklistVadesi::where('firma_id', $firma->id)->where('kriter_anahtari', 'saglik_raporu')->first()->vade_tarihi,
        );
    }

    public function test_acil_durum_plani_konulari_bosken_kriter_karsilanmaz_dolunca_karsilanir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($firma, 'acil_durum_plani'));

        \App\Models\AcilDurumPlani::create(['firma_id' => $firma->id, 'konular' => ['yangin', 'deprem']]);
        $firma->refresh();

        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($firma, 'acil_durum_plani'));
    }

    public function test_eksik_firmalar_kriteri_karsilamayanlari_doner(): void
    {
        $tamam = Firma::factory()->for($this->uzman)->create(['unvan' => 'Tamamlayan Firma']);
        $eksik = Firma::factory()->for($this->uzman)->create(['unvan' => 'Eksik Firma']);
        \App\Models\AcilDurumPlani::create(['firma_id' => $tamam->id, 'konular' => ['yangin']]);

        $eksikFirmalar = PortfoyKarne::eksikFirmalar($this->uzman->id, 'acil_durum_plani');

        $this->assertCount(1, $eksikFirmalar);
        $this->assertSame('Eksik Firma', $eksikFirmalar->first()->unvan);
    }

    public function test_sayfa_sekmeli_acilir(): void
    {
        Firma::factory()->for($this->uzman)->create();

        Livewire::test(Profilim::class)
            ->assertOk()
            ->assertSet('sekme', 'genel')
            ->call('sekmeSec', 'firmalar')->assertSet('sekme', 'firmalar')
            ->call('sekmeSec', 'firma_takip')->assertSet('sekme', 'firma_takip')
            ->call('sekmeSec', 'calisanlar')->assertSet('sekme', 'calisanlar')
            ->call('sekmeSec', 'egitimler')->assertSet('sekme', 'egitimler')
            ->call('sekmeSec', 'risklerim')->assertSet('sekme', 'risklerim')
            ->call('sekmeSec', 'pazarlama')->assertSet('sekme', 'pazarlama')
            ->call('sekmeSec', 'arsiv')->assertSet('sekme', 'arsiv')
            ->call('sekmeSec', 'raporlar')->assertSet('sekme', 'raporlar')
            ->call('sekmeSec', 'firma_ziyaretleri')->assertSet('sekme', 'firma_ziyaretleri')
            ->call('sekmeSec', 'yok')->assertSet('sekme', 'firma_ziyaretleri');
    }

    public function test_unvan_ayari_action_kullaniciyi_gunceller(): void
    {
        Livewire::test(Profilim::class)
            ->callAction('unvanAyari', [
                'unvan' => 'a_sinifi',
                'telefon' => '0555 111 22 33',
                'sertifika_no' => 'ABC-123',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('a_sinifi', $this->uzman->fresh()->unvan);
        $this->assertSame('0555 111 22 33', $this->uzman->fresh()->telefon);
    }

    public function test_kase_bilgisi_action_gorseli_kaydeder(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        Livewire::test(Profilim::class)
            ->callAction('kaseBilgisi', [
                'kase_gorseli' => \Illuminate\Http\Testing\File::image('kase.png'),
            ])
            ->assertHasNoActionErrors();

        $this->assertNotNull($this->uzman->fresh()->kase_gorseli);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($this->uzman->fresh()->kase_gorseli);
    }

    public function test_calisan_dagilimi_ve_aktivite_hesaplanir(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firma']);
        $b = Firma::factory()->for($this->uzman)->create(['unvan' => 'B Firma']);
        Calisan::create(['firma_id' => $a->id, 'ad_soyad' => 'x', 'aktif' => true]);
        Calisan::create(['firma_id' => $a->id, 'ad_soyad' => 'y', 'aktif' => true]);

        $dagilim = PortfoyKarne::calisanDagilimi($this->uzman->id);
        $this->assertSame(2, $dagilim['A Firma']);
        $this->assertSame(0, $dagilim['B Firma']);

        $aktivite = PortfoyKarne::aktiviteGunluk($this->uzman->id, 90);
        $this->assertCount(90, $aktivite);
        $this->assertGreaterThanOrEqual(2, $aktivite[now()->toDateString()]); // bugün 2 firma + 2 çalışan
    }

    public function test_calisanlar_yalniz_kendi_firmalarindan_gelir(): void
    {
        $benim = Firma::factory()->for($this->uzman)->create();
        $baskasi = Firma::factory()->create();
        Calisan::create(['firma_id' => $benim->id, 'ad_soyad' => 'Benimki', 'aktif' => true]);
        Calisan::create(['firma_id' => $baskasi->id, 'ad_soyad' => 'Yabancı', 'aktif' => true]);

        $liste = Livewire::test(Profilim::class)->instance()->calisanlar();

        $this->assertCount(1, $liste);
        $this->assertSame('Benimki', $liste->first()->ad_soyad);
    }

    public function test_egitim_turleri_varsayilan_olarak_tek_maddedir(): void
    {
        $turler = Livewire::test(Profilim::class)->instance()->egitimTurleri();

        $this->assertCount(1, $turler);
        $this->assertSame('is_sagligi_guvenligi_egitimi', $turler->first()->anahtar);
        $this->assertSame('Temel İş Sağlığı ve Güvenliği Eğitimi', $turler->first()->ad);
    }

    public function test_egitim_matrisi_varsayilan_ve_sonradan_eklenen_konuyu_dondurur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);
        $calisan = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Test Çalışan', 'aktif' => true]);

        EgitimTuru::aktifListe($this->uzman->id); // varsayılan "Temel İSG" satırını oluşturur
        EgitimTuru::create([
            'user_id' => $this->uzman->id, 'anahtar' => 'ilkyardim_temel',
            'ad' => 'İlkyardım (Temel Bilgilendirme)', 'gecerlilik_ay' => 36, 'sira' => 1,
        ]);
        EgitimKaydi::create(['calisan_id' => $calisan->id, 'tur' => 'ilkyardim_temel', 'tarih' => now()->subMonth()]);

        $matris = collect(Livewire::test(Profilim::class)->instance()->egitimMatrisi())->keyBy(fn ($s) => $s['calisan']->id);

        $this->assertNull($matris[$calisan->id]['hucreler']['is_sagligi_guvenligi_egitimi']['tarih']);
        $this->assertSame('gecerli', $matris[$calisan->id]['hucreler']['ilkyardim_temel']['durum']);
    }

    public function test_egitim_matrisi_aktif_isten_ayrilan_sekmesine_gore_filtrelenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Aktif Çalışan', 'aktif' => true]);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ayrılan Çalışan', 'aktif' => false]);

        $aktifIsimler = collect(Livewire::test(Profilim::class)->instance()->egitimMatrisi())
            ->pluck('calisan.ad_soyad');
        $this->assertSame(['Aktif Çalışan'], $aktifIsimler->all());

        $ayrilanIsimler = collect(Livewire::test(Profilim::class)
            ->set('egitimDurumTab', 'ayrilan')
            ->instance()
            ->egitimMatrisi())
            ->pluck('calisan.ad_soyad');
        $this->assertSame(['Ayrılan Çalışan'], $ayrilanIsimler->all());
    }

    public function test_egitim_matrisi_arama_ve_firma_filtresi(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firma']);
        $b = Firma::factory()->for($this->uzman)->create(['unvan' => 'B Firma']);
        Calisan::create(['firma_id' => $a->id, 'ad_soyad' => 'Ahmet Yılmaz', 'aktif' => true]);
        Calisan::create(['firma_id' => $b->id, 'ad_soyad' => 'Mehmet Kaya', 'aktif' => true]);

        $component = Livewire::test(Profilim::class)->set('egitimArama', 'Ahmet');
        $this->assertCount(1, $component->instance()->egitimMatrisi());

        $component = Livewire::test(Profilim::class)->set('egitimFirmaId', $b->id);
        $isimler = collect($component->instance()->egitimMatrisi())->pluck('calisan.ad_soyad');
        $this->assertSame(['Mehmet Kaya'], $isimler->all());
    }

    public function test_egitim_kategori_filtresi_yalniz_eslesen_sutunlari_gosterir(): void
    {
        EgitimTuru::aktifListe($this->uzman->id); // genel kategoride "Temel İSG"
        EgitimTuru::create([
            'user_id' => $this->uzman->id, 'anahtar' => 'yuksekte_calisma',
            'ad' => 'Yüksekte Çalışma', 'gecerlilik_ay' => 12, 'sira' => 1,
        ]);

        $component = Livewire::test(Profilim::class)->set('egitimKategoriFiltre', 'risk_bazli');

        $gorunen = collect($component->instance()->egitimTurleriGorunen())->pluck('anahtar');
        $this->assertSame(['yuksekte_calisma'], $gorunen->all());
    }

    public function test_egitim_durum_filtresi_yalniz_eslesen_calisanlari_gosterir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);
        $gecerli = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Geçerli Çalışan', 'aktif' => true]);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Eksik Çalışan', 'aktif' => true]);

        EgitimTuru::aktifListe($this->uzman->id);
        EgitimKaydi::create(['calisan_id' => $gecerli->id, 'tur' => 'is_sagligi_guvenligi_egitimi', 'tarih' => now()]);

        $component = Livewire::test(Profilim::class)->set('egitimDurumFiltre', 'eksik');
        $isimler = collect($component->instance()->egitimMatrisi())->pluck('calisan.ad_soyad');
        $this->assertSame(['Eksik Çalışan'], $isimler->all());
    }

    public function test_egitim_ozet_toplam_ve_eksik_sayisini_hesaplar(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'az_tehlikeli']);
        $dolu = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Dolu Çalışan', 'aktif' => true]);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Eksik Çalışan', 'aktif' => true]);

        EgitimTuru::aktifListe($this->uzman->id);
        EgitimKaydi::create(['calisan_id' => $dolu->id, 'tur' => 'is_sagligi_guvenligi_egitimi', 'tarih' => now()]);

        $ozet = Livewire::test(Profilim::class)->instance()->egitimOzet();

        $this->assertSame(2, $ozet['toplam']);
        $this->assertSame(1, $ozet['eksik']);
    }

    public function test_egitim_turu_ekle_action_katalogdan_ekler(): void
    {
        Livewire::test(Profilim::class)
            ->callAction('egitimTuruEkle', [
                'katalog' => 'ilkyardim_temel',
                'gecerlilik_ay' => 36,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('egitim_turleri', [
            'user_id' => $this->uzman->id,
            'anahtar' => 'ilkyardim_temel',
            'ad' => 'İlkyardım (Temel Bilgilendirme)',
        ]);
    }

    public function test_egitim_turu_ekle_action_ozel_konu_ekler(): void
    {
        Livewire::test(Profilim::class)
            ->callAction('egitimTuruEkle', [
                'ozel_ad' => 'Forklift Kullanımı',
                'gecerlilik_ay' => 24,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('egitim_turleri', [
            'user_id' => $this->uzman->id,
            'anahtar' => 'forklift_kullanimi',
            'ad' => 'Forklift Kullanımı',
            'gecerlilik_ay' => 24,
        ]);
    }

    public function test_egitim_turu_kaldir_action_kaldirir(): void
    {
        EgitimTuru::aktifListe($this->uzman->id);

        Livewire::test(Profilim::class)
            ->call('egitimTuruKaldir', 'is_sagligi_guvenligi_egitimi');

        $this->assertDatabaseMissing('egitim_turleri', [
            'user_id' => $this->uzman->id,
            'anahtar' => 'is_sagligi_guvenligi_egitimi',
        ]);
    }

    public function test_egitim_yukle_action_ice_aktarir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek A.Ş.']);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ahmet Yılmaz', 'aktif' => true]);

        $kitap = new Spreadsheet();
        $kitap->getActiveSheet()->fromArray([
            ['Çalışan', 'Firma', 'Temel İş Sağlığı ve Güvenliği Eğitimi'],
            ['Ahmet Yılmaz', 'Örnek A.Ş.', '01.01.2026'],
        ], null, 'A1');
        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        Livewire::test(Profilim::class)
            ->callAction('egitimYukle', [
                'dosya' => File::createWithContent('egitim.xlsx', file_get_contents($yol)),
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('2026-01-01', EgitimKaydi::where('tur', 'is_sagligi_guvenligi_egitimi')->firstOrFail()->tarih->toDateString());

        unlink($yol);
    }
}
