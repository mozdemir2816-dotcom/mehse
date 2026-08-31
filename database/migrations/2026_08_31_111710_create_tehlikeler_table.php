<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk Kütüphanesi — tehlike maddeleri (kategoriye bağlı, forma aktarılır).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tehlikeler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tehlike_kategorisi_id')->constrained('tehlike_kategorileri')->cascadeOnDelete();
            $table->string('kod')->nullable();
            $table->string('bolum')->nullable();      // önerilen çalışma bölümü
            $table->string('faaliyet')->nullable();
            $table->text('tehlike');
            $table->text('risk')->nullable();         // tehlikeli durum / sonuç
            $table->text('mevcut_onlem')->nullable(); // önerilen kontrol tedbiri
            $table->string('mevzuat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tehlikeler');
    }
};
