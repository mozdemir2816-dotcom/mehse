<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soru Bankası — kalıcı, kaynaklı ve onaylı İSG sınav sorusu havuzu. Sektör +
 * konu + zorluk etiketli; her soru bir yasal/eğitsel dayanağa (kaynak) bağlı ve
 * taslak -> onaylandı -> arşiv durumundan geçer. Eğitim Soruları ekranı ile
 * Uzaktan Eğitim paketleri sınavlarını YALNIZ onaylı sorulardan besler.
 * user_id NULL = sistem/ortak havuz (seeder); dolu = uzmanın kendi eklediği.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soru_bankasi_sorulari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('sektor_anahtari')->nullable();
            $table->string('konu')->nullable();
            $table->string('zorluk')->default('orta'); // kolay / orta / zor

            $table->text('soru');
            $table->json('secenekler');            // 4 şık
            $table->unsignedTinyInteger('dogru_index')->default(0);
            $table->text('aciklama')->nullable();   // doğru cevabın gerekçesi (cevap anahtarı notu)
            $table->string('kaynak')->nullable();   // yasal dayanak / referans (ör. "6331 sK m.4")

            $table->string('durum')->default('taslak'); // taslak / onaylandi / arsiv
            $table->string('uretim_kaynagi')->nullable(); // seed / manuel / ai
            $table->string('onaylayan')->nullable();
            $table->dateTime('onay_tarihi')->nullable();

            $table->timestamps();

            $table->index(['durum', 'sektor_anahtari', 'konu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soru_bankasi_sorulari');
    }
};
