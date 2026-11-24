<?php

namespace Tests\Feature;

use App\Models\Calisan;
use App\Models\CanliEgitimOturumu;
use App\Models\EgitimAtamasi;
use App\Models\EgitimKatilim;
use App\Models\EgitimPaketi;
use App\Models\EgitimSinavi;
use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\IsgProfesyoneli;
use App\Models\Sertifika;
use App\Models\Talimat;
use App\Models\User;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\TalimatUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvrakDenetimPaketBTest extends TestCase
{
    use RefreshDatabase;

    public function test_egitim_sinavi_dogru_cevaplari_calisan_kagidina_sizdirmaz(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id]);
        $sinav = EgitimSinavi::create([
            'firma_id' => $firma->id,
            'sektor' => 'genel',
            'zorluk' => 'orta',
            'zaman' => 'son',
            'cevap_anahtari_dahil' => true,
            'katilimcilar' => [['ad_soyad' => 'Ali Yılmaz', 'tc' => '10000000146', 'gorev' => 'Operatör']],
            'sorular' => [[
                'soru' => 'Acil çağrı numarası kaçtır?',
                'secenekler' => ['112', '110', '155', '156'],
                'dogru_index' => 0,
            ]],
        ]);

        $html = view('pdf.egitim-sinavi', ['sinav' => $sinav, 'firma' => $firma])->render();

        // Çalışan sınav sayfasında doğru şık işaretlenmemeli
        $this->assertStringNotContainsString('class="secenek dogru"', $html);
        $this->assertStringContainsString('CEVAP ANAHTARI', $html);
        $this->assertStringContainsString('T.C. Kimlik No', $html);
        $this->assertStringContainsString('Katılımcı (Çalışan) İmzası', $html);
    }

    public function test_uzaktan_egitim_ek2_belgesinde_tc_isveren_ve_4_konu_kutucuklari_dogru_basilir(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $igu = IsgProfesyoneli::create(['user_id' => $user->id, 'ad_soyad' => 'Özgür İGU', 'tip' => 'igu', 'sertifika_no' => 'IGU-99']);
        $firma = Firma::factory()->create([
            'user_id' => $user->id,
            'unvan' => 'Çelik Makine A.Ş.',
            'tehlike_sinifi' => 'cok_tehlikeli',
            'isveren_ad' => 'Kemal Patron',
            'igu_id' => $igu->id,
        ]);
        $calisan = Calisan::create([
            'firma_id' => $firma->id,
            'ad_soyad' => 'İbrahim Şahin',
            'tc' => '10000000146',
            'gorev' => 'Kaynakçı',
            'aktif' => true,
        ]);
        $paket = EgitimPaketi::create([
            'user_id' => $user->id,
            'ad' => 'Çok Tehlikeli Temel İSG',
            'egitim_turu' => 'ilk_defa',
            'aktif' => true,
        ]);
        $atama = EgitimAtamasi::create([
            'calisan_id' => $calisan->id,
            'egitim_paketi_id' => $paket->id,
            'atayan_user_id' => $user->id,
            'egitim_turu' => 'ilk_defa',
            'durum' => 'tamamlandi',
            'atandi_at' => now()->subDays(12),
            'tamamlandi_at' => now()->subDays(10),
        ]);

        $atama->load('calisan.firma.igu', 'paket', 'atayan');
        $html = view('pdf.uzaktan-egitim-belgesi', [
            'atama' => $atama,
            'calisan' => $atama->calisan,
            'firma' => $atama->calisan->firma,
            'paket' => $atama->paket,
            'sinav' => null,
        ])->render();

        $this->assertStringContainsString('10000000146', $html);
        $this->assertStringContainsString('Kemal Patron', $html);
        $this->assertStringContainsString('Geçerlilik tarihi:', $html);
        $this->assertStringContainsString($atama->tamamlandi_at->format('d.m.Y'), $html);
        // Çok tehlikeli sınıfta hem Yüz yüze hem 4. başlık a) işaretli (☒) olmalı
        $this->assertMatchesRegularExpression('/Yüz yüze\s*<span class="kutu dolu">☒<\/span>/u', $html);
    }

    public function test_sertifika_belge_no_ve_canli_sertifika_turkce_buyuk_harf_ve_talimat_tc(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'isveren_ad' => 'Hasan İşveren', 'tehlike_sinifi' => 'tehlikeli']);

        $sertifika = Sertifika::create([
            'firma_id' => $firma->id,
            'tip' => 'isg',
            'egitim_turu' => 'ilk_defa',
            'egitim_sekli' => 'yuz_yuze',
            'sure_metni' => '12 Ders saati',
            'egitim_tarihleri' => [now()->toDateString()],
            'gecerlilik_tarihi' => now()->addYears(2)->toDateString(),
            'katilimcilar' => [['ad_soyad' => 'Ali İhsan', 'tc' => '10000000146', 'gorev' => 'Teknisyen']],
            'konu_icerigi' => ['tip' => 'genel', 'genel_konular' => [], 'saglik_konulari' => [], 'teknik_konular' => []],
        ]);
        $sertHtml = view('pdf.sertifika', ['sertifika' => $sertifika, 'firma' => $firma, 'imzali' => true])->render();
        $this->assertStringContainsString($sertifika->belge_no, $sertHtml);
        $this->assertStringContainsString('2. Sağlık Konuları', $sertHtml);
        $this->assertContains('12 saat', config('isg.sertifika.sureler'));

        // Canlı eğitim sertifikasında Türkçe İ korunmalı (ALİ İHSAN, ALI IHSAN değil!)
        $oturum = CanliEgitimOturumu::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'baslik' => 'Yüksekte Çalışma',
            'kategori' => 'teknik',
            'sure_saat' => 4,
            'oturum_tarihi' => now()->toDateString(),
            'katilim_token' => str_repeat('t', 32),
        ]);
        $canliHtml = view('pdf.canli-egitim-sertifika', [
            'oturum' => $oturum->load('firma.igu', 'user'),
            'sonuc' => ['ad_soyad' => 'Ali İhsan', 'tc' => '10000000146', 'gorev' => 'Bakım', 'puan' => 90, 'kod' => 'KOD-1', 'tarih' => '24.11.2026'],
            'qrUri' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
        ])->render();
        $this->assertStringContainsString('ALİ İHSAN', $canliHtml);
        $this->assertStringContainsString('Hasan İşveren', $canliHtml);

        // Eğitim katılım formunda eğitici kapalı olsa da İşveren imzası çıkmalı
        $ek = EgitimKatilim::create([
            'firma_id' => $firma->id,
            'baslik_anahtari' => 'genel',
            'egitim_turu' => 'ilk_defa',
            'egitim_sekli' => 'yuz_yuze',
            'isg_uzmani_var' => false,
            'isyeri_hekimi_var' => false,
            'katilimcilar' => [],
        ]);
        $ekHtml = view('pdf.egitim-katilim', [
            'kayit' => $ek,
            'firma' => $firma,
            'icerik' => ['tip' => 'genel', 'saat' => 12, 'genel_konular' => [], 'saglik_konulari' => [], 'teknik_konular' => []],
            'imzali' => true,
        ])->render();
        $this->assertStringContainsString('İşveren / İşveren Vekili', $ekHtml);

        // Talimat & İşbaşı tutanağı imzaları + T.C.
        $talimat = Talimat::create(['firma_id' => $firma->id, 'baslik' => 'Torna Talimatı', 'maddeler' => ['Koruyucu takın']]);
        $talHtml = view('pdf.talimat', [
            'talimat' => $talimat,
            'firma' => $firma,
            'logo' => null,
            'tebligEden' => TalimatUretici::tebligEden($firma),
            'imzali' => true,
            'tebellugAlan' => ['ad_soyad' => 'Ali', 'tc' => '10000000146'],
            'tebellugSatiri' => 5,
        ])->render();
        $this->assertStringContainsString('ONAYLAYAN (İŞVEREN / VEKİLİ)', $talHtml);
        $this->assertStringContainsString('T.C. Kimlik No', $talHtml);

        $isbasi = IsbasiEgitimTutanagi::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Ali İhsan',
            'egitim_tarihi' => now()->toDateString(),
            'sure_saat' => 2,
        ]);
        $isbasiHtml = view('pdf.isbasi-egitim-tutanagi', ['tutanak' => $isbasi, 'firma' => $firma])->render();
        $this->assertStringContainsString('İŞVEREN / VEKİLİ (ONAY)', $isbasiHtml);
        $this->assertStringContainsString('RG 02.04.2026 / 33212, m.7', $isbasiHtml);
    }
}
