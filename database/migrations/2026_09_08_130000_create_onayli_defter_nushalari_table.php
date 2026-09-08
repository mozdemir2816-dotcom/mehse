<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onaylı Defter Nüshaları — mehse'de yazılan Tespit ve Öneri Defteri'nin (ya da
 * İşyeri Hekimi Defteri'nin) noter/İSG-KATİP onaylı, imzalanmış nüshalarının
 * taranmış hâli. Firma + defter türü başına sıralı numara (1, 2, 3...) verilir;
 * İSG-KATİP'e yüklenen resmi nüshaların kaydı burada tutulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onayli_defter_nushalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('tespit_oneri_defteri_id')->nullable()->constrained('tespit_oneri_defterleri')->nullOnDelete();

            $table->string('defter_turu')->default('tespit_oneri'); // config isg.onayli_defter.turleri
            $table->unsignedInteger('nusha_no');                    // firma + tür başına sıralı
            $table->date('onay_tarihi')->nullable();                // imza / onay tarihi
            $table->string('donem')->nullable();                    // serbest: "2026 / 1. dönem" vb.

            $table->string('dosya_adi')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->unsignedBigInteger('boyut')->default(0);

            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->unique(['firma_id', 'defter_turu', 'nusha_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onayli_defter_nushalari');
    }
};
