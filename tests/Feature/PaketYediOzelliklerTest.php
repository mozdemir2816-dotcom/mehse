<?php

namespace Tests\Feature;

use App\Filament\Pages\Araclar;
use App\Filament\Pages\CariHesap;
use App\Filament\Pages\GeciciGorevlendirme;
use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\SertifikaOlustur;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Models\Sertifika;
use App\Models\User;
use App\Support\CariHesapYonetici;
use App\Support\GeciciGorevlendirmeUretici;
use App\Support\SertifikaUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class PaketYediOzelliklerTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
        $this->firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Örnek İnşaat A.Ş.',
            'tehlike_sinifi' => 'cok_tehlikeli',
            'calisan_sayisi' => 40,
            'aktif' => true,
        ]);
    }

    public function test_gecici_gorevlendirme_4_sablonla_pdf_ve_tc_maskeleme_uretir(): void
    {
        $calisan = Calisan::factory()->for($this->firma)->create([
            'ad_soyad' => 'Ali Kaya',
            'tc' => '12345678901',
            'gorev' => 'Elektrik Teknisyeni',
            'aktif' => true,
        ]);

        $this->assertSame('123******01', GeciciGorevlendirmeUretici::tcMaskele('12345678901', true));

        $yanit = GeciciGorevlendirmeUretici::pdf($this->firma, [
            'sablon' => 'gecici_is_iliskisi',
            'gorev_yeri' => 'Bursa Nilüfer Şantiyesi',
            'gorev_konusu' => 'Elektrik pano montajı',
            'baslangic_tarihi' => '2026-11-20',
            'bitis_tarihi' => '2026-12-20',
            'tc_maskele' => true,
            'calisanlar' => [['ad_soyad' => $calisan->ad_soyad, 'tc' => $calisan->tc, 'gorev' => $calisan->gorev]],
        ]);

        $this->assertSame(200, $yanit->getStatusCode());

        $this->get(GeciciGorevlendirme::getUrl())
            ->assertOk()
            ->assertSee('Geçici Görevlendirme')
            ->assertSee('4857 m.7');
    }

    public function test_sertifika_kimyasal_tipi_tc_maskeleme_ve_katilimci_basina_zip_calisir(): void
    {
        $this->assertArrayHasKey('kimyasal', config('isg.sertifika.tipler'));

        $sertifika = Sertifika::create([
            'firma_id' => $this->firma->id,
            'tip' => 'kimyasal',
            'tur' => 'ilk_defa',
            'sekil' => 'yuz_yuze',
            'cerceve' => 'sade',
            'katilimcilar' => [
                ['ad_soyad' => 'Ayşe Yılmaz', 'tc' => '11122233344', 'gorev' => 'Operatör'],
                ['ad_soyad' => 'Mehmet Demir', 'tc' => '55566677788', 'gorev' => 'Teknisyen'],
            ],
            'konu_icerigi' => ['tip' => 'ozel', 'baslik' => 'Kimyasal Güvenlik', 'maddeler' => []],
        ]);

        $htmlMaskeli = view('pdf.sertifika', [
            'sertifika' => $sertifika,
            'firma' => $this->firma,
            'imzali' => false,
            'tcMaskele' => true,
        ])->render();
        $this->assertStringContainsString('111******44', $htmlMaskeli);
        $this->assertStringNotContainsString('11122233344', $htmlMaskeli);

        $zipYanit = SertifikaUretici::zip($sertifika, false, true);
        $this->assertSame(200, $zipYanit->getStatusCode());

        $zipYolu = $zipYanit->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipYolu) === true);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();
        @unlink($zipYolu);

        Livewire::test(SertifikaOlustur::class)
            ->set('firmaId', $this->firma->id)
            ->assertSee('Kimyasal Güvenlik Sertifikası')
            ->assertSee('KVKK: Sertifikada T.C. Kimlik Numarasını Maskele');
    }

    public function test_ilo_kaza_siklik_agirlik_ve_insidans_orani_dogru_hesaplanir(): void
    {
        $c = Livewire::test(Araclar::class)
            ->set('kazaSayisi', 4)
            ->set('kayipGunSayisi', 40)
            ->set('toplamCalismaSaati', 200000)
            ->set('kazaCalisanSayisi', 100)
            ->set('oncekiDonemKso', 25.0)
            ->assertSee('Kaza İnsidans Oranı')
            ->assertSee('Önceki Dönem Kıyas');

        $this->assertSame(20.0, $c->instance()->sikikHizi());
        $this->assertSame(40.0, $c->instance()->insidansOrani());
        $this->assertSame(-20.0, $c->instance()->ksoDegisimYuzde());
        $this->assertStringContainsString('Kaza İnsidans Oranı', $c->instance()->kazaOzetiMetni());
    }

    public function test_komuta_merkezi_benim_gunum_bulgu_7_gun_ertele_ve_tamamla_calisir(): void
    {
        $bulgu = SahaBulgusu::create([
            'firma_id' => $this->firma->id,
            'uygunsuzluk' => 'İskele korkuluğu eksik',
            'durum' => 'acik',
            'termin' => today()->subDays(2),
        ]);

        Livewire::test(KontrolMerkezi::class)
            ->set('sekme', 'gunluk')
            ->assertSee('Benim Günüm')
            ->call('bulguErtele', $bulgu->id, 7);

        $bulgu->refresh();
        $this->assertSame('ertelendi', $bulgu->durum);
        $this->assertSame(today()->addDays(7)->toDateString(), $bulgu->termin->toDateString());

        Livewire::test(KontrolMerkezi::class)
            ->set('sekme', 'gunluk')
            ->call('bulguTamamla', $bulgu->id);

        $bulgu->refresh();
        $this->assertSame('kapandi', $bulgu->durum);
        $this->assertNotNull($bulgu->kapanis_tarihi);
    }

    public function test_osgb_cari_hesap_tahakkuk_tahsilat_ve_ekstre_pdf_calisir(): void
    {
        CariHesapYonetici::ucretKaydet($this->uzman, $this->firma->id, 10000.0, 20);
        $adet1 = CariHesapYonetici::buAyTahakkukOlustur($this->uzman, '2026-11');
        $adet2 = CariHesapYonetici::buAyTahakkukOlustur($this->uzman, '2026-11');

        // Mükerrer tahakkuk oluşturmamalı
        $this->assertSame(1, $adet1);
        $this->assertSame(0, $adet2);

        CariHesapYonetici::hareketEkle($this->uzman, $this->firma->id, 'tahsilat', 5000.0, '2026-11-20', 'Kısmi havale');

        $ozet = CariHesapYonetici::firmaOzeti($this->uzman->fresh(), $this->firma);
        $this->assertSame(12000.0, $ozet['toplam_borc']);
        $this->assertSame(5000.0, $ozet['toplam_tahsilat']);
        $this->assertSame(7000.0, $ozet['bakiye']);

        $pdf = CariHesapYonetici::ekstrePdf($this->uzman->fresh(), $this->firma);
        $this->assertSame(200, $pdf->getStatusCode());

        $this->get(CariHesap::getUrl())
            ->assertOk()
            ->assertSee('OSGB Cari')
            ->assertSee('Örnek İnşaat A.Ş.');
    }
}
