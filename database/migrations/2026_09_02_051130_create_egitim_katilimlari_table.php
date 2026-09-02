<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eğitim Katılım Formu kaydı — isgpratik EĞİTİM ekranları. Her "Kaydet ve PDF
 * İndir" tıklaması yeni bir kayıt oluşturur (aynı firmada tekrarlanan
 * eğitimler ayrı belge olarak izlenir); konu içeriği o anki config'ten
 * anlık görüntü (snapshot) olarak saklanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_katilimlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('belge_no')->nullable();
            $table->string('baslik_anahtari')->default('genel'); // 'genel' veya config isg.egitim.ozel_basliklar anahtarı
            $table->string('sektor_anahtari')->nullable();       // yalnız 'genel' başlıkta: işyerine özgü riskler
            $table->string('egitim_yeri')->nullable();
            $table->date('belge_tarihi')->nullable();
            $table->unsignedInteger('sure_gun')->default(1);

            $table->boolean('isg_uzmani_var')->default(true);
            $table->boolean('isyeri_hekimi_var')->default(false);
            $table->string('isyeri_hekimi_adi')->nullable();

            $table->json('konu_secimleri')->nullable(); // EgitimIcerikOlusturucu çıktısının anlık görüntüsü
            $table->json('katilimcilar')->nullable();   // [{ad_soyad, tc, gorev}]

            $table->timestamps();

            $table->index(['firma_id', 'belge_tarihi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_katilimlari');
    }
};
