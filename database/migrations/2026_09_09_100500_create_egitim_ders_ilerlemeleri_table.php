<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bir atamadaki tek dersin izlenme durumu — çalışanın videoyu ne kadar izlediği
 * (yüzde) ve "izlendi" bayrağı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_ders_ilerlemeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egitim_atamasi_id')->constrained('egitim_atamalari')->cascadeOnDelete();
            $table->foreignId('egitim_dersi_id')->constrained('egitim_dersleri')->cascadeOnDelete();

            $table->unsignedTinyInteger('izleme_yuzdesi')->default(0);
            $table->boolean('izlendi')->default(false);
            $table->timestamp('izlendi_at')->nullable();
            $table->timestamps();

            $table->unique(['egitim_atamasi_id', 'egitim_dersi_id'], 'ders_ilerleme_tekil');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_ders_ilerlemeleri');
    }
};
