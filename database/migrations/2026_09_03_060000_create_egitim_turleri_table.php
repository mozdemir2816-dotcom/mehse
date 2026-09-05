<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eğitim Türü — kullanıcının Profilim > Eğitimler'de fiilen takip ettiği
 * eğitim konuları listesi. Varsayılan olarak yalnız "Temel İş Sağlığı ve
 * Güvenliği Eğitimi" ile başlar (EgitimTuru::aktifListe); "Konu Ekle"
 * butonuyla config isg.egitim_kayit_turleri katalogundan veya özel isimle
 * yeni satır eklenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_turleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('anahtar');
            $table->string('ad');
            $table->unsignedInteger('gecerlilik_ay')->nullable();
            $table->unsignedInteger('sira')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'anahtar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_turleri');
    }
};
