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

    /** 02.11.2026: tarihi girilmiş, durumu seçilmemiş ziyaret "Boş" değil "Planlandı" görünür; Boş'a düşmez. */
    public function test_tarihli_ziyaret_durum_secilmemisse_planlandi_gorunur(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-01 10:00');
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'FOM Makina']);
        $this->ekimZiyaretleri($firma, [['2026-10-05', 'bos', 2]]);

        $w = Livewire::test(BuAyZiyaretlerWidget::class);
        $this->assertSame('planlandi', $w->instance()->ayinZiyaretleri()[0]['durum']);
        $w->assertSee('Planlandı')->assertDontSee('>Boş<', false);

        // Rozet: tarihli ziyarette Planlandı ⇄ Tamamlandı (Boş'a dönmez)
        $p = ZiyaretProgrami::where('firma_id', $firma->id)->first();
        $p->durumIlerlet(9, 0);
        $this->assertSame('tamamlandi', $p->fresh()->ziyaretler[9][0]['durum']);
        $p->fresh()->durumIlerlet(9, 0);
        $this->assertSame('planlandi', $p->fresh()->ziyaretler[9][0]['durum']);

        // Tarihsiz girdi eski döngüde kalır
        $this->assertSame('bos', ZiyaretProgrami::ayGirdileri(['tarih' => null, 'durum' => 'bos'])[0]['durum']);
        \Illuminate\Support\Carbon::setTestNow();
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

    /**
     * 02.11.2026: "Eksik saha süresi — plan önerisi" tablosu ve süre uyarısı kaldırıldı. O ayın takviminde
     * planı olan firmaya (süresi az olsa da) uyarı verilmez; yalnız takvimde HİÇ kaydı olmayan firma
     * "Ziyaret planlanmamış" satırında görünür.
     */
    public function test_plani_olan_firmaya_sure_uyarisi_verilmez_yalniz_plansiz_firma_gorunur(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-15 10:00');

        // Çok tehlikeli 10 çalışan (400 dk gerekir) ama yalnız 1 saatlik planı var → uyarı YOK
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'Planlı Az Süreli', 'tehlike_sinifi' => 'cok_tehlikeli', 'calisan_sayisi' => 10]);
        $this->ekimZiyaretleri($a, [['2026-10-22', 'planlandi', 1]]);
        // Takvimde hiç kaydı yok → planlanmamış
        Firma::factory()->for($this->uzman)->create(['unvan' => 'Takvimde Yok', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 3]);

        $w = Livewire::test(BuAyZiyaretlerWidget::class);
        $this->assertSame(['Takvimde Yok'], array_column($w->instance()->ziyaretOzeti['plansiz'], 'firma'));
        $this->assertFalse(method_exists($w->instance(), 'sureEksikleri'));

        $w->assertSee('Ziyaret planlanmamış 1 firma')
            ->assertDontSee('Eksik saha süresi')->assertDontSee('Eksik süre')->assertDontSee('saha süresi eksik')
            ->assertDontSee('gitmeniz gereken');
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
