<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eğitim Katılım Formu çıktısına eğitmen (İş Güvenliği Uzmanı / İşyeri Hekimi)
 * kaşe/imza alanı — belge oluşturulduğunda firmaya atanmış İSG Profesyoneli'nden
 * (Firma.igu_id / isyeri_hekimi_id) ad + kaşe görseli anlık kopyalanır (DÖF /
 * İş Kazası Raporu deseni; sonradan atama değişse bile belge sabit kalsın).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->string('isg_uzmani_adi')->nullable()->after('isg_uzmani_var');
            $table->string('isg_uzmani_kase')->nullable()->after('isg_uzmani_adi');
            $table->string('isyeri_hekimi_kase')->nullable()->after('isyeri_hekimi_adi');
        });
    }

    public function down(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->dropColumn(['isg_uzmani_adi', 'isg_uzmani_kase', 'isyeri_hekimi_kase']);
        });
    }
};
