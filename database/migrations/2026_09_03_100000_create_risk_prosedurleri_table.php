<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk Analizi Prosedürü — kullanıcının RİSK ANALİZİ klasöründeki gerçek
 * belgelerden alınan 2 yöntem prosedürü (Matris / Fine-Kinney) ile başlar;
 * "Prosedür Yükle" ile kendi .docx'ini yükleyip değiştirebilir veya yeni bir
 * yöntem için ekleyebilir. Risk Değerlendirmesi PDF'inde Kapak'tan sonra,
 * Form'dan önce basılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_prosedurleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('yontem');
            $table->string('ad');
            $table->json('icerik'); // [{tip: 'baslik'|'paragraf', metin: string}]
            $table->string('dosya_adi')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'yontem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_prosedurleri');
    }
};
