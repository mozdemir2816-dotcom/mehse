<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kapaktaki "İşyeri / proje adı" — aynı firmanın farklı şantiye / işyerleri
 * için ayrı değerlendirme yapılabildiğinden firma kaydına değil değerlendirmeye
 * bağlıdır (örn. "X Şantiyesi ve Kampı").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->string('proje_adi')->nullable()->after('firma_adres');
        });
    }

    public function down(): void
    {
        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->dropColumn('proje_adi');
        });
    }
};
