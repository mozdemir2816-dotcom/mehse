<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Muayene Formu (EK-2) — İşyeri Hekimi ve Diğer Sağlık Personelinin Görev,
 * Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik EK-2'ye göre.
 * isgpratik'te ekran görüntüsü yok — mevzuattaki standart forma göre kuruldu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muayene_formlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('calisan_id')->nullable()->constrained('calisanlar')->nullOnDelete();

            $table->string('belge_no')->nullable();

            $table->string('calisan_ad_soyad')->nullable();
            $table->string('calisan_tc')->nullable();
            $table->date('calisan_dogum_tarihi')->nullable();
            $table->string('calisan_gorevi')->nullable();
            $table->date('ise_giris_tarihi')->nullable();

            $table->date('muayene_tarihi')->nullable();
            $table->string('muayene_turu')->default('periyodik'); // ise_giris | periyodik | araya_giren | ise_donus

            $table->text('meslek_oykusu')->nullable();
            $table->text('maruz_kalinan_riskler')->nullable();
            $table->text('ozgecmis')->nullable();
            $table->text('soygecmis')->nullable();

            $table->json('sistemik_muayene')->nullable(); // [{baslik, sonuc: normal|anormal, not}]
            $table->json('tetkikler')->nullable(); // [{anahtar, yapildi, sonuc: normal|anormal|null, not}]

            $table->string('sonuc_kanaati')->nullable(); // uygun | sartli_uygun | uygun_degil | is_degisikligi
            $table->text('sart_aciklamasi')->nullable();
            $table->date('onerilen_kontrol_tarihi')->nullable();

            $table->string('hekim_adi')->nullable();
            $table->string('hekim_kase')->nullable();

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muayene_formlari');
    }
};
