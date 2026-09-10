<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşe Dönüş Belgesi — uzun süreli rapor / iş kazası / meslek hastalığı sonrası
 * çalışanın işe dönüşünde işyeri hekiminin uygunluk değerlendirmesi ve varsa
 * geçici iş kısıtlamaları (6331 s.K. Md.15, İşyeri Hekimi Yönetmeliği).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ise_donus_belgeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('calisan_id')->nullable()->constrained('calisanlar')->nullOnDelete();
            $table->foreignId('is_kazasi_raporu_id')->nullable()->constrained('is_kazasi_raporlari')->nullOnDelete();
            $table->string('belge_no')->nullable();
            $table->string('calisan_ad_soyad');
            $table->string('calisan_tc')->nullable();
            $table->string('gorev')->nullable();
            $table->string('neden')->default('hastalik');   // is_kazasi | meslek_hastaligi | hastalik | ameliyat | diger
            $table->date('devamsizlik_baslangic')->nullable();
            $table->date('devamsizlik_bitis')->nullable();
            $table->date('ise_donus_tarihi')->nullable();
            $table->string('uygunluk')->default('tam');      // tam | kisitli | uygun_degil
            $table->json('kisitlamalar')->nullable();        // seçili kısıtlama etiketleri
            $table->text('hekim_gorusu')->nullable();
            $table->date('kontrol_muayene_tarihi')->nullable();
            $table->string('hekim_adi')->nullable();
            $table->string('hekim_kase')->nullable();
            $table->date('belge_tarihi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ise_donus_belgeleri');
    }
};
