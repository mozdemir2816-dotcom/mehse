<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 02.04.2026 tarihli Çalışanların İSG Eğitimleri Yönetmeliği uyumu:
 * - Md.12/4 giriş-çıkış ve tamamlama verisi: oturumun son etkinliği (çıkış),
 *   fiilen izlenen süre ve aktif katılım (açılır pencere) cevap sayısı.
 * - Md.12/5 ileri sarma engeli: ders başına fiilen oynatılan saniye ve
 *   meşru olarak ulaşılan en ileri konum.
 * - Md.16/1 seviye tespiti (ön test), Md.16/3 üç sınav hakkı → eğitimin
 *   yeniden başlatılması.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_girisleri', function (Blueprint $table) {
            $table->timestamp('cikis_at')->nullable();
            $table->unsignedInteger('izleme_sn')->default(0);
            $table->unsignedSmallInteger('yoklama_sayisi')->default(0);
        });

        Schema::table('egitim_ders_ilerlemeleri', function (Blueprint $table) {
            $table->unsignedInteger('izlenen_sn')->default(0);
            $table->unsignedInteger('son_konum_sn')->default(0);
        });

        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->unsignedTinyInteger('on_test_puani')->nullable();
            $table->timestamp('on_test_at')->nullable();
            $table->unsignedTinyInteger('yeniden_baslatma')->default(0);
            $table->timestamp('yeniden_baslatildi_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('egitim_girisleri', fn (Blueprint $t) => $t->dropColumn(['cikis_at', 'izleme_sn', 'yoklama_sayisi']));
        Schema::table('egitim_ders_ilerlemeleri', fn (Blueprint $t) => $t->dropColumn(['izlenen_sn', 'son_konum_sn']));
        Schema::table('egitim_atamalari', fn (Blueprint $t) => $t->dropColumn(['on_test_puani', 'on_test_at', 'yeniden_baslatma', 'yeniden_baslatildi_at']));
    }
};
