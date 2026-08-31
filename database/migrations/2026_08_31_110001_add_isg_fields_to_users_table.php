<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('unvan')->default('c_sinifi')->after('name'); // config isg.uzman_unvanlari
            $table->string('rol')->default('uzman')->after('unvan');
            $table->string('telefon')->nullable()->after('email');
            $table->string('katip_no')->nullable();      // İSG-KATİP sözleşme/kişi no
            $table->string('sertifika_no')->nullable();
            $table->date('sertifika_gecerlilik')->nullable();
            $table->string('kase_gorseli')->nullable();   // PDF/Word çıktıları için
            $table->string('imza_gorseli')->nullable();

            // Abonelik (gerçek ödeme kapsam dışı — bilgi + kalan gün göstergesi)
            $table->string('abonelik_plani')->default('uzman');
            $table->date('abonelik_baslangic')->nullable();
            $table->date('abonelik_bitis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'unvan', 'rol', 'telefon', 'katip_no', 'sertifika_no', 'sertifika_gecerlilik',
                'kase_gorseli', 'imza_gorseli', 'abonelik_plani', 'abonelik_baslangic', 'abonelik_bitis',
            ]);
        });
    }
};
