<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kontrol Kartı (14.11.2026) — bir alanın (ör. basınçlı tüp depolama) tek fotoğraf
 * üzerinde numaralı işaretli kontrol listesi; uygunsuz maddelerde Fine-Kinney
 * önce/sonra. Maddeler (durum, işaret konumu, risk) JSON tutulur, şablon
 * config/kontrol_kartlari.php; Gemini'nin yeni alanlar için hazırladığı listeler
 * kontrol_karti_sablonlari tablosuna eklenir.
 *
 * Canlı: `node deploy/onbellek.mjs --komut "kurulum:kontrol-karti"` (yalnız bu dosyayı çalıştırır).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontrol_kartlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('belge_no', 30)->nullable()->index();
            $table->date('belge_tarihi')->nullable();
            $table->string('sablon', 60);
            $table->string('alan_adi')->nullable();
            $table->string('konum')->nullable();
            $table->date('kontrol_tarihi')->nullable();
            $table->string('kontrol_eden')->nullable();
            $table->string('foto_yolu')->nullable();
            $table->json('maddeler')->nullable();
            $table->timestamps();
        });

        // Gemini'nin yeni alan/ekipman için hazırladığı kontrol listeleri (config'tekilere ek).
        Schema::create('kontrol_karti_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anahtar', 80)->unique();
            $table->string('ad');
            $table->string('aciklama')->nullable();
            $table->json('maddeler');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontrol_karti_sablonlari');
        Schema::dropIfExists('kontrol_kartlari');
    }
};
