<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * JSA (İşe Özgü Risk Değerlendirmesi) kütüphanesi — kullanıcı, "İş Güvenliği
 * Analizi (JSA)" formatındaki Excel dosyalarını (bkz. örnek DUVAR ÖRME) yükleyip
 * bir kütüphanede biriktirir; ilerde bir firmaya lazım olduğunda oradan çekip
 * PDF/Word çıktısı alır. Şablon BÖLÜNMEZ — başlık + tüm iş adımları + notlar +
 * imza bloğu tek kayıt olarak saklanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsa_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('baslik');
            $table->string('dokuman_ref')->nullable();
            $table->string('revizyon')->nullable();
            $table->string('belge_tarihi')->nullable();
            $table->text('kapsam')->nullable();
            $table->json('adimlar');
            $table->json('notlar')->nullable();
            $table->json('imza_rolleri')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsa_sablonlari');
    }
};
