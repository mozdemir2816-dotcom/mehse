<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Ekipmanı — periyodik kontrol/muayene takip künyesi (isgpratik "Ekipman &
 * Periyodik Kontrol Motoru"). Her ekipman ayrı kayıt: kategori + tip + yasal
 * standart + muayene periyodu; son muayene tarihi girilince "sonraki vize"
 * otomatik hesaplanır. İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik
 * Şartları Yönetmeliği EK-III.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('is_ekipmanlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('kategori');                       // config isg.periyodik_kontrol.kategoriler anahtarı
            $table->string('kategori_adi')->nullable();       // özel kategori için serbest ad
            $table->string('tip')->nullable();               // tip adı (katalogdan ya da serbest)
            $table->string('ekipman_adi');
            $table->unsignedSmallInteger('muayene_periyodu_ay')->default(12);
            $table->string('yasal_standart')->nullable();
            $table->string('deney_test')->nullable();
            $table->string('seri_no')->nullable();
            $table->string('marka_model')->nullable();
            $table->string('konum')->nullable();
            $table->string('kapasite')->nullable();
            $table->date('son_muayene_tarihi')->nullable();
            $table->date('sonraki_vize_tarihi')->nullable();
            $table->string('muayene_yapan')->nullable();      // A tipi muayene kuruluşu / yetkili kişi
            $table->string('rapor_no')->nullable();
            $table->string('sonuc')->default('bekliyor');     // bekliyor|uygun|sartli|uygun_degil
            $table->text('ozel_notlar')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['firma_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('is_ekipmanlari');
    }
};
