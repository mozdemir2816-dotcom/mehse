<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\FirmaResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GorselTemaSeciciTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_sayfasi_tema_seciciyi_ve_erken_tema_scriptini_icerir(): void
    {
        $this->actingAs(User::factory()->create());

        $yanit = $this->get(FirmaResource::getUrl('index'));

        $yanit->assertOk();
        // İlk boyamadan önce <html data-mehse-tema> basan script.
        $yanit->assertSee('window.mehseTemaUygula', escape: false);
        // Topbar'daki seçici ve 4 tema seçeneği.
        $yanit->assertSee('fi-mehse-tema-secenek', escape: false);
        $yanit->assertSeeInOrder(['Klasik', 'Google Material', 'Windows 11', 'Saha']);
    }
}
