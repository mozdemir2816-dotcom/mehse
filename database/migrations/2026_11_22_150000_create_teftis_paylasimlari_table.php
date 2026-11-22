<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teftis_paylasimlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('baslik', 200);
            $table->string('mufettis_adi', 160)->nullable();
            $table->timestamp('bitis_tarihi');
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('erisim_sayisi')->default(0);
            $table->timestamp('son_erisim_at')->nullable();
            $table->json('erisim_kayitlari')->nullable();
            $table->text('notlar')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teftis_paylasimlari');
    }
};
