<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tatbikat: planlanan (hedef) tahliye süresi — gerçekleşen tahliye süresiyle kıyaslanır (02.11.2026). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tatbikat_tutanaklari', function (Blueprint $table) {
            $table->unsignedSmallInteger('hedef_tahliye_dk')->nullable()->after('tahliye_dk');
        });
    }

    public function down(): void
    {
        Schema::table('tatbikat_tutanaklari', function (Blueprint $table) {
            $table->dropColumn('hedef_tahliye_dk');
        });
    }
};
