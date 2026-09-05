<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nace_kodlari', function (Blueprint $table) {
            $table->id();
            $table->string('kod')->unique();
            $table->string('tanim', 500);
            $table->string('tehlike_sinifi');
            $table->string('sektor_adi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nace_kodlari');
    }
};
