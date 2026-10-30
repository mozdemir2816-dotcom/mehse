<?php

namespace Tests\Feature;

use App\Filament\Pages\FirmaDenetimOperasyon;
use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\RiskMerkezi;
use App\Models\Firma;
use App\Models\User;
use App\Support\KomutaFirma;
use App\Support\SayfaAileleri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Komuta Merkezi sadeleştirmesi (29.10.2026): 3 sekme, ortak firma seçimi, birleşik Firma Denetim & Operasyon sayfası. */
class KomutaMerkeziTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $ilk;

    private Firma $ikinci;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->ilk = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Yapı', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 5]);
        $this->ikinci = Firma::factory()->for($this->uzman)->create(['unvan' => 'B Metal', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 5]);
    }

    public function test_sekme_seridi_besten_uce_indi(): void
    {
        $g = SayfaAileleri::sekmeGruplari([FirmaDenetimOperasyon::class]);

        $this->assertSame('kontrol', $g['aile']);
        $this->assertSame(
            ['Portföy Genel Bakış', 'Firma Denetim & Operasyon', 'Risk & Tehlike Analitiği'],
            array_column($g['ust'], 'etiket'),
        );
        $this->assertSame([false, true, false], array_column($g['ust'], 'aktif'));

        // Profilim, "Portföy Genel Bakış" sekmesinin alt sayfası (ayrı menü girişi değil).
        $genel = SayfaAileleri::sekmeGruplari([\App\Filament\Pages\Profilim::class]);
        $this->assertSame('Portföy Genel Bakış', collect($genel['ust'])->firstWhere('aktif', true)['etiket']);
        $this->assertSame(['Portföy Genel Bakış', 'Profilim'], array_column($genel['alt'], 'etiket'));
    }

    public function test_birlesik_sayfa_acilir_ve_uc_bolum_gosterir(): void
    {
        $this->get(FirmaDenetimOperasyon::getUrl(['firma' => $this->ikinci->id]))->assertOk()->assertSee('B Metal');

        $sayfa = Livewire::test(FirmaDenetimOperasyon::class)->assertSet('firmaId', $this->ikinci->id)
            ->assertSee('Operasyon Skoru & Aksiyonlar')->assertSee('Bugün neye müdahale etmeliyim?');

        $sayfa->call('bolumSec', 'firma360')->assertSee('Genel uyum durumu');
        $sayfa->call('bolumSec', 'uzman')->assertSee('İşyeri uygunluk özeti');
        $sayfa->call('bolumSec', 'gecersiz')->assertSet('bolum', 'uzman');
    }

    public function test_firma_secimi_sayfalar_arasinda_paylasilir(): void
    {
        Livewire::test(KontrolMerkezi::class)->set('firmaId', $this->ikinci->id);
        $this->assertSame($this->ikinci->id, KomutaFirma::oturumdaki());

        Livewire::test(FirmaDenetimOperasyon::class)->assertSet('firmaId', $this->ikinci->id);
        Livewire::test(RiskMerkezi::class)->assertSet('firmaId', $this->ikinci->id);

        // Firma Denetim'de değişince Risk Merkezi onu açar; Tüm Portföy seçimi de taşınır.
        Livewire::test(FirmaDenetimOperasyon::class)->set('firmaId', $this->ilk->id);
        Livewire::test(RiskMerkezi::class)->assertSet('firmaId', $this->ilk->id);

        Livewire::test(KontrolMerkezi::class)->set('firmaId', null);
        Livewire::test(KontrolMerkezi::class)->assertSet('firmaId', null);
    }

    public function test_baska_kullanicinin_firmasi_secilmez(): void
    {
        $baska = Firma::factory()->for(User::factory()->create())->create();
        KomutaFirma::sec($baska->id);

        Livewire::test(FirmaDenetimOperasyon::class)->assertSet('firmaId', $this->ilk->id);
    }

    public function test_eski_uc_sayfayi_gorebilen_kullanici_birlesik_sayfaya_girer(): void
    {
        $uye = User::factory()->create(['rol' => 'uzman']);
        $this->actingAs($uye);

        $this->assertFalse(FirmaDenetimOperasyon::canAccess());

        $uye->sayfaYetkileri()->create(['sayfa_anahtari' => 'isyeri-durum-merkezi']);

        $this->assertTrue(FirmaDenetimOperasyon::canAccess());
    }
}
