<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşe özgü eğitim konuları kütüphanesi — uzmanın kendi kaydettiği konular.
 * Sistem konuları (VİZYON şablonu, iş kalemleri, NACE grupları) koddadır;
 * `sistem_anahtari` dolu kayıt o sistem konusunu düzenlenmiş haliyle
 * geçersiz kılar, `gizli` ise listeden kaldırır. Yıllık eğitim planının 4.
 * bölümü firmanın NACE kodu / iş kalemleriyle eşleşen konulardan dolar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ise_ozgu_egitim_konulari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('sistem_anahtari', 80)->nullable();
            $table->string('ad');
            $table->text('hedef')->nullable();
            $table->string('egitici')->nullable();
            $table->string('nace_onekleri')->nullable();   // ",41,4321,"
            $table->json('is_kalemleri')->nullable();
            $table->boolean('gizli')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ise_ozgu_egitim_konulari');
    }
};
