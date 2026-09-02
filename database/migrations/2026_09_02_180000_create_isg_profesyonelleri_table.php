<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İSG Profesyonelleri — İGU / İşyeri Hekimi / DSP kayıtları, kaşe/imza görselleriyle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isg_profesyonelleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('tip'); // igu | isyeri_hekimi | dsp — config isg.isg_profesyonelleri.tipler
            $table->string('ad_soyad');
            $table->string('unvan')->nullable();
            $table->string('sertifika_no')->nullable();
            $table->string('telefon')->nullable();
            $table->string('kase_gorseli')->nullable();
            $table->string('imza_gorseli')->nullable();
            $table->boolean('aktif')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'tip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isg_profesyonelleri');
    }
};
