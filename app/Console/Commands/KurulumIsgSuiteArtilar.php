<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Canlıda İSG Suite karşılaştırma artıları tablolarını
 * (saha_ziyaret_kanitlari, canli_egitim_oturumlari) güvenle kurar.
 * Çağrı: node deploy/onbellek.mjs --komut "kurulum:isgsuite-artilar"
 */
class KurulumIsgSuiteArtilar extends Command
{
    protected $signature = 'kurulum:isgsuite-artilar';

    protected $description = 'saha_ziyaret_kanitlari ve canli_egitim_oturumlari tablolarını kurar';

    public function handle(): int
    {
        $hatalar = 0;

        $tablolar = [
            'saha_ziyaret_kanitlari' => 'database/migrations/2026_11_22_210000_create_saha_ziyaret_kanitlari_table.php',
            'canli_egitim_oturumlari' => 'database/migrations/2026_11_22_211000_create_canli_egitim_oturumlari_table.php',
        ];

        foreach ($tablolar as $tablo => $yol) {
            if (Schema::hasTable($tablo)) {
                $this->info("{$tablo} zaten var.");
                continue;
            }

            $kod = $this->call('migrate', ['--path' => $yol, '--force' => true]);
            if ($kod !== 0 || ! Schema::hasTable($tablo)) {
                $this->error("{$tablo} KURULAMADI.");
                $hatalar++;
            } else {
                $this->info("{$tablo} kuruldu.");
            }
        }

        return $hatalar === 0 ? self::SUCCESS : self::FAILURE;
    }
}
