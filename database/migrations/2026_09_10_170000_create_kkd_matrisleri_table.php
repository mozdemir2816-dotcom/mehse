<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KKD Seçim Matrisi — firma başına bir kayıt. İş kalemi × KKD türü tablosu
 * (KKD Yönetmeliği + risk değerlendirmesi). Hangi işte hangi KKD'nin
 * gerektiği `satirlar` JSON'unda tutulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kkd_matrisleri', function (Blueprint $table) {
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
        Schema::dropIfExists('kkd_matrisleri');
    }
};
