<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saha_ziyaret_kanitlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->date('ziyaret_tarihi')->index();
            $table->string('konu', 255)->nullable();
            $table->timestamp('giris_at')->nullable();
            $table->timestamp('cikis_at')->nullable();
            $table->unsignedInteger('sure_dk')->default(0);
            $table->decimal('giris_enlem', 10, 7)->nullable();
            $table->decimal('giris_boylam', 10, 7)->nullable();
            $table->decimal('cikis_enlem', 10, 7)->nullable();
            $table->decimal('cikis_boylam', 10, 7)->nullable();
            $table->boolean('qr_dogrulandi')->default(false);
            $table->boolean('imza_onay')->default(false);
            $table->string('defter_dosyasi', 255)->nullable();
            $table->string('durum', 24)->default('devam_ediyor'); // devam_ediyor | tamamlandi
            $table->text('notlar')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'ziyaret_tarihi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saha_ziyaret_kanitlari');
    }
};
