<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soru Bankası editoryal akışı (isgsuite eisa_question_bank): soru kodu +
 * sürüm, NACE kapsamı (rakam ön ekleri ",41,4321," biçiminde — LIKE ile
 * firma NACE'sinin ön ekleri aranır), birden çok doğrulanabilir kaynak,
 * inceleme notu. Durum akışına 'incelemede' eklenir (string sütun, şema
 * değişmez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('soru_bankasi_sorulari', function (Blueprint $table) {
            $table->string('soru_kodu', 60)->nullable();
            $table->unsignedInteger('surum')->default(1);
            $table->unsignedBigInteger('onceki_surum_id')->nullable();
            $table->string('nace_onekleri')->nullable();
            $table->json('kaynaklar')->nullable();
            $table->text('inceleme_notu')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('soru_bankasi_sorulari', fn (Blueprint $t) => $t->dropColumn(['soru_kodu', 'surum', 'onceki_surum_id', 'nace_onekleri', 'kaynaklar', 'inceleme_notu']));
    }
};
