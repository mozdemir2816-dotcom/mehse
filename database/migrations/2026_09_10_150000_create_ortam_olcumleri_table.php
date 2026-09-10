<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş Hijyeni / Ortam Ölçümleri Takibi — firma başına bir kayıt. Ölçülen
 * parametreler (gürültü, toz, aydınlatma, termal konfor, VOC, ağır metal vb.),
 * ölçüm tarihi, sonuç (sınır değerle karşılaştırma) ve bir sonraki ölçüm tarihi
 * `olcumler` JSON'unda tutulur (İş Hijyeni Ölçüm, Test ve Analizi Yön.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ortam_olcumleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->json('olcumler')->nullable();
            $table->text('genel_not')->nullable();
            $table->timestamps();

            $table->unique('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ortam_olcumleri');
    }
};
