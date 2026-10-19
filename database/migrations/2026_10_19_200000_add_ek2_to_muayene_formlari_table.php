<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EK-2 İşe Giriş / Periyodik Muayene Formu referans şablonunun yeni alanları (işyeri iletişimi,
 * çalışan bilgileri, önceki işyerleri, aşılar, soygeçmiş, özgeçmiş soruları, alışkanlıklar,
 * vital bulgular, fizik muayene, laboratuvar grupları, kanaat seçenekleri, hekim kimliği) tek
 * JSON sütunda tutulur — bkz. App\Models\MuayeneFormu::ek2Sablon().
 *
 * Canlı (shell yok): ALTER TABLE `u2858154_mehse`.`muayene_formlari` ADD `ek2` JSON NULL;
 * + INSERT INTO `u2858154_mehse`.`migrations` (migration, batch) ...
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muayene_formlari', function (Blueprint $table) {
            $table->json('ek2')->nullable()->after('hekim_kase');
        });
    }

    public function down(): void
    {
        Schema::table('muayene_formlari', function (Blueprint $table) {
            $table->dropColumn('ek2');
        });
    }
};
