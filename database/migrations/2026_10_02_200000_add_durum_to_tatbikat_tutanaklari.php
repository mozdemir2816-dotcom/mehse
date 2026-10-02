<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tatbikat Tutanağı → Tatbikat Yönetimi (isgsuite): kayıt durumu (planlandı /
 * yapıldı / takip gerekiyor / iptal), toplanma alanı ve katılımcı sayısı.
 * Mevcut kayıtlar tutanak olarak üretildiği için "yapildi" sayılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tatbikat_tutanaklari', function (Blueprint $table) {
            $table->string('durum')->default('yapildi')->after('senaryo_metni');
            $table->string('toplanma_alani')->nullable()->after('tatbikat_yeri');
            $table->unsignedInteger('katilimci_sayisi')->nullable()->after('tahliye_dk');
        });
    }

    public function down(): void
    {
        Schema::table('tatbikat_tutanaklari', function (Blueprint $table) {
            $table->dropColumn(['durum', 'toplanma_alani', 'katilimci_sayisi']);
        });
    }
};
