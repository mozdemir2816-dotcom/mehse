<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eğitim katılım formuna "çalışan grubu / iş kolu" (ör. Kazı Ekibi, Kaba İnşaat Ekibi): aynı firmada farklı
 * çalışan gruplarına ayrı form, ayrı konu şablonu ve ayrı sertifika (kullanıcı 03.11.2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->string('is_kolu')->nullable()->after('egitim_yeri');
            $table->json('is_kolu_kalemleri')->nullable()->after('is_kolu');
        });
    }

    public function down(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->dropColumn(['is_kolu', 'is_kolu_kalemleri']);
        });
    }
};
