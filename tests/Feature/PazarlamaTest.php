<?php

namespace Tests\Feature;

use App\Filament\Pages\Pazarlama;
use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Models\AdayFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PazarlamaTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_pazarlama_ozeti_asamalara_gore_sayar(): void
    {
        AdayFirma::create(['user_id' => $this->uzman->id, 'unvan' => 'A', 'asama' => 'aday']);
        AdayFirma::create(['user_id' => $this->uzman->id, 'unvan' => 'B', 'asama' => 'teklif']);
        AdayFirma::create(['user_id' => $this->uzman->id, 'unvan' => 'C', 'asama' => 'kazanildi']);
        AdayFirma::create(['user_id' => $this->uzman->id, 'unvan' => 'D', 'asama' => 'aday', 'hatirlatma_tarihi' => now()->subDay()]);

        $ozet = Livewire::test(Pazarlama::class)->instance()->pazarlamaOzeti();

        $this->assertSame(4, $ozet['toplam']);
        $this->assertSame(1, $ozet['teklif']);
        $this->assertSame(1, $ozet['kazanilan']);
        $this->assertSame(1, $ozet['acik_hatirlatma']);
    }

    public function test_aday_firma_kaydet_action_olusturur_ve_gunceller(): void
    {
        Livewire::test(Pazarlama::class)
            ->callAction('adayFirmaKaydet', [
                'unvan' => 'Örnek Aday A.Ş.',
                'yetkili_ad' => 'Ahmet Yılmaz',
                'asama' => 'aday',
            ])
            ->assertHasNoActionErrors();

        $aday = AdayFirma::where('user_id', $this->uzman->id)->firstOrFail();
        $this->assertSame('Örnek Aday A.Ş.', $aday->unvan);

        Livewire::test(Pazarlama::class)
            ->mountAction('adayFirmaKaydet', ['id' => $aday->id])
            ->assertActionDataSet(['unvan' => 'Örnek Aday A.Ş.', 'asama' => 'aday'])
            ->setActionData(['unvan' => 'Örnek Aday A.Ş.', 'asama' => 'teklif'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame('teklif', $aday->fresh()->asama);
        $this->assertSame(1, AdayFirma::count());
    }

    public function test_aday_firma_sil(): void
    {
        $aday = AdayFirma::create(['user_id' => $this->uzman->id, 'unvan' => 'Silinecek', 'asama' => 'aday']);

        Livewire::test(Pazarlama::class)->call('adayFirmaSil', $aday->id);

        $this->assertDatabaseMissing('aday_firmalar', ['id' => $aday->id]);
    }

    public function test_baskasinin_adayini_silemez(): void
    {
        $baskasi = User::factory()->create();
        $aday = AdayFirma::create(['user_id' => $baskasi->id, 'unvan' => 'Yabancı', 'asama' => 'aday']);

        Livewire::test(Pazarlama::class)->call('adayFirmaSil', $aday->id);

        $this->assertDatabaseHas('aday_firmalar', ['id' => $aday->id]);
    }

    public function test_kazanilan_aday_firmaya_donusturulur(): void
    {
        $aday = AdayFirma::create([
            'user_id' => $this->uzman->id, 'unvan' => 'Kazanılan A.Ş.',
            'telefon' => '5551234567', 'sehir' => 'İstanbul', 'asama' => 'kazanildi',
        ]);

        Livewire::test(Pazarlama::class)
            ->call('adayFirmaDonustur', $aday->id)
            ->assertRedirect(CreateFirma::getUrl());

        $this->assertSame([
            'unvan' => 'Kazanılan A.Ş.',
            'telefon' => '5551234567',
            'il' => 'İstanbul',
        ], session('aday_firma_donusum'));
    }

    public function test_create_firma_sayfasi_session_verisiyle_formu_doldurur(): void
    {
        session(['aday_firma_donusum' => [
            'unvan' => 'Aktarılan Firma',
            'telefon' => '5559876543',
            'il' => 'Ankara',
        ]]);

        Livewire::test(CreateFirma::class)
            ->assertFormSet([
                'unvan' => 'Aktarılan Firma',
                'telefon' => '5559876543',
                'il' => 'Ankara',
                'tehlike_sinifi' => 'az_tehlikeli',
            ]);
    }

    public function test_pazarlama_ve_talepler_tek_menu_girisinde_uc_sekme(): void
    {
        $menu = collect(\Filament\Facades\Filament::getNavigation())->flatMap(fn ($g) => $g->getItems())->map(fn ($i) => $i->getLabel());

        $this->assertCount(1, $menu->filter(fn ($e) => $e === 'Pazarlama & Talepler'));
        $this->assertCount(0, $menu->filter(fn ($e) => in_array($e, ['Pazarlama', 'Teklif Talepleri', 'Başvurular'], true)));

        $this->assertSame(
            ['Aday Firmalar', 'Teklif Talepleri', 'OSGB Başvuruları'],
            array_column(\App\Support\SayfaAileleri::sekmeler([Pazarlama::class]), 'etiket'),
        );

        Livewire::test(Pazarlama::class)->assertSee('Aday Firma Ekle')->assertSee('Toplam Aday');
    }
}
