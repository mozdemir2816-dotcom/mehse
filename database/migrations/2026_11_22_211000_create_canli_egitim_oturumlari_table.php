<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canli_egitim_oturumlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('baslik', 255);
            $table->string('kategori', 40)->default('temel_isg'); // temel_isg | yuksekte_8s | isyeri_hijyeni_4s | gida_hijyeni_4s
            $table->unsignedSmallInteger('sure_saat')->default(4);
            $table->timestamp('oturum_tarihi');
            $table->string('toplanti_url', 500)->nullable();
            $table->string('katilim_token', 64)->unique();
            $table->unsignedTinyInteger('gecme_notu')->default(70);
            $table->json('sorular')->nullable();
            $table->json('sonuclar')->nullable(); // [{ad_soyad, tc, puan, gecti, kod, tarih}]
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['firma_id', 'oturum_tarihi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canli_egitim_oturumlari');
    }
};
