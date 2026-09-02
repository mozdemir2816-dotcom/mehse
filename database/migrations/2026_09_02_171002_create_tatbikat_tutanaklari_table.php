<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tatbikat Tutanağı — isgpratik 61-65.jpg. Senaryo seçimi + görev alan
 * ekipler + değerlendirme kontrol listesi + katılımcılar + DÖF önerileri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tatbikat_tutanaklari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('senaryo_anahtari')->nullable();
            $table->text('senaryo_metni')->nullable();

            $table->date('tatbikat_tarihi')->nullable();
            $table->string('tatbikat_yeri')->nullable();
            $table->string('baslama_saati')->nullable();
            $table->string('bitis_saati')->nullable();
            $table->unsignedSmallInteger('tahliye_dk')->nullable();
            $table->boolean('haberli_tatbikat')->default(true);
            $table->boolean('yillik_plan_dahilinde')->default(true);

            $table->string('isveren_vekili')->nullable();
            $table->string('is_guvenligi_uzmani')->nullable();
            $table->string('tatbikat_koordinatoru')->nullable();
            $table->boolean('isyeri_hekimi_imzasi')->default(false);
            $table->date('belge_tarihi')->nullable();

            $table->json('ekipler')->nullable();         // [{ad_soyad, ekip}]
            $table->json('degerlendirmeler')->nullable(); // [{soru, cevap: evet|hayir|kismen}]
            $table->text('gozlem')->nullable();
            $table->json('eksiklikler')->nullable();       // ["eksiklik metni", ...]
            $table->json('dof_onerileri')->nullable();     // [{faaliyet, sorumlu, tarih}]
            $table->json('katilimcilar')->nullable();      // [{ad_soyad, tc, gorev}]

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tatbikat_tutanaklari');
    }
};
