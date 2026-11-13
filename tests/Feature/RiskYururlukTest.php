<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\OlayKaydi;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\GorevDurumu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 13.11.2026: firma/birim başına tek yürürlükteki risk değerlendirmesi + md.12 revizyon gerekçeleri. */
class RiskYururlukTest extends TestCase
{
    use RefreshDatabase;

    private function rd(Firma $firma, string $tarih, array $ek = []): RiskDegerlendirmesi
    {
        return RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => $tarih] + $ek);
    }

    public function test_yururluge_alinca_ayni_birimdeki_onceki_arsive_gider(): void
    {
        $firma = Firma::factory()->create();
        $eski = $this->rd($firma, '2025-01-10');
        $yeni = $this->rd($firma, '2026-01-10');
        $baskaBirim = $this->rd($firma, '2025-05-10', ['proje_adi' => 'B Şantiyesi']);

        $eski->yururlugeAl();
        $baskaBirim->yururlugeAl();
        $yeni->yururlugeAl();

        $this->assertSame('arsiv', $eski->fresh()->durum);
        $this->assertSame('yayinlandi', $yeni->fresh()->durum);
        $this->assertSame('yayinlandi', $baskaBirim->fresh()->durum, 'farklı birim etkilenmez');
        $this->assertSame($yeni->id, RiskDegerlendirmesi::gecerliOlan($firma->id)->id);
    }

    public function test_rapordan_sonraki_olaylar_revizyon_gorevi_uretir(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create(['calisan_sayisi' => 0]);
        $rd = $this->rd($firma, now()->subMonths(3)->toDateString());

        $this->assertSame([], $rd->revizyonGerekceleri());

        OlayKaydi::create(['firma_id' => $firma->id, 'olay_tipi' => 'ramak_kala', 'olay_tarihi' => now()->subMonth()->toDateString()]);
        IsEkipmani::create(['firma_id' => $firma->id, 'ekipman_adi' => 'Forklift', 'kategori' => 'kaldirma_iletme', 'aktif' => true]);

        $this->assertSame(['1 ramak kala olay', '1 yeni iş ekipmanı'], $rd->fresh()->revizyonGerekceleri());

        $gorev = GorevDurumu::gorevler($uzman)->firstWhere('baslik', 'Risk değerlendirmesi revizyonu');
        $this->assertNotNull($gorev);
        $this->assertStringContainsString('ramak kala', $gorev['aciklama']);
    }
}
