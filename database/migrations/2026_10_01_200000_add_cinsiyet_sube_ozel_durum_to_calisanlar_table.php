<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personel kaydı — isgsuite karşılaştırmasında eksik kalan alanlar: cinsiyet
 * (kadın/erkek özeti, 6331 md.10 özel politika grupları), şube (serbest metin,
 * firmanın ayrı şube kaydı yok) ve özel durum (engelli / hükümlü vb.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calisanlar', function (Blueprint $table) {
            $table->string('cinsiyet', 10)->nullable()->after('departman');
            $table->string('sube')->nullable()->after('cinsiyet');
            $table->string('ozel_durum')->nullable()->after('sube');
        });
    }

    public function down(): void
    {
        Schema::table('calisanlar', function (Blueprint $table) {
            $table->dropColumn(['cinsiyet', 'sube', 'ozel_durum']);
        });
    }
};
