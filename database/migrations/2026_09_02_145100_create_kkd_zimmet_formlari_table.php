<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KKD Zimmet Formu — isgpratik 71-75.jpg. Seçilen çalışanlara seçilen KKD
 * setinin teslim edildiğini belgeleyen tutanak; her kayıt bir teslim olayı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kkd_zimmet_formlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('form_no')->nullable();
            $table->date('teslim_tarihi')->nullable();
            $table->date('periyodik_kontrol_tarihi')->nullable();
            $table->string('teslim_eden')->nullable();

            $table->json('calisanlar')->nullable(); // [{ad_soyad, tc, departman}]
            $table->json('kkdler')->nullable();      // [{ad, standart, kategori}]

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kkd_zimmet_formlari');
    }
};
