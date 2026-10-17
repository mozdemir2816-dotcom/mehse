<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HAZOP çıktısı kaynak Excel'deki sütun düzeniyle basılsın diye (puan yok) senaryonun
 * alanları ayrı ayrı tutulur: parametre, kılavuz kelime, neden, sonuç, korumalar,
 * öneri, öncelik, kapanış kanıtı, durum. Fine-Kinney / 5x5 satırlarında null kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['tehlikeler', 'risk_maddeleri'] as $tablo) {
            Schema::table($tablo, function (Blueprint $table) {
                $table->json('hazop')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['tehlikeler', 'risk_maddeleri'] as $tablo) {
            Schema::table($tablo, function (Blueprint $table) {
                $table->dropColumn('hazop');
            });
        }
    }
};
