<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saha_denetimleri', function (Blueprint $table) {
            $table->string('sektor_anahtari')->nullable()->after('firma_id');
        });
    }

    public function down(): void
    {
        Schema::table('saha_denetimleri', function (Blueprint $table) {
            $table->dropColumn('sektor_anahtari');
        });
    }
};
