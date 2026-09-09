<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan eğitim dersi — pakete bağlı bir video (YouTube / Vimeo gizli link vb.).
 * Çalışan portalda sırayla izler; video_zorunlu_yuzde'ye ulaşınca "izlendi" olur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_dersleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egitim_paketi_id')->constrained('egitim_paketleri')->cascadeOnDelete();

            $table->unsignedInteger('sira')->default(0);
            $table->string('baslik');
            $table->string('video_url');
            $table->string('saglayici')->default('youtube'); // youtube | vimeo | diger
            $table->unsignedInteger('sure_sn')->nullable();  // biliniyorsa
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->index(['egitim_paketi_id', 'sira']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_dersleri');
    }
};
