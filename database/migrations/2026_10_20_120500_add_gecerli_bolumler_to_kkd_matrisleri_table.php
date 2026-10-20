<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firma her inşaat faaliyet bölümünü yapmıyor olabilir (örn. sadece kazı,
 * veya sadece dış cephe/iskele/sıva/boya) — KkdSecimMatrisi'ndeki standart
 * referans panelini bu firma için geçerli faaliyet bölümleriyle sınırlamak
 * için. Null/boş = filtre yok, tüm bölümler gösterilir (mevcut kayıtlar
 * için davranış değişmez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kkd_matrisleri', function (Blueprint $table) {
            $table->json('gecerli_bolumler')->nullable()->after('satirlar');
        });
    }

    public function down(): void
    {
        Schema::table('kkd_matrisleri', function (Blueprint $table) {
            $table->dropColumn('gecerli_bolumler');
        });
    }
};
