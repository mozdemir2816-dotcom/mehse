<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\IseDonusBelgesi;
use App\Models\IsKazasiRaporu;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use App\Support\KullaniciAyarlari;
use App\Support\PortfoyKarne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Olay bazlı kriterler (iş kazası, meslek hastalığı...) yalnız takip edilir,
 * eksik sayılmaz; yıllık değerlendirme geçen yıldan atalı firmada, işe dönüş
 * muayenesi + işbaşı eğitimi yalnız kaza olan firmada beklenir.
 */
class KontrolMerkeziTakipKriterTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        KullaniciAyarlari::onbellegiTemizle();
        $this->uzman = User::factory()->create();
    }

    private function satir(Firma $firma, string $anahtar): array
    {
        return collect(PortfoyKarne::firmaChecklistDetay($firma->fresh()))->firstWhere('anahtar', $anahtar);
    }

    public function test_is_kazasi_kaydi_yoksa_eksik_degil_takip(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        $this->assertSame('takip', $this->satir($firma, 'is_kazasi_bildirimi')['durum']);
        $this->assertSame('takip', $this->satir($firma, 'meslek_hastaligi_bildirimi')['durum']);
        $this->assertSame('takip', $this->satir($firma, 'calisma_izin_formu')['durum']);
        $this->assertSame([], PortfoyKarne::eksikFirmalar($this->uzman->id, 'is_kazasi_bildirimi')->all());

        $kriter = collect(PortfoyKarne::kriterler($this->uzman->id))->firstWhere('anahtar', 'is_kazasi_bildirimi');
        $this->assertTrue($kriter['takip']);
    }

    public function test_takip_kriterleri_matris_oranina_girmez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'matris_5x5', 'rapor_tarihi' => now()]);
        $tum = array_column(config('isg.kontrol_merkezi.kriterler'), 'anahtar');
        KullaniciAyarlari::kaydet($this->uzman, [
            'kontrol_haric' => array_values(array_diff($tum, ['risk_degerlendirmesi', 'is_kazasi_bildirimi'])),
        ]);

        // İş kazası takipte: yalnız risk değerlendirmesi sayılır → %100, tam uyumlu.
        $this->assertSame(100, PortfoyKarne::firmaKriterMatrisi($this->uzman->id)[0]['oran']);
        $this->assertSame(1, PortfoyKarne::ozet($this->uzman->id)['tam_uyumlu']);
        $this->assertSame(100, (int) PortfoyKarne::ozet($this->uzman->id)['uyum_yuzde']);

        // Takip listesi boşaltılınca iş kazası da eksik sayılır → %50.
        KullaniciAyarlari::kaydet($this->uzman, ['kontrol_yalniz_takip' => []]);
        $this->assertSame(50, PortfoyKarne::firmaKriterMatrisi($this->uzman->id)[0]['oran']);
        $this->assertSame(0, PortfoyKarne::ozet($this->uzman->id)['tam_uyumlu']);
    }

    public function test_kullanici_ayarla_kriteri_takibe_alabilir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $this->assertSame('eksik', $this->satir($firma, 'acil_durum_tatbikat')['durum']);

        KullaniciAyarlari::kaydet($this->uzman, ['kontrol_yalniz_takip' => ['acil_durum_tatbikat']]);

        $this->assertSame('takip', $this->satir($firma, 'acil_durum_tatbikat')['durum']);
        // Varsayılan takip listesinin yerine geçer.
        $this->assertSame('eksik', $this->satir($firma, 'is_kazasi_bildirimi')['durum']);
    }

    public function test_yillik_degerlendirme_bu_yil_atanan_firmada_beklenmez(): void
    {
        $yeni = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => now()->startOfYear()->addDay()]);
        $eski = Firma::factory()->for($this->uzman)->create(['sozlesme_baslangic' => now()->subYear()]);

        $this->assertTrue($this->satir($yeni, 'yillik_degerlendirme')['tamam']);
        $this->assertSame('eksik', $this->satir($eski, 'yillik_degerlendirme')['durum']);
    }

    public function test_kaza_olunca_ise_donus_ve_isbasi_egitimi_beklenir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();

        // Kaza yoksa muaf.
        $this->assertTrue($this->satir($firma, 'ise_donus_muayenesi')['tamam']);
        $this->assertTrue($this->satir($firma, 'kaza_sonrasi_isbasi_egitimi')['tamam']);

        IsKazasiRaporu::create(['firma_id' => $firma->id, 'kazazede_ad_soyad' => 'Ali Veli', 'kaza_tarihi' => now()->subDays(10)]);

        $this->assertSame('eksik', $this->satir($firma, 'ise_donus_muayenesi')['durum']);
        $this->assertSame('eksik', $this->satir($firma, 'kaza_sonrasi_isbasi_egitimi')['durum']);

        // Kazadan ÖNCEKİ işbaşı eğitimi saymaz.
        IsbasiEgitimTutanagi::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'Ali Veli', 'egitim_tarihi' => now()->subDays(30)]);
        $this->assertSame('eksik', $this->satir($firma, 'kaza_sonrasi_isbasi_egitimi')['durum']);

        IsbasiEgitimTutanagi::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'Ali Veli', 'egitim_tarihi' => now()->subDays(2)]);
        IseDonusBelgesi::create(['firma_id' => $firma->id, 'calisan_ad_soyad' => 'Ali Veli', 'ise_donus_tarihi' => now()->subDays(3)]);

        $this->assertSame('tamamlandi', $this->satir($firma, 'ise_donus_muayenesi')['durum']);
        $this->assertSame('tamamlandi', $this->satir($firma, 'kaza_sonrasi_isbasi_egitimi')['durum']);
    }
}
