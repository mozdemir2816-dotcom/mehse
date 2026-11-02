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
 *  2. Kalanlar 25'lik partilerle yapay zekâya gider (Fine-Kinney ölçeği — kütüphane ölçeği): yalnız boş
 *     alanlar dolar; tehlikeyle ÇELİŞEN risk tanımı (ör. mikser çarpmasına "elektrik çarpması") düzeltilir;
 *     termin puandan hesaplanır.
 *
 * Ücretsiz Gemini kotası günde ~20 istek: zamanlayıcı (--zamanlanmis) yalnız analiz kuyrukları boşken ve
 * günde en fazla config('isg.risk_ai.kutuphane_onarim_gunluk_istek') istek atar. Elle çalıştırmada sınır
 * --limit'tir. Yapay zekânın dolduramadığı madde 30 gün yeniden denenmez (--sifirla ile sıfırlanır).
 * HAZOP klasörleri puan / öneri kullanmadığından kapsam dışıdır.
 */
class RiskKutuphaneOnar extends Command
{
    protected $signature = 'risk:repair-library-all-fields
        {--limit=50 : Yapay zekâya gönderilecek en fazla madde (25\'lik partiler)}
        {--kategori= : Yalnız bu kütüphane klasörü (tehlike_kategorileri.anahtar)}
        {--sadece-oneri : Yalnız önerisi boş maddeler}
        {--zamanlanmis : Zamanlayıcıdan çağrı — günlük istek sınırı ve kuyruk önceliği uygulanır}
        {--sifirla : Daha önce denenip doldurulamayan maddeleri yeniden dene}';

    protected $description = 'Risk kütüphanesindeki eksik alanları (öneri, mevzuat, puan, rezidüel, sorumlu, termin) analizlerden ve yapay zekâ ile kalıcı tamamlar';

    public const PARTI = 25;

    private const DENENEN_ANAHTARI = 'risk-kutuphane-onar-denenen';

    public function handle(): int
    {
        if ($this->option('sifirla')) {
            Cache::forget(self::DENENEN_ANAHTARI);
        }

        $once = $this->eksikSorgu()->count();
        $kopyalanan = $this->analizlerdenDoldur();
        $this->info("Analizlerden kopyalanan (kotasız): {$kopyalanan} kütüphane maddesi.");

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
            : RiskAlanTamamlama::eksikKosulu($q, true);
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

    /** 2. adım — kalanları yapay zekâ ile (25'lik partiler). */
    private function yapayZekaIleDoldur(): int
    {
        if (! GeminiRiskPuanTamamlayici::aktifMi()) {
            $this->warn('Gemini anahtarı tanımlı değil — yapay zekâ adımı atlandı.');

            return 0;
        }

        $limit = max(0, (int) $this->option('limit'));
        $gunAnahtari = 'risk-kutuphane-onar-istek-'.now()->toDateString();

        if ($this->option('zamanlanmis')) {
            // Kullanıcının bastığı analiz kuyrukları önce; kota onlara kalsın.
            if (Cache::get(RiskPuanTamamla::ONCELIK_ANAHTARI) || Cache::get(RiskOneriTamamla::ONCELIK_ANAHTARI)) {
                return 0;
            }
            $kalanIstek = (int) config('isg.risk_ai.kutuphane_onarim_gunluk_istek', 8) - (int) Cache::get($gunAnahtari, 0);
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
            $oneriler = GeminiRiskPuanTamamlayici::alanTopluOner($tehlikeler->values(), 'fine_kinney', $baglam, riskDenetle: true);

            if ($oneriler === []) {
                $this->warn('Yapay zekâ yanıt vermedi (kota / yoğunluk) — sonraki çalıştırmada devam edilir.');
                break;
            }

            Cache::put(self::DENENEN_ANAHTARI, array_values(array_unique([...$denenen, ...$tehlikeler->keys()->all()])), now()->addDays(30));

            foreach ($oneriler as $id => $p) {
                $t = $tehlikeler[$id] ?? null;
                if (! $t) {
                    continue;
                }
                $degisen = RiskAlanTamamlama::uygula($t, $p, true, riskDuzelt: true);
                if (RiskAlanTamamlama::bosMu($t->termin) && ($termin = RiskAlanTamamlama::kutuphaneTermini($t))) {
                    $t->termin = $termin;
                    $degisen[] = 'termin';
                }
                if ($degisen) {
                    $t->unsetRelation('kategori')->save();
                    $dolan++;
                }
            }

            $limit -= $tehlikeler->count();
            $this->line("  parti: {$tehlikeler->count()} madde gönderildi, ".count($oneriler).' yanıt.');
        }

        return $dolan;
    }
}
