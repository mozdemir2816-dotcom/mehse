<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uzaktan eğitim paketinin final sınav soru havuzu. Sınavda paketten rastgele
 * `sinav_soru_sayisi` kadar soru seçilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_paketi_sorulari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egitim_paketi_id')->constrained('egitim_paketleri')->cascadeOnDelete();

            $table->text('soru');
            $table->json('secenekler');                 // ["A şıkkı", "B şıkkı", ...]
            $table->unsignedTinyInteger('dogru_index'); // secenekler dizisindeki doğru cevap
            $table->text('aciklama')->nullable();       // doğru cevabın gerekçesi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_paketi_sorulari');
    }
};
