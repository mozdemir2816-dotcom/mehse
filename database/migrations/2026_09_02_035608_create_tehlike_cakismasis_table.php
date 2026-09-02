<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Excel'den Risk Kütüphanesi'ne toplu yüklerken bulunan olası mükerrer
 * (benzer metinli ama birebir aynı olmayan) maddeler — kullanıcı gözden
 * geçirip "mevcudu koru / yenisini kullan / ikisini de tut" seçene kadar
 * burada bekler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tehlike_cakismalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tehlike_kategorisi_id')->constrained('tehlike_kategorileri')->cascadeOnDelete();
            $table->foreignId('mevcut_tehlike_id')->constrained('tehlikeler')->cascadeOnDelete();
            $table->unsignedTinyInteger('benzerlik_yuzdesi');
            $table->json('yeni_veri'); // kod, bolum, faaliyet, tehlike, risk, mevcut_onlem, mevzuat
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tehlike_cakismalari');
    }
};
