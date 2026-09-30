<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kullanıcı ayarları (Ayarlar sayfası): görünüm (tema/yoğunluk/yazı boyutu),
 * uyarı eşikleri (gün) ve Kontrol Merkezi'nde takip dışı bırakılan kriterler.
 * Tek JSON sütun — anahtarlar ve varsayılanlar App\Support\KullaniciAyarlari'da.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('ayarlar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ayarlar');
        });
    }
};
