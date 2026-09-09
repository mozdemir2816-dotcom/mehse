<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İSG Afiş / Pano — işyerine asılacak bilgilendirme afişleri (GHS piktogram
 * levhaları, kimyasal güvenlik, acil durum, KKD, genel İSG). Firmaya özel ya da
 * genel (firma_id null) olabilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isg_afisleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->nullable()->constrained('firmalar')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('baslik');
            $table->string('kategori')->default('genel'); // config isg.afis.kategoriler
            $table->string('dosya_adi');
            $table->string('dosya_yolu');
            $table->unsignedBigInteger('boyut')->default(0);
            $table->text('aciklama')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isg_afisleri');
    }
};
