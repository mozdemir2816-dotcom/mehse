<?php

namespace Tests\Feature;

use App\Models\AcilDurumKrokisi;
use App\Models\CalisanTemsilcisiSecimi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\IsgProfesyoneli;
use App\Models\IsKazasiRaporu;
use App\Models\KkdMatrisi;
use App\Models\KkdZimmetFormu;
use App\Models\OrtamOlcumu;
use App\Models\PeriyodikKontrol;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\RiskDegerlendirmesiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EvrakDenetimPaketCTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_degerlendirmesi_turkce_i_ekip_ve_belge_no_dogru_cozulur(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $igu = IsgProfesyoneli::create(['user_id' => $user->id, 'ad_soyad' => 'Özgür Çelik', 'tip' => 'igu', 'sertifika_no' => 'IGU-A-777']);
        $hekim = IsgProfesyoneli::create(['user_id' => $user->id, 'ad_soyad' => 'Dr. Deniz', 'tip' => 'hekim', 'sertifika_no' => 'IH-888']);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'igu_id' => $igu->id, 'isyeri_hekimi_id' => $hekim->id]);

        $rd = RiskDegerlendirmesi::create([
            'firma_id' => $firma->id,
            'firma_unvan' => $firma->unvan,
            'igu_id' => $igu->id,
            'yontem' => 'fine_kinney',
            'ekip' => [
                ['unvan' => 'ÇALIŞAN TEMSİLCİSİ', 'ad' => 'Ali Temsilci 1'],
                ['unvan' => 'ÇALIŞAN TEMSİLCİSİ', 'ad' => 'Veli Temsilci 2'],
                ['unvan' => 'BİLGİ SAHİBİ PERSONEL', 'ad' => 'Mehmet Usta'],
            ],
        ]);

        $v = RiskDegerlendirmesiUretici::veri($rd);

        $this->assertStringContainsString('IGU-A-777', (string) $v['iguAdi']);
        $this->assertStringContainsString('IH-888', (string) $v['hekimAdi']);
        $this->assertSame('Ali Temsilci 1', $v['temsilci']['ad'] ?? null);
        $this->assertSame('Mehmet Usta', $v['bilgiSahibiCalisan']['ad'] ?? null);

        $temsilciSatirlari = collect($v['ekipGosterim'])->where('unvan', 'Çalışan Temsilcisi')->pluck('ad')->all();
        $this->assertSame(['Ali Temsilci 1', 'Veli Temsilci 2'], $temsilciSatirlari);
    }

    public function test_acil_durum_afis_112_ve_kroki_xmlns_ve_calisan_temsilcisi_5_yil_oylama(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'tehlike_sinifi' => 'cok_tehlikeli', 'isveren_ad' => 'Ahmet Bey']);

        $afisHtml = view('pdf.acil-durum-afis', [
            'firma' => $firma,
            'afis' => ['baslik' => 'YANGIN', 'ad' => 'Yangın Talimatı', 'adimlar' => ['Sakin olun']],
        ])->render();
        $this->assertStringNotContainsString('İtfaiye: 110', $afisHtml);
        $this->assertStringContainsString('112 Tek Acil Çağrı Merkezi', $afisHtml);

        $kroki = AcilDurumKrokisi::create(['firma_id' => $firma->id, 'hazirlanma_tarihi' => now()->toDateString()]);
        $krokiHtml = view('pdf.acil-durum-krokisi', ['kroki' => $kroki, 'firma' => $firma])->render();
        $this->assertStringContainsString('xmlns:xlink="http://www.w3.org/1999/xlink"', $krokiHtml);
        $this->assertStringContainsString('112 Tek Acil Çağrı Merkezi', $krokiHtml);

        $secim = CalisanTemsilcisiSecimi::create([
            'firma_id' => $firma->id,
            'secim_tarihi' => '2026-11-24',
            'isyeri_calisan_sayisi' => 80,
            'zorunlu_temsilci_sayisi' => 2,
            'adaylar' => [
                ['ad_soyad' => 'Ali Yılmaz', 'unvan' => 'Usta', 'oy' => 40, 'secildi' => true, 'bas_temsilci' => true],
                ['ad_soyad' => 'Ayşe Kaya', 'unvan' => 'Operatör', 'oy' => 30, 'secildi' => true, 'bas_temsilci' => false],
            ],
        ]);
        $tutanakHtml = view('pdf.calisan-temsilcisi-tutanak', ['secim' => $secim, 'firma' => $firma])->render();
        $this->assertStringContainsString('5 (beş) yıl', $tutanakHtml);
        $this->assertStringContainsString('24.11.2031', $tutanakHtml);
        $this->assertStringContainsString('BAŞ TEMSİLCİ', $tutanakHtml);
        $this->assertStringContainsString('Ayşe Kaya', $tutanakHtml);
        $this->assertStringContainsString('Toplam Geçerli Oy', $tutanakHtml);
    }

    public function test_kkd_periyodik_ortam_ve_is_kazasi_resmi_tatil_3_is_gunu(): void
    {
        $user = User::factory()->create(['rol' => 'sahip']);
        $firma = Firma::factory()->create(['user_id' => $user->id, 'sgk_sicil_no' => '2-2562-01']);

        // KKD Zimmet: dinamik sayfa no + Marka/Model + uygulamalı bakım eğitimi
        $kkd = KkdZimmetFormu::create([
            'firma_id' => $firma->id,
            'teslim_tarihi' => '2026-11-24',
            'calisanlar' => [
                ['ad_soyad' => 'Ali', 'gorev' => 'Kaynakçı', 'departman' => 'Üretim', 'tc' => '10000000146'],
                ['ad_soyad' => 'Veli', 'gorev' => 'Montajcı', 'departman' => 'Montaj', 'tc' => '10000000146'],
            ],
            'kkdler' => [['ad' => 'Baret', 'marka_model' => '3M G3000', 'standart' => 'EN 397', 'donem' => '2 yıl', 'miktar' => 1]],
        ]);
        $kkdHtml = view('pdf.kkd-zimmet-formu', ['form' => $kkd, 'firma' => $firma])->render();
        $this->assertStringContainsString('1 / 2', $kkdHtml);
        $this->assertStringContainsString('2 / 2', $kkdHtml);
        $this->assertStringContainsString('3M G3000', $kkdHtml);
        $this->assertStringContainsString('uygulamalı kullanım eğitimi', $kkdHtml);

        // KKD Matris: 8 haneli hex yok, EN standartı var
        $matris = KkdMatrisi::create(['firma_id' => $firma->id, 'satirlar' => [['is_kalemi' => 'Kaynak', 'baret' => 'zorunlu']]]);
        $matrisHtml = view('pdf.kkd-matris', [
            'matris' => $matris,
            'firma' => $firma,
            'sutunlar' => config('isg.kkd_matris.sutunlar'),
            'durumlar' => config('isg.kkd_matris.durumlar'),
        ])->render();
        $this->assertStringContainsString('Baret (EN 397)', $matrisHtml);
        $this->assertStringContainsString('#fee2e2', $matrisHtml);

        // Periyodik Kontrol: .d-uygun_degil tanımlı ve EKİPNET başlığı var
        $pk = PeriyodikKontrol::create(['firma_id' => $firma->id]);
        $ekipman = IsEkipmani::create([
            'firma_id' => $firma->id,
            'kategori' => 'basincli_kaplar',
            'ekipman_adi' => 'Kompresör',
            'muayene_periyodu_ay' => 12,
            'sonuc' => 'uygun_degil',
            'aktif' => true,
        ]);
        $pkHtml = view('pdf.periyodik-kontrol', [
            'kontrol' => $pk,
            'firma' => $firma,
            'ekipmanlar' => collect([$ekipman]),
            'sonuclar' => config('isg.periyodik_kontrol.sonuclar'),
        ])->render();
        $this->assertStringContainsString('.d-uygun_degil', $pkHtml);
        $this->assertStringContainsString('EKİPNET No', $pkHtml);
        $this->assertStringContainsString('Ağır Kusur', $pkHtml);

        // Ortam Ölçümü: Gürültü 85 dB(A) Eylem / 87 dB(A) Sınır ayrımı
        $oo = OrtamOlcumu::create([
            'firma_id' => $firma->id,
            'olcumler' => [['parametre' => 'Kişisel Gürültü Maruziyeti', 'olculen_deger' => '86', 'sinir_deger' => '85', 'birim' => 'dB(A)', 'sonuc' => 'asim']],
        ]);
        $ooHtml = view('pdf.ortam-olcumleri', [
            'olcum' => $oo,
            'firma' => $firma,
            'sonuclar' => config('isg.ortam_olcum.sonuclar'),
        ])->render();
        $this->assertStringContainsString('Eylem: 85 (Sınır: 87) dB(A)', $ooHtml);

        // İş Kazası: 28 Ekim 2026 (Çarşamba) → 29 Ekim Cumhuriyet Bayramı (Perşembe) atlanarak 3. iş günü 3 Kasım Salı olmalı!
        $kaza = new IsKazasiRaporu(['kaza_tarihi' => Carbon::parse('2026-10-28')]);
        $this->assertSame('2026-11-03', $kaza->sgkSonTarih()?->toDateString());
    }
}
