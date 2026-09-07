<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Kazası Raporuna fotoğraf kanıtı eklenmesi (kullanıcı isteği) — olay yeri /
 * kaza sonrası durumun genel fotoğrafları; Tatbikat ve Ceza/Tebliğ Tutanağı ile
 * aynı desen (tutanak geneli json path dizisi, madde bazlı değil).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('is_kazasi_raporlari', function (Blueprint $table) {
            $table->json('fotograflar')->nullable()->after('taniklar');
        });
    }

    public function down(): void
    {
        Schema::table('is_kazasi_raporlari', function (Blueprint $table) {
            $table->dropColumn('fotograflar');
        });
    }
};
