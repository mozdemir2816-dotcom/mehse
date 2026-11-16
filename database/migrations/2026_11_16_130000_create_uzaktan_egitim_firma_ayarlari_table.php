<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan eğitim firma ayarları: eğitimi verenler, sertifika kaşesi / logosu, çalışan imza alanı
 * ve içerik onayı (içerik değişirse onay yeniden istenir). Canlıda FK kurulamadığı için anahtarsız.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('uzaktan_egitim_firma_ayarlari')) {
            return;
        }

        Schema::create('uzaktan_egitim_firma_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id')->unique();
            $table->string('egitici_igu_ad')->nullable();
            $table->string('egitici_igu_unvan')->nullable();
            $table->string('egitici_hekim_ad')->nullable();
            $table->string('egitici_hekim_unvan')->nullable();
            $table->boolean('kase_bas')->default(true);
            $table->string('logo_konumu', 10)->default('yok'); // yok | sol | sag
            $table->boolean('calisan_imza_alani')->default(false);
            $table->unsignedBigInteger('icerik_paket_id')->nullable();
            $table->string('icerik_surumu', 40)->nullable();
            $table->timestamp('icerik_onay_at')->nullable();
            $table->string('icerik_onaylayan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzaktan_egitim_firma_ayarlari');
    }
};
