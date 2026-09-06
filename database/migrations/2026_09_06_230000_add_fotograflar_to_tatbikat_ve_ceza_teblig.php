<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tatbikat Tutanağı ve Ceza/Tebliğ Tutanağına fotoğraf kanıtı eklenmesi
 * (kullanıcı isteği) — DÖF'te olduğu gibi her madde için ayrı değil, tüm
 * tutanak için genel bir fotoğraf listesi (json path dizisi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tatbikat_tutanaklari', function (Blueprint $table) {
            $table->json('fotograflar')->nullable()->after('katilimcilar');
        });

        Schema::table('ceza_teblig_tutanaklari', function (Blueprint $table) {
            $table->json('fotograflar')->nullable()->after('ihlaller');
        });
    }

    public function down(): void
    {
        Schema::table('tatbikat_tutanaklari', function (Blueprint $table) {
            $table->dropColumn('fotograflar');
        });

        Schema::table('ceza_teblig_tutanaklari', function (Blueprint $table) {
            $table->dropColumn('fotograflar');
        });
    }
};
