<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Herkese açık tanıtım sayfasındaki teklif formundan gelen talepler (üye olmayan ziyaretçiler).
 * Kullanıcıya bağlı değil; yalnız sahip hesap panelde görür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teklif_talepleri', function (Blueprint $table) {
            $table->id();
            $table->string('firma_adi', 150);
            $table->string('kisi', 100);
            $table->string('telefon', 30);
            $table->unsignedInteger('calisan_sayisi');
            $table->string('tehlike_sinifi', 30);
            $table->string('durum', 20)->default('yeni')->index();
            $table->text('not')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('tarayici', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teklif_talepleri');
    }
};
