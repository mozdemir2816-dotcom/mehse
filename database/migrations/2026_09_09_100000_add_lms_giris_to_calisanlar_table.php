<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan Eğitim portalı — çalışanların e-posta + şifre ile girebilmesi için.
 * Şifre İSG uzmanı tarafından atama sırasında üretilir; çalışan ilk girişte
 * değiştirmeye zorlanabilir (sifre_belirlendi_at null ise "geçici").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calisanlar', function (Blueprint $table) {
            $table->string('sifre')->nullable()->after('eposta');
            $table->timestamp('sifre_belirlendi_at')->nullable()->after('sifre');
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('calisanlar', function (Blueprint $table) {
            $table->dropColumn(['sifre', 'sifre_belirlendi_at', 'remember_token']);
        });
    }
};
