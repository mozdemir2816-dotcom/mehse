<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İSG Ceza ve Tebliğ Tutanağı — isgpratik 79-80.jpg. Çalışana uygulanan
 * disiplin yaptırımını ve tebliğ/tebellüğ durumunu belgeler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceza_teblig_tutanaklari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('tutanak_no')->nullable();
            $table->date('tutanak_tarihi')->nullable();

            $table->string('calisan_ad_soyad')->nullable();
            $table->string('calisan_tc')->nullable();
            $table->string('calisan_gorev')->nullable();
            $table->string('calisan_bolum')->nullable();
            $table->date('ise_giris_tarihi')->nullable();
            $table->string('istihdam_sekli')->default('kadrolu'); // kadrolu | alt_isveren | gecici_gorevli

            $table->date('olay_tarihi')->nullable();
            $table->string('olay_saati')->nullable();
            $table->string('olay_yeri')->nullable();
            $table->text('olay_aciklamasi')->nullable();

            $table->json('taniklar')->nullable();  // [{ad_soyad, gorev}]
            $table->json('ihlaller')->nullable();  // [{madde, dayanak}]

            $table->string('yaptirim')->nullable(); // sozlu_uyari | yazili_ihtar | ucret_kesme | yazili_savunma

            $table->date('teblig_tarihi')->nullable();
            $table->string('imza_durumu')->nullable(); // imzaladi | imtina_etti

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceza_teblig_tutanaklari');
    }
};
