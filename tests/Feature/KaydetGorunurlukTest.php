<?php

namespace Tests\Feature;

use App\Filament\Resources\Calisans\Pages\CreateCalisan;
use App\Filament\Resources\Calisans\Pages\EditCalisan;
use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Pages\BasePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kullanıcı isteği (06.10.2026): uzun formlarda Kaydet aşağıda kalıp görünmüyordu.
 * Tüm modallarda başlık + Kaydet şeridi sabit (AppServiceProvider), kayıt
 * sayfalarında Kaydet başlıkta da var (KaydetUstte).
 */
class KaydetGorunurlukTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_tum_aksiyon_turlerinde_modal_basligi_ve_alt_bilgisi_sabit(): void
    {
        foreach ([Action::make('x'), CreateAction::make(), EditAction::make()] as $aksiyon) {
            $this->assertTrue($aksiyon->isModalFooterSticky(), $aksiyon::class);
            $this->assertTrue($aksiyon->isModalHeaderSticky(), $aksiyon::class);
        }

        // Tek tek kapatılabilir (genel ayar yalnız varsayılan).
        $this->assertFalse(Action::make('y')->stickyModalFooter(false)->isModalFooterSticky());
        $this->assertTrue(BasePage::$formActionsAreSticky);
    }

    public function test_calisan_ekle_penceresinde_kaydet_seridi_sabit(): void
    {
        $sayfa = Livewire::test(ListCalisans::class)->mountAction('create')->assertActionMounted('create');

        $aksiyon = $sayfa->instance()->getMountedAction();
        $this->assertTrue($aksiyon->isModalFooterSticky());
        $this->assertTrue($aksiyon->isModalHeaderSticky());
    }

    public function test_kayit_sayfalarinda_kaydet_basliktada_var(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ali Veli', 'aktif' => true]);

        // Başlıktaki düğme sayfa formunu (id="form") gönderir.
        Livewire::test(CreateCalisan::class)->assertSeeHtml('form="form"');
        Livewire::test(EditCalisan::class, ['record' => $calisan->getKey()])->assertSeeHtml('form="form"');
        Livewire::test(CreateFirma::class)->assertSeeHtml('form="form"');
        Livewire::test(EditFirma::class, ['record' => $firma->getKey()])->assertSeeHtml('form="form"');
    }

    public function test_ustteki_kaydet_dugmesi_kaydeder(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $calisan = Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ali Veli', 'aktif' => true]);

        Livewire::test(EditCalisan::class, ['record' => $calisan->getKey()])
            ->fillForm(['gorev' => 'Kaynakçı'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Kaynakçı', $calisan->refresh()->gorev);
    }
}
