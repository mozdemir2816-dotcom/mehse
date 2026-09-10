<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eğitim Katılım Formuna eğitim şekli (yüz yüze / uzaktan / karma) alanı —
 * eğitim türü (ilk defa / tekrar) zaten `egitim_turu` sütununda. İkisi de
 * PDF formda işaretlenebilir kutu olarak gösterilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->string('egitim_sekli')->default('yuz_yuze')->after('egitim_turu');
        });
    }

    public function down(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->dropColumn('egitim_sekli');
        });
    }
};
