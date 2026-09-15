<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firmanın yaptığı işler (ör. İnşaat/Şantiye iş kalemleri anahtarları,
 * config isg.kkd_matris.is_kalemleri). Seçilince risk/talimat/KKD/eğitim
 * içerikleri o iş kalemlerine göre önerilebilir (bkz. IsKalemiEvrakHazirlayici).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->json('is_kalemleri')->nullable()->after('nace_aciklama');
        });
    }

    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropColumn('is_kalemleri');
        });
    }
};
