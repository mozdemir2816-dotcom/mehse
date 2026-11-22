<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Canlı sunucuda (SSH yok) N1–N4 veritabanı değişikliklerini güvenle uygular.
 * deploy/onbellek.mjs --komut "kurulum:nanoisg-artilar" ile tetiklenir.
 */
class KurulumNanoIsgArtilar extends Command
{
    protected $signature = 'kurulum:nanoisg-artilar';

    protected $description = 'N1-N4 ziyaretçi giriş öncesi eğitim/test kilidi, günlük yoklama/puantaj ve çalışan rol/mesleki belge alanlarını uygular.';

    public function handle(): int
    {
        $yol = 'database/migrations/2026_11_22_235900_nanoisg_artilar.php';
        $this->info('Migration uygulanıyor: '.$yol);
        $kod = Artisan::call('migrate', ['--path' => $yol, '--force' => true]);
        $this->line(trim(Artisan::output()));

        return $kod;
    }
}
