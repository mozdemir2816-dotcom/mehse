<?php

namespace App\Support;

use App\Models\Talimat;
use DOMDocument;
use DOMXPath;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

/**
 * Kullanıcının kendi hazırladığı talimat dosyasını (Word .docx / PDF / Excel)
 * kütüphane şablonuna çevirir (10.10.2026 — "bu tarz hazırladığım talimatları
 * yüklememe izin ver"). Metin değiştirilmez; yalnız yapı çıkarılır:
 *
 *  - künye: "Doküman No:" satırı, başlık (sayfa başlığındaki "...TALİMATI")
 *  - bölümlü: en az 3 "N. BÜYÜK HARF BAŞLIK" satırı varsa (Kalıp/Demir düzeni),
 *    başlık altındaki düz metin açıklama, işaretli satırlar madde; "TAAHHÜT"
 *    bölümü taahhüt metni olur
 *  - düz: "1. … 2. …" numaralı (paragrafa yapışmış numaralar da ayrılır) ya da
 *    madde işaretli satırlar; "Her işçi kendi emniyetini…/Yapılacak işin
 *    gereğine…" ile başlayan kapanış taahhüt olur
 *  - "TEBLİĞ EDEN / TEBELLÜĞ EDEN / Ad Soyad" imza alanından sonrası atılır
 *
 * Dönen dizi config/talimat_insaat.php kayıtlarıyla aynı biçimdedir.
 */
class TalimatDosyaOkuyucu
{
    public const UZANTILAR = ['docx', 'pdf', 'xlsx', 'xls'];

    private const MADDE_ISARETI = '• ';

    /** @return array{baslik: string, kategori: null, aciklama: string, dokuman_no: ?string, kkdler: array, maddeler: array, bolumler: ?array, taahhut: ?string} */
    public static function oku(string $yol, string $dosyaAdi): array
    {
        $uzanti = strtolower(pathinfo($dosyaAdi, PATHINFO_EXTENSION));

        [$satirlar, $basliklar, $paragrafBazli, $hucreBazli] = match ($uzanti) {
            'docx' => self::wordSatirlari($yol),
            'pdf' => self::pdfSatirlari($yol),
            'xlsx', 'xls' => self::excelSatirlari($yol),
            default => throw new RuntimeException('Desteklenmeyen dosya türü: .'.$uzanti.' (Word .docx, PDF veya Excel yükleyin).'),
        };

        $yedekBaslik = trim(preg_replace('/[_\s]+/u', ' ', pathinfo($dosyaAdi, PATHINFO_FILENAME)));

        return self::yapilandir($satirlar, $basliklar, $yedekBaslik, $paragrafBazli, $hucreBazli);
    }

    /*
    |--------------------------------------------------------------------------
    | Dosya → satırlar
    |--------------------------------------------------------------------------
    | Her okuyucu [gövde satırları, sayfa başlığı hücreleri, paragraf bazlı mı]
    | döndürür. Madde işaretli satırlar "• " ile başlar.
    */

    /** @return array{0: array<int, string>, 1: array<int, string>, 2: bool} */
    private static function wordSatirlari(string $yol): array
    {
        $zip = new ZipArchive;

        if ($zip->open($yol) !== true || ($govde = $zip->getFromName('word/document.xml')) === false) {
            throw new RuntimeException('Word dosyası açılamadı (yalnız .docx desteklenir; eski .doc dosyasını Word\'de .docx olarak kaydedin).');
        }

        $basliklar = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $ad = $zip->getNameIndex($i);
            if (preg_match('#^word/header\d*\.xml$#', $ad)) {
                // Hücre bazlı: logo hücresindeki yazı başlık hücresine karışmasın
                [$x] = self::xpath($zip->getFromName($ad));
                foreach ($x->query('//w:tc | //w:p[not(ancestor::w:tc)]') as $oge) {
                    $basliklar[] = self::temizle(implode(' ', array_map(fn ($p) => self::metin($x, $p), iterator_to_array($oge->localName === 'p' ? [$oge] : $x->query('.//w:p', $oge)))));
                }
            }
        }

