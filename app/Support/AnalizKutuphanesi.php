<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Analiz ↔ kütüphane eşitlemesi (kullanıcı kararı 12.11.2026):
 *
 * 1) Sisteme giren her analiz maddesi kütüphanede olsun: kütüphaneye bağlı olmayan madde (Excel'den yüklenen,
 *    elle eklenen) aynı yöntemin firma sektörüne uygun klasörlerinde metniyle aranır; varsa ona bağlanır, yoksa
 *    "Analizlerden – {Sektör} ({Yöntem})" klasörüne yeni kütüphane maddesi olarak eklenir. Kütüphane maddesi
 *    firmaya bağlı değildir — firma / analiz silinse de kalır, aynı sektörden yeni firmaya çekilebilir.
 * 2) Mükerrer olmasın: eşleşme "faaliyet + tehlike" (büyük/küçük harf, boşluk, sondaki nokta farkı yok sayılır),
 *    yoksa klasörlerde tek karşılığı olan "tehlike" metni. Aynı yüklemedeki tekrarlar da tek maddeye bağlanır.
 * 3) Bir maddede dolan alan, aynı kütüphane maddesine bağlı diğer analiz maddelerinin BOŞ alanına da yazılır
 *    (kardeslereYay) — yapay zekâ aynı maddeyi her firmada ayrı ayrı doldurmaz. Dolu alana dokunulmaz.
 *
 * Kütüphane tüm kullanıcılarda ortak olduğundan yalnız sahip hesabın analizleri kütüphaneye yazılır ve
 * yayılma yalnız aynı kullanıcının firmalarında olur (müşteri verisi başka hesaba geçmez).
 * Hastane şablonu, HAZOP ve FMEA kapsam dışıdır (kendi ölçekleri / alanları var).
 */
class AnalizKutuphanesi
{
    public const YONTEMLER = ['fine_kinney', 'matris_5x5'];

    private const METIN = ['risk', 'mevcut_onlem', 'oneri', 'mevzuat', 'sorumlu'];

    /** Yayılma sırasında iç içe tetiklenmeyi önler. */
    private static bool $yayiliyor = false;

    public static function kapsamdaMi(?RiskDegerlendirmesi $rd): bool
    {
        return $rd && in_array($rd->yontem, self::YONTEMLER, true) && ! $rd->hastaneMi()
            && (bool) static::firmasi($rd)?->user?->sahipMi();
    }

    /** Çöp Kutusu'ndaki firmanın analizi de kütüphaneye girer ("firmayı silsem de kalsın"). */
    private static function firmasi(RiskDegerlendirmesi $rd): ?Firma
    {
        return $rd->firma ?? Firma::withTrashed()->find($rd->firma_id);
    }

    /**
     * Analizin kütüphaneye bağlı olmayan maddelerini bağlar / kütüphaneye ekler.
     *
     * @return array{baglanan: int, eklenen: int}
     */
    public static function esitle(RiskDegerlendirmesi $rd): array
    {
        $sonuc = ['baglanan' => 0, 'eklenen' => 0];
        if (! static::kapsamdaMi($rd)) {
            return $sonuc;
        }

        $maddeler = $rd->maddeler()->whereNull('tehlike_id')->orderBy('sira')->orderBy('id')->get();
        if ($maddeler->isEmpty()) {
            return $sonuc;
        }

        $grup = NaceGrubu::bul(static::firmasi($rd)?->nace_kodu);
        [$tam, $yalniz] = static::dizin($rd->yontem, $grup);
        $kategori = null;

        foreach ($maddeler as $m) {
            if (RiskAlanTamamlama::bosMu($m->tehlike)) {
                continue;
            }

            $anahtar = static::anahtar($m->faaliyet, $m->tehlike);
            $t = $tam[$anahtar] ?? (($y = $yalniz[static::sade($m->tehlike)] ?? null) && count($y) === 1 ? $y[0] : null);

            if ($t) {
                $sonuc['baglanan']++;
            } else {
                $kategori ??= static::kategori($rd->yontem, $grup);
                $t = Tehlike::create(static::kutuphaneAlanlari($m, $rd->yontem) + [
                    'tehlike_kategorisi_id' => $kategori->id,
                    'kod' => 'AN-'.strtoupper(Str::random(6)),
                ]);
                $tam[$anahtar] = $t;
                $yalniz[static::sade($m->tehlike)][] = $t;
                $sonuc['eklenen']++;
            }

            // Olaysız bağlama: kaydetme kancaları (puan hesabı, PDF önbelleği) tetiklenmez.
            RiskMaddesi::whereKey($m->id)->update(['tehlike_id' => $t->id]);
            $m->tehlike_id = $t->id;
            RiskAlanTamamlama::kutuphaneyeYaz($m);
        }

        return $sonuc;
    }

