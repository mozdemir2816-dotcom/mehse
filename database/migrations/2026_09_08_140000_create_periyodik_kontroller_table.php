<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Ekipmanları Periyodik Kontrol — firma başına bir kayıt; kapasite raporundan
 * belirlenen ekipman listesi ve her ekipman için son kontrol tarihi / sonuç /
 * sonraki kontrol tarihi `ekipmanlar` JSON'unda tutulur (İş Ekipmanları Yön. EK-3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periyodik_kontroller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->json('ekipmanlar')->nullable();
            $table->text('genel_not')->nullable();
            $table->timestamps();

            $table->unique('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periyodik_kontroller');
    }
};