        [$x] = self::xpath($govde);
        $satirlar = [];
        $sayac = 0;
        foreach ($x->query('//w:body//w:p') as $p) {
            $metin = self::metin($x, $p);
            if ($metin === '') {
                continue;
            }

            if ($x->query('.//w:numPr', $p)->length > 0) {
                // Otomatik numaralı büyük harf başlık (ör. Word listesiyle "AMAÇ") → "N. AMAÇ"
                $kalin = $x->query('.//w:r[w:rPr/w:b]', $p)->length > 0;
                $metin = $kalin && self::buyukHarfMi($metin) && mb_strlen($metin) < 80 && ! preg_match('/^\d/', $metin)
                    ? (++$sayac).'. '.$metin
                    : self::MADDE_ISARETI.$metin;
            }

            $satirlar[] = $metin;
        }

        return [$satirlar, $basliklar, true, true];
    }

    /** @return array{0: array<int, string>, 1: array<int, string>, 2: bool, 3: bool} */
    private static function pdfSatirlari(string $yol): array
    {
        try {
            $sayfalar = (new PdfParser)->parseFile($yol)->getPages();
        } catch (\Throwable $e) {
            throw new RuntimeException('PDF okunamadı: '.$e->getMessage());
        }

        $sayfaSatirlari = array_map(
            fn ($s) => array_values(array_filter(array_map([self::class, 'temizle'], preg_split('/\R/u', $s->getText())), fn ($l) => $l !== '')),
            $sayfalar,
        );

        if (! array_filter($sayfaSatirlari)) {
            throw new RuntimeException('PDF\'te metin bulunamadı (taranmış/resim PDF olabilir). Word veya Excel halini yükleyin.');
        }

        // Her sayfada tekrar eden satırlar (başlık tablosu, alt bilgi) gövdeden ayrılır.
        $basliklar = [];
        if (count($sayfaSatirlari) > 1) {
            $sayim = array_count_values(array_merge(...array_map('array_unique', $sayfaSatirlari)));
            $basliklar = array_keys(array_filter($sayim, fn ($n) => $n >= count($sayfaSatirlari)));
        }

        $satirlar = [];
        foreach ($sayfaSatirlari as $sayfa) {
            foreach ($sayfa as $satir) {
                if (! in_array($satir, $basliklar, true)) {
                    $satirlar[] = preg_replace('/^[\x{2022}\x{25CF}\x{25AA}\x{25E6}\x{F0B7}\x{F0A7}\x{00B7}]\s*/u', self::MADDE_ISARETI, $satir);
                }
            }
        }

        return [$satirlar, $basliklar, false, false];
    }

    /** @return array{0: array<int, string>, 1: array<int, string>, 2: bool} */
    private static function excelSatirlari(string $yol): array
    {
        $okuyucu = IOFactory::createReaderForFile($yol);
        $okuyucu->setReadDataOnly(true);
        $sayfa = $okuyucu->load($yol)->getSheet(0);

        $satirlar = [];
        foreach ($sayfa->toArray(null, true, false, false) as $hucreler) {
            $dolu = array_values(array_filter(array_map(fn ($h) => is_string($h) || is_numeric($h) ? trim((string) $h) : '', $hucreler), fn ($h) => $h !== ''));
            if (! $dolu) {
                continue;
            }

            // "1 | Madde metni" gibi sıra no ayrı hücredeyse "1. Madde metni"
            if (count($dolu) > 1 && preg_match('/^\d{1,3}\.?$/', $dolu[0])) {
                $dolu[0] = rtrim($dolu[0], '.').'.';
            }

            foreach (preg_split('/\R/u', implode(' ', array_unique($dolu))) as $satir) {
                if (($satir = self::temizle($satir)) !== '') {
                    $satirlar[] = preg_replace('/^[\x{2022}\x{25CF}\x{25AA}\x{F0B7}\x{00B7}]\s*/u', self::MADDE_ISARETI, $satir);
                }
            }
        }

        return [$satirlar, [], true, true];
    }

    /*
    |--------------------------------------------------------------------------
    | Satırlar → talimat yapısı
    |--------------------------------------------------------------------------
    */

    private static function yapilandir(array $satirlar, array $basliklar, string $yedekBaslik, bool $paragrafBazli, bool $hucreBazli): array
    {
        $dokumanNo = null;

        foreach ([...$basliklar, ...$satirlar] as $satir) {
            if (preg_match('/Doküman\s*No\s*:\s*(\S+)/iu', $satir, $m) && ! $dokumanNo) {
                $dokumanNo = $m[1];
            }
        }

        // Künye etiketleri (aynı satır/hücrede olabilir) metinden ayrılır
        $kunyesiz = fn (string $s) => self::temizle(preg_replace('/(Doküman No|Yayınlanma Tarihi|Revizyon No|Revizyon Tarihi)\s*:.*/iu', '', $s));
        $talimatMi = fn (string $s) => (bool) preg_match('/TAL[İI]MAT|[Tt]alimat/u', $s);

        $parcalar = array_values(array_filter(array_map($kunyesiz, $basliklar), fn ($b) => $b !== '' && ! preg_match('/^\d+$/', $b) && self::buyukHarfMi($b)));
        $baslik = null;
        $i = collect($parcalar)->keys()->last(fn ($k) => $talimatMi($parcalar[$k]));

        if ($i !== null) {
            $baslik = $parcalar[$i];
            // PDF'te başlık hücresi satırlara bölünür ("KALIP İŞLERİ GÜVENLİ" / "ÇALIŞMA TALİMATI")
            for ($j = $i - 1; ! $hucreBazli && $j >= 0 && ! $talimatMi($parcalar[$j]); $j--) {
                $baslik = $parcalar[$j].' '.$baslik;
            }
        }

        // Başlık gövdenin ilk satırlarında olabilir (başlık tablosu gövdeye konmuşsa / Excel)
        $baslik ??= collect(array_slice($satirlar, 0, 6))->map($kunyesiz)
            ->first(fn ($s) => $talimatMi($s) && self::buyukHarfMi($s) && mb_strlen($s) < 120);
        $baslik = $baslik ? self::temizle($baslik) : $yedekBaslik;
        $baslikNorm = self::norm($baslik);

        // Gövdeden künye, sayfa no, başlık (ve parçaları) çıkarılır; imza alanında kesilir.
        $govde = [];
        foreach ($satirlar as $satir) {
            if (preg_match('/^(•\s*)?(TEBLİĞ EDEN|TEBELLÜĞ EDEN)/iu', $satir)) {
                break;
            }
            // İmza etiketleri (Ad Soyad : / Tarih : / İmza :) — PDF'te sırası karışabildiği için kesilmez, atlanır
            if (preg_match('/^(•\s*)?((Ad\s*Soyad[ıi]?|Tarih|İmza|Görevi|Unvan[ıi]?)\s*:\s*\/?\s*)+$/iu', $satir)) {
                continue;
            }
            $satir = $kunyesiz($satir);
            if ($satir === '' || preg_match('/^\d{1,3}$/', $satir)) {
                continue;
            }
            if (self::buyukHarfMi($satir) && ! preg_match('/^\d/', $satir) && mb_strlen(self::norm($satir)) >= 5 && str_contains($baslikNorm, self::norm($satir))) {
                continue;
            }
            $govde[] = $satir;
        }

        $bolumBasligi = '/^(\d{1,2})\s*\.\s*(\S.*)$/u';
        $basliklar = array_filter($govde, fn ($s) => preg_match($bolumBasligi, $s, $m) && self::buyukHarfMi($m[2]) && mb_strlen($m[2]) < 90);

        $sonuc = count($basliklar) >= 3
            ? self::bolumlu($govde, $bolumBasligi)
            : self::duz($govde, $paragrafBazli);

        if (! $sonuc['maddeler'] && ! $sonuc['bolumler']) {
            throw new RuntimeException('Dosyada talimat maddesi bulunamadı.');
        }

        $sonuc['aciklama'] = $sonuc['bolumler']
            ? 'Kendi talimatım — '.count($sonuc['bolumler']).' bölüm'
            : 'Kendi talimatım — '.count($sonuc['maddeler']).' madde';

        return ['baslik' => $baslik, 'kategori' => null, 'dokuman_no' => $dokumanNo, ...$sonuc];
    }

    private static function bolumlu(array $govde, string $bolumBasligi): array
    {
        $bolumler = [];
        $aktif = null;
        $maddeAcik = false;

        foreach ($govde as $satir) {
            if (preg_match($bolumBasligi, $satir, $m) && self::buyukHarfMi($m[2]) && mb_strlen($m[2]) < 90) {
                if ($aktif) {
                    $bolumler[] = $aktif;
                }
                $aktif = ['baslik' => self::temizle($m[2]), 'aciklama' => '', 'maddeler' => []];
                $maddeAcik = false;

                continue;
            }
            if (! $aktif) {
                continue;   // ilk başlıktan önceki satırlar (gövdedeki başlık tekrarı)
            }

            if (str_starts_with($satir, self::MADDE_ISARETI)) {
                $aktif['maddeler'][] = self::temizle(mb_substr($satir, 2));
                $maddeAcik = true;
            } elseif ($maddeAcik && self::devamMi($satir)) {
                $son = count($aktif['maddeler']) - 1;
                $aktif['maddeler'][$son] .= ' '.$satir;   // PDF'te satıra sarılan madde
            } else {
                $aktif['aciklama'] = self::temizle($aktif['aciklama'].' '.$satir);
                $maddeAcik = false;
            }
        }
        if ($aktif) {
            $bolumler[] = $aktif;
        }

        $taahhut = null;
        $i = collect($bolumler)->search(fn ($b) => str_contains($b['baslik'], 'TAAHH'));
        if ($i !== false) {
            $taahhut = self::temizle(implode(' ', array_filter([$bolumler[$i]['aciklama'], ...$bolumler[$i]['maddeler']])));
            array_splice($bolumler, $i, 1);
        }

        $kkd = collect($bolumler)->first(fn ($b) => str_contains($b['baslik'], 'KİŞİSEL KORUYUCU') || str_contains($b['baslik'], '(KKD)'));

        return [
            'kkdler' => $kkd['maddeler'] ?? [],
            'maddeler' => [],
            'bolumler' => $bolumler,
            'taahhut' => $taahhut === '' || self::norm((string) $taahhut) === self::norm(Talimat::TAAHHUT_BOLUMLU) ? null : $taahhut,
        ];
    }

    private static function duz(array $govde, bool $paragrafBazli): array
    {
        $maddeler = [];
        $kapanis = [];
        $beklenen = 1;
        $isaretli = (bool) array_filter($govde, fn ($s) => str_starts_with($s, self::MADDE_ISARETI));
        $numarali = (bool) array_filter($govde, fn ($s) => preg_match('/^\d{1,2}\s*\./', $s));

        foreach ($govde as $satir) {
            if ($kapanis || preg_match('/^(Her işçi kendi emniyetini|Yapılacak işin gereğine|Yukarıdaki maddeleri|Bu talimatı okudum)/iu', $satir)) {
                $kapanis[] = $satir;

                continue;
            }

            if ($isaretli && ! $numarali) {
                if (str_starts_with($satir, self::MADDE_ISARETI) || ! $maddeler) {
                    $maddeler[] = self::temizle(ltrim($satir, '• '));
                } else {
                    $maddeler[count($maddeler) - 1] .= ($paragrafBazli ? "\n" : ' ').$satir;
                }

                continue;
            }

            if (! $numarali) {
                $maddeler[] = $satir;   // numarasız, işaretsiz: her paragraf bir madde

                continue;
            }

            $satir = ltrim($satir, '• ');
            // Paragraf içine yapışmış "N. " numaralarından böl (yalnız sıradaki numara; bir atlama tolere)
            $parcalar = preg_split('/(?<![\d,.])(\d{1,2})\.(?=\s*\S|\s*$)/u', $satir, -1, PREG_SPLIT_DELIM_CAPTURE);
            $once = trim(array_shift($parcalar));

            if ($once !== '') {
                if ($maddeler) {
                    $alt = $paragrafBazli || preg_match('/^[a-zçğıöşü]\)|^[a-zçğıöşü]\.\s/u', $once);
                    $maddeler[count($maddeler) - 1] .= ($alt ? "\n" : ' ').$once;   // devam satırı / a. b. c. alt maddesi
                } else {
                    $maddeler[] = $once;
                }
            }

            for ($k = 0; $k < count($parcalar); $k += 2) {
                $no = (int) $parcalar[$k];
                $metin = trim($parcalar[$k + 1] ?? '');

                if ($no === $beklenen || $no === $beklenen + 1) {
                    if ($maddeler && preg_match('/^\p{Ll}/u', $metin)) {
                        $maddeler[count($maddeler) - 1] .= ' '.$metin;   // Word numarası cümle ortasına düşmüş ("takviye / 8. edilmeli")
                    } else {
                        $maddeler[] = $metin;
                    }
                    $beklenen = $no + 1;
                } elseif ($maddeler) {
                    $maddeler[count($maddeler) - 1] .= ' '.$parcalar[$k].'.'.($parcalar[$k + 1] ?? '');   // sıra dışı numara metnin parçası
                } else {
                    $maddeler[] = $parcalar[$k].'.'.($parcalar[$k + 1] ?? '');
                }
            }
        }

        $maddeler = array_values(array_filter(array_map('trim', $maddeler), fn ($m) => $m !== ''));
        $taahhut = $paragrafBazli ? implode("\n", $kapanis) : self::temizle(implode(' ', $kapanis));

        return [
            'kkdler' => [],
            'maddeler' => $maddeler,
            'bolumler' => null,
            'taahhut' => $taahhut === '' || self::norm($taahhut) === self::norm(Talimat::TAAHHUT_DUZ) ? null : $taahhut,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Yardımcılar
    |--------------------------------------------------------------------------
    */

    /** @return array{0: DOMXPath} */
    private static function xpath(string $xml): array
    {
        $d = new DOMDocument;
        $d->loadXML($xml, LIBXML_NONET);
        $x = new DOMXPath($d);
        $x->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        return [$x];
    }

    private static function metin(DOMXPath $x, \DOMNode $p): string
    {
        $t = '';
        foreach ($x->query('.//w:t|.//w:tab', $p) as $r) {
            $t .= $r->localName === 't' ? $r->textContent : ' ';
        }

        return self::temizle($t);
    }

    private static function temizle(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $s)));
    }

    /** Küçük harf içermiyor ve en az bir harf var (Türkçe dahil). */
    private static function buyukHarfMi(string $s): bool
    {
        return ! preg_match('/\p{Ll}/u', $s) && preg_match('/\p{Lu}/u', $s);
    }

    /** PDF'te alt satıra sarılmış metin: küçük harf/noktalama ile başlıyor. */
    private static function devamMi(string $s): bool
    {
        return $s !== '' && (preg_match('/^[\p{Ll}(,;:\-–]/u', $s) === 1);
    }

    private static function norm(string $s): string
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $s));
    }
}
