<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firma (işyeri) — İSG uzmanının portföyündeki her müşteri işyeri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('unvan');
            $table->string('kisa_ad')->nullable();
            $table->string('sgk_sicil_no')->nullable();
            $table->string('vergi_no')->nullable();
            $table->string('katip_no')->nullable();               // İSG-KATİP işyeri no

            $table->string('nace_kodu')->nullable();
            $table->string('nace_aciklama')->nullable();
            $table->string('tehlike_sinifi')->default('az_tehlikeli'); // config isg.tehlike_siniflari

            $table->string('isveren_ad')->nullable();
            $table->string('isveren_vekili')->nullable();
            $table->string('telefon')->nullable();
            $table->string('eposta')->nullable();
            $table->text('adres')->nullable();
            $table->string('il')->nullable();
            $table->string('ilce')->nullable();

            $table->unsignedInteger('calisan_sayisi')->default(0);
            $table->date('sozlesme_baslangic')->nullable();
            $table->date('sozlesme_bitis')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('aktif')->default(true);
            $table->text('notlar')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmalar');
    }
};
