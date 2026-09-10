<?php

namespace Tests\Feature;

use App\Filament\Pages\KazaIstatistikleri as KazaIstatistikleriSayfasi;
use App\Models\Firma;
use App\Models\IsKazasiRaporu;
use App\Models\KazaIstatistigi;
use App\Models\OlayKaydi;
use App\Models\User;
use App\Support\KazaIstatistigiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class KazaIstatistikleriTest extends TestCase
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

    public function test_is_kazasi_raporu_ve_olay_kaydi_secilen_yila_gore_hesaba_katilir(): void
    {
        IsKazasiRaporu::create([
            'firma_id' => $this->firma->id, 'kaza_tarihi' => '2026-04-10', 'kayip_gun_sayisi' => 8,
        ]);
        IsKazasiRaporu::create([
            'firma_id' => $this->firma->id, 'kaza_tarihi' => '2025-11-01', 'kayip_gun_sayisi' => 3, // farklı yıl
        ]);
        OlayKaydi::create([
            'firma_id' => $this->firma->id, 'olay_tipi' => 'is_kazasi', 'olay_tarihi' => '2026-07-20', 'kayip_gun_sayisi' => 2,
        ]);
        OlayKaydi::create([
            'firma_id' => $this->firma->id, 'olay_tipi' => 'ramak_kala', 'olay_tarihi' => '2026-07-20', // iş kazası değil
        ]);

        $kayit = KazaIstatistigi::firmaYilIcin($this->firma, 2026);

        $this->assertSame(2, $kayit->kazaSayisi());
        $this->assertSame(10, $kayit->toplamKayipGun());
    }

    public function test_siklik_ve_agirlik_orani_hesaplanir(): void
    {
        IsKazasiRaporu::create(['firma_id' => $this->firma->id, 'kaza_tarihi' => '2026-03-01', 'kayip_gun_sayisi' => 20]);
        IsKazasiRaporu::create(['firma_id' => $this->firma->id, 'kaza_tarihi' => '2026-06-01', 'kayip_gun_sayisi' => 0]);

        $kayit = KazaIstatistigi::firmaYilIcin($this->firma, 2026);
        $kayit->update([
            'standart' => 'turkiye_1m',
            'aylik_veriler' => array_fill(0, 12, ['ort_calisan' => 50, 'calisma_saati' => 8000]),
        ]);
        $kayit->refresh();

        // Toplam saat = 12 × 8000 = 96.000
        $this->assertSame(96000, $kayit->toplamCalismaSaati());
        // Sıklık = 2 × 1.000.000 / 96.000 = 20.83
        $this->assertSame(20.83, $kayit->siklikOrani());
        // Ağırlık = 20 × 1.000.000 / 96.000 = 208.33
        $this->assertSame(208.33, $kayit->agirlikOrani());
    }

    public function test_calisma_saati_yoksa_oranlar_null(): void
    {
        IsKazasiRaporu::create(['firma_id' => $this->firma->id, 'kaza_tarihi' => '2026-03-01', 'kayip_gun_sayisi' => 5]);

        $kayit = KazaIstatistigi::firmaYilIcin($this->firma, 2026);

        $this->assertNull($kayit->siklikOrani());
        $this->assertNull($kayit->agirlikOrani());
    }

    public function test_osha_standardi_200bin_carpan_kullanir(): void
    {
        IsKazasiRaporu::create(['firma_id' => $this->firma->id, 'kaza_tarihi' => '2026-03-01', 'kayip_gun_sayisi' => 10]);

        $kayit = KazaIstatistigi::firmaYilIcin($this->firma, 2026);
        $kayit->update([
            'standart' => 'osha_200k',
            'aylik_veriler' => array_fill(0, 12, ['ort_calisan' => 10, 'calisma_saati' => 1600]),
        ]);
        $kayit->refresh();

        // Toplam saat = 19.200 ; Sıklık = 1 × 200.000 / 19.200 = 10.42
        $this->assertSame(10.42, $kayit->siklikOrani());
    }

    public function test_harici_kaza_eklenir_ve_hesaba_girer(): void
    {
        Livewire::test(KazaIstatistikleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yil', 2026)
            ->call('hariciKazaEkle')
            ->set('hariciKazalar.0.tarih', '2026-05-05')
            ->set('hariciKazalar.0.aciklama', 'Tutanağı sonradan girilecek kaza')
            ->set('hariciKazalar.0.kayip_gunu', 4)
            ->call('kaydet');

        $kayit = KazaIstatistigi::where('firma_id', $this->firma->id)->where('yil', 2026)->sole();
        $this->assertSame(1, $kayit->kazaSayisi());
        $this->assertSame(4, $kayit->toplamKayipGun());
    }

    public function test_olumlu_kaza_kayip_zamanli_sayilir(): void
    {
        IsKazasiRaporu::create([
            'firma_id' => $this->firma->id, 'kaza_tarihi' => '2026-02-02',
            'agirlik_derecesi' => 'olumlu', 'kayip_gun_sayisi' => 0,
        ]);

        $kayit = KazaIstatistigi::firmaYilIcin($this->firma, 2026);

        $this->assertSame(1, $kayit->olumluKazaSayisi());
        $this->assertSame(1, $kayit->kayipZamanliKazaSayisi());
    }

    public function test_pdf_ve_excel_uretilir(): void
    {
        IsKazasiRaporu::create(['firma_id' => $this->firma->id, 'kaza_tarihi' => '2026-03-01', 'kayip_gun_sayisi' => 6]);
        $kayit = KazaIstatistigi::firmaYilIcin($this->firma, 2026);
        $kayit->update(['aylik_veriler' => array_fill(0, 12, ['ort_calisan' => 20, 'calisma_saati' => 3500])]);

        foreach (['pdf' => '%PDF', 'excel' => 'PK'] as $metod => $imza) {
            $yanit = KazaIstatistigiUretici::$metod($kayit);
            $this->assertInstanceOf(StreamedResponse::class, $yanit);
            ob_start();
            $yanit->sendContent();
            $this->assertStringStartsWith($imza, ob_get_clean());
        }
    }

    public function test_yil_degisince_veriler_yeniden_yuklenir(): void
    {
        $k2025 = KazaIstatistigi::firmaYilIcin($this->firma, 2025);
        $k2025->update(['aylik_veriler' => array_fill(0, 12, ['ort_calisan' => 99, 'calisma_saati' => 1])]);

        Livewire::test(KazaIstatistikleriSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('yil', 2025)
            ->assertSet('aylikVeriler.0.ort_calisan', 99);
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(KazaIstatistikleriSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }
}
