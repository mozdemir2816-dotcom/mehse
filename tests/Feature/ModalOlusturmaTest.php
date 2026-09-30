<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\EgitimPaketis\Pages\ListEgitimPaketis;
use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Filament\Resources\IsgProfesyonelis\Pages\ListIsgProfesyonelis;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\ListRiskDegerlendirmesis;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Models\Calisan;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "+ Ekle" butonları ayrı oluştur sayfasına gitmek yerine modal açar.
 */
class ModalOlusturmaTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_ekle_butonlari_url_degil_modal_acar(): void
    {
        foreach ([ListFirmas::class, ListCalisans::class, ListIsgProfesyonelis::class, ListRiskDegerlendirmesis::class, ListTehlikes::class, ListEgitimPaketis::class] as $sayfa) {
            Livewire::test($sayfa)
                ->assertActionExists('create', fn (CreateAction $action): bool => $action->getUrl() === null)
                ->mountAction('create')
                ->assertActionMounted('create');
        }
    }

    public function test_calisan_modaldan_eklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        Livewire::test(ListCalisans::class)
            ->callAction('create', ['firma_id' => $firma->id, 'ad_soyad' => 'Ayşe Yılmaz'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas(Calisan::class, ['firma_id' => $firma->id, 'ad_soyad' => 'Ayşe Yılmaz']);
    }

    public function test_egitim_paketi_modaldan_eklenince_sahibi_oturumdaki_uzman(): void
    {
        Livewire::test(ListEgitimPaketis::class)
            ->callAction('create', ['ad' => 'Yüksekte Çalışma', 'dersler' => []])
            ->assertHasNoActionErrors();

        $this->assertSame($this->uzman->id, EgitimPaketi::query()->where('ad', 'Yüksekte Çalışma')->value('user_id'));
    }

    public function test_bos_risk_degerlendirmesi_modaldan_sonra_duzenleme_sayfasina_gider(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $test = Livewire::test(ListRiskDegerlendirmesis::class)
            ->callAction('create', ['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()->toDateString(), 'ekip' => []])
            ->assertHasNoActionErrors();

        $rd = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->firstOrFail();
        $test->assertRedirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd]));
    }

    public function test_url_ile_firma_ekle_modali_acilir(): void
    {
        // HosGeldinWidget "Firma Ekle" → /admin/firmas?action=create. Filament modalı
        // sayfa yüklenince tarayıcıda wire:init ile açar (sunucu testinde mount olmaz).
        Livewire::withQueryParams(['action' => 'create'])
            ->test(ListFirmas::class)
            ->assertSeeHtml("wire:init=\"mountAction('create'");
    }
}
