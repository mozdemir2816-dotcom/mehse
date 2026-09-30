<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İSG Kurulu yeniden yapılanması (isgsuite.tr "İSG Kurulu" modülü referansı):
 * - kurul_uyeleri: firmaya bağlı kalıcı kurul üyeleri (rol = İSG Kurulları
 *   Hakkında Yönetmelik Md.6 üyelik türü; config isg.kurul_toplantisi.roller).
 * - kurul_toplantilari: belge/revizyon, bitiş saati, tür, durum, sonraki
 *   toplantı ve notlar. Mevcut `saat` başlangıç saati olarak kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurul_uyeleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->string('rol');
            $table->string('ad_soyad');
            $table->string('gorev')->nullable();
            $table->foreignId('calisan_id')->nullable()->constrained('calisanlar')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['firma_id', 'aktif']);
        });

        Schema::table('kurul_toplantilari', function (Blueprint $table) {
            $table->string('belge_no')->nullable()->after('toplanti_no');
            $table->string('revizyon_no')->nullable()->after('belge_no');
            $table->string('bitis_saati')->nullable()->after('saat');
            $table->string('tur')->default('olagan')->after('yer');
            $table->string('durum')->default('taslak')->after('tur');
            $table->date('sonraki_toplanti')->nullable()->after('durum');
            $table->text('notlar')->nullable()->after('kararlar');
        });

        // Bu migration'dan önce kaydedilmiş toplantılar tutanağı çıkarılmış,
        // yapılmış toplantılardır — varsayılan "taslak" yerine "tamamlandı".
        DB::table('kurul_toplantilari')->update(['durum' => 'tamamlandi']);
    }

    public function down(): void
    {
        Schema::table('kurul_toplantilari', function (Blueprint $table) {
            $table->dropColumn(['belge_no', 'revizyon_no', 'bitis_saati', 'tur', 'durum', 'sonraki_toplanti', 'notlar']);
        });

        Schema::dropIfExists('kurul_uyeleri');
    }
};
