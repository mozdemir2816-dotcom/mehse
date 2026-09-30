<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kayıt/başvuru kayıtları (isgsuite.tr "Başvuru seçenekleri" referansı):
 * - tip=uzman: bireysel İş Güvenliği Uzmanı kaydı — hesap anında açılır,
 *   kayıt burada yasal onay (sözleşme/KVKK) izi için tutulur (durum=onaylandi).
 * - tip=osgb: OSGB başvurusu — sahip "Başvurular" sayfasından onaylayınca
 *   hesap açılır ve deneme başlar, reddedilirse neden yazılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basvurular', function (Blueprint $table) {
            $table->id();
            $table->string('tip');
            $table->string('durum')->default('beklemede');
            $table->string('ad_soyad');
            $table->string('eposta');
            $table->string('telefon')->nullable();
            // OSGB alanları
            $table->string('osgb_adi')->nullable();
            $table->string('yetki_no')->nullable();
            $table->string('vergi_no')->nullable();
            $table->string('sorumlu_mudur')->nullable();
            $table->string('iletisim_eposta')->nullable();
            $table->text('adres')->nullable();
            $table->text('not')->nullable();
            // Yasal onay izi: [{anahtar, revizyon, onay_at}] + IP
            $table->json('onaylar')->nullable();
            $table->string('ip', 45)->nullable();
            // İnceleme
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('inceleyen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('incelendi_at')->nullable();
            $table->text('red_nedeni')->nullable();
            $table->timestamps();
            $table->index(['tip', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basvurular');
    }
};
