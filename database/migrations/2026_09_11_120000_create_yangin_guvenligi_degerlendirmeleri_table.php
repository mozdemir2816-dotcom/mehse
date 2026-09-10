<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yangın Güvenliği Genel Durum Değerlendirmesi — firma başına bir kayıt. Bina
 * kullanımı, kat/kullanıcı yükü, bölümler ve faaliyet/depolama riskleri girilir;
 * bina yangın tehlike sınıfı (düşük/orta/yüksek) belirlenir (Binaların Yangından
 * Korunması Hakkında Yönetmelik).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yangin_guvenligi_degerlendirmeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('inceleme_yeri')->nullable();
            $table->string('yapi_durumu')->nullable();          // betonarme / çelik / yığma / prefabrik ...
            $table->text('kullanim_aciklamasi')->nullable();
            $table->string('kullanim_turu')->nullable();        // config anahtarı
            $table->decimal('taban_alani_m2', 12, 2)->nullable();
            $table->unsignedSmallInteger('kat_sayisi')->nullable();
            $table->unsignedInteger('kullanici_yuku')->nullable();
            $table->json('ozel_kullanimlar')->nullable();       // {kapali_otopark, bodrum, konaklama, toplanti, kazan_dairesi} => bool
            $table->json('bolumler')->nullable();              // seçili bölüm etiketleri
            $table->json('riskler')->nullable();               // seçili risk etiketleri
            $table->string('belirlenen_tehlike_sinifi')->nullable();  // dusuk | orta | yuksek (elle geçersiz kılınabilir)
            $table->boolean('sinif_elle')->default(false);
            $table->json('tespitler')->nullable();             // [{madde, oncelik}]
            $table->text('genel_not')->nullable();
            $table->date('degerlendirme_tarihi')->nullable();
            $table->timestamps();

            $table->unique('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yangin_guvenligi_degerlendirmeleri');
    }
};
