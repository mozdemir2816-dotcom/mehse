<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan eğitim olay günlüğü / zaman çizelgesi (Md.12/4): atama, giriş, hatalı
 * giriş, ön test, oturum, sınav ve ekrandan ayrılma olayları. Canlıda FK
 * kurulamadığı için anahtarsız (egitim_girisleri ile aynı).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('egitim_olaylari')) {
            return;
        }

        Schema::create('egitim_olaylari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('egitim_atamasi_id')->index();
            $table->unsignedBigInteger('calisan_id')->nullable()->index();
            $table->string('tur', 20); // atama | giris | hatali_giris | on_test | oturum | sinav | uyari | tamamlama
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('olay_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_olaylari');
    }
};
