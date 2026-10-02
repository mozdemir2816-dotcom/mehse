<?php

namespace Tests\Feature;

use App\Filament\Pages\TesisUygunlukOzeti;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\IsIzinFormu;
use App\Models\SahaBulgusu;
use App\Models\Taseron;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use App\Support\TesisUygunlugu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TesisUygunlukOzetiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Ahmet Yapı', 'tehlike_sinifi' => 'tehlikeli', 'calisan_sayisi' => 0]);
    }

    public function test_temiz_isyeri_tam_puan(): void
    {
        $s = TesisUygunlugu::hesapla($this->firma, $this->uzman);

        $this->assertSame(100, $s['skor']);
        $this->assertSame('Uygun', $s['seviye']);
        $this->assertSame([], $s['dokum']);
        $this->assertSame(0, $s['sayilar']['toplam_dikkat']);
    }

    public function test_sorunlar_aciklanabilir_puan_dusurur(): void
    {
        $this->firma->update(['calisan_sayisi' => 10]);   // 10 × 20 dk = 200 dk gerekli, ziyaret yok → kapasite kritik

        Taseron::create(['firma_id' => $this->firma->id, 'tur' => 'taseron', 'unvan' => 'Beta Taşeron', 'aktif' => true, 'sozlesme_bitis' => now()->subDay()]);
        IsIzinFormu::create(['firma_id' => $this->firma->id, 'durum' => 'onaylandi', 'baslangic' => now()->subDays(2), 'bitis' => now()->subDay()]);
        IsEkipmani::create(['firma_id' => $this->firma->id, 'kategori' => 'kaldirma', 'ekipman_adi' => 'Vinç', 'muayene_periyodu_ay' => 12,
            'son_muayene_tarihi' => now()->subMonths(13), 'sonraki_vize_tarihi' => now()->subMonth()]);
        SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'Korkuluk yok', 'termin' => now()->subDays(2)]);
        DofRaporu::create(['firma_id' => $this->firma->id, 'maddeler' => [
            ['tespit' => 'A', 'termin' => now()->addDays(5)->toDateString(), 'durum' => 'acik'],
            ['tespit' => 'B', 'termin' => now()->subDays(5)->toDateString(), 'durum' => 'tamamlandi'],
        ]]);

        $s = TesisUygunlugu::hesapla($this->firma, $this->uzman);
        $n = $s['sayilar'];

        $this->assertSame(1, $n['aktif_taseron']);
        $this->assertSame(1, $n['taseron_belge']);
        $this->assertSame(1, $n['ptw']);
        $this->assertSame(1, $n['periyodik']);
        $this->assertSame(1, $n['denetim']);
        $this->assertSame(2, $n['acik_aksiyon']);        // DÖF açık + saha bulgusu
        $this->assertSame(1, $n['gecikmis_aksiyon']);    // saha bulgusu termini geçti
        $this->assertSame(1, $n['kapasite_kritik']);

        // 100 − (4 belge + 6 sözleşme + 5 PTW + 5 periyodik + 2 denetim + 3 gecikmiş + 12 kapasite) = 63
        $this->assertSame(63, $s['skor']);
        $this->assertSame('Müdahale gerekli', $s['seviye']);
        $this->assertSame(100 - $s['skor'], array_sum(array_column($s['dokum'], 'puan')));
        $this->assertSame('kritik', $s['oneriler'][0]['seviye']);
        $this->assertContains('Açık aksiyonları takip et', array_column($s['oneriler'], 'baslik'));
    }

    public function test_planli_ziyaret_kapasite_acigini_kapatir(): void
    {
        $this->firma->update(['calisan_sayisi' => 6]);   // 6 × 20 = 120 dk
        ZiyaretProgrami::create(['firma_id' => $this->firma->id, 'yil' => now()->year, 'ziyaretler' => [
            now()->month - 1 => [['tarih' => now()->addDay()->toDateString(), 'sure_saat' => '2', 'durum' => 'planlandi']],
        ]]);

        $s = TesisUygunlugu::hesapla($this->firma, $this->uzman);

        $this->assertSame(120, $s['kapasite']['planli']);
        $this->assertSame(0, $s['sayilar']['kapasite_kritik'] + $s['sayilar']['kapasite_uyari']);
    }

    public function test_sayfa_firma_secimi_ve_izolasyon(): void
    {
        $diger = Firma::factory()->for($this->uzman)->create(['unvan' => 'Gama']);
        $yabanci = Firma::factory()->for(User::factory())->create(['unvan' => 'Yabancı']);

        $sayfa = Livewire::test(TesisUygunlukOzeti::class)
            ->set('firmaId', $diger->id)
            ->assertSee('İşyeri İSG operasyon skoru')
            ->assertSee('Skor dökümü')
            ->assertDontSee('Yabancı');

        $sayfa->set('firmaId', $yabanci->id);
        $this->assertNull($sayfa->instance()->sonuc);

        $this->get(TesisUygunlukOzeti::getUrl(['firma' => $this->firma->id]))->assertOk()->assertSee('Ahmet Yapı');
    }
}
