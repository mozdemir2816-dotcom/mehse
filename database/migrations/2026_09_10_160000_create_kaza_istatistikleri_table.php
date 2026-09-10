<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kaza İstatistikleri — firma × yıl. İş Kazası Raporları + Olay Kayıtları
 * (iş kazası tipi) + elle eklenen harici kazalardan ve aylık çalışma
 * verilerinden Sıklık / Ağırlık oranı hesaplanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaza_istatistikleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->unsignedSmallInteger('yil');
            $table->string('standart')->default('turkiye_1m');
            $table->json('aylik_veriler')->nullable();   // 12 ay: {ort_calisan, calisma_saati}
            $table->json('harici_kazalar')->nullable();  // tutanak dışı: {tarih, aciklama, kayip_gunu, olumlu}
            $table->text('not')->nullable();
            $table->timestamps();

            $table->unique(['firma_id', 'yil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaza_istatistikleri');
    }
};
