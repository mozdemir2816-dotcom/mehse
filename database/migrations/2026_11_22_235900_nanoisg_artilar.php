<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ziyaretciler')) {
            Schema::table('ziyaretciler', function (Blueprint $table): void {
                if (! Schema::hasColumn('ziyaretciler', 'egitim_zorunlu')) {
                    $table->boolean('egitim_zorunlu')->default(true)->after('isg_bilgilendirme');
                }
                if (! Schema::hasColumn('ziyaretciler', 'brifing_video_url')) {
                    $table->string('brifing_video_url', 255)->nullable()->after('egitim_zorunlu');
                }
                if (! Schema::hasColumn('ziyaretciler', 'egitim_tamamlandi_at')) {
                    $table->timestamp('egitim_tamamlandi_at')->nullable()->after('brifing_video_url');
                }
                if (! Schema::hasColumn('ziyaretciler', 'test_puani')) {
                    $table->unsignedTinyInteger('test_puani')->nullable()->after('egitim_tamamlandi_at');
                }
                if (! Schema::hasColumn('ziyaretciler', 'test_cevaplari')) {
                    $table->json('test_cevaplari')->nullable()->after('test_puani');
                }
            });
        }

        if (! Schema::hasTable('yoklama_kayitlari')) {
            Schema::create('yoklama_kayitlari', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('firma_id')->index();
                $table->unsignedBigInteger('calisan_id')->index();
                $table->date('tarih')->index();
                $table->string('durum', 24)->default('mevcut');
                $table->string('aciklama', 200)->nullable();
                $table->string('kaydeden', 120)->nullable();
                $table->timestamps();
                $table->unique(['firma_id', 'calisan_id', 'tarih'], 'yoklama_firma_calisan_tarih_uniq');
            });
        }

        if (Schema::hasTable('calisanlar')) {
            Schema::table('calisanlar', function (Blueprint $table): void {
                if (! Schema::hasColumn('calisanlar', 'rol_etiketleri')) {
                    $table->json('rol_etiketleri')->nullable()->after('ozel_durum');
                }
                if (! Schema::hasColumn('calisanlar', 'myk_belge_no')) {
                    $table->string('myk_belge_no', 80)->nullable()->after('rol_etiketleri');
                }
                if (! Schema::hasColumn('calisanlar', 'myk_gecerlilik')) {
                    $table->date('myk_gecerlilik')->nullable()->after('myk_belge_no');
                }
                if (! Schema::hasColumn('calisanlar', 'src_gecerlilik')) {
                    $table->date('src_gecerlilik')->nullable()->after('myk_gecerlilik');
                }
                if (! Schema::hasColumn('calisanlar', 'psikoteknik_gecerlilik')) {
                    $table->date('psikoteknik_gecerlilik')->nullable()->after('src_gecerlilik');
                }
                if (! Schema::hasColumn('calisanlar', 'operatorluk_gecerlilik')) {
                    $table->date('operatorluk_gecerlilik')->nullable()->after('psikoteknik_gecerlilik');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('calisanlar')) {
            Schema::table('calisanlar', function (Blueprint $table): void {
                $cols = array_filter(['rol_etiketleri', 'myk_belge_no', 'myk_gecerlilik', 'src_gecerlilik', 'psikoteknik_gecerlilik', 'operatorluk_gecerlilik'], fn ($c) => Schema::hasColumn('calisanlar', $c));
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('yoklama_kayitlari');

        if (Schema::hasTable('ziyaretciler')) {
            Schema::table('ziyaretciler', function (Blueprint $table): void {
                $cols = array_filter(['egitim_zorunlu', 'brifing_video_url', 'egitim_tamamlandi_at', 'test_puani', 'test_cevaplari'], fn ($c) => Schema::hasColumn('ziyaretciler', $c));
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
