<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bir atamanın final sınav denemesi — puan, geçti/kaldı ve verilen cevaplar.
 * Çalışan geçene kadar tekrar deneyebilir (deneme_no artar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_sinav_sonuclari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egitim_atamasi_id')->constrained('egitim_atamalari')->cascadeOnDelete();

            $table->unsignedInteger('deneme_no')->default(1);
            $table->unsignedTinyInteger('puan');
            $table->boolean('gecti')->default(false);
            $table->json('cevaplar'); // [{soru, secenekler, dogru_index, verilen_index}]
            $table->timestamp('tamamlandi_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_sinav_sonuclari');
    }
};
