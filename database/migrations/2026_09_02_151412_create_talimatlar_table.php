<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Çalışma Talimatı — isgpratik 82-83.jpg. Hazır şablon kütüphanesinden
 * seçilip firmaya özel kaydedilir; madde metinleri AI (Gemini) ile üretilir
 * veya elle düzenlenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talimatlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('baslik');
            $table->string('kategori')->nullable();
            $table->text('aciklama')->nullable();

            $table->json('kkdler')->nullable();   // ["Baret", ...]
            $table->json('maddeler')->nullable(); // ["1. adım metni", ...]

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talimatlar');
    }
};
