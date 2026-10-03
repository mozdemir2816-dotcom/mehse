<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saha Gözlem Raporu (isgsuite referansı, 03.10.2026): taslak → tamamlandı
 * yaşam döngüsü, kalıcı fotoğraf havuzu, mevzuat referansları (QR) ve
 * tamamlanınca açılan DÖF bağlantısı. FK yok — canlıda FK'ler errno 150 veriyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saha_analizleri', function (Blueprint $table) {
            $table->string('durum', 20)->default('taslak')->after('belge_no');
            $table->timestamp('tamamlanma_tarihi')->nullable()->after('durum');
            $table->json('fotograflar')->nullable()->after('bulgular');
            $table->json('mevzuat_referanslari')->nullable()->after('fotograflar');
            $table->unsignedBigInteger('dof_raporu_id')->nullable()->after('mevzuat_referanslari');
        });

        // Bu özellikten önce kaydedilenler "Rapor Oluştur" ile kesinleşmiş raporlardı.
        DB::table('saha_analizleri')->update(['durum' => 'tamamlandi']);
    }

    public function down(): void
    {
        Schema::table('saha_analizleri', function (Blueprint $table) {
            $table->dropColumn(['durum', 'tamamlanma_tarihi', 'fotograflar', 'mevzuat_referanslari', 'dof_raporu_id']);
        });
    }
};
