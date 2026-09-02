<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yıllık Planlar'ın diğer 2 sekmesi — isgpratik 88-90.jpg. "Yıllık Eğitim
 * Planı" (aylık durum matrisli, faaliyetler ile aynı yapı) ve "Yıllık
 * Değerlendirme Raporu" (aylık matris yok, satır bazlı serbest metin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yillik_planlar', function (Blueprint $table) {
            $table->json('egitimler')->nullable()->after('faaliyetler');
            $table->json('degerlendirmeler')->nullable()->after('egitimler');
        });
    }

    public function down(): void
    {
        Schema::table('yillik_planlar', function (Blueprint $table) {
            $table->dropColumn(['egitimler', 'degerlendirmeler']);
        });
    }
};
