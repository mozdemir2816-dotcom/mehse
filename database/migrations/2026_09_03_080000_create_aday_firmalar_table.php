<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aday Firma — Profilim > Pazarlama (Saha CRM, isgpratik 143.jpg). Görüşme
 * aşamasındaki potansiyel müşteri; "Kazanıldı" olunca gerçek Firma kaydına
 * elle dönüştürülür (App\Models\AdayFirma).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aday_firmalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('unvan');
            $table->string('yetkili_ad')->nullable();
            $table->string('telefon')->nullable();
            $table->string('sehir')->nullable();
            $table->string('asama')->default('aday');
            $table->date('hatirlatma_tarihi')->nullable();
            $table->text('notlar')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'asama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aday_firmalar');
    }
};
