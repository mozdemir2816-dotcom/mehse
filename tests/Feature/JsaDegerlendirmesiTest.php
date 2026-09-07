<?php

namespace Tests\Feature;

use App\Filament\Pages\JsaDegerlendirmesi as JsaSayfasi;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Models\JsaSablonu;
use App\Models\User;
use App\Support\JsaExcelOkuyucu;
use App\Support\JsaUretici;
use App\Support\JsaWordUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class JsaDegerlendirmesiTest extends TestCase
{
    use RefreshDatabase;

    private User $uzman;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uzman = User::factory()->create();
        $this->actingAs($this->uzman);
    }

    /** Örnek DUVAR ÖRME dosyasıyla aynı yerleşimde bir JSA .xlsx üretir. */
    private function jsaXlsx(): string
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();

        $s->setCellValue('A1', 'İŞ GÜVENLİĞİ ANALİZİ (JSA) - DUVAR ÖRME İŞLERİ');
        $s->mergeCells('A1:H1');
        $s->setCellValue('A2', 'Doküman Ref: JSA-BLOCK-001');
        $s->setCellValue('F2', 'Rev: 00 | Tarih: 31 Temmuz 2026');

        $s->fromArray(
            ['Sıra No', 'İş Adımı / Faaliyet', 'Olası Tehlikeler', 'Olası Sonuçlar / Riskler', 'Başlangıç Risk Seviyesi', "Kontrol Tedbirleri (Gerekli KKD'ler Dahil)", 'Kalıntı Risk Seviyesi', 'Sorumlu'],
            null, 'A4'
        );
        $s->fromArray([
            ['1', 'İş öncesi planlama', 'Yetersiz planlama', 'Ciddi kaza', 'Yüksek', 'TBT yapılır, PTW alınır', 'Düşük', 'Süpervizör'],
            ['2', 'Saha hazırlığı', 'Düzensiz zemin', 'Kayma-düşme', 'Orta', 'Zemin düzeltilir', 'Düşük', 'İşçi'],
            ['3', 'Duvar örülmesi', 'Düşen bloklar', 'Ezilme', 'Yüksek', 'Baret takılır, alan bariyerlenir', 'Orta', 'Duvar Ustası'],
        ], null, 'A5');

        $s->setCellValue('A9', 'Notlar ve Ek Gereksinimler:');
        $s->mergeCells('A9:H9');
        $s->setCellValue('A10', '1. Bu JSA yalnızca onaylı Yöntem Bildirimi ile geçerlidir.');
        $s->mergeCells('A10:H10');
        $s->setCellValue('A11', '2. Tüm personel bilgilendirilmelidir.');
        $s->mergeCells('A11:H11');

        $s->setCellValue('A13', 'Onay ve İmza (Acknowledgement / Sign-off)');
        $s->mergeCells('A13:H13');
        $s->fromArray(['Görevi / Rolü', 'Adı Soyadı', 'İmza', 'Tarih'], null, 'A14');
        $s->setCellValue('A15', 'Hazırlayan (İSG / HSE)');
        $s->setCellValue('A16', 'Onaylayan (Proje Müdürü)');

        $yol = tempnam(sys_get_temp_dir(), 'jsa').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        return $yol;
    }

    public function test_okuyucu_kanonik_bicimi_tam_cozer(): void
    {
        $sonuc = JsaExcelOkuyucu::oku($this->jsaXlsx());

        $this->assertTrue($sonuc['ok']);
        $this->assertStringContainsString('DUVAR ÖRME', $sonuc['baslik']);
        $this->assertSame('JSA-BLOCK-001', $sonuc['dokuman_ref']);
        $this->assertSame('00', $sonuc['revizyon']);
        $this->assertSame('31 Temmuz 2026', $sonuc['belge_tarihi']);
        $this->assertCount(3, $sonuc['adimlar']);
        $this->assertSame('Duvar örülmesi', $sonuc['adimlar'][2]['is_adimi']);
        $this->assertSame('Yüksek', $sonuc['adimlar'][2]['baslangic_risk']);
        $this->assertCount(2, $sonuc['notlar']);
        $this->assertCount(2, $sonuc['imza_rolleri']);
    }

    public function test_bicimsiz_dosya_hata_doner(): void
    {
        $kitap = new Spreadsheet;
        $kitap->getActiveSheet()->fromArray([['Ad', 'Soyad'], ['Ali', 'Veli']], null, 'A1');
        $yol = tempnam(sys_get_temp_dir(), 'jsa').'.xlsx';
        (new Xlsx($kitap))->save($yol);

        $sonuc = JsaExcelOkuyucu::oku($yol);

        $this->assertFalse($sonuc['ok']);
        $this->assertNotNull($sonuc['hata']);
    }

    public function test_excel_yukleme_kutuphaneye_kayit_ekler(): void
    {
        Livewire::test(JsaSayfasi::class)
            ->callAction('excelYukle', data: [
                'dosya' => UploadedFile::fake()->createWithContent('duvar-orme.xlsx', file_get_contents($this->jsaXlsx())),
            ]);

        $this->assertDatabaseCount('jsa_sablonlari', 1);
        $sablon = JsaSablonu::first();
        $this->assertSame($this->uzman->id, $sablon->user_id);
        $this->assertCount(3, $sablon->adimlar);
        $this->assertStringContainsString('DUVAR ÖRME', $sablon->baslik);
    }

    public function test_pdf_ve_word_uretilir(): void
    {
        $sablon = JsaSablonu::create([
            'user_id' => $this->uzman->id,
            'baslik' => 'JSA - Test İşi',
            'dokuman_ref' => 'JSA-T-1',
            'revizyon' => '00',
            'belge_tarihi' => '1 Ocak 2026',
            'adimlar' => [
                ['sira' => '1', 'is_adimi' => 'Adım', 'tehlikeler' => 'Tehlike', 'sonuclar' => 'Sonuç', 'baslangic_risk' => 'Yüksek', 'kontrol_tedbirleri' => 'Tedbir', 'kalinti_risk' => 'Düşük', 'sorumlu' => 'Uzman'],
            ],
            'notlar' => ['1. Not'],
            'imza_rolleri' => JsaSablonu::VARSAYILAN_IMZA_ROLLERI,
        ]);

        $pdf = JsaUretici::pdf($sablon);
        $this->assertInstanceOf(StreamedResponse::class, $pdf);
        ob_start();
        $pdf->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $word = JsaWordUretici::word($sablon);
        ob_start();
        $word->sendContent();
        $this->assertStringStartsWith('PK', ob_get_clean());
    }

    public function test_secili_birden_fazla_jsa_tek_belgede_birlesir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek İnşaat A.Ş.']);
        $kazi = JsaSablonu::create([
            'user_id' => $this->uzman->id, 'baslik' => 'JSA - Kazı İşleri', 'adimlar' => [
                ['sira' => '1', 'is_adimi' => 'Kazı', 'tehlikeler' => 'Göçük', 'sonuclar' => '', 'baslangic_risk' => 'Yüksek', 'kontrol_tedbirleri' => '', 'kalinti_risk' => 'Orta', 'sorumlu' => ''],
            ],
        ]);
        $elektrik = JsaSablonu::create([
            'user_id' => $this->uzman->id, 'baslik' => 'JSA - Elektrik Tesisatı', 'adimlar' => [
                ['sira' => '1', 'is_adimi' => 'Pano montajı', 'tehlikeler' => 'Elektrik çarpması', 'sonuclar' => '', 'baslangic_risk' => 'Yüksek', 'kontrol_tedbirleri' => '', 'kalinti_risk' => 'Düşük', 'sorumlu' => ''],
            ],
        ]);

        $pdf = JsaUretici::topluPdf(collect([$kazi, $elektrik]), $firma);
        $this->assertInstanceOf(StreamedResponse::class, $pdf);
        ob_start();
        $pdf->sendContent();
        $this->assertStringStartsWith('%PDF', ob_get_clean());

        $word = JsaWordUretici::topluWord(collect([$kazi, $elektrik]), $firma);
        ob_start();
        $word->sendContent();
        $this->assertStringStartsWith('PK', ob_get_clean());

        // Birleşik HTML iki analizi de içerir.
        $html = view('pdf.jsa-toplu', ['sablonlar' => collect([$kazi, $elektrik]), 'firma' => $firma])->render();
        $this->assertStringContainsString('JSA - Kazı İşleri', $html);
        $this->assertStringContainsString('JSA - Elektrik Tesisatı', $html);
        $this->assertStringContainsString('Örnek İnşaat A.Ş.', $html);
        // Her analiz kendi sayfasında (page-break kuralı toplu stilinde).
        $this->assertSame(2, substr_count($html, 'class="sayfa"'));
    }

    public function test_toplu_cikti_sayfadan_secilip_uretilir(): void
    {
        $a = JsaSablonu::create(['user_id' => $this->uzman->id, 'baslik' => 'JSA A', 'adimlar' => []]);
        $b = JsaSablonu::create(['user_id' => $this->uzman->id, 'baslik' => 'JSA B', 'adimlar' => []]);

        $component = Livewire::test(JsaSayfasi::class)->call('tumunuSec');
        $this->assertEqualsCanonicalizing(
            [(string) $a->id, (string) $b->id],
            $component->get('secili'),
        );

        // Seçim yokken uyarır, çıktı üretmez.
        Livewire::test(JsaSayfasi::class)->set('secili', [])->call('topluPdf')
            ->assertNotified('Önce en az bir JSA işaretleyin');
    }

    public function test_baska_uzmanin_jsasi_toplu_secime_alinmaz(): void
    {
        $baskasi = User::factory()->create();
        $yabanci = JsaSablonu::create(['user_id' => $baskasi->id, 'baslik' => 'Yabancı JSA', 'adimlar' => []]);

        Livewire::test(JsaSayfasi::class)
            ->set('secili', [(string) $yabanci->id])
            ->call('topluPdf')
            ->assertNotified('Önce en az bir JSA işaretleyin');
    }

    public function test_firma_secilince_cikti_kunyesine_firma_gelir(): void
    {
        $firma = Firma::factory()->for($this->uzman)->create(['unvan' => 'Örnek İnşaat A.Ş.']);
        $sablon = JsaSablonu::create([
            'user_id' => $this->uzman->id, 'baslik' => 'JSA - Duvar', 'adimlar' => [
                ['sira' => '1', 'is_adimi' => 'x', 'tehlikeler' => '', 'sonuclar' => '', 'baslangic_risk' => 'Orta', 'kontrol_tedbirleri' => '', 'kalinti_risk' => 'Düşük', 'sorumlu' => ''],
            ],
        ]);

        $html = view('pdf.jsa', ['sablon' => $sablon, 'firma' => $firma])->render();

        $this->assertStringContainsString('Örnek İnşaat A.Ş.', $html);
    }

    public function test_hazirlayan_rolu_tespiti(): void
    {
        $this->assertTrue(JsaSablonu::hazirlayanRoluMu('Hazırlayan (İSG / HSE)'));
        $this->assertTrue(JsaSablonu::hazirlayanRoluMu('HAZIRLAYAN'));
        $this->assertFalse(JsaSablonu::hazirlayanRoluMu('Onaylayan (Proje Müdürü)'));
        $this->assertFalse(JsaSablonu::hazirlayanRoluMu(null));
    }

    public function test_firma_secilince_hazirlayan_satiri_uzman_ve_kasesiyle_dolar(): void
    {
        $igu = IsgProfesyoneli::factory()->for($this->uzman)->create([
            'tip' => 'igu',
            'ad_soyad' => 'Uzman Ayşe Yılmaz',
            'unvan' => 'A Sınıfı İş Güvenliği Uzmanı',
            'kase_gorseli' => 'isg-profesyonel-kase/ayse.png',
        ]);
        $firma = Firma::factory()->for($this->uzman)->create([
            'unvan' => 'Örnek İnşaat A.Ş.',
            'igu_id' => $igu->id,
        ]);
        $sablon = JsaSablonu::create([
            'user_id' => $this->uzman->id, 'baslik' => 'JSA - Duvar', 'adimlar' => [
                ['sira' => '1', 'is_adimi' => 'x', 'tehlikeler' => '', 'sonuclar' => '', 'baslangic_risk' => 'Orta', 'kontrol_tedbirleri' => '', 'kalinti_risk' => 'Düşük', 'sorumlu' => ''],
            ],
            'imza_rolleri' => JsaSablonu::VARSAYILAN_IMZA_ROLLERI,
        ]);

        $html = view('pdf.jsa', ['sablon' => $sablon, 'firma' => $firma, 'uzman' => $firma->igu])->render();

        $this->assertStringContainsString('Uzman Ayşe Yılmaz', $html);
        $this->assertStringContainsString('A Sınıfı İş Güvenliği Uzmanı', $html);
        $this->assertStringContainsString('isg-profesyonel-kase/ayse.png', $html);

        // Word çıktısı da kaşe olmadan (dosya yok) hatasız üretilmeli.
        ob_start();
        JsaWordUretici::word($sablon, $firma)->sendContent();
        $this->assertStringStartsWith('PK', ob_get_clean());
    }

    public function test_firmasiz_ciktida_hazirlayan_satiri_bos_kalir(): void
    {
        $sablon = JsaSablonu::create([
            'user_id' => $this->uzman->id, 'baslik' => 'JSA - Genel', 'adimlar' => [],
            'imza_rolleri' => JsaSablonu::VARSAYILAN_IMZA_ROLLERI,
        ]);

        $html = view('pdf.jsa', ['sablon' => $sablon, 'firma' => null])->render();

        $this->assertStringContainsString('Hazırlayan (İSG / HSE)', $html);
    }

    public function test_baska_uzmanin_jsasi_gorunmez_ve_silinemez(): void
    {
        $baskasi = User::factory()->create();
        $yabanci = JsaSablonu::create([
            'user_id' => $baskasi->id, 'baslik' => 'Gizli JSA', 'adimlar' => [],
        ]);

        $component = Livewire::test(JsaSayfasi::class);
        $this->assertCount(0, $component->instance()->sablonlar());

        $component->call('sil', $yabanci->id);
        $this->assertDatabaseHas('jsa_sablonlari', ['id' => $yabanci->id]);
    }
}
