<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kütüphane kategorisine yöntem (fine_kinney / matris_5x5 / hazop) ve sabit NACE
 * grupları (config/risk_nace.php gruplar anahtarları, "ortak" = her grupta) eklenir.
 * Toplu ekleme yalnızca firmanın yöntemi ve NACE grubuna uyan kategorileri listeler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tehlike_kategorileri', function (Blueprint $table) {
            $table->string('yontem', 20)->nullable()->after('ekipman_tipleri')->index();
            $table->json('nace_gruplari')->nullable()->after('yontem');
        });
    }

    public function down(): void
    {
        Schema::table('tehlike_kategorileri', function (Blueprint $table) {
            $table->dropIndex(['yontem']);
            $table->dropColumn(['yontem', 'nace_gruplari']);
        });
    }
};
