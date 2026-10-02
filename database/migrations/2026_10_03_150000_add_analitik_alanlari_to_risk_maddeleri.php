<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tehlike ve Risk Analitiği (isgsuite): risk maddesine tehlike türü
 * (fiziksel / kimyasal / biyolojik / ergonomik / psikososyal) ve maruz kalan
 * kişi sayısı. Tür boşsa analitik, tehlike metninden anahtar kelimeyle tahmin eder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_maddeleri', function (Blueprint $table) {
            $table->string('tehlike_turu', 20)->nullable();
            $table->unsignedInteger('maruz_kisi')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('risk_maddeleri', fn (Blueprint $t) => $t->dropColumn(['tehlike_turu', 'maruz_kisi']));
    }
};
