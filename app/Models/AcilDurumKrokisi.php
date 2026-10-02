<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acil Durum / Tahliye Krokisi — firma başına bir kayıt. Editör (tarayıcı)
 * duvarları, işaretleri, odaları, kaçış yollarını ve metinleri JSON olarak,
 * kaydedildiği andaki görünümü de PNG (`gorsel_yolu`) olarak yazar; PDF bu
 * PNG'yi basar (editörle birebir). PNG yoksa eski SVG çizimi kullanılır.
 */
class AcilDurumKrokisi extends Model
{
    protected $table = 'acil_durum_krokileri';

    protected $guarded = ['id'];

    protected $casts = [
        'duvarlar' => 'array',
        'semboller' => 'array',
        'ogeler' => 'array',
        'antet' => 'array',
        'hazirlanma_tarihi' => 'date',
    ];

    public const SURUM = 2;

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(Firma $firma): self
    {
        $kroki = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $kroki->exists) {
            $kroki->hazirlanma_tarihi = now();
            $kroki->duvarlar = [];
            $kroki->semboller = [];
            $kroki->ogeler = ['odalar' => [], 'yollar' => [], 'metinler' => []];
            $kroki->antet = ['surum' => static::SURUM];
            $kroki->save();
        }

        return $kroki;
    }

    /**
     * Editöre gönderilecek veri. 1. sürüm (1000x700 tuval) krokiler yeni
     * tuvale (1400x990) ölçeklenir.
     *
     * @return array{duvarlar: array, semboller: array, ogeler: array, antet: array}
     */
    public function editorVerisi(): array
    {
        $antet = $this->antet ?? [];
        $kat = ($antet['surum'] ?? 1) >= static::SURUM ? 1.0 : 1.4;
        $o = fn ($v) => round((float) $v * $kat, 1);

        return [
            'duvarlar' => collect($this->duvarlar ?? [])->map(fn ($d) => ['x1' => $o($d['x1']), 'y1' => $o($d['y1']), 'x2' => $o($d['x2']), 'y2' => $o($d['y2'])])->values()->all(),
            'semboller' => collect($this->semboller ?? [])->map(fn ($s) => [...$s, 'x' => $o($s['x']), 'y' => $o($s['y']), 'r' => (int) ($s['r'] ?? 0), 's' => (float) ($s['s'] ?? 1)])->values()->all(),
            'ogeler' => [
                'odalar' => array_values($this->ogeler['odalar'] ?? []),
                'yollar' => array_values($this->ogeler['yollar'] ?? []),
                'metinler' => array_values($this->ogeler['metinler'] ?? []),
            ],
            'antet' => [...$antet, 'surum' => static::SURUM],
        ];
    }

    /**
     * Tarayıcıdan gelen veriyi doğrular / sınırlar (yalnız bilinen işaret
     * tipleri, sayısal koordinatlar, kısa metinler).
     *
     * @return array{duvarlar: array, semboller: array, ogeler: array, antet: array}
     */
    public static function temizle(array $veri): array
    {
        $sayi = fn ($v, $min = -2000, $max = 4000) => round(max($min, min($max, (float) $v)), 1);
        $metin = fn ($v, $uz = 80) => mb_substr(trim(strip_tags((string) ($v ?? ''))), 0, $uz);
        $renk = fn ($v, $vars) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $vars;
        $tipler = array_keys(config('isg.kroki.semboller', []));

        $duvarlar = collect($veri['duvarlar'] ?? [])->take(800)->filter(fn ($d) => is_array($d))
            ->map(fn ($d) => ['x1' => $sayi($d['x1'] ?? 0), 'y1' => $sayi($d['y1'] ?? 0), 'x2' => $sayi($d['x2'] ?? 0), 'y2' => $sayi($d['y2'] ?? 0)])
            ->values()->all();

        $semboller = collect($veri['semboller'] ?? [])->take(800)
            ->filter(fn ($s) => is_array($s) && in_array($s['tip'] ?? null, $tipler, true))
            ->map(fn ($s) => [
                'tip' => $s['tip'],
                'x' => $sayi($s['x'] ?? 0),
                'y' => $sayi($s['y'] ?? 0),
                'r' => ((int) ($s['r'] ?? 0)) % 360,
                's' => round(max(0.4, min(4, (float) ($s['s'] ?? 1))), 2),
                'etiket' => $metin($s['etiket'] ?? null, 60) ?: null,
            ])->values()->all();

        $o = $veri['ogeler'] ?? [];
        $ogeler = [
            'odalar' => collect($o['odalar'] ?? [])->take(200)->filter(fn ($a) => is_array($a))->map(fn ($a) => [
                'x' => $sayi($a['x'] ?? 0), 'y' => $sayi($a['y'] ?? 0),
                'w' => $sayi($a['w'] ?? 0, 5, 4000), 'h' => $sayi($a['h'] ?? 0, 5, 4000),
                'etiket' => $metin($a['etiket'] ?? null, 60), 'renk' => $renk($a['renk'] ?? null, '#e2e8f0'),
            ])->values()->all(),
            'yollar' => collect($o['yollar'] ?? [])->take(100)->filter(fn ($y) => is_array($y))->map(fn ($y) => [
                'noktalar' => collect($y['noktalar'] ?? [])->take(60)->filter(fn ($n) => is_array($n) && count($n) >= 2)
                    ->map(fn ($n) => [$sayi($n[0]), $sayi($n[1])])->values()->all(),
            ])->filter(fn ($y) => count($y['noktalar']) >= 2)->values()->all(),
            'metinler' => collect($o['metinler'] ?? [])->take(200)->filter(fn ($m) => is_array($m) && filled($m['metin'] ?? null))->map(fn ($m) => [
                'x' => $sayi($m['x'] ?? 0), 'y' => $sayi($m['y'] ?? 0),
                'metin' => $metin($m['metin'], 120), 'boyut' => (int) max(8, min(72, (int) ($m['boyut'] ?? 18))),
                'renk' => $renk($m['renk'] ?? null, '#111827'), 'r' => ((int) ($m['r'] ?? 0)) % 360,
            ])->values()->all(),
        ];

        $a = $veri['antet'] ?? [];
        $antet = [
            'surum' => static::SURUM,
            'baslik' => $metin($a['baslik'] ?? 'ACİL DURUM TAHLİYE KROKİSİ', 80),
            'kat' => $metin($a['kat'] ?? null, 60),
            'hazirlayan' => $metin($a['hazirlayan'] ?? null, 60),
            'onaylayan' => $metin($a['onaylayan'] ?? null, 60),
            'revizyon' => $metin($a['revizyon'] ?? null, 20),
            'notlar' => $metin($a['notlar'] ?? null, 200),
            'antet_goster' => (bool) ($a['antet_goster'] ?? true),
            'lejant_goster' => (bool) ($a['lejant_goster'] ?? true),
            'izgara' => (bool) ($a['izgara'] ?? true),
            'altlik_opaklik' => round(max(0, min(1, (float) ($a['altlik_opaklik'] ?? 0.5))), 2),
        ];

        return compact('duvarlar', 'semboller', 'ogeler', 'antet');
    }

    /** Sembol tiplerinin adet dağılımı — lejant için. */
    public function lejant(): array
    {
        $etiketler = config('isg.kroki.semboller');

        return collect($this->semboller ?? [])
            ->groupBy('tip')
            ->map(fn ($grup, $tip) => [
                'tip' => $tip,
                'ad' => $etiketler[$tip]['ad'] ?? $tip,
                'renk' => $etiketler[$tip]['renk'] ?? '#666',
                'adet' => $grup->count(),
            ])
            ->values()
            ->all();
    }
}
