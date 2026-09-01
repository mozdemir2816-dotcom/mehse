<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sektörel risk şablonları — bir risk seti sektöre etiketlenip kaydedilir;
 * aynı sektörden yeni firma gelince Risk Sihirbazında tek tıkla uygulanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ad');
            $table->string('sektor')->nullable();      // config isg.risk_ai.sektorler anahtarı
            $table->string('sektor_adi')->nullable();  // sektör listede yoksa serbest metin
            $table->string('yontem')->default('matris_5x5');
            $table->json('maddeler');                  // risk maddesi dizisi (sihirbaz şekli)
            $table->boolean('paylasildi')->default(false);
            $table->unsignedInteger('kullanim_sayisi')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sektor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_sablonlari');
    }
};
