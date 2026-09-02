<?php

namespace Tests\Feature;

use App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
