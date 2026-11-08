<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 08.11.2026 — Uzaktan eğitimde çift onay + soru konu dağılımı.
 * - egitim_atamalari: İşyeri hekimi onayı (İGU hekim adına işaretler; hekimin adı ve
 *   sertifika/diploma no onay anında saklanır — sonradan firmanın hekimi değişse de belge değişmez).
 * - egitim_paketi_sorulari: sorunun Ek-1 konu başlığı (1 Genel, 2 Sağlık, 3 Teknik) ve zorluğu;
 *   final sınavı soruları konulara dengeli dağıtılarak çekilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->timestamp('ih_onay_at')->nullable();
            $table->unsignedBigInteger('ih_onaylayan_id')->nullable();
            $table->string('ih_ad_soyad')->nullable();
            $table->string('ih_belge_no')->nullable();
        });

        Schema::table('egitim_paketi_sorulari', function (Blueprint $table) {
            $table->unsignedTinyInteger('modul')->nullable();
            $table->string('zorluk', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->dropColumn(['ih_onay_at', 'ih_onaylayan_id', 'ih_ad_soyad', 'ih_belge_no']);
        });

        Schema::table('egitim_paketi_sorulari', function (Blueprint $table) {
            $table->dropColumn(['modul', 'zorluk']);
        });
    }
};
