<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firmaya atanan İGU / İşyeri Hekimi / DSP — isg_profesyonelleri tablosuna işaret eder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->foreignId('igu_id')->nullable()->after('tehlike_sinifi')
                ->constrained('isg_profesyonelleri')->nullOnDelete();
            $table->foreignId('isyeri_hekimi_id')->nullable()->after('igu_id')
                ->constrained('isg_profesyonelleri')->nullOnDelete();
            $table->foreignId('dsp_id')->nullable()->after('isyeri_hekimi_id')
                ->constrained('isg_profesyonelleri')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropConstrainedForeignId('igu_id');
            $table->dropConstrainedForeignId('isyeri_hekimi_id');
            $table->dropConstrainedForeignId('dsp_id');
        });
    }
};
