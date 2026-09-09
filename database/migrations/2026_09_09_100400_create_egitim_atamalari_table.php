<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bir çalışana atanmış uzaktan eğitim paketi. İlerleme (izlenen dersler) ve
 * sınav sonuçları buna bağlı. Tamamlanınca "Uzaktan Eğitim Katılım Belgesi" üretilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_atamalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egitim_paketi_id')->constrained('egitim_paketleri')->cascadeOnDelete();
            $table->foreignId('calisan_id')->constrained('calisanlar')->cascadeOnDelete();
            $table->foreignId('atayan_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('atandi_at');
            $table->date('son_tarih')->nullable();
            $table->string('durum')->default('atandi'); // atandi | devam | sinav | tamamlandi | basarisiz
            $table->timestamp('tamamlandi_at')->nullable();
            $table->string('egitim_turu')->default('yenileme'); // ilk_defa | yenileme | isbasi
            $table->timestamps();

            $table->unique(['egitim_paketi_id', 'calisan_id']);
            $table->index(['calisan_id', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_atamalari');
    }
};
