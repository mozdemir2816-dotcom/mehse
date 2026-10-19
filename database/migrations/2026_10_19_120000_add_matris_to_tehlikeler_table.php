<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 5x5 Excel'i kütüphaneye yüklenirken puanlar Fine-Kinney ölçeğine çevrilir (kütüphane hep
 * FK ölçeğinde). Geri çevirme kayıplı olduğu için (5x5 olasılık 1 → FK 0,2 → 5x5 2)
 * kaynak 5x5 değerleri {olasilik, siddet, son_olasilik, son_siddet} olarak ayrıca saklanır.
 *
 * Canlı (shell yok): ALTER TABLE `u2858154_mehse`.`tehlikeler` ADD `matris` JSON NULL;
 * + INSERT INTO `u2858154_mehse`.`migrations` (migration, batch) ...
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->json('matris')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->dropColumn('matris');
        });
    }
};
