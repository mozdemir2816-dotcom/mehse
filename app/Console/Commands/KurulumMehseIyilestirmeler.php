<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Canlıda 22.11.2026 iyileştirme tablolarını (canli_imzalar, teftis_paylasimlari)
 * güvenle kurar ve kirli test kayıtlarını temizler.
 * Çağrı: node deploy/onbellek.mjs --komut "kurulum:mehse-iyilestirmeler"
 */
class KurulumMehseIyilestirmeler extends Command
{
    protected $signature = 'kurulum:mehse-iyilestirmeler';

    protected $description = 'canli_imzalar ve teftis_paylasimlari tablolarını kurar ve canlı veri temizliğini uygular';

    public function handle(): int
    {
        $hatalar = 0;

        if (! Schema::hasTable('canli_imzalar')) {
            $kod = $this->call('migrate', [
                '--path' => 'database/migrations/2026_11_22_140000_create_canli_imzalar_table.php',
                '--force' => true,
            ]);
            if ($kod !== 0 || ! Schema::hasTable('canli_imzalar')) {
                $this->error('canli_imzalar KURULAMADI.');
                $hatalar++;
            } else {
                $this->info('canli_imzalar kuruldu.');
            }
        } else {
            $this->info('canli_imzalar zaten var.');
        }

        if (! Schema::hasTable('teftis_paylasimlari')) {
            $kod = $this->call('migrate', [
                '--path' => 'database/migrations/2026_11_22_150000_create_teftis_paylasimlari_table.php',
                '--force' => true,
            ]);
            if ($kod !== 0 || ! Schema::hasTable('teftis_paylasimlari')) {
                $this->error('teftis_paylasimlari KURULAMADI.');
                $hatalar++;
            } else {
                $this->info('teftis_paylasimlari kuruldu.');
            }
        } else {
            $this->info('teftis_paylasimlari zaten var.');
        }

        $temizKod = $this->call('mehse:veri-temizle', ['--apply' => true]);
        if ($temizKod !== 0) {
            $hatalar++;
        }

        return $hatalar === 0 ? self::SUCCESS : self::FAILURE;
    }
}
