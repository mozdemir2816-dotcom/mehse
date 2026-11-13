<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Saha Gözlem Raporu — "Excel'den bulgu ekle" (13.11.2026). Hazır İstemler'in
 * saha gözlem istemiyle Gemini'de (fotoğraflar sohbete eklenerek) hazırlanan
 * tabloyu okur; her satır, fotoğraftan yapay zekâ analiziyle gelmiş gibi
 * GeminiSahaAnalizi::normalize'dan geçer (Fine-Kinney ölçeğe yuvarlanır,
 * risk derecesi skordan hesaplanır). Maddeler sayfada yine tek tek onaylanır.
 */
class SahaBulguExcelOkuyucu
{
    /** Başlıkta aranan kelimeler (normalize edilmiş) => alan; ilk eşleşen kazanır. */
    private const SUTUNLAR = [
        'fotograftakikonum' => 'fotograftaki_konum',
        'konum' => 'fotograftaki_konum',
        'bina' => 'bina_bolge',
        'bolge' => 'bina_bolge',
        'bolum' => 'bina_bolge',
        'kategori' => 'kategori',
        'tespit' => 'tespit',
        'uygunsuzluk' => 'tespit',
        'oneri' => 'oneriler',
        'onlem' => 'oneriler',
        'yasal' => 'yasal_gerekce',
        'mevzuat' => 'yasal_gerekce',
        'olasilik' => 'olasilik',
        'frekans' => 'frekans',
        'siddet' => 'siddet',
    ];

    /**
     * @return array{bulgular: array<int, array<string, mixed>>, atlanan: int}
     */
    public static function oku(string $yol): array
    {
        $tablo = IOFactory::load($yol)->getSheet(0)->toArray(null, true, false, false);

        $harita = null;
        $bulgular = [];
        $atlanan = 0;

        foreach ($tablo as $satir) {
            $metinler = array_map(fn ($h) => trim((string) $h), $satir);

            if ($harita === null) {
                $harita = static::baslikHaritasi($metinler);

                continue;
            }

            $al = fn (string $alan) => isset($harita[$alan]) ? ($metinler[$harita[$alan]] ?? '') : '';
            $tespit = $al('tespit');

            // Boş satır ya da parça parça yapıştırmada araya giren başlık satırı.
            if ($tespit === '' || static::norm($tespit) === 'tespit' || static::norm($tespit) === 'uygunsuzluk') {
                continue;
            }

            if (mb_strlen($tespit) < 5) {
                $atlanan++;

                continue;
            }

            $sayi = fn (string $alan) => ($v = str_replace(',', '.', $al($alan))) !== '' && is_numeric($v) ? (float) $v : null;

            $bulgular[] = GeminiSahaAnalizi::normalize([
                'bina_bolge' => $al('bina_bolge') ?: null,
                'fotograftaki_konum' => $al('fotograftaki_konum') ?: null,
                'kategori' => $al('kategori') ?: null,
                'tespit' => $tespit,
                'oneriler' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|\s*•\s*|\s+\|\s+/u', $al('oneriler'))))),
                'yasal_gerekce' => $al('yasal_gerekce') ?: null,
                'olasilik' => $sayi('olasilik'),
                'frekans' => $sayi('frekans'),
                'siddet' => $sayi('siddet'),
            ]);
        }

        if ($harita === null || ! isset($harita['tespit'])) {
            throw new \RuntimeException('"Tespit" sütunu bulunamadı — tablonun ilk satırı başlık satırı olmalı.');
        }

        return ['bulgular' => $bulgular, 'atlanan' => $atlanan];
    }

    /** @return array<string, int>|null alan => sütun indeksi */
    private static function baslikHaritasi(array $baslik): ?array
    {
        $harita = [];

        foreach ($baslik as $i => $h) {
            $n = static::norm($h);

            if ($n === '') {
                continue;
            }

            foreach (self::SUTUNLAR as $kelime => $alan) {
                if (str_contains($n, $kelime) && ! isset($harita[$alan])) {
                    $harita[$alan] = $i;

                    break;
                }
            }
        }

        return $harita ?: null;
    }

    private static function norm(string $metin): string
    {
        $metin = mb_strtolower(strtr($metin, ['İ' => 'i', 'I' => 'ı']));

        return preg_replace('/[^a-z0-9]/', '', strtr($metin, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u'])) ?? '';
    }
}
