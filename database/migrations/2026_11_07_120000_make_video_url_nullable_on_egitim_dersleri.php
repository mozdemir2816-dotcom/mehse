<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video bekleyen konu başlığı (taslak ders) için video_url boş olabilir. Taslak dersler çalışana
 * gösterilmez, süre/ders sayısına katılmaz; video bağlanınca otomatik yayına girer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egitim_dersleri', function (Blueprint $table) {
            $table->string('video_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('egitim_dersleri', function (Blueprint $table) {
            $table->string('video_url')->nullable(false)->change();
        });
    }
};
