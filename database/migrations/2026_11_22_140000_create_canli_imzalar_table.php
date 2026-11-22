<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canli_imzalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('belge_turu', 40)->index();
            $table->unsignedBigInteger('belge_id')->nullable();
            $table->string('belge_baslik', 255);
            $table->string('imza_token', 64)->unique();
            $table->string('calisan_adi', 160)->nullable();
            $table->string('calisan_tc', 11)->nullable();
            $table->string('imza_gorseli', 255)->nullable();
            $table->timestamp('imzalandi_at')->nullable();
            $table->string('dogrulama_kodu', 32)->nullable()->unique();
            $table->string('ip_adresi', 45)->nullable();
            $table->string('cihaz_bilgisi', 255)->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'belge_turu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canli_imzalar');
    }
};
