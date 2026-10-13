<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telefon bildirimleri (Web Push, 13.10.2026): cihaz abonelikleri + bildirimin
 * telefona gönderildiği an (aynı uyarı ikinci kez gönderilmesin).
 *
 * Canlı DB (phpMyAdmin — veritabanı adı nitelikli, yanlış DB'de çalışmasın):
 *   CREATE TABLE u2858154_mehse.push_abonelikleri (
 *     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     user_id BIGINT UNSIGNED NOT NULL,
 *     endpoint TEXT NOT NULL,
 *     endpoint_hash CHAR(64) NOT NULL,
 *     p256dh VARCHAR(255) NOT NULL,
 *     auth VARCHAR(255) NOT NULL,
 *     cihaz VARCHAR(255) NULL,
 *     son_basari_at TIMESTAMP NULL,
 *     hata_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
 *     created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 *     UNIQUE KEY push_abonelikleri_endpoint_hash_unique (endpoint_hash),
 *     KEY push_abonelikleri_user_id_index (user_id)
 *   ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *   ALTER TABLE u2858154_mehse.bildirimler ADD push_at TIMESTAMP NULL DEFAULT NULL;
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_abonelikleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique();   // endpoint TEXT — tekillik sha256 ile
            $table->string('p256dh');
            $table->string('auth');
            $table->string('cihaz')->nullable();           // "Android · Chrome" gibi, listede göstermek için
            $table->timestamp('son_basari_at')->nullable();
            $table->unsignedInteger('hata_sayisi')->default(0);
            $table->timestamps();
        });

        Schema::table('bildirimler', function (Blueprint $table) {
            $table->timestamp('push_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bildirimler', fn (Blueprint $t) => $t->dropColumn('push_at'));
        Schema::dropIfExists('push_abonelikleri');
    }
};
