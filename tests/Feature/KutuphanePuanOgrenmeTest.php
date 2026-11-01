<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Puansız kütüphane maddesi ilk kez puanlanınca puan kütüphaneye yazılır (yalnız boşsa, yalnız sahip). */
class KutuphanePuanOgrenmeTest extends TestCase
{
    use RefreshDatabase;

    private function tehlike(array $ek = []): Tehlike
    {
        $kategori = TehlikeKategorisi::create(['anahtar' => 'test_kat', 'ad' => 'Test', 'sira' => 1]);

        return Tehlike::create(array_merge(['tehlike_kategorisi_id' => $kategori->id, 'kod' => 'T-1', 'tehlike' => 'Zemin kayganlığı'], $ek));
    }

    private function madde(User $user, Tehlike $t, string $yontem, array $puan): RiskMaddesi
    {
        $this->actingAs($user);
        $firma = Firma::factory()->for($user)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => $yontem, 'rapor_tarihi' => now()]);

        return RiskMaddesi::create(array_merge(['risk_degerlendirmesi_id' => $rd->id, 'tehlike_id' => $t->id, 'tehlike' => $t->tehlike], $puan));
    }

    public function test_fine_kinney_puani_bir_kez_ogrenilir_ustune_yazilmaz(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip']);
        $t = $this->tehlike();

        $this->madde($sahip, $t, 'fine_kinney', ['olasilik' => 3, 'frekans' => 6, 'siddet' => 15, 'son_olasilik' => 0.5]);
        $t->refresh();
        $this->assertSame([3.0, 6.0, 15.0, 0.5, 6.0, 15.0], array_map('floatval', [$t->olasilik, $t->frekans, $t->siddet, $t->son_olasilik, $t->son_frekans, $t->son_siddet]));

        // İkinci puanlama kütüphaneyi değiştirmez
        $this->madde($sahip, $t, 'fine_kinney', ['olasilik' => 10, 'frekans' => 10, 'siddet' => 100]);
        $this->assertEquals(3, $t->refresh()->olasilik);
    }

    public function test_5x5_puani_matrise_yazilir_ve_sihirbazda_geri_gelir(): void
    {
        $sahip = User::factory()->create(['rol' => 'sahip']);
        $t = $this->tehlike();

        $this->madde($sahip, $t, 'matris_5x5', ['olasilik' => 4, 'siddet' => 3, 'son_olasilik' => 2]);
        $t->refresh();
        $this->assertSame(['olasilik' => 4, 'siddet' => 3, 'son_olasilik' => 2, 'son_siddet' => 3], $t->matris);
        $this->assertNull($t->olasilik);

        $madde = \App\Support\RiskKutuphanesi::maddeyeCevir($t, 'matris_5x5');
        $this->assertSame([4, 3, 2, 3], [$madde['olasilik'], $madde['siddet'], $madde['son_olasilik'], $madde['son_siddet']]);
    }

    public function test_sahip_olmayan_ve_dolu_kutuphane_maddesi_degismez(): void
    {
        $uzman = User::factory()->create(['rol' => 'uzman']);
        $bos = $this->tehlike();
        $this->madde($uzman, $bos, 'fine_kinney', ['olasilik' => 3, 'frekans' => 6, 'siddet' => 15]);
        $this->assertNull($bos->refresh()->olasilik);

        // FK puanı olan madde 5x5'te de dönüştürülerek dolu gelir → puansız sayılmaz
        $sahip = User::factory()->create(['rol' => 'sahip']);
        $dolu = Tehlike::create(['tehlike_kategorisi_id' => $bos->tehlike_kategorisi_id, 'kod' => 'T-2', 'tehlike' => 'Düşme', 'olasilik' => 6, 'frekans' => 3, 'siddet' => 40]);
        $this->madde($sahip, $dolu, 'matris_5x5', ['olasilik' => 1, 'siddet' => 1]);
        $this->assertNull($dolu->refresh()->matris);
    }
}
