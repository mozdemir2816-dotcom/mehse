<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Periyodik kontrol ekipman listesi artık ayrı `is_ekipmanlari` tablosunda
 * (Ekipman & Periyodik Kontrol Motoru). `periyodik_kontroller` yalnız genel not
 * + belge kaydı kapsayıcısı olarak kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periyodik_kontroller', function (Blueprint $table) {
            if (Schema::hasColumn('periyodik_kontroller', 'ekipmanlar')) {
                $table->dropColumn('ekipmanlar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('periyodik_kontroller', function (Blueprint $table) {
            $table->json('ekipmanlar')->nullable();
        });
    }
};
