<?php

namespace App\Support;

use App\Models\KontrolKarti;
use App\Models\KontrolKartiSablonu;
use App\Models\SahaAnalizi;
use Illuminate\Support\Str;

/**
 * Hazır İstemler → "Kontrol kartı" isteminin Gemini cevabını (JSON) okur (14.11.2026).
 *
 *  - Cevap bir JSON nesnesidir: {"alan_adi", "sablon", "maddeler": [...]}; ```json çitleri
 *    ve önündeki/arkasındaki açıklama ayıklanır.
 *  - Şablon sistemde varsa (anahtar ya da ≥ %85 benzer ad) şablonun maddeleri sırasıyla
 *    Gemini'nin değerlendirmesiyle doldurulur; Gemini'nin eklediği fazladan maddeler sona eklenir.
 *  - Yoksa Gemini'nin maddelerinden YENİ şablon oluşturulur (kontrol_karti_sablonlari) —
 *    aynı alan bir dahaki sefere listeden seçilebilir.
 */
class KontrolKartiIceAktarici
{
    /**
     * @return array{alan_adi: string, sablon: string, yeni_sablon: bool, maddeler: array<int, array<string, mixed>>}
     */
    public static function aktar(string $metin, ?int $userId = null): array
    {
        $veri = static::cozumle($metin);
        $gelen = array_map(static::normalize(...), $veri['maddeler']);
        $alanAdi = trim((string) ($veri['alan_adi'] ?? '')) ?: 'Kontrol alanı';

        $anahtar = static::sablonBul((string) ($veri['sablon'] ?? ''), $alanAdi);
        $yeni = false;

        if (! $anahtar) {
            $anahtar = static::sablonOlustur($alanAdi, $gelen, $userId);
            $yeni = true;
        }

        return [
            'alan_adi' => $alanAdi,
            'sablon' => $anahtar,
            'yeni_sablon' => $yeni,
            'maddeler' => static::sablonlaBirlestir($anahtar, $gelen),
        ];
    }

    /** @return array{alan_adi?: string, sablon?: string, maddeler: array<int, array<string, mixed>>} */
    public static function cozumle(string $metin): array
    {
        $metin = trim($metin);

        if (preg_match('/```(?:json)?\s*(.+?)```/su', $metin, $m)) {
            $metin = trim($m[1]);
        }

        $bas = strpos($metin, '{');
        $son = strrpos($metin, '}');

        if ($bas === false || $son === false) {
            throw new \RuntimeException('Cevapta JSON bulunamadı. Gemini\'nin verdiği kod bloğunun tamamını yapıştırın.');
        }

        $veri = json_decode(substr($metin, $bas, $son - $bas + 1), true);

        if (! is_array($veri) || ! is_array($veri['maddeler'] ?? null) || ! $veri['maddeler']) {
            throw new \RuntimeException('JSON okunamadı ya da "maddeler" listesi boş. Cevap yarıda kesildiyse Gemini\'ye "JSON\'u eksiksiz tekrar ver" yazın.');
        }

        $veri['maddeler'] = array_values(array_filter($veri['maddeler'], fn ($m) => is_array($m) && filled($m['baslik'] ?? null)));

        if (! $veri['maddeler']) {
            throw new \RuntimeException('Maddelerde "baslik" alanı yok.');
        }

        return $veri;
    }

    /** Tek maddeyi kart biçimine çevirir (ölçeğe yuvarlama, durum ve işaret temizliği). */
    public static function normalize(array $m): array
    {
        $metin = fn (string $a, string $v = '') => trim((string) ($m[$a] ?? $v));
        $olcek = fn (string $olcek, string $a) => SahaAnalizi::olcegeYuvarla($olcek, $m[$a] ?? null) ?? '';

        $isaret = null;
        if (is_array($m['isaret'] ?? null) && is_numeric($m['isaret']['x'] ?? null) && is_numeric($m['isaret']['y'] ?? null)) {
            $isaret = [
                'x' => round(max(0, min(100, (float) $m['isaret']['x'])), 2),
                'y' => round(max(0, min(100, (float) $m['isaret']['y'])), 2),
            ];
        }

        return [
            'kod' => Str::slug($metin('kod') ?: $metin('baslik'), '_') ?: 'madde',
            'baslik' => $metin('baslik'),
            'kontrol' => $metin('kontrol'),
            'durum' => static::durum($metin('durum')),
            'isaret' => $isaret,
            'tespit' => $metin('tespit'),
            'olasi_sonuc' => $metin('olasi_sonuc'),
            'etkilenenler' => $metin('etkilenenler', 'Tüm çalışanlar'),
            'mevcut_onlem' => $metin('mevcut_onlem', 'Yok'),
            'olasilik' => $olcek('olasilik', 'olasilik'),
            'frekans' => $olcek('frekans', 'frekans'),
            'siddet' => $olcek('siddet', 'siddet'),
            'oneri' => $metin('oneri'),
            'sorumlu' => $metin('sorumlu', 'İşveren / İşveren Vekili'),
            'hedef_gun' => is_numeric($m['hedef_gun'] ?? null) ? (string) (int) $m['hedef_gun'] : '30',
            'yeni_olasilik' => $olcek('olasilik', 'yeni_olasilik'),
            'yeni_frekans' => $olcek('frekans', 'yeni_frekans'),
            'yeni_siddet' => $olcek('siddet', 'yeni_siddet'),
            'yonetmelik' => $metin('yonetmelik'),
            'standart' => $metin('standart'),
        ];
    }

