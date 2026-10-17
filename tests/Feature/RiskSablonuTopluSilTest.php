<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskSablonus\Pages\ListRiskSablonus;
use App\Models\RiskSablonu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiskSablonuTopluSilTest extends TestCase
{
    use RefreshDatabase;

    private function sablon(User $sahip, array $ozellik = []): RiskSablonu
    {
        return RiskSablonu::create(array_merge([
            'user_id' => $sahip->id,
            'ad' => 'Şablon',
            'sektor' => 'insaat',
            'yontem' => 'matris_5x5',
            'maddeler' => [],
        ], $ozellik));
    }

    public function test_grup_anahtari_sektor_kodu_ve_serbest_metni_ayirir(): void
    {
        $sahip = User::factory()->create();

        $this->assertSame('insaat', $this->sablon($sahip)->grupAnahtari());
        $this->assertSame(
            'Kazı Çalışmaları',
            $this->sablon($sahip, ['sektor' => null, 'sektor_adi' => ' Kazı Çalışmaları '])->grupAnahtari(),
        );
    }

    public function test_ayni_sektor_farkli_yazilmis_serbest_metinler_tek_gruba_girer(): void
    {
        $sahip = User::factory()->create();
        $this->sablon($sahip, ['sektor' => null, 'sektor_adi' => 'Kazı Çalışmaları']);
        $this->sablon($sahip, ['sektor' => null, 'sektor_adi' => ' Kazı Çalışmaları ']);
        $this->sablon($sahip, ['sektor' => 'insaat']);
        $this->sablon($sahip, ['sektor' => null, 'sektor_adi' => null]);

        $gruplar = RiskSablonu::query()->selectRaw(RiskSablonu::GRUP_IFADESI.' as grup')
            ->groupBy(\DB::raw(RiskSablonu::GRUP_IFADESI))
            ->pluck('grup');

        $this->assertCount(3, $gruplar);
        $this->assertContains('insaat', $gruplar);
    }

    public function test_sektor_listesi_gruplu_olarak_render_olur(): void
    {
        $sahip = User::factory()->create();
        $this->sablon($sahip, ['sektor' => null, 'sektor_adi' => 'Kazı Çalışmaları']);
        $this->actingAs($sahip);

        Livewire::test(ListRiskSablonus::class)->assertSee('Kazı Çalışmaları');
    }

    public function test_benim_gruplari_yalniz_kullanicinin_sablonlarini_sayar(): void
    {
        $sahip = User::factory()->create();
        $baskasi = User::factory()->create();

        $this->sablon($sahip);
        $this->sablon($sahip);
        $this->sablon($baskasi);

        $gruplar = RiskSablonu::benimGruplari($sahip->id);

        $this->assertCount(1, $gruplar);
        $this->assertStringContainsString('(2 şablon)', $gruplar->first());
    }

    public function test_top_lu_sil_baskasinin_sablonuna_dokunmaz(): void
    {
        $sahip = User::factory()->create();
        $baskasi = User::factory()->create();

        $benim = $this->sablon($sahip);
        $baskaninki = $this->sablon($baskasi, ['paylasildi' => true]);

        $sayi = RiskSablonu::topluSil(RiskSablonu::all(), $sahip->id);

        $this->assertSame(1, $sayi);
        $this->assertNull(RiskSablonu::find($benim->id));
        $this->assertNotNull(RiskSablonu::find($baskaninki->id));
    }

    public function test_grubaGoreSil_yalniz_secilen_grubu_siler(): void
    {
        $sahip = User::factory()->create();
        $this->sablon($sahip, ['sektor' => 'insaat']);
        $this->sablon($sahip, ['sektor' => 'insaat']);
        $diger = $this->sablon($sahip, ['sektor' => 'lojistik']);

        $this->actingAs($sahip);
        Livewire::test(ListRiskSablonus::class)
            ->callAction('grubaGoreSil', ['grup' => 'insaat']);

        $this->assertSame(0, RiskSablonu::where('sektor', 'insaat')->count());
        $this->assertNotNull(RiskSablonu::find($diger->id));
    }

    public function test_secilenleri_sil_yalniz_sahibinin_kayitlarini_siler(): void
    {
        $sahip = User::factory()->create();
        $baskasi = User::factory()->create();

        $benim = $this->sablon($sahip);
        $baskaninki = $this->sablon($baskasi, ['paylasildi' => true]);

        $this->actingAs($sahip);
        Livewire::test(ListRiskSablonus::class)
            ->callTableBulkAction('secileniSil', [$benim, $baskaninki]);

        $this->assertNull(RiskSablonu::find($benim->id));
        $this->assertNotNull(RiskSablonu::find($baskaninki->id));
    }
}
