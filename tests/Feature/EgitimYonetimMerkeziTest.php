<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim;
use App\Filament\Pages\ToolboxKonusmalari;
use App\Models\User;
use App\Support\SayfaAileleri;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Eğitim & Yetkinlik Yönetim Merkezi: tek menü girişi ve 3 ana sekme. */
class EgitimYonetimMerkeziTest extends TestCase
{
    use RefreshDatabase;

    public function test_menude_tek_giris_ve_uc_ana_sekme(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'sahip']));

        $menu = collect(Filament::getNavigation());
        $this->assertNull($menu->first(fn ($g) => $g->getLabel() === 'Eğitimler'));
        $this->assertCount(1, $menu->flatMap(fn ($g) => $g->getItems())->filter(fn ($i) => $i->getLabel() === 'Eğitim Yönetim Merkezi'));
        $this->assertFalse(ToolboxKonusmalari::shouldRegisterNavigation());

        $g = SayfaAileleri::sekmeGruplari([ToolboxKonusmalari::class]);
        $this->assertSame('egitim', $g['aile']);
        $this->assertSame(
            ['Yasal Eğitim Takibi & Tutanaklar', 'Online Eğitim Yönet', 'Soru & İçerik Bankası'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame('Soru & İçerik Bankası', collect($g['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertCount(4, $g['alt']);

        $this->get(EgitimKatilim::getUrl())->assertSuccessful()->assertSee('Yasal Eğitim Takibi');
    }
}
