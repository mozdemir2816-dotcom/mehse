<?php

namespace Tests\Feature;

use App\Filament\Pages\EgitimKatilim;
use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Pages\YillikPlan\YillikEgitimPlani;
use App\Models\Firma;
use App\Models\User;
use App\Models\YillikPlan;
use App\Support\YillikPlanExcelUretici;
use App\Support\YillikPlanSablonu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Yıllık plan Excel çıktıları (kullanıcının VİZYON şablonları) + şantiye
 * şablonu + eğitim planı 4. bölümünün Eğitim Katılım formuna aktarılması.
 */
class YillikPlanExcelTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    private function insaatFirmasi(array $ek = []): Firma
    {
        return Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Örnek İnşaat Ltd. Şti.', 'nace_kodu' => '41.00.01', 'tehlike_sinifi' => 'cok_tehlikeli',
            'adres' => 'Merkez Mah. No:1', 'sgk_sicil_no' => '12345', ...$ek,
        ]);
    }

    public function test_insaat_firmasi_saptanir(): void
    {
        $this->assertTrue(YillikPlanSablonu::santiyeMi($this->insaatFirmasi()));
        $this->assertFalse(YillikPlanSablonu::santiyeMi(Firma::factory()->for($this->uzman)->create(['nace_kodu' => '10.71.01', 'is_kalemleri' => null])));
    }

    public function test_insaat_firmasinin_yeni_plani_santiye_sablonuyla_acilir(): void
    {
        $plan = YillikPlan::firmaYilIcin($this->insaatFirmasi(), 2026);

        $this->assertCount(36, $plan->faaliyetler);
        $this->assertSame('PLANLAMA VE DOKÜMANTASYON', $plan->faaliyetler[0]['ana_konu']);
        $ozgu = collect($plan->egitimler)->where('kategori', 'ise_ozgu')->pluck('konu')->all();
        $this->assertSame(['Yüksekte Çalışma', 'Kazı ve zemin işleri', 'Kaldırma ve saha trafiği', 'Elektrik ve özel işler', 'Sahaya özgü acil durum', 'Kalıp, demir ve beton'], $ozgu);
    }

    public function test_sozlesme_oncesi_aylar_bos_kalir(): void
    {
        $firma = $this->insaatFirmasi(['sozlesme_baslangic' => '2026-09-07']);
        $plan = YillikPlan::firmaYilIcin($firma, 2026);

        $aylar = collect($plan->egitimler)->firstWhere('konu', 'Yüksekte Çalışma')['aylar'];
        $this->assertSame('bos', $aylar[7]);        // Ağustos
        $this->assertSame('planlandi', $aylar[8]);  // Eylül
    }

    public function test_egitim_plani_excel_sablon_duzeninde_uretilir(): void
    {
        $plan = YillikPlan::firmaYilIcin($this->insaatFirmasi(), 2026);
        $s = YillikPlanExcelUretici::egitimDoldur($plan)->getSheet(0);

        $this->assertSame('2026 YILLIK EĞİTİM PLANI', $s->getCell('C1')->getValue());
        $this->assertStringContainsString('ÖRNEK İNŞAAT LTD. ŞTİ.', $s->getCell('H1')->getValue());
        $this->assertSame(' İŞE VE İŞYERİNE ÖZGÜ RİSKLER ', $s->getCell('A27')->getValue());
        $this->assertSame('Yüksekte Çalışma', $s->getCell('B27')->getValue());
        // Ocak, 2. hafta = F; 5. Diğer Eğitimlerde Kasım 3. hafta = AU.
        $this->assertSame('P', $s->getCell('F27')->getValue());
        $this->assertSame('P', $s->getCell('AU33')->getValue());
        $this->assertSame('A1:BC41', $s->getPageSetup()->getPrintArea());
        $this->assertArrayHasKey('A32', $s->getBreaks());
        $this->assertArrayHasKey('CF', $s->getHeaderFooter()->getImages()); // imza şeridi
    }

    public function test_egitim_plani_bolumu_genisler_ve_daralir(): void
    {
        $firma = $this->insaatFirmasi();
        $plan = YillikPlan::firmaYilIcin($firma, 2026);
        $egitimler = collect($plan->egitimler)->reject(fn ($e) => $e['kategori'] === 'ise_ozgu')->values()->all();
        foreach (range(1, 9) as $i) {
            $egitimler[] = ['konu' => "Özgü konu {$i}", 'kategori' => 'ise_ozgu', 'hafta' => 1, 'aylar' => array_fill(0, 12, 'bos')];
        }
        $plan->update(['egitimler' => $egitimler]);

        $s = YillikPlanExcelUretici::egitimDoldur($plan->fresh())->getSheet(0);

        // 4. bölüm 6 → 9 satır: 27..35, 5. bölüm 36'dan başlar, alt satırlar 3 kayar.
        $this->assertSame('Özgü konu 9', $s->getCell('B35')->getValue());
        $this->assertSame('5. DİĞER EĞİTİMLER', $s->getCell('A36')->getValue());
        $this->assertArrayHasKey('A27:A35', $s->getMergeCells());
        $this->assertSame('A1:BC44', $s->getPageSetup()->getPrintArea());
        $this->assertArrayHasKey('A35', $s->getBreaks());
    }

    public function test_calisma_plani_excel_sablon_duzeninde_uretilir(): void
    {
        $plan = YillikPlan::firmaYilIcin($this->insaatFirmasi(), 2026);
        $plan->update(['faaliyetler' => collect($plan->faaliyetler)->map(function ($f, $i) {
            if ($i === 0) {
                $f['aylar'][0] = 'tamamlandi';
            }

            return $f;
        })->all()]);

        $kitap = YillikPlanExcelUretici::calismaDoldur($plan->fresh());
        $s = $kitap->getSheetByName('Yıllık Çalışma Planı');

        $this->assertSame('İŞ SAĞLIĞI VE GÜVENLİĞİ YILLIK ÇALIŞMA PLANI – 2026', $s->getCell('C1')->getValue());
        $this->assertSame('RİSK YÖNETİMİ', $s->getCell('B11')->getValue());
        $this->assertSame('P', $s->getCell('F8')->getValue());
        $this->assertSame('G', $s->getCell('G8')->getValue());
        $this->assertStringContainsString('Kayıt/Not: İSG yönetim sistemi', $s->getCell('AD8')->getValue());
        $this->assertSame([], $s->getTableNames()); // şablondaki bozuk Excel tablosu kaldırıldı
        $this->assertStringNotContainsString('_x000a_', $s->getHeaderFooter()->getOddFooter());
        $this->assertSame('2026 İSG YILLIK ÇALIŞMA PLANI – AYLIK ÖZET', $kitap->getSheetByName('Aylık Özet')->getCell('A1')->getValue());
    }

    public function test_calisma_plani_fazla_faaliyette_satir_ve_ozet_formulleri_genisler(): void
    {
        $plan = YillikPlan::firmaYilIcin($this->insaatFirmasi(), 2026);
        $faaliyetler = $plan->faaliyetler;
        foreach (range(1, 4) as $i) {
            $faaliyetler[] = ['ana_konu' => 'EK', 'faaliyet' => "Ek faaliyet {$i}", 'aylar' => array_fill(0, 12, 'planlandi')];
        }
        $plan->update(['faaliyetler' => $faaliyetler]);

        $kitap = YillikPlanExcelUretici::calismaDoldur($plan->fresh());
        $s = $kitap->getSheetByName('Yıllık Çalışma Planı');

        $this->assertSame('Ek faaliyet 4', $s->getCell('C47')->getValue());
        $this->assertSame(40, $s->getCell('A47')->getValue());
        $this->assertStringContainsString('$B$8:$B$47', $kitap->getSheetByName('Aylık Özet')->getCell('B4')->getValue());
        // 40 satır ortadan bölünür: 1–20 / 21–40 (satır 27'den sonra sayfa sonu), imza 48'de.
        $this->assertSame(['A27'], array_keys($s->getBreaks()));
        $this->assertSame('A1:AE48', $s->getPageSetup()->getPrintArea());
    }

    public function test_calisma_plani_a4_yatay_ortadan_bolunur_imza_tablonun_altinda(): void
    {
        $igu = \App\Models\IsgProfesyoneli::factory()->for($this->uzman)->create(['ad_soyad' => 'Ali Uzman']);
        $firma = $this->insaatFirmasi(['igu_id' => $igu->id, 'isveren_vekili' => 'Veli Patron']);
        $s = YillikPlanExcelUretici::calismaDoldur(YillikPlan::firmaYilIcin($firma, 2026))
            ->getSheetByName('Yıllık Çalışma Planı');
        $ayar = $s->getPageSetup();

        // 38 satır (36 faaliyet + 2 numaralı boş satır) → 1–19 / 20–38: satır 26'dan sonra sayfa sonu.
        $this->assertSame(['A26'], array_keys($s->getBreaks()));
        $this->assertSame(37, $s->getCell('A44')->getValue());
        $this->assertSame(38, $s->getCell('A45')->getValue());
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4, $ayar->getPaperSize());
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE, $ayar->getOrientation());
        $this->assertFalse($ayar->getFitToPage()); // "sığdır" elle sayfa sonunu yok sayar
        $this->assertGreaterThan(62, $ayar->getScale());
        $this->assertSame([1, 7], $ayar->getRowsToRepeatAtTop());
        $this->assertSame('A1:AE46', $ayar->getPrintArea());

        // İmza tablonun hemen altında (satır 46), alt bilgide artık imza yok.
        $this->assertStringContainsString('VELİ PATRON', $s->getCell('B46')->getValue());
        $this->assertStringContainsString('ALİ UZMAN', $s->getCell('G46')->getValue());
        $this->assertStringContainsString('İŞYERİ HEKİMİ', $s->getCell('U46')->getValue());
        $this->assertStringNotContainsString('İŞ GÜVENLİĞİ UZMANI', $s->getHeaderFooter()->getOddFooter());
    }

    public function test_sayfadan_excel_indirilir_ve_standart_sablon_uygulanir(): void
    {
        $firma = $this->insaatFirmasi();
        $plan = YillikPlan::firmaYilIcin($firma, 2026);
        $plan->update(['faaliyetler' => []]);

        Livewire::test(YillikCalismaPlani::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026)
            ->callAction('sablonuUygula')
            ->callAction('calismaExcel')
            ->assertFileDownloaded('yillik-calisma-plani-ornek-insaat-ltd-sti-2026.xlsx');

        $this->assertCount(36, $plan->fresh()->faaliyetler);

        Livewire::test(YillikEgitimPlani::class)
            ->set('firmaId', $firma->id)
            ->set('yil', 2026)
            ->callAction('egitimExcel', ['imzali' => '1'])
            ->assertFileDownloaded('yillik-egitim-plani-ornek-insaat-ltd-sti-2026.xlsx');
    }

    public function test_egitim_katilim_isyerine_ozgu_bolumu_yillik_plandan_gelir(): void
    {
        $firma = $this->insaatFirmasi();
        YillikPlan::firmaYilIcin($firma, (int) now()->year);

        $icerik = Livewire::test(EgitimKatilim::class)
            ->set('firmaId', $firma->id)
            ->get('icerik');

        $this->assertStringContainsString('Yıllık Eğitim Planı', $icerik['isyerine_ozgu']['sektor']);
        $this->assertSame('Yüksekte Çalışma', $icerik['isyerine_ozgu']['maddeler'][0]['madde']);
        $this->assertCount(6, $icerik['isyerine_ozgu']['maddeler']);
        // Çok tehlikeli ilk eğitim bloğu 180 dk → 6 konuya 30 dk.
        $this->assertSame(30, $icerik['isyerine_ozgu']['maddeler'][0]['dakika']);
    }
}
