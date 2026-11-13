<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\YasalTakvim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** 13.11.2026: Kontrol Merkezi > Günlük Akış — portföyün süreli yasal yükümlülükleri. */
class YasalTakvimTest extends TestCase
{
    use RefreshDatabase;

    public function test_gecen_ve_yaklasan_kalemleri_listeler_uzak_olani_almaz(): void
    {
        $uzman = User::factory()->create();
        $firma = Firma::factory()->for($uzman)->create(['calisan_sayisi' => 5, 'sozlesme_baslangic' => now()->subMonths(2)]);
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()->subYears(2), 'gecerlilik_tarihi' => now()->subDays(3), 'durum' => 'yayinlandi']);
        $ekipman = ['firma_id' => $firma->id, 'kategori' => 'kaldirma_iletme', 'aktif' => true, 'son_muayene_tarihi' => now()->subMonth()];
        IsEkipmani::create($ekipman + ['ekipman_adi' => 'Forklift', 'sonraki_vize_tarihi' => now()->addDays(20)]);
        IsEkipmani::create($ekipman + ['ekipman_adi' => 'Vinç', 'sonraki_vize_tarihi' => now()->addYear()]);

        $k = YasalTakvim::kalemler($uzman->id, 90)->keyBy('tur');

        $this->assertSame('gecikti', $k['risk']['durum']);
        $this->assertSame(-3, $k['risk']['kalan_gun']);
        $this->assertSame('yaklasiyor', $k['ekipman']['durum']);
        $this->assertStringContainsString('1 ekipmanın vizesi (toplam 2)', $k['ekipman']['aciklama']);
        // Tatbikat: sözleşme başlangıcı + 1 yıl → 90 gün dışında, listede yok.
        $this->assertFalse($k->has('acil'));
    }

    public function test_elli_ustu_firmada_hic_kurul_yoksa_gecikmis_sayar(): void
    {
        $uzman = User::factory()->create();
        Firma::factory()->for($uzman)->create(['calisan_sayisi' => 60, 'tehlike_sinifi' => 'tehlikeli']);

        $kurul = YasalTakvim::kalemler($uzman->id)->firstWhere('tur', 'kurul');

        $this->assertNotNull($kurul);
        $this->assertSame('gecikti', $kurul['durum']);
        $this->assertNull($kurul['tarih']);
    }
}
