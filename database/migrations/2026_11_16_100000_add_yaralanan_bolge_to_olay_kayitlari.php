<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Olay Kaydı'na yaralanan vücut bölgesi (Olay Komuta 360° vücut haritası için).
 * Değer, config('isg.olay.vucut_bolgeleri') anahtarlarından biridir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('olay_kayitlari', 'yaralanan_bolge')) {
            return;
        }

        Schema::table('olay_kayitlari', function (Blueprint $table) {
            $table->string('yaralanan_bolge')->nullable()->after('yaralanma_turu');
        });
    }

    public function down(): void
    {
        Schema::table('olay_kayitlari', function (Blueprint $table) {
            $table->dropColumn('yaralanan_bolge');
        });
    }
};
