<?php

namespace App\Console\Commands;

use App\Models\RiskMaddesi;
use App\Support\GeminiRiskPuanTamamlayici;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * "Öneri" (düzeltici / önleyici faaliyet) metni boş risk maddelerini Gemini ile doldurur
 * (kullanıcı 14.10.2026: "önerisi eksik olanları yapay zekâ ile doldur"). Zamanlayıcı her
 * dakika çalıştırır; her çalıştırma tek istekte bir parti madde işler, boş madde kalmayınca
 * tek sorguyla biter. Var olan metnin üzerine yazılmaz.
 *
 * Ücretsiz Gemini kotası model başına günde 20 istek (15.10.2026) — komut günde en fazla
 * GUNLUK_ISTEK istek atar ki kalan kota elle kullanılan AI özelliklerine (sihirbaz vb.) kalsın.
 */
class RiskOneriTamamla extends Command
{
    protected $signature = 'risk:oneri-tamamla {--limit=25 : Tek istekte gönderilecek madde sayısı}';

    protected $description = 'Öneri (düzeltici faaliyet) metni boş risk maddelerini yapay zekâ ile doldurur';

    /** Yeni eklenen maddeye dokunma — kullanıcı o sırada öneriyi elle yazıyor olabilir. */
    private const BEKLEME_DK = 10;

    private const ARA_ANAHTARI = 'risk-oneri-tamamla-ara';

    private const ARA_DK = 15;

    /** Günde en fazla bu kadar Gemini isteği (25 madde × 10 = 250 madde/gün). */
    public const GUNLUK_ISTEK = 10;

    public function handle(): int
    {
        // Gemini kota / yoğunluk verdiyse bir süre deneme — her dakika boş istek ve log olmasın.
        if (! GeminiRiskPuanTamamlayici::aktifMi() || Cache::has(self::ARA_ANAHTARI)) {
            return self::SUCCESS;
        }

        $sayacAnahtari = 'risk-oneri-tamamla-istek-'.now()->toDateString();
        $istekSayisi = (int) Cache::get($sayacAnahtari, 0);

        if ($istekSayisi >= self::GUNLUK_ISTEK) {
            return self::SUCCESS;
        }

        $maddeler = RiskMaddesi::query()
            ->where(fn ($q) => $q->whereNull('oneri')->orWhere('oneri', ''))
            ->whereNotNull('tehlike')->where('tehlike', '!=', '')
            ->where('created_at', '<=', now()->subMinutes(self::BEKLEME_DK))
            ->whereHas('riskDegerlendirmesi')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get()
            ->keyBy('id');

        if ($maddeler->isEmpty()) {
            return self::SUCCESS;
        }

        Cache::put($sayacAnahtari, $istekSayisi + 1, now()->addDays(2));

        $dolan = 0;

        foreach (GeminiRiskPuanTamamlayici::oneriTopluOner($maddeler) as $id => $oneri) {
            // Bu arada kullanıcı elle doldurduysa üzerine yazma.
            if (RiskMaddesi::whereKey($id)->where(fn ($q) => $q->whereNull('oneri')->orWhere('oneri', ''))->exists()) {
                $maddeler[$id]->forceFill(['oneri' => $oneri])->save();
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
