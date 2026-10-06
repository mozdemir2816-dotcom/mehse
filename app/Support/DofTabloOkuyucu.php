<?php

namespace App\Support;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelTarih;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use ZipArchive;

/**
 * Kullanıcının bilgisayarda hazırladığı DÖF tablosunu (Word .docx ya da Excel
 * .xlsx/.xls) DÖF Oluştur sayfasının maddelerine çevirir; böylece çıktı
 * sistemin kendi DÖF PDF düzeninde alınır.
 *
 * Başlık satırı sütun adlarından bulunur (Tespit/Uygunsuzluk, Öncelik, Öneri/
 * Düzeltici Faaliyet, Sorumlu, Termin, Durum, Foto) — sütun sırası serbesttir.
 * Üst bilgi tablosundaki "Etiket | Değer" çiftleri (Alan / Bölge, Gözetim
 * Yapan, Sertifika No...) sayfanın rapor alanlarına aktarılır. Hücreye gömülü
 * fotoğraf varsa ilgili maddenin fotoğrafı olur.
 */
class DofTabloOkuyucu
{
    /** Sütun anahtarı => başlıkta aranan kelimeler (sıra önemli: ilk eşleşen kazanır). */
    private const SUTUNLAR = [
        'tespit' => ['tespit', 'uygunsuzluk', 'bulgu'],
        'oneri' => ['öneri', 'düzeltici', 'faaliyet', 'önlem'],
        'oncelik' => ['öncelik', 'risk'],
        'sorumlu' => ['sorumlu'],
        'termin' => ['termin', 'tarih'],
        'durum' => ['durum'],
        'foto' => ['foto', 'görsel', 'resim'],
    ];

    /** Üst bilgi etiketi => DofOlustur özelliği. */
    private const BILGILER = [
        'alan' => 'alanBolge',
        'bölge' => 'alanBolge',
        'gözetim tarih' => 'gozetimTarihAraligi',
        'rapor tarih' => 'raporTarihi',
        'gözetim yapan' => 'gozetimYapan',
        'sertifika' => 'gozetimYapanSertifikaNo',
        'sorumlu kişi' => 'sorumluKisi',
        'işveren vekili' => 'isverenVekiliAdi',
    ];

    /**
     * @return array{bilgi: array<string, string>, maddeler: array<int, array<string, mixed>>}
     */
    public static function oku(string $yol, ?string $uzanti = null): array
    {
        $uzanti = strtolower($uzanti ?: pathinfo($yol, PATHINFO_EXTENSION));
        $tablolar = $uzanti === 'docx' ? static::wordTablolari($yol) : static::excelTablolari($yol);

        return static::cozumle($tablolar);
    }

    /**
     * @param  array<int, array<int, array<int, array{metin: string, resim: ?string}>>>  $tablolar
     * @return array{bilgi: array<string, string>, maddeler: array<int, array<string, mixed>>}
     */
    public static function cozumle(array $tablolar): array
    {
        $bilgi = [];
        $maddeler = [];

        foreach ($tablolar as $satirlar) {
            $harita = null;

            foreach ($satirlar as $satir) {
                if ($harita === null) {
                    if ($bulunan = static::baslikHaritasi($satir)) {
                        $harita = $bulunan;

                        continue;
                    }

                    static::bilgiTopla($satir, $bilgi);

                    continue;
                }

                $madde = static::maddeOlustur($satir, $harita);

                if ($madde) {
                    $maddeler[] = $madde;
                }
            }
        }

        return ['bilgi' => $bilgi, 'maddeler' => $maddeler];
    }

    /** @return array<string, int>|null sütun anahtarı => hücre indeksi */
    private static function baslikHaritasi(array $satir): ?array
    {
        $harita = [];

        foreach ($satir as $i => $hucre) {
            $metin = static::kucuk($hucre['metin']);

            if ($metin === '' || mb_strlen($metin) > 60) {
                continue;
            }

            foreach (self::SUTUNLAR as $anahtar => $kelimeler) {
                if (isset($harita[$anahtar])) {
                    continue;
                }

                foreach ($kelimeler as $kelime) {
                    if (str_contains($metin, $kelime)) {
                        $harita[$anahtar] = $i;

                        continue 3;
                    }
                }
            }
        }

        return isset($harita['tespit'], $harita['oneri']) ? $harita : null;
    }

