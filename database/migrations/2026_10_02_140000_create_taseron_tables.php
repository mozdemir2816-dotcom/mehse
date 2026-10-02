<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taşeron / Alt İşveren Yönetimi (isgsuite "Taşeron Yönetimi"): işyeri bazında
 * taşeron ve alt işveren firmaları, sözleşme bilgileri, çalışanları (ana
 * personel listesine eklenmez), belge + geçerlilik takibi ve iş izni bağları.
 * Yabancı anahtar yok — canlı DB'de FK kurulamıyor (errno 150); silme
 * temizliği kodda yapılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taseronlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id')->index();
            $table->string('tur')->default('alt_isveren'); // alt_isveren | taseron
            $table->string('unvan');
            $table->string('faaliyet')->nullable();
            $table->string('vergi_no')->nullable();
            $table->string('sgk_sicil_no')->nullable();
            $table->string('tehlike_sinifi')->nullable();
            $table->string('sozlesme_no')->nullable();
            $table->date('sozlesme_baslangic')->nullable();
            $table->date('sozlesme_bitis')->nullable();
            $table->string('yetkili')->nullable();
            $table->string('telefon')->nullable();
            $table->string('eposta')->nullable();
            $table->string('isg_uzmani')->nullable();
            $table->string('isyeri_hekimi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->text('notlar')->nullable();
            $table->timestamps();
        });

        Schema::create('taseron_calisanlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('taseron_id')->index();
            $table->string('ad_soyad');
            $table->string('gorev')->nullable();
            $table->string('tc_maskeli')->nullable();
            $table->date('ise_giris')->nullable();
            $table->date('isg_egitim_tarihi')->nullable();
            $table->date('saglik_raporu_tarihi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('taseron_belgeleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('taseron_id')->index();
            $table->string('tur');
            $table->string('baslik')->nullable();
            $table->date('gecerlilik_sonu')->nullable();
            $table->string('dosya_adi')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->unsignedBigInteger('boyut')->nullable();
            $table->string('notu')->nullable();
            $table->timestamps();
        });

        Schema::create('taseron_is_izinleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('taseron_id')->index();
            $table->unsignedBigInteger('is_izin_formu_id')->index();
            $table->timestamps();

            $table->unique(['taseron_id', 'is_izin_formu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taseron_is_izinleri');
        Schema::dropIfExists('taseron_belgeleri');
        Schema::dropIfExists('taseron_calisanlari');
        Schema::dropIfExists('taseronlar');
    }
};
