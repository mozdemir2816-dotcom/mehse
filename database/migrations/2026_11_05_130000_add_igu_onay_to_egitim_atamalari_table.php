<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_atamalari', function (Blueprint $table) {
            // Tehlikeli / çok tehlikeli işyerinde 4. modül yüz yüze tamamlanınca İGU verir; belge bundan sonra açılır.
            $table->timestamp('igu_onay_at')->nullable();
            $table->unsignedBigInteger('igu_onaylayan_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->dropColumn(['igu_onay_at', 'igu_onaylayan_id']);
        });
    }
};
