<?php

namespace Tests\Feature;

use App\Filament\Pages\OrtamOlcumleri as OrtamOlcumleriSayfasi;
use App\Models\Firma;
use App\Models\OrtamOlcumu;
use App\Models\User;
use App\Support\OrtamOlcumuUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrtamOlcumleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create();
    }

    public function test_katalogdan_olcum_eklenir_ve_kaydedilir(): void
    {
        Livewire::test(OrtamOlcumleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('katalogdanEkle', 'Fiziksel Etkenler', 'Gürültü Maruziyeti (LEX,8h)')
            ->call('katalogdanEkle', 'Toz Maruziyeti', 'Solunabilir Toz')
            ->call('kaydet');

        $olcum = OrtamOlcumu::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(2, $olcum->olcumler);
        $this->assertSame('Gürültü Maruziyeti (LEX,8h)', $olcum->olcumler[0]['parametre']);
        $this->assertSame('dB(A)', $olcum->olcumler[0]['birim']);
        $this->assertSame('bekliyor', $olcum->olcumler[0]['sonuc']);
    }

    public function test_olcum_tarihinden_sonraki_tarih_periyoda_gore_hesaplanir(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $olcum->update(['olcumler' => [
            ['parametre' => 'Gürültü', 'periyot_ay' => 24, 'olcum_tarihi' => '2026-03-10', 'sonuc' => 'uygun'],
            ['parametre' => 'Kuvars', 'periyot_ay' => 12, 'olcum_tarihi' => '2026-03-10', 'sonuc' => 'asim'],
        ]]);

        $olcum->refresh();
        $this->assertSame('2028-03-10', $olcum->olcumler[0]['sonraki_olcum_tarihi']);
        $this->assertSame('2027-03-10', $olcum->olcumler[1]['sonraki_olcum_tarihi']);
    }

    public function test_elle_verilen_sonraki_tarih_korunur(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $olcum->update(['olcumler' => [
            ['parametre' => 'Aydınlatma', 'periyot_ay' => 24, 'olcum_tarihi' => '2026-01-01', 'sonraki_olcum_tarihi' => '2026-07-01', 'sonuc' => 'uygun'],
        ]]);

        $this->assertSame('2026-07-01', $olcum->refresh()->olcumler[0]['sonraki_olcum_tarihi']);
    }

    public function test_serbest_olcum_eklenir(): void
    {
        Livewire::test(OrtamOlcumleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniParametre', 'Kaynak Dumanı - Kaynakhane')
            ->set('yeniPeriyot', 12)
            ->call('serbestOlcumEkle')
            ->call('kaydet');

        $olcum = OrtamOlcumu::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('Kaynak Dumanı - Kaynakhane', $olcum->olcumler[0]['parametre']);
        $this->assertSame(12, $olcum->olcumler[0]['periyot_ay']);
    }

    public function test_baslatilmis_ve_yaklasan_ozeti(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $this->assertFalse($olcum->baslatilmisMi());

        $olcum->update(['olcumler' => [
            ['parametre' => 'Gürültü', 'periyot_ay' => 1, 'olcum_tarihi' => now()->subMonths(2)->toDateString(), 'sonuc' => 'uygun'],
            ['parametre' => 'Toz', 'periyot_ay' => 24, 'olcum_tarihi' => now()->toDateString(), 'sonuc' => 'uygun'],
        ]]);
        $olcum->refresh();

        $this->assertTrue($olcum->baslatilmisMi());
        $this->assertCount(1, $olcum->yaklasanlar());   // ilki geçmiş, ikincisi 2 yıl sonra
        $this->assertSame(2, $olcum->ozet()['toplam']);
    }

    public function test_pdf_uretilir(): void
    {
        $olcum = OrtamOlcumu::firmaIcin($this->firma);
        $olcum->update(['olcumler' => [
            ['parametre' => 'Gürültü Maruziyeti', 'grup' => 'Fiziksel Etkenler', 'bolge' => 'Pres hattı', 'periyot_ay' => 24, 'olcum_tarihi' => '2026-02-01', 'laboratuvar' => 'X Hijyen Lab.', 'olculen_deger' => '88', 'sinir_deger' => '85', 'birim' => 'dB(A)', 'sonuc' => 'asim'],
        ]]);

        $yanit = OrtamOlcumuUretici::pdf($olcum);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(OrtamOlcumleriSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
