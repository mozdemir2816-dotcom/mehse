<?php

namespace App\Support;

/**
 * Tabela editörünün piktogram kütüphanesi (resources/tabela/piktogramlar.json). Editör bu listeyi kendi
 * JavaScript'inde tutar; JSON, sunucu tarafı SVG/PDF üretimi içindir ve editör ile aynı anahtarları taşır
 * (TabelaTest anahtarların eşitliğini doğrular).
 */
class TabelaPiktogramlari
{
    /** @var array<string, array{name: string, section: string, cat: string, svg: string}>|null */
    private static ?array $liste = null;

    /** @return array<string, array{name: string, section: string, cat: string, svg: string}> */
    public static function tum(): array
    {
        return self::$liste ??= json_decode((string) file_get_contents(resource_path('tabela/piktogramlar.json')), true) ?: [];
    }

    public static function var(?string $anahtar): bool
    {
        return $anahtar !== null && isset(self::tum()[$anahtar]);
    }

    public static function ad(?string $anahtar): string
    {
        return self::tum()[$anahtar]['name'] ?? '';
    }

    /**
     * Piktogramın iç çizimi (dış <svg> etiketi atılmış) ve viewBox'ı; başka bir SVG'nin içine
     * yuvalanabilir.
     *
     * @return array{viewBox: string, ic: string}|null
     */
    public static function ic(?string $anahtar): ?array
    {
        $svg = self::tum()[$anahtar]['svg'] ?? null;

        if (! $svg || ! preg_match('/^\s*<svg\b([^>]*)>(.*)<\/svg>\s*$/s', $svg, $m)) {
            return null;
        }

        preg_match('/viewBox="([^"]+)"/', $m[1], $vb);

        return ['viewBox' => $vb[1] ?? '0 0 100 100', 'ic' => $m[2]];
    }
}
