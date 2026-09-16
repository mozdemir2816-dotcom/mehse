<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kullanıcının kendi hazırladığı Word/PDF dosyasını doğrudan talimat olarak
 * yükleyebilmesi için — OnayliDefterNushasi ile aynı dosya alanı deseni.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talimatlar', function (Blueprint $table) {
            $table->string('dosya_adi')->nullable()->after('maddeler');
            $table->string('dosya_yolu')->nullable()->after('dosya_adi');
            $table->unsignedBigInteger('boyut')->default(0)->after('dosya_yolu');
        });
    }

    public function down(): void
    {
        Schema::table('talimatlar', function (Blueprint $table) {
            $table->dropColumn(['dosya_adi', 'dosya_yolu', 'boyut']);
        });
    }
};
