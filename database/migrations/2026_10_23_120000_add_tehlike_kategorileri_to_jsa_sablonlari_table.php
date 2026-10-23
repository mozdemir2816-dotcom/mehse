<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JSA şablonunun hangi Risk Kütüphanesi kategorilerini (tehlike_kategorileri.anahtar)
 * kapsadığı — Risk Sihirbazı'nda o kategoriden madde eklenince eşleşen JSA'yı önermek için
 * (23.10.2026 kullanıcı kararı: "iş kalemine göre işe özgü risk değerlendirmesi uyarısı").
 * Null/boş = eşleşme yok, hiçbir öneri tetiklenmez (mevcut kayıtlar için davranış değişmez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jsa_sablonlari', function (Blueprint $table) {
            $table->json('tehlike_kategorileri')->nullable()->after('adimlar');
        });
    }

    public function down(): void
    {
        Schema::table('jsa_sablonlari', function (Blueprint $table) {
            $table->dropColumn('tehlike_kategorileri');
        });
    }
};
