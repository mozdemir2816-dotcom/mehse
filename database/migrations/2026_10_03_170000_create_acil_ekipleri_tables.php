<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acil Durum Ekipleri / Destek Elemanları (isgsuite acil_ekipler): firma
 * başına ekipler (söndürme, kurtarma, koruma, ilk yardım, tahliye,
 * haberleşme) ve üyeleri — asıl / yedek, lider, vardiya, eğitim belgesi.
 * İkisi de silinince geri alınabilir (soft delete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acil_ekipleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id')->index();
            $table->string('tur', 20);
            $table->string('ad');
            $table->unsignedInteger('min_uye')->nullable();   // boşsa yasal orandan
            $table->text('notlar')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('acil_ekip_uyeleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('acil_ekip_id')->index();
            $table->unsignedBigInteger('calisan_id')->nullable()->index();
            $table->string('ad_soyad');
            $table->string('gorev')->nullable();
            $table->string('bolum')->nullable();
            $table->string('sicil_no')->nullable();
            $table->string('telefon')->nullable();
            $table->string('uyelik', 10)->default('asil');   // asil | yedek
            $table->boolean('lider')->default(false);
            $table->string('vardiya')->nullable();
            $table->string('belge_no')->nullable();
            $table->date('belge_tarihi')->nullable();
            $table->date('belge_bitis')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acil_ekip_uyeleri');
        Schema::dropIfExists('acil_ekipleri');
    }
};
