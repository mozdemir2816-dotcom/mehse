<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meslek Hastalığı Bildirimi — 6331 s.K. m.14, SGK'ya bildirim (4/a: işveren,
 * öğrenilme tarihinden itibaren 3 iş günü içinde). Öğrenilme kaynağı işyeri
 * hekimi, sağlık kuruluşu veya sigortalının kendisi olabilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meslek_hastaligi_bildirimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('calisan_id')->nullable()->constrained('calisanlar')->nullOnDelete();

            $table->string('belge_no')->nullable();
            $table->string('calisan_ad_soyad');
            $table->string('calisan_tc')->nullable();
            $table->string('calisan_gorevi')->nullable();

            $table->string('ogrenme_kaynagi'); // isyeri_hekimi | saglik_kurulusu | sigortali_kendisi
            $table->date('ogrenme_tarihi');
            $table->date('bildirim_son_tarihi')->nullable(); // ogrenme_tarihi + 3 iş günü

            $table->string('tani_hastane')->nullable();
            $table->date('tani_tarihi')->nullable();
            $table->string('saglik_kurulu_rapor_no')->nullable();
            $table->text('meslek_hastaligi_tanisi')->nullable();

            $table->boolean('sgk_bildirimi_yapildi')->default(false);
            $table->date('sgk_bildirim_tarihi')->nullable();
            $table->string('sgk_bildirim_yontemi')->nullable(); // e_sgk | e_devlet

            $table->string('sgk_kurul_onay_durumu')->default('bekliyor'); // bekliyor | onaylandi | reddedildi
            $table->decimal('meslekte_kazanma_gucu_kaybi_yuzde', 5, 2)->nullable();

            $table->text('notlar')->nullable();

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meslek_hastaligi_bildirimleri');
    }
};
