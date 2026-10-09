<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\Talimat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Firmaya verilen çalışma talimatlarının listesi = Talimat Teslim Tutanağı
 * (PDF / Excel). Metin ve düzen kullanıcının "Talimat İçerikleri.xlsx"
 * tutanağından BİREBİR (10.10.2026): TALİMATLAR listesi, teslim ve işveren
 * yükümlülükleri metni, solda Teslim Eden (İGU), sağda Teslim Alan (işveren).
 */
class TalimatListesiUretici
{
    public const GIRIS = 'Tarafımızca hazırlanmış olan Güvenli Çalışma Talimatları, işyerinizde yürütülen faaliyetlerde çalışanların iş sağlığı ve güvenliği kurallarına uygun hareket etmelerini sağlamak amacıyla hazırlanmış olup, işbu tutanak ile tarafınıza teslim edilmiştir.';

    public const ISVEREN_OLARAK = 'İşveren olarak;';

    /** @var array<int, string> */
    public const YUKUMLULUKLER = [
        'Teslim edilen güvenli çalışma talimatlarının tüm çalışanlara okutulması ve anlaşılır şekilde tebliğ edilmesi,',
        'Tebliğ edilen talimatlara ilişkin çalışanlardan imza alınarak kayıt altına alınması,',
        'Talimatların işyerinde uygun ve görünür alanlarda bulundurulması,',
        'Yeni işe başlayan çalışanlara da aynı şekilde oryantasyon sürecinde tebliğ edilmesi,',
        'Uygulamaların düzenli olarak takip edilmesi ve denetlenmesi hususlarının yerine getirilmesi gerektiği tarafınıza bildirilmiştir.',
    ];

    public const KAPANIS = 'İşbu tutanak iki nüsha olarak düzenlenmiş olup, taraflarca imza altına alınmıştır.';

    /** @return Collection<int, Talimat> firmaya veriliş sırasıyla */
    public static function talimatlar(Firma $firma): Collection
    {
        return $firma->talimatlar()->orderBy('created_at')->orderBy('id')->get();
    }

    /** "12 madde" / "16 bölüm" / "Yüklenen dosya (Word)" */
    public static function icerikEtiketi(Talimat $t): string
    {
        if ($t->dosyaVarMi()) {
            $uzanti = strtolower(pathinfo((string) $t->dosya_adi, PATHINFO_EXTENSION));

            return 'Yüklenen dosya ('.($uzanti === 'pdf' ? 'PDF' : 'Word').')';
        }

        return $t->bolumluMu() ? count($t->doluBolumler()).' bölüm' : count($t->maddeler ?? []).' madde';
    }

    public static function pdf(Firma $firma, ?string $tarih = null): StreamedResponse
    {
        $firma->loadMissing('igu', 'user');

        $pdf = Pdf::loadView('pdf.talimat-listesi', [
            'firma' => $firma,
            'talimatlar' => self::talimatlar($firma),
            'tarih' => self::tarih($tarih),
            'logo' => TalimatUretici::firmaLogosu($firma),
            'teslimEden' => TalimatUretici::tebligEden($firma),
        ])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), self::dosyaAdi($firma, 'pdf'));
    }

    public static function excel(Firma $firma, ?string $tarih = null): StreamedResponse
    {
        $firma->loadMissing('igu', 'user');
        $teslimEden = TalimatUretici::tebligEden($firma);

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Teslim Tutanağı');
        $s->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        // Üst bilgi: işyeri + tarih (kullanıcının tutanağında el ile yazılıyordu)
        $s->setCellValue('A1', 'TALİMAT TESLİM TUTANAĞI');
        $s->mergeCells('A1:G1');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $s->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->setCellValue('A2', 'İşyeri: '.$firma->unvan);
        $s->mergeCells('A2:E2');
        $s->setCellValue('F2', 'Tarih: '.self::tarih($tarih));
        $s->mergeCells('F2:G2');

        // TALİMATLAR listesi (A sıra no, B:G birleşik ad) — kullanıcının tutanağıyla aynı
        $s->setCellValue('A4', 'TALİMATLAR');
        $s->mergeCells('A4:G4');
        $s->getStyle('A4')->getFont()->setBold(true);
        $s->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');

        $satir = 5;
        foreach (self::talimatlar($firma) as $i => $t) {
            $s->setCellValue("A{$satir}", $i + 1);
            $s->setCellValue("B{$satir}", $t->baslik);
            $s->mergeCells("B{$satir}:G{$satir}");
            $satir++;
        }
        $son = $satir - 1;
        $s->getStyle("A4:G{$son}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $s->getStyle("A5:A{$son}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Teslim ve işveren yükümlülükleri metni (tek birleşik hücre)
        $metinBas = $son + 2;
        $metinSon = $metinBas + 15;
        $s->setCellValue("A{$metinBas}", implode("\n", [
            self::GIRIS, '', self::ISVEREN_OLARAK, '',
            ...array_map(fn ($y) => '• '.$y, self::YUKUMLULUKLER),
            '', self::KAPANIS,
        ]));
        $s->mergeCells("A{$metinBas}:G{$metinSon}");
        $s->getStyle("A{$metinBas}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP)->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);

        // İmzalar: solda Teslim Eden, sağda Teslim Alan
        $imza = $metinSon + 2;
        $s->setCellValue("A{$imza}", "Teslim Eden\nAdı Soyadı: ".$teslimEden->ad."\nUnvanı: ".$teslimEden->unvan."\nİmza:");
        $s->mergeCells("A{$imza}:C".($imza + 4));
        $s->setCellValue("E{$imza}", "Teslim Alan\nAdı Soyadı: ".($firma->isveren_ad ?: '………………………………………')."\nUnvanı: İşveren / Yetkili\nİmza:");
        $s->mergeCells("E{$imza}:G".($imza + 4));
        $s->getStyle("A{$imza}:G{$imza}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        foreach (['A' => 6, 'B' => 14, 'C' => 14, 'D' => 10, 'E' => 14, 'F' => 14, 'G' => 22] as $sutun => $genislik) {
            $s->getColumnDimension($sutun)->setWidth($genislik);
        }
        $s->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $s->getPageSetup()->setPrintArea('A1:G'.($imza + 4));

        $yazici = new Xlsx($kitap);

        return response()->streamDownload(fn () => $yazici->save('php://output'), self::dosyaAdi($firma, 'xlsx'));
    }

    private static function tarih(?string $tarih): string
    {
        return ($tarih ? Carbon::parse($tarih) : now())->format('d.m.Y');
    }

    private static function dosyaAdi(Firma $firma, string $uzanti): string
    {
        return 'talimat-teslim-tutanagi-'.Str::slug($firma->unvan).'.'.$uzanti;
    }
}
