<?php

namespace Tests\Feature;

use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\GorevDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 13.11.2026: belge numarası "sayı + 1" ile üretildiği için silme sonrası tekrar ediyordu. */
class BelgeNumarasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_silinen_kayittan_sonra_risk_belge_no_tekrar_etmez(): void
    {
        $firma = Firma::factory()->create();
        $yil = now()->year;
        $a = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now(), 'belge_no' => RiskDegerlendirmesi::belgeNoUret()]);
        $b = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now(), 'belge_no' => RiskDegerlendirmesi::belgeNoUret()]);
        $this->assertSame("RD-{$yil}-001", $a->belge_no);
        $this->assertSame("RD-{$yil}-002", $b->belge_no);

        $a->delete();

        $this->assertSame("RD-{$yil}-003", RiskDegerlendirmesi::belgeNoUret());
    }

    public function test_egitim_formu_numarasi_en_buyuk_numaradan_devam_eder(): void
    {
        $firma = Firma::factory()->create();
        $yil = now()->year;
        EgitimKatilim::create(['firma_id' => $firma->id, 'belge_no' => "EGT-{$yil}-041"]);
        $yeni = EgitimKatilim::create(['firma_id' => $firma->id]);

        $this->assertSame("EGT-{$yil}-042", $yeni->belge_no);
    }

    public function test_calisan_listesi_girilmemis_gorevi_uretilir(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create(['calisan_sayisi' => 12]);

        $gorev = GorevDurumu::gorevler($uzman)->firstWhere('baslik', 'Çalışan listesi girilmemiş');

        $this->assertNotNull($gorev);
        $this->assertSame($firma->id, $gorev['firma_id']);
        $this->assertStringContainsString('12 çalışan bildiriyor', $gorev['aciklama']);
    }
}
