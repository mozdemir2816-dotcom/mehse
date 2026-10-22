<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('isg_katip_sozlesmeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('firma_id')->nullable()->constrained('firmalar')->nullOnDelete();

            $table->string('katip_sozlesme_id');
            $table->string('sozlesme_adi')->nullable();
            $table->boolean('oto_guncelleme_izni')->nullable();
            $table->boolean('mevzuata_uygun_mu')->nullable();

            $table->string('kisi_tc')->nullable();
            $table->string('kisi_ad')->nullable();
            $table->string('sertifika_tipi')->nullable();
            $table->string('sertifika_no')->nullable();

            $table->string('sozlesme_tipi')->nullable();
            $table->string('calisma_suresi')->nullable();

            $table->string('hizmet_veren_unvan')->nullable();
            $table->string('hizmet_veren_sgk_no')->nullable();
            $table->string('hizmet_veren_ili')->nullable();
            $table->string('hizmet_veren_yetki_no')->nullable();

            $table->string('hizmet_alan_unvan')->nullable();
            $table->string('hizmet_alan_sgk_no')->nullable();
            $table->string('hizmet_alan_ili')->nullable();
            $table->unsignedInteger('hizmet_alan_calisan_sayisi')->nullable();
            $table->string('hizmet_alan_tehlike_sinifi')->nullable(); // config isg.tehlike_siniflari

            $table->date('baslangic_tarihi')->nullable();
            $table->date('bitis_tarihi')->nullable();

            $table->string('statu')->nullable();
            $table->string('onay_durumu')->nullable();
            $table->date('onay_tarihi')->nullable();
            $table->string('onaylayan_ad')->nullable();
            $table->string('sonlandiran_ad')->nullable();
            $table->string('sonlandirma_nedeni')->nullable();

            $table->json('ham_veri')->nullable(); // export'taki geri kalan sütunlar (plaka, tetkikler, ölçümler…)
            $table->timestamp('son_iceaktarim_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'katip_sozlesme_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isg_katip_sozlesmeleri');
    }
};
