<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KkdFormu (toplu teslim formu) → KkdZimmet (kalıcı takip sicili) köprüsü.
 * KkdFormu::kaydet() artık aynı teslimi burada da otomatik satır olarak
 * açıyor; bu kolon o satırların hangi forma ait olduğunu işaretler ve
 * form tekrar kaydedildiğinde eski otomatik satırların bulunup silinmesini
 * sağlar. Bkz. App\Support\KkdZimmetFormuSenkron.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kkd_zimmetleri', function (Blueprint $table) {
            $table->foreignId('kkd_zimmet_formu_id')->nullable()
                ->after('kkd_stok_karti_id')
                ->constrained('kkd_zimmet_formlari')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kkd_zimmetleri', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kkd_zimmet_formu_id');
        });
    }
};
