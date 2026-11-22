<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matbu Evrak Paketi (22.11.2026) — kimyasal başına MSDS (GBF) özeti ve afiş içeriği.
 * ozet: Gemini elle isteminden gelen yapılandırılmış özet (App\Support\KimyasalOzetleri);
 * ozet_yz_taslak: uzman onayına kadar belgede "taslak" etiketi; evrak_cikti_at: firmaya
 * matbu paketle gönderildiği an (boşsa "gönderilmedi" sayılır).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kimyasal_urunler', function (Blueprint $table) {
            $table->json('ozet')->nullable()->after('aciklama');
            $table->boolean('ozet_yz_taslak')->default(false)->after('ozet');
            $table->timestamp('ozet_onaylandi_at')->nullable()->after('ozet_yz_taslak');
            $table->timestamp('evrak_cikti_at')->nullable()->after('ozet_onaylandi_at');
        });
    }

    public function down(): void
    {
        Schema::table('kimyasal_urunler', function (Blueprint $table) {
            $table->dropColumn(['ozet', 'ozet_yz_taslak', 'ozet_onaylandi_at', 'evrak_cikti_at']);
        });
    }
};
