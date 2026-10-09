<?php

namespace App\Support;

use App\Models\Talimat;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;
use PhpOffice\PhpWord\Style\ListItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Çalışma Talimatı Word (.docx) çıktısı — `pdf.talimat` ile aynı düzen
 * (kullanıcının inşaat talimatları, 10.10.2026): sayfa başlığında firma logosu
 * | talimat adı | doküman no / yayın / revizyon tablosu, altta sayfa no. Madde
 * sayısı talimattan talimata değiştiğinden sabit .docx şablonu yerine içerik
 * `phpoffice/phpword` ile programatik üretilir.
 */
class TalimatWordUretici
{
    private const YAZI = ['name' => 'Arial', 'size' => 11];

    public static function word(Talimat $talimat, bool $imzali = true): StreamedResponse
    {
        $talimat->loadMissing('firma.igu', 'firma.user');

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);
        $bolum = $phpWord->addSection(['marginTop' => 1000, 'marginBottom' => 900, 'marginLeft' => 1200, 'marginRight' => 1200, 'headerHeight' => 500]);

        self::ustBilgi($bolum, $talimat);
        $bolum->addFooter()->addPreserveText('{PAGE}', ['size' => 10], ['alignment' => Jc::CENTER]);

        $paragraf = ['alignment' => Jc::BOTH, 'spaceAfter' => 120];

        if ($talimat->bolumluMu()) {
            $bolumler = $talimat->doluBolumler();
            $bolum->addText($talimat->baslik, ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

            foreach ($bolumler as $i => $b) {
                $bolum->addText(($i + 1).'. '.$b['baslik'], ['bold' => true], ['spaceBefore' => 120, 'spaceAfter' => 80]);

                if (filled($b['aciklama'] ?? null)) {
                    self::cokSatirli($bolum, $b['aciklama'], $paragraf);
                }

                foreach ($b['maddeler'] ?? [] as $m) {
                    $bolum->addListItem($m, 0, self::YAZI, ['listType' => ListItem::TYPE_BULLET_FILLED], ['spaceAfter' => 60]);
                }
            }

            // keepNext: taahhüt imza tablosundan ayrı sayfaya düşmesin
            $bolum->addText((count($bolumler) + 1).'. TAAHHÜT', ['bold' => true], ['spaceBefore' => 120, 'spaceAfter' => 80, 'keepNext' => true]);
            self::cokSatirli($bolum, $talimat->taahhutMetni(), [...$paragraf, 'indentation' => ['left' => 600], 'keepNext' => true]);
        } else {
            if ($talimat->kkdler) {
                $run = $bolum->addTextRun($paragraf);
                $run->addText('Kullanılacak kişisel koruyucu donanımlar: ', ['bold' => true]);
                $run->addText(implode(', ', $talimat->kkdler));
            }

            foreach (($talimat->maddeler ?: ['Madde eklenmedi.']) as $i => $madde) {
                self::cokSatirli($bolum, ($i + 1).'. '.$madde, $paragraf);
            }

            $bolum->addTextBreak();
            self::cokSatirli($bolum, $talimat->taahhutMetni(), [...$paragraf, 'keepNext' => true]);
        }

        $bolum->addTextBreak(1, null, ['keepNext' => true]);
        self::imzaTablosu($bolum, $talimat, $imzali);

        $geciciYol = tempnam(sys_get_temp_dir(), 'mehse-talimat').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($geciciYol);

        return response()->streamDownload(function () use ($geciciYol) {
            echo file_get_contents($geciciYol);
            @unlink($geciciYol);
        }, TalimatUretici::dosyaAdi($talimat, 'docx'));
    }

    /** Her sayfada tekrar eden üç hücreli künye tablosu. */
    private static function ustBilgi($bolum, Talimat $talimat): void
    {
        $ust = $bolum->addHeader();
        $tablo = $ust->addTable(['borderSize' => 6, 'borderColor' => '222222', 'cellMargin' => 80, 'width' => 100 * 50, 'unit' => 'pct']);
        $tablo->addRow(1250);
        $hucre = ['valign' => VerticalJc::CENTER];

        $logoHucresi = $tablo->addCell(3200, $hucre);
        $logo = TalimatUretici::firmaLogosu($talimat->firma);
        $boyut = $logo ? @getimagesize($logo) : false;

        if ($boyut) {
            // 55 pt yüksekliğe sığdır, genişlik 150 pt'yi geçmesin
            $olcek = min(55 / max(1, $boyut[1]), 150 / max(1, $boyut[0]));
            $logoHucresi->addImage($logo, ['width' => round($boyut[0] * $olcek), 'height' => round($boyut[1] * $olcek), 'alignment' => Jc::CENTER]);
        } else {
            $logoHucresi->addText((string) $talimat->firma?->unvan, ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        }

        $tablo->addCell(3200, $hucre)->addText($talimat->baslik, ['size' => 12], ['alignment' => Jc::CENTER]);

        $kunye = $tablo->addCell(3200, $hucre);
        foreach ([
            'Doküman No: '.$talimat->dokumanNoGoster(),
            'Yayınlanma Tarihi: '.$talimat->yayinTarihiGoster(),
            'Revizyon No: '.$talimat->revizyon_no,
            'Revizyon Tarihi: '.$talimat->revizyon_tarihi?->format('d.m.Y'),
        ] as $satir) {
            $kunye->addText($satir, ['size' => 9.5], ['spaceAfter' => 0]);
        }

        $ust->addText('', ['size' => 6]);   // tablo ile metin arası boşluk
    }

    private static function imzaTablosu($bolum, Talimat $talimat, bool $imzali): void
    {
        $teblig = TalimatUretici::tebligEden($talimat->firma);
        $tablo = $bolum->addTable(['cellMargin' => 80]);
        $tablo->addRow(null, ['cantSplit' => true]);

        $sol = $tablo->addCell(4800);
        $sol->addText('TEBLİĞ EDEN', ['bold' => true]);
        foreach (['Ad Soyad : '.($imzali ? $teblig->ad : ''), 'Unvan : '.$teblig->unvan, 'Tarih :', 'İmza :'] as $satir) {
            $sol->addText($satir, null, ['spaceAfter' => 60]);
        }

        if ($imzali) {
            foreach ([$teblig->kase_gorseli, $teblig->imza_gorseli] as $gorsel) {
                if ($gorsel && is_file($yol = storage_path('app/public/'.$gorsel))) {
                    $sol->addImage($yol, ['height' => 45]);
                }
            }
        }

        $sag = $tablo->addCell(4800);
        $sag->addText('TEBELLÜĞ EDEN', ['bold' => true]);
        foreach (['Ad Soyad :', 'Görevi :', 'Tarih :', 'İmza :'] as $satir) {
            $sag->addText($satir, null, ['spaceAfter' => 60]);
        }
    }

    /** Satır sonlarını (a. b. c. alt maddeleri, iki paragraflı taahhüt) koruyarak yazar. */
    private static function cokSatirli(AbstractContainer $kap, string $metin, array $paragraf): void
    {
        foreach (preg_split('/\R/u', $metin) as $satir) {
            $kap->addText($satir, null, $paragraf);
        }
    }
}
