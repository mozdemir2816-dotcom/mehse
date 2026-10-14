<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\RiskSkorlama;
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
        $this->assertEquals(30.0, $madde->siddet);    // Matris 5 (ölüm) -> FK şiddet 30 (Ölüm)
        $this->assertEquals(1.0, $madde->son_olasilik); // Matris 2 -> FK olasılık 1
        $this->assertEquals(1.0, $madde->son_frekans);  // Matris 2 -> FK frekans 1
        $this->assertEquals(30.0, $madde->son_siddet);  // Matris 5 -> FK 30
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

    /** 0,1 / 0,2 / 0,5 → Matris 2 (kullanıcı kararı 14.10.2026, eski davranış). */
    public function test_fine_kinney_ondalik_olasiliklar_dogru_eslenir(): void
    {
        $this->assertSame([2, 1], RiskYontemDonusturucu::fineKinneyDegeriniMatrise(0.1, 1));
        $this->assertSame([2, 1], RiskYontemDonusturucu::fineKinneyDegeriniMatrise(0.2, 1));
        $this->assertSame([2, 1], RiskYontemDonusturucu::fineKinneyDegeriniMatrise(0.5, 1));
        $this->assertSame([2, 2], RiskYontemDonusturucu::fineKinneyDegeriniMatrise(1, 3));
        $this->assertSame([5, 5], RiskYontemDonusturucu::fineKinneyDegeriniMatrise(10, 40));
        $this->assertSame([5, 5], RiskYontemDonusturucu::fineKinneyDegeriniMatrise(10, 30)); // ölüm
    }

    public function test_ondalik_olasilik_kaydedilip_matrise_donusturulur(): void
    {
        $this->actingAs(User::factory()->create());
        $firma = Firma::factory()->create();
        $kaynak = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney', 'rapor_tarihi' => now()]);
        $kaynak->maddeler()->create(['tehlike' => 'Teorik', 'olasilik' => 0.2, 'frekans' => 1, 'siddet' => 7]);

        $this->assertEquals(0.2, $kaynak->maddeler()->first()->olasilik); // 2 değil

        $madde = RiskYontemDonusturucu::fineKinneydenMatrise($kaynak)->maddeler()->first();
        $this->assertEquals(2, $madde->olasilik);
        $this->assertEquals(3, $madde->siddet);
    }

    public function test_virgullu_puan_ondalik_okunur(): void
    {
        $this->assertSame(0.2, RiskSkorlama::sayi('0,2'));
        $this->assertSame(0.2, RiskSkorlama::sayi(' 0,2 '));
        $this->assertSame(0.5, RiskSkorlama::sayi('0.5'));
        $this->assertSame(0.1, RiskSkorlama::sayi(0.1));
        $this->assertSame(3.0, RiskSkorlama::sayi(3));
        $this->assertSame(10.0, RiskSkorlama::sayi('10'));
        $this->assertNull(RiskSkorlama::sayi('Yüksek'));
        $this->assertNull(RiskSkorlama::sayi(null));
        $this->assertNull(RiskSkorlama::sayi(''));
    }
}
