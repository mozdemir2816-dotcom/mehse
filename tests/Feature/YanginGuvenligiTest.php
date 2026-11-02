<?php

namespace Tests\Feature;

use App\Filament\Pages\YanginGuvenligi as YanginSayfasi;
use App\Models\Firma;
use App\Models\User;
use App\Models\YanginGuvenligiDegerlendirmesi;
use App\Support\YanginGuvenligiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class YanginGuvenligiTest extends TestCase
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

    public function test_yuksek_riskli_madde_secilince_sinif_yuksek_olur(): void
    {
        $d = new YanginGuvenligiDegerlendirmesi([
            'kullanim_turu' => 'buro',
            'riskler' => ['Basınçlı gaz tüpleri', 'Yanıcı / parlayıcı sıvı (tiner, solvent, yakıt)'],
        ]);

        $this->assertSame('yuksek', $d->hesaplaTehlikeSinifi());
    }

    public function test_endustriyel_yapi_risksiz_bile_en_az_orta(): void
    {
        $d = new YanginGuvenligiDegerlendirmesi(['kullanim_turu' => 'endustriyel', 'riskler' => []]);
        $this->assertSame('orta', $d->hesaplaTehlikeSinifi());
    }

    public function test_dusuk_riskli_buro_dusuk_sinif(): void
    {
        $d = new YanginGuvenligiDegerlendirmesi(['kullanim_turu' => 'buro', 'riskler' => []]);
        $this->assertSame('dusuk', $d->hesaplaTehlikeSinifi());
    }

    public function test_saving_hook_sinifi_yazar_elle_degilse(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->update(['kullanim_turu' => 'depolama', 'riskler' => ['Yüksek raflı depolama (> 4 m)']]);

        $this->assertSame('yuksek', $d->refresh()->belirlenen_tehlike_sinifi);
    }

    public function test_elle_sinif_secilince_hesap_ezilmez(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->belirlenen_tehlike_sinifi = 'dusuk';
        $d->sinif_elle = true;
        $d->riskler = ['Yanıcı / parlayıcı sıvı (tiner, solvent, yakıt)'];
        $d->save();

        $this->assertSame('dusuk', $d->refresh()->belirlenen_tehlike_sinifi);
    }

    public function test_kutuphaneden_tespit_eklenir_ve_kaydedilir(): void
    {
        Livewire::test(YanginSayfasi::class)
            ->set('firmaId', $this->firma->id)
            ->set('kullanimTuru', 'endustriyel')
            ->set('riskler', ['Boya kabini / solventli yüzey işlem'])
            ->call('tespitEkle', 0)
            ->call('tespitEkle', 0)   // kopya eklemez
            ->call('serbestTespitEkle', 'Yangın butonu ilave edilecek')
            ->call('kaydet');

        $d = YanginGuvenligiDegerlendirmesi::where('firma_id', $this->firma->id)->sole();
        $this->assertCount(2, $d->tespitler);
        $this->assertSame('yuksek', $d->belirlenen_tehlike_sinifi);
    }

    public function test_pdf_uretilir(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->update([
            'kullanim_turu' => 'endustriyel', 'taban_alani_m2' => 2400, 'kat_sayisi' => 2, 'kullanici_yuku' => 120,
            'bolumler' => ['Üretim / atölye', 'Kimyasal depolama alanı'],
            'riskler' => ['Yanıcı kimyasal madde', 'Kaynak / taşlama / sıcak çalışma'],
            'tespitler' => [['madde' => 'Sprinkler değerlendirilmeli', 'oncelik' => 'yuksek']],
        ]);

        $yanit = YanginGuvenligiUretici::pdf($d);
        ob_start();
        $yanit->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());
    }

    public function test_baska_uzmanin_firmasi_secilemez(): void
    {
        $baska = User::factory()->create();
        $baskaFirma = Firma::factory()->for($baska)->create();

        $firmalar = Livewire::test(YanginSayfasi::class)->instance()->firmalar();
        $this->assertArrayNotHasKey($baskaFirma->id, $firmalar);
    }

    /** 02.11.2026: BYKHY Md.32 / Ek-5/A kullanıcı yükü ve Md.99/2 söndürücü adedi (resmî metinden). */
    public function test_kullanici_yuku_ve_sondurucu_adedi_bykhy_ye_gore_hesaplanir(): void
    {
        $this->firma->update(['calisan_sayisi' => 10]);
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->update([
            'kullanim_turu' => 'endustriyel', 'taban_alani_m2' => 2500, 'kat_sayisi' => 2, 'kullanici_yuku' => 1, // tüm katların toplamı
            'bolumler' => ['Üretim / atölye', 'Ürün veya ham madde deposu'],
            'tespitler' => [['madde' => 'Yangın söndürme cihazları (YSC) her 200 m² için 1 adet ve max 25 m aralıkla yerleştirilmeli', 'oncelik' => 'orta']],
        ]);
        $d = $d->fresh();

        // 2500 m² ÷ 10 m²/kişi = 250 kişi > çalışan 10 > beyan 1
        $this->assertSame(2500.0, $d->toplamAlan());
        $this->assertSame(['katsayi' => 10.0, 'hesaplanan' => 250, 'girilen' => 1, 'calisan' => 10, 'esas' => 250], $d->kullaniciYukuHesabi());

        // Endüstriyel → en az orta tehlike → her 250 m² için 1 adet: 2500 / 250 = 10
        $this->assertSame('orta', $d->belirlenen_tehlike_sinifi);
        $ysc = $d->yscHesabi();
        $this->assertSame([10, 250, ['Ürün veya ham madde deposu']], [$ysc['adet'], $ysc['birim'], $ysc['tekerlekli']]);

        // Eski "200 m²" tespiti raporda firmaya göre hesaplanmış metinle değişir.
        $madde = $d->raporTespitleri()[0]['madde'];
        $this->assertStringContainsString('Toplam 2.500 m² kapalı alan (2 katın toplamı) için en az 10 adet 6 kg ABC', $madde);
        $this->assertStringContainsString("her 250 m²'ye 1 adet", $madde);
        $this->assertStringNotContainsString('200 m²', $madde);

        // Düşük tehlike: her 500 m²; alan küçükse en az 1. Alan yoksa hesap yok, çalışan sayısı esas.
        $buro = new YanginGuvenligiDegerlendirmesi(['kullanim_turu' => 'buro', 'taban_alani_m2' => 120]);
        $buro->belirlenen_tehlike_sinifi = 'dusuk';
        $this->assertSame([1, 500], [$buro->yscHesabi()['adet'], $buro->yscHesabi()['birim']]);
        $alansiz = new YanginGuvenligiDegerlendirmesi(['kullanim_turu' => 'karma']);
        $alansiz->setRelation('firma', $this->firma);
        $this->assertNull($alansiz->yscHesabi());
        $this->assertSame(10, $alansiz->kullaniciYukuHesabi()['esas']);

        // Ekran ve PDF
        Livewire::test(YanginSayfasi::class)->set('firmaId', $this->firma->id)
            ->assertSee('10 adet 6 kg KKT')->assertSee('250 kişi');
        $html = view('pdf.yangin-guvenligi', ['kayit' => $d, 'firma' => $this->firma])->render();
        $this->assertStringContainsString('<b>250 kişi</b>', $html);
        $this->assertStringContainsString('en az 10 adet 6 kg KKT', $html);
        $this->assertStringContainsString('İşveren / İşveren Vekili', $html);
    }

    public function test_tespit_onceligi_ekrandan_degistirilir(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->update(['tespitler' => [['madde' => 'Yangın dolabı / hidrant sayısı ve yerleşimi yönetmeliğe uygun hale getirilmeli', 'oncelik' => 'orta']]]);

        Livewire::test(YanginSayfasi::class)->set('firmaId', $this->firma->id)
            ->call('oncelikDegistir', 0)->assertSet('tespitler.0.oncelik', 'yuksek')
            ->call('kaydet');

        $this->assertSame('yuksek', $d->fresh()->tespitler[0]['oncelik']);
    }

    /** Uzman önerisi (ör. her 200 m²) yönetmelik asgarisiyle (250 m²) birlikte yazılır; gevşek öneri yok sayılır. */
    public function test_uzman_onerisi_sondurucu_yonetmelik_asgarisiyle_birlikte_yazilir(): void
    {
        $d = YanginGuvenligiDegerlendirmesi::firmaIcin($this->firma);
        $d->update(['kullanim_turu' => 'endustriyel', 'riskler' => ['Yanıcı kimyasal madde'], 'taban_alani_m2' => 1520, 'kat_sayisi' => 2,
            'tespitler' => [['madde' => 'Yangın söndürme cihazları (YSC) her 250 m² için 1 adet', 'oncelik' => 'orta']]]);

        $this->assertSame([7, null], [$d->fresh()->yscHesabi()['adet'], $d->fresh()->yscHesabi()['uzman_adet']]);

        Livewire::test(YanginSayfasi::class)->set('firmaId', $this->firma->id)
            ->set('yscUzmanM2', 200)->assertSee('uzman önerisi 8 adet')->call('kaydet');

        $d = $d->fresh();
        $this->assertSame('yuksek', $d->belirlenen_tehlike_sinifi);
        $this->assertSame([7, 200, 8], [$d->yscHesabi()['adet'], $d->yscHesabi()['uzman_m2'], $d->yscHesabi()['uzman_adet']]);
        $madde = $d->raporTespitleri()[0]['madde'];
        $this->assertStringContainsString("Toplam 1.520 m² kapalı alan (2 katın toplamı) için her 200 m²'ye 1 adet düşecek şekilde en az 8 adet 6 kg ABC", $madde);
        $this->assertStringContainsString("BYKHY Md.99/2 asgarisi her 250 m²'ye 1 adet = 7 adet", $madde);
        $html = view('pdf.yangin-guvenligi', ['kayit' => $d, 'firma' => $this->firma])->render();
        $this->assertStringContainsString('<b>Uzman önerisi:</b> her 200 m²', $html);

        $d->update(['ysc_uzman_m2' => 400]); // yönetmelikten gevşek → yok sayılır
        $this->assertNull($d->fresh()->yscHesabi()['uzman_m2']);
    }
}
