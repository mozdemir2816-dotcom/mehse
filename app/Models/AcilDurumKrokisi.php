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
        'kroki3b' => 'array',
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

    /**
     * Krokide bulunması gereken unsurlar (Acil Durumlar Yön. Md.12(1)(f),
     * 01.10.2021 değişikliğiyle). `zorunlu` = false olanlar "bulunması halinde"
     * gösterilir (uyarı sistemi) veya iyi uygulamadır (Buradasınız) — eksikliği
     * kontrolü düşürmez, yalnız hatırlatılır.
     *
     * @var array<string, array{0: string, 1: array<int, string>, 2: bool}>
     */
    public const YONETMELIK_UNSURLARI = [
        'sondurme' => ['Yangın söndürme ekipmanı (Md.12/1-f-1)', ['sondurucu', 'co2', 'kkt', 'kopuk', 'hidrant', 'dis_hidrant', 'kum', 'sprinkler'], true],
        'ilkyardim' => ['İlk yardım malzemesi (Md.12/1-f-2)', ['ilkyardim', 'sedye', 'aed'], true],
        'cikis' => ['Acil çıkış (Md.12/1-f-3)', ['cikis', 'merdiven'], true],
        'kacis' => ['Kaçış yolu / yönü (Md.12/1-f-3)', ['cikis_sol', 'cikis_sag'], true],
        'toplanma' => ['Toplanma yeri (Md.12/1-f-3)', ['toplanma'], true],
        'enerji' => ['Elektrik / gaz kesme noktası (Md.12/1-f-7)', ['elektrik_pano', 'gaz_vana'], true],
        'uyari' => ['Uyarı sistemi — varsa (Md.12/1-f-3)', ['alarm', 'siren'], false],
        'buradasiniz' => ['"Buradasınız" işareti (asılacak her kopyada)', ['buradasiniz'], false],
    ];

    /**
     * Yönetmelik unsur kontrolü: kaçış yönü çizilmiş kaçış yolu (`ogeler.yollar`)
     * ile de sağlanır.
     *
     * @return array<string, array{baslik: string, var: bool, zorunlu: bool}>
     */
    public function yonetmelikKontrolu(): array
    {
        if ($this->kroki3bVar() && empty($this->semboller) && empty($this->duvarlar)) {
            return $this->yonetmelikKontrolu3b();
        }

        $tipler = collect($this->semboller ?? [])->pluck('tip')->unique()->all();
        $yolVar = ! empty($this->ogeler['yollar'] ?? []);

        return collect(static::YONETMELIK_UNSURLARI)->map(fn (array $u, string $k) => [
            'baslik' => $u[0],
            'var' => array_intersect($u[1], $tipler) !== [] || ($k === 'kacis' && $yolVar),
            'zorunlu' => $u[2],
        ])->all();
    }

    /** @return array<int, string> eksik zorunlu unsurların başlıkları */
    public function eksikZorunluUnsurlar(): array
    {
        return collect($this->yonetmelikKontrolu())
            ->filter(fn (array $u) => $u['zorunlu'] && ! $u['var'])
            ->pluck('baslik')->values()->all();
    }

    /*
    |--------------------------------------------------------------------------
    | 3B (izometrik) editör — kroki3b: {S: öğeler, G: ızgara, I: işletme/plan bilgisi}
    |--------------------------------------------------------------------------
    | S öğe türleri: b = kutu/duvar/makine, z = zemin/alan, s = ISO 7010 işareti (k = kod),
    | a = yön oku, r = tahliye rotası (p = noktalar).
    */

    /** 3B editörde çizim var mı? */
    public function kroki3bVar(): bool
    {
        return count($this->kroki3b['S'] ?? []) > 0;
    }

    /**
     * 3B editör verisini doğrular / sınırlar. Editör iframe'i mehse ile aynı kaynaktan çalıştığı için
     * metinlerden HTML/tırnak karakterleri atılır; yalnız bilinen öğe türleri kalır, sayılar
     * sınırlanır, toplam boyut sınırlıdır. Geçersiz/boş veri null döner.
     *
     * @return array{S: array, G: array, I: array}|null
     */
    public static function temizle3b(array $veri): ?array
    {
        $metin = fn ($v, int $uz = 200) => mb_substr(preg_replace('/[<>"`\\\\\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', trim(strip_tags((string) $v))), 0, $uz);
        $temizDeger = null;
        $temizDeger = function ($v, int $derinlik) use (&$temizDeger, $metin) {
            if (is_bool($v) || $v === null) {
                return $v;
            }
            if (is_int($v) || is_float($v)) {
                return round(max(-100000, min(100000, (float) $v)), 3);
            }
            if (is_string($v)) {
                return $metin($v, 600);
            }
            if (is_array($v) && $derinlik < 4) {
                $cikti = [];
                foreach (array_slice($v, 0, 400, true) as $a => $d) {
                    $anahtar = is_int($a) ? $a : (preg_match('/^[A-Za-z0-9_]{1,16}$/', (string) $a) ? (string) $a : null);
                    if ($anahtar !== null) {
                        $cikti[$anahtar] = $temizDeger($d, $derinlik + 1);
                    }
                }

                return array_is_list($v) ? array_values($cikti) : $cikti;
            }

            return null;
        };

        $ogeler = collect($veri['S'] ?? [])->take(3000)
            ->filter(fn ($o) => is_array($o) && in_array($o['t'] ?? null, ['b', 'z', 's', 'a', 'r'], true))
            ->map(fn ($o) => $temizDeger($o, 1))
            ->values()->all();

        $g = $veri['G'] ?? [];
        $izgara = [
            'w' => (int) max(4, min(120, (float) ($g['w'] ?? 24))),
            'h' => (int) max(4, min(120, (float) ($g['h'] ?? 16))),
            'cell' => round(max(0.5, min(20, (float) ($g['cell'] ?? 2.5))), 2),
        ];

        $bilgi = is_array($veri['I'] ?? null) ? $temizDeger($veri['I'], 1) : [];

        $sonuc = ['S' => $ogeler, 'G' => $izgara, 'I' => $bilgi];

        return strlen((string) json_encode($sonuc)) > 1_500_000 ? null : $sonuc;
    }

    /**
     * 3B krokide bulunması gereken unsurlar (işaret kodlarına göre; Md.12(1)(f)).
     *
     * @var array<string, array{0: string, 1: array<int, string>, 2: bool}>
     */
    public const YONETMELIK_UNSURLARI_3B = [
        'sondurme' => ['Yangın söndürme ekipmanı (Md.12/1-f-1)', ['F001', 'F002'], true],
        'ilkyardim' => ['İlk yardım malzemesi (Md.12/1-f-2)', ['E003'], true],
        'cikis' => ['Acil çıkış (Md.12/1-f-3)', ['E001', 'E002'], true],
        'kacis' => ['Kaçış yolu / yönü (Md.12/1-f-3) — rota veya en az 3 yön oku', [], true],
        'toplanma' => ['Toplanma yeri (Md.12/1-f-3)', ['E007'], true],
        'uyari' => ['Yangın alarm butonu — varsa (Md.12/1-f-3)', ['F005'], false],
        'buradasiniz' => ['"Buradasınız" işareti (asılacak her kopyada)', ['YOU'], false],
    ];

    /** @return array<string, array{baslik: string, var: bool, zorunlu: bool}> */
    public function yonetmelikKontrolu3b(): array
    {
        $ogeler = collect($this->kroki3b['S'] ?? []);
        $kodlar = $ogeler->where('t', 's')->pluck('k')->unique()->all();
        $yonVar = $ogeler->contains(fn ($o) => ($o['t'] ?? null) === 'r' && count($o['p'] ?? []) > 1) || $ogeler->where('t', 'a')->count() >= 3;

        return collect(static::YONETMELIK_UNSURLARI_3B)->map(fn (array $u, string $k) => [
            'baslik' => $u[0],
            'var' => $k === 'kacis' ? $yonVar : array_intersect($u[1], $kodlar) !== [],
            'zorunlu' => $u[2],
        ])->all();
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
