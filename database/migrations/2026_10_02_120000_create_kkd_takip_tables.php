<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KKD Takip (isgsuite "KKD Takip"): personel bazlı kalıcı KKD zimmet sicili +
 * firma bazlı KKD stok kartları ve stok hareketleri (giriş / zimmet çıkışı /
 * iade / fire). Zimmet tutanağı üreten KKD Zimmet Formu'ndan (kkd_zimmet_formlari)
 * ayrıdır: burada her teslim tek satırdır ve yenileme / SKT takibi yapılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kkd_stok_kartlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('kategori')->nullable();
            $table->string('tur');
            $table->string('marka')->nullable();
            $table->string('model')->nullable();
            $table->string('beden')->nullable();
            $table->integer('mevcut')->default(0);
            $table->unsignedInteger('asgari')->default(0);
            $table->string('raf_omru')->nullable();
            $table->date('son_kullanma')->nullable();
            $table->date('yenileme_tarihi')->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->index('firma_id');
        });

        Schema::create('kkd_zimmetleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('calisan_id')->nullable()->constrained('calisanlar')->nullOnDelete();
            $table->foreignId('kkd_stok_karti_id')->nullable()->constrained('kkd_stok_kartlari')->nullOnDelete();
            $table->string('zimmet_no')->nullable();
            $table->string('personel_ad_soyad')->nullable();
            $table->string('bolum')->nullable();
            $table->date('teslim_tarihi')->nullable();
            $table->unsignedInteger('adet')->default(1);
            $table->string('kategori')->nullable();
            $table->string('tur');
            $table->string('marka')->nullable();
            $table->string('model')->nullable();
            $table->string('beden')->nullable();
            $table->string('seri_no')->nullable();
            $table->string('raf_omru')->nullable();
            $table->string('garanti')->nullable();
            $table->date('son_kullanma')->nullable();
            $table->date('yenileme_tarihi')->nullable();
            $table->string('durum')->default('teslim_edildi');
            $table->date('iade_tarihi')->nullable();
            $table->string('teslim_eden')->nullable();
            $table->text('risk_alani')->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'durum']);
        });

        Schema::create('kkd_stok_hareketleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kkd_stok_karti_id')->constrained('kkd_stok_kartlari')->cascadeOnDelete();
            $table->foreignId('kkd_zimmet_id')->nullable()->constrained('kkd_zimmetleri')->nullOnDelete();
            $table->string('tip'); // giris | zimmet | iade | fire
            $table->integer('miktar');
            $table->date('tarih')->nullable();
            $table->string('aciklama')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kkd_stok_hareketleri');
        Schema::dropIfExists('kkd_zimmetleri');
        Schema::dropIfExists('kkd_stok_kartlari');
    }
};
