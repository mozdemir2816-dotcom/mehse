<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\MuayeneFormu;
use App\Models\OlayKaydi;
use App\Models\RiskDegerlendirmesi;
use App\Models\TatbikatTutanagi;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\YillikDegerlendirmeExcelUretici;
use App\Support\YillikDegerlendirmeVerisi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class YillikDegerlendirmeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_donem_atanma_tarihinden_yil_sonuna(): void
    {
        $atanan = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => '2026-03-15']);
        $eski = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => '2024-06-01']);

        $this->assertSame('15.03.2026–31.12.2026', YillikDegerlendirmeVerisi::donemMetni($atanan, 2026));
        $this->assertSame('01.01.2026–31.12.2026', YillikDegerlendirmeVerisi::donemMetni($eski, 2026));
    }

    public function test_kayitlardan_satirlar_dolar_donem_disi_sayilmaz(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Mehmet Uzman']);
        $firma = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => '2026-03-01', 'igu_id' => $igu->id]);

        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => '2026-05-03', 'gecerlilik_tarihi' => '2030-05-03']);
        $rd->maddeler()->create(['sira' => 1, 'tehlike' => 'Düşme', 'olasilik' => 5, 'siddet' => 5, 'oneri' => 'Korkuluk']);
        MuayeneFormu::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'A', 'muayene_turu' => 'ise_giris', 'muayene_tarihi' => '2026-04-10']);
        MuayeneFormu::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'B', 'muayene_turu' => 'ise_giris', 'muayene_tarihi' => '2026-09-20']);
        MuayeneFormu::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'C', 'muayene_turu' => 'ise_giris', 'muayene_tarihi' => '2026-02-01']);   // atanmadan önce
        TatbikatTutanagi::create(['firma_id' => $firma->id, 'senaryo_anahtari' => 'yangin', 'durum' => 'yapildi', 'tatbikat_tarihi' => '2026-10-12']);
        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'is_kazasi', 'olay_tarihi' => '2026-06-01', 'kayip_gun_sayisi' => 4, 'etkilenen_ad_soyad' => 'X']);

        $satirlar = collect(YillikDegerlendirmeVerisi::planiDoldur($firma, 2026, [])['satirlar'])->keyBy('anahtar');

        $this->assertSame('03.05.2026', $satirlar['risk']['tarih']);
        $this->assertStringContainsString('İncelenen risk: 1 madde', $satirlar['risk']['sonuc']);
        $this->assertStringContainsString('açık tedbir: 1', $satirlar['risk']['sonuc']);
        $this->assertStringNotContainsString('Mehmet Uzman', $satirlar['risk']['yapan_kisi']);   // görevli adı yazılmaz

        $this->assertSame('10.04.2026–20.09.2026', $satirlar['ise_giris']['tarih']);
        $this->assertStringStartsWith('2 kişinin işe giriş', $satirlar['ise_giris']['sonuc']);

        $this->assertStringContainsString('12.10.2026 tarihinde Yangın Tatbikatı yapıldı', $satirlar['tatbikat']['sonuc']);
        $this->assertStringContainsString('kayıp iş günü: 4', $satirlar['is_kazasi']['sonuc']);
        $this->assertStringContainsString('kayıp iş günü: 4', $satirlar['genel_2']['sonuc']);

        // Kaydı olmayan satır şablon metniyle kalır, uydurma sonuç yazılmaz
        $this->assertFalse($satirlar['toksikolojik']['otomatik']);
        $this->assertStringStartsWith('Gereklilik: …', $satirlar['toksikolojik']['sonuc']);
    }

    public function test_elle_duzenlenen_satir_korunur_istenirse_ezilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()->startOfYear()->addMonths(2)]);
        $satirlar = YillikDegerlendirmeVerisi::sablonSatirlari();
        $satirlar[0] = [...$satirlar[0], 'sonuc' => 'Benim yorumum', 'elle' => true];

        $this->assertSame('Benim yorumum', YillikDegerlendirmeVerisi::planiDoldur($firma, now()->year, $satirlar)['satirlar'][0]['sonuc']);
        $this->assertStringStartsWith('Rapor tarihi:', YillikDegerlendirmeVerisi::planiDoldur($firma, now()->year, $satirlar, true)['satirlar'][0]['sonuc']);

        // Eski 11 satırlık yapı yeni şablona çevrilir
        $eski = [['calisma' => 'Risk değerlendirmesi', 'yapan_kisi' => 'x', 'sonuc' => 'y']];
        $this->assertCount(42, YillikDegerlendirmeVerisi::planiDoldur($firma, now()->year, $eski)['satirlar']);
    }

    public function test_excel_sablon_duzeninde_isimsiz_uretilir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Mehmet Uzman']);
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Deneme A.Ş.', 'sgk_sicil_no' => '123', 'igu_id' => $igu->id, 'isveren_vekili' => 'Patron Bey', 'sozlesme_baslangic' => '2026-03-01']);
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => '2026-05-03']);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        $yanit = YillikDegerlendirmeExcelUretici::excel($plan, Carbon::parse('2027-01-05'));
        ob_start();
        $yanit->sendContent();
        $tmp = tempnam(sys_get_temp_dir(), 't').'.xlsx';
        file_put_contents($tmp, ob_get_clean());

        $s = IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        $this->assertSame('İşyeri unvanı: Deneme A.Ş.', $s->getCell('A2')->getValue());
        $this->assertSame('Rapor yılı: 2026 | Dönem: 01.03.2026–31.12.2026', $s->getCell('D2')->getValue());
        $this->assertStringStartsWith('Rapor tarihi: 05.01.2027', $s->getCell('D6')->getValue());
        $this->assertSame('03.05.2026', $s->getCell('B8')->getValue());
        $this->assertStringStartsWith('Rapor tarihi: 03.05.2026', $s->getCell('F8')->getValue());
        $this->assertSame('Tarih / Kaşe / İmza:', $s->getCell('A16')->getValue());

        $hepsi = collect($s->toArray())->flatten()->implode(' ');
        $this->assertStringNotContainsString('Mehmet Uzman', $hepsi);
        $this->assertStringNotContainsString('Patron Bey', $hepsi);
        $this->assertCount(6, $s->getBreaks());
    }
}
