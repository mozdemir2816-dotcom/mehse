<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk değerlendirmesi (rapor) — firma başına birden çok / revizyonlu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_degerlendirmeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('yontem')->default('matris_5x5'); // config isg.risk_yontemleri
            $table->string('belge_no')->nullable();
            $table->string('revizyon_no')->default('00');
            $table->date('rapor_tarihi')->nullable();
            $table->date('gecerlilik_tarihi')->nullable();

            // Firma künye snapshot (rapor basıldığında dondurulur)
            $table->string('firma_unvan')->nullable();
            $table->string('firma_sgk_sicil_no')->nullable();
            $table->string('firma_nace')->nullable();
            $table->string('tehlike_sinifi')->nullable();
            $table->string('firma_adres')->nullable();

            $table->json('ekip')->nullable();          // [{ad, unvan}] risk değerlendirme ekibi
            $table->string('durum')->default('taslak'); // taslak | yayinlandi
            $table->text('kapsam_notu')->nullable();
            $table->text('revizyon_nedeni')->nullable();

            $table->timestamps();
            $table->index(['firma_id', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_degerlendirmeleri');
    }
};
