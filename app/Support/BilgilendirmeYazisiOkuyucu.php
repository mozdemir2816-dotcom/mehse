<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use ZipArchive;

/**
 * Tablosuz Word yazısını (ör. "İş Güvenliği Bilgilendirmesi") Tespit Öneri Defteri'ne
 * tek madde olarak çevirir (kullanıcı 15.10.2026: "saha gözlemden çok genel
 * bilgilendirme yazıyor"):
 *  - Tespit: konu (ilk paragraf) + ilk başlığa kadarki açıklama paragrafları
 *  - Öneri: başlıklar ve altındaki maddeler ("bilgilerinize sunarım" gibi kapanışa kadar)
 *  - Dayanak: "Dayanak:" ile başlayan paragraf
 * İşyeri/Tarih/İlgili gibi künye satırları, belge türü satırı ve imza bloğu alınmaz.
 */
class BilgilendirmeYazisiOkuyucu
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @return array{tespit: string, oneri: string, dayanak: ?string, tarih: ?string}|null */
    public static function oku(string $yol): ?array
    {
        $paragraflar = static::paragraflar($yol);

        if (count($paragraflar) < 2) {
            return null;
        }

        $dayanak = null;
        $tarih = null;
        $kapandi = false;
        $govde = [];

        foreach ($paragraflar as $p) {
            if (preg_match('/^dayanak\s*:\s*(.+)$/iu', $p['metin'], $m)) {
                $dayanak = trim($m[1]);

                continue;
            }

            // Kapanış cümlesi ve sonrası (imza bloğu) alınmaz — yalnız Dayanak aranır.
            if ($kapandi || preg_match('/(sunarım|arz ederim|rica ederim)\W*$/iu', $p['metin'])) {
                $kapandi = true;

                continue;
            }

            if (! $tarih && preg_match('/tarih\s*:\s*(\d{1,2}[.\/]\d{1,2}[.\/]\d{4})/iu', static::kucuk($p['metin']), $m)) {
                $tarih = str_replace('/', '.', $m[1]);
            }

            $govde[] = $p;
        }

        $konu = array_shift($govde)['metin'] ?? '';
        $tespit = [$konu];
        $oneri = [];
        $baslikGeldi = false;

        foreach ($govde as $p) {
            $metin = $p['metin'];

            if (! $baslikGeldi && static::kunyeMi($metin)) {
                continue;
            }

            if ($p['baslik']) {
                $baslikGeldi = true;
                $oneri[] = ($oneri ? "\n" : '').$metin;

                continue;
            }

            if ($baslikGeldi) {
                $oneri[] = '• '.$metin;
            } else {
                $tespit[] = $metin;
            }
        }

        // Başlık yoksa: ilk açıklama paragrafı tespit, kalanı öneri.
        if (! $baslikGeldi && count($tespit) > 2) {
            $oneri = array_map(fn ($m) => '• '.$m, array_splice($tespit, 2));
        }

        if (! $oneri) {
            return null;
        }

        return [
            'tespit' => implode("\n", array_filter($tespit, 'filled')),
            'oneri' => implode("\n", $oneri),
            'dayanak' => $dayanak,
            'tarih' => $tarih,
        ];
    }

    /** "İşyeri: … Tarih: …" künye satırı ya da büyük harfli kısa belge türü ("İŞ GÜVENLİĞİ BİLGİLENDİRMESİ"). */
    private static function kunyeMi(string $metin): bool
    {
        return (bool) preg_match('/^(işyeri|tarih|ilgili|saat|konu|sayı|kime)\s*:/u', static::kucuk($metin))
            || (mb_strlen($metin) <= 60 && $metin === mb_strtoupper($metin, 'UTF-8') && preg_match('/\p{Lu}/u', $metin));
    }

    /** Türkçe I/İ doğru küçültülür (PCRE /i İşyeri'ni işyeri ile eşleştirmez). */
    private static function kucuk(string $metin): string
    {
        return mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $metin), 'UTF-8');
    }

    /** @return array<int, array{metin: string, baslik: bool}> */
    private static function paragraflar(string $yol): array
    {
        $zip = new ZipArchive;

        if ($zip->open($yol) !== true) {
            return [];
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xml) {
            return [];
        }

        $dom = new DOMDocument;
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);

        $sonuc = [];

        foreach ($xp->query('//w:body/w:p') as $p) {
            $metin = '';

            foreach ($xp->query('.//w:t|.//w:tab|.//w:br', $p) as $n) {
                /** @var DOMElement $n */
                $metin .= $n->localName === 't' ? $n->textContent : ' ';
            }

            $metin = trim(preg_replace('/\s+/u', ' ', $metin));

            if ($metin === '') {
                continue;
            }

            $stil = (string) $xp->query('w:pPr/w:pStyle/@w:val', $p)->item(0)?->nodeValue;

            // Word'ün "Başlık 1" stili Türkçe sürümde "Balk1", İngilizcede "Heading1" kaydedilir.
            $sonuc[] = [
                'metin' => $metin,
                'baslik' => (bool) preg_match('/^(heading|balk|başlık)\d/iu', $stil)
                    || (mb_strlen($metin) <= 100 && preg_match('/^\d+[\s.)]+\p{L}/u', $metin) && ! preg_match('/[.;:]$/u', $metin)),
            ];
        }

        return $sonuc;
    }
}
