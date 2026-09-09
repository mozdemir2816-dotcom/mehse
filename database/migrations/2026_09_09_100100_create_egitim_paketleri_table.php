<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan Eğitim Paketi — bir eğitim başlığı (ör. "İnşaat Sektörü Uzaktan İSG
 * Eğitimi"): sıralı video dersleri + final sınavı. İSG uzmanı oluşturur,
 * firmadaki çalışanlara atar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_paketleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('ad');
            $table->string('kod')->nullable();               // UE-2026-001
            $table->string('sektor')->nullable();             // config isg.risk_ai.sektorler
            $table->text('aciklama')->nullable();
            $table->unsignedTinyInteger('gecme_puani')->default(70);
            $table->unsignedTinyInteger('video_zorunlu_yuzde')->default(90); // dersi "izlendi" saymak için
            $table->unsignedTinyInteger('sinav_soru_sayisi')->default(20);
            $table->boolean('aktif')->default(true);
            $table->boolean('paylasildi')->default(false);   // diğer uzmanlar da atayabilir
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_paketleri');
    }
};
