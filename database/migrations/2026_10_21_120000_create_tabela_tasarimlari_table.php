<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela / Güvenlik Levhası Tasarım Atölyesi — firma başına birden çok
 * tasarım (hasMany). Tasarım (renkler, piktogram slotları, metinler, ebat)
 * JSON olarak, kaydedildiği andaki görünümün PNG'si ayrı dosya yolunda tutulur.
 *
 * Canlı (shell yok): CREATE TABLE `u2858154_mehse`.`tabela_tasarimlari` (
 *   `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   `firma_id` BIGINT UNSIGNED NOT NULL,
 *   `baslik` VARCHAR(255) NOT NULL,
 *   `tasarim` JSON NOT NULL,
 *   `gorsel_yolu` VARCHAR(255) NULL,
 *   `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
 *   INDEX (`firma_id`),
 *   CONSTRAINT FOREIGN KEY (`firma_id`) REFERENCES `firmalar`(`id`) ON DELETE CASCADE
 * ) + INSERT INTO `u2858154_mehse`.`migrations` (migration, batch) ...
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabela_tasarimlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('baslik');
            $table->json('tasarim');
            $table->string('gorsel_yolu')->nullable();
            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabela_tasarimlari');
    }
};
