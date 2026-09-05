<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('myk_meslekleri', function (Blueprint $table) {
            $table->id();
            $table->string('yeterlilik_kodu')->unique();
            $table->string('yeterlilik_adi');
            $table->string('belge_zorunluluk_tarihi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('myk_meslekleri');
    }
};
