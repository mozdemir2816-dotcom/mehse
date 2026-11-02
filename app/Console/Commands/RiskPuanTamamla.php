<?php

namespace App\Console\Commands;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Support\GeminiRiskPuanTamamlayici;
use App\Support\RiskSkorlama;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * "Eksik Puan/Önlemleri AI ile Tamamla" (02.11.2026): puanı (O / F / Ş) eksik maddeleri yapay zekâ ile
 * TOPLU tamamlar — tek istekte 25 madde (GeminiRiskPuanTamamlayici::puanTopluOner). Düğme ilk partiyi
 * hemen işler, kalan raporu öncelik listesine alır; zamanlayıcı her dakika bir parti daha işler.
 * Termin, puan girilince RiskMaddesi kaydı tarafından risk düzeyine göre (Derhal / 1 Ay / 3 Ay /
 * Sürekli-Rutin) atanır. Var olan puan ve önlemlerin üzerine yazılmaz.
 */
class RiskPuanTamamla extends Command
{
    protected $signature = 'risk:puan-tamamla {--limit=25 : Tek istekte gönderilecek madde sayısı}';

    protected $description = 'Puanı eksik risk maddelerini yapay zekâ ile toplu tamamlar (öncelik listesindeki raporlar)';

    public const ONCELIK_ANAHTARI = 'risk-puan-tamamla-oncelik';

    public const PARTI = 25;

    public static function oncelikliyeAl(int $rdId): void
    {
        Cache::forever(self::ONCELIK_ANAHTARI, array_values(array_unique([...Cache::get(self::ONCELIK_ANAHTARI, []), $rdId])));
    }

    public static function kuyruktaMi(int $rdId): bool
    {
        return in_array($rdId, Cache::get(self::ONCELIK_ANAHTARI, []), true);
    }

    /**
     * Puanı eksik ya da önlemi hiç olmayan (mevcut önlem VE öneri boş) maddeler — sihirbazla aynı kural.
     * HAZOP puan kullanmaz.
     */
    public static function eksikSorgu(RiskDegerlendirmesi $rd): Builder
    {
        return RiskMaddesi::query()
            ->where('risk_degerlendirmesi_id', $rd->id)
            ->whereNotNull('tehlike')->where('tehlike', '!=', '')
            ->where(function ($q) use ($rd) {
                $q->whereNull('olasilik')->orWhereNull('siddet')
                    ->orWhere(fn ($o) => $o->where(fn ($a) => $a->whereNull('mevcut_onlem')->orWhere('mevcut_onlem', ''))
                        ->where(fn ($b) => $b->whereNull('oneri')->orWhere('oneri', '')));
                if (RiskSkorlama::ucEksenliMi($rd->yontem)) {
                    $q->orWhereNull('frekans');
                }
            });
    }

    /** Raporun bir partisini işler; tamamlanan madde sayısı. */
    public static function isle(RiskDegerlendirmesi $rd, int $limit = self::PARTI): int
    {
        if (! in_array($rd->yontem, ['fine_kinney', 'matris_5x5'], true)) {
            return 0;
        }

        $maddeler = self::eksikSorgu($rd)->orderBy('sira')->orderBy('id')->limit($limit)->get()->keyBy('id');
        if ($maddeler->isEmpty()) {
            return 0;
        }

        $f = $rd->firma;
        $baglam = $f ? trim(implode(' · ', array_filter([
            $f->unvan, $f->nace_kodu ? 'NACE '.$f->nace_kodu : null, $f->nace_aciklama,
            $f->tehlike_sinifi ? $f->tehlikeSinifiEtiketi() : null,
        ]))) : null;

        $tamamlanan = 0;
        foreach (GeminiRiskPuanTamamlayici::puanTopluOner($maddeler->values(), $rd->yontem, $baglam) as $id => $p) {
            $m = $maddeler[$id] ?? null;
            if (! $m) {
                continue;
            }
            // Yalnız boş alanlar dolar; kullanıcının girdiği puan / önlem korunur.
            foreach (['olasilik', 'frekans', 'siddet', 'son_olasilik', 'son_frekans', 'son_siddet'] as $alan) {
                if ($m->{$alan} === null && $p[$alan] !== null) {
                    $m->{$alan} = $p[$alan];
                }
            }
            if (blank($m->mevcut_onlem) && filled($p['mevcut_onlem'])) {
                $m->mevcut_onlem = $p['mevcut_onlem'];
            }
            $m->save();   // puan / düzey / termin kayıtta hesaplanır
            $tamamlanan++;
        }

        return $tamamlanan;
    }

    public function handle(): int
    {
        if (! GeminiRiskPuanTamamlayici::aktifMi()) {
            return self::SUCCESS;
        }

        foreach (Cache::get(self::ONCELIK_ANAHTARI, []) as $rdId) {
            $rd = RiskDegerlendirmesi::find($rdId);
            if ($rd && self::eksikSorgu($rd)->exists()) {
                $sayi = self::isle($rd, (int) $this->option('limit'));
                $this->info("RD {$rdId}: {$sayi} madde tamamlandı.");

                return self::SUCCESS;   // dakikada tek istek
            }

            Cache::forever(self::ONCELIK_ANAHTARI, array_values(array_diff(Cache::get(self::ONCELIK_ANAHTARI, []), [$rdId])));
        }

        return self::SUCCESS;
    }
}
