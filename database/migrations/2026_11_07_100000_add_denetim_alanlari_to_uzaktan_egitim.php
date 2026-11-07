<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan eğitim denetim kanıtı (07.11.2026): çalışanın KVKK aydınlatma + eğitim taahhüdü onayı,
 * giriş günlüğünde cihaz/tarayıcı bilgisi, belgedeki QR'ın doğrulama kodu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calisanlar', function (Blueprint $table) {
            $table->timestamp('kvkk_onay_at')->nullable();
            $table->string('kvkk_onay_ip', 45)->nullable();
        });

        Schema::table('egitim_girisleri', function (Blueprint $table) {
            $table->string('cihaz', 255)->nullable()->after('ip');
        });

        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->string('dogrulama_kodu', 32)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('egitim_atamalari', fn (Blueprint $t) => $t->dropColumn('dogrulama_kodu'));
        Schema::table('egitim_girisleri', fn (Blueprint $t) => $t->dropColumn('cihaz'));
        Schema::table('calisanlar', fn (Blueprint $t) => $t->dropColumn(['kvkk_onay_at', 'kvkk_onay_ip']));
    }
};
