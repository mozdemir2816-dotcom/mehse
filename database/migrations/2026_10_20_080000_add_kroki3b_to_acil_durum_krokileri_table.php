<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 3B (izometrik) Acil Durum Kroki Editörü verisi — {S: öğeler, G: ızgara, I: işletme/plan bilgisi}.
 * Klasik (2B) editörün duvarlar/semboller/ogeler sütunlarına dokunulmaz; iki editör yan yana çalışır.
 *
 * Canlı (shell yok): ALTER TABLE `u2858154_mehse`.`acil_durum_krokileri` ADD `kroki3b` JSON NULL;
 * + INSERT INTO `u2858154_mehse`.`migrations` (migration, batch) ...
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acil_durum_krokileri', function (Blueprint $table) {
            $table->json('kroki3b')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('acil_durum_krokileri', function (Blueprint $table) {
            $table->dropColumn('kroki3b');
        });
    }
};
