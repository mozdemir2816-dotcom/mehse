<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 23.11.2026 — Yıl başı toplu online eğitim:
 *  - egitim_paketleri.egitim_turu: ilk_defa (Temel İSG Eğitimi, 8 saat online) | yenileme
 *    (Temel İSG Yenileme Eğitimi, 4 saat online). İki paket birbirine karışmaz.
 *  - egitim_atamalari: aynı yenileme paketi her dönem aynı çalışana yeniden atanabilsin diye
 *    (paket, çalışan) tekilliği kaldırıldı; `donem_yili` toplu gönderimin yılı.
 *  - egitim_toplu_bildirimleri: firma × yıl, firma yetkilisine giden bildirim kaydı.
 *  - uzaktan_egitim_firma_ayarlari.icerik_onaylari: içerik onayı paket başına (8 saat ve 4 saat
 *    paketleri aynı firmada birlikte onaylı olabilsin).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('egitim_paketleri', 'egitim_turu')) {
            Schema::table('egitim_paketleri', function (Blueprint $table) {
                $table->string('egitim_turu', 20)->default('ilk_defa')->after('ad');
            });
        }

        Schema::table('egitim_atamalari', function (Blueprint $table) {
            // Yabancı anahtar için tek sütun dizini, tekil dizin kaldırılmadan önce eklenmeli (MySQL).
            $table->index('egitim_paketi_id', 'egitim_atamalari_paket_index');
        });
        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->dropUnique(['egitim_paketi_id', 'calisan_id']);
            $table->unsignedSmallInteger('donem_yili')->nullable()->after('egitim_turu');
        });

        if (! Schema::hasColumn('uzaktan_egitim_firma_ayarlari', 'icerik_onaylari')) {
            Schema::table('uzaktan_egitim_firma_ayarlari', function (Blueprint $table) {
                $table->json('icerik_onaylari')->nullable();
            });
        }

        Schema::create('egitim_toplu_bildirimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->unsignedSmallInteger('yil');
            $table->unsignedInteger('yenileme_sayisi')->default(0);
            $table->unsignedInteger('ilk_defa_sayisi')->default(0);
            $table->string('eposta')->nullable();
            $table->timestamp('eposta_gonderildi_at')->nullable();
            $table->text('eposta_hatasi')->nullable();
            $table->timestamps();

            $table->unique(['firma_id', 'yil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_toplu_bildirimleri');

        Schema::table('uzaktan_egitim_firma_ayarlari', function (Blueprint $table) {
            $table->dropColumn('icerik_onaylari');
        });

        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->dropColumn('donem_yili');
            $table->unique(['egitim_paketi_id', 'calisan_id']);
        });
        Schema::table('egitim_atamalari', function (Blueprint $table) {
            $table->dropIndex('egitim_atamalari_paket_index');
        });

        Schema::table('egitim_paketleri', function (Blueprint $table) {
            $table->dropColumn('egitim_turu');
        });
    }
};
