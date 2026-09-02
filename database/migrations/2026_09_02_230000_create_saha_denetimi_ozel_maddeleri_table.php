<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saha Denetimi — kullanıcının kendi eklediği sektöre özel kontrol maddeleri
 * (config'teki 41 maddelik sabit listenin üzerine, "kendi arşivimden ekleme"
 * deseninin bir başka kullanımı — Talimat Oluştur/Risk Kütüphanesi ile aynı).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saha_denetimi_ozel_maddeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('sektor_anahtari')->nullable(); // null = tüm sektörler — config isg.risk_ai.sektorler
            $table->string('kategori_ad');
            $table->string('ifade');
            $table->boolean('kritik')->default(false);
            $table->boolean('uygulanamaz_izni')->default(true);
            $table->unsignedInteger('sira')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'sektor_anahtari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saha_denetimi_ozel_maddeleri');
    }
};
