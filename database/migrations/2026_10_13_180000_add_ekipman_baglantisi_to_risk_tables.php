<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ekipman → risk değerlendirmesi bağlantısı (App\Support\EkipmanKatalogu).
 *  - risk_maddeleri.tehlike_id: maddenin geldiği kütüphane maddesi (FK'siz — canlıda
 *    FK'ler kurulamıyor, bkz. kurul_uyeleri). Var olan madde bir daha önerilmez.
 *  - risk_degerlendirmeleri.haric_tehlikeler: "gerekmiyor" denen kütüphane maddeleri.
 *
 * Canlı (phpMyAdmin):
 *   ALTER TABLE `u2858154_mehse`.`risk_maddeleri` ADD `tehlike_id` BIGINT UNSIGNED NULL AFTER `risk_degerlendirmesi_id`, ADD INDEX `risk_maddeleri_tehlike_id_index` (`tehlike_id`);
 *   ALTER TABLE `u2858154_mehse`.`risk_degerlendirmeleri` ADD `haric_tehlikeler` JSON NULL AFTER `revizyon_nedeni`;
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_maddeleri', function (Blueprint $table) {
            $table->unsignedBigInteger('tehlike_id')->nullable()->after('risk_degerlendirmesi_id')->index();
        });

        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->json('haric_tehlikeler')->nullable()->after('revizyon_nedeni');
        });
    }

    public function down(): void
    {
        Schema::table('risk_maddeleri', function (Blueprint $table) {
            $table->dropIndex(['tehlike_id']);
            $table->dropColumn('tehlike_id');
        });

        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->dropColumn('haric_tehlikeler');
        });
    }
};
