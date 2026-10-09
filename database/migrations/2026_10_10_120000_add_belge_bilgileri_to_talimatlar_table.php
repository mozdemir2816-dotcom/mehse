<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Talimatlar kullanıcının kendi inşaat talimatlarının yapısına (10.10.2026):
 * her sayfada doküman no / yayın / revizyon künyesi, isteğe bağlı bölümlü
 * içerik (Amaç, Kapsam, KKD, Yasaklar…) ve sondaki taahhüt metni.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talimatlar', function (Blueprint $table) {
            $table->string('dokuman_no')->nullable()->after('kategori');
            $table->date('yayin_tarihi')->nullable()->after('dokuman_no');
            $table->string('revizyon_no')->nullable()->after('yayin_tarihi');
            $table->date('revizyon_tarihi')->nullable()->after('revizyon_no');
            $table->json('bolumler')->nullable()->after('maddeler');   // [{baslik, aciklama, maddeler[]}]
            $table->text('taahhut')->nullable()->after('bolumler');    // boşsa Talimat::varsayilanTaahhut()
        });
    }

    public function down(): void
    {
        Schema::table('talimatlar', function (Blueprint $table) {
            $table->dropColumn(['dokuman_no', 'yayin_tarihi', 'revizyon_no', 'revizyon_tarihi', 'bolumler', 'taahhut']);
        });
    }
};
