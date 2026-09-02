<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş İzin Formu (Permit to Work) — isgpratik 76-78.jpg.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('is_izin_formlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('izin_no')->nullable();
            $table->string('calisma_alani')->nullable();
            $table->text('is_detayi')->nullable();
            $table->dateTime('baslangic')->nullable();
            $table->dateTime('bitis')->nullable();

            $table->json('izin_turleri')->nullable();        // ['sicak_is', ...]
            $table->json('guvenlik_onlemleri')->nullable();   // işaretlenen madde metinleri
            $table->json('gerekli_kkdler')->nullable();       // ['Baret', ...]

            $table->string('onay1_baslik')->nullable(); // Formen/Mühendis vb.
            $table->string('onay1_ad')->nullable();
            $table->string('onay2_baslik')->nullable(); // İSG Uzmanı/Amir vb.
            $table->string('onay2_ad')->nullable();

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('is_izin_formlari');
    }
};
