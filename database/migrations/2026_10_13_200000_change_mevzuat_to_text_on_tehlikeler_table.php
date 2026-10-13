<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kütüphane maddesi mevzuat dayanağı 191 karakteri aşabiliyor (TFD-RA-01: İş Ekipmanları +
 * Makina Emniyeti + standart dayanakları kısaltılmadan). varchar(191) → text.
 *
 * Canlı (phpMyAdmin):
 *   ALTER TABLE `u2858154_mehse`.`tehlikeler` MODIFY `mevzuat` TEXT NULL DEFAULT NULL;
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->text('mevzuat')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->string('mevzuat')->nullable()->change();
        });
    }
};
