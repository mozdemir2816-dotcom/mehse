<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İSG Kurulu toplantısı — isgpratik yardım/kurul-toplantisi rehberi.
 * Katılımcı/gündem/karar listeleri JSON tutulur (codebase'deki diğer
 * "liste of item" alanlarıyla aynı desen — bkz. AcilDurumPlani.konular).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurul_toplantilari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->date('tarih')->nullable();
            $table->string('saat')->nullable();
            $table->string('yer')->nullable();
            $table->string('baskan')->nullable();

            $table->json('katilimcilar')->nullable(); // [{ad_soyad, gorev, katildi}]
            $table->json('gundem')->nullable();        // ["madde metni", ...]
            $table->json('kararlar')->nullable();       // [{gundem_maddesi, karar_metni, sorumlu, termin, durum}]

            $table->timestamps();

            $table->index(['firma_id', 'tarih']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurul_toplantilari');
    }
};
