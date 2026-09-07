<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İSG Kurul toplantısına takip numarası (`toplanti_no`) — firma + yıl bazında
 * "2026/1", "2026/2" biçiminde otomatik atanır, elle düzeltilebilir. Kullanıcı
 * "toplantıları takip edebileyim" dediği için eklendi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurul_toplantilari', function (Blueprint $table) {
            $table->string('toplanti_no')->nullable()->after('firma_id');
        });
    }

    public function down(): void
    {
        Schema::table('kurul_toplantilari', function (Blueprint $table) {
            $table->dropColumn('toplanti_no');
        });
    }
};
