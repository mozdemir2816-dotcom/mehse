<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kütüphane maddesine hedef (önlem sonrası) puanlar. Ekipman / şantiye Fine-Kinney
 * dosyalarındaki "Hedef P/F/S" — değerlendirmeye eklenince "Son" puanlar bunlardan
 * gelir (yoksa model varsayımı: olasılık 1, frekans ve şiddet aynı). Değerler
 * MakineRiskKutuphanesiSeeder'dan yazılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->decimal('son_olasilik', 5, 1)->nullable()->after('siddet');
            $table->decimal('son_frekans', 5, 1)->nullable()->after('son_olasilik');
            $table->decimal('son_siddet', 5, 1)->nullable()->after('son_frekans');
        });
    }

    public function down(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->dropColumn(['son_olasilik', 'son_frekans', 'son_siddet']);
        });
    }
};
