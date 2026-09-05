<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskSablonus\Pages\EditRiskSablonu;
use App\Models\RiskSablonu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiskSablonuResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_maddeler_repeater_ile_yeni_madde_eklenip_kaydedilir(): void
    {
        $sablon = RiskSablonu::olustur($this->uzman, 'Test Şablonu', 'fabrika', null, 'matris_5x5', []);

        Livewire::test(EditRiskSablonu::class, ['record' => $sablon->getRouteKey()])
            ->fillForm([
                'maddeler' => [
                    ['bolum' => 'Atölye', 'faaliyet' => 'Kaynak', 'tehlike' => 'Kaynak dumanı', 'risk' => 'Solunum yolu hastalığı', 'mevcut_onlem' => 'Maske', 'olasilik' => 3, 'siddet' => 3, 'oneri' => null, 'sorumlu' => null, 'termin' => null],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $sablon->refresh();
        $this->assertCount(1, $sablon->maddeler);
        $this->assertSame('Kaynak dumanı', $sablon->maddeler[0]['tehlike']);
    }

    public function test_mevcut_madde_duzenlenir(): void
    {
        $sablon = RiskSablonu::olustur($this->uzman, 'Test Şablonu', 'fabrika', null, 'matris_5x5', [
            ['bolum' => 'Depo', 'faaliyet' => 'İstifleme', 'tehlike' => 'Raf devrilmesi', 'risk' => 'Ezilme', 'mevcut_onlem' => null, 'olasilik' => 2, 'siddet' => 3],
        ]);

        Livewire::test(EditRiskSablonu::class, ['record' => $sablon->getRouteKey()])
            ->fillForm([
                'maddeler' => [
                    ['bolum' => 'Depo', 'faaliyet' => 'İstifleme', 'tehlike' => 'Raf devrilmesi (güncellendi)', 'risk' => 'Ezilme', 'mevcut_onlem' => 'Raf sabitleme', 'olasilik' => 1, 'siddet' => 3],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $sablon->refresh();
        $this->assertSame('Raf devrilmesi (güncellendi)', $sablon->maddeler[0]['tehlike']);
        $this->assertSame('Raf sabitleme', $sablon->maddeler[0]['mevcut_onlem']);
    }

    public function test_madde_silinir(): void
    {
        $sablon = RiskSablonu::olustur($this->uzman, 'Test Şablonu', 'fabrika', null, 'matris_5x5', [
            ['bolum' => 'A', 'faaliyet' => null, 'tehlike' => 'Tehlike 1', 'risk' => null, 'mevcut_onlem' => null, 'olasilik' => 1, 'siddet' => 1],
            ['bolum' => 'B', 'faaliyet' => null, 'tehlike' => 'Tehlike 2', 'risk' => null, 'mevcut_onlem' => null, 'olasilik' => 1, 'siddet' => 1],
        ]);

        Livewire::test(EditRiskSablonu::class, ['record' => $sablon->getRouteKey()])
            ->fillForm([
                'maddeler' => [
                    ['bolum' => 'A', 'faaliyet' => null, 'tehlike' => 'Tehlike 1', 'risk' => null, 'mevcut_onlem' => null, 'olasilik' => 1, 'siddet' => 1],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $sablon->refresh();
        $this->assertCount(1, $sablon->maddeler);
    }

    public function test_baskasinin_sablonunu_duzenleyemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaSablon = RiskSablonu::olustur($baskaUzman, 'Başkasının Şablonu', 'fabrika', null, 'matris_5x5', []);
        $baskaSablon->update(['paylasildi' => true]); // paylaşılmadıysa zaten görünmez (404); paylaşılınca "görünür ama düzenlenemez" (403) test edilir

        Livewire::test(EditRiskSablonu::class, ['record' => $baskaSablon->getRouteKey()])
            ->assertForbidden();
    }
}
