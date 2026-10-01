<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Güvenlik sayfası (isgsuite "Güvenlik ve Denetim" karşılaştırması):
 * - Filament uygulama tabanlı iki adımlı doğrulama (TOTP) — sır + kurtarma kodları
 *   (ikisi de şifreli cast),
 * - yasal_onaylar: kullanıcının sürüm bazlı yasal metin onay izi
 *   [{anahtar, baslik, revizyon, onay_at, ip}].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->json('yasal_onaylar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes', 'yasal_onaylar']);
        });
    }
};
