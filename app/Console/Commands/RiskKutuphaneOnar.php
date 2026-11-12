<?php

namespace App\Console\Commands;

use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Support\GeminiRiskPuanTamamlayici;
use App\Support\RiskAlanTamamlama;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Kütüphane onarımı (02.11.2026): Risk Kütüphanesindeki (inşaat, metal, hastane… tüm klasörler) eksik
 * alanlı maddeleri kalıcı olarak tamamlar.
 *
 *  1. Kotasız: aynı kütüphane maddesinden (tehlike_id) ya da aynı tehlike metninden eklenmiş analiz
 *     maddelerinde doldurulmuş öneri / önlem / mevzuat / risk / sorumlu kütüphaneye kopyalanır.
 *  2. Kotasız: puanı olup rezidüel puanı / termini boş maddelerde bunlar analizdeki kuralla hesaplanır
 *     (önlem sonrası şiddet ve frekans aynı, olasılık bir alt basamak — RiskMaddesi::varsayilanSonOlasilik).
 *  3. Kalanlar 40'lık partilerle yapay zekâya gider (Fine-Kinney ölçeği — kütüphane ölçeği): yalnız boş
 *     alanlar dolar; tehlikeyle ÇELİŞEN risk tanımı (ör. mikser çarpmasına "elektrik çarpması") düzeltilir;
 *     termin puandan hesaplanır.
 *
 * Ücretsiz Gemini kotası model başına günde ~20 istek: zamanlayıcı (--zamanlanmis, 15 dakikada bir) yalnız
 * analiz kuyrukları boşken ve günde en fazla config('isg.risk_ai.kutuphane_onarim_gunluk_istek') istek atar;
 * Gemini kotanın dolduğunu bildirince (429) ertesi gün 10:30'a kadar durur — kütüphane günler içinde kendiliğinden dolar. Elle çalıştırmada sınır
 * --limit'tir. Yapay zekânın dolduramadığı madde 30 gün yeniden denenmez (--sifirla ile sıfırlanır).
 * HAZOP klasörleri puan / öneri kullanmadığından kapsam dışıdır.
 */
