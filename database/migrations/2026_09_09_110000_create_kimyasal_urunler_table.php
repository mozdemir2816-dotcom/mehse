<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kimyasal Ürün Sicili — işyerindeki tehlikeli kimyasalların envanteri; her ürün
 * için Malzeme Güvenlik Bilgi Formu (SDS/MSDS) dosyası, GHS/CLP tehlike sınıfları
 * ve gözden geçirme tarihi (Kimyasal Maddelerle Çalışmalarda İSG Yönetmeliği).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kimyasal_urunler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('urun_adi');
            $table->string('cas_no')->nullable();
            $table->string('tedarikci')->nullable();
            $table->string('fiziksel_hal')->nullable();       // sivi | kati | gaz | aerosol
            $table->string('kullanim_alani')->nullable();
            $table->string('miktar')->nullable();             // "200 L / ay" serbest
            $table->text('depolama')->nullable();
            $table->json('ghs')->nullable();                  // config isg.kimyasal.ghs anahtarları

            $table->string('sds_dosya_adi')->nullable();
            $table->string('sds_dosya_yolu')->nullable();
            $table->date('sds_tarihi')->nullable();           // SDS düzenlenme / revizyon tarihi
            $table->date('sonraki_gozden_gecirme')->nullable();

            $table->text('aciklama')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['firma_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kimyasal_urunler');
    }
};