    private static function bilgiTopla(array $satir, array &$bilgi): void
    {
        $hucreler = array_values($satir);

        for ($i = 0; $i < count($hucreler) - 1; $i++) {
            $etiket = static::kucuk($hucreler[$i]['metin']);
            $deger = trim($hucreler[$i + 1]['metin']);

            if ($etiket === '' || $deger === '' || mb_strlen($etiket) > 40) {
                continue;
            }

            foreach (self::BILGILER as $kelime => $alan) {
                if (str_contains($etiket, $kelime) && ! isset($bilgi[$alan])) {
                    $bilgi[$alan] = in_array($alan, ['raporTarihi'], true)
                        ? (static::tarih($deger) ?? $deger)
                        : $deger;
                    $i++;

                    break;
                }
            }
        }
    }

    private static function maddeOlustur(array $satir, array $harita): ?array
    {
        $al = fn (string $anahtar) => isset($harita[$anahtar]) ? trim($satir[$harita[$anahtar]]['metin'] ?? '') : '';

        $tespit = $al('tespit');

        if ($tespit === '') {
            return null;
        }

        $resim = null;

        foreach ($satir as $hucre) {
            if (! empty($hucre['resim'])) {
                $resim = $hucre['resim'];

                break;
            }
        }

        return [
            'tespit' => $tespit,
            'oncelik' => static::oncelik($al('oncelik')),
            'oneri' => $al('oneri') ?: null,
            'sorumlu' => $al('sorumlu') ?: null,
            'termin' => static::tarih($al('termin')),
            'durum' => static::durum($al('durum')),
            'foto_yolu' => $resim,
        ];
    }

    public static function oncelik(string $metin): string
    {
        $m = static::kucuk($metin);

        return match (true) {
            $m === '' => 'orta',
            str_contains($m, 'çok') || str_contains($m, 'kritik') || str_contains($m, 'acil') => 'kritik',
            str_contains($m, 'yüksek') => 'yuksek',
            str_contains($m, 'düşük') => 'dusuk',
            default => 'orta',
        };
    }

    public static function durum(string $metin): string
    {
        $m = static::kucuk($metin);

        return match (true) {
            str_contains($m, 'devam') => 'devam_ediyor',
            str_contains($m, 'tamam') || str_contains($m, 'kapa') => 'tamamlandi',
            str_contains($m, 'ertel') => 'ertelendi',
            default => 'acik',
        };
    }

