<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ziyaret Programı — isgpratik'te ekran görüntüsü yok; kullanıcı onayıyla
 * basitleştirilmiş liste (firma+yıl başına 12 aylık satır) olarak kuruldu,
 * isgpratik'teki tam takvim/sürükle-bırak arayüzü kapsam dışı bırakıldı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ziyaret_programlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->unsignedSmallInteger('yil');

            $table->json('ziyaretler')->nullable(); // 12 satır: [{tarih, amac, durum, sure_saat, notlar}]

            $table->timestamps();

            $table->unique(['firma_id', 'yil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ziyaret_programlari');
    }
};
