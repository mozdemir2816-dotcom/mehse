<?php

namespace App\Console\Commands;

use App\Support\EkipmanTalimatTaslagi;
use App\Support\GeminiTalimatUretici;
use Illuminate\Console\Command;

/**
 * Kütüphanede talimatı olmayan ekipman tipleri için yapay zekâ talimat taslağı (12.11.2026).
 *
 *   php artisan ekipman:talimat-taslak --kuru            → hangi tipler için üretileceğini listeler
 *   php artisan ekipman:talimat-taslak                   → hepsini üretir (tip başına bir istek)
 *   php artisan ekipman:talimat-taslak --tip="Kule Vinç"
 */
class EkipmanTalimatTaslak extends Command
{
    protected $signature = 'ekipman:talimat-taslak
        {--tip=* : Yalnız bu ekipman tipleri}
        {--kuru : Üretmeden listele}';

    protected $description = 'Talimatı olmayan iş ekipmanları için yapay zekâ ile talimat taslağı hazırlar';

    public function handle(): int
    {
        @set_time_limit(0);
        $tipler = $this->option('tip') ?: EkipmanTalimatTaslagi::talimatsizTipler();

        if ($this->option('kuru')) {
            $this->line(implode("\n", $tipler));
            $this->info(count($tipler).' tip için taslak üretilecek.');

            return self::SUCCESS;
        }
        if (! GeminiTalimatUretici::aktifMi()) {
            $this->warn('Gemini anahtarı tanımlı değil.');

            return self::FAILURE;
        }

        $tamam = 0;
        foreach ($tipler as $tip) {
            $s = EkipmanTalimatTaslagi::olustur($tip);
            $s ? $tamam++ : null;
            $this->line(($s ? '✓ ' : '✗ ').$tip.($s ? ' — '.count($s->bolumler ?? []).' bölüm' : ' — yanıt yok'));
        }
        $this->info("{$tamam}/".count($tipler).' taslak hazır.');

        return self::SUCCESS;
    }
}
