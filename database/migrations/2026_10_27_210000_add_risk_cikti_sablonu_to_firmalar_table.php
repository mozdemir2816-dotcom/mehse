<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firma bazlı risk çıktı şablonu. 'hastane' seçili firmanın Fine-Kinney risk değerlendirmeleri
 * Bursa Şehir Hastanesi formunda (Excel / PDF) üretilir; boş = standart sistem çıktısı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->string('risk_cikti_sablonu', 30)->nullable()->after('tehlike_sinifi');
        });
    }

    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropColumn('risk_cikti_sablonu');
        });
    }
};
