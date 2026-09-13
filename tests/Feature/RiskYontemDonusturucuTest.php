<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\RiskYontemDonusturucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskYontemDonusturucuTest extends TestCase
{
    use RefreshDatabase;

    public function test_matristen_fine_kinneye_donusturulur(): void
    {
        $this->actingAs(User::factory()->create());
        $firma = Firma::factory()->create();
        $kaynak = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $kaynak->maddeler()->create([
            'tehlike' => 'Korkuluksuz kenar', 'olasilik' => 4, 'siddet' => 5,
            'son_olasilik' => 2, 'son_siddet' => 5,
        ]);

        $yeni = RiskYontemDonusturucu::matristenFineKinneye($kaynak);

        $this->assertSame('fine_kinney', $yeni->yontem);
        $this->assertSame($firma->id, $yeni->firma_id);

        $madde = $yeni->maddeler()->first();
        $this->assertSame('Korkuluksuz kenar', $madde->tehlike);
        $this->assertEquals(6.0, $madde->olasilik);   // Matris 4 -> FK olasılık 6
        $this->assertEquals(6.0, $madde->frekans);    // Matris 4 -> FK frekans 6
        $this->assertEquals(40.0, $madde->siddet);    // Matris 5 -> FK şiddet 40
        $this->assertEquals(1.0, $madde->son_olasilik); // Matris 2 -> FK olasılık 1
        $this->assertEquals(1.0, $madde->son_frekans);  // Matris 2 -> FK frekans 1
        $this->assertEquals(40.0, $madde->son_siddet);  // Matris 5 -> FK şiddet 40
        $this->assertNotNull($madde->puan);
        $this->assertNotNull($madde->duzey);

        // Kaynak değişmemiş olmalı.
        $kaynak->refresh();
        $this->assertSame('matris_5x5', $kaynak->yontem);
    }

    public function test_fine_kinneyden_matrise_donusturulur(): void
    {
        $this->actingAs(User::factory()->create());
        $firma = Firma::factory()->create();
        $kaynak = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $kaynak->maddeler()->create([
            'tehlike' => 'Gürültü', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 15,
        ]);

        $yeni = RiskYontemDonusturucu::fineKinneydenMatrise($kaynak);

        $this->assertSame('matris_5x5', $yeni->yontem);

        $madde = $yeni->maddeler()->first();
        $this->assertEquals(4, $madde->olasilik); // FK 6 -> Matris 4
        $this->assertNull($madde->frekans);       // Matris'te frekans yok
        $this->assertEquals(4, $madde->siddet);   // FK 15 -> Matris 4
        $this->assertNotNull($madde->puan);
    }

    public function test_matris_fk_matris_gidip_gelince_ayni_deger_doner(): void
    {
        $this->actingAs(User::factory()->create());
        $firma = Firma::factory()->create();
        $kaynak = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $kaynak->maddeler()->create(['tehlike' => 'X', 'olasilik' => 3, 'siddet' => 2]);

        $fk = RiskYontemDonusturucu::matristenFineKinneye($kaynak);
        $geriDonus = RiskYontemDonusturucu::fineKinneydenMatrise($fk);

        $madde = $geriDonus->maddeler()->first();
        $this->assertEquals(3, $madde->olasilik);
        $this->assertEquals(2, $madde->siddet);
    }
}
