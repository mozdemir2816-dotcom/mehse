<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toolbox_konusmalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('baslik');
            $table->string('dosya_adi');
            $table->string('dosya_yolu');
            $table->unsignedBigInteger('boyut')->default(0);
            $table->text('aciklama')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toolbox_konusmalari');
    }
};
