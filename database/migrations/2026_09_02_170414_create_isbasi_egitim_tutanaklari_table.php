<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşbaşı / Oryantasyon Eğitim Tutanağı — isgpratik 60.jpg. İşe yeni başlayan
 * her çalışan için ayrı, tek sayfalık tutanak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isbasi_egitim_tutanaklari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('calisan_ad_soyad');
            $table->string('calisan_tc')->nullable();
            $table->boolean('tc_gizli')->default(false);

            $table->date('egitim_tarihi')->nullable();
            $table->unsignedTinyInteger('sure_saat')->nullable();
            $table->string('egitim_yeri')->nullable();
            $table->string('egitimi_veren')->nullable();
            $table->string('egitim_yontemi')->default('Uygulamalı');
            $table->date('belge_tarihi')->nullable();

            $table->boolean('igu_imzasi')->default(true);
            $table->boolean('isyeri_hekimi_imzasi')->default(false);

            $table->json('konular')->nullable(); // işaretlenen madde metinleri

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isbasi_egitim_tutanaklari');
    }
};
