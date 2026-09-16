<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşe özgü eğitim sunumu kütüphanesi — uzmanın görev/iş başına yüklediği
 * PowerPoint/PDF slaytları. Firmadan bağımsız, kullanıcının kendi arşivi
 * (RiskProsedur/TalimatSablonu ile aynı desen); İşbaşı Eğitim Tutanağı
 * sayfasında çalışanın görevine göre eşleşen sunum önerilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_sunumlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('gorev');
            $table->string('baslik');
            $table->string('dosya_adi')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->unsignedBigInteger('boyut')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'gorev']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_sunumlari');
    }
};
