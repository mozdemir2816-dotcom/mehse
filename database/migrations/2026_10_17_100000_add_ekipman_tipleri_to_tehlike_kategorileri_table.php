<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kütüphane kategorisi ↔ ekipman tipi bağı (isg.periyodik_kontrol.tipler 'ad').
 * config/ekipman_katalogu.php'deki bağlara ek olarak panelden yüklenen ekipman
 * risk analizleri burada bağlanır (EkipmanKatalogu::tanim birleştirir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tehlike_kategorileri', function (Blueprint $table) {
            $table->json('ekipman_tipleri')->nullable()->after('anahtar');
        });
    }

    public function down(): void
    {
        Schema::table('tehlike_kategorileri', function (Blueprint $table) {
            $table->dropColumn('ekipman_tipleri');
        });
    }
};
