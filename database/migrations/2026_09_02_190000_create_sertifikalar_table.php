<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sertifika Oluştur — isgpratik 66-68.jpg. Katılımcı başına bir sayfa PDF üretir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('belge_no')->nullable();
            $table->string('tip'); // config isg.sertifika.tipler
            $table->string('sektor_anahtari')->nullable(); // yalnız 'isg' tipi

            $table->unsignedTinyInteger('gun_sayisi')->default(1);
            $table->json('egitim_tarihleri')->nullable();
            $table->date('gecerlilik_tarihi')->nullable();
            $table->string('sure_metni')->nullable();

            $table->boolean('egitici_igu_dahil')->default(true);
            $table->string('egitici_igu_adi')->nullable();
            $table->string('egitici_igu_kase')->nullable();
            $table->boolean('egitici_hekim_dahil')->default(false);
            $table->string('egitici_hekim_adi')->nullable();
            $table->string('egitici_hekim_kase')->nullable();

            $table->string('logo_konumu')->default('sol');
            $table->string('cerceve')->default('klasik_siyah');

            $table->json('konu_icerigi')->nullable();
            $table->json('katilimcilar')->nullable();

            $table->timestamps();

            $table->index(['firma_id', 'tip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikalar');
    }
};
