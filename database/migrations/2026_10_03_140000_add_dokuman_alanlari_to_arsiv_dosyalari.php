<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doküman Yönetimi (isgsuite "Doküman Yönetimi"): Profilim > Arşiv'in düz
 * dosya listesi kategori, başlık, açıklama, başlangıç / geçerlilik sonu,
 * versiyon ve aktif / pasif durumuyla genişletilir. Eski arşiv kayıtları
 * "Diğer" kategorisinde, aktif ve süresiz kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsiv_dosyalari', function (Blueprint $table) {
            $table->string('kategori')->nullable();
            $table->string('baslik')->nullable();
            $table->text('aciklama')->nullable();
            $table->date('baslangic_tarihi')->nullable();
            $table->date('gecerlilik_sonu')->nullable();
            $table->string('versiyon', 20)->nullable();
            $table->boolean('aktif')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('arsiv_dosyalari', fn (Blueprint $t) => $t->dropColumn(['kategori', 'baslik', 'aciklama', 'baslangic_tarihi', 'gecerlilik_sonu', 'versiyon', 'aktif']));
    }
};
