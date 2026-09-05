<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acil_durum_krokileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->unique()->constrained('firmalar')->cascadeOnDelete();
            $table->string('arka_plan_gorseli')->nullable();
            $table->json('duvarlar')->nullable();
            $table->json('semboller')->nullable();
            $table->date('hazirlanma_tarihi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acil_durum_krokileri');
    }
};
