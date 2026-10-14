<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kütüphane maddesine "Alınacak önlem" (öneri) alanı. Ekipman / şantiye Fine-Kinney
 * dosyalarındaki "Açıklayıcı DÖF" metni daha önce mevcut_onlem'e yazılıyordu;
 * değerlendirmeye eklenince "Mevcut önlemler"e gidip PDF'teki Öneri sütunu boş
 * kalıyordu (kullanıcı 14.10.2026). Bu dosyalardan gelen maddelerde metin taşınır.
 */
return new class extends Migration
{
    private const ONEKLER = "^(MAK|CNC|TFD|EKP|SK)-";

    public function up(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->text('oneri')->nullable()->after('mevcut_onlem');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE tehlikeler SET oneri = mevcut_onlem, mevcut_onlem = NULL WHERE kod REGEXP '".self::ONEKLER."' AND oneri IS NULL");
        }
    }

    public function down(): void
    {
        Schema::table('tehlikeler', function (Blueprint $table) {
            $table->dropColumn('oneri');
        });
    }
};
