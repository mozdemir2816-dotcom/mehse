<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete eklendi: bir kullanıcı varsayılan bir prosedürü (ör. Matris)
 * bilerek silerse, VARSAYILANLAR listesine yeni bir yöntem eklendiğinde
 * (ör. HAZOP/FMEA) çalışan geriye dönük tamamlama bu silinmiş kaydı
 * "hiç var olmamış" sanıp yeniden diriltmesin — bkz. RiskProsedur::varsayilanlariSeedEt().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_prosedurleri', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('risk_prosedurleri', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
