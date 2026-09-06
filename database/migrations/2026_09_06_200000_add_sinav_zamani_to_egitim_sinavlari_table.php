<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_sinavlari', function (Blueprint $table) {
            // 'once' (ön test) veya 'sonra' (son test) — isgpratik'in "eğitim
            // sonrası sınav" varsayımıyla tutarlı, varsayılan 'sonra'.
            $table->string('sinav_zamani')->default('sonra')->after('zorluk');
        });
    }

    public function down(): void
    {
        Schema::table('egitim_sinavlari', function (Blueprint $table) {
            $table->dropColumn('sinav_zamani');
        });
    }
};
