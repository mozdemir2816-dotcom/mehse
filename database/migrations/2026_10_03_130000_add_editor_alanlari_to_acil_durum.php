<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acil durum kroki editörü (ISGCEO / isgsuite referansı) + plan uygulama
 * bilgileri:
 * - acil_durum_krokileri.ogeler  : odalar, kaçış yolları, metinler
 * - acil_durum_krokileri.antet   : antet / lejant ayarları (sürüm, başlık, kat…)
 * - acil_durum_krokileri.gorsel_yolu : editörün kaydettiği PNG (PDF bunu basar)
 * - acil_durum_planlari.uygulama : senaryolar, tedbirler, müdahale yöntemi,
 *   iletişim listesi, yayın / onay / tatbikat doğrulaması (isgsuite plan sihirbazı)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acil_durum_krokileri', function (Blueprint $table) {
            $table->json('ogeler')->nullable();
            $table->json('antet')->nullable();
            $table->string('gorsel_yolu')->nullable();
        });

        Schema::table('acil_durum_planlari', function (Blueprint $table) {
            $table->json('uygulama')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('acil_durum_krokileri', fn (Blueprint $t) => $t->dropColumn(['ogeler', 'antet', 'gorsel_yolu']));
        Schema::table('acil_durum_planlari', fn (Blueprint $t) => $t->dropColumn('uygulama'));
    }
};