    /** Tek madde (formdan eklenen) için esitle(). */
    public static function maddeyiEsitle(RiskMaddesi $m): void
    {
        $rd = $m->riskDegerlendirmesi;
        if ($m->tehlike_id || ! static::kapsamdaMi($rd)) {
            return;
        }

        static::esitle($rd);
    }

    /**
     * Maddenin dolu alanlarını aynı kütüphane maddesine bağlı, aynı kullanıcının diğer analiz maddelerinin BOŞ
     * alanlarına yazar. Puanlar yalnız aynı yöntemde ve karşı maddede hiç puan yoksa kopyalanır.
     */
    public static function kardeslereYay(RiskMaddesi $m): int
    {
        if (static::$yayiliyor || ! $m->tehlike_id) {
            return 0;
        }
        $rd = $m->riskDegerlendirmesi;
        if (! static::kapsamdaMi($rd)) {
            return 0;
        }

        $kardesler = RiskMaddesi::query()->with('riskDegerlendirmesi')
            ->where('tehlike_id', $m->tehlike_id)->whereKeyNot($m->id)
            ->whereHas('riskDegerlendirmesi', fn ($q) => $q->whereIn('yontem', self::YONTEMLER)
                ->whereHas('firma', fn ($f) => $f->where('user_id', static::firmasi($rd)->user_id)))
            ->get();

        $sayi = 0;
        static::$yayiliyor = true;
        try {
            foreach ($kardesler as $k) {
                if ($k->riskDegerlendirmesi?->hastaneMi()) {
                    continue;
                }
                if (static::bostanDoldur($k, $m, ayniYontem: $k->riskDegerlendirmesi->yontem === $rd->yontem)) {
                    $k->save();
                    $sayi++;
                }
            }
        } finally {
            static::$yayiliyor = false;
        }

        return $sayi;
    }

    /**
     * Kotasız tamamlama (yapay zekâdan önce): maddenin boş alanlarını kütüphane karşılığından ve aynı kütüphane
     * maddesine bağlı diğer analiz maddelerinden doldurur. En az bir alan dolduysa kaydeder.
     */
    public static function kotasizDoldur(RiskMaddesi $m): bool
    {
        if (! $m->tehlike_id) {
            return false;
        }

        $degisti = false;
        $rd = $m->riskDegerlendirmesi;

        $t = Tehlike::find($m->tehlike_id);
        if ($t) {
            foreach (self::METIN as $alan) {
                if (static::alanBos($m, $alan) && ! static::alanBos($t, $alan)) {
                    $m->{$alan} = $t->{$alan};
                    $degisti = true;
                }
            }
            if ($rd?->yontem === 'fine_kinney' && $m->olasilik === null && $m->siddet === null && $m->frekans === null
                && $t->olasilik !== null && $t->siddet !== null && $t->frekans !== null) {
                foreach (['olasilik', 'frekans', 'siddet', 'son_olasilik', 'son_frekans', 'son_siddet'] as $a) {
                    $m->{$a} = $t->{$a};
                }
                $degisti = true;
            }
        }

        if ($rd && static::kapsamdaMi($rd)) {
            $kaynaklar = RiskMaddesi::query()->with('riskDegerlendirmesi')
                ->where('tehlike_id', $m->tehlike_id)->whereKeyNot($m->id)
                ->whereHas('riskDegerlendirmesi.firma', fn ($f) => $f->where('user_id', static::firmasi($rd)->user_id))
                ->limit(20)->get();
            foreach ($kaynaklar as $k) {
                $degisti = static::bostanDoldur($m, $k, ayniYontem: $k->riskDegerlendirmesi?->yontem === $rd->yontem) || $degisti;
            }
        }

        if ($degisti) {
            static::$yayiliyor = true;
            try {
                $m->save();
            } finally {
                static::$yayiliyor = false;
            }
        }

        return $degisti;
    }

    /** $hedef'in boş alanlarını $kaynak'tan doldurur; değişiklik olduysa true (kaydetmez). */
    private static function bostanDoldur(RiskMaddesi $hedef, RiskMaddesi $kaynak, bool $ayniYontem): bool
    {
        $degisti = false;
        foreach (self::METIN as $alan) {
            if (static::alanBos($hedef, $alan) && ! static::alanBos($kaynak, $alan)
                && ! ($alan === 'risk' && static::sade($kaynak->risk) === static::sade($hedef->tehlike))) {
                $hedef->{$alan} = $kaynak->{$alan};
                $degisti = true;
            }
        }

        $eksenler = ['olasilik', 'frekans', 'siddet'];
        if ($ayniYontem && $hedef->olasilik === null && $hedef->siddet === null && $hedef->frekans === null
            && $kaynak->olasilik !== null && $kaynak->siddet !== null) {
            foreach ([...$eksenler, 'son_olasilik', 'son_frekans', 'son_siddet'] as $a) {
                $hedef->{$a} = $kaynak->{$a};
            }
            $degisti = true;
        }

        return $degisti;
    }