    public static function durum(string $metin): ?string
    {
        $n = static::norm($metin);

        return match (true) {
            $n === '' => null,
            str_contains($n, 'uygunsuz') || str_contains($n, 'uygundegil') => KontrolKarti::UYGUNSUZ,
            str_contains($n, 'kapsam') || str_contains($n, 'yok') && str_contains($n, 'ilgili') => KontrolKarti::KAPSAM_DISI,
            str_contains($n, 'uygun') => KontrolKarti::UYGUN,
            default => null,   // "bakılmadı", "görülmüyor" vb.
        };
    }

    /** Var olan şablonun anahtarı: önce Gemini'nin yazdığı anahtar, sonra benzer ad. */
    public static function sablonBul(string $anahtar, string $alanAdi): ?string
    {
        $secenekler = KontrolKarti::sablonSecenekleri();

        if ($anahtar !== '' && isset($secenekler[$anahtar])) {
            return $anahtar;
        }

        $aranan = static::norm($alanAdi);

        foreach ($secenekler as $k => $ad) {
            similar_text($aranan, static::norm($ad), $yuzde);

            if ($yuzde >= 85) {
                return $k;
            }
        }

        return null;
    }

    /** Gemini'nin maddelerinden yeni şablon (durum/işaret olmadan, risk değerleri öneri olarak). */
    public static function sablonOlustur(string $alanAdi, array $maddeler, ?int $userId): string
    {
        $temel = 'ozel_'.(Str::slug($alanAdi, '_') ?: 'alan');
        $anahtar = Str::limit($temel, 70, '');
        $i = 2;

        while (KontrolKartiSablonu::where('anahtar', $anahtar)->exists() || config('kontrol_kartlari.'.$anahtar)) {
            $anahtar = Str::limit($temel, 66, '').'_'.$i++;
        }

        KontrolKartiSablonu::create([
            'user_id' => $userId,
            'anahtar' => $anahtar,
            'ad' => Str::limit($alanAdi, 250, ''),
            'aciklama' => 'Gemini kontrol kartından eklendi ('.now()->format('d.m.Y').').',
            'maddeler' => array_map(fn (array $m) => [
                'kod' => $m['kod'],
                'baslik' => $m['baslik'],
                'kontrol' => $m['kontrol'],
                'tespit' => $m['tespit'],
                'olasi_sonuc' => $m['olasi_sonuc'],
                'etkilenenler' => $m['etkilenenler'],
                'oneri' => $m['oneri'],
                'sorumlu' => $m['sorumlu'],
                'hedef_gun' => (int) $m['hedef_gun'],
                'once' => [$m['olasilik'], $m['frekans'], $m['siddet']],
                'sonra' => [$m['yeni_olasilik'], $m['yeni_frekans'], $m['yeni_siddet']],
                'yonetmelik' => $m['yonetmelik'],
                'standart' => $m['standart'],
            ], $maddeler),
        ]);

        return $anahtar;
    }

    /**
     * Şablon maddeleri sırasıyla; Gemini'de karşılığı (kod ya da başlık) olan madde
     * Gemini'nin değerlendirmesini alır, olmayan "bakılmadı" kalır. Fazlalar sona.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sablonlaBirlestir(string $anahtar, array $gelen): array
    {
        $kalan = $gelen;
        $sonuc = [];

        foreach (KontrolKarti::bosMaddeler($anahtar) as $s) {
            $eslesen = null;

            foreach ($kalan as $j => $g) {
                if ($g['kod'] === $s['kod'] || static::norm($g['baslik']) === static::norm($s['baslik'])) {
                    $eslesen = $j;

                    break;
                }
            }

            if ($eslesen === null) {
                $sonuc[] = $s;

                continue;
            }

            $g = $kalan[$eslesen];
            unset($kalan[$eslesen]);

            // Gemini'nin boş bıraktığı alan şablondaki öneriyle dolar.
            $sonuc[] = array_merge($s, array_filter($g, fn ($v) => $v !== '' && $v !== null), [
                'kod' => $s['kod'],
                'baslik' => $s['baslik'],
                'kontrol' => $s['kontrol'],
                'durum' => $g['durum'],
                'isaret' => $g['isaret'],
            ]);
        }

        return [...$sonuc, ...array_values($kalan)];
    }

    private static function norm(string $metin): string
    {
        $metin = mb_strtolower(strtr($metin, ['İ' => 'i', 'I' => 'ı']));

        return preg_replace('/[^a-z0-9]/', '', strtr($metin, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u'])) ?? '';
    }
}
