<?php

namespace Tests\Feature;

use App\Filament\Resources\EgitimPaketis\Pages\EditEgitimPaketi;
use App\Filament\Resources\EgitimPaketis\RelationManagers\DerslerRelationManager;
use App\Models\EgitimPaketi;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EgitimPaketiVideolarTest extends TestCase
{
    use RefreshDatabase;

    private EgitimPaketi $paket;

    protected function setUp(): void
    {
        parent::setUp();
        $u = User::factory()->create();
        $this->actingAs($u);
        Filament::setCurrentPanel('admin');
        $this->paket = EgitimPaketi::create(['user_id' => $u->id, 'ad' => 'Paket']);
    }

    private function yonetici()
    {
        return Livewire::test(DerslerRelationManager::class, ['ownerRecord' => $this->paket, 'pageClass' => EditEgitimPaketi::class]);
    }

    public function test_her_video_aninda_kaydedilir_sira_bos_ise_konu_basliginin_sonuna_eklenir(): void
    {
        $y = $this->yonetici();

        foreach ([['Mevzuat', 1], ['Yangın', 3], ['Elektrik', 3]] as [$baslik, $modul]) {
            $y->callAction(TestAction::make('create')->table(), [
                'modul' => $modul, 'baslik' => $baslik, 'video_url' => 'https://youtu.be/abcdefghijk',
            ])->assertHasNoFormErrors();
        }

        $d = $this->paket->dersler()->get();
        $this->assertSame(['Mevzuat', 'Yangın', 'Elektrik'], $d->pluck('baslik')->all());
        $this->assertSame([1, 1, 2], $d->pluck('sira')->all());
    }

    public function test_sira_numarasi_yazilinca_liste_sirasi_degisir_ve_biri_silinince_digerleri_kalir(): void
    {
        $a = $this->paket->dersler()->create(['modul' => 3, 'sira' => 1, 'baslik' => 'A', 'video_url' => 'https://youtu.be/aaaaaaaaaaa']);
        $b = $this->paket->dersler()->create(['modul' => 3, 'sira' => 2, 'baslik' => 'B', 'video_url' => 'https://youtu.be/bbbbbbbbbbb']);
        $c = $this->paket->dersler()->create(['modul' => 1, 'sira' => 1, 'baslik' => 'C', 'video_url' => 'https://youtu.be/ccccccccccc']);

        $y = $this->yonetici();
        $y->call('updateTableColumnState', 'sira', (string) $a->getKey(), '5');
        $this->assertSame(['C', 'B', 'A'], $this->paket->dersler()->pluck('baslik')->all());

        $y->callAction(TestAction::make('delete')->table($b));
        $this->assertSame(['C', 'A'], $this->paket->dersler()->pluck('baslik')->all());
    }

    public function test_video_bekleyen_konu_calisana_gorunmez_video_baglaninca_yayina_girer(): void
    {
        $this->paket->dersler()->create(['modul' => 1, 'sira' => 1, 'baslik' => 'Hazır', 'video_url' => 'https://youtu.be/aaaaaaaaaaa', 'sure_sn' => 600]);
        $taslak = $this->paket->tumDersler()->create(['modul' => 2, 'sira' => 1, 'baslik' => 'İlk yardım', 'video_url' => null]);

        $paket = $this->paket->fresh();
        $this->assertSame(['Hazır'], $paket->dersler()->pluck('baslik')->all());
        $this->assertSame(['Hazır', 'İlk yardım'], $paket->tumDersler()->pluck('baslik')->all());
        $this->assertSame(1, $paket->videoBekleyenSayisi());
        $this->assertSame(10, $paket->toplamSureDk());

        $this->yonetici()->callAction(
            TestAction::make('edit')->table($taslak),
            ['modul' => 2, 'sira' => 1, 'baslik' => 'İlk yardım', 'video_url' => 'https://youtu.be/bbbbbbbbbbb'],
        )->assertHasNoFormErrors();

        $paket = $this->paket->fresh();
        $this->assertSame(['Hazır', 'İlk yardım'], $paket->dersler()->pluck('baslik')->all());
        $this->assertSame(0, $paket->videoBekleyenSayisi());
    }
}
