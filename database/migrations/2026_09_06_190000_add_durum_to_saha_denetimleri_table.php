<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saha_denetimleri', function (Blueprint $table) {
            // 'taslak' | 'tamamlandi' — eski kayıtlar (hepsi PDF alınarak
            // bitirilmiş) varsayılan 'tamamlandi' ile geriye dönük tutarlı kalır.
            $table->string('durum')->default('tamamlandi')->after('revizyon');
        });
    }

    public function down(): void
    {
        Schema::table('saha_denetimleri', function (Blueprint $table) {
            $table->dropColumn('durum');
        });
    }
};
