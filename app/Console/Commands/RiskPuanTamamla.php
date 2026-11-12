<?php

namespace App\Console\Commands;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Support\GeminiRiskPuanTamamlayici;
use App\Support\KutuphanePuanOgrenme;
use App\Support\RiskAlanTamamlama;
use App\Support\RiskSkorlama;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * "Eksik Alanları AI ile Tamamla" (02.11.2026): eksik alanı olan maddeleri (risk tanımı, mevcut önlem,
 * öneri, mevzuat, rol bazlı sorumlu, O/F/Ş ve önlem sonrası puanlar — App\Support\RiskAlanTamamlama)
 * yapay zekâ ile TOPLU tamamlar: tek istekte 25 madde. Düğme ilk partiyi hemen işler, kalan raporu öncelik
 * listesine alır; zamanlayıcı her dakika bir parti daha işler. Yalnız boş alanlar dolar; termin risk
 * düzeyinden hesaplanır. Dolan alanlar kütüphanedeki karşılığına da kalıcı yazılır (KutuphanePuanOgrenme).
 *
 * Bir kez denenip yapay zekânın dolduramadığı madde 1 gün tekrar denenmez (sonsuz istek döngüsü olmasın);
 * düğmeye yeniden basmak bu listeyi sıfırlar.
 *
 *   php artisan risk:puan-tamamla --rapor=RD-2026-127   → raporu kuyruğa alır, ilk partiyi hemen işler
 */
class RiskPuanTamamla extends Command
{
    protected $signature = 'risk:puan-tamamla
        {--limit=25 : Tek istekte gönderilecek madde sayısı}
        {--rapor=* : Belge numarası (ör. RD-2026-127) — kuyruğa alınır ve ilk parti hemen işlenir}
        {--sadece-kuyruk : --rapor ile: hemen işlemeden yalnız kuyruğa al (zamanlayıcı dakikada bir parti işler)}
        {--mevzuat : --rapor ile: yalnız mevzuatı boş maddeleri hemen tamamla (kuyruk yok; diğer boş alanlar da dolar)}';

    protected $description = 'Eksik alanlı risk maddelerini (öneri, mevzuat, puan, sorumlu…) yapay zekâ ile toplu tamamlar';

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

    private static function denenenAnahtari(int $rdId): string
    {
        return 'risk-puan-tamamla-denenen-'.$rdId;
    }

    public static function denemeleriSifirla(int $rdId): void
    {
        Cache::forget(self::denenenAnahtari($rdId));
    }

    /** Eksik alanı olan maddeler. HAZOP / hastane şablonu puan kullanmaz (çağıran süzer). */
    public static function eksikSorgu(RiskDegerlendirmesi $rd): Builder
    {
        return RiskAlanTamamlama::eksikKosulu(
            RiskMaddesi::query()->where('risk_degerlendirmesi_id', $rd->id),
            RiskSkorlama::ucEksenliMi($rd->yontem),
            mevzuat: false,   // mevzuat eksik sayılmaz (kota); mevzuatı boşlar için --mevzuat (mevzuatsizSorgu)
        );
    }

    /** Henüz denenmemiş eksik maddeler. */
    private static function islenecekSorgu(RiskDegerlendirmesi $rd): Builder
    {
        return self::eksikSorgu($rd)->whereNotIn('id', Cache::get(self::denenenAnahtari($rd->id), []));
    }

    /**
     * Mevzuatı boş maddeler (12.11.2026). "Eksik" sayımına mevzuat katılmadığı için ayrı yoldan, elle tetiklenir:
     *   php artisan risk:puan-tamamla --rapor=RD-2026-127 --mevzuat
     */
    public static function mevzuatsizSorgu(RiskDegerlendirmesi $rd): Builder
    {
        return RiskMaddesi::query()->where('risk_degerlendirmesi_id', $rd->id)
            ->where(fn ($w) => $w->whereNull('mevzuat')->orWhereRaw('TRIM(mevzuat) = ?', ['']));
    }

