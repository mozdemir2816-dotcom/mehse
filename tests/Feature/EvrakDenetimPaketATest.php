<?php

namespace Tests\Feature;

use App\Models\ArsivDosya;
use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IseDonusBelgesi;
use App\Models\IsgProfesyoneli;
use App\Models\MuayeneFormu;
use App\Models\SaglikGozetimi;
use App\Models\Taseron;
use App\Models\User;
use App\Support\CalisanOzlukZipUretici;
use App\Support\SaglikGuvenlikPlaniUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class EvrakDenetimPaketATest extends TestCase
{
    use RefreshDatabase;

    public function test_sgp_pdf_cokmeden_uretilir_ve_taseron_sutunlari_dogru_basilir(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $igu = IsgProfesyoneli::create(['user_id' => $user->id, 'ad_soyad' => 'Ali İGU', 'tip' => 'igu', 'sertifika_no' => 'IGU-A-101']);
        $firma = Firma::factory()->create([
            'user_id' => $user->id,
            'unvan' => 'Kaya İnşaat A.Ş.',
            'isveren_ad' => 'Ahmet Kaya',
            'igu_id' => $igu->id,
            'katip_no' => 'KTP-999',
        ]);
        Taseron::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'unvan' => 'Demir Elektrik Ltd.',
            'faaliyet' => 'Elektrik Tesisatı',
            'yetkili' => 'Hasan Demir',
            'aktif' => true,
        ]);

        // Modaldan gelmeyen hazirlik_koordinatoru ve baslangic_tarihi olmadan da çalışmalı
        $yanit = SaglikGuvenlikPlaniUretici::pdf($firma, [
            'santiye_adi' => 'Nilüfer Konut Şantiyesi',
            'santiye_adresi' => 'Bursa',
            'proje_muduru' => '',
            'uygulama_koordinatoru' => '',
            'tahmini_sure_gun' => 60,
            'azami_calisan' => 30,
            'toplam_yevmiye' => 900,
        ]);

        ob_start();
        $yanit->sendContent();
        $pdf = (string) ob_get_clean();

        $this->assertStringStartsWith('%PDF-1.', $pdf);
        $this->assertGreaterThan(2000, strlen($pdf));

        $html = view('pdf.saglik-guvenlik-plani', [
            'firma' => $firma->load('igu', 'isyeriHekimi', 'taseronlar'),
            'veri' => [
                'santiye_adi' => 'Nilüfer Konut Şantiyesi',
                'santiye_adresi' => 'Bursa',
                'proje_muduru' => 'Ahmet Kaya',
                'hazirlik_koordinatoru' => 'Ali İGU',
                'uygulama_koordinatoru' => 'Ali İGU',
                'baslangic_tarihi' => '24.11.2026',
                'duzenleme_tarihi' => '24.11.2026',
                'dokuman_no' => 'SGP-2026-001',
                'tahmini_sure_gun' => 60,
                'azami_calisan' => 30,
                'toplam_yevmiye' => 900,
                'taseron_sayisi' => 1,
                'ek3_zorunlu' => true,
                'ozel_kurallar' => null,
            ],
            'fazlar' => [],
            'taseronlar' => $firma->taseronlar,
            'oncelikliRiskler' => collect(),
        ])->render();

        $this->assertStringContainsString('Elektrik Tesisatı', $html);
        $this->assertStringContainsString('Hasan Demir', $html);
        $this->assertStringContainsString('IGU-A-101', $html);
    }

    public function test_calisan_ozluk_zip_benzer_isimli_baska_calisan_dosyasini_sizdirmaz(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('arsiv/ali-can.pdf', '%PDF-1.4 ali can');
        Storage::disk('local')->put('arsiv/ali-caner.pdf', '%PDF-1.4 ali caner GIZLI');

        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id]);
        $aliCan = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ali Can', 'aktif' => true]);

        ArsivDosya::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'kategori' => 'egitim',
            'baslik' => 'Ali Can Sertifika',
            'kisi_adi' => 'Ali Can',
            'dosya_adi' => 'ali-can.pdf',
            'dosya_yolu' => 'arsiv/ali-can.pdf',
        ]);
        ArsivDosya::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'kategori' => 'saglik',
            'baslik' => 'Ali Caner Sağlık',
            'kisi_adi' => 'Ali Caner',
            'dosya_adi' => 'ali-caner.pdf',
            'dosya_yolu' => 'arsiv/ali-caner.pdf',
        ]);

        $yanit = CalisanOzlukZipUretici::indir($aliCan);
        ob_start();
        $yanit->sendContent();
        $zipBytes = (string) ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'test_ozluk_').'.zip';
        file_put_contents($tmp, $zipBytes);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $isimler = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $isimler[] = $zip->getNameIndex($i);
        }
        $ozet = $zip->getFromName('00_Ozluk_Ozeti.txt');
        $zip->close();
        @unlink($tmp);

        $this->assertContains('Belgeler/01_ali-can.pdf', $isimler);
        $this->assertNotContains('Belgeler/02_ali-caner.pdf', $isimler);
        $this->assertStringContainsString('6331 SK m.15/5', (string) $ozet);
    }

    public function test_egitim_katilim_bos_baslik_guvenli_ve_firma_karnesi_katip_no_basar(): void
    {
        $ek = new EgitimKatilim(['baslik_anahtari' => null]);
        $this->assertSame('İş Sağlığı ve Güvenliği', $ek->basliklarEtiketi());

        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'katip_no' => 'KTP-2026-777']);

        $html = view('pdf.isveren-firma-karnesi', [
            'firma' => $firma,
            'calisan' => 10,
            'tarih' => '24.11.2026',
            'ozet' => ['oran' => 80, 'tamam' => 8, 'eksik' => 2, 'muaf' => 0],
            'sureler' => ['igu_dk' => 100, 'hekim_dk' => 50, 'dsp_dk' => 0],
            'kontenjan' => null,
            'firmaEvraklari' => [],
            'calisanEvraklari' => [],
        ])->render();

        $this->assertStringContainsString('KTP-2026-777', $html);
    }

    public function test_tibbi_pdflerde_6331_m15_5_gizliligi_ve_guvenli_varsayilanlar(): void
    {
        // EK-2 varsayılan olarak gece çalışabilir = false olmalı
        $this->assertFalse(MuayeneFormu::ek2Sablon()['kanaat']['gece']);

        $user = User::factory()->create(['rol' => 'sahip']);
        $hekim = IsgProfesyoneli::create(['user_id' => $user->id, 'ad_soyad' => 'Dr. Deniz', 'tip' => 'hekim', 'sertifika_no' => 'IH-555']);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'isyeri_hekimi_id' => $hekim->id, 'isveren_ad' => 'Ali Patron', 'tehlike_sinifi' => 'cok_tehlikeli']);

        // 1) EK-1 Radyasyon boş alanlarda otomatik "Normal / Elverişlidir" basmamalı
        $ek1 = view('pdf.ek1-radyasyon', [
            'firma' => $firma->load('isyeriHekimi'),
            'calisan_ad' => 'Ayşe Y.',
            'calisan_tc' => '10000000146',
            'calisan_gorev' => 'Radyoloji Teknisyeni',
            'tarih' => '24.11.2026',
            'dozimetre_no' => 'DZ-1',
            'hemogram' => '',
            'periferik' => '',
            'goz' => '',
            'cilt' => '',
            'tiroid' => '',
            'kanaat' => '',
            'hekim_adi' => '',
        ])->render();
        $this->assertStringNotContainsString('Normal sınırlar içinde', $ek1);
        $this->assertStringNotContainsString('çalışmaya elverişlidir.', $ek1);
        $this->assertStringContainsString('6331 SK m.15/5', $ek1);

        // 2) Sağlık Gözetimi çizelgesi kan kurşun değerini işveren listesinde ifşa etmemeli
        $sg = SaglikGozetimi::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'satirlar' => [[
                'calisan_adi' => 'Veli K.',
                'gorev' => 'Akü',
                'tetkik_turu' => 'kursun',
                'tarih' => '2026-11-01',
                'sonraki_tarih' => '2027-11-01',
                'sonuc' => 'uygun',
                'kursun_deger' => '42.5',
            ]],
        ]);
        $sgHtml = view('pdf.saglik-gozetimi', [
            'gozetim' => $sg,
            'firma' => $firma->load('isyeriHekimi'),
            'turler' => config('isg.saglik_tetkik.turleri'),
            'sonuclar' => config('isg.saglik_tetkik.sonuclar'),
        ])->render();
        $this->assertStringNotContainsString('42.5', $sgHtml);
        $this->assertStringContainsString('6331 SK m.15/5', $sgHtml);

        // 3) İşe Dönüş belgesinde İşveren tebellüğ imzası ve gizlilik ibaresi olmalı
        $idb = IseDonusBelgesi::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Veli K.',
            'neden' => 'is_kazasi',
            'belge_tarihi' => now()->toDateString(),
        ]);
        $idbHtml = view('pdf.ise-donus-belgesi', ['belge' => $idb, 'firma' => $firma->load('isyeriHekimi'), 'imzali' => true])->render();
        $this->assertStringContainsString('İşveren / İşveren Vekili (Tebellüğ)', $idbHtml);
        $this->assertStringContainsString('6331 SK m.15/5', $idbHtml);
        $this->assertStringContainsString('IH-555', $idbHtml);

        // 4) Revir Protokol Defterinde gizlilik + Hekim imza alanı olmalı
        $revirHtml = view('pdf.revir-protokol-defteri', [
            'firma' => $firma->load('isyeriHekimi', 'dsp'),
            'kayitlar' => collect(),
        ])->render();
        $this->assertStringContainsString('6331 SK m.15/5', $revirHtml);
        $this->assertStringContainsString('IH-555', $revirHtml);
        $this->assertStringContainsString('(Kaşe / İmza)', $revirHtml);
    }
}
