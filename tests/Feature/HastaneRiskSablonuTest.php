<?php

namespace Tests\Feature;

use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\ListRiskDegerlendirmesis;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\User;
use App\Support\HastaneRisk;
use App\Support\HastaneRiskExcelUretici;
use App\Support\HastaneRiskIceAktarici;
use App\Support\HastaneRiskPdfUretici;
use App\Support\HastaneRiskSablonu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Bursa Şehir Hastanesi risk formu (27.10.2026): olduğu gibi içe aktarma, hastane şablonunda
 * Excel / PDF çıktı, firma bazlı şablon seçimi.
 */
class HastaneRiskSablonuTest extends TestCase
{
    use RefreshDatabase;

    private const KAYNAK = '1-hastane-geneli.xlsx';

    private User $uzman;

    private Firma $hastane;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->hastane = Firma::factory()->for($this->uzman)->create(['unvan' => 'Bursa Şehir Hastanesi', 'risk_cikti_sablonu' => 'hastane']);
        $this->actingAs($this->uzman);
    }

    private function icAktar(): RiskDegerlendirmesi
    {
        $sayfalar = HastaneRiskIceAktarici::oku(HastaneRiskSablonu::yol(self::KAYNAK), ['HASTANE GENELİ']);
        $this->assertCount(1, $sayfalar);

        return HastaneRiskIceAktarici::kaydet($this->hastane, $sayfalar[0], 'HASTANE GENELİ', self::KAYNAK);
    }

    public function test_hastane_formu_olduğu_gibi_okunur_ve_ayri_degerlendirme_olur(): void
    {
        $rd = $this->icAktar();

        $this->assertTrue($rd->hastaneMi());
        $this->assertSame('fine_kinney', $rd->yontem);
        $this->assertSame('HASTANE GENELİ', $rd->proje_adi);
        $this->assertSame('2025-12-08', $rd->rapor_tarihi->toDateString());
        $this->assertSame('2027-12-08', $rd->gecerlilik_tarihi->toDateString());
        $this->assertSame(63, $rd->maddeler()->count());

        $ilk = $rd->maddeler()->orderBy('sira')->first();
        $this->assertSame('BÖLÜM 1: HASTANE GENELİ', $ilk->bolum);
        $this->assertSame([6.0, 3.0, 15.0], [$ilk->olasilik, $ilk->frekans, $ilk->siddet]);
        $this->assertSame(270.0, $ilk->puan);
        $this->assertSame('B', $ilk->duzey);
        $this->assertNotEmpty($ilk->etki_alani);
    }

    public function test_hastane_modunda_bos_son_risk_ve_sorumlu_varsayilanla_doldurulmaz_olcek_degismez(): void
    {
        $rd = RiskDegerlendirmesi::create(['firma_id' => $this->hastane->id, 'yontem' => 'fine_kinney', 'cikti_sablonu' => 'hastane']);

        // Ş = 40 (ölüm, standart ölçek) 30'a çevrilmez; son risk / sorumlu / termin boşsa boş kalır.
        $m = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Deneme', 'olasilik' => 3, 'frekans' => 2, 'siddet' => 40]);

        $this->assertSame(240.0, $m->puan);
        $this->assertSame('B', $m->duzey);
        $this->assertNull($m->son_olasilik);
        $this->assertNull($m->son_puan);
        $this->assertNull($m->sorumlu);
        $this->assertNull($m->termin);

        // Puan değişince RG harfi yeniden hesaplanır.
        $m->update(['olasilik' => 10, 'frekans' => 6, 'siddet' => 15]);
        $this->assertSame(900.0, $m->puan);
        $this->assertSame('A', $m->duzey);

        // Harf elle verilmişse (dosyadaki gibi) puan değişmedikçe korunur.
        $n = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Y', 'olasilik' => 3, 'frekans' => 6, 'siddet' => 3, 'duzey' => 'C']);
        $this->assertSame(54.0, $n->puan);
        $this->assertSame('C', $n->duzey);
    }

    public function test_standart_firmada_sistem_davranisi_degismez(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create();
        $rd = RiskDegerlendirmesi::create(['firma_id' => $firma->id, 'yontem' => 'fine_kinney']);
        $m = RiskMaddesi::create(['risk_degerlendirmesi_id' => $rd->id, 'tehlike' => 'Deneme', 'olasilik' => 3, 'frekans' => 2, 'siddet' => 15]);

        $this->assertFalse($rd->hastaneMi());
        $this->assertSame(1.0, $m->son_olasilik);          // standart: önlem sonrası olasılık varsayılan 1
        $this->assertSame(RiskMaddesi::VARSAYILAN_SORUMLU, $m->sorumlu);
    }

    public function test_hastane_firmasinda_yeni_fine_kinney_degerlendirmesi_hastane_sablonunda_acilir(): void
    {
        $fk = RiskDegerlendirmesi::create(['firma_id' => $this->hastane->id, 'yontem' => 'fine_kinney']);
        $matris = RiskDegerlendirmesi::create(['firma_id' => $this->hastane->id, 'yontem' => 'matris_5x5']);

        $this->assertTrue($fk->hastaneMi());
        $this->assertFalse($matris->hastaneMi());
    }

    public function test_excel_cikti_sablonu_korur_satirlar_ve_ozet_guncel(): void
    {
        $rd = $this->icAktar();
        $icerik = HastaneRiskExcelUretici::olustur($rd);

        $yol = tempnam(sys_get_temp_dir(), 'hrt').'.xlsx';
        file_put_contents($yol, $icerik);
        $kitap = IOFactory::load($yol);
        @unlink($yol);

        $ws = $kitap->getSheet(0);
        $this->assertSame(1, $kitap->getSheetCount());

        // Künye bandı kaynak şablondan aynen.
        $this->assertStringContainsString('SAĞLIK BAKANLIĞI', HastaneRiskSablonu::metin($ws, 'D', 1));
        $this->assertStringContainsString('RY.YD.03', HastaneRiskSablonu::metin($ws, 'A', 4));

        $duzen = HastaneRiskSablonu::duzen($ws);
        $satirlar = array_values(array_filter(range($duzen['ilk'], $duzen['son_veri']), fn ($r) => HastaneRiskSablonu::veriMi($ws, $r)));
        $this->assertCount(63, $satirlar);

        $ilk = $satirlar[0];
        $this->assertSame('270', (string) $ws->getCell('I'.$ilk)->getCalculatedValue());
        $this->assertSame('B', HastaneRiskSablonu::metin($ws, 'J', $ilk));
        $this->assertSame('FFC000', $ws->getStyle('J'.$ilk)->getFill()->getStartColor()->getRGB());

        // Excel satır sınırı ve sayfa düzeni: A4 yatay, genişliğe sığdır, yükseklik otomatik.
        foreach ($satirlar as $r) {
            $this->assertLessThanOrEqual(409.5, $ws->getRowDimension($r)->getRowHeight());
        }
        $ps = $ws->getPageSetup();
        $this->assertSame('landscape', $ps->getOrientation());
        $this->assertSame(9, $ps->getPaperSize());
        $this->assertSame([1, 0], [$ps->getFitToWidth(), $ps->getFitToHeight()]);

        // Alt bilgi özeti güncel sayılarla.
        $alt = '';
        for ($r = $duzen['footer']; $r <= $duzen['son']; $r++) {
            $alt .= HastaneRiskSablonu::metin($ws, 'A', $r).'|'.HastaneRiskSablonu::metin($ws, 'I', $r).'|';
        }
        $this->assertStringContainsString('TOPLAM RİSK SAYISI: 63', $alt);
        $this->assertStringContainsString('RİSK DEĞERLENDİRME EKİBİ', $alt);
    }

    public function test_cok_uzun_metin_satir_sinirina_sigsin_diye_yazi_kuculur(): void
    {
        $rd = $this->icAktar();
        $rd->maddeler()->first()->update(['oneri' => trim(str_repeat("Çok uzun düzeltici önleyici faaliyet metni, satıra sığmalı. \n", 200))]);

        $yol = tempnam(sys_get_temp_dir(), 'hrt').'.xlsx';
        file_put_contents($yol, HastaneRiskExcelUretici::olustur($rd->refresh()));
        $ws = IOFactory::load($yol)->getSheet(0);
        @unlink($yol);

        $r = HastaneRiskSablonu::duzen($ws)['ilk'] + 1; // 1. satır bölüm başlığı, 2. satır ilk madde
        $this->assertLessThanOrEqual(409.5, $ws->getRowDimension($r)->getRowHeight());
        $this->assertLessThan(12, $ws->getStyle('K'.$r)->getFont()->getSize());
    }

    public function test_pdf_uretilir(): void
    {
        $rd = $this->icAktar();
        $pdf = HastaneRiskPdfUretici::olustur($rd);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(10_000, strlen($pdf));
    }

    public function test_edit_sayfasinda_hastane_cikti_dugmeleri_standartlar_gizlenir(): void
    {
        $rd = $this->icAktar();

        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $rd->getRouteKey()])
            ->assertActionVisible('hastanePdf')
            ->assertActionVisible('hastaneExcel')
            ->assertActionHidden('word')
            ->assertActionHidden('excel')
            ->assertActionHidden('pdf');

        $standart = RiskDegerlendirmesi::create(['firma_id' => Firma::factory()->for($this->uzman)->create()->id, 'yontem' => 'fine_kinney']);
        Livewire::test(EditRiskDegerlendirmesi::class, ['record' => $standart->getRouteKey()])
            ->assertActionHidden('hastanePdf')
            ->assertActionVisible('word');
    }

    public function test_liste_sayfasinda_excel_yukle_dugmesi_var(): void
    {
        Livewire::test(ListRiskDegerlendirmesis::class)->assertActionExists('excelYukle');
    }

    public function test_rg_harfi_bantlari(): void
    {
        $this->assertSame('A', HastaneRisk::harf(400));
        $this->assertSame('B', HastaneRisk::harf(399));
        $this->assertSame('B', HastaneRisk::harf(200));
        $this->assertSame('C', HastaneRisk::harf(70));
        $this->assertSame('D', HastaneRisk::harf(20));
        $this->assertSame('E', HastaneRisk::harf(19.9));
        $this->assertNull(HastaneRisk::harf(null));
    }
}
