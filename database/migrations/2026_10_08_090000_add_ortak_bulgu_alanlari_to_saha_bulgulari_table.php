<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saha kontrolleri yeniden yapılandırması ("tek bulgu, çok çıktı") 1. aşama:
 * saha_bulgulari ortak bulgu kaydı olur. Saha Gözlem Raporu (yasal gerekçe,
 * hedef risk), DÖF (öncelik, kök neden, düzeltici/önleyici) ve Tespit-Öneri
 * (dayanak) maddelerinin alanları eklenir; kaynak_* eski kayıttan taşınan
 * bulgunun izini tutar. Yalnız eklenen, boş bırakılabilir sütunlar — mevcut
 * veriye dokunmaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saha_bulgulari', function (Blueprint $table) {
            $table->text('yasal_gerekce')->nullable()->after('mevcut_onlemler');
            $table->string('oncelik', 20)->nullable()->after('siddet');      // boşsa 5×5 skordan
            $table->unsignedTinyInteger('hedef_olasilik')->nullable()->after('oncelik');
            $table->unsignedTinyInteger('hedef_siddet')->nullable()->after('hedef_olasilik');
            $table->text('kok_neden')->nullable()->after('aksiyon');
            $table->text('duzeltici')->nullable()->after('kok_neden');
            $table->text('onleyici')->nullable()->after('duzeltici');
            $table->string('kaynak_tablo', 40)->nullable()->after('kaynak');
            $table->unsignedBigInteger('kaynak_kayit_id')->nullable()->after('kaynak_tablo');
            $table->unsignedSmallInteger('kaynak_sira')->nullable()->after('kaynak_kayit_id');

            $table->index(['kaynak_tablo', 'kaynak_kayit_id']);
        });
    }

    public function down(): void
    {
        Schema::table('saha_bulgulari', function (Blueprint $table) {
            $table->dropIndex(['kaynak_tablo', 'kaynak_kayit_id']);
            $table->dropColumn([
                'yasal_gerekce', 'oncelik', 'hedef_olasilik', 'hedef_siddet',
                'kok_neden', 'duzeltici', 'onleyici',
                'kaynak_tablo', 'kaynak_kayit_id', 'kaynak_sira',
            ]);
        });
    }
};
