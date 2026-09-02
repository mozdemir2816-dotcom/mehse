<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yıllık Çalışma Planı — isgpratik 86-87.jpg. Firma + yıl başına TEK kayıt;
 * faaliyetler ve her birinin 12 aylık durumu JSON tutulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yillik_planlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->unsignedSmallInteger('yil');
            $table->unsignedTinyInteger('baslangic_ayi')->default(1);

            $table->json('faaliyetler')->nullable(); // [{faaliyet, sorumlu, aciklama, aylar: [12 x bos|planlandi|tamamlandi]}]

            $table->timestamps();

            $table->unique(['firma_id', 'yil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yillik_planlar');
    }
};
