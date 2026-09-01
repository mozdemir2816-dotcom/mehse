<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acil Durum Eylem Planı — firma başına bir kayıt. isgpratik 19-21, 146-154.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acil_durum_planlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->unique()->constrained('firmalar')->cascadeOnDelete();

            $table->string('dokuman_no')->nullable();
            $table->date('rapor_tarihi')->nullable();
            $table->date('gecerlilik_tarihi')->nullable();
            $table->string('kapak_cercevesi')->default('klasik');

            $table->json('konular')->nullable();   // seçili acil durum konu anahtarları
            $table->json('ekipler')->nullable();   // {sondurme:[isim], kurtarma:[], koruma:[], ilk_yardim:[]}

            $table->string('kase_1_gorseli')->nullable();
            $table->string('kase_2_gorseli')->nullable();
            $table->string('cikti_logosu')->nullable();
            $table->string('tahliye_plani_gorseli')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acil_durum_planlari');
    }
};
