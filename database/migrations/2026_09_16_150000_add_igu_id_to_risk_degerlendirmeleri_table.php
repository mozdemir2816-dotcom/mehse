<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bu risk değerlendirmesini hazırlayan İGU — firmanın atanmış İGU'sundan
 * (Firma.igu_id) farklı olabilir, o yüzden değerlendirme başına ayrı saklanır.
 * Boş bırakılırsa RiskDegerlendirmesi::booted() firmanın atanmış İGU'sunu
 * otomatik doldurur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->foreignId('igu_id')->nullable()->after('firma_id')
                ->constrained('isg_profesyonelleri')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->dropConstrainedForeignId('igu_id');
        });
    }
};
