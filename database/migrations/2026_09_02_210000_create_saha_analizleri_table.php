<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Saha Analizi → İSG Saha Gözetim Raporu — isgpratik AI SAHA ANALİZİ/1-6.jpg.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saha_analizleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('belge_no')->nullable();
            $table->string('alan_bolge')->nullable();
            $table->string('gozetim_tarih_araligi')->nullable();
            $table->date('rapor_tarihi')->nullable();

            $table->string('gozetim_yapan')->nullable();
            $table->string('gozetim_yapan_sertifika_no')->nullable();
            $table->string('gozetim_yapan_kase')->nullable();
            $table->string('sorumlu_kisi')->nullable();
            $table->string('isveren_vekili_adi')->nullable();

            $table->text('baglam_notu')->nullable();
            $table->json('bulgular')->nullable();

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saha_analizleri');
    }
};
