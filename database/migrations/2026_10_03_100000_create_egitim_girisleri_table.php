<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan eğitim giriş günlüğü: çalışan portala (kullanıcı kodu / e-posta
 * ile) girdiğinde ve bir eğitimi açtığında satır düşer — "hangi tarihlerde
 * girdi" sorusunun cevabı. Canlıda FK kurulamadığı için anahtarsız.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_girisleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calisan_id')->index();
            $table->unsignedBigInteger('egitim_atamasi_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->timestamp('giris_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_girisleri');
    }
};
