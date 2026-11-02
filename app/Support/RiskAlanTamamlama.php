<?php

namespace App\Support;

use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Risk maddelerinde TAM ALAN tamamlama motoru (02.11.2026).
 *
 * "Eksik madde": şu alanlardan herhangi biri boş — risk tanımı (ya da tehlike metninin kopyası), mevcut
 * önlem, öneri, mevzuat dayanağı, sorumlu (rol bazlı; sistemin koyduğu varsayılan "İşveren / İşveren Vekili"
 * eksik sayılır), O/Ş(/F) ve önlem sonrası (rezidüel) puanlar. Termin AI'dan istenmez: risk düzeyinden
 * hesaplanır (ÇY Derhal, YK 1 Ay, ÖN 3 Ay, düşük Sürekli / Rutin — RiskMaddesi::varsayilanTermin).
 *
 * Analiz maddesinde (risk_maddeleri) mevzuat sütunu yok — mevzuat yalnız kütüphanede tutulur; analizde
 * yapay zekânın önerdiği mevzuat kütüphanedeki karşılığına yazılır.
 *
 * Aynı kurallar hem analiz maddesine (RiskMaddesi) hem kütüphane maddesine (Tehlike) uygulanır. Doldurma
 * yalnız BOŞ alanlara yapılır; kullanıcının yazdığına dokunulmaz. Analizde dolan alanlar kütüphanedeki
 * karşılığına da kalıcı yazılır (kutuphaneyeYaz) — bir sonraki analizde dolu gelir, kota yeniden harcanmaz.
 */
class RiskAlanTamamlama
{
    public const METIN_ALANLARI = ['risk', 'mevcut_onlem', 'oneri', 'mevzuat', 'sorumlu'];

    /** Kütüphaneye geri yazılan metin alanları. */
    public const KUTUPHANE_METIN_ALANLARI = ['risk', 'mevcut_onlem', 'oneri', 'mevzuat', 'sorumlu'];

    /** Eksik madde koşulu (RiskMaddesi: $mevzuat=false — sütun yok; Tehlike: true). */
    public static function eksikKosulu(Builder $q, bool $ucEksen, bool $mevzuat = true): Builder
    {
        $bos = fn (string $alan) => fn ($w) => $w->whereNull($alan)->orWhereRaw('TRIM('.$alan.') = ?', ['']);
        $alanlar = $mevzuat ? self::METIN_ALANLARI : array_values(array_diff(self::METIN_ALANLARI, ['mevzuat']));

        return $q->whereNotNull('tehlike')->where('tehlike', '!=', '')
            ->where(function ($w) use ($bos, $ucEksen, $alanlar) {
                foreach ($alanlar as $alan) {
                    $w->orWhere($bos($alan));
                }
                $w->orWhereColumn('risk', 'tehlike')
                    ->orWhere('sorumlu', RiskMaddesi::VARSAYILAN_SORUMLU)
                    ->orWhereNull('olasilik')->orWhereNull('siddet')
                    ->orWhereNull('son_olasilik')->orWhereNull('son_siddet');
                if ($ucEksen) {
                    $w->orWhereNull('frekans')->orWhereNull('son_frekans');
                }
            });
    }

    public static function bosMu(mixed $deger): bool
    {
        return $deger === null || trim((string) $deger) === '';
    }

    /** Risk tanımı boş ya da tehlike metninin kopyası mı. */
    public static function riskEksikMi(Model $m): bool
    {
        return static::bosMu($m->risk) || static::sade($m->risk) === static::sade($m->tehlike);
    }

    public static function sorumluEksikMi(Model $m): bool
    {
        return static::bosMu($m->sorumlu) || trim((string) $m->sorumlu) === RiskMaddesi::VARSAYILAN_SORUMLU;
    }

