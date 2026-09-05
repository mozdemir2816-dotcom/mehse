<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arşiv Dosyası — Profilim > Arşiv (isgpratik 144.jpg). Firma başına düz
 * dosya listesi (klasör yok) — App\Models\ArsivDosya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsiv_dosyalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('dosya_adi');
            $table->string('dosya_yolu');
            $table->unsignedBigInteger('boyut')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsiv_dosyalari');
    }
};
