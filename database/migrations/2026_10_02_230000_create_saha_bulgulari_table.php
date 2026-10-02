<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hızlı Saha Bulgusu (isgsuite Saha Denetimi "Hızlı bulgu") — sahada tek
 * uygunsuzluğun kaydı: bölüm, gözlem konumu + GPS, tehlike kategorisi,
 * uygunsuzluk, mevcut önlemler, 5×5 risk skoru, aksiyon / sorumlu / termin,
 * fotoğraf kanıtı ve kapanış. Yabancı anahtar yok (canlı DB'de kurulamıyor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saha_bulgulari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id')->index();
            $table->string('bulgu_no')->nullable();
            $table->string('kaynak')->default('manuel'); // manuel | ai
            $table->string('bolum')->nullable();
            $table->string('gozlem_konumu')->nullable();
            $table->decimal('enlem', 10, 7)->nullable();
            $table->decimal('boylam', 10, 7)->nullable();
            $table->string('kategori')->nullable();
            $table->string('tehlike')->nullable();
            $table->text('uygunsuzluk');
            $table->text('mevcut_onlemler')->nullable();
            $table->unsignedTinyInteger('olasilik')->default(3);
            $table->unsignedTinyInteger('siddet')->default(3);
            $table->text('aksiyon')->nullable();
            $table->string('sorumlu')->nullable();
            $table->date('termin')->nullable();
            $table->json('fotograflar')->nullable();
            $table->string('durum')->default('acik');
            $table->date('kapanis_tarihi')->nullable();
            $table->text('kapanis_notu')->nullable();
            $table->string('kaydeden')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saha_bulgulari');
    }
};
