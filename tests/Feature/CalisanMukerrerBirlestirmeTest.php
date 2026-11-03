<?php

namespace Tests\Feature;

use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\RelationManagers\CalisanlarRelationManager;
use App\Models\AcilEkip;
use App\Models\AtamaYazisi;
use App\Models\Calisan;
use App\Models\EgitimKaydi;
use App\Models\Firma;
use App\Models\User;
use App\Support\CalisanMukerrerBirlestirici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalisanMukerrerBirlestirmeTest extends TestCase
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

    /** Vizyon vakası: T.C.'siz eski kayıt + Excel'den gelen T.C.'li/görevli yeni kayıt. */
    private function cift(string $ad = 'EYÜP KAÇAR'): array
    {
        $eski = Calisan::factory()->for($this->firma)->create(['ad_soyad' => $ad, 'tc' => null, 'gorev' => null, 'sube' => 'ŞENER USTA EKİBİ', 'aktif' => true, 'agir_tehlikeli_iste' => true]);
        $yeni = Calisan::factory()->for($this->firma)->create(['ad_soyad' => $ad, 'tc' => '22072952964', 'gorev' => 'Beton Kalıpçısı', 'sube' => 'TAŞERON ŞENEL USTA', 'aktif' => true, 'agir_tehlikeli_iste' => false]);

        return [$eski, $yeni];
    }

    public function test_onizleme_veri_yazmaz_ve_degisenleri_gosterir(): void
    {
        [$eski, $yeni] = $this->cift();
        EgitimKaydi::create(['calisan_id' => $eski->id, 'tur' => 'is_sagligi_guvenligi_egitimi', 'tarih' => '2025-01-01']);

        $o = CalisanMukerrerBirlestirici::onizle($this->firma);

        $this->assertSame(1, $o['grup']);
        $this->assertSame(1, $o['silinecek']);
        $this->assertSame($eski->id, $o['satirlar'][0]['tutulan']);
        $this->assertSame([$yeni->id], $o['satirlar'][0]['silinecek']);
        $this->assertArrayHasKey('tc', $o['satirlar'][0]['degisen']);
        $this->assertSame('Beton Kalıpçısı', $o['satirlar'][0]['degisen']['gorev'][1]);
        $this->assertSame(2, Calisan::where('firma_id', $this->firma->id)->count(), 'önizleme yazmamalı');
    }

    public function test_birlestirme_eski_kaydi_tutar_gecmisi_tasir_ve_alanlari_birlestirir(): void
    {
        [$eski, $yeni] = $this->cift();
        $baska = Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Başka Kişi']);

        // Eğitim kaydı çakışması: aynı tür — yeni tarihli (yeni kayıttaki) kazanır; farklı tür taşınır.
        EgitimKaydi::create(['calisan_id' => $eski->id, 'tur' => 'is_sagligi_guvenligi_egitimi', 'tarih' => '2024-01-01']);
        EgitimKaydi::create(['calisan_id' => $yeni->id, 'tur' => 'is_sagligi_guvenligi_egitimi', 'tarih' => '2026-02-02']);
        EgitimKaydi::create(['calisan_id' => $yeni->id, 'tur' => 'yuksekte_calisma', 'tarih' => '2026-03-03']);

        // Acil ekip: ikisi de aynı ekipte → tek üye kalır.
        $ekip = AcilEkip::create(['firma_id' => $this->firma->id, 'tur' => 'sondurme', 'ad' => 'Söndürme']);
        $ekip->uyeler()->create(['calisan_id' => $eski->id, 'ad_soyad' => 'EYÜP KAÇAR']);
        $ekip->uyeler()->create(['calisan_id' => $yeni->id, 'ad_soyad' => 'EYÜP KAÇAR']);

        // Atama yazısında yeni kayda bağlı üye.
        $atama = AtamaYazisi::create(['firma_id' => $this->firma->id, 'rol_anahtari' => 'sondurme_ekibi', 'tarih' => now(), 'uyeler' => [['calisan_id' => $yeni->id, 'ad_soyad' => 'EYÜP KAÇAR']]]);

        $sonuc = CalisanMukerrerBirlestirici::birlestir($this->firma);

        $this->assertSame(1, $sonuc['grup']);
        $this->assertSame(1, $sonuc['silinen']);

        $this->assertNull(Calisan::find($yeni->id));
        $this->assertSame(2, Calisan::where('firma_id', $this->firma->id)->count());   // eski + başka
        $this->assertNotNull(Calisan::find($baska->id));

        $eski->refresh();
        $this->assertSame('22072952964', $eski->tc);
        $this->assertSame('Beton Kalıpçısı', $eski->gorev);
        $this->assertSame('TAŞERON ŞENEL USTA', $eski->sube, 'en yeni dolu değer');
        $this->assertTrue($eski->agir_tehlikeli_iste, 'evet/hayır: biri evet ise evet');
        $this->assertTrue($eski->aktif);

        // Eğitim kayıtları: aynı tür tek kayıt (yeni tarihli), farklı tür taşındı; hepsi tutulan kişide.
        $kayitlar = EgitimKaydi::where('calisan_id', $eski->id)->orderBy('tur')->get();
        $this->assertCount(2, $kayitlar);
        $this->assertSame('2026-02-02', $kayitlar->firstWhere('tur', 'is_sagligi_guvenligi_egitimi')->tarih->toDateString());
        $this->assertSame(0, EgitimKaydi::where('calisan_id', $yeni->id)->count());

        // Ekipte tek üye; atama yazısı tutulan kişiyi gösterir.
        $this->assertSame(1, $ekip->uyeler()->where('calisan_id', $eski->id)->count());
        $this->assertSame(1, $ekip->uyeler()->count());
        $this->assertSame($eski->id, $atama->fresh()->uyeler[0]['calisan_id']);
    }

    public function test_farkli_tcli_ayni_adlilar_birlestirilmez_ve_baska_firmaya_dokunulmaz(): void
    {
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'Ali Veli', 'tc' => '11111111111']);
        Calisan::factory()->for($this->firma)->create(['ad_soyad' => 'ALİ VELİ', 'tc' => '22222222222']);
        $diger = Firma::factory()->for($this->uzman)->create();
        Calisan::factory()->for($diger)->create(['ad_soyad' => 'Ali Veli']);

        $o = CalisanMukerrerBirlestirici::onizle($this->firma);
        $this->assertSame(0, $o['grup']);
        $this->assertCount(1, $o['atlanan']);

        $this->assertSame(0, CalisanMukerrerBirlestirici::birlestir($this->firma)['grup']);
        $this->assertSame(2, Calisan::where('firma_id', $this->firma->id)->count());
        $this->assertSame(1, Calisan::where('firma_id', $diger->id)->count());
    }

    public function test_onizleme_metni_kisi_kayit_ve_degisiklikleri_gosterir(): void
    {
        [$eski, $yeni] = $this->cift();

        $html = (string) \App\Filament\Resources\Calisans\CalisanAksiyonlari::mukerrerOnizlemeHtml($this->firma);

        $this->assertStringContainsString('EYÜP KAÇAR', $html);
        $this->assertStringContainsString('1</strong> mükerrer kayıt silinecek', $html);
        $this->assertStringContainsString('#'.$eski->id.' ← #'.$yeni->id, $html);
        $this->assertStringContainsString('Görev: — → Beton Kalıpçısı', $html);

        $this->assertSame('Bu firmada mükerrer (aynı adlı) personel yok.', \App\Filament\Resources\Calisans\CalisanAksiyonlari::mukerrerOnizlemeHtml(Firma::factory()->for($this->uzman)->create()));
        $this->assertSame('Önce firma seçin.', \App\Filament\Resources\Calisans\CalisanAksiyonlari::mukerrerOnizlemeHtml(null));
    }

    public function test_arayuzde_onay_olmadan_birlestirilmez(): void
    {
        $this->cift();

        $rm = fn () => Livewire::test(CalisanlarRelationManager::class, ['ownerRecord' => $this->firma, 'pageClass' => EditFirma::class]);

        $rm()->callTableAction('mukerrerBirlestir', data: ['onay' => false])->assertHasTableActionErrors();
        $this->assertSame(2, Calisan::where('firma_id', $this->firma->id)->count());

        $rm()->callTableAction('mukerrerBirlestir', data: ['onay' => true])->assertHasNoTableActionErrors();
        $this->assertSame(1, Calisan::where('firma_id', $this->firma->id)->count());
    }
}
