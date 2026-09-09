<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş İzin Şablonu — uzmanın kendi izin kütüphanesi. config'teki hazır izin
 * kataloğunun yanında İş İzin Formu ekranında "Kütüphaneden Uygula" ile seçilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('is_izin_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('ad');
            $table->text('aciklama')->nullable();
            $table->json('turler')->nullable();
            $table->json('ek_onlemler')->nullable();
            $table->json('kkdler')->nullable();
            $table->unsignedSmallInteger('gecerlilik_saat')->nullable();
            $table->json('uyarilar')->nullable();
            $table->text('ozel_kosullar')->nullable();
            $table->string('onay1_baslik')->nullable();
            $table->string('onay2_baslik')->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('is_izin_sablonlari');
    }
};
