<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşyeri (işveren) girişi — firma başına tek, kalıcı e-posta + şifre
 * (isgsuite "İşyeri Kiosk Giriş Bilgileri" karşılığı). İşveren /isyeri
 * panelinde yalnız kendi firmasının evraklarını görür ve indirir.
 * sifre: giriş için hash; sifre_acik: uzmanın tekrar gösterebilmesi için
 * şifreli (Crypt) saklanır. firma_id bilinçli olarak FK'siz (canlıda FK
 * errno 150 sorunu) — firma silinince Firma::deleting hesabı siler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isyeri_hesaplari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id')->unique();
            $table->string('eposta')->unique();
            $table->string('sifre');
            $table->text('sifre_acik')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamp('son_giris_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isyeri_hesaplari');
    }
};
