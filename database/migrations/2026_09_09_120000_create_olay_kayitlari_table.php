<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Olay Kayıtları / Ramak Kala — İş Kazası Raporu'ndan ayrı, TÜM İSG olaylarını
 * (ramak kala, tehlikeli durum/davranış, ilk yardım, maddi hasar, çevre olayı,
 * iş kazası, meslek hastalığı şüphesi) tek deftere kaydeder; her kayıtta
 * sınıflandırma + potansiyel risk (olasılık × şiddet) + 5 Neden (5N) kök neden
 * analizi + düzeltici faaliyet (DÖF'e aktarılabilir) tutulur. 6331 s.K. m.14/1-b.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olay_kayitlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('calisan_id')->nullable()->constrained('calisanlar')->nullOnDelete();
            $table->foreignId('dof_raporu_id')->nullable()->constrained('dof_raporlari')->nullOnDelete();

            $table->string('belge_no')->nullable();
            $table->string('olay_tipi')->nullable();

            $table->date('olay_tarihi')->nullable();
            $table->string('olay_saati')->nullable();
            $table->string('olay_yeri')->nullable();
            $table->string('bildiren_ad_soyad')->nullable();
            $table->date('bildirim_tarihi')->nullable();
            $table->string('etkilenen_ad_soyad')->nullable();
            $table->string('etkilenen_gorev')->nullable();

            $table->text('olay_ozeti')->nullable();

            // Sınıflandırma
            $table->string('sonuc_turu')->nullable();
            $table->json('etkilenen_kategorileri')->nullable();
            $table->string('olasilik')->nullable();
            $table->string('siddet')->nullable();
            $table->unsignedSmallInteger('potansiyel_skor')->nullable();

            // 5N kök neden analizi
            $table->json('bes_neden')->nullable();
            $table->text('kok_neden')->nullable();
            $table->json('kok_neden_kategorileri')->nullable();

            // Düzeltici faaliyet
            $table->text('duzeltici_faaliyet')->nullable();

            // İş kazası ek alanları
            $table->unsignedInteger('kayip_gun_sayisi')->nullable();
            $table->boolean('sgk_bildirimi_yapildi')->default(false);
            $table->date('sgk_bildirim_tarihi')->nullable();
            $table->boolean('kolluk_bildirimi_yapildi')->default(false);

            $table->json('taniklar')->nullable();
            $table->json('fotograflar')->nullable();

            $table->string('rapor_hazirlayan')->nullable();
            $table->string('rapor_hazirlayan_kase')->nullable();

            $table->timestamps();

            $table->index(['firma_id', 'olay_tipi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olay_kayitlari');
    }
};
