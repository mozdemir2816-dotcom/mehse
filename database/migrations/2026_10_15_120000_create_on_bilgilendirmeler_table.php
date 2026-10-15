<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ön Bilgilendirme / Tebliğ: İGU'nun işe başlamadan önce hazırladığı bilgilendirme
 * yazıları (Word/PDF, olduğu gibi saklanır) firmaya tebliğ edilir — elden teslim
 * kaydı ve/veya işveren panelinde "okudum, tebellüğ ettim" onayı. Yabancı anahtar
 * yok — canlı DB'de FK kurulamıyor (errno 150).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('on_bilgilendirmeler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('firma_id')->index();
            $table->string('konu');
            $table->date('bildirim_tarihi')->nullable();
            $table->text('aciklama')->nullable();
            $table->string('dosya_adi');
            $table->string('dosya_yolu');
            $table->unsignedBigInteger('boyut')->default(0);
            $table->timestamp('isyerine_gonderildi_at')->nullable();
            $table->timestamp('isyeri_okundu_at')->nullable();
            $table->string('isyeri_onaylayan')->nullable();
            $table->date('elden_teslim_tarihi')->nullable();
            $table->string('elden_teslim_alan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('on_bilgilendirmeler');
    }
};
