<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Kazası Raporu → İş Kazası İnceleme ve Kök Neden Analiz Raporu (isgpratik
 * 6 adımlı sihirbaz): 5 Neden (sabit sorular) + Balık Kılçığı (6M) + DÖF tablosu
 * + kritik notlar + taslak/tamamlandı durumu + işyeri hekimi kaşesi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('is_kazasi_raporlari', function (Blueprint $table) {
            $table->string('kazazede_kidem')->nullable()->after('kazazede_gorev');
            $table->json('bes_neden')->nullable()->after('kok_neden_kategorileri');
            $table->json('balik_kilcigi')->nullable()->after('bes_neden');
            $table->json('dof_maddeleri')->nullable()->after('balik_kilcigi');
            $table->text('kritik_notlar')->nullable()->after('alinan_onlemler');
            $table->string('durum')->default('taslak')->after('belge_no');
            $table->boolean('isyeri_hekimi_dahil')->default(true)->after('rapor_hazirlayan_kase');
            $table->string('isyeri_hekimi_adi')->nullable()->after('isyeri_hekimi_dahil');
            $table->string('isyeri_hekimi_kase')->nullable()->after('isyeri_hekimi_adi');
        });
    }

    public function down(): void
    {
        Schema::table('is_kazasi_raporlari', function (Blueprint $table) {
            $table->dropColumn([
                'kazazede_kidem', 'bes_neden', 'balik_kilcigi', 'dof_maddeleri', 'kritik_notlar',
                'durum', 'isyeri_hekimi_dahil', 'isyeri_hekimi_adi', 'isyeri_hekimi_kase',
            ]);
        });
    }
};
