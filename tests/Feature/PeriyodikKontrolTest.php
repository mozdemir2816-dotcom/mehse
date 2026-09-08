<?php

namespace Tests\Feature;

use App\Filament\Pages\PeriyodikKontrol as PeriyodikKontrolSayfasi;
use App\Models\Firma;
use App\Models\PeriyodikKontrol;
use App\Models\User;
use App\Support\PeriyodikKontrolUretici;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PeriyodikKontrolTest extends TestCase
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

    public function test_katalogdan_ekipman_eklenir_ve_kaydedilir(): void
    {
        Livewire::test(PeriyodikKontrolSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->call('katalogdanEkle', 'Kaldırma ve İletme Ekipmanları', 'Forklift')
            ->call('katalogdanEkle', 'Tesisatlar', 'Topraklama Tesisatı')
            ->call('kaydet');

        $kontrol = PeriyodikKontrol::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(2, $kontrol->ekipmanlar);
        $this->assertSame('Forklift', $kontrol->ekipmanlar[0]['ad']);
        $this->assertSame(12, $kontrol->ekipmanlar[0]['periyot_ay']);
        $this->assertSame('bekliyor', $kontrol->ekipmanlar[0]['sonuc']);
    }

    public function test_son_kontrol_tarihinden_sonraki_tarih_periyoda_gore_hesaplanir(): void
    {
        $kontrol = PeriyodikKontrol::firmaIcin($this->firma);
        $kontrol->update(['ekipmanlar' => [
            ['ad' => 'Kule Vinç', 'kategori' => 'Kaldırma', 'periyot_ay' => 12, 'son_kontrol_tarihi' => '2026-03-10', 'sonuc' => 'uygun'],
            ['ad' => 'Sepetli Platform', 'kategori' => 'Kaldırma', 'periyot_ay' => 6, 'son_kontrol_tarihi' => '2026-03-10', 'sonuc' => 'uygun'],
        ]]);

        $kontrol->refresh();
        $this->assertSame('2027-03-10', $kontrol->ekipmanlar[0]['sonraki_kontrol_tarihi']);
        $this->assertSame('2026-09-10', $kontrol->ekipmanlar[1]['sonraki_kontrol_tarihi']);
    }

    public function test_elle_verilen_sonraki_tarih_korunur(): void
    {
        $kontrol = PeriyodikKontrol::firmaIcin($this->firma);
        $kontrol->update(['ekipmanlar' => [
            ['ad' => 'Kompresör', 'periyot_ay' => 12, 'son_kontrol_tarihi' => '2026-01-01', 'sonraki_kontrol_tarihi' => '2026-07-01', 'sonuc' => 'sartli'],
        ]]);

        $this->assertSame('2026-07-01', $kontrol->refresh()->ekipmanlar[0]['sonraki_kontrol_tarihi']);
    }

    public function test_serbest_ekipman_eklenir(): void
    {
        Livewire::test(PeriyodikKontrolSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yeniAd', 'Hidrolik Rampa')
            ->set('yeniPeriyot', 24)
            ->call('serbestEkipmanEkle')
            ->call('kaydet');

        $kontrol = PeriyodikKontrol::where('firma_id', $this->firma->id)->sole();
        $this->assertSame('Hidrolik Rampa', $kontrol->ekipmanlar[0]['ad']);
        $this->assertSame(24, $kontrol->ekipmanlar[0]['periyot_ay']);
    }

    public function test_kontrol_merkezi_kriteri_tarih_girilince_karsilanir(): void
    {
        $kriter = fn () => PortfoyKarne::firmaKriterKarsilarMi($this->firma->fresh(), 'periyodik_kontrol_raporu');

        $kontrol = PeriyodikKontrol::firmaIcin($this->firma);
        $kontrol->update(['ekipmanlar' => [['ad' => 'Forklift', 'periyot_ay' => 12, 'sonuc' => 'bekliyor']]]);
        $this->assertFalse($kriter()); // eklendi ama kontrol tarihi yok

        $kontrol->update(['ekipmanlar' => [['ad' => 'Forklift', 'periyot_ay' => 12, 'son_kontrol_tarihi' => '2026-05-01', 'sonuc' => 'uygun']]]);
        $this->assertTrue($kriter());
    }

    public function test_pdf_uretilir(): void
    {
        $kontrol = PeriyodikKontrol::firmaIcin($this->firma);
        $kontrol->update(['ekipmanlar' => [['ad' => 'Buhar Kazanı', 'kategori' => 'Basınçlı Kaplar', 'periyot_ay' => 12, 'son_kontrol_tarihi' => '2026-02-01', 'kontrol_eden' => 'A Tipi Muayene Ltd.', 'sonuc' => 'uygun']]]);

        $yanit = PeriyodikKontrolUretici::pdf($kontrol);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(PeriyodikKontrolSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
