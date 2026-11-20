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
        // Topbar'daki seçici: Pano varsayılan; MEHSE adlı temalar yok.
        $yanit->assertSee('"tema":"pano"', escape: false);
        $yanit->assertSee('fi-mehse-tema-secenek', escape: false);
        $yanit->assertSeeInOrder(['Modern Kurumsal (Varsayılan)', 'Klasik', 'Google Material', 'Windows 11', 'Saha']);
        $yanit->assertDontSee('MEHSE Modern', escape: false);
    }
}
