<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İSG-KATİP sözleşmesindeki İGU aylık çalışma süresi (dk). KATİP bunu güncel çalışan
 * sayısı ve tehlike sınıfına göre hesaplar; her KATİP Excel yüklemesinde en son
 * dosyadaki değerle güncellenir. Doluysa aylık süre hesapları bunu kullanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->unsignedInteger('katip_aylik_dk')->nullable()->after('katip_no');
        });
    }

    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropColumn('katip_aylik_dk');
        });
    }
};
