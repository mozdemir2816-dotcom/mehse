<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşe özgü eğitim kütüphanesinde konunun hangi çalışan grubu / iş koluna ait olduğu (Eğitim Katılım formunda
 * iş koluna göre kayıt edilen konular kütüphanede o iş koluyla saklanır — kullanıcı 03.11.2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ise_ozgu_egitim_konulari', function (Blueprint $table) {
            $table->string('is_kolu')->nullable()->after('is_kalemleri');
        });
    }

    public function down(): void
    {
        Schema::table('ise_ozgu_egitim_konulari', function (Blueprint $table) {
            $table->dropColumn('is_kolu');
        });
    }
};
