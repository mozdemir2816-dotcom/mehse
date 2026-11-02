<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzman önerisi: her X m² için 1 söndürücü (BYKHY Md.99/2 asgarisinden sıkı olabilir). Boşsa
 * raporda yalnız yönetmelik asgarisi yazılır (kullanıcı 02.11.2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yangin_guvenligi_degerlendirmeleri', function (Blueprint $table) {
            $table->unsignedSmallInteger('ysc_uzman_m2')->nullable()->after('taban_alani_m2');
        });
    }

    public function down(): void
    {
        Schema::table('yangin_guvenligi_degerlendirmeleri', function (Blueprint $table) {
            $table->dropColumn('ysc_uzman_m2');
        });
    }
};
