<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kimyasal Risk Değerlendirmesi — firma başına bir kayıt. Kimyasal envanterindeki
 * her ürün için tehlike grubu × kullanım miktarı × uçuculuk/tozlaşma'dan kontrol
 * bantlama (COSHH Essentials yaklaşımı 1-4) hesaplanır. `satirlar` JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kimyasal_risk_degerlendirmeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->json('satirlar')->nullable();
            $table->text('genel_not')->nullable();
            $table->date('degerlendirme_tarihi')->nullable();
            $table->timestamps();

            $table->unique('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kimyasal_risk_degerlendirmeleri');
    }
};
