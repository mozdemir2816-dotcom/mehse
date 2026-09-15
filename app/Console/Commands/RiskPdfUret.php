<?php

namespace App\Console\Commands;

use App\Models\RiskDegerlendirmesi;
use App\Support\RiskDegerlendirmesiUretici;
use Illuminate\Console\Command;

/**
 * Büyük risk değerlendirmesi raporlarının PDF'ini arka planda üretir —
 * bkz. RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI ve
 * EditRiskDegerlendirmesi'nin "PDF İndir/Hazırla" aksiyonu. Paylaşımlı
 * hosting'te cPanel Cron Jobs ile her dakika çalıştırılır (SSH/queue
 * worker olmadığından artisan komutlarını çalıştırmanın tek yolu bu).
 */
class RiskPdfUret extends Command
{
    protected $signature = 'risk:pdf-uret';

    protected $description = 'Talep edilmiş büyük risk değerlendirmesi PDF\'lerini arka planda üretir';

    /** Tek çalıştırmada en fazla bu kadar rapor işlenir — süre sınırını aşmasın. */
    private const PARCA = 3;

    /** Bu süreden eski talepler tekrar denenmez (kalıcı hatalı kayıt sonsuz denenmesin). */
    private const AZAMI_BEKLEME_DK = 15;

    public function handle(): int
    {
        $adaylar = RiskDegerlendirmesi::query()
            ->whereNotNull('pdf_talep_edildi_at')
            ->where('pdf_talep_edildi_at', '>=', now()->subMinutes(self::AZAMI_BEKLEME_DK))
            ->where(function ($q) {
                $q->whereNull('pdf_hazir_at')
                    ->orWhereColumn('pdf_hazir_at', '<', 'pdf_talep_edildi_at');
            })
            ->orderBy('pdf_talep_edildi_at')
            ->limit(self::PARCA)
            ->get();

        foreach ($adaylar as $rd) {
            try {
                $yol = RiskDegerlendirmesiUretici::uretVeKaydet($rd);
                $rd->forceFill(['pdf_yolu' => $yol, 'pdf_hazir_at' => now()])->saveQuietly();
                $this->info("#{$rd->id} PDF üretildi: {$yol}");
            } catch (\Throwable $e) {
                report($e);
                $this->error("#{$rd->id} PDF üretilemedi: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
