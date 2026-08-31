<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\CreateCalisan;
use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Filament\Widgets\PortfoyOzetiWidget;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirmaCalisanTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_firma_kaydedilince_user_id_oturumdan_gelir(): void
    {
        $firma = Firma::create(['unvan' => 'Deneme A.Ş.', 'tehlike_sinifi' => 'tehlikeli']);

        $this->assertSame($this->uzman->id, $firma->fresh()->user_id);
        $this->assertSame('Tehlikeli', $firma->tehlikeSinifiEtiketi());
        $this->assertSame(4, $firma->riskGecerlilikYili());
    }

    public function test_uzman_yalnizca_kendi_firmalarini_gorur(): void
    {
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Benim Firmam']);
        Firma::factory()->create(['unvan' => 'Baskasinin Firmasi']);

        Livewire::test(ListFirmas::class)
            ->assertCanSeeTableRecords(Firma::where('user_id', $this->uzman->id)->get())
            ->assertCountTableRecords(1);
    }

    public function test_firma_ve_calisan_sayfalari_acilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($firma)->create();

        Livewire::test(ListFirmas::class)->assertOk();
        Livewire::test(CreateFirma::class)->assertOk();
        Livewire::test(EditFirma::class, ['record' => $firma->getRouteKey()])->assertOk();
        Livewire::test(ListCalisans::class)->assertOk();
        Livewire::test(CreateCalisan::class)->assertOk();
    }

    public function test_portfoy_ozeti_widget_kendi_sayaclarini_verir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['aktif' => true]);
        Calisan::factory()->count(3)->for($firma)->create(['aktif' => true]);
        Firma::factory()->create(); // başka uzmanın firması

        Livewire::test(PortfoyOzetiWidget::class)
            ->assertSee('Aktif firma')
            ->assertSee('Çalışan');
    }
}