    private static function alanBos($m, string $alan): bool
    {
        return match ($alan) {
            'risk' => RiskAlanTamamlama::riskEksikMi($m),
            'sorumlu' => RiskAlanTamamlama::sorumluEksikMi($m),
            default => RiskAlanTamamlama::bosMu($m->{$alan}),
        };
    }

    /**
     * Yöntem + sektör (+ ortak) klasörlerindeki maddelerin dizini.
     *
     * @return array{0: array<string, Tehlike>, 1: array<string, list<Tehlike>>}
     */
    private static function dizin(string $yontem, ?string $grup): array
    {
        $kategoriIdler = TehlikeKategorisi::query()->uygun($yontem, $grup)->pluck('id')
            ->merge(TehlikeKategorisi::where('anahtar', static::kategoriAnahtari($yontem, $grup))->pluck('id'))
            ->unique();

        $tam = $yalniz = [];
        Tehlike::whereIn('tehlike_kategorisi_id', $kategoriIdler)->orderBy('id')
            ->get()->each(function (Tehlike $t) use (&$tam, &$yalniz): void {
                $tam[static::anahtar($t->faaliyet, $t->tehlike)] ??= $t;
                $yalniz[static::sade($t->tehlike)][] = $t;
            });

        return [$tam, $yalniz];
    }

    private static function kategoriAnahtari(string $yontem, ?string $grup): string
    {
        return 'analiz_'.($grup ?: NaceGrubu::ORTAK).'_'.$yontem;
    }

    private static function kategori(string $yontem, ?string $grup): TehlikeKategorisi
    {
        $sektor = $grup ? (config("risk_nace.gruplar.{$grup}.ad") ?? $grup) : 'Sektörü belirsiz';

        return TehlikeKategorisi::firstOrCreate(
            ['anahtar' => static::kategoriAnahtari($yontem, $grup)],
            [
                'ad' => "Analizlerden – {$sektor} (".config("isg.risk_yontemleri.{$yontem}", $yontem).')',
                'yontem' => $yontem,
                'nace_gruplari' => [$grup ?: NaceGrubu::ORTAK],
                'sira' => 700,
            ],
        );
    }

    /** Analiz maddesinden kütüphane alanları (firmaya özgü termin / durum / bölüm adı taşınmaz). */
    private static function kutuphaneAlanlari(RiskMaddesi $m, string $yontem): array
    {
        $alanlar = [
            'faaliyet' => $m->faaliyet,
            'tehlike' => trim((string) $m->tehlike),
            'risk' => RiskAlanTamamlama::riskEksikMi($m) ? null : $m->risk,
            'mevcut_onlem' => $m->mevcut_onlem,
            'oneri' => $m->oneri,
            'mevzuat' => $m->mevzuat,
            'sorumlu' => RiskAlanTamamlama::sorumluEksikMi($m) ? null : $m->sorumlu,
        ];

        if ($yontem === 'fine_kinney' && $m->olasilik !== null && $m->siddet !== null && $m->frekans !== null) {
            $alanlar += [
                'olasilik' => $m->olasilik, 'frekans' => $m->frekans, 'siddet' => $m->siddet,
                'son_olasilik' => $m->son_olasilik, 'son_frekans' => $m->son_frekans, 'son_siddet' => $m->son_siddet,
            ];
        } elseif ($yontem === 'matris_5x5' && $m->olasilik !== null && $m->siddet !== null) {
            $alanlar['matris'] = [
                'olasilik' => (int) $m->olasilik, 'siddet' => (int) $m->siddet,
                'son_olasilik' => $m->son_olasilik !== null ? (int) $m->son_olasilik : null,
                'son_siddet' => $m->son_siddet !== null ? (int) $m->son_siddet : null,
            ];
        }

        return $alanlar;
    }

    private static function anahtar(?string $faaliyet, ?string $tehlike): string
    {
        return static::sade($faaliyet).'|'.static::sade($tehlike);
    }

    public static function sade(mixed $metin): string
    {
        // Türkçe I/ı/İ/i farkı (Excel'deki "KAYNAK DUMANI" = "Kaynak dumanı") eşleşmeyi bozmasın.
        $metin = strtr((string) $metin, ['İ' => 'i', 'I' => 'i', 'ı' => 'i']);

        return (string) Str::of(mb_strtolower($metin, 'UTF-8'))->squish()->trim(' .;,');
    }
}
