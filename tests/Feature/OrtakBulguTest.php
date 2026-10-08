<?php

namespace Tests\Feature;

use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Models\User;
use App\Support\BulguDonusturucu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Saha kontrolleri "tek bulgu, çok çıktı" 1. aşama: saha_bulgulari ortak kayıt,
 * eski madde yapıları (DÖF, Saha Gözlem, Tespit-Öneri) kayıpsız dönüşür.
 */
class OrtakBulguTest extends TestCase
{
    use RefreshDatabase;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $uzman = User::factory()->create();
        $this->actingAs($uzman);
        $this->firma = Firma::factory()->for($uzman)->create();
    }

    public function test_ortak_alanlar_eklendi(): void
    {
        foreach (['yasal_gerekce', 'oncelik', 'hedef_olasilik', 'hedef_siddet', 'kok_neden', 'duzeltici', 'onleyici', 'kaynak_tablo', 'kaynak_kayit_id', 'kaynak_sira'] as $sutun) {
            $this->assertTrue(Schema::hasColumn('saha_bulgulari', $sutun), $sutun);
        }
    }

    public function test_oncelik_elle_verilmezse_5x5_skordan_turetilir(): void
    {
        $b = new SahaBulgusu(['olasilik' => 4, 'siddet' => 5]);
        $this->assertSame('kritik', $b->oncelikAnahtari());   // 20

        $b->oncelik = 'orta';
        $this->assertSame('orta', $b->oncelikAnahtari());     // elle verilen öncelikli

        foreach (array_keys(SahaBulgusu::ONCELIKLER) as $oncelik) {
            [$o, $s] = SahaBulgusu::onceliktenRisk($oncelik);
            $this->assertSame($oncelik, SahaBulgusu::skordanOncelik($o * $s), $oncelik);
        }
    }

    public function test_dof_maddesi_bulguya_ve_geri_kayipsiz_doner(): void
    {
        $madde = [
            'tespit' => 'Uçurum kenarında korkuluk yok',
            'oncelik' => 'yuksek',
            'oneri' => "1- Korkuluk kurulmalı\n2- Levha asılmalı",
            'sorumlu' => 'İşveren / İşveren Vekili',
            'termin' => '2026-10-15',
            'durum' => 'tamamlandi',
            'foto_yolu' => 'dof-foto/a.jpg',
            'kok_neden' => 'Planlama eksikliği',
            'duzeltici' => 'Korkuluk',
            'onleyici' => 'Kontrol listesi',
            'kapanis_tarihi' => '2026-10-10',
        ];

        $alanlar = BulguDonusturucu::dofMaddesinden($madde);
        $this->assertSame('kapandi', $alanlar['durum']);
        $this->assertSame(['dof-foto/a.jpg'], $alanlar['fotograflar']);

        $bulgu = SahaBulgusu::create($alanlar + ['firma_id' => $this->firma->id]);
        $geri = BulguDonusturucu::dofMaddesi($bulgu->fresh());

        $this->assertSame($bulgu->id, $geri['bulgu_id']);
        foreach (['tespit', 'oncelik', 'oneri', 'sorumlu', 'termin', 'durum', 'foto_yolu', 'kok_neden', 'duzeltici', 'onleyici', 'kapanis_tarihi'] as $anahtar) {
            $this->assertSame($madde[$anahtar], $geri[$anahtar], $anahtar);
        }
    }

    public function test_dof_tablosu_metin_oncelik_ve_tarihleri_anlasilir(): void
    {
        $alanlar = BulguDonusturucu::dofMaddesinden(['tespit' => 'X', 'oncelik' => 'Çok yüksek', 'termin' => '15.10.2026']);

        $this->assertSame('kritik', $alanlar['oncelik']);
        $this->assertSame('2026-10-15', $alanlar['termin']);
        $this->assertSame('acik', $alanlar['durum']);
    }

    public function test_gozlem_maddesi_risk_derecesi_ve_yasal_gerekceyle_doner(): void
    {
        $alanlar = BulguDonusturucu::gozlemMaddesinden([
            'tespit' => 'İskele korkuluğu eksik',
            'oneriler_metni' => 'Korkuluk tamamlanmalı',
            'yasal_gerekce' => 'Yapı İşlerinde İSG Yönetmeliği',
            'bina_bolge' => 'B blok',
            'kategori' => 'Yüksekte çalışma',
            'risk_derecesi' => 1,
            'kaynak' => 'foto',
            'foto_yolu' => 'saha-analiz-foto/b.jpg',
            'dof_sorumlu' => 'Şantiye şefi',
            'dof_termin' => '2026-10-20',
        ]);

        $this->assertSame('kritik', $alanlar['oncelik']);
        $this->assertSame('kritik', SahaBulgusu::skordanOncelik($alanlar['olasilik'] * $alanlar['siddet']));
        $this->assertSame('Yapı İşlerinde İSG Yönetmeliği', $alanlar['yasal_gerekce']);
        $this->assertSame('B blok', $alanlar['bolum']);
        $this->assertSame('ai', $alanlar['kaynak']);
        $this->assertSame('Şantiye şefi', $alanlar['sorumlu']);
        $this->assertSame('2026-10-20', $alanlar['termin']);
    }

    public function test_tespit_oneri_maddesi_dayanakla_doner(): void
    {
        $alanlar = BulguDonusturucu::tespitOneriMaddesinden(['tespit' => 'Yangın tüpü dolumu geçmiş', 'oneri' => 'Dolum yaptırılmalı', 'oncelik' => 'dusuk', 'dayanak' => 'Binaların Yangından Korunması Yönetmeliği']);

        $this->assertSame('dusuk', $alanlar['oncelik']);
        $this->assertSame('Binaların Yangından Korunması Yönetmeliği', $alanlar['yasal_gerekce']);
        $this->assertSame('Dolum yaptırılmalı', $alanlar['aksiyon']);
    }

    public function test_devam_eden_bulgu_termini_gecince_gecikmis_sayilir(): void
    {
        $b = SahaBulgusu::create(['firma_id' => $this->firma->id, 'uygunsuzluk' => 'X', 'durum' => 'devam_ediyor', 'termin' => today()->subDay()]);
        $this->assertTrue($b->terminGectiMi());

        $b->update(['durum' => 'kapandi']);
        $this->assertFalse($b->fresh()->terminGectiMi());
    }
}
