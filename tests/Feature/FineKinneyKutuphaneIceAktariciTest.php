<?php

namespace Tests\Feature;

use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Models\User;
use App\Support\FineKinneyKutuphaneIceAktarici;
use App\Support\RiskKutuphanesi;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Sektörel Fine-Kinney risk analizi Excel'ini Risk Kütüphanesine aktarma —
 * "Faaliyet Alanı" kategori olur, O/F/Ş taşınır.
 */
class FineKinneyKutuphaneIceAktariciTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function ornekExcel(): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        // Gerçek dosyadaki gibi: üstte lejant satırları, sonra başlık
        $s->fromArray(['İNŞAAT İSG RİSK VERİTABANI'], null, 'A1');
        $s->fromArray([
            'Sıra No', 'Faaliyet Alanı (Ana Kategori)', 'Alt Faaliyet / Bölüm', 'Tehlike Kaynağı',
            'Olası Risk & Sonuç', 'Riskten Etkilenenler', 'Olasılık', 'Frekans', 'Şiddet',
            'Alınması Gereken Önleyici ve Düzeltici Tedbirler', 'Yasal Mevzuat / Standart',
        ], null, 'A3');
        $s->fromArray([1, 'Kazı İşleri', 'Elle kazı', 'Göçük', 'Ezilme, ölüm', 'Çalışanlar', 3, 6, 15, 'İksa/şev hesabı yapılır.', 'Yapı İşleri Yön. Ek-4'], null, 'A4');
        $s->fromArray([2, 'Kazı İşleri', 'Makineyle kazı', 'Devrilme', 'Yaralanma', 'Operatör', 3, 3, 7, 'Manevracı bulundurulur.', 'İş Ekipmanları Yön.'], null, 'A5');
        $s->fromArray([3, 'Elektrik İşleri', 'Pano montajı', 'Elektrik çarpması', 'Ölüm', 'Çalışanlar', 6, 6, 15, 'Enerji kesilir, LOTO uygulanır.', 'Elektrik Tesisatı Yön.'], null, 'A6');

        $yol = tempnam(sys_get_temp_dir(), 'fk').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    /** "Beton dökümü" dosyasındaki gibi: 2 satırlık başlık + her iş kalemi ayrı sayfa. */
    private function ikiSatirBaslikVeCokSayfaExcel(): string
    {
        $kitap = new Spreadsheet;

        foreach (['Beton' => 'Betonarme ve Beton Dökümü', 'kazı' => 'Hafriyat ve Kazı İşleri'] as $sayfaAd => $kategori) {
            $s = $kitap->getSheetCount() === 1 && $kitap->getActiveSheet()->getHighestRow() === 1
                ? $kitap->getActiveSheet()->setTitle($sayfaAd)
                : $kitap->createSheet()->setTitle($sayfaAd);

            // Ana başlık (satır 3) + alt başlık (satır 4: O1/F1/S1 …)
            $s->fromArray([
                'Sıra No', 'Faaliyet Alanı (Ana Kategori)', 'Alt Faaliyet / Bölüm', 'Tehlike Kaynağı (Hazard)',
                'Olası Risk & Sonuç', 'MEVCUT DURUM RİSK DEĞERLENDİRMESİ', null, null, null, null,
                'Alınması Gereken Önleyici ve Düzeltici Tedbirler', 'ÖNLEMLER SONRASI', null, null, null, null, 'Sorumlu Birim / Termin',
            ], null, 'A3');
            $s->fromArray([
                null, null, null, null, null,
                'Olasılık (O1)', 'Frekans (F1)', 'Şiddet (S1)', 'Risk Skoru (R1)', 'Risk Seviyesi 1',
                null, 'Olasılık (O2)', 'Frekans (F2)', 'Şiddet (S2)', 'Risk Skoru (R2)', 'Risk Seviyesi 2', null,
            ], null, 'A4');
            $s->fromArray([1, $kategori, 'Süreç A', 'Tehlike '.$sayfaAd, 'Yaralanma', 3, 6, 7, 126, 'ÖNEMLİ RİSK', 'Önlem metni.', 0.2, 6, 7, 8.4, 'KABUL EDİLEBİLİR RİSK', 'Kısım Şefi'], null, 'A5');
            $s->fromArray([2, $kategori, 'Süreç B', 'Tehlike 2 '.$sayfaAd, 'Ölüm', 3, 6, 15, 270, 'YÜKSEK RİSK', 'Diğer önlem.', 0.2, 6, 7, 8.4, 'KABUL EDİLEBİLİR RİSK', 'Kısım Şefi'], null, 'A6');
        }

        $yol = tempnam(sys_get_temp_dir(), 'fk2').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_iki_satir_baslik_ve_cok_sayfali_dosya_tum_sayfalari_okur(): void
    {
        $sonuc = FineKinneyKutuphaneIceAktarici::iceAktar($this->ikiSatirBaslikVeCokSayfaExcel());

        $this->assertSame(4, $sonuc['basarili']);          // 2 sayfa × 2 satır
        $this->assertSame(2, $sonuc['yeniKategori']);

        $beton = TehlikeKategorisi::where('ad', 'Betonarme ve Beton Dökümü')->sole();
        $this->assertSame(2, $beton->tehlikeler()->count());

        $t = Tehlike::where('tehlike', 'Tehlike Beton')->sole();
        // Alt başlık satırındaki O1/F1/S1 doğru sütuna bağlandı; Risk Skoru sütunu (126) alınmadı
        $this->assertEqualsWithDelta(3.0, (float) $t->olasilik, 0.01);
        $this->assertEqualsWithDelta(6.0, (float) $t->frekans, 0.01);
        $this->assertEqualsWithDelta(7.0, (float) $t->siddet, 0.01);
        $this->assertSame('Süreç A', $t->faaliyet);
    }

    public function test_faaliyet_alani_kategori_olur_ve_ofs_yazilir(): void
    {
        $sonuc = FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());

        $this->assertSame(3, $sonuc['basarili']);
        $this->assertSame(2, $sonuc['yeniKategori']); // Kazı İşleri + Elektrik İşleri

        $kazi = TehlikeKategorisi::where('ad', 'Kazı İşleri')->sole();
        $this->assertSame(2, $kazi->tehlikeler()->count());

        $gocuk = Tehlike::where('tehlike', 'Göçük')->sole();
        $this->assertSame('Elle kazı', $gocuk->faaliyet);
        $this->assertEqualsWithDelta(3.0, (float) $gocuk->olasilik, 0.01);
        $this->assertEqualsWithDelta(6.0, (float) $gocuk->frekans, 0.01);
        $this->assertEqualsWithDelta(15.0, (float) $gocuk->siddet, 0.01);
        $this->assertStringContainsString('İksa', $gocuk->mevcut_onlem);
    }

    public function test_ikinci_yukleme_ayni_maddeyi_gunceller_kopya_olusmaz(): void
    {
        FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());
        FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());

        $this->assertSame(3, Tehlike::count());
        $this->assertSame(2, TehlikeKategorisi::count());
    }

    public function test_maddeye_cevir_kutuphanedeki_ofs_degerini_tasir(): void
    {
        FineKinneyKutuphaneIceAktarici::iceAktar($this->ornekExcel());
        $t = Tehlike::where('tehlike', 'Elektrik çarpması')->sole();

        $madde = RiskKutuphanesi::maddeyeCevir($t);

        $this->assertSame(6.0, $madde['olasilik']);
        $this->assertSame(6.0, $madde['frekans']);
        $this->assertSame(15.0, $madde['siddet']);
        $this->assertSame($t->id, $madde['tehlike_id']);
    }

    public function test_list_sayfasindan_fine_kinney_yukleme_aksiyonu(): void
    {
        $dosya = UploadedFile::fake()->createWithContent('insaat-fk.xlsx', file_get_contents($this->ornekExcel()));

        Livewire::test(ListTehlikes::class)
            ->callAction('fineKinneyYukle', ['dosya' => $dosya]);

        $this->assertGreaterThan(0, Tehlike::whereNotNull('olasilik')->count());
    }

    public function test_grup_toplu_sil_tek_kategori_varsa_onu_onceden_secer(): void
    {
        $k = TehlikeKategorisi::create(['ad' => 'Tek Kategori', 'anahtar' => 'tek_kategori', 'sira' => 1]);
        Tehlike::create(['tehlike_kategorisi_id' => $k->id, 'kod' => 'TEK-R-001', 'tehlike' => 'T', 'risk' => 'R', 'bolum' => 'B']);

        Livewire::test(ListTehlikes::class)
            ->mountAction(TestAction::make('grupSil')->table())
            ->assertActionDataSet(["kategori" => $k->id]);
    }
    /**
     * 08.11.2026: dışarıda hazırlanmış "MEVCUT DURUM / HEDEF DURUM" düzeni — başlık iki satır,
     * puan sütunları tek harf (İ / F / Ş), hücreler biçimli (zengin) metin, altta skala lejantı,
     * kategori sütunu yok ve sayfa adı genel ("Risk Değerlendirmesi").
     */
    private function mevcutHedefDuzeniExcel(): string
    {
        $zengin = function (string $metin) {
            $rt = new \PhpOffice\PhpSpreadsheet\RichText\RichText;
            $rt->createTextRun($metin)->getFont()->setBold(true);

            return $rt;
        };

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet()->setTitle('Risk Değerlendirmesi');
        $s->setCellValue('A1', $zengin('BİNA İÇİ MOBİL İSKELE KAPSAMLI İSG RİSK DEĞERLENDİRMESİ'));
        $s->setCellValue('A2', $zengin('FİNE-KİNNEY METODU İLE İSG RİSK ANALİZİ (2 MADDE)'));
        foreach (['A4' => 'No', 'B4' => 'Tehlike & Risk Konusu', 'C4' => 'Tehlikeli Durum / Olası Zarar ve Sonuç',
            'D4' => 'MEVCUT DURUM (ÖNLEM ÖNCESİ)', 'I4' => 'Alınacak Kontrol Önlemleri (DÖF)', 'J4' => 'HEDEF DURUM (ÖNLEM SONRASI)',
            'O4' => 'Sorumlu', 'P4' => 'Termin', 'Q4' => 'Yasal Dayanak & Standart Açıklaması',
            'D5' => 'İ', 'E5' => 'F', 'F5' => 'Ş', 'G5' => 'Skor (R)', 'H5' => 'Risk Düzeyi',
            'J5' => 'İ', 'K5' => 'F', 'L5' => 'Ş', 'M5' => 'Skor (R)', 'N5' => 'Risk Düzeyi'] as $hucre => $metin) {
            $s->setCellValue($hucre, $zengin($metin));
        }
        $s->mergeCells('D4:H4');
        $s->mergeCells('J4:N4');
        $s->fromArray([
            [1, 'Tekerlek frenlerinin açık unutulması', 'İskelenin kayması, düşme', 6, 6, 40, '=D6*E6*F6', 'Tolerans gösterilemez', 'Tekerlekler kilitlenmeli', 0.2, 6, 15, '=J6*K6*L6', 'Önemsiz', 'Formen', 'Her çalışma', 'TS EN 1004-1 Md. 7.2'],
            [2, 'Korkuluk eksikliği', 'Platformdan düşme', 3, 6, 40, '=D7*E7*F7', 'Esaslı', 'Korkuluk takılmalı', 0.2, 6, 15, '=J7*K7*L7', 'Önemsiz', 'Kurucu', 'Kurulumda', 'Yapı İşleri İSG Yön. Ek-IV'],
        ], null, 'A6');
        $s->setCellValue('B10', 'FİNE - KİNNEY RİSK DEĞERLENDİRME SKALA LEJANTI');
        $s->setCellValue('B11', 'R > 400');
        $s->setCellValue('C11', 'Tolerans Gösterilemez Risk');
        $s->setCellValue('B12', '200 < R <= 400');

        $yol = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid().'-mobil-iskele.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_tek_harfli_mevcut_hedef_duzeni_okunur_lejant_atlanir(): void
    {
        $sonuc = FineKinneyKutuphaneIceAktarici::iceAktar($this->mevcutHedefDuzeniExcel());

        $this->assertSame(2, $sonuc['basarili']);
        $this->assertSame(2, Tehlike::count());

        $t = Tehlike::where('tehlike', 'Tekerlek frenlerinin açık unutulması')->firstOrFail();
        $this->assertEquals([6, 6, 40], [(float) $t->olasilik, (float) $t->frekans, (float) $t->siddet]); // mevcut durum, hedef değil
        $this->assertSame('İskelenin kayması, düşme', $t->risk);
        $this->assertSame('Tekerlekler kilitlenmeli', $t->mevcut_onlem);
        $this->assertSame('TS EN 1004-1 Md. 7.2', $t->mevzuat);
        $this->assertSame('Bina İçi Mobil İskele Kapsamlı', $t->kategori->ad);
    }

    public function test_toplu_yukle_fine_kinney_dosyasini_otomatik_tanir(): void
    {
        $dosya = UploadedFile::fake()->createWithContent('mobil-iskele.xlsx', file_get_contents($this->mevcutHedefDuzeniExcel()));

        Livewire::test(ListTehlikes::class)
            ->callAction('excelYukle', ['dosya' => $dosya])
            ->assertNotified();

        $this->assertSame(2, Tehlike::whereNotNull('olasilik')->count());
    }
}
