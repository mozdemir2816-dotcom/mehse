<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\RelationManagers\MaddelerRelationManager;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 01.11.2026 (FOM MAKİNA RD-2026-122 bulguları): Fine-Kinney'de önlem sonrası puan ilkinden yüksek çıkmasın,
 * boş termin risk düzeyine göre dolsun; manuel ekipman klasörleri Hızlı Kurulum'da, mevcut analize klasör
 * bazında mükerrersiz toplu ekleme.
 */
class RiskVarsayilanSonPuanTerminTest extends TestCase
{
    use RefreshDatabase;

    private function rd(string $yontem = 'fine_kinney', ?User $user = null): RiskDegerlendirmesi
    {
        $user ??= User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($user);
        $firma = Firma::factory()->for($user)->create(['nace_kodu' => '28.41.03']);

        return RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => $yontem, 'rapor_tarihi' => now()]);
    }

    public function test_fine_kinney_son_puan_ilkinden_yuksek_cikmaz(): void
    {
        $rd = $this->rd();
        $m = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Topraklama', 'olasilik' => 0.5, 'frekans' => 3, 'siddet' => 15]);
        $this->assertEquals(22.5, $m->puan);
        $this->assertEquals(0.2, $m->son_olasilik);
        $this->assertEquals(9, $m->son_puan);

        $bir = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Yangın', 'olasilik' => 1, 'frekans' => 6, 'siddet' => 15]);
        $this->assertEquals(0.5, $bir->son_olasilik);
        $this->assertLessThan($bir->puan, $bir->son_puan);

        $yuksek = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Ezilme', 'olasilik' => 6, 'frekans' => 6, 'siddet' => 40]);
        $this->assertEquals(0.2, $yuksek->son_olasilik);   // 1440 → 48: önlem sonrası 70'in altına iner

        // Madde 50 (bant zımpara gövde kaçağı): 3×6×40=720 → eskiden 1×6×40=240 (Yüksek kalıyordu) → 0,2 → 48
        $zimpara = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Gövde kaçağı', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 40]);
        $this->assertEquals(0.2, $zimpara->son_olasilik);
        $this->assertEquals(48, $zimpara->son_puan);
        $this->assertSame('Olası Risk', $zimpara->son_duzey);

        // Madde 20 (yangın): 3×6×10=180 → 1×6×10=60
        $this->assertEquals(60, RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Yangın', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 10])->son_puan);

        // Elle girilen son olasılık korunur
        $elle = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'X', 'olasilik' => 0.5, 'frekans' => 3, 'siddet' => 15, 'son_olasilik' => 0.5]);
        $this->assertEquals(0.5, $elle->son_olasilik);
    }

    public function test_bos_termin_risk_duzeyine_gore_dolar(): void
    {
        $rd = $this->rd();
        $yeni = fn (array $a) => RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Ezilme'] + $a);

        $this->assertSame('Derhal / 3 Gün', $yeni(['olasilik' => 6, 'frekans' => 6, 'siddet' => 40])->termin); // 1440 ÇY
        $this->assertSame('1 Ay', $yeni(['olasilik' => 3, 'frekans' => 6, 'siddet' => 15])->termin);           // 270 YK
        $this->assertSame('3 Ay', $yeni(['olasilik' => 1, 'frekans' => 6, 'siddet' => 15])->termin);           // 90 ÖN
        $this->assertSame('SÜREKLİ', $yeni(['olasilik' => 1, 'frekans' => 2, 'siddet' => 7])->termin);         // 14 KE
        $this->assertSame('SÜREKLİ', $yeni(['olasilik' => 6, 'frekans' => 6, 'siddet' => 40, 'oneri' => 'Operatörlere eğitim verilmeli'])->termin);
        $this->assertSame('15.12.2026', $yeni(['olasilik' => 6, 'frekans' => 6, 'siddet' => 40, 'termin' => '15.12.2026'])->termin);

        $matris = $this->rd('matris_5x5');
        $this->assertSame('1 Ay', RiskMaddesi::create(['risk_degerlendirmesi_id' => $matris->id, 'tehlike' => 'A', 'olasilik' => 4, 'siddet' => 4])->termin);
        $this->assertSame('3 Ay', RiskMaddesi::create(['risk_degerlendirmesi_id' => $matris->id, 'tehlike' => 'B', 'olasilik' => 3, 'siddet' => 3])->termin);
    }

    public function test_hizli_kurulumda_manueldeki_diger_ekipmanlar_ayri_kutu_olarak_gelir(): void
    {
        $kat = fn (string $anahtar, string $ad, ?string $yontem = 'fine_kinney', ?array $nace = ['metal']) => tap(
            TehlikeKategorisi::create(['anahtar' => $anahtar, 'ad' => $ad, 'yontem' => $yontem, 'nace_gruplari' => $nace, 'sira' => 1]),
            fn ($k) => Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => $anahtar.'-1', 'tehlike' => $ad.' tehlikesi', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 15]),
        );
        $kat('ekipman_torna', 'Ekipman – Torna');                       // config kutusunda → ayrıca listelenmez
        $kat('ekipman_sutunlu_pergel_vinc', 'Ekipman – Sütunlu pergel vinç');
        $kat('atolye_kesme_tezgahi', 'Atölye – Kesme tezgahı', null, null);
        $kat('ekipman_kule_vinc', 'Ekipman – Kule vinç', 'fine_kinney', ['insaat']); // NACE uymaz
        $kat('ekipman_tank_kaynak', 'Ekipman – Tank kaynak', 'matris_5x5', ['ortak']); // yöntem uymaz

        $user = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($user);
        $firma = Firma::factory()->for($user)->create(['tehlike_sinifi' => 'tehlikeli', 'nace_kodu' => '28.41.03']);

        $s = Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)->set('yontem', 'fine_kinney')->call('ileri')
            ->call('yontemSec', 'hizli')->call('ileri');

        $ekipmanlar = $s->instance()->hizliGruplar['ekipmanlar'];
        $digerler = collect($ekipmanlar)->filter(fn ($e) => $e['diger'] ?? false);
        $this->assertSame(['Kesme tezgahı', 'Sütunlu pergel vinç'], $digerler->pluck('ad')->values()->all());
        $this->assertArrayNotHasKey('kat_ekipman_torna', $ekipmanlar);
        $this->assertSame(1, $ekipmanlar['torna']['adet']);

        $s->assertSee('Kütüphanedeki diğer ekipman ve prosesler (2)')
            ->call('hizliEkipmanToggle', 'kat_ekipman_sutunlu_pergel_vinc')
            ->call('ekipmanRiskleriAktar');
        $this->assertSame(['Ekipman – Sütunlu pergel vinç tehlikesi'], array_column($s->get('secilenler'), 'tehlike'));
        $this->assertSame(1, $s->instance()->hizliGruplar['ekipmanlar']['kat_ekipman_sutunlu_pergel_vinc']['eklenen']);
    }

    public function test_mevcut_analize_klasor_bazinda_mukerrersiz_toplu_ekleme(): void
    {
        $k = TehlikeKategorisi::create(['anahtar' => 'ekipman_kompresor_ve_hava_tanki', 'ad' => 'Ekipman – Kompresör', 'yontem' => 'fine_kinney', 'nace_gruplari' => ['metal'], 'sira' => 1]);
        $t1 = Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => 'K-1', 'tehlike' => 'Tank patlaması', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 40]);
        Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => 'K-2', 'tehlike' => 'Emniyet ventili arızası', 'olasilik' => 1, 'frekans' => 6, 'siddet' => 40]);

        $rd = $this->rd();
        RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike_id' => $t1->id, 'tehlike' => 'Tank patlaması', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 40]);

        Livewire::test(MaddelerRelationManager::class, ['ownerRecord' => $rd, 'pageClass' => EditRiskDegerlendirmesi::class])
            ->callTableAction('kutuphanedenAktar', data: ['kategori_idler' => [$k->id]])
            ->assertHasNoTableActionErrors()
            ->assertNotified('1 risk maddesi eklendi');

        $this->assertSame(['Tank patlaması', 'Emniyet ventili arızası'], $rd->maddeler()->orderBy('id')->pluck('tehlike')->all());
    }
}
