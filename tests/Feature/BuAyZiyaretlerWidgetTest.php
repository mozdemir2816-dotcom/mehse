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

    /** Ekim 2026 programına satırlar yazar: [tarih, durum, saat]. */
    private function ekimZiyaretleri(Firma $firma, array $satirlar): void
    {
        $p = ZiyaretProgrami::firmaYilIcin($firma, 2026);
        $aylar = $p->ziyaretler;
        $aylar[9] = collect($satirlar)->map(fn (array $s) => [
            'tarih' => $s[0], 'amac' => 'Ziyaret', 'durum' => $s[1], 'sure_saat' => $s[2], 'notlar' => null,
        ])->all();
        $p->update(['ziyaretler' => $aylar]);
    }

    public function test_ay_ozeti_planli_tamamlanan_gecikmis(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-15 10:00');
        $firma = Firma::factory()->for($this->uzman)->create();
        $this->ekimZiyaretleri($firma, [
            ['2026-10-05', 'tamamlandi', 2],
            ['2026-10-10', 'planlandi', 2],   // tarihi geçti → gecikmiş
            ['2026-10-20', 'planlandi', 2],
            ['2026-10-25', 'bos', null],
        ]);

        $c = Livewire::test(BuAyZiyaretlerWidget::class);

        $this->assertSame(['toplam' => 4, 'planli' => 2, 'tamamlanan' => 1, 'gecikmis' => 1], $c->instance()->ayOzeti);
        $this->assertTrue(BuAyZiyaretlerWidget::gecikmisMi(['tarih' => '2026-10-10', 'durum' => 'planlandi']));
        $this->assertFalse(BuAyZiyaretlerWidget::gecikmisMi(['tarih' => '2026-10-10', 'durum' => 'tamamlandi']));
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_eksik_saha_suresi_calisan_ve_tehlike_sinifina_gore(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-15 10:00');

        // Çok tehlikeli, 10 çalışan → 400 dk gerekli; 2 saat yapıldı + 3 saat planlı = 300 dk → 100 eksik, plan var
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firma', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 10]);
        $this->ekimZiyaretleri($a, [['2026-10-05', 'tamamlandi', 2], ['2026-10-22', 'planlandi', 3]]);

        // Tehlikeli, 3 çalışan → 60 dk; hiç ziyaret yok → 60 eksik, plan yok
        Firma::factory()->for($this->uzman)->create(['unvan' => 'B Firma', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 3]);

        // Az tehlikeli, 6 çalışan → 60 dk; 1 saat tamamlandı → eksik yok (listede çıkmaz)
        $c = Firma::factory()->for($this->uzman)->create(['unvan' => 'C Firma', 'tehlike_sinifi' => 'az_tehlikeli', 'calisan_sayisi' => 6]);
        $this->ekimZiyaretleri($c, [['2026-10-02', 'tamamlandi', 1]]);

        // Pasif firma ve başkasının firması sayılmaz
        Firma::factory()->for($this->uzman)->create(['aktif' => false, 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 50]);
        Firma::factory()->for(User::factory()->create())->create(['tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 50]);

        $eksik = Livewire::test(BuAyZiyaretlerWidget::class)->instance()->sureEksikleri;

        $this->assertSame(['A Firma', 'B Firma'], array_column($eksik, 'firma'));
        $this->assertSame([400, 120, 180, 100, true], [$eksik[0]['gerekli'], $eksik[0]['yapilan'], $eksik[0]['planli'], $eksik[0]['eksik'], $eksik[0]['plan_var']]);
        $this->assertSame([60, 60, false], [$eksik[1]['gerekli'], $eksik[1]['eksik'], $eksik[1]['plan_var']]);
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_bugun_ve_tumunu_goster(): void
    {
        $c = Livewire::test(BuAyZiyaretlerWidget::class)
            ->call('ayDegistir', -3)
            ->call('bugun')
            ->assertSet('gosterilenAy', now()->format('Y-m'))
            ->assertSet('seciliTarih', now()->toDateString())
            ->call('tumunuGoster')
            ->assertSet('seciliTarih', null);

        $c->assertSee('Toplam ziyaret');
    }
}
