<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('revir_kayitlari')) {
            Schema::create('revir_kayitlari', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('firma_id')->index();
                $table->unsignedBigInteger('calisan_id')->nullable()->index();
                $table->string('calisan_ad_soyad', 160);
                $table->string('protokol_no', 40)->nullable();
                $table->dateTime('tarih')->index();
                $table->string('basvuru_nedeni', 200);
                $table->text('bulgu_tani')->nullable();
                $table->text('yapilan_islem')->nullable();
                $table->string('sonuc', 30)->default('ayakta');
                $table->unsignedSmallInteger('istirahat_gun')->nullable();
                $table->string('hekim_dsp', 140)->nullable();
                $table->text('notlar')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ecza_dolabi_kalemleri')) {
            Schema::create('ecza_dolabi_kalemleri', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('firma_id')->index();
                $table->string('malzeme_adi', 160);
                $table->string('kategori', 30)->default('pansuman');
                $table->unsignedInteger('miktar')->default(0);
                $table->unsignedInteger('kritik_miktar')->default(5);
                $table->string('birim', 30)->default('Adet');
                $table->date('skt')->nullable()->index();
                $table->string('konum', 120)->nullable();
                $table->text('notlar')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('asi_kayitlari')) {
            Schema::create('asi_kayitlari', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('firma_id')->index();
                $table->unsignedBigInteger('calisan_id')->nullable()->index();
                $table->string('calisan_ad_soyad', 160);
                $table->string('asi_turu', 80);
                $table->string('doz', 40)->default('1. Doz');
                $table->date('yapilma_tarihi')->index();
                $table->date('sonraki_doz_tarihi')->nullable()->index();
                $table->string('uygulayan', 140)->nullable();
                $table->text('notlar')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('taseron_basvurulari')) {
            Schema::create('taseron_basvurulari', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('firma_id')->index();
                $table->unsignedBigInteger('taseron_id')->nullable()->index();
                $table->string('token', 48)->unique();
                $table->string('unvan', 200)->nullable();
                $table->string('vergi_no', 30)->nullable();
                $table->string('faaliyet', 200)->nullable();
                $table->string('yetkili_ad', 140)->nullable();
                $table->string('telefon', 40)->nullable();
                $table->string('eposta', 160)->nullable();
                $table->string('durum', 24)->default('link_acik')->index();
                $table->json('yuklenen_belgeler')->nullable();
                $table->text('ret_nedeni')->nullable();
                $table->timestamp('basvuru_at')->nullable();
                $table->timestamp('incelenme_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kkd_talepleri')) {
            Schema::create('kkd_talepleri', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('firma_id')->index();
                $table->unsignedBigInteger('calisan_id')->index();
                $table->string('calisan_ad_soyad', 160);
                $table->string('kkd_turu', 120);
                $table->string('beden', 40)->nullable();
                $table->string('neden', 30)->default('yipranma');
                $table->text('aciklama')->nullable();
                $table->string('durum', 24)->default('bekliyor')->index();
                $table->text('yanit_notu')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('saha_bulgulari') && ! Schema::hasColumn('saha_bulgulari', 'isveren_ret_gecmisi')) {
            Schema::table('saha_bulgulari', function (Blueprint $table): void {
                $table->json('isveren_ret_gecmisi')->nullable()->after('isveren_kapanis_gonderen');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('saha_bulgulari') && Schema::hasColumn('saha_bulgulari', 'isveren_ret_gecmisi')) {
            Schema::table('saha_bulgulari', function (Blueprint $table): void {
                $table->dropColumn('isveren_ret_gecmisi');
            });
        }

        Schema::dropIfExists('kkd_talepleri');
        Schema::dropIfExists('taseron_basvurulari');
        Schema::dropIfExists('asi_kayitlari');
        Schema::dropIfExists('ecza_dolabi_kalemleri');
        Schema::dropIfExists('revir_kayitlari');
    }
};
