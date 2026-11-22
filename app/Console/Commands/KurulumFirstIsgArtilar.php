<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Canlı sunucuda (SSH yok) F1–F9 veritabanı değişikliklerini güvenle uygular.
 * deploy/onbellek.mjs --komut "kurulum:firstisg-artilar" ile tetiklenir.
 */
class KurulumFirstIsgArtilar extends Command
{
    protected $signature = 'kurulum:firstisg-artilar';

    protected $description = 'F1-F9 kapalı devre DÖF, risk sürüm kilidi, OSGB kadro/muafiyet, denetim kayıtları ve ekip duyuruları tablolarını uygular.';

    public function handle(): int
    {
        $yol = 'database/migrations/2026_11_22_230000_firstisg_artilar.php';
        $this->info('Migration uygulanıyor: '.$yol);
        $kod = Artisan::call('migrate', ['--path' => $yol, '--force' => true]);
        $this->line(trim(Artisan::output()));

        return $kod;
    }
}
