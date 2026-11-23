<?php

namespace Tests\Feature;

use App\Filament\Pages\AnaSayfa;
use App\Filament\Widgets\BaslangicListesiWidget;
use App\Filament\Widgets\HosGeldinWidget;
use App\Models\AcilDurumPlani;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\KullaniciAyarlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** O12 (23.11.2026): ana sayfa başlangıç listesi + sözleşme kartında "Bilinmiyor". */
class BaslangicListesiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create(['rol' => 'sahip']);
        $this->actingAs($this->uzman);
        KullaniciAyarlari::onbellegiTemizle();
    }

    public function test_yeni_kullanicida_hic_adim_tamam_degil_ve_kart_gorunur(): void
    {
        $adimlar = BaslangicListesiWidget::adimlar($this->uzman);

        $this->assertCount(6, $adimlar);
        $this->assertSame(0, collect($adimlar)->where('tamam', true)->count());
        $this->assertTrue(BaslangicListesiWidget::canView());

        Livewire::test(BaslangicListesiWidget::class)
            ->assertSee('Başlangıç listesi')
            ->assertSee('0</strong>/6 tamam', false)
            ->assertSee('Firma ekle');
    }

    public function test_adimlar_kayitlardan_isaretlenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['aktif' => true]);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ali Veli', 'aktif' => true]);
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'rapor_tarihi' => now()]);

        $tamam = collect(BaslangicListesiWidget::adimlar($this->uzman))->pluck('tamam')->all();

        $this->assertSame([true, true, false, true, false, false], $tamam);
    }

    public function test_hepsi_tamamlaninca_kart_kaybolur(): void
    {
        $igu = IsgProfesyoneli::factory()->create(['user_id' => $this->uzman->id]);
        $firma = Firma::factory()->for($this->uzman)->create(['aktif' => true, 'igu_id' => $igu->id]);
        Calisan::create(['firma_id' => $firma->id, 'ad_soyad' => 'Ali Veli', 'aktif' => true]);
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'rapor_tarihi' => now()]);
        AcilDurumPlani::create(['firma_id' => $firma->id, 'konular' => ['yangin'], 'rapor_tarihi' => now()]);
        YillikPlan::create(['firma_id' => $firma->id, 'yil' => now()->year]);

        $this->assertSame(6, collect(BaslangicListesiWidget::adimlar($this->uzman))->where('tamam', true)->count());
        $this->assertFalse(BaslangicListesiWidget::canView());
    }

    public function test_gizle_kalici_olarak_saklar(): void
    {
        Livewire::test(BaslangicListesiWidget::class)->call('gizle');

        KullaniciAyarlari::onbellegiTemizle();
        $this->assertTrue((bool) KullaniciAyarlari::hepsi($this->uzman->fresh())['baslangic_gizli']);
        $this->actingAs($this->uzman->fresh());
        $this->assertFalse(BaslangicListesiWidget::canView());
    }

    public function test_sozlesme_tarihi_hic_yoksa_bilinmiyor_der(): void
    {
        Firma::factory()->for($this->uzman)->create(['aktif' => true, 'sozlesme_bitis' => null]);

        Livewire::test(HosGeldinWidget::class)
            ->assertSee('Bilinmiyor')
            ->assertSee('Hiçbir firmada sözleşme bitiş tarihi girilmemiş')
            ->assertDontSee('Güncel');
    }

    public function test_kismen_tarihli_portfoyde_tarihsiz_sayisi_gosterilir(): void
    {
        Firma::factory()->for($this->uzman)->create(['aktif' => true, 'sozlesme_bitis' => now()->addYear()]);
        Firma::factory()->for($this->uzman)->create(['aktif' => true, 'sozlesme_bitis' => null]);

        Livewire::test(HosGeldinWidget::class)
            ->assertSee('Güncel')
            ->assertSee('1 firmada bitiş tarihi yok');
    }

    public function test_ana_sayfa_acilir(): void
    {
        $this->get(AnaSayfa::getUrl())->assertSuccessful()->assertSee('Başlangıç listesi');
    }
}