    /**
     * Yapay zekâ önerisini maddenin YALNIZ boş alanlarına uygular (kaydetmez). $riskDuzelt: risk tanımı
     * tehlikeyle çelişiyorsa (AI risk_uyumlu=false) dolu olsa da düzeltilir — kütüphane onarımında kullanılır.
     *
     * @param  array<string, mixed>  $p  GeminiRiskPuanTamamlayici::alanTopluOner çıktısının bir maddesi
     * @return array<int, string> değişen alanlar
     */
    public static function uygula(Model $m, array $p, bool $ucEksen, bool $riskDuzelt = false): array
    {
        $degisen = [];

        if (filled($p['risk'] ?? null) && (static::riskEksikMi($m) || ($riskDuzelt && ($p['risk_uyumlu'] ?? true) === false))) {
            $m->risk = $p['risk'];
            $degisen[] = 'risk';
        }
        foreach ($m instanceof Tehlike ? ['mevcut_onlem', 'oneri', 'mevzuat'] : ['mevcut_onlem', 'oneri'] as $alan) {
            if (static::bosMu($m->{$alan}) && filled($p[$alan] ?? null)) {
                $m->{$alan} = $p[$alan];
                $degisen[] = $alan;
            }
        }
        if (static::sorumluEksikMi($m) && filled($p['sorumlu'] ?? null)) {
            $m->sorumlu = $p['sorumlu'];
            $degisen[] = 'sorumlu';
        }

        $eksenler = $ucEksen
            ? ['olasilik', 'frekans', 'siddet', 'son_olasilik', 'son_frekans', 'son_siddet']
            : ['olasilik', 'siddet', 'son_olasilik', 'son_siddet'];
        foreach ($eksenler as $alan) {
            if ($m->{$alan} === null && ($p[$alan] ?? null) !== null) {
                $m->{$alan} = $p[$alan];
                $degisen[] = $alan;
            }
        }

        return $degisen;
    }

    /**
     * Analiz maddesinin dolu alanlarını kütüphanedeki karşılığının BOŞ alanlarına kalıcı yazar.
     * Karşılık: tehlike_id; yoksa tehlike metni birebir aynı kütüphane maddeleri (en fazla 10).
     * Risk tanımı yalnız kütüphanede boş / tehlike kopyasıysa yazılır. Puanlar KutuphanePuanOgrenme'de.
     * $ek: maddede sütunu olmayan değerler (yapay zekânın önerdiği mevzuat).
     *
     * @param  array<string, ?string>  $ek
     * @return int güncellenen kütüphane maddesi sayısı
     */
    public static function kutuphaneyeYaz(RiskMaddesi $m, array $ek = []): int
    {
        $kaynaklar = static::kutuphaneKarsiliklari($m);
        $sayi = 0;

        foreach ($kaynaklar as $t) {
            $degisti = false;

            foreach (self::KUTUPHANE_METIN_ALANLARI as $alan) {
                $deger = $ek[$alan] ?? $m->getAttribute($alan);
                if (static::bosMu($deger) || ($alan === 'sorumlu' && trim((string) $deger) === RiskMaddesi::VARSAYILAN_SORUMLU)) {
                    continue;
                }
                $yaz = match ($alan) {
                    'risk' => static::riskEksikMi($t) && static::sade($deger) !== static::sade($t->tehlike),
                    'sorumlu' => static::sorumluEksikMi($t),
                    default => static::bosMu($t->{$alan}),
                };
                if ($yaz) {
                    $t->{$alan} = $deger;
                    $degisti = true;
                }
            }

            if ($degisti) {
                $t->save();
                $sayi++;
            }
        }

        return $sayi;
    }

    /** @return Collection<int, Tehlike> */
    public static function kutuphaneKarsiliklari(RiskMaddesi $m): Collection
    {
        if ($m->tehlike_id) {
            $t = Tehlike::find($m->tehlike_id);

            return $t ? collect([$t]) : collect();
        }

        if (static::bosMu($m->tehlike)) {
            return collect();
        }

        return Tehlike::where('tehlike', trim((string) $m->tehlike))->limit(10)->get();
    }

    /** Kütüphane maddesinin (Fine-Kinney ölçeği) puanından termin. */
    public static function kutuphaneTermini(Tehlike $t): ?string
    {
        if ($t->olasilik === null || $t->siddet === null || $t->frekans === null) {
            return null;
        }
        $h = RiskSkorlama::hesapla('fine_kinney', (float) $t->olasilik, (float) $t->siddet, (float) $t->frekans);

        return $h['puan'] ? RiskMaddesi::varsayilanTermin('fine_kinney', $h['duzey'], $t->tehlike.' '.$t->oneri) : null;
    }

    private static function sade(mixed $metin): string
    {
        return (string) Str::of((string) $metin)->lower()->squish()->trim(' .');
    }
}
