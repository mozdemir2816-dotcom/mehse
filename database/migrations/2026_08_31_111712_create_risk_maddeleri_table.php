<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk maddesi — bir risk değerlendirmesindeki tek satır.
 * Mevcut O/(F)/Ş → puan/düzey; önlem sonrası rezidüel O/(F)/Ş → son puan/düzey.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_maddeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_degerlendirmesi_id')->constrained('risk_degerlendirmeleri')->cascadeOnDelete();
            $table->unsignedInteger('sira')->default(0);

            $table->string('bolum')->nullable();
            $table->string('faaliyet')->nullable();
            $table->text('tehlike');
            $table->text('risk')->nullable();
            $table->text('mevcut_onlem')->nullable();
            $table->boolean('etkilenen_calisan')->default(true);
            $table->boolean('etkilenen_diger')->default(false); // taşeron / ziyaretçi

            // Mevcut skor
            $table->decimal('olasilik', 5, 1)->nullable();
            $table->decimal('frekans', 5, 1)->nullable();   // yalnız Fine-Kinney
            $table->decimal('siddet', 5, 1)->nullable();
            $table->decimal('puan', 8, 1)->nullable();
            $table->string('duzey')->nullable();

            // Planlanan önlem + rezidüel skor
            $table->text('oneri')->nullable();
            $table->string('sorumlu')->nullable();
            $table->string('termin')->nullable();            // tarih ya da "Sürekli"
            $table->decimal('son_olasilik', 5, 1)->nullable();
            $table->decimal('son_frekans', 5, 1)->nullable();
            $table->decimal('son_siddet', 5, 1)->nullable();
            $table->decimal('son_puan', 8, 1)->nullable();
            $table->string('son_duzey')->nullable();

            $table->string('durum')->default('acik');        // config isg.risk_madde_durumlari
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->index('risk_degerlendirmesi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_maddeleri');
    }
};
