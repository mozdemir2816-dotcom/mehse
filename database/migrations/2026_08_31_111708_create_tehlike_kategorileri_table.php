<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Risk Kütüphanesi — tehlike kategorileri (Fabrika, Atölye, Elektrik vb.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tehlike_kategorileri', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('anahtar')->unique();
            $table->unsignedInteger('sira')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tehlike_kategorileri');
    }
};
