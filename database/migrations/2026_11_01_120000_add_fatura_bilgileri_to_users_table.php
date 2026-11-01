<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kullanıcının kendi girdiği fatura / kurumsal bilgileri (Fatura & Profil sayfası).
 * fatura_tipi değerleri User::FATURA_TIPLERI (bireysel, kurumsal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fatura_tipi', 10)->nullable();
            $table->string('fatura_unvan')->nullable();
            $table->string('vergi_dairesi', 100)->nullable();
            $table->string('vergi_no', 11)->nullable();
            $table->string('fatura_adresi', 500)->nullable();
            $table->string('fatura_il', 50)->nullable();
            $table->string('fatura_ilce', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fatura_tipi', 'fatura_unvan', 'vergi_dairesi', 'vergi_no', 'fatura_adresi', 'fatura_il', 'fatura_ilce']);
        });
    }
};
