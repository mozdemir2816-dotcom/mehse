<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Görevlendirme (atama) yazısı — isgpratik 38-44.jpg. Görev tipine göre
 * (`config isg.atama.roller[].tip`) 'tekli' tek üyeli, 'ekip' çok üyeli olur;
 * ikisi de aynı `uyeler` JSON şemasını kullanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atama_yazilari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('rol_anahtari'); // config isg.atama.roller anahtarı
            $table->string('dokuman_no')->nullable();
            $table->date('tarih')->nullable();
            $table->string('isveren_vekili_adi')->nullable();
            $table->date('gorev_baslangic')->nullable(); // yalnız 'tekli' roller
            $table->date('gorev_bitis')->nullable();

            $table->json('uyeler')->nullable(); // [{ad_soyad, tc, gorev, bas_uye}]

            $table->timestamps();

            $table->index(['firma_id', 'rol_anahtari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atama_yazilari');
    }
};
