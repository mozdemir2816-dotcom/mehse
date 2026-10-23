<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Kazası Raporu'na iki alan eklendi (24.10.2026 kullanıcı kararı):
 *  - yaralanmalar: vücut bölgesi + yaralanma türü + açıklama listesi (SGK/istatistik).
 *  - kinney_*: kazanın Olasılık/Frekans/Şiddet puanı (Fine-Kinney) — DÖF önceliklendirme
 *    ve "Risk Analizine Ekle" aksiyonunda yeni risk maddesinin başlangıç puanı için.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('is_kazasi_raporlari', function (Blueprint $table) {
            $table->json('yaralanmalar')->nullable()->after('agirlik_derecesi');
            $table->decimal('kinney_olasilik', 5, 1)->nullable()->after('kaza_tanimi');
            $table->decimal('kinney_frekans', 5, 1)->nullable()->after('kinney_olasilik');
            $table->decimal('kinney_siddet', 5, 1)->nullable()->after('kinney_frekans');
        });
    }

    public function down(): void
    {
        Schema::table('is_kazasi_raporlari', function (Blueprint $table) {
            $table->dropColumn(['yaralanmalar', 'kinney_olasilik', 'kinney_frekans', 'kinney_siddet']);
        });
    }
};
