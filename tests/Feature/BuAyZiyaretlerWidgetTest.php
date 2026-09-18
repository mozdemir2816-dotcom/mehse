<?php

namespace Tests\Feature;

use App\Filament\Widgets\BuAyZiyaretlerWidget;
use App\Models\Firma;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BuAyZiyaretlerWidgetTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    public function test_gosterilen_ay_varsayilan_olarak_bugunun_ayidir(): void
    {
        $component = Livewire::test(BuAyZiyaretlerWidget::class);

        $this->assertSame(now()->format('Y-m'), $component->get('gosterilenAy'));
    }

    public function test_bu_ayin_ziyaretleri_sadece_gosterilen_aya_ve_kullaniciya_ait_kayitlari_listeler(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $baskaUzmanFirma = Firma::factory()->for(User::factory()->create())->create();

        $buAy = now()->format('Y-m');
        $gecenAy = now()->subMonthNoOverflow()->format('Y-m');

        $p = ZiyaretProgrami::firmaYilIcin($firma, (int) now()->format('Y'));

        $aylar = $p->ziyaretler;
        $ayIndex = (int) now()->format('n') - 1;
        $aylar[$ayIndex][0]['tarih'] = $buAy.'-15';
        $aylar[$ayIndex][0]['amac'] = 'Bu Ayki Ziyaret';
        $p->update(['ziyaretler' => $aylar]);

        $baskaP = ZiyaretProgrami::firmaYilIcin($baskaUzmanFirma, (int) now()->format('Y'));
        $baskaAylar = $baskaP->ziyaretler;
        $baskaAylar[$ayIndex][0]['tarih'] = $buAy.'-20';
        $baskaP->update(['ziyaretler' => $baskaAylar]);

        $component = Livewire::test(BuAyZiyaretlerWidget::class);
        $ziyaretler = $component->instance()->ayinZiyaretleri();

        $this->assertCount(1, $ziyaretler);
        $this->assertSame('Bu Ayki Ziyaret', $ziyaretler[0]['amac']);
        $this->assertSame($p->id, $ziyaretler[0]['program_id']);
    }

    public function test_durum_degistir_baska_uzmanin_kaydini_guncelleyemez(): void
    {
        $baskaUzman = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baskaUzman)->create();
        $p = ZiyaretProgrami::firmaYilIcin($baskaFirma, (int) now()->format('Y'));

        Livewire::test(BuAyZiyaretlerWidget::class)
            ->call('durumDegistir', $p->id, 0, 0);

        $p->refresh();
        $this->assertSame('bos', $p->ziyaretler[0][0]['durum']);
    }

    public function test_durum_degistir_kendi_kaydini_ilerletir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $p = ZiyaretProgrami::firmaYilIcin($firma, (int) now()->format('Y'));

        Livewire::test(BuAyZiyaretlerWidget::class)
            ->call('durumDegistir', $p->id, 0, 0);

        $p->refresh();
        $this->assertSame('planlandi', $p->ziyaretler[0][0]['durum']);
    }

    public function test_panel_ana_sayfasi_widget_ile_birlikte_hatasiz_acilir(): void
    {
        $this->get('/admin')->assertOk();
    }

    public function test_gun_secilince_sadece_o_gune_suzulur(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $buAy = now()->format('Y-m');
        $ayIndex = (int) now()->format('n') - 1;

        $p = ZiyaretProgrami::firmaYilIcin($firma, (int) now()->format('Y'));
        $aylar = $p->ziyaretler;
        $aylar[$ayIndex][0]['tarih'] = $buAy.'-05';
        $p->update(['ziyaretler' => $aylar]);

        $p2 = ZiyaretProgrami::firmaYilIcin(Firma::factory()->for($this->uzman)->create(), (int) now()->format('Y'));
        $aylar2 = $p2->ziyaretler;
        $aylar2[$ayIndex][0]['tarih'] = $buAy.'-06';
        $p2->update(['ziyaretler' => $aylar2]);

        $component = Livewire::test(BuAyZiyaretlerWidget::class)
            ->call('gunSec', $buAy.'-05');

        $this->assertCount(1, $component->instance()->ayinZiyaretleri());
        $this->assertSame($buAy.'-05', $component->instance()->ayinZiyaretleri()[0]['tarih']);
    }
}
