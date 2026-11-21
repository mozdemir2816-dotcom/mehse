<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Toplu Makine Paketi (21.11.2026) — config'te olmayan makine tipleri. Her satır açılışta
 * config'e eklenir (App\Support\OzelEkipmanTipleri::configeEkle): periyodik kontrol tip
 * listesi, günlük kontrol formu, ekipman kataloğu ve afiş kütüphanesi. Talimat
 * (talimat_sablonlari.ekipman_tipi) ve risk maddeleri (tehlike_kategorileri.ekipman_tipleri)
 * kendi tablolarında durur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ozel_ekipman_tipleri', function (Blueprint $table) {
            $table->id();
            $table->string('ad')->unique();
            $table->string('anahtar', 80)->unique();
            $table->string('kategori', 40);
            $table->unsignedSmallInteger('periyot_ay')->default(12);
            $table->string('standart')->nullable();
            $table->string('deney')->nullable();
            $table->json('kontrol_sorulari')->nullable();
            $table->text('resim_sahnesi')->nullable();
            $table->boolean('yz_taslak')->default(true);
            $table->timestamp('onaylandi_at')->nullable();
            $table->foreignId('onaylayan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ozel_ekipman_tipleri');
    }
};