class RiskKutuphaneOnar extends Command
{
    protected $signature = 'risk:repair-library-all-fields
        {--limit=80 : Yapay zekâya gönderilecek en fazla madde (40\'lık partiler)}
        {--kategori= : Yalnız bu kütüphane klasörü (tehlike_kategorileri.anahtar)}
        {--sadece-oneri : Yalnız önerisi boş maddeler}
        {--zamanlanmis : Zamanlayıcıdan çağrı — günlük istek sınırı ve kuyruk önceliği uygulanır}
        {--sifirla : Daha önce denenip doldurulamayan maddeleri yeniden dene}';

    protected $description = 'Risk kütüphanesindeki eksik alanları (öneri, mevzuat, puan, rezidüel, sorumlu, termin) analizlerden ve yapay zekâ ile kalıcı tamamlar';

    public const PARTI = 40;

    private const DENENEN_ANAHTARI = 'risk-kutuphane-onar-denenen';

    /** Gemini 429 (günlük kota doldu) verdiyse zamanlanmış onarım bu ana kadar bekler. */
    public const KOTA_ANAHTARI = 'risk-kutuphane-onar-kota-doldu';

    /** Zamanlanmış çalıştırmada kotasız (tarama) adımlar saatte bir yeter. */
    private const TARAMA_ANAHTARI = 'risk-kutuphane-onar-tarama';

    public function handle(): int
    {
        if ($this->option('sifirla')) {
            Cache::forget(self::DENENEN_ANAHTARI);
        }

        $once = $this->eksikSorgu()->count();

        if (! $this->option('zamanlanmis') || Cache::add(self::TARAMA_ANAHTARI, true, now()->addHour())) {
            $kopyalanan = $this->analizlerdenDoldur();
            $hesaplanan = $this->hesaplananlariDoldur();
            $this->info("Analizlerden kopyalanan (kotasız): {$kopyalanan} · rezidüel puan / termini hesaplanan (kotasız): {$hesaplanan} kütüphane maddesi.");
        }

        $aiDolan = $this->yapayZekaIleDoldur();

        $kalan = $this->eksikSorgu()->count();
        $bosOneri = $this->kapsam(Tehlike::query())->where(fn ($q) => $q->whereNull('oneri')->orWhere('oneri', ''))->count();
        $this->info("Eksik madde: {$once} → {$kalan} · yapay zekâ ile tamamlanan: {$aiDolan} · önerisi boş kalan: {$bosOneri}");

        return self::SUCCESS;
    }

    /** HAZOP dışı, tehlikesi dolu kütüphane maddeleri (isteğe bağlı tek klasör). */
    private function kapsam(Builder $q): Builder
    {
        return $q->whereNotNull('tehlike')->where('tehlike', '!=', '')
            ->whereHas('kategori', fn ($k) => $k->where(fn ($w) => $w->whereNull('yontem')->orWhere('yontem', '!=', 'hazop'))
                ->when($this->option('kategori'), fn ($w, $a) => $w->where('anahtar', $a)));
    }

    private function eksikSorgu(): Builder
    {
        $q = $this->kapsam(Tehlike::query());

        return $this->option('sadece-oneri')
            ? $q->where(fn ($w) => $w->whereNull('oneri')->orWhere('oneri', ''))
            : RiskAlanTamamlama::eksikKosulu($q, true, mevcutOnlem: false);   // mevcut önlem firmaya özgü (12.11.2026)
    }

    /** 1. adım — doldurulmuş analiz maddelerinden kütüphaneye kopya (API yok). */
    private function analizlerdenDoldur(): int
    {
        $sayi = 0;

        $this->eksikSorgu()->select('tehlikeler.*')->chunkById(500, function ($tehlikeler) use (&$sayi) {
            $maddeler = RiskMaddesi::query()
                ->where(fn ($q) => $q->whereIn('tehlike_id', $tehlikeler->pluck('id'))
                    ->orWhereIn('tehlike', $tehlikeler->pluck('tehlike')->unique()))
                ->whereHas('riskDegerlendirmesi')
                ->latest('id')
                ->get(['id', 'tehlike_id', 'tehlike', ...array_diff(RiskAlanTamamlama::KUTUPHANE_METIN_ALANLARI, ['mevzuat'])]);   // analizde mevzuat sütunu yok

            foreach ($tehlikeler as $t) {
                $adaylar = $maddeler->filter(fn ($m) => $m->tehlike_id === $t->id || ($m->tehlike_id === null && trim((string) $m->tehlike) === trim((string) $t->tehlike)));
                $degisti = false;

                foreach (array_diff(RiskAlanTamamlama::KUTUPHANE_METIN_ALANLARI, ['mevzuat']) as $alan) {
                    $eksik = match ($alan) {
                        'risk' => RiskAlanTamamlama::riskEksikMi($t),
                        'sorumlu' => RiskAlanTamamlama::sorumluEksikMi($t),
                        default => RiskAlanTamamlama::bosMu($t->{$alan}),
                    };
                    if (! $eksik) {
                        continue;
                    }
                    $kaynak = $adaylar->first(fn ($m) => ! RiskAlanTamamlama::bosMu($m->{$alan})
                        && ! ($alan === 'sorumlu' && trim((string) $m->sorumlu) === RiskMaddesi::VARSAYILAN_SORUMLU)
                        && ! ($alan === 'risk' && RiskAlanTamamlama::riskEksikMi($m)));
                    if ($kaynak) {
                        $t->{$alan} = $kaynak->{$alan};
                        $degisti = true;
                    }
                }

                if ($degisti) {
                    $t->save();
                    $sayi++;
                }
            }
        });

        return $sayi;
    }

    /** 2. adım — puanı olan maddede rezidüel puan ve termin hesaplanır (API yok). */
    private function hesaplananlariDoldur(): int
    {
        $sayi = 0;

        $this->kapsam(Tehlike::query())
            ->whereNotNull('olasilik')->whereNotNull('frekans')->whereNotNull('siddet')
            ->where(fn ($w) => $w->whereNull('son_olasilik')->orWhereNull('son_frekans')->orWhereNull('son_siddet')
                ->orWhereNull('termin')->orWhere('termin', ''))
            ->chunkById(500, function ($tehlikeler) use (&$sayi) {
                foreach ($tehlikeler as $t) {
                    $t->son_siddet ??= $t->siddet;
                    $t->son_frekans ??= $t->frekans;
                    $t->son_olasilik ??= RiskMaddesi::varsayilanSonOlasilik('fine_kinney', (float) $t->olasilik, (float) $t->son_frekans, (float) $t->son_siddet);
                    if (RiskAlanTamamlama::bosMu($t->termin)) {
                        $t->termin = RiskAlanTamamlama::kutuphaneTermini($t);
                    }
                    if ($t->isDirty()) {
                        $t->save();
                        $sayi++;
                    }
                }
            });

        return $sayi;
    }

    /** 3. adım — kalanları yapay zekâ ile (40'lık partiler). */
    private function yapayZekaIleDoldur(): int
    {
        if (! GeminiRiskPuanTamamlayici::aktifMi()) {
            $this->warn('Gemini anahtarı tanımlı değil — yapay zekâ adımı atlandı.');

            return 0;
        }

        $limit = max(0, (int) $this->option('limit'));
        $gunAnahtari = 'risk-kutuphane-onar-istek-'.now()->toDateString();

        if ($this->option('zamanlanmis')) {
            if (Cache::has(self::KOTA_ANAHTARI)) {
                return 0;
            }
            // Kullanıcının bastığı analiz kuyrukları önce; kota onlara kalsın.
            if (Cache::get(RiskPuanTamamla::ONCELIK_ANAHTARI) || Cache::get(RiskOneriTamamla::ONCELIK_ANAHTARI)) {
                return 0;
            }
            $kalanIstek = (int) config('isg.risk_ai.kutuphane_onarim_gunluk_istek', 25) - (int) Cache::get($gunAnahtari, 0);
            $limit = min($limit, max(0, $kalanIstek) * self::PARTI);
        }

        $dolan = 0;
        while ($limit > 0) {
            $denenen = Cache::get(self::DENENEN_ANAHTARI, []);
            $tehlikeler = $this->eksikSorgu()->whereNotIn('id', $denenen)->with('kategori')
                ->orderBy('tehlike_kategorisi_id')->orderBy('id')
                ->limit(min(self::PARTI, $limit))->get()->keyBy('id');

            if ($tehlikeler->isEmpty()) {
                break;
            }

            $baglam = 'Risk kütüphanesi: '.$tehlikeler->map(fn ($t) => $t->kategori?->ad)->filter()->unique()->take(4)->implode(', ');
            Cache::put($gunAnahtari, (int) Cache::get($gunAnahtari, 0) + 1, now()->addDays(2));
            $oneriler = GeminiRiskPuanTamamlayici::alanTopluOner($tehlikeler->values(), 'fine_kinney', $baglam, riskDenetle: true, mevcutOnlem: false);

            if ($oneriler === []) {
                if (GeminiRiskPuanTamamlayici::$sonHataDurumu === 429) {
                    // Günlük kota doldu: Gemini kotası Pasifik gece yarısı (TR ~10:00-11:00) yenilenir.
                    Cache::put(self::KOTA_ANAHTARI, true, now()->hour < 10 ? now()->setTime(10, 30) : now()->addDay()->setTime(10, 30));
                    $this->warn('Gemini günlük kotası doldu — onarım kota yenilenince kendiliğinden devam eder.');
                } else {
                    $this->warn('Yapay zekâ yanıt vermedi (yoğunluk) — sonraki çalıştırmada devam edilir.');
                }
                break;
            }

            Cache::put(self::DENENEN_ANAHTARI, array_values(array_unique([...$denenen, ...$tehlikeler->keys()->all()])), now()->addDays(30));

            // Teşhis (12.11.2026): yanıt kaç maddeyi kapsıyor, hangi alandan kaç değer geldi.
            $gelen = collect($oneriler);
            $this->line('Parti: '.$tehlikeler->count().' madde gönderildi, '.$gelen->count().' yanıt · gelen alanlar: '
                .collect(['risk', 'oneri', 'mevzuat', 'sorumlu', 'olasilik', 'son_olasilik'])
                    ->map(fn ($a) => $a.' '.$gelen->filter(fn ($p) => filled($p[$a] ?? null))->count())->implode(', '));

            foreach ($oneriler as $id => $p) {
                $t = $tehlikeler[$id] ?? null;
                if (! $t) {
                    continue;
                }
                unset($p['mevcut_onlem']);
                $degisen = RiskAlanTamamlama::uygula($t, $p, true, riskDuzelt: true);
                if (RiskAlanTamamlama::bosMu($t->termin) && ($termin = RiskAlanTamamlama::kutuphaneTermini($t))) {
                    $t->termin = $termin;
                    $degisen[] = 'termin';
                }
                if ($degisen) {
                    try {
                        $t->unsetRelation('kategori')->save();
                        $dolan++;
                    } catch (\Throwable $e) {
                        // Tek maddenin kayıt hatası partinin geri kalanını düşürmesin (07.11.2026).
                        \Illuminate\Support\Facades\Log::warning('Kütüphane onarımı: madde kaydedilemedi', ['tehlike' => $t->id, 'hata' => $e->getMessage()]);
                    }
                }
            }

            $this->line("  → {$dolan} madde kaydedildi (toplam)");
            $limit -= $tehlikeler->count();
            $this->line("  parti: {$tehlikeler->count()} madde gönderildi, ".count($oneriler).' yanıt.');
        }

        return $dolan;
    }
}
