<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saha Denetimi ("Şantiye Denetim ve Değerlendirme") — isgpratik SAHA
 * DENETİMİ/1-15.jpg + gerçek örnek PDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saha_denetimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->unsignedInteger('revizyon')->default(1);
            $table->string('is_tanimi')->nullable();
            $table->string('santiye_adi')->nullable();
            $table->string('santiye_sorumlusu')->nullable();
            $table->string('is_referans_no')->nullable();
            $table->date('denetim_tarihi')->nullable();
            $table->string('denetim_saati')->nullable();

            $table->string('denetci_adi')->nullable();
            $table->string('denetci_kase')->nullable();

            $table->json('cevaplar')->nullable();
            $table->json('ekip_uyeleri')->nullable();
            $table->text('genel_notlar')->nullable();

            $table->decimal('uygunluk_yuzdesi', 5, 2)->nullable();
            $table->boolean('kritik_uygunsuzluk_var')->default(false);

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saha_denetimleri');
    }
};
