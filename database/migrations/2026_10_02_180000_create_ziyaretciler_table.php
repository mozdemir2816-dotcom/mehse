<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ziyaretçi Yönetimi (isgsuite "Ziyaretçi Yönetimi") — işyerine gelen dış
 * kişilere süreli, QR'lı geçiş kartı; İSG bilgilendirmesi, verilen KKD,
 * giriş / çıkış zamanı. `token` QR doğrulama adresinin anahtarıdır.
 * Yabancı anahtar yok (canlı DB'de FK kurulamıyor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ziyaretciler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id')->index();
            $table->string('kart_no')->nullable();
            $table->string('token', 64)->unique();
            $table->string('ad_soyad');
            $table->string('kurum')->nullable();
            $table->string('telefon')->nullable();
            $table->string('ziyaret_amaci')->nullable();
            $table->string('ziyaret_edilen')->nullable();
            $table->dateTime('gecerlilik_baslangic');
            $table->dateTime('gecerlilik_bitis');
            $table->dateTime('giris_zamani')->nullable();
            $table->dateTime('cikis_zamani')->nullable();
            $table->boolean('isg_bilgilendirme')->default(false);
            $table->string('verilen_kkd')->nullable();
            $table->boolean('iptal')->default(false);
            $table->text('notlar')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'gecerlilik_baslangic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ziyaretciler');
    }
};
