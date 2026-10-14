<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kütüphane maddesinin kaynak Excel'deki Sorumlu / Termin önerisi; risk maddesine aktarılırken kopyalanır.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->string('sorumlu')->nullable()->after('oneri');
            $table->string('termin')->nullable()->after('sorumlu');
        });
    }

    public function down(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->dropColumn(['sorumlu', 'termin']);
        });
    }
};
