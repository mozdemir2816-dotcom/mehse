<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\KurulToplantisi;
use App\Models\User;
use App\Support\KurulToplantisiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kurul tutanağı — katılım özeti satırı ve sayfa numaralı PDF (isgsuite karşılaştırması). */
class KurulTutanakKatilimTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutanakta_katilim_ozeti_ve_pdf_uretilir(): void
    {
        $firma = Firma::factory()->for(User::factory())->create();
        $toplanti = KurulToplantisi::create([
            'firma_id' => $firma->id, 'tarih' => '2026-09-16', 'tur' => 'olagan', 'durum' => 'taslak',
            'katilimcilar' => [
                ['ad_soyad' => 'A', 'gorev' => 'x', 'katildi' => true],
                ['ad_soyad' => 'B', 'gorev' => 'y', 'katildi' => true],
                ['ad_soyad' => 'C', 'gorev' => 'z', 'katildi' => false],
            ],
            'gundem' => [], 'kararlar' => [],
        ]);

        $html = view('pdf.kurul-toplantisi', ['toplanti' => $toplanti, 'firma' => $firma])->render();
        $this->assertStringContainsString('Katılan: 2 / 3 kişi · Katılmayan: 1', $html);

        ob_start();
        KurulToplantisiUretici::pdf($toplanti)->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }
}
