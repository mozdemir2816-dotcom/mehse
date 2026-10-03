<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Doküman Yönetimi → Arşiv (04.10.2026, kullanıcının FirstİSG Arşiv
 * referansı): kategori kurallı arşiv + şablondan belge üretimi.
 * - arsiv_dosyalari: asama (dosyada / imza_bekliyor), yil, kisi_adi,
 *   kaynak (yukleme / sistem / sablon), sablon_adi, alanlar (şablon değerleri).
 * - belge_sablonlari: kullanıcının {{yer_tutucu}}lu Word / Excel şablonları.
 * - Eski kategori anahtarları yenilerine taşınır (config arsiv.eski_kategoriler).
 * FK yok — canlıda FK'ler errno 150 veriyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsiv_dosyalari', function (Blueprint $table) {
            $table->string('asama', 20)->default('dosyada')->after('aktif');
            $table->unsignedSmallInteger('yil')->nullable()->after('asama');
            $table->string('kisi_adi')->nullable()->after('yil');
            $table->string('kaynak', 20)->nullable()->after('kisi_adi');
            $table->string('sablon_adi')->nullable()->after('kaynak');
            $table->json('alanlar')->nullable()->after('sablon_adi');
        });

        Schema::create('belge_sablonlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ad');
            $table->string('kategori', 40)->nullable();
            $table->string('dosya_adi');
            $table->string('dosya_yolu');
            $table->string('tur', 10);
            $table->json('yer_tutucular')->nullable();
            $table->timestamps();
        });

        foreach (config('arsiv.eski_kategoriler', []) as $eski => $yeni) {
            DB::table('arsiv_dosyalari')->where('kategori', $eski)->update(['kategori' => $yeni]);
        }
    }

    public function down(): void
    {
        foreach (config('arsiv.eski_kategoriler', []) as $eski => $yeni) {
            DB::table('arsiv_dosyalari')->where('kategori', $yeni)->update(['kategori' => $eski]);
        }

        Schema::dropIfExists('belge_sablonlari');

        Schema::table('arsiv_dosyalari', function (Blueprint $table) {
            $table->dropColumn(['asama', 'yil', 'kisi_adi', 'kaynak', 'sablon_adi', 'alanlar']);
        });
    }
};
