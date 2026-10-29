<?php

namespace Tests\Feature;

use App\Filament\Pages\RiskSihirbazi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\EkipmanKatalogu;
use App\Support\KutuphaneEksikleri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Risk kütüphanesinde NACE / ekipman eksiklerinin uyarılması ve yakın eşleşme (28.10.2026). */
class KutuphaneEksikleriTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function firma(?string $nace): Firma
    {
        return Firma::factory()->for($this->uzman)->create(['nace_kodu' => $nace]);
    }

    private function ekipman(Firma $firma, string $ad, ?string $tip): IsEkipmani
    {
        return IsEkipmani::create(['firma_id' => $firma->id, 'ekipman_adi' => $ad, 'tip' => $tip, 'kategori' => 'uretim_atolye', 'aktif' => true]);
    }

    private function baglaKutuphane(string $tip): void
    {
        $k = TehlikeKategorisi::create(['anahtar' => 'ekipman_test_'.md5($tip), 'ad' => 'Ekipman – '.$tip, 'sira' => 1, 'yontem' => 'matris_5x5', 'nace_gruplari' => ['ortak'], 'ekipman_tipleri' => [$tip]]);
        Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => 'T-'.md5($tip), 'tehlike' => 'Test tehlikesi']);
    }

    public function test_nace_bos_bilinmeyen_veya_sektorsuzse_uyarir(): void
    {
        $bos = KutuphaneEksikleri::nace($this->firma(null));
        $this->assertStringContainsString('NACE kodu girilmemiş', $bos[0]['mesaj']);

        $bilinmeyen = KutuphaneEksikleri::nace($this->firma('99.99.99'));
        $this->assertStringContainsString('99.99.99 NACE kodu kütüphanede bulunamadı', $bilinmeyen[0]['mesaj']);

        // Grup tanınıyor (metal) ama kütüphanede o gruba ait kategori yok.
        $sektorsuz = KutuphaneEksikleri::nace($this->firma('28.29.01'));
        $this->assertStringContainsString('sektörel risk kütüphanede tanımlı değil', $sektorsuz[0]['mesaj']);

        // Gruba ait kategori + madde eklenince uyarı kalkar.
        $k = TehlikeKategorisi::create(['anahtar' => 'metal_test', 'ad' => 'Metal test', 'sira' => 1, 'yontem' => 'matris_5x5', 'nace_gruplari' => ['metal']]);
        Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => 'M-1', 'tehlike' => 'Dönen aksam']);
        $this->assertSame([], KutuphaneEksikleri::nace($this->firma('28.29.01')));
    }

    public function test_ekipman_tipi_yakin_yazimla_kutuphaneye_eslesir(): void
    {
        $this->baglaKutuphane('Forklift');

        $this->assertSame('Forklift', EkipmanKatalogu::tipBul('forklift'));
        $this->assertSame('Forklift', EkipmanKatalogu::tipBul('Forklıft'));
        $this->assertSame('Forklift', EkipmanKatalogu::tipBul('Forklift 3 ton (Dizel)'));
        $this->assertNull(EkipmanKatalogu::tipBul('Plazma Kesim'));
        $this->assertNull(EkipmanKatalogu::tipBul(''));

        $firma = $this->firma('28.29.01');
        $this->ekipman($firma, 'Depo forkliftı', 'forklıft');

        $this->assertSame([], KutuphaneEksikleri::ekipman($firma), 'Yakın yazım kütüphanede yok sayılmamalı.');
        $this->assertNotEmpty(EkipmanKatalogu::beklenenRiskler($firma));
    }

    public function test_olmayan_ve_tipsiz_ekipman_uyarilir_ayni_tip_bir_kez(): void
    {
        $firma = $this->firma('28.29.01');
        $this->ekipman($firma, 'Plazma 1', 'Plazma Kesim');
        $this->ekipman($firma, 'Plazma 2', 'plazma kesim');
        $this->ekipman($firma, 'Bant', null);

        $mesajlar = array_column(KutuphaneEksikleri::ekipman($firma), 'mesaj');

        $this->assertCount(2, $mesajlar);
        $this->assertStringContainsString("'Plazma Kesim' kütüphanede tanımlı değil", $mesajlar[0]);
        $this->assertStringContainsString("'Bant' için ekipman tipi seçilmemiş", $mesajlar[1]);
    }

    public function test_sihirbaz_ucuncu_adimda_eksikleri_gosterir(): void
    {
        $firma = $this->firma('99.99.99');
        $this->ekipman($firma, 'Plazma', 'Plazma Kesim');

        Livewire::test(RiskSihirbazi::class)
            ->set('firmaId', $firma->id)
            ->set('yontem', 'matris_5x5')
            ->set('yontemSecim', 'manuel')
            ->set('adim', 3)
            ->assertSee('Kütüphanede eksik tanım var (2)')
            ->assertSee('99.99.99 NACE kodu kütüphanede bulunamadı')
            ->assertSee("'Plazma Kesim' kütüphanede tanımlı değil");
    }
}