    /** Raporun bir partisini işler; en az bir alanı dolan madde sayısı. */
    public static function isle(RiskDegerlendirmesi $rd, int $limit = self::PARTI, bool $mevzuat = false): int
    {
        if (! in_array($rd->yontem, ['fine_kinney', 'matris_5x5'], true) || $rd->hastaneMi()) {
            return 0;
        }

        $sorgu = $mevzuat
            ? self::mevzuatsizSorgu($rd)->whereNotIn('id', Cache::get(self::denenenAnahtari($rd->id), []))
            : self::islenecekSorgu($rd);
        $maddeler = $sorgu->orderBy('sira')->orderBy('id')->limit($limit)->get()->keyBy('id');
        if ($maddeler->isEmpty()) {
            return 0;
        }

        $f = $rd->firma;
        $baglam = $f ? trim(implode(' · ', array_filter([
            $f->nace_kodu ? 'NACE '.$f->nace_kodu : null, $f->nace_aciklama,
            $f->tehlike_sinifi ? $f->tehlikeSinifiEtiketi() : null,
        ]))) : null;

        $oneriler = GeminiRiskPuanTamamlayici::alanTopluOner($maddeler->values(), $rd->yontem, $baglam ?: null);
        if ($oneriler === []) {
            return 0;   // yanıt yok (kota / yoğunluk) — denenmiş sayılmaz, sonra yeniden denenir
        }

        $anahtar = self::denenenAnahtari($rd->id);
        Cache::put($anahtar, array_values(array_unique([...Cache::get($anahtar, []), ...$maddeler->keys()->all()])), now()->addDay());

        $ucEksen = RiskSkorlama::ucEksenliMi($rd->yontem);

        return KutuphanePuanOgrenme::sistemOlarak(function () use ($oneriler, $maddeler, $ucEksen): int {
            $tamamlanan = 0;
            foreach ($oneriler as $id => $p) {
                $m = $maddeler[$id] ?? null;
                // Yalnız boş alanlar dolar; kullanıcının girdiği metin / puan korunur.
                if ($m && RiskAlanTamamlama::uygula($m, $p, $ucEksen)) {
                    $m->save();   // puan / düzey / termin kayıtta hesaplanır, kütüphaneye yazılır
                    $tamamlanan++;
                }
                if ($m && filled($p['mevzuat'] ?? null)) {
                    RiskAlanTamamlama::kutuphaneyeYaz($m, ['mevzuat' => $p['mevzuat']]);
                }
            }

            return $tamamlanan;
        });
    }

    public function handle(): int
    {
        if (! GeminiRiskPuanTamamlayici::aktifMi()) {
            $this->warn('Gemini anahtarı tanımlı değil.');

            return self::SUCCESS;
        }

        if ($raporlar = (array) $this->option('rapor')) {
            foreach ($raporlar as $belgeNo) {
                $rd = RiskDegerlendirmesi::where('belge_no', $belgeNo)->first();
                if (! $rd) {
                    $this->error("{$belgeNo} bulunamadı.");

                    continue;
                }
                self::denemeleriSifirla($rd->id);

                if ($this->option('mevzuat')) {
                    $bos = self::mevzuatsizSorgu($rd)->count();
                    // Parti parti, ilerleme durana kadar (yanıt yoksa ya da hepsi denendiyse biter).
                    for ($i = 0; $i < 20 && self::mevzuatsizSorgu($rd)->exists(); $i++) {
                        if (self::isle($rd, (int) $this->option('limit'), mevzuat: true) === 0) {
                            break;
                        }
                    }
                    $kalan = self::mevzuatsizSorgu($rd)->count();
                    $rd->pdfOnbellegiTemizle();
                    $rd->saveQuietly();
                    $this->info("{$belgeNo} ({$rd->firma_unvan}): mevzuatı boş {$bos} maddeden ".($bos - $kalan)." doldu, kalan {$kalan}.");

                    continue;
                }

                $eksik = self::eksikSorgu($rd)->count();
                // Önce kuyruğa: web üzerinden çağrıda bağlantı zaman aşımına uğrasa da zamanlayıcı devam eder.
                self::oncelikliyeAl($rd->id);
                $this->info("{$belgeNo} ({$rd->firma_unvan}): {$eksik} eksik madde kuyruğa alındı.");
                if ($this->option('sadece-kuyruk')) {
                    continue;
                }
                $sayi = self::isle($rd, (int) $this->option('limit'));
                $kalan = self::eksikSorgu($rd)->count();
                $rd->pdfOnbellegiTemizle();
                $rd->saveQuietly();
                $this->info("{$belgeNo} ({$rd->firma_unvan}): {$eksik} eksik maddeden {$sayi} tamamlandı, kalan {$kalan} arka planda dakikada ".self::PARTI.' madde.');
            }

            return self::SUCCESS;
        }

        foreach (Cache::get(self::ONCELIK_ANAHTARI, []) as $rdId) {
            $rd = RiskDegerlendirmesi::find($rdId);
            if ($rd && self::islenecekSorgu($rd)->exists()) {
                $sayi = self::isle($rd, (int) $this->option('limit'));
                $this->info("RD {$rdId}: {$sayi} madde tamamlandı.");

                return self::SUCCESS;   // dakikada tek istek
            }

            Cache::forever(self::ONCELIK_ANAHTARI, array_values(array_diff(Cache::get(self::ONCELIK_ANAHTARI, []), [$rdId])));
        }

        return self::SUCCESS;
    }
}
