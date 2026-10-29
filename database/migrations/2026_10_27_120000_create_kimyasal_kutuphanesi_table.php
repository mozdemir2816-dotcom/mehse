<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kimyasal Kütüphanesi — firmaların envanterine tek tıkla kopyalanan hazır
 * kimyasal kayıtları (Tehlike Kütüphanesi'nin kimyasal karşılığı). `user_id`
 * boşsa hazır (sistem) kayıt, doluysa o uzmanın kendi eklediği kayıttır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kimyasal_kutuphanesi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->string('urun_adi');
            $table->string('cas_no')->nullable();
            $table->string('tedarikci')->nullable();
            $table->string('fiziksel_hal')->nullable();
            $table->string('kullanim_alani')->nullable();
            $table->text('depolama')->nullable();
            $table->json('ghs')->nullable();
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'urun_adi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kimyasal_kutuphanesi');
    }
};
