<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Canlı sunucuda (SSH yok) K1–K6 veritabanı değişikliklerini güvenle uygular.
 * deploy/onbellek.mjs --komut "kurulum:kontinus-artilar" ile tetiklenir.
 */
class KurulumKontinusArtilar extends Command
{
    protected $signature = 'kurulum:kontinus-artilar';

    protected $description = 'K1-K6 revir/ecza/aşı, taşeron başvuru portalı, KKD talepleri ve DÖF ret geçmişi tablolarını uygular.';

    public function handle(): int
    {
        $yol = 'database/migrations/2026_11_22_235000_kontinus_artilar.php';
        $this->info('Migration uygulanıyor: '.$yol);
        $kod = Artisan::call('migrate', ['--path' => $yol, '--force' => true]);
        $this->line(trim(Artisan::output()));

        return $kod;
    }
}
