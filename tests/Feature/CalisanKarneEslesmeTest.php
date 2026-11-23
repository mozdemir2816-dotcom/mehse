<?php

namespace Tests\Feature;

use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\EgitimKaydi;
use App\Models\Firma;
use App\Models\MuayeneFormu;
use App\Models\SaglikGozetimi;
use App\Models\User;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** K3 (23.11.2026): çalışan eğitim/muayene eşleşmesi kimlik + TC ile, geçerlilik süresiyle. */
class CalisanKarneEslesmeTest extends TestCase
{
    use RefreshDatabase;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $uzman = User::factory()->create();
        $this->actingAs($uzman);
        $this->firma = Firma::factory()->for($uzman)->create(['tehlike_sinifi' => 'cok_tehlikeli']);
    }

    private function calisan(string $ad, ?string $tc = null): Calisan
    {
        return Calisan::create(['firma_id' => $this->firma->id, 'ad_soyad' => $ad, 'tc' => $tc, 'aktif' => true]);
    }

    private function eksikAdlar(string $anahtar): array
    {
        return PortfoyKarne::calisanKarne($this->firma)[$anahtar]->pluck('ad_soyad')->sort()->values()->all();
    }

    public function test_katilim_formunda_tc_ile_eslesir_yazim_farki_onemsiz(): void
    {
        $this->calisan('Mehmet Ali Işık', '11111111110');
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'belge_tarihi' => now()->subMonth(),
            'katilimcilar' => [['ad_soyad' => 'M. Ali ISIK', 'tc' => '11111111110']]]);

        $this->assertSame([], $this->eksikAdlar('egitim_eksik'));
    }

    public function test_ayni_adli_iki_calisanda_ad_ile_eslesme_yapilmaz(): void
    {
        $this->calisan('Ahmet Yılmaz', '22222222220');
        $this->calisan('Ahmet Yılmaz', '33333333330');
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'belge_tarihi' => now()->subMonth(),
            'katilimcilar' => [['ad_soyad' => 'AHMET YILMAZ']]]);

        $this->assertCount(2, PortfoyKarne::calisanKarne($this->firma)['egitim_eksik']);
    }

    public function test_tc_siz_kayitta_tekil_ad_turkce_harf_farkiyla_eslesir(): void
    {
        $this->calisan('İsmail Işık');
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'belge_tarihi' => now()->subMonth(),
            'katilimcilar' => [['ad_soyad' => '  İSMAİL  IŞIK ']]]);

        $this->assertSame([], $this->eksikAdlar('egitim_eksik'));
    }

    public function test_egitim_suresi_dolunca_eksik_ve_suresi_dolan_olarak_isaretlenir(): void
    {
        $eski = $this->calisan('Eski Eğitimli', '44444444440');
        $this->calisan('Yeni Eğitimli', '55555555550');
        EgitimKaydi::create(['calisan_id' => $eski->id, 'tur' => 'is_sagligi_guvenligi_egitimi', 'tarih' => now()->subMonths(13)]); // çok tehlikeli: 1 yıl
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'belge_tarihi' => now()->subMonths(2),
            'katilimcilar' => [['ad_soyad' => 'Yeni Eğitimli', 'tc' => '55555555550']]]);

        $karne = PortfoyKarne::calisanKarne($this->firma);
        $this->assertSame(['Eski Eğitimli'], $karne['egitim_eksik']->pluck('ad_soyad')->all());
        $this->assertSame([$eski->id], $karne['egitim_suresi_dolan']);
    }

    public function test_basarisiz_katilimci_egitimli_sayilmaz(): void
    {
        $this->calisan('Kalan Kişi', '66666666660');
        EgitimKatilim::create(['firma_id' => $this->firma->id, 'belge_tarihi' => now()->subMonth(),
            'katilimcilar' => [['ad_soyad' => 'Kalan Kişi', 'tc' => '66666666660', 'katildi' => false]]]);

        $this->assertSame(['Kalan Kişi'], $this->eksikAdlar('egitim_eksik'));
    }

    public function test_muayene_formu_onerilen_kontrol_tarihi_ve_gozetim_satiri_sayilir(): void
    {
        $a = $this->calisan('Formlu', '77777777770');
        $b = $this->calisan('Gözetimli', '88888888880');
        $this->calisan('Süresi Dolan', '99999999990');

        MuayeneFormu::create(['firma_id' => $this->firma->id, 'calisan_tc' => '77777777770', 'calisan_ad_soyad' => 'Formlu',
            'muayene_tarihi' => now()->subMonths(2), 'onerilen_kontrol_tarihi' => now()->addMonths(10)]);
        MuayeneFormu::create(['firma_id' => $this->firma->id, 'calisan_tc' => '99999999990', 'calisan_ad_soyad' => 'Süresi Dolan',
            'muayene_tarihi' => now()->subMonths(14)]);   // çok tehlikeli: yılda bir
        SaglikGozetimi::create(['firma_id' => $this->firma->id, 'satirlar' => [
            ['calisan_id' => $b->id, 'calisan_adi' => 'Gözetimli', 'tetkik_turu' => 'periyodik', 'tarih' => now()->subMonths(3)->toDateString(), 'sonuc' => 'uygun'],
        ]]);

        $karne = PortfoyKarne::calisanKarne($this->firma);
        $this->assertSame(['Süresi Dolan'], $karne['muayene_eksik']->pluck('ad_soyad')->all());
        $this->assertCount(1, $karne['muayene_suresi_dolan']);
        $this->assertNotContains($a->id, $karne['muayene_suresi_dolan']);
    }
}
