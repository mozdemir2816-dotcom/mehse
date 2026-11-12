<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akıllı ekipman takibi (12.11.2026) — mevcut yapının üzerine:
 *
 *  - Ekipman kütüphanesi (şablon) zaten var: config/ekipman_katalogu.php + isg.periyodik_kontrol.tipler
 *    (yasal periyot / standart) + isg.haftalik_ekipman_kontrol.tipler (kontrol formu soruları) +
 *    TalimatKutuphanesi (talimat). Ayrı şablon tablosu açılmadı.
 *  - Firma ekipmanı = is_ekipmanlari; periyodik kontrol geçmişi = is_ekipmani_kontrolleri (var).
 *
 * Yeni:
 *  - is_ekipmanlari: üretim yılı, operatör QR anahtarı (giriş gerektirmeyen sayfa), isteğe bağlı PIN,
 *    bakım sorumlusu (arıza bildirimi gider).
 *  - is_ekipmani_gunluk_kontrolleri: operatörün telefondan (QR) ya da panelden doldurduğu günlük kontrol.
 *  - is_ekipmani_bakimlari: bakım / arıza kaydı; günlük kontrolde "Uygun Değil" çıkınca kendiliğinden açılır.
 *  - Tebliğ takibi (is_ekipmanlari): tebliğ paketi (talimat + aylık çizelge + tutanak) çıktısı alındı mı,
 *    işverene ıslak imzalı tebliğ edildi mi, imzalı nüshanın fotoğrafı / PDF'i.
 *  - talimat_sablonlari: ekipman tipine bağlı kütüphane talimatı (yapay zekâ taslağı dahil) — katalogda
 *    talimatı olmayan makineler için.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('is_ekipmanlari', function (Blueprint $table) {
            $table->unsignedSmallInteger('uretim_yili')->nullable()->after('marka_model');
            $table->string('qr_anahtar', 40)->nullable()->unique()->after('aktif');
            $table->string('qr_pin')->nullable()->after('qr_anahtar');          // hash; boşsa PIN istenmez
            $table->string('bakim_sorumlusu')->nullable()->after('qr_pin');
            $table->string('bakim_sorumlusu_eposta')->nullable()->after('bakim_sorumlusu');
            $table->dateTime('teblig_cikti_at')->nullable()->after('bakim_sorumlusu_eposta');   // paket basıldı → "İmza bekliyor"
            $table->boolean('isverene_teblig_edildi')->default(false)->after('teblig_cikti_at');
            $table->date('teblig_tarihi')->nullable()->after('isverene_teblig_edildi');
            $table->string('imzali_belge_yolu')->nullable()->after('teblig_tarihi');           // local disk, yalnız indirme ile
        });

        Schema::create('is_ekipmani_gunluk_kontrolleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('is_ekipmani_id')->constrained('is_ekipmanlari')->cascadeOnDelete();
            $table->dateTime('kontrol_zamani');
            $table->string('operator_adi');
            $table->string('form_adi')->nullable();                 // doldurulduğu andaki form (sorular değişse de kayıt okunur)
            $table->json('cevaplar');                                // [{soru, cevap: U|UD|Y, not}]
            $table->unsignedSmallInteger('uygunsuz_sayisi')->default(0);
            $table->boolean('kullanima_uygun')->default(true);
            $table->text('notu')->nullable();
            $table->string('kaynak', 10)->default('qr');             // qr | panel
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['is_ekipmani_id', 'kontrol_zamani'], 'ekipman_gunluk_zaman_index');
        });

        Schema::create('is_ekipmani_bakimlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('is_ekipmani_id')->constrained('is_ekipmanlari')->cascadeOnDelete();
            $table->foreignId('gunluk_kontrol_id')->nullable()->constrained('is_ekipmani_gunluk_kontrolleri')->nullOnDelete();
            $table->string('tur', 10)->default('ariza');             // ariza | bakim
            $table->string('durum', 10)->default('acik');            // acik | devam | kapali
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            $table->json('uygunsuz_maddeler')->nullable();
            $table->string('bildiren')->nullable();
            $table->string('sorumlu')->nullable();
            $table->dateTime('acilis_zamani');
            $table->dateTime('kapanis_zamani')->nullable();
            $table->text('kapanis_notu')->nullable();
            $table->timestamps();

            $table->index(['is_ekipmani_id', 'durum'], 'ekipman_bakim_durum_index');
        });

        Schema::table('talimat_sablonlari', function (Blueprint $table) {
            $table->string('ekipman_tipi')->nullable()->index()->after('kategori');
            $table->boolean('yz_taslak')->default(false)->after('ekipman_tipi');   // uzman gözden geçirince false
        });
    }

    public function down(): void
    {
        Schema::table('talimat_sablonlari', function (Blueprint $table) {
            $table->dropIndex(['ekipman_tipi']);
            $table->dropColumn(['ekipman_tipi', 'yz_taslak']);
        });
        Schema::dropIfExists('is_ekipmani_bakimlari');
        Schema::dropIfExists('is_ekipmani_gunluk_kontrolleri');
        Schema::table('is_ekipmanlari', function (Blueprint $table) {
            $table->dropUnique(['qr_anahtar']);
            $table->dropColumn(['uretim_yili', 'qr_anahtar', 'qr_pin', 'bakim_sorumlusu', 'bakim_sorumlusu_eposta',
                'teblig_cikti_at', 'isverene_teblig_edildi', 'teblig_tarihi', 'imzali_belge_yolu']);
        });
    }
};
