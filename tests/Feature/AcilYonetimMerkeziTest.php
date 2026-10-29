<?php

namespace Tests\Feature;

use App\Filament\Pages\AcilDurumPlani;
use App\Filament\Pages\TatbikatTutanagi;
use App\Filament\Pages\YanginGuvenligi;
use App\Models\Firma;
use App\Models\User;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Acil Durum & Yangın Yönetim Merkezi: tek menü girişi, 3 ana sekme, ekip sayacı. */
class AcilYonetimMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
    }

    public function test_menude_tek_giris_ve_uc_ana_sekme(): void
    {
        $grup = collect(Filament::getNavigation())->first(fn ($g) => $g->getLabel() === 'Acil Durum & Yangın');
        $this->assertNotNull($grup);
        $this->assertCount(1, $grup->getItems());
        $this->assertFalse(YanginGuvenligi::shouldRegisterNavigation());
        $this->assertFalse(TatbikatTutanagi::shouldRegisterNavigation());

        $g = SayfaAileleri::sekmeGruplari([TatbikatTutanagi::class]);
        $this->assertSame('acil', $g['aile']);
        $this->assertCount(3, $g['ust']);
        $this->assertSame('Tatbikat Tutanağı & Senaryolar', collect($g['ust'])->firstWhere('aktif', true)['etiket']);
    }

    public function test_ekip_sayaci_yasal_asgariyi_ve_eksigi_gosterir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 60]);

        $sayfa = Livewire::test(AcilDurumPlani::class, ['firmaId' => $firma->id])
            ->set('ekipMetni.sondurme', 'Ali Veli');

        $s = $sayfa->instance()->ekipSayaclari();
        $this->assertSame(['gereken' => 2, 'atanan' => 1, 'eksik' => 1], $s['sondurme']);
        $this->assertSame(6, $s['ilk_yardim']['gereken']);
        $sayfa->assertSee('1 eksik');
    }
}
