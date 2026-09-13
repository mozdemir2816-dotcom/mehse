<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('rol');
        });

        // Mevcut sahip hesabı tam yetkili "sahip" rolüne geçer.
        \DB::table('users')->where('email', 'mozdemir2816@gmail.com')->update(['rol' => 'sahip']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }
};
