<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calisan_temsilcisi_secimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('dokuman_no')->nullable();
            $table->date('ilan_tarihi')->nullable();
            $table->date('aday_basvuru_son_tarihi')->nullable();
            $table->unsignedInteger('isyeri_calisan_sayisi')->nullable();
            $table->unsignedInteger('zorunlu_temsilci_sayisi')->nullable();
            $table->date('secim_tarihi')->nullable();
            $table->string('secim_saati')->nullable();
            $table->string('secim_yeri')->nullable();
            $table->json('adaylar')->nullable();
            $table->unsignedInteger('secilen_aday_index')->nullable();
            $table->date('gorevlendirme_tarihi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calisan_temsilcisi_secimleri');
    }
};
