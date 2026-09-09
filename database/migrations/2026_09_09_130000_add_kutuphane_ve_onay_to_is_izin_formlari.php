<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İş İzin Formu — izin kütüphanesi izleri + onay/kapanış yaşam döngüsü.
 * PTW artık canlı bir belge: taslak -> onay bekliyor -> onaylandı (çalışma
 * yetkisi) -> iş tamamlandı -> kapatıldı (saha teslim). İki onaycı bağımsız
 * onay/ret verir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('is_izin_formlari', function (Blueprint $table) {
            // Kütüphane izi + ek alanlar
            $table->string('sablon_kaynak')->nullable()->after('izin_no');
            $table->unsignedSmallInteger('gecerlilik_saat')->nullable()->after('bitis');
            $table->json('uyarilar')->nullable()->after('gerekli_kkdler');
            $table->text('ozel_kosullar')->nullable()->after('uyarilar');

            // Onay / kapanış yaşam döngüsü
            $table->string('durum')->default('taslak')->after('onay2_ad');
            $table->string('onay1_durum')->nullable()->after('durum'); // bekliyor / onayladi / reddetti
            $table->dateTime('onay1_tarih')->nullable()->after('onay1_durum');
            $table->string('onay2_durum')->nullable()->after('onay1_tarih');
            $table->dateTime('onay2_tarih')->nullable()->after('onay2_durum');
            $table->text('red_gerekcesi')->nullable()->after('onay2_tarih');
            $table->dateTime('is_bitis_tarihi')->nullable()->after('red_gerekcesi');
            $table->boolean('saha_teslim_alindi')->default(false)->after('is_bitis_tarihi');
            $table->text('kapanis_notu')->nullable()->after('saha_teslim_alindi');
            $table->string('kapatan')->nullable()->after('kapanis_notu');
        });
    }

    public function down(): void
    {
        Schema::table('is_izin_formlari', function (Blueprint $table) {
            $table->dropColumn([
                'sablon_kaynak', 'gecerlilik_saat', 'uyarilar', 'ozel_kosullar',
                'durum', 'onay1_durum', 'onay1_tarih', 'onay2_durum', 'onay2_tarih',
                'red_gerekcesi', 'is_bitis_tarihi', 'saha_teslim_alindi', 'kapanis_notu', 'kapatan',
            ]);
        });
    }
};
