<?php

namespace App\Console\Commands;

use App\Models\RiskMaddesi;
use App\Support\GeminiRiskPuanTamamlayici;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * "Öneri" (düzeltici / önleyici faaliyet) metni boş risk maddelerini Gemini ile doldurur
 * (kullanıcı 14.10.2026: "önerisi eksik olanları yapay zekâ ile doldur"). Zamanlayıcı her
 * dakika çalıştırır; her çalıştırma GeminiRiskPuanTamamlayici'nin süre bütçesi kadar madde
 * işler, boş madde kalmayınca tek sorguyla biter. Var olan metnin üzerine yazılmaz.
 */
class RiskOneriTamamla extends Command
{
    protected $signature = 'risk:oneri-tamamla {--limit=60 : Tek çalıştırmada en fazla madde}';

    protected $description = 'Öneri (düzeltici faaliyet) metni boş risk maddelerini yapay zekâ ile doldurur';

    /** Yeni eklenen maddeye dokunma — kullanıcı o sırada öneriyi elle yazıyor olabilir. */
    private const BEKLEME_DK = 10;

    private const ARA_ANAHTARI = 'risk-oneri-tamamla-ara';

    private const ARA_DK = 15;

    public function handle(): int
    {
        // Gemini kota / yoğunluk verdiyse bir süre deneme — her dakika boş istek ve log olmasın.
        if (! GeminiRiskPuanTamamlayici::aktifMi() || Cache::has(self::ARA_ANAHTARI)) {
            return self::SUCCESS;
        }

        $maddeler = RiskMaddesi::query()
            ->where(fn ($q) => $q->whereNull('oneri')->orWhere('oneri', ''))
            ->whereNotNull('tehlike')->where('tehlike', '!=', '')
            ->where('created_at', '<=', now()->subMinutes(self::BEKLEME_DK))
            ->whereHas('riskDegerlendirmesi')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($maddeler->isEmpty()) {
            return self::SUCCESS;
        }

        GeminiRiskPuanTamamlayici::devreyiSifirla();
        $dolan = 0;

        foreach ($maddeler as $m) {
            if (GeminiRiskPuanTamamlayici::devreKesikMi()) {
                break;
            }

            $oneri = GeminiRiskPuanTamamlayici::oneriOner($m->tehlike, $m->risk, $m->bolum, $m->faaliyet, $m->mevcut_onlem);

            // Bu arada kullanıcı elle doldurduysa üzerine yazma.
            if ($oneri && RiskMaddesi::whereKey($m->id)->where(fn ($q) => $q->whereNull('oneri')->orWhere('oneri', ''))->exists()) {
                $m->forceFill(['oneri' => $oneri])->save();
                $dolan++;
            }
        }

        if ($dolan === 0) {
            Cache::put(self::ARA_ANAHTARI, true, now()->addMinutes(self::ARA_DK));
        }

        $this->info("{$dolan} maddenin önerisi dolduruldu.");

        return self::SUCCESS;
    }
}
