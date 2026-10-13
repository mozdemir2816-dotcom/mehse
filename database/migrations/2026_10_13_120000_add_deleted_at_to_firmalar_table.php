<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firmalar için Çöp Kutusu (13.10.2026): silinen firma kalıcı silinmez, 3 ay
 * boyunca geri alınabilir (App\Models\Firma::COP_SURESI_AY).
 *
 * Canlı DB (phpMyAdmin):
 *   ALTER TABLE firmalar ADD deleted_at TIMESTAMP NULL DEFAULT NULL, ADD INDEX firmalar_deleted_at_index (deleted_at);
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->softDeletes()->index();
        });
    }

    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
