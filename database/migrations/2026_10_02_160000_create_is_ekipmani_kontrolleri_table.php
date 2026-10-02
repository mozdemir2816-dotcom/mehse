<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İş ekipmanı periyodik kontrol geçmişi — her muayene ayrı satır (tarih, yapan,
 * rapor no, sonuç, sonraki termin, taranmış rapor dosyası). Ekipmanın kendi
 * satırı her zaman SON kontrolü gösterir; önceki kontroller burada kalır.
 * Yabancı anahtar yok (canlı DB'de FK kurulamıyor); silme temizliği modelde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('is_ekipmani_kontrolleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('is_ekipmani_id')->index();
            $table->date('kontrol_tarihi');
            $table->date('sonraki_tarih')->nullable();
            $table->string('kontrol_eden')->nullable();
            $table->string('rapor_no')->nullable();
            $table->string('sonuc')->default('bekliyor');
            $table->string('dosya_adi')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->text('notu')->nullable();
            $table->timestamps();

            $table->unique(['is_ekipmani_id', 'kontrol_tarihi']);
        });

        // Mevcut ekipmanların son kontrolü geçmişin ilk satırı olur.
        DB::table('is_ekipmani_kontrolleri')->insertUsing(
            ['is_ekipmani_id', 'kontrol_tarihi', 'sonraki_tarih', 'kontrol_eden', 'rapor_no', 'sonuc', 'created_at', 'updated_at'],
            DB::table('is_ekipmanlari')
                ->whereNotNull('son_muayene_tarihi')
                ->select(['id', 'son_muayene_tarihi', 'sonraki_vize_tarihi', 'muayene_yapan', 'rapor_no', 'sonuc', 'updated_at', 'updated_at']),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('is_ekipmani_kontrolleri');
    }
};
