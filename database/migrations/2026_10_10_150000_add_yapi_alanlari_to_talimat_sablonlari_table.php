<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzmanın kendi Word/PDF/Excel talimatlarından yüklenen arşiv şablonları da
 * talimatlarla aynı yapıyı taşır (10.10.2026): doküman no, bölümler, taahhüt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talimat_sablonlari', function (Blueprint $table) {
            $table->string('dokuman_no')->nullable()->after('kategori');
            $table->json('bolumler')->nullable()->after('maddeler');
            $table->text('taahhut')->nullable()->after('bolumler');
        });
    }

    public function down(): void
    {
        Schema::table('talimat_sablonlari', function (Blueprint $table) {
            $table->dropColumn(['dokuman_no', 'bolumler', 'taahhut']);
        });
    }
};
