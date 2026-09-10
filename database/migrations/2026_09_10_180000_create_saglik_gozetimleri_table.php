<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sağlık Gözetimi Takibi — firma başına bir kayıt. Çalışan × sağlık tetkiki
 * (işe giriş / periyodik muayene, odyometri, SFT, portör, psikoteknik vb.);
 * tetkik tarihi + periyot → sonraki tetkik tarihi otomatik. `satirlar` JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saglik_gozetimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->json('satirlar')->nullable();
            $table->text('genel_not')->nullable();
            $table->timestamps();

            $table->unique('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saglik_gozetimleri');
    }
};
