<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Kazası Raporu — 6331 s.K. ve standart kaza inceleme raporu formatı
 * (5N1K + kök neden analizi). isgpratik'te ilgili ekran görüntüsü yok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('is_kazasi_raporlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('calisan_id')->nullable()->constrained('calisanlar')->nullOnDelete();

            $table->string('belge_no')->nullable();

            $table->string('kazazede_ad_soyad')->nullable();
            $table->string('kazazede_tc')->nullable();
            $table->string('kazazede_gorev')->nullable();

            $table->date('kaza_tarihi')->nullable();
            $table->string('kaza_saati')->nullable();
            $table->string('kaza_yeri')->nullable();
            $table->string('kaza_turu')->nullable();
            $table->string('agirlik_derecesi')->nullable();
            $table->unsignedInteger('kayip_gun_sayisi')->nullable();

            $table->text('kaza_tanimi')->nullable();
            $table->text('kaza_nasil_oldu')->nullable();
            $table->json('kok_neden_kategorileri')->nullable();
            $table->text('kaza_nedeni')->nullable();
            $table->text('alinan_onlemler')->nullable();

            $table->json('taniklar')->nullable();

            $table->boolean('sgk_bildirimi_yapildi')->default(false);
            $table->date('sgk_bildirim_tarihi')->nullable();

            $table->string('rapor_hazirlayan')->nullable();
            $table->string('rapor_hazirlayan_kase')->nullable();

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('is_kazasi_raporlari');
    }
};
