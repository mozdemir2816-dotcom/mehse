<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('acil_durum_planlari', function (Blueprint $table) {
            $table->string('revizyon_no')->nullable()->after('gecerlilik_tarihi');
            $table->string('toplanma_yeri')->nullable()->after('kapak_cercevesi');
            $table->text('disaridan_etkileyebilecek_isyerleri')->nullable()->after('toplanma_yeri');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acil_durum_planlari', function (Blueprint $table) {
            $table->dropColumn(['revizyon_no', 'toplanma_yeri', 'disaridan_etkileyebilecek_isyerleri']);
        });
    }
};
