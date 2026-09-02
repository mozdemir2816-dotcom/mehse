<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DÖF Oluştur (Çoklu DÖF) — isgpratik 158.jpg.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dof_raporlari', function (Blueprint $table) {
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

            $table->json('maddeler')->nullable();

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dof_raporlari');
    }
};
