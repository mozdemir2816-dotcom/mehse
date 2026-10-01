<?php

namespace Tests\Feature;

use App\Filament\Widgets\BuAyZiyaretlerWidget;
use App\Models\Firma;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Panel ana sayfası — "bu ay gitmem gereken firmaların yüzde kaçına gittim"
 * (01.10.2026, kullanıcı isteği). Ziyaret Programı'nda o ay tarihi girilmiş
 * firmalar = gidilecek; en az bir ziyareti tamamlanmış = gidildi.
 */
class AylikPlanGerceklesmeTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 10:00');
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param  array<int, array{0: string, 1: string}>  $ziyaretler  [tarih, durum] */
    private function ziyaretler(Firma $firma, array $ziyaretler): void
    {
        $p = ZiyaretProgrami::firmaYilIcin($firma, 2026);
        $aylar = $p->ziyaretler;

        foreach ($ziyaretler as [$tarih, $durum]) {
            $ay = (int) substr($tarih, 5, 2) - 1;
            $sablon = $aylar[$ay][0] ?? [];
            $bos = collect($aylar[$ay])->search(fn ($z) => blank($z['tarih'] ?? null));
            $sira = $bos === false ? count($aylar[$ay]) : $bos;
            $aylar[$ay][$sira] = [...$sablon, 'tarih' => $tarih, 'durum' => $durum];
        }

        $p->update(['ziyaretler' => $aylar]);
    }

    public function test_gidilmesi_gereken_firmalarin_yuzde_kacina_gidildi(): void
    {
        $a = Firma::factory()->for($this->uzman)->create(['unvan' => 'A Firma']);
        $b = Firma::factory()->for($this->uzman)->create(['unvan' => 'B Firma']);
        $c = Firma::factory()->for($this->uzman)->create(['unvan' => 'C Firma']);
        $d = Firma::factory()->for($this->uzman)->create(['unvan' => 'D Firma']);

        // A: iki ziyaretten biri tamam → gidildi (firma bir kez sayılır)
        $this->ziyaretler($a, [['2026-10-03', 'tamamlandi'], ['2026-10-24', 'planlandi']]);
        $this->ziyaretler($b, [['2026-10-08', 'tamamlandi']]);
        $this->ziyaretler($c, [['2026-10-20', 'planlandi']]);
        $this->ziyaretler($d, [['2026-10-28', 'bos']]); // tarih girilmiş = gidilecek
        // Kapsam dışı: başka ay ve başka uzman
        $this->ziyaretler(Firma::factory()->for($this->uzman)->create(), [['2026-09-10', 'tamamlandi']]);
        $this->ziyaretler(Firma::factory()->for(User::factory())->create(), [['2026-10-10', 'planlandi']]);

        $oz = Livewire::test(BuAyZiyaretlerWidget::class)->instance()->ziyaretOzeti();

        $this->assertSame(4, $oz['toplam']);
        $this->assertSame(2, $oz['gidilen']);
        $this->assertSame(50, $oz['yuzde']);
        $this->assertSame(48, $oz['takvim_yuzde'], '15 Ekim = 31 günün %48i');
        // Gidilmeyenler önce
        $this->assertSame(['C Firma', 'D Firma', 'A Firma', 'B Firma'], array_column($oz['firmalar'], 'firma'));
    }

    public function test_ekranda_cumle_ve_yuzde_gorunur_ay_degisince_yenilenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $this->ziyaretler($firma, [['2026-10-03', 'tamamlandi'], ['2026-09-05', 'planlandi']]);

        $widget = Livewire::test(BuAyZiyaretlerWidget::class)
            ->assertSee('gitmeniz gereken')->assertSee('%100');

        $widget->call('ayDegistir', -1);
        $oz = $widget->instance()->ziyaretOzeti();
        $this->assertSame(0, $oz['yuzde'], 'Eylül ziyareti tamamlanmadı');
        $this->assertSame(100, $oz['takvim_yuzde'], 'geçmiş ay tamamen geçmiş sayılır');

        // Durum değişince özet tazelenir
        $p = ZiyaretProgrami::where('firma_id', $firma->id)->first();
        $sira = collect($p->ziyaretler[8])->search(fn ($z) => ($z['tarih'] ?? null) === '2026-09-05');
        $widget->call('durumDegistir', $p->id, 8, $sira);
        $this->assertSame(100, $widget->instance()->ziyaretOzeti()['yuzde']);
    }

    public function test_ziyaret_yoksa_bilgi_mesaji(): void
    {
        Livewire::test(BuAyZiyaretlerWidget::class)
            ->assertSee('tarihi girilmiş firma ziyareti yok');
    }
}
