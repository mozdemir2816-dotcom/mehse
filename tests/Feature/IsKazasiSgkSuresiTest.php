<?php

namespace Tests\Feature;

use App\Models\IsKazasiRaporu;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** O11 — kaza raporunda SGK bildirimi için 3 iş günü son gün ve gecikme işareti. */
class IsKazasiSgkSuresiTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_son_gun_hafta_sonunu_atlar(): void
    {
        $r = new IsKazasiRaporu(['kaza_tarihi' => '2026-11-19']); // Perşembe

        $this->assertSame('2026-11-24', $r->sgkSonTarih()->toDateString()); // Cuma, Pzt, Salı
    }

    public function test_suresinde_bildirim_gecikme_sayilmaz(): void
    {
        $r = new IsKazasiRaporu(['kaza_tarihi' => '2026-11-19', 'sgk_bildirimi_yapildi' => true, 'sgk_bildirim_tarihi' => '2026-11-24']);

        $this->assertFalse($r->sgkBildirimiGecikti());
    }

    public function test_gec_bildirim_gecikme_sayilir(): void
    {
        $r = new IsKazasiRaporu(['kaza_tarihi' => '2026-11-19', 'sgk_bildirimi_yapildi' => true, 'sgk_bildirim_tarihi' => '2026-11-25']);

        $this->assertTrue($r->sgkBildirimiGecikti());
    }

    public function test_bildirilmemis_ve_sure_gecmis(): void
    {
        Carbon::setTestNow('2026-11-26');
        $r = new IsKazasiRaporu(['kaza_tarihi' => '2026-11-19', 'sgk_bildirimi_yapildi' => false]);

        $this->assertTrue($r->sgkBildirimiGecikti());
    }

    public function test_kaza_tarihi_yoksa_son_gun_yok(): void
    {
        $r = new IsKazasiRaporu();

        $this->assertNull($r->sgkSonTarih());
        $this->assertFalse($r->sgkBildirimiGecikti());
    }
}
