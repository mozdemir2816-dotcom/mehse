<?php

namespace Tests\Feature;

use App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource;
use App\Filament\Resources\IsgProfesyonelis\Pages\ListIsgProfesyonelis;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IsgProfesyoneliTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_liste_ve_olusturma_sayfalari_acilir(): void
    {
        $this->get(IsgProfesyoneliResource::getUrl('index'))->assertSuccessful();
        $this->get(IsgProfesyoneliResource::getUrl('create'))->assertSuccessful();
    }

    public function test_kaydedilince_user_id_oturumdan_gelir(): void
    {
        $p = IsgProfesyoneli::create(['tip' => 'igu', 'ad_soyad' => 'Test Uzman']);

        $this->assertSame($this->uzman->id, $p->user_id);
    }

    public function test_baska_uzmanin_profesyoneli_listede_gorunmez(): void
    {
        $baskaUzman = User::factory()->create();
        IsgProfesyoneli::factory()->for($baskaUzman)->create();

        $this->assertSame(0, IsgProfesyoneliResource::getEloquentQuery()->count());
    }

    public function test_tip_etiketi_config_uzerinden_gelir(): void
    {
        $p = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'isyeri_hekimi']);

        $this->assertSame('İşyeri Hekimi', $p->tipEtiketi());
    }

    public function test_firmaya_profesyonel_atanir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu']);
        $firma = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]);

        $this->assertSame($igu->id, $firma->fresh()->igu->id);
    }

    public function test_firma_alani_tipe_gore_dogru_sutunu_dondurur(): void
    {
        $this->assertSame('igu_id', IsgProfesyoneli::factory()->make(['tip' => 'igu'])->firmaAlani());
        $this->assertSame('isyeri_hekimi_id', IsgProfesyoneli::factory()->make(['tip' => 'isyeri_hekimi'])->firmaAlani());
        $this->assertSame('dsp_id', IsgProfesyoneli::factory()->make(['tip' => 'dsp'])->firmaAlani());
    }

    public function test_firmalara_ata_action_toplu_atar_ve_secilmeyeni_kaldirir(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'igu']);
        $a = Firma::factory()->for($this->uzman)->create(['igu_id' => $igu->id]); // önceden atanmış, kaldırılacak
        $b = Firma::factory()->for($this->uzman)->create();
        $c = Firma::factory()->for($this->uzman)->create();
        $baskasininFirmasi = Firma::factory()->create();

        Livewire::test(ListIsgProfesyonelis::class)
            ->callTableAction('firmalaraAta', $igu, data: [
                'firma_idler' => [$b->id, $c->id],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertNull($a->fresh()->igu_id);
        $this->assertSame($igu->id, $b->fresh()->igu_id);
        $this->assertSame($igu->id, $c->fresh()->igu_id);
        $this->assertNull($baskasininFirmasi->fresh()->igu_id);
    }

    public function test_firmalara_ata_action_mevcut_atamalari_onceden_isaretler(): void
    {
        $hekim = IsgProfesyoneli::factory()->for($this->uzman)->create(['tip' => 'isyeri_hekimi']);
        $firma = Firma::factory()->for($this->uzman)->create(['isyeri_hekimi_id' => $hekim->id]);

        Livewire::test(ListIsgProfesyonelis::class)
            ->mountTableAction('firmalaraAta', $hekim)
            ->assertTableActionDataSet(['firma_idler' => [$firma->id]]);
    }
}
