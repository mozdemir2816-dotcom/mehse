<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskDegerlendirmesis\Pages\ExcelYukle;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Risk Değerlendirmeleri → Excel Yükle önizleme / eşleme düzeltme sayfası (28.10.2026).
 */
class RiskExcelYukleSayfasiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    private Firma $firma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->firma = Firma::factory()->for($this->uzman)->create();
        $this->actingAs($this->uzman);
    }

    private function dosya(array $satirlar, string $ad = 'Tank Isleri.xlsx'): UploadedFile
    {
        $kitap = new Spreadsheet;
        $kitap->getActiveSheet()->setTitle('Risk')->fromArray($satirlar, null, 'A1');
        $yol = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $icerik = file_get_contents($yol);
        unlink($yol);

        return UploadedFile::fake()->createWithContent($ad, $icerik);
    }

    public function test_sayfa_acilir_ve_liste_dugmesi_oraya_gider(): void
    {
        $this->get(ExcelYukle::getUrl())->assertOk();
    }

    public function test_dosya_secilince_eslesme_onizlenir_ve_onayla_yukle_kayit_acar(): void
    {
        $dosya = $this->dosya([
            ['Sıra', 'Aktivite / Görev', 'Tehlike', 'Olası Sonuçlar', 'Başlangıç S', 'Başlangıç P', 'İlave Kontrol'],
            [1, 'Tank girişi', 'Yanıcı buhar', 'Patlama', 5, 4, 'Gaz ölçümü yapın'],
            [2, 'Kaynak', 'Kıvılcım', 'Yangın', 4, 3, 'Yangın battaniyesi'],
        ]);

        $test = Livewire::test(ExcelYukle::class)->set('dosyalar', [$dosya]);

        $this->assertCount(1, $test->get('girdiler'));
        $sayfa = $test->get('girdiler.0.sayfalar.0');
        $this->assertFalse($sayfa['taninmadi']);
        $this->assertSame('tehlike', $sayfa['sutunlar'][3]);
        $this->assertSame('siddet', $sayfa['sutunlar'][5]);
        $this->assertSame('olasilik', $sayfa['sutunlar'][6]);

        $test->set('firma_id', $this->firma->id)->call('kaydet')->assertHasNoErrors();

        $rd = RiskDegerlendirmesi::where('firma_id', $this->firma->id)->firstOrFail();
        $this->assertSame('matris_5x5', $rd->yontem);
        $this->assertSame('Tank Isleri', $rd->proje_adi);
        $this->assertCount(2, $rd->maddeler);
    }

    public function test_yanlis_eslenen_sutun_duzeltilince_kayda_yansir(): void
    {
        $dosya = $this->dosya([
            ['Sıra', 'Aktivite / Görev', 'Tehlike', 'Olası Sonuçlar', 'Başlangıç S', 'Başlangıç P', 'İlave Kontrol'],
            [1, 'Tank girişi', 'Yanıcı buhar', 'Patlama', 5, 4, 'Gaz ölçümü yapın'],
        ]);

        Livewire::test(ExcelYukle::class)
            ->set('dosyalar', [$dosya])
            // Kullanıcı "İlave Kontrol" sütununu "Mevcut önlem" yapar, "Olası Sonuçlar"ı kullanmaz.
            ->set('girdiler.0.sayfalar.0.sutunlar.7', 'mevcut_onlem')
            ->set('girdiler.0.sayfalar.0.sutunlar.4', '')
            ->set('firma_id', $this->firma->id)
            ->call('kaydet');

        $madde = RiskDegerlendirmesi::where('firma_id', $this->firma->id)->firstOrFail()->maddeler->first();
        $this->assertSame('Gaz ölçümü yapın', $madde->mevcut_onlem);
        $this->assertNull($madde->oneri);
    }

    public function test_basligi_taninmayan_sayfa_elle_eslenir(): void
    {
        $dosya = $this->dosya([
            ['Ürün', 'Ne olur', 'Etki', 'x', 'y'],
            ['Forklift', 'Devrilme', 'Ezilme', 3, 5],
            ['Pres', 'Sıkışma', 'Uzuv kaybı', 4, 4],
        ]);

        $test = Livewire::test(ExcelYukle::class)->set('dosyalar', [$dosya]);
        $this->assertTrue($test->get('girdiler.0.sayfalar.0.taninmadi'));

        $test->set('firma_id', $this->firma->id)->call('kaydet');
        $this->assertSame(0, RiskDegerlendirmesi::count(), 'Tehlike eşlenmeden kayıt açılmamalı.');

        $test->set('girdiler.0.sayfalar.0.kullan', true)
            ->set('girdiler.0.sayfalar.0.sutunlar.1', 'faaliyet')
            ->set('girdiler.0.sayfalar.0.sutunlar.2', 'tehlike')
            ->set('girdiler.0.sayfalar.0.sutunlar.4', 'olasilik')
            ->set('girdiler.0.sayfalar.0.sutunlar.5', 'siddet')
            ->call('kaydet');

        $rd = RiskDegerlendirmesi::where('firma_id', $this->firma->id)->firstOrFail();
        $this->assertCount(2, $rd->maddeler);
        $this->assertSame('Devrilme', $rd->maddeler->first()->tehlike);
    }

    public function test_mukerrer_maddeler_yazilmaz(): void
    {
        $dosya = $this->dosya([
            ['Sıra', 'Faaliyet', 'Tehlike', 'Risk', 'Olasılık', 'Şiddet'],
            [1, 'Kalıp', 'Yüksekten düşme', 'Yaralanma', 3, 5],
            [2, 'Kalıp', 'yüksekten  düşme', 'Yaralanma', 3, 5],
            [3, 'Beton', 'Yüksekten düşme', 'Yaralanma', 3, 5],
        ]);

        Livewire::test(ExcelYukle::class)->set('dosyalar', [$dosya])->set('firma_id', $this->firma->id)->call('kaydet');

        $this->assertCount(2, RiskDegerlendirmesi::where('firma_id', $this->firma->id)->firstOrFail()->maddeler);
    }

    public function test_ayni_alan_iki_sutuna_atanirsa_kayit_engellenir(): void
    {
        $dosya = $this->dosya([
            ['Sıra', 'Tehlike', 'Başlangıç S', 'Başlangıç P'],
            [1, 'Yanıcı buhar', 5, 4],
        ]);

        Livewire::test(ExcelYukle::class)
            ->set('dosyalar', [$dosya])
            ->set('girdiler.0.sayfalar.0.sutunlar.1', 'tehlike')
            ->set('firma_id', $this->firma->id)
            ->call('kaydet');

        $this->assertSame(0, RiskDegerlendirmesi::count());
    }

    public function test_baska_uzmanin_firmasina_yuklenemez(): void
    {
        $baska = Firma::factory()->for(User::factory()->create())->create();
        $dosya = $this->dosya([['Sıra', 'Tehlike', 'Başlangıç S', 'Başlangıç P'], [1, 'Yanıcı buhar', 5, 4]]);

        try {
            Livewire::test(ExcelYukle::class)->set('dosyalar', [$dosya])->set('firma_id', $baska->id)->call('kaydet');
        } catch (\Throwable) {
            // Firma bulunamadı (404) — beklenen.
        }

        $this->assertSame(0, RiskDegerlendirmesi::count());
    }
}
