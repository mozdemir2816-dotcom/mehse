<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('saha_bulgulari')) {
            Schema::table('saha_bulgulari', function (Blueprint $table): void {
                if (! Schema::hasColumn('saha_bulgulari', 'kontrol_hiyerarsisi')) {
                    $table->string('kontrol_hiyerarsisi', 32)->nullable()->after('oncelik');
                }
                if (! Schema::hasColumn('saha_bulgulari', 'etkinlik_puani')) {
                    $table->unsignedTinyInteger('etkinlik_puani')->nullable()->after('hedef_siddet');
                }
                if (! Schema::hasColumn('saha_bulgulari', 'kapanis_kaniti_dosya')) {
                    $table->string('kapanis_kaniti_dosya', 255)->nullable()->after('kapanis_notu');
                }
                if (! Schema::hasColumn('saha_bulgulari', 'isveren_kapanis_talebi_at')) {
                    $table->timestamp('isveren_kapanis_talebi_at')->nullable()->after('kapanis_kaniti_dosya');
                }
                if (! Schema::hasColumn('saha_bulgulari', 'isveren_kapanis_notu')) {
                    $table->text('isveren_kapanis_notu')->nullable()->after('isveren_kapanis_talebi_at');
                }
                if (! Schema::hasColumn('saha_bulgulari', 'isveren_kapanis_dosya')) {
                    $table->string('isveren_kapanis_dosya', 255)->nullable()->after('isveren_kapanis_notu');
                }
                if (! Schema::hasColumn('saha_bulgulari', 'isveren_kapanis_gonderen')) {
                    $table->string('isveren_kapanis_gonderen', 150)->nullable()->after('isveren_kapanis_dosya');
                }
            });
        }

        if (Schema::hasTable('risk_degerlendirmeleri')) {
            Schema::table('risk_degerlendirmeleri', function (Blueprint $table): void {
                if (! Schema::hasColumn('risk_degerlendirmeleri', 'kilitli_at')) {
                    $table->timestamp('kilitli_at')->nullable()->after('durum');
                }
                if (! Schema::hasColumn('risk_degerlendirmeleri', 'kilitli_by')) {
                    $table->unsignedBigInteger('kilitli_by')->nullable()->after('kilitli_at');
                }
                if (! Schema::hasColumn('risk_degerlendirmeleri', 'onceki_versiyon_id')) {
                    $table->unsignedBigInteger('onceki_versiyon_id')->nullable()->after('kilitli_by');
                }
            });
        }

        if (Schema::hasTable('firmalar')) {
            Schema::table('firmalar', function (Blueprint $table): void {
                if (! Schema::hasColumn('firmalar', 'igu_hizmeti_yok')) {
                    $table->boolean('igu_hizmeti_yok')->default(false)->after('dsp_id');
                }
                if (! Schema::hasColumn('firmalar', 'hekim_hizmeti_yok')) {
                    $table->boolean('hekim_hizmeti_yok')->default(false)->after('igu_hizmeti_yok');
                }
                if (! Schema::hasColumn('firmalar', 'dsp_hizmeti_yok')) {
                    $table->boolean('dsp_hizmeti_yok')->default(false)->after('hekim_hizmeti_yok');
                }
                if (! Schema::hasColumn('firmalar', 'tam_sureli_igu')) {
                    $table->boolean('tam_sureli_igu')->default(false)->after('dsp_hizmeti_yok');
                }
                if (! Schema::hasColumn('firmalar', 'tam_sureli_hekim')) {
                    $table->boolean('tam_sureli_hekim')->default(false)->after('tam_sureli_igu');
                }
            });
        }

        if (! Schema::hasTable('denetim_kayitlari')) {
            Schema::create('denetim_kayitlari', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('kullanici_adi', 120)->nullable();
                $table->unsignedBigInteger('firma_id')->nullable()->index();
                $table->string('modul', 60)->index();
                $table->string('islem', 40)->index();
                $table->string('kayit_tipi', 80)->nullable();
                $table->unsignedBigInteger('kayit_id')->nullable();
                $table->string('ozet', 255);
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('ekip_duyurulari')) {
            Schema::create('ekip_duyurulari', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('baslik', 180);
                $table->text('icerik');
                $table->string('oncelik', 20)->default('normal');
                $table->json('okuyanlar')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ekip_duyurulari');
        Schema::dropIfExists('denetim_kayitlari');

        if (Schema::hasTable('firmalar')) {
            Schema::table('firmalar', function (Blueprint $table): void {
                $cols = array_filter(['igu_hizmeti_yok', 'hekim_hizmeti_yok', 'dsp_hizmeti_yok', 'tam_sureli_igu', 'tam_sureli_hekim'], fn ($c) => Schema::hasColumn('firmalar', $c));
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('risk_degerlendirmeleri')) {
            Schema::table('risk_degerlendirmeleri', function (Blueprint $table): void {
                $cols = array_filter(['kilitli_at', 'kilitli_by', 'onceki_versiyon_id'], fn ($c) => Schema::hasColumn('risk_degerlendirmeleri', $c));
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('saha_bulgulari')) {
            Schema::table('saha_bulgulari', function (Blueprint $table): void {
                $cols = array_filter(['kontrol_hiyerarsisi', 'etkinlik_puani', 'kapanis_kaniti_dosya', 'isveren_kapanis_talebi_at', 'isveren_kapanis_notu', 'isveren_kapanis_dosya', 'isveren_kapanis_gonderen'], fn ($c) => Schema::hasColumn('saha_bulgulari', $c));
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
