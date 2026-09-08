<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk Kütüphanesi tehlikelerine önerilen Fine-Kinney / 5x5 puanları — sektörel
 * risk analizlerinden (ör. İnşaat Fine-Kinney) içe aktarılan maddeler O/F/Ş
 * değerlerini de taşısın; Risk Sihirbazı manuel seçimde bu değerler forma
 * önceden dolar (bkz. RiskKutuphanesi::maddeyeCevir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->decimal('olasilik', 5, 1)->nullable()->after('mevzuat');
            $table->decimal('frekans', 5, 1)->nullable()->after('olasilik'); // yalnız Fine-Kinney
            $table->decimal('siddet', 5, 1)->nullable()->after('frekans');
        });
    }

    public function down(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->dropColumn(['olasilik', 'frekans', 'siddet']);
        });
    }
};
