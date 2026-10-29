<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hastane risk şablonu (Bursa Şehir Hastanesi formu). `cikti_sablonu = 'hastane'` olan
 * değerlendirmeler Excel/PDF çıktısında hastanenin kendi formatını korur; puanlar
 * standart Fine-Kinney ölçeğinde (Ş 40 = ölüm) olduğu gibi saklanır.
 * `sablon_verisi`: kaynak dosya (resources/hastane-sablonlari) ve sayfa adı.
 * `etki_alani`: formdaki "ETKİ ALANI" sütunu (serbest metin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->string('cikti_sablonu', 30)->nullable()->after('yontem');
            $table->json('sablon_verisi')->nullable()->after('cikti_sablonu');
        });

        Schema::table('risk_maddeleri', function (Blueprint $table) {
            $table->text('etki_alani')->nullable()->after('risk');
        });
    }

    public function down(): void
    {
        Schema::table('risk_maddeleri', function (Blueprint $table) {
            $table->dropColumn('etki_alani');
        });

        Schema::table('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->dropColumn(['cikti_sablonu', 'sablon_verisi']);
        });
    }
};
