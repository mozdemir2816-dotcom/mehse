<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\FirmaResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GorselTemaSeciciTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_sayfasi_erken_tema_scriptini_icerir_tema_secici_yok(): void
    {
        $this->actingAs(User::factory()->create());

        $yanit = $this->get(FirmaResource::getUrl('index'));

        $yanit->assertOk();
        // İlk boyamadan önce <html data-mehse-tema> basan script.
        $yanit->assertSee('window.mehseTemaUygula', escape: false);
        // 13.11.2026: tek tema (Pano) — topbar seçicisi kaldırıldı.
        $yanit->assertSee('"tema":"pano"', escape: false);
        $yanit->assertDontSee('fi-mehse-tema-secenek', escape: false);
    }
}