    /** "13.10.2026", "2026-10-13" ya da Excel seri sayısı → Y-m-d. */
    public static function tarih(string $metin): ?string
    {
        $metin = trim($metin);

        if ($metin === '') {
            return null;
        }

        if (is_numeric($metin) && (float) $metin > 20000 && (float) $metin < 80000) {
            return Carbon::instance(ExcelTarih::excelToDateTimeObject((float) $metin))->toDateString();
        }

        foreach (['d.m.Y', 'd/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.y'] as $bicim) {
            try {
                $t = Carbon::createFromFormat('!'.$bicim, $metin);

                if ($t && $t->format($bicim) === $metin) {
                    return $t->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private static function kucuk(string $metin): string
    {
        return trim(mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $metin)));
    }

    /*
    |--------------------------------------------------------------------------
    | Word
    |--------------------------------------------------------------------------
    */

    private static function wordTablolari(string $yol): array
    {
        $zip = new ZipArchive;

        if ($zip->open($yol) !== true) {
            return [];
        }

        $xml = $zip->getFromName('word/document.xml');
        $iliskiler = static::wordIliskileri((string) $zip->getFromName('word/_rels/document.xml.rels'));

        if (! $xml) {
            $zip->close();

            return [];
        }

        $dom = new DOMDocument;
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xp->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xp->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $tablolar = [];

        foreach ($xp->query('//w:tbl[not(ancestor::w:tbl)]') as $tbl) {
            $satirlar = [];

            foreach ($xp->query('w:tr', $tbl) as $tr) {
                $hucreler = [];

                foreach ($xp->query('w:tc', $tr) as $tc) {
                    $paragraflar = [];

                    foreach ($xp->query('.//w:p', $tc) as $p) {
                        $metin = '';

                        foreach ($xp->query('.//w:t|.//w:tab|.//w:br', $p) as $n) {
                            /** @var DOMElement $n */
                            $metin .= match ($n->localName) {
                                't' => $n->textContent,
                                'tab' => ' ',
                                default => "\n",
                            };
                        }

                        $paragraflar[] = $metin;
                    }

                    $resim = null;
                    $blip = $xp->query('.//a:blip/@r:embed', $tc)->item(0);

                    if ($blip && isset($iliskiler[$blip->nodeValue])) {
                        $resim = static::resimKaydet($zip->getFromName('word/'.$iliskiler[$blip->nodeValue]), $iliskiler[$blip->nodeValue]);
                    }

                    $hucreler[] = [
                        'metin' => trim(implode("\n", array_filter($paragraflar, fn ($p) => trim($p) !== ''))),
                        'resim' => $resim,
                    ];
                }

                $satirlar[] = $hucreler;
            }

            $tablolar[] = $satirlar;
        }

        $zip->close();

        return $tablolar;
    }

    /** @return array<string, string> rId => media/image1.png */
    private static function wordIliskileri(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $dom = new DOMDocument;
        $dom->loadXML($xml, LIBXML_NONET);
        $sonuc = [];

        foreach ($dom->getElementsByTagName('Relationship') as $r) {
            if (str_ends_with($r->getAttribute('Type'), '/image')) {
                $sonuc[$r->getAttribute('Id')] = ltrim($r->getAttribute('Target'), '/');
            }
        }

        return $sonuc;
    }

    /*
    |--------------------------------------------------------------------------
    | Excel
    |--------------------------------------------------------------------------
    */

    private static function excelTablolari(string $yol): array
    {
        $kitap = IOFactory::load($yol);
        $tablolar = [];

        foreach ($kitap->getAllSheets() as $sayfa) {
            // Satıra gömülü fotoğraflar (satır numarası => resim yolu)
            $resimler = [];

            foreach ($sayfa->getDrawingCollection() as $cizim) {
                if ($cizim instanceof Drawing && is_file($cizim->getPath())) {
                    $satirNo = (int) preg_replace('/\D/', '', $cizim->getCoordinates());
                    $resimler[$satirNo] ??= static::resimKaydet((string) file_get_contents($cizim->getPath()), $cizim->getPath());
                } elseif ($cizim instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                    $satirNo = (int) preg_replace('/\D/', '', $cizim->getCoordinates());
                    ob_start();
                    imagepng($cizim->getImageResource());
                    $resimler[$satirNo] ??= static::resimKaydet((string) ob_get_clean(), 'resim.png');
                }
            }

            $satirlar = [];

            foreach ($sayfa->getRowIterator() as $satir) {
                $hucreler = [];
                $hucreIt = $satir->getCellIterator();
                $hucreIt->setIterateOnlyExistingCells(false);

                foreach ($hucreIt as $hucre) {
                    $deger = $hucre->getValue();

                    if (is_numeric($deger) && ExcelTarih::isDateTime($hucre)) {
                        $deger = Carbon::instance(ExcelTarih::excelToDateTimeObject((float) $deger))->format('d.m.Y');
                    } else {
                        $deger = (string) $hucre->getFormattedValue();
                    }

                    $hucreler[] = ['metin' => trim($deger), 'resim' => null];
                }

                $no = $satir->getRowIndex();

                if (isset($resimler[$no]) && $hucreler) {
                    $hucreler[count($hucreler) - 1]['resim'] = $resimler[$no];
                }

                $satirlar[] = $hucreler;
            }

            $tablolar[] = $satirlar;
        }

        return $tablolar;
    }

    private static function resimKaydet(string|false $icerik, string $adi): ?string
    {
        $uzanti = strtolower(pathinfo($adi, PATHINFO_EXTENSION));

        if (! $icerik || ! in_array($uzanti, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }

        $yol = 'dof-foto/'.Str::random(32).'.'.$uzanti;
        Storage::disk('public')->put($yol, $icerik);

        return $yol;
    }
}
