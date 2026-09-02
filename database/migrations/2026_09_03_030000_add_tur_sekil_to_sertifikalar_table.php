<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * isgpratik referans sertifikasındaki "Eğitim Türü / Şekli" bilgisi
 * (İlk Defa/Tekrar | Yüz Yüze/Uzaktan/Karma) için.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sertifikalar', function (Blueprint $table) {
            $table->string('tur')->default('ilk_defa')->after('tip');
            $table->string('sekil')->default('yuz_yuze')->after('tur');
        });
    }

    public function down(): void
    {
        Schema::table('sertifikalar', function (Blueprint $table) {
            $table->dropColumn(['tur', 'sekil']);
        });
    }
};
