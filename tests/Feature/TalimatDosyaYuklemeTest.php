<?php

namespace Tests\Feature;

use App\Filament\Pages\TalimatOlustur as TalimatSayfasi;
use App\Models\Firma;
use App\Models\Talimat;
use App\Models\TalimatSablonu;
use App\Models\User;
use App\Support\TalimatDosyaOkuyucu;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\ListItem;
use RuntimeException;
use Tests\TestCase;

/**
 * Kullanıcının kendi hazırladığı talimatları (Word/PDF/Excel) yükleyip arşive
 * alması (10.10.2026). Okuyucu kullanıcının 12 inşaat talimatının 12 Word +
 * 9 PDF halinde config/talimat_insaat.php ile birebir aynı sonucu verdi;
 * burada aynı düzenler küçük örnek dosyalarla sınanır.
 */
class TalimatDosyaYuklemeTest extends TestCase
{
    use RefreshDatabase;

    private string $klasor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->klasor = storage_path('framework/testing/talimat-okuyucu');
        @mkdir($this->klasor, 0777, true);
    }

    /** Kalıp talimatı düzeni: sayfa başlığında künye tablosu, kalın "N. BAŞLIK", madde işaretli satırlar, TAAHHÜT. */
    private function bolumluWord(): string
    {
        $w = new PhpWord;
        $s = $w->addSection();
        $tablo = $s->addHeader()->addTable();
        $tablo->addRow();
        $tablo->addCell(3000)->addText('LOGO');
        $tablo->addCell(3000)->addText('KALIP İŞLERİ GÜVENLİ ÇALIŞMA TALİMATI');
        $kunye = $tablo->addCell(3000);
        $kunye->addText('Doküman No: İn_klp_tlmt');
        $kunye->addText('Yayınlanma Tarihi: 18.03.2026');

        $s->addText('KALIP İŞLERİ GÜVENLİ ÇALIŞMA TALİMATI', ['bold' => true]);
        $bolumler = [
            '1. AMAÇ' => ['Bu talimatın amacı; kalıp işlerinde güvenliği sağlamaktır.', []],
            '2. KAPSAM' => ['Bu talimat; kalıp işlerinde çalışanları kapsar.', []],
            '3. KİŞİSEL KORUYUCU DONANIMLAR (KKD)' => ['Çalışanlar aşağıdakileri kullanmak zorundadır:', ['Baret', 'İş eldiveni']],
            '4. KALIP SÖKÜM KURALLARI' => [null, ['Aynı anda en fazla iki aks sökülmelidir.', 'Sökülen malzemeler aşağıya atılmamalıdır.']],
            '5. TAAHHÜT' => ['Bu talimatı okudum, anladım ve kabul ederim.', []],
        ];
        foreach ($bolumler as $baslik => [$aciklama, $maddeler]) {
            $s->addText($baslik, ['bold' => true]);
            if ($aciklama) {
                $s->addText($aciklama);
            }
            foreach ($maddeler as $m) {
                $s->addListItem($m, 0, null, ['listType' => ListItem::TYPE_BULLET_FILLED]);
            }
        }
        $s->addText('Ad Soyad : / Tarih : / İmza :', ['bold' => true]);

        $yol = $this->klasor.'/kalip.docx';
        WordIO::createWriter($w, 'Word2007')->save($yol);

        return $yol;
    }

    /** Boya talimatı düzeni: numaralı paragraflar (biri yapışık), kapanış paragrafı, TEBLİĞ EDEN. */
    private function duzPdf(): string
    {
        $html = '<html><body style="font-family:DejaVu Sans">'
            .'<p>Doküman No: İn_boya_tlmt</p>'
            .'<p>1. Boya ve solventlerin parlayıcı olduğu unutulmamalıdır.</p>'
            .'<p>2. Kapalı alanlar havalandırılmalıdır. 3. Boya yapılan yerlerde sigara içilmemelidir.</p>'
            .'<p>4. Seyyar merdivenle çalışırken elektrik tellerine 3 m. fazla yaklaşılmamalıdır.</p>'
            .'<p>Her işçi kendi emniyetini almakla yükümlüdür. Okudum, imza ediyorum.</p>'
            .'<p>TEBLİĞ EDEN TEBELLÜĞ EDEN</p></body></html>';

        $yol = $this->klasor.'/BOYA İŞLERİNDE GÜVENLİ ÇALIŞMA TALİMATI.pdf';
        file_put_contents($yol, Pdf::loadHTML($html)->output());

        return $yol;
    }

    /** Excel'de talimat: başlık satırı, künye, A sütununda sıra no + B'de madde. */
    private function duzExcel(): string
    {
        $k = new Spreadsheet;
        $s = $k->getActiveSheet();
        $s->fromArray([
            ['FORKLİFT KULLANMA TALİMATI', null, 'Doküman No: FRK-01'],
            [1, 'Forklifti yalnız belgeli operatör kullanır.'],
            [2, "Yük kaldırılmışken\naltında durulmaz."],
            [3, 'Park edilince çatallar yere indirilir.'],
        ]);
        $yol = $this->klasor.'/forklift.xlsx';
        (new Xlsx($k))->save($yol);

        return $yol;
    }

    public function test_bolumlu_word_talimati_okunur(): void
    {
        $t = TalimatDosyaOkuyucu::oku($this->bolumluWord(), 'kalip.docx');

        $this->assertSame('KALIP İŞLERİ GÜVENLİ ÇALIŞMA TALİMATI', $t['baslik']);
        $this->assertSame('İn_klp_tlmt', $t['dokuman_no']);
        $this->assertSame(['AMAÇ', 'KAPSAM', 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)', 'KALIP SÖKÜM KURALLARI'], array_column($t['bolumler'], 'baslik'));
        $this->assertSame('Bu talimatın amacı; kalıp işlerinde güvenliği sağlamaktır.', $t['bolumler'][0]['aciklama']);
        $this->assertSame(['Aynı anda en fazla iki aks sökülmelidir.', 'Sökülen malzemeler aşağıya atılmamalıdır.'], $t['bolumler'][3]['maddeler']);
        $this->assertSame(['Baret', 'İş eldiveni'], $t['kkdler']);
        $this->assertSame('Bu talimatı okudum, anladım ve kabul ederim.', $t['taahhut']);   // imza etiketleri karışmaz
    }

    public function test_duz_pdf_talimati_okunur(): void
    {
        $t = TalimatDosyaOkuyucu::oku($this->duzPdf(), 'BOYA İŞLERİNDE GÜVENLİ ÇALIŞMA TALİMATI.pdf');

        $this->assertSame('İn_boya_tlmt', $t['dokuman_no']);
        $this->assertSame('BOYA İŞLERİNDE GÜVENLİ ÇALIŞMA TALİMATI', $t['baslik']);   // başlık yoksa dosya adı
        $this->assertNull($t['bolumler']);
        $this->assertSame([
            'Boya ve solventlerin parlayıcı olduğu unutulmamalıdır.',
            'Kapalı alanlar havalandırılmalıdır.',
            'Boya yapılan yerlerde sigara içilmemelidir.',
            'Seyyar merdivenle çalışırken elektrik tellerine 3 m. fazla yaklaşılmamalıdır.',
        ], $t['maddeler']);
        $this->assertStringStartsWith('Her işçi kendi emniyetini', $t['taahhut']);
    }

    public function test_excel_talimati_okunur(): void
    {
        $t = TalimatDosyaOkuyucu::oku($this->duzExcel(), 'forklift.xlsx');

        $this->assertSame('FORKLİFT KULLANMA TALİMATI', $t['baslik']);
        $this->assertSame('FRK-01', $t['dokuman_no']);
        $this->assertSame(['Forklifti yalnız belgeli operatör kullanır.', "Yük kaldırılmışken\naltında durulmaz.", 'Park edilince çatallar yere indirilir.'], $t['maddeler']);
        $this->assertNull($t['taahhut']);
    }

    public function test_desteklenmeyen_ve_bos_dosya_hata_verir(): void
    {
        file_put_contents($yol = $this->klasor.'/bos.xlsx', '');
        (new Xlsx(new Spreadsheet))->save($yol);

        $this->expectException(RuntimeException::class);
        TalimatDosyaOkuyucu::oku($yol, 'bos.xlsx');
    }

    public function test_dosyalar_arsive_ve_firmaya_yuklenir(): void
    {
        $uzman = User::factory()->create();
        $this->actingAs($uzman);
        $firma = Firma::factory()->for($uzman)->create();

        $sayfa = Livewire::test(TalimatSayfasi::class)
            ->set('firmaId', $firma->id)
            ->callAction('talimatDosyasiYukle', data: [
                'dosyalar' => [
                    UploadedFile::fake()->createWithContent('kalip.docx', file_get_contents($this->bolumluWord())),
                    UploadedFile::fake()->createWithContent('forklift.xlsx', file_get_contents($this->duzExcel())),
                ],
                'kategori' => 'insaat_saha',
                'firmayaEkle' => true,
            ])
            ->assertHasNoActionErrors();

        $arsiv = TalimatSablonu::where('user_id', $uzman->id)->get()->keyBy('baslik');
        $this->assertCount(2, $arsiv);
        $kalip = $arsiv['KALIP İŞLERİ GÜVENLİ ÇALIŞMA TALİMATI'];
        $this->assertSame('İn_klp_tlmt', $kalip->dokuman_no);
        $this->assertCount(4, $kalip->bolumler);
        $this->assertSame('insaat_saha', $kalip->kategori);
        $this->assertCount(2, Talimat::where('firma_id', $firma->id)->get());

        // Arşivden seçilince bölümler/künye/maddeler forma gelir
        $sayfa->call('sablonSec', 'ozel', $kalip->id)
            ->assertSet('bolumlu', true)
            ->assertSet('dokumanNo', 'İn_klp_tlmt')
            ->assertSet('taahhut', 'Bu talimatı okudum, anladım ve kabul ederim.');
        $sayfa->call('sablonSec', 'ozel', $arsiv['FORKLİFT KULLANMA TALİMATI']->id)
            ->assertSet('bolumlu', false)
            ->assertCount('maddeler', 3);

        // Aynı dosya tekrar yüklenince kopya açılmaz, güncellenir; firmaya ikinci kez eklenmez
        $sayfa->callAction('talimatDosyasiYukle', data: [
            'dosyalar' => [UploadedFile::fake()->createWithContent('kalip.docx', file_get_contents($this->bolumluWord()))],
            'firmayaEkle' => true,
        ]);
        $this->assertSame(2, TalimatSablonu::where('user_id', $uzman->id)->count());
        $this->assertCount(2, Talimat::where('firma_id', $firma->id)->get());
    }
}
