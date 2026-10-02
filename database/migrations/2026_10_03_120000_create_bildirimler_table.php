<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bildirim Merkezi (isgsuite "Bildirimler"): "Süreleri Kontrol Et" taraması
 * süre / eksiklik uyarılarını `anahtar` ile tekilleştirerek yazar; sorun
 * giderilince kayıt `cozuldu_at` ile kapanır. Canlıda FK kurulamadığı için anahtarsız.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bildirimler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('firma_id')->nullable()->index();
            $table->string('anahtar', 150);
            $table->string('seviye', 20);                 // kritik | uyari | bilgi
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            $table->date('tarih')->nullable();            // ilgili son tarih / termin
            $table->string('url', 500)->nullable();
            $table->timestamp('okundu_at')->nullable();
            $table->timestamp('cozuldu_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'anahtar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bildirimler');
    }
};
