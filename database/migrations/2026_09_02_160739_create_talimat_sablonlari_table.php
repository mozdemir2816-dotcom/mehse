<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kullanıcının kendi arşivinden Excel ile toplu yüklediği talimat şablonları
 * — `config isg.talimat.sablonlar`daki 30 hazır şablonun yanında, Talimat
 * Oluştur kütüphanesinde ayrıca listelenir. Uzman bazlı (kendi arşivi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talimat_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('baslik');
            $table->string('kategori')->nullable();
            $table->text('aciklama')->nullable();

            $table->json('kkdler')->nullable();   // ["Baret", ...]
            $table->json('maddeler')->nullable(); // ["1. adım metni", ...]

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talimat_sablonlari');
    }
};
