<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eğitim Kaydı — Profilim > Eğitimler (isgpratik 139-140.jpg). Çalışan başına
 * eğitim türü (config isg.egitim_kayit_turleri) × tamamlanma tarihi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_kayitlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calisan_id')->constrained('calisanlar')->cascadeOnDelete();

            $table->string('tur');
            $table->date('tarih');
            $table->text('notlar')->nullable();

            $table->timestamps();

            $table->unique(['calisan_id', 'tur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_kayitlari');
    }
};
