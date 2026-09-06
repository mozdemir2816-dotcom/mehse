<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->string('egitim_turu')->default('ilk')->after('baslik_anahtari'); // 'ilk' veya 'tekrar'
        });
    }

    public function down(): void
    {
        Schema::table('egitim_katilimlari', function (Blueprint $table) {
            $table->dropColumn('egitim_turu');
        });
    }
};
