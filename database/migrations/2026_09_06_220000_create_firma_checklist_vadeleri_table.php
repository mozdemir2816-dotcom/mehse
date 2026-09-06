<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firma Takip — kriter başına elle girilen vade tarihi (isgpratik "Firma
 * Checklist" referansı). Kontrol Merkezi'nin otomatik tamam/eksik mantığına
 * ek, isteğe bağlı bir manuel takip katmanıdır: yalnız kullanıcı bir vade
 * tarihi girdiğinde satır oluşur (sparse tablo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firma_checklist_vadeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('kriter_anahtari');
            $table->date('vade_tarihi')->nullable();
            $table->timestamps();

            $table->unique(['firma_id', 'kriter_anahtari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firma_checklist_vadeleri');
    }
};
