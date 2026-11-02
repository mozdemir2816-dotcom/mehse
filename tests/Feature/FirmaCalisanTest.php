<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\CreateCalisan;
use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\Pages\ListFirmas;
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

    /** 02.11.2026: Portföy Özeti (3 kutu) ana sayfa kokpitine (HosGeldinWidget) taşındı. */
    public function test_ana_sayfa_kokpiti_kendi_sayaclarini_verir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['aktif' => true, 'sozlesme_bitis' => now()->subDay(), 'calisan_sayisi' => 3, 'tehlike_sinifi' => 'az_tehlikeli', 'katip_aylik_dk' => null]);
        Calisan::factory()->count(3)->for($firma)->create(['aktif' => true]);
        Firma::factory()->create(['aktif' => true]); // başka uzmanın firması
        \App\Models\SahaBulgusu::create(['firma_id' => $firma->id, 'uygunsuzluk' => 'Korkuluk eksik', 'termin' => now()->subDays(3)]);

        // Kokpit kartı = görev panosunun "Günü geçen" / "Yaklaşan" sayısı (aynı liste; 02.11.2026: 9 ↔ 2 uyuşmazlığı)
        $pano = \App\Support\GorevDurumu::ozet(\App\Support\GorevDurumu::gorevler($this->uzman));
        $this->assertGreaterThanOrEqual(1, $pano['gecikmis']);

        Livewire::test(\App\Filament\Widgets\HosGeldinWidget::class)
            ->assertSee('Aktif Portföy')
            ->assertSeeHtml('1 <small>firma</small>')
            ->assertSeeHtml('3 <small>çalışan</small>')
            ->assertSee('İSG-KATİP Kapasitesi')
            ->assertSeeHtml('1 <small>geciken sözleşme</small>')
            ->assertSee('Yenileme gerekli')
            ->assertSeeHtml($pano['gecikmis'].' <small>günü geçen</small>')
            ->assertSeeHtml($pano['yaklasan'].' <small>yaklaşan</small>')
            ->assertSee('Risk ve DÖF: 1')
            ->assertSee('Acil Durum / Kroki')
            ->assertSee('az tehlikeli 10');   // KATİP formülü yalnız ipucunda
    }
}
