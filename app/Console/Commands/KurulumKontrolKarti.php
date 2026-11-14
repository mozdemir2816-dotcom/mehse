<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Canlıda Kontrol Kartı tablosunu kurar (14.11.2026). Canlıda SSH yok ve
 * bazı tablolar phpMyAdmin'den elle kurulduğu için genel `migrate` güvenli
 * değil; bu komut YALNIZ kontrol_kartlari migration'ını çalıştırır.
 * Tablo varsa bir şey yapmaz. Çağrı: node deploy/onbellek.mjs --komut "kurulum:kontrol-karti"
 */
class KurulumKontrolKarti extends Command
{
    protected $signature = 'kurulum:kontrol-karti';

    protected $description = 'Kontrol Kartı tablosunu (kontrol_kartlari) yoksa kurar';

    public function handle(): int
    {
        if (Schema::hasTable('kontrol_kartlari') && Schema::hasTable('kontrol_karti_sablonlari')) {
            $this->info('kontrol_kartlari zaten var — değişiklik yok.');

            return self::SUCCESS;
        }

        $kod = $this->call('migrate', [
            '--path' => 'database/migrations/2026_11_14_100000_create_kontrol_kartlari_table.php',
            '--force' => true,
        ]);

        $this->info(Schema::hasTable('kontrol_kartlari') ? 'kontrol_kartlari kuruldu.' : 'kontrol_kartlari KURULAMADI.');

        return $kod === 0 && Schema::hasTable('kontrol_kartlari') ? self::SUCCESS : self::FAILURE;
    }
}
