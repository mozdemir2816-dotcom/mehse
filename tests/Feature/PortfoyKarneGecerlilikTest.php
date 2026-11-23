<?php

namespace Tests\Feature;

use App\Models\AcilDurumPlani;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\RiskDegerlendirmesi;
use App\Models\TatbikatTutanagi;
use App\Models\User;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** K2 (23.11.2026): uyum kriterleri kaydın varlığına değil, süresinin geçmemesine bakar. */
class PortfoyKarneGecerlilikTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function firma(string $sinif = 'cok_tehlikeli', int $calisan = 10): Firma
    {
        return Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => $sinif, 'calisan_sayisi' => $calisan]);
    }

    public function test_risk_degerlendirmesi_suresi_gecince_karsilanmaz(): void
    {
        $eski = $this->firma('cok_tehlikeli');
        RiskDegerlendirmesi::create(['firma_id' => $eski->id, 'rapor_tarihi' => now()->subYears(3)]);   // 2 yıl geçerli
        $yeni = $this->firma('cok_tehlikeli');
        RiskDegerlendirmesi::create(['firma_id' => $yeni->id, 'rapor_tarihi' => now()->subYear()]);
        $azTehlikeli = $this->firma('az_tehlikeli');
        RiskDegerlendirmesi::create(['firma_id' => $azTehlikeli->id, 'rapor_tarihi' => now()->subYears(3)]); // 6 yıl geçerli

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($eski, 'risk_degerlendirmesi'));
        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($yeni, 'risk_degerlendirmesi'));
        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($azTehlikeli, 'risk_degerlendirmesi'));
    }

    public function test_acil_durum_plani_suresi_gecince_karsilanmaz(): void
    {
        $eski = $this->firma('tehlikeli');
        AcilDurumPlani::create(['firma_id' => $eski->id, 'konular' => ['yangin'], 'rapor_tarihi' => now()->subYears(5)]); // 4 yıl
        $yeni = $this->firma('tehlikeli');
        AcilDurumPlani::create(['firma_id' => $yeni->id, 'konular' => ['yangin'], 'rapor_tarihi' => now()->subYears(3)]);

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($eski->fresh(), 'acil_durum_plani'));
        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($yeni->fresh(), 'acil_durum_plani'));
    }

    public function test_tatbikat_son_12_ayda_yapilmis_olmali(): void
    {
        $eski = $this->firma();
        TatbikatTutanagi::create(['firma_id' => $eski->id, 'durum' => 'yapildi', 'tatbikat_tarihi' => now()->subMonths(13)]);
        $yeni = $this->firma();
        TatbikatTutanagi::create(['firma_id' => $yeni->id, 'durum' => 'yapildi', 'tatbikat_tarihi' => now()->subMonths(11)]);

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($eski, 'acil_durum_tatbikat'));
        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($yeni, 'acil_durum_tatbikat'));
    }

    public function test_kurul_periyodu_tehlike_sinifina_gore(): void
    {
        $cok = $this->firma('cok_tehlikeli', 60);
        KurulToplantisi::create(['firma_id' => $cok->id, 'tarih' => now()->subDays(45)]);   // ayda bir → gecikmiş
        $az = $this->firma('az_tehlikeli', 60);
        KurulToplantisi::create(['firma_id' => $az->id, 'tarih' => now()->subDays(45)]);    // 3 ayda bir → güncel

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($cok, 'isg_kurulu'));
        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($az, 'isg_kurulu'));
    }

    public function test_egitim_tekrar_suresi_gecince_karsilanmaz(): void
    {
        $eski = $this->firma('cok_tehlikeli');
        EgitimKatilim::create(['firma_id' => $eski->id, 'belge_tarihi' => now()->subMonths(14)]);   // yılda bir
        $yeni = $this->firma('az_tehlikeli');
        EgitimKatilim::create(['firma_id' => $yeni->id, 'belge_tarihi' => now()->subMonths(14)]);   // 3 yılda bir

        $this->assertFalse(PortfoyKarne::firmaKriterKarsilarMi($eski, 'egitim_katilim_formu'));
        $this->assertTrue(PortfoyKarne::firmaKriterKarsilarMi($yeni, 'egitim_katilim_formu'));
    }

    public function test_tarihi_bos_kayit_guncel_sayilmaz(): void
    {
        $this->assertFalse(PortfoyKarne::tarihGecerliMi(null, 24));
        $this->assertTrue(PortfoyKarne::tarihGecerliMi(now()->subYears(10), 24, now()->addDay()));
        $this->assertFalse(PortfoyKarne::tarihGecerliMi(now(), 24, now()->subDay()));
    }
}
