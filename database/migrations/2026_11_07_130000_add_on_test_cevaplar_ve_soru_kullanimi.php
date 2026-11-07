<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - egitim_atamalari.on_test_cevaplar: ön testin (seviye tespit) soru bazlı cevapları.
 * - egitim_paketi_sorulari.kullanim: soru ön testte mi, final sınavında mı, ikisinde de mi sorulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->json('on_test_cevaplar')->nullable()->after('on_test_at');
        });

        Schema::table('egitim_paketi_sorulari', function (Blueprint $table) {
            $table->string('kullanim', 10)->default('ortak')->after('aciklama');
        });
    }

    public function down(): void
    {
        Schema::table('egitim_atamalari', fn (Blueprint $table) => $table->dropColumn('on_test_cevaplar'));
        Schema::table('egitim_paketi_sorulari', fn (Blueprint $table) => $table->dropColumn('kullanim'));
    }
};
