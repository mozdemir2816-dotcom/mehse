<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saha Hızlı İşlem → "PTW Kapat": iş izni sahada kapatılırken çekilen kamera
 * kanıtı (isteğe bağlı) — `public` diskteki dosya yolu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('is_izin_formlari', function (Blueprint $table) {
            $table->string('kapanis_fotografi')->nullable()->after('kapatan');
        });
    }

    public function down(): void
    {
        Schema::table('is_izin_formlari', function (Blueprint $table) {
            $table->dropColumn('kapanis_fotografi');
        });
    }
};
