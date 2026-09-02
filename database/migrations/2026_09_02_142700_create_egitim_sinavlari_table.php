<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eğitim Soruları — isgpratik 69-70.jpg. Sektör + zorluğa göre AI (Gemini) ile
 * 10 soruluk çoktan seçmeli sınav üretilir; katılımcı listesine PDF verilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_sinavlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('sektor_anahtari')->nullable(); // config isg.risk_ai.sektorler anahtarı, null = Genel
            $table->string('zorluk')->default('karisik');
            $table->boolean('cevap_anahtari_dahil')->default(true); // "Sınavdan Sonra" (cevap anahtarlı) mı

            $table->json('sorular')->nullable();      // [{soru, secenekler: [4], dogru_index}]
            $table->json('katilimcilar')->nullable();  // [{ad_soyad, tc, sinav_tarihi}]

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_sinavlari');
    }
};
