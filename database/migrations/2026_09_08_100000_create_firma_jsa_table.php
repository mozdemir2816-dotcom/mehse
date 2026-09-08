<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JSA kütüphanesindeki bir analizin (örn. "Kazı İşleri") bir veya birden çok
 * firmaya atanması. Atanan JSA, firmanın "Evrakları İndir" toplu ZIP'inde ve
 * Profilim > Raporlar listesinde görünür (bkz. config isg.raporlar.kaynaklar).
 * Pivot + ilk sınıf kayıt: created_at Raporlar sıralamasında kullanılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firma_jsa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('jsa_sablonu_id')->constrained('jsa_sablonlari')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['firma_id', 'jsa_sablonu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firma_jsa');
    }
};
