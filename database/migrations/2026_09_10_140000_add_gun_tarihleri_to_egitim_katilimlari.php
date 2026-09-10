<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 11 saati / 12 ders saatini aşan eğitim 2 güne planlanınca, her günün ayrı
 * eğitim tarihi girilebilsin — form künyesinde "1. Gün / 2. Gün" olarak yazar,
 * katılımcı imzaları zaten gün bazlı sütunlara ayrılıyordu. Tek günlük
 * eğitimlerde null kalır (künyede yalın "Tarih" gösterilir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->json('gun_tarihleri')->nullable()->after('sure_gun');
        });
    }

    public function down(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->dropColumn('gun_tarihleri');
        });
    }
};
