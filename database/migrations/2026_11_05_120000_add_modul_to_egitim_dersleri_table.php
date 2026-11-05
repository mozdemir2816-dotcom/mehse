<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_dersleri', function (Blueprint $table) {
            // Yönetmelik Ek-1 ana konu grubu: 1 Genel, 2 Sağlık, 3 Teknik, 4 İşyerine özgü (null = eski, modülsüz ders).
            $table->unsignedTinyInteger('modul')->nullable()->after('egitim_paketi_id');
        });
    }

    public function down(): void
    {
        Schema::table('egitim_dersleri', function (Blueprint $table) {
            $table->dropColumn('modul');
        });
    }
};
