<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patlamadan Korunma Dokümanı (PKD) Sicili — isgsuite "PKD Sicili": işyeri ve
 * bölüm / proses bazında PKD künyesi, zone sınıfları, tutuşturucu kaynaklar,
 * korunma önlemleri, revizyon / gözden geçirme ve doküman dosyası.
 * Yabancı anahtar yok (canlı DB'de FK kurulamıyor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkd_kayitlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id')->index();
            $table->string('dokuman_no');
            $table->string('bolum');
            $table->string('proses')->nullable();
            $table->string('ortam_turu')->default('gaz_buhar_sis');
            $table->string('revizyon_no')->nullable();
            $table->date('dokuman_tarihi')->nullable();
            $table->date('sonraki_gozden_gecirme')->nullable();
            $table->string('durum')->default('taslak');
            $table->text('tehlikeli_maddeler')->nullable();
            $table->json('zonelar')->nullable();
            $table->json('tutusturucular')->nullable();
            $table->json('onlemler')->nullable();
            $table->string('sorumlu')->nullable();
            $table->string('hazirlayan')->nullable();
            $table->string('onaylayan')->nullable();
            $table->text('notlar')->nullable();
            $table->string('dosya_adi')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pkd_kayitlari');
    }
};
