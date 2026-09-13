<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->string('isveren_kase_gorseli')->nullable()->after('isveren_vekili');
            $table->string('isveren_imza_gorseli')->nullable()->after('isveren_kase_gorseli');
        });
    }

    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropColumn(['isveren_kase_gorseli', 'isveren_imza_gorseli']);
        });
    }
};
