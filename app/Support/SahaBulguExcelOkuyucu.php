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
        'isaretx' => 'isaret_x',
        'isarety' => 'isaret_y',
        'isaretcap' => 'isaret_cap',
        'fotografno' => 'foto_no',
        'fotono' => 'foto_no',
        'fotografsira' => 'foto_no',
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
        $sayfa = IOFactory::load($yol)->getSheet(0);
        $tablo = $sayfa->toArray(null, true, false, false);
        // Maddenin satırına yapıştırılmış fotoğraflar (satır no => yol).
        $resimler = DofTabloOkuyucu::satirResimleri($sayfa, 'saha-analiz-foto');

        $harita = null;
        $bulgular = [];
        $atlanan = 0;

        foreach ($tablo as $sira => $satir) {
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

            $bulgu = GeminiSahaAnalizi::normalize([
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

            // Gemini'ye eklenen fotoğrafın sırası (1, 2, 3…) — yüklemede o fotoğraf maddeye bağlanır.
            $bulgu['foto_no'] = static::fotoNo($al('foto_no'));
            // Gemini'nin verdiği işaret (yüzde): yüklemede fotoğrafa kırmızı çember çizilir.
            $bulgu['isaret'] = $sayi('isaret_x') !== null && $sayi('isaret_y') !== null
                ? ['x' => $sayi('isaret_x'), 'y' => $sayi('isaret_y'), 'cap' => $sayi('isaret_cap')]
                : null;
            $bulgu['foto_yolu'] = $resimler[$sira + 1] ?? null;   // toArray A1'den başlar
            $bulgular[] = $bulgu;
        }

        if ($harita === null || ! isset($harita['tespit'])) {
            throw new \RuntimeException('"Tespit" sütunu bulunamadı — tablonun ilk satırı başlık satırı olmalı.');
        }

        return ['bulgular' => $bulgular, 'atlanan' => $atlanan];
    }

    /**
     * Yüklenen fotoğrafları Fotoğraf No'ya eşler: no => yol. Tüm dosya adlarında
     * farklı bir sayı (1–100) varsa ("1.jpg", "foto 3.jpg") ada göre; yoksa yükleme
     * sırasına göre (1. fotoğraf = 1).
     *
     * @param  array<int, string>  $yollar  yükleme sırasıyla
     * @param  array<string, string>  $adlar  yol => özgün dosya adı
     * @return array<int, string>
     */
    public static function fotoEsle(array $yollar, array $adlar = []): array
    {
        $yollar = array_values(array_filter($yollar));
        $adaGore = [];

        foreach ($yollar as $yol) {
            $no = static::fotoNo(pathinfo((string) ($adlar[$yol] ?? ''), PATHINFO_FILENAME));

            // Telefon adları (IMG_20261114_1234) numara sayılmaz: yalnız 1–100.
            if ($no === null || $no > 100 || isset($adaGore[$no])) {
                $adaGore = null;

                break;
            }

            $adaGore[$no] = $yol;
        }

        if ($adaGore) {
            return $adaGore;
        }

        return $yollar ? array_combine(range(1, count($yollar)), $yollar) : [];
    }

    /** "3", "Fotoğraf 3", "3, 5" → 3 (ilk sayı); yoksa null. */
    public static function fotoNo(string $metin): ?int
    {
        return preg_match('/\d+/', $metin, $m) && (int) $m[0] > 0 ? (int) $m[0] : null;
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
