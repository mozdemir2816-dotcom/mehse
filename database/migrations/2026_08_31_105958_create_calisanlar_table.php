<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Çalışan (personel) — bir firmaya bağlı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calisanlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('ad_soyad');
            $table->string('tc', 11)->nullable();
            $table->string('gorev')->nullable();
            $table->string('departman')->nullable();
            $table->date('ise_giris')->nullable();
            $table->date('isten_cikis')->nullable();
            $table->date('dogum_tarihi')->nullable();
            $table->string('kan_grubu')->nullable();
            $table->string('telefon')->nullable();
            $table->string('eposta')->nullable();
            $table->boolean('agir_tehlikeli_iste')->default(false);
            $table->boolean('aktif')->default(true);
            $table->text('notlar')->nullable();

            $table->timestamps();

            $table->index(['firma_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calisanlar');
    }
};
