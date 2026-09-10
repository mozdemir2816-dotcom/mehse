<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Olay Kaydı'na Balık Kılçığı (Ishikawa) kök neden analizi — 6M kategorisi
 * (insan / makine / yontem / malzeme / cevre / yonetim) başına neden listesi.
 * 5N zincirinden ve kök neden kategorilerinden otomatik doldurulabilir;
 * `balik_kilcigi` JSON'unda tutulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olay_kayitlari', function (Blueprint $table) {
            $table->json('balik_kilcigi')->nullable()->after('kok_neden_kategorileri');
        });
    }

    public function down(): void
    {
        Schema::table('olay_kayitlari', function (Blueprint $table) {
            $table->dropColumn('balik_kilcigi');
        });
    }
};
