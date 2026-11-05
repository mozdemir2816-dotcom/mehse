<?php

namespace Tests\Feature;

use App\Filament\Resources\EgitimPaketis\Pages\CreateEgitimPaketi;
use App\Models\EgitimPaketi;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EgitimPaketiModulTest extends TestCase
{
    use RefreshDatabase;

    public function test_dersler_modullere_kaydedilir_ve_numaralanir(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(CreateEgitimPaketi::class)
            ->fillForm([
                'ad' => 'Test Paketi',
                'modul1_dersler' => [
                    ['baslik' => 'Mevzuat', 'video_url' => 'https://youtu.be/abcdefghijk'],
                    ['baslik' => 'Haklar', 'video_url' => 'https://youtu.be/bcdefghijkl'],
                ],
                'modul3_dersler' => [
                    ['baslik' => 'Yangın', 'video_url' => 'https://youtu.be/cdefghijklm'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $paket = EgitimPaketi::firstOrFail();
        $sirali = $paket->dersler()->get();

        $this->assertSame([1, 1, 3], $sirali->pluck('modul')->all());
        $this->assertSame(['Mevzuat', 'Haklar', 'Yangın'], $sirali->pluck('baslik')->all());
        $this->assertSame([1, 2], $sirali->where('modul', 1)->pluck('sira')->values()->all());
        $this->assertSame('M1 · 2. Haklar', $sirali[1]->numaraliBaslik());
    }
}
