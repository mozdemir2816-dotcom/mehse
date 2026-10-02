<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Olay Kaydı — isgsuite "Yeni Olay Kaydı" karşılaştırmasında eksik kalan alanlar:
 * bölüm/alan/yapılan iş, ekipman/kimyasal, tehlike sınıflandırması, olay detayı,
 * olay etkileri (yaralanma / sağlık şikayeti / tıbbi müdahale / iş göremezlik /
 * ekipman hasarı / ramak kala), risk analizi ve acil durum ilişkisi, kaza ve
 * yaralanma türü, müdahale detayı, sistemsel eksiklik, genel değerlendirme,
 * işyeri hekimi + işveren vekili ve kayıt durumu (açık / incelemede / kapandı).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olay_kayitlari', function (Blueprint $table) {
            $table->string('durum')->default('acik')->after('olay_tipi');
            $table->string('bolum')->nullable()->after('olay_yeri');
            $table->string('alan')->nullable()->after('bolum');
            $table->string('yapilan_is')->nullable()->after('alan');
            $table->string('ekipman')->nullable()->after('yapilan_is');
            $table->string('kimyasal')->nullable()->after('ekipman');
            $table->string('siniflandirma')->nullable()->after('kimyasal');
            $table->text('olay_detayi')->nullable()->after('olay_ozeti');
            $table->json('etkiler')->nullable()->after('olay_detayi');
            $table->string('risk_analizinde')->nullable()->after('potansiyel_skor');
            $table->text('risk_analizi_notu')->nullable()->after('risk_analizinde');
            $table->string('acil_durum_iliskisi')->nullable()->after('risk_analizi_notu');
            $table->text('acil_durum_notu')->nullable()->after('acil_durum_iliskisi');
            $table->text('sistemsel_eksiklik')->nullable()->after('kok_neden');
            $table->text('genel_degerlendirme')->nullable()->after('duzeltici_faaliyet');
            $table->string('kaza_turu')->nullable()->after('kayip_gun_sayisi');
            $table->string('yaralanma_turu')->nullable()->after('kaza_turu');
            $table->text('mudahale_detayi')->nullable()->after('yaralanma_turu');
            $table->string('isyeri_hekimi')->nullable()->after('rapor_hazirlayan_kase');
            $table->string('isveren_vekili')->nullable()->after('isyeri_hekimi');
        });
    }

    public function down(): void
    {
        Schema::table('olay_kayitlari', function (Blueprint $table) {
            $table->dropColumn([
                'durum', 'bolum', 'alan', 'yapilan_is', 'ekipman', 'kimyasal', 'siniflandirma',
                'olay_detayi', 'etkiler', 'risk_analizinde', 'risk_analizi_notu',
                'acil_durum_iliskisi', 'acil_durum_notu', 'sistemsel_eksiklik', 'genel_degerlendirme',
                'kaza_turu', 'yaralanma_turu', 'mudahale_detayi', 'isyeri_hekimi', 'isveren_vekili',
            ]);
        });
    }
};
