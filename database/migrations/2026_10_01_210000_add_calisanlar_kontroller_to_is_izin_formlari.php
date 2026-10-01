<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş İzin Formu — isgsuite "Çalışma İzinleri" karşılaştırmasında eksik kalanlar:
 * izinde çalışacak personel (firma çalışanlarından, ad/görev anlık kopyası),
 * taşeron, bağlı saha denetimi ve PTW operasyon kontrolleri (LOTO, gaz ölçümü,
 * KKD, ekipman, acil durum, yetkinlik — her biri bekliyor/uygun/uygun değil/gerekli değil).
 * saha_denetimi_id bilinçli olarak FK'siz (canlıda FK errno 150 sorunu yaşandı).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('is_izin_formlari', function (Blueprint $table) {
            $table->json('calisanlar')->nullable()->after('is_detayi');
            $table->string('taseron')->nullable()->after('calisanlar');
            $table->unsignedBigInteger('saha_denetimi_id')->nullable()->after('taseron');
            $table->json('kontroller')->nullable()->after('gerekli_kkdler');
        });
    }

    public function down(): void
    {
        Schema::table('is_izin_formlari', function (Blueprint $table) {
            $table->dropColumn(['calisanlar', 'taseron', 'saha_denetimi_id', 'kontroller']);
        });
    }
};
