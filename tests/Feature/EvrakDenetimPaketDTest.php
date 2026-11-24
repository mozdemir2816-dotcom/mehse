<?php

namespace Tests\Feature;

use App\Models\CanliImza;
use App\Models\CezaTebligTutanagi;
use App\Models\Firma;
use App\Models\IpcTebligi;
use App\Models\IsIzinFormu;
use App\Models\SahaAnalizi;
use App\Models\Taseron;
use App\Models\TespitOneriDefteri;
use App\Models\User;
use App\Support\TespitOneriDefteriUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvrakDenetimPaketDTest extends TestCase
{
    use RefreshDatabase;

    public function test_tespit_oneri_defteri_3_nusha_notu_3lu_imza_ve_sayfa_no_icerir(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'isveren_ad' => 'Ali Patron']);
        $defter = TespitOneriDefteri::create([
            'firma_id' => $firma->id,
            'maddeler' => [['tespit' => 'Kablo açıkta', 'oneri' => 'Kapatılmalı', 'oncelik' => 'yuksek']],
        ]);

        $html = view('pdf.tespit-oneri-defteri', ['defter' => $defter, 'firma' => $firma])->render();
        $this->assertStringContainsString('İSG Hizmetleri Yön. m.7/3–5', $html);
        $this->assertStringContainsString('İşyeri Hekimi', $html);

        $yanit = TespitOneriDefteriUretici::pdf($defter);
        ob_start();
        $yanit->sendContent();
        $pdf = (string) ob_get_clean();
        $this->assertStringStartsWith('%PDF-1.', $pdf);
    }

    public function test_ptw_4lu_imza_ipc_30_gun_ve_ceza_tutanagi_tc_maskeleme_imtina(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id]);

        // PTW: tek sayıda kontrol maddesi + Talep Eden & Kapanış imzaları
        $ptw = IsIzinFormu::create([
            'firma_id' => $firma->id,
            'is_turu' => 'sicak_calisma',
            'tarih' => now()->toDateString(),
            'ekip_lideri' => 'Hasan Usta',
            'kapatan' => 'Mehmet Şef',
            'kontroller' => ['yangin_tupu' => 'uygun'],
        ]);
        $ptwHtml = view('pdf.is-izin-formu', ['form' => $ptw, 'firma' => $firma, 'qrUri' => null])->render();
        $this->assertStringContainsString('İşi Talep Eden / Ekip Sorumlusu', $ptwHtml);
        $this->assertStringContainsString('İş Bitimi / Saha Güvenli Teslim', $ptwHtml);

        // İPC: 6331 m.26/2 30 gün ödeme + 5326 m.17/6 kanun yolu güvencesi
        $ipc = IpcTebligi::create([
            'firma_id' => $firma->id,
            'ceza_tutari' => 50000,
        ]);
        $ipcHtml = view('pdf.ipc-tebligi', ['tebligi' => $ipc, 'firma' => $firma])->render();
        $this->assertStringContainsString('BİLGİLENDİRME VE TAKİP TUTANAĞI', $ipcHtml);
        $this->assertStringContainsString('30 gün içinde', $ipcHtml);
        $this->assertStringContainsString('kanun yoluna başvurma hakkını etkilemez', $ipcHtml);

        // Ceza Tutanağı: %33.33 genişlik, T.C. maskeli, 2 tanık imtina şerhi, 4857 m.38
        $ceza = CezaTebligTutanagi::create([
            'firma_id' => $firma->id,
            'calisan_ad_soyad' => 'Veli K.',
            'calisan_tc' => '10000000146',
            'imza_durumu' => 'imtina_etti',
            'yaptirim' => 'yazili_uyari',
        ]);
        $cezaHtml = view('pdf.ceza-teblig-tutanagi', ['tutanak' => $ceza, 'firma' => $firma])->render();
        $this->assertStringContainsString('width: 33.33%', $cezaHtml);
        $this->assertStringNotContainsString('10000000146', $cezaHtml);
        $this->assertStringContainsString('*****', $cezaHtml);
        $this->assertStringContainsString('İMZADAN İMTİNA ŞERHİ', $cezaHtml);
        $this->assertStringContainsString('1. Tanık Ad Soyad / İmza', $cezaHtml);
        $this->assertStringContainsString('ÇSGB hesabına', $cezaHtml);
    }

    public function test_taseron_personel_canli_imza_sozlesme_ve_qr_duzeltmeleri(): void
    {
        $user = User::factory()->create([
            'rol' => 'sahip',
            'vergi_dairesi' => 'KADIKÖY VERGİ DAİRESİ',
        ]);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'sgk_sicil_no' => '2-1111']);

        // Taşeron Taahhütname: 6331 m.22/2 & m.23 & 4857 m.2/6-7
        $taseron = Taseron::create([
            'user_id' => $user->id,
            'firma_id' => $firma->id,
            'unvan' => 'Alt İşveren A.Ş.',
            'yetkili' => 'Kemal Yetkili',
            'faaliyet' => 'İskele Kurulumu',
            'tur' => 'alt_isveren',
            'aktif' => true,
        ]);
        $tasHtml = view('pdf.taseron-taahhut', ['t' => $taseron, 'firma' => $firma])->render();
        $this->assertStringContainsString('m.22/2', $tasHtml);
        $this->assertStringContainsString('m.23', $tasHtml);
        $this->assertStringContainsString('4857 sayılı İş Kanunu m.2/6–7', $tasHtml);

        // Personel Listesi: mülga "Ağır-Teh." yok, KVKK şerhi var
        $perHtml = view('pdf.personel-listesi', [
            'firma' => $firma,
            'calisanlar' => collect(),
            'gorunum' => 'Aktif',
            'sube' => null,
            'ozet' => ['toplam' => 0, 'kadin' => 0, 'erkek' => 0, 'belirtilmemis' => 0, 'engelli' => 0],
        ])->render();
        $this->assertStringNotContainsString('Ağır-Teh.', $perHtml);
        $this->assertStringContainsString('KVKK GİZLİLİK NOTU', $perHtml);

        // Canlı İmza: "Islak" çelişkisi yok
        $canli = new CanliImza(['belge_turu' => 'kkd_zimmet', 'belge_baslik' => 'KKD', 'calisan_adi' => 'Ali']);
        $canliHtml = view('pdf.canli-imza-muhurlu', [
            'imza' => $canli,
            'imzaBase64' => null,
            'qrUri' => null,
            'dogrulamaUrl' => 'https://mehse.com/imza-dogrula/TEST',
        ])->render();
        $this->assertStringNotContainsString('Islak / Canlı', $canliHtml);
        $this->assertStringContainsString('Çalışan Canlı İmza Görseli', $canliHtml);

        // Hizmet Sözleşmesi: "VERGİ DAİRESİ" ikinci kez eklenmemeli
        $sozHtml = view('pdf.hizmet_sozlesmesi', [
            'user' => $user,
            'metin' => \App\Support\YasalMetinler::aktifler()['sozlesme'],
            'onay' => null,
            'onayAt' => null,
            'guncel' => false,
            'saglayici' => config('isg.kayit.saglayici'),
            'olusturma' => now()->timezone('Europe/Istanbul'),
        ])->render();
        $this->assertStringContainsString('KADIKÖY VERGİ DAİRESİ', $sozHtml);
        $this->assertStringNotContainsString('KADIKÖY VERGİ DAİRESİ Vergi Dairesi', $sozHtml);

        // QR Poster: kırık 📱 emojisi yok
        $qrHtml = view('pdf.firma-risk-bildirim-qr', [
            'firma' => $firma,
            'qrDataUri' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
            'url' => 'https://mehse.com/risk-bildir/1/abc',
            'tarih' => '24.11.2026',
        ])->render();
        $this->assertStringNotContainsString('📱', $qrHtml);

        // Saha Analizi: tablo genişliği 100% ve İşveren imzası
        $saha = SahaAnalizi::create(['firma_id' => $firma->id, 'bulgular' => []]);
        $sahaHtml = view('pdf.saha-analiz-raporu', ['rapor' => $saha, 'firma' => $firma])->render();
        $this->assertStringContainsString('İŞVEREN / İŞVEREN VEKİLİ (TEBELLÜĞ)', $sahaHtml);
        $this->assertStringNotContainsString('object-fit: cover', $sahaHtml);
    }
}
