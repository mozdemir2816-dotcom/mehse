<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\User;
use App\Support\HazopCiktisi;
use App\Support\RiskDegerlendirmesiOfisUretici;
use App\Support\RiskDegerlendirmesiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HazopCiktiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function hazopDegerlendirmesi(): RiskDegerlendirmesi
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'hazop', 'rapor_tarihi' => now()]);

        $rd->maddeler()->create([
            'sira' => 1,
            'bolum' => 'N1 — Silo dolumu',
            'faaliyet' => 'Basınç / FAZLA',
            'tehlike' => 'Silo basıncı tasarım sınırını aşar',
            'risk' => 'Neden: Filtre tıkanması',
            'oneri' => '[P1] Tahliye kapasitesini doğrulayın.',
            'hazop' => [
                'dugum' => 'N1 — Silo dolumu',
                'parametre' => 'Basınç',
                'kilavuz' => 'FAZLA',
                'sapma' => 'Silo basıncı tasarım sınırını aşar',
                'neden' => 'Filtre tıkanması',
                'sonuc' => 'Gövde hasarı, ölüm',
                'korumalar' => 'Basınç alarmı, tahliye valfi',
                'oneri' => 'Tahliye kapasitesini doğrulayın.',
                'oncelik' => 'P1',
                'sorumlu' => 'Üretim / bakım',
                'termin' => 'Atanacak',
                'kapanis' => '',
                'durum' => 'Açık',
            ],
        ]);

        return $rd->fresh();
    }

    public function test_sutun_duzeni_kaynak_excel_ile_ayni_ve_puansiz(): void
    {
        $basliklar = array_column(HazopCiktisi::sutunlar(), 0);

        $this->assertSame([
            'No', 'Düğüm', 'Parametre', 'Kılavuz kelime', 'Sapma', 'Olası nedenler', 'Olası sonuçlar',
            'Doğrulanacak korumalar', 'Öneri / aksiyon', 'Öncelik', 'Sorumlu rol', 'Hedef tarih', 'Kapanış kanıtı', 'Durum',
        ], $basliklar);

        // Genişlikler yatay A4 tablosuna sığmalı.
        $this->assertEqualsWithDelta(100.0, array_sum(array_column(HazopCiktisi::sutunlar(), 3)), 0.01);
    }

    public function test_excel_hazop_tek_sayfa_ve_kaynak_sutunlari(): void
    {
        $kitap = RiskDegerlendirmesiOfisUretici::excelKitabi($this->hazopDegerlendirmesi());

        $this->assertSame(['Risk Değerlendirmesi'], $kitap->getSheetNames());

        $sayfa = $kitap->getSheetByName('Risk Değerlendirmesi');
        $baslikSatiri = null;
        for ($r = 1; $r <= 30 && $baslikSatiri === null; $r++) {
            if ($sayfa->getCell("A{$r}")->getValue() === 'No') {
                $baslikSatiri = $r;
            }
        }
        $this->assertNotNull($baslikSatiri);

        $this->assertSame(
            array_column(HazopCiktisi::sutunlar(), 0),
            array_map(fn ($c) => $sayfa->getCell([$c, $baslikSatiri])->getValue(), range(1, 14)),
        );

        $this->assertSame('Basınç', $sayfa->getCell([3, $baslikSatiri + 1])->getValue());
        $this->assertSame('Gövde hasarı, ölüm', $sayfa->getCell([7, $baslikSatiri + 1])->getValue());
    }

    public function test_pdf_hazop_tablosu_puan_sutunu_icermez(): void
    {
        $rd = $this->hazopDegerlendirmesi();
        $veri = RiskDegerlendirmesiUretici::veri($rd);

        $html = view('pdf.risk-degerlendirmesi', $veri + ['bolum' => 'yatay'])->render();

        $this->assertStringContainsString('Doğrulanacak korumalar', $html);
        $this->assertStringContainsString('Gövde hasarı, ölüm', $html);
        $this->assertStringNotContainsString('>Puan<', $html);
        $this->assertStringNotContainsString('>Son Puan<', $html);
        $this->assertStringNotContainsString('Düzey kısaltmaları', $html);

        $dikey = view('pdf.risk-degerlendirmesi', $veri + ['bolum' => 'dikey'])->render();
        $this->assertStringNotContainsString('2. DEĞERLENDİRME YÖNTEMİ', $dikey);
    }

    public function test_word_hazop_uretilir(): void
    {
        $belge = RiskDegerlendirmesiOfisUretici::wordBelgesi($this->hazopDegerlendirmesi());

        $this->assertNotNull($belge);
    }
}
