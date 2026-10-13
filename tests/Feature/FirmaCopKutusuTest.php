<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Firmalar Çöp Kutusu (13.10.2026): sil → 3 ay geri alınabilir → süre dolunca kalıcı silinir. */
class FirmaCopKutusuTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Yakup Meral']);
        Calisan::factory()->for($this->firma)->count(2)->create();
    }

    public function test_silinen_firma_cop_kutusuna_duser_kayitlari_korunur(): void
    {
        Livewire::test(ListFirmas::class)->callTableAction(DeleteAction::class, $this->firma);

        $this->assertSoftDeleted($this->firma);
        $this->assertSame(2, Calisan::where('firma_id', $this->firma->id)->count());

        Livewire::test(ListFirmas::class)->assertCanNotSeeTableRecords([$this->firma]);
        Livewire::test(ListFirmas::class)->set('activeTab', 'cop')->assertCanSeeTableRecords([$this->firma]);

        // Silinen firmanın çalışanları listelerde görünmez.
        Livewire::test(ListCalisans::class)->assertCountTableRecords(0);
    }

    public function test_duzenleme_sayfasindan_silinince_de_cop_kutusuna_duser(): void
    {
        Livewire::test(EditFirma::class, ['record' => $this->firma->getRouteKey()])->callAction(DeleteAction::class);

        $this->assertSoftDeleted($this->firma);
    }

    public function test_pasif_firma_da_cop_kutusunda_gorunur(): void
    {
        $this->firma->update(['aktif' => false]);
        $this->firma->delete();

        Livewire::test(ListFirmas::class)->set('activeTab', 'cop')->assertCanSeeTableRecords([$this->firma]);
    }

    public function test_cop_kutusundan_geri_alinir(): void
    {
        $this->firma->delete();

        Livewire::test(ListFirmas::class)->set('activeTab', 'cop')->callTableAction('restore', $this->firma);

        $this->assertNotSoftDeleted($this->firma);
        Livewire::test(ListCalisans::class)->assertCountTableRecords(2);
    }

    public function test_kalici_sil_alt_kayitlari_da_siler(): void
    {
        $this->firma->delete();

        Livewire::test(ListFirmas::class)->set('activeTab', 'cop')->callTableAction('forceDelete', $this->firma);

        $this->assertModelMissing($this->firma);
        $this->assertSame(0, Calisan::where('firma_id', $this->firma->id)->count());
    }

    public function test_uc_ayi_dolan_firma_liste_acilinca_kalici_silinir(): void
    {
        $eski = Firma::factory()->for($this->uzman)->create(['unvan' => 'Eski Firma']);
        $eski->delete();
        $eski->forceFill(['deleted_at' => now()->subMonths(Firma::COP_SURESI_AY)->subDay()])->saveQuietly();

        $this->firma->delete();
        $this->firma->forceFill(['deleted_at' => now()->subMonths(Firma::COP_SURESI_AY)->addDays(5)])->saveQuietly();

        Livewire::test(ListFirmas::class);

        $this->assertModelMissing($eski);
        $this->assertSoftDeleted($this->firma);
        $this->assertSame(5, $this->firma->fresh()->copteKalanGun());
    }
}
