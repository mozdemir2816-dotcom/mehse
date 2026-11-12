<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analiz maddesine mevzuat dayanağı (12.11.2026). Kütüphaneden gelen maddelerde kayıtta kopyalanır;
 * PDF/Excel/Word'de "Öneri" hücresinin altında "Mevzuat: …" satırı olarak basılır (ayrı sütun yok).
 *
 * Canlı (phpMyAdmin): deploy/canli-risk-madde-mevzuat-12-11-2026.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_maddeleri', function (Blueprint $table) {
            $table->text('mevzuat')->nullable()->after('oneri');
        });
    }

    public function down(): void
    {
        Schema::table('risk_maddeleri', fn (Blueprint $t) => $t->dropColumn('mevzuat'));
    }
};
