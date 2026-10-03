<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use App\Models\AcilEkip;
use App\Models\AtamaYazisi;
use App\Models\DofRaporu;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\IsEkipmani;
use App\Models\IsEkipmaniKontrolu;
use App\Models\KimyasalUrun;
use App\Models\KkdZimmet;
use App\Models\KurulToplantisi;
use App\Models\MeslekHastaligiBildirimi;
use App\Models\MuayeneFormu;
use App\Models\NaceKodu;
use App\Models\OlayKaydi;
use App\Models\OnayliDefterNushasi;
use App\Models\OrtamOlcumu;
use App\Models\RiskDegerlendirmesi;
use App\Models\SaglikGozetimi;
use App\Models\SahaDenetimi;
use App\Models\Taseron;
use App\Models\TatbikatTutanagi;
use App\Models\TespitOneriDefteri;
use App\Models\YillikPlan;
use App\Models\Ziyaretci;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * İSG Yıllık Değerlendirme Raporu (kullanıcının şablonu: config/
 * yillik_degerlendirme_sablonu.php) — 36 satırın "Tarih / Dönem" ve "Sonuç ve
 * yorum" alanlarını sistemdeki kayıtlardan doldurur.
 *
 * Değerlendirme dönemi: uzmanın atandığı tarih (firma sözleşme başlangıcı)
 * o yıl içindeyse o tarih, değilse 1 Ocak → yılın son günü (31.12). Yalnız bu
 * aralıktaki kayıtlar sayılır; kaydı olmayan satır şablon metniyle kalır
 * (rapor uydurma sonuç yazmaz — şablon notu: "yalnızca kayıtlarla doğrulanan
 * çalışmaları yazınız"). Sağlık verileri toplu yazılır, kişi adı / tanı yok.
 */
class YillikDegerlendirmeVerisi
{
    /** @return array{0: Carbon, 1: Carbon} */
    public static function donem(Firma $firma, int $yil): array
    {
        $bas = Carbon::create($yil, 1, 1)->startOfDay();
        $son = Carbon::create($yil, 12, 31)->endOfDay();
        $atama = $firma->sozlesme_baslangic;

        if ($atama && $atama->year === $yil) {
            $bas = $atama->copy()->startOfDay();
        }

        return [$bas, $son];
    }

    public static function donemMetni(Firma $firma, int $yil): string
    {
        [$b, $s] = static::donem($firma, $yil);

        return $b->format('d.m.Y').'–'.$s->format('d.m.Y');
    }

    /** Şablonun 36 satırı + 6 genel sonuç satırı (boş plan için). */
    public static function sablonSatirlari(): array
    {
        $satirlar = collect(config('yillik_degerlendirme_sablonu.satirlar'))->map(fn ($s) => [
            'tur' => 'calisma', 'anahtar' => $s['anahtar'], 'calisma' => $s['calisma'], 'tarih' => null,
            'yapan_kisi' => $s['yapan_kisi'], 'yontem' => $s['yontem'], 'sonuc' => $s['sonuc_sablonu'],
            'otomatik' => false, 'elle' => false,
        ]);

        $genel = collect(config('yillik_degerlendirme_sablonu.genel_sonuc'))->map(fn ($g, $i) => [
            'tur' => 'genel', 'anahtar' => 'genel_'.$i, 'calisma' => $g['baslik'], 'sonuc' => $g['sablon'], 'otomatik' => false, 'elle' => false,
        ]);

        return $satirlar->merge($genel)->values()->all();
    }

    /** 01.10.2026 öncesi 11 satırlık eski yapı (anahtar yok) mı? */
    public static function eskiYapidaMi(array $satirlar): bool
    {
        return $satirlar !== [] && ! collect($satirlar)->contains(fn ($s) => filled($s['anahtar'] ?? null));
    }

    /**
     * Plan satırlarını sistem verisiyle günceller. Elle düzenlenen satırlar
     * ($ustuneYaz = false iken) korunur; kaydı olmayan satıra dokunulmaz.
     *
     * @return array{satirlar: array<int, array<string, mixed>>, doldurulan: int}
     */
    public static function planiDoldur(Firma $firma, int $yil, array $satirlar, bool $ustuneYaz = false): array
    {
        if ($satirlar === [] || static::eskiYapidaMi($satirlar)) {
            $satirlar = static::sablonSatirlari();
        }

        $hesap = static::hesapla($firma, $yil);
        $doldurulan = 0;

        foreach ($satirlar as $i => $s) {
            $veri = $hesap[$s['anahtar'] ?? ''] ?? null;

            if (! $veri || (($s['elle'] ?? false) && ! $ustuneYaz)) {
                continue;
            }

            // Çıktılarda görevli adı yazılmaz (kaşe yeterli) — "Yapan kişi" şablondaki unvanla kalır.
            unset($veri['yapan_kisi']);
            $satirlar[$i] = [...$s, ...array_filter($veri, fn ($v) => $v !== null), 'otomatik' => true, 'elle' => false];
            $doldurulan++;
        }

        return ['satirlar' => array_values($satirlar), 'doldurulan' => $doldurulan];
    }

    /**
     * Her şablon satırı için sistemden hesaplanan alanlar.
     *
     * @return array<string, array{tarih?: ?string, sonuc: string, yapan_kisi?: ?string}>
     */
    public static function hesapla(Firma $firma, int $yil): array
    {
        [$bas, $son] = static::donem($firma, $yil);
        $sonuc = [];

        foreach (static::hesaplayicilar() as $anahtar => $fn) {
            try {
                $v = $fn($firma, $bas, $son, $yil);
            } catch (Throwable $e) {
                report($e);
                $v = null;
            }
            if ($v) {
                $sonuc[$anahtar] = $v;
            }
        }

        return [...$sonuc, ...static::genelSonuc($firma, $bas, $son, $sonuc)];
    }

    /** Başlık bloğu: çalışan sayıları (erkek / kadın / toplam / genç / çocuk), NACE ve dönem. */
    public static function kunye(Firma $firma, int $yil): array
    {
        [, $son] = static::donem($firma, $yil);
        $calisanlar = $firma->calisanlar()->where('aktif', true)->get(['cinsiyet', 'dogum_tarihi']);
        $yas = fn ($c) => $c->dogum_tarihi ? (int) $c->dogum_tarihi->diffInYears($son) : null;

        return [
            'erkek' => $calisanlar->where('cinsiyet', 'erkek')->count(),
            'kadin' => $calisanlar->where('cinsiyet', 'kadin')->count(),
            'toplam' => $calisanlar->count() ?: (int) $firma->calisan_sayisi,
            'genc' => $calisanlar->filter(fn ($c) => ($y = $yas($c)) !== null && $y >= 15 && $y < 18)->count(),
            'cocuk' => $calisanlar->filter(fn ($c) => ($y = $yas($c)) !== null && $y < 15)->count(),
            'nace' => trim(($firma->nace_kodu ?: '').' '.(NaceKodu::bul((string) $firma->nace_kodu)?->tanim ?? '')),
            'donem' => static::donemMetni($firma, $yil),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Yardımcılar
    |--------------------------------------------------------------------------
    */

    private static function t($c): ?string
    {
        return $c ? Carbon::parse($c)->format('d.m.Y') : null;
    }

    /** Tarih listesi → "03.05.2026" ya da "12.02.2026–20.11.2026". */
    private static function aralik(Collection $tarihler): ?string
    {
        $t = $tarihler->filter()->map(fn ($d) => Carbon::parse($d))->sort()->values();

        if ($t->isEmpty()) {
            return null;
        }

        return $t->first()->isSameDay($t->last()) ? static::t($t->first()) : static::t($t->first()).'–'.static::t($t->last());
    }

    private static function arada($tarih, Carbon $bas, Carbon $son): bool
    {
        return filled($tarih) && Carbon::parse($tarih)->betweenIncluded($bas, $son);
    }

    /** Sağlık gözetimi satırları (tetkik türlerine göre, dönem içinde). */
    private static function tetkikler(Firma $firma, Carbon $bas, Carbon $son, array $turler): Collection
    {
        $g = SaglikGozetimi::query()->where('firma_id', $firma->id)->first();

        return collect($g?->satirlar ?? [])->filter(fn ($s) => in_array($s['tetkik_turu'] ?? null, $turler, true) && static::arada($s['tarih'] ?? null, $bas, $son));
    }

    private static function tetkikSatiri(Firma $firma, Carbon $bas, Carbon $son, array $turler, string $metin): ?array
    {
        $t = static::tetkikler($firma, $bas, $son, $turler);
        if ($t->isEmpty()) {
            return null;
        }
        $kisi = $t->pluck('calisan_adi')->filter()->unique()->count() ?: $t->count();

        return ['tarih' => static::aralik($t->pluck('tarih')), 'sonuc' => sprintf($metin, $kisi, $t->whereIn('sonuc', ['sartli', 'uygun_degil'])->count())];
    }

    private static function muayeneSatiri(Firma $firma, Carbon $bas, Carbon $son, string $tur, string $metin): ?array
    {
        $formlar = MuayeneFormu::query()->where('firma_id', $firma->id)->where('muayene_turu', $tur)
            ->whereBetween('muayene_tarihi', [$bas, $son])->get(['calisan_ad_soyad', 'calisan_tc', 'muayene_tarihi', 'sonuc_kanaati', 'hekim_adi']);
        $tetkik = static::tetkikler($firma, $bas, $son, [$tur]);

        if ($formlar->isEmpty() && $tetkik->isEmpty()) {
            return null;
        }

        $kisiler = $formlar->map(fn ($f) => mb_strtolower($f->calisan_tc ?: (string) $f->calisan_ad_soyad))
            ->merge($tetkik->map(fn ($s) => mb_strtolower((string) $s['calisan_adi'])))->filter()->unique();
        $kisitli = $formlar->filter(fn ($f) => filled($f->sonuc_kanaati) && $f->sonuc_kanaati !== 'uygun')->count()
            + $tetkik->whereIn('sonuc', ['sartli', 'uygun_degil'])->count();
        $hekim = $formlar->pluck('hekim_adi')->filter()->unique()->first();

        return [
            'tarih' => static::aralik($formlar->pluck('muayene_tarihi')->merge($tetkik->pluck('tarih'))),
            'sonuc' => sprintf($metin, $kisiler->count() ?: $formlar->count(), $kisitli),
            'yapan_kisi' => $hekim ? $hekim.' (İşyeri Hekimi)' : null,
        ];
    }

    private static function ekipUyeleri(Firma $firma, array $turler): Collection
    {
        return AcilEkipDurumu::ekipler($firma)->whereIn('tur', $turler)->flatMap->uyeler;
    }

    private static function ekipmanSatiri(Firma $f, Carbon $bas, Carbon $son, array $kategoriler, string $metin): ?array
    {
        $ekipmanlar = IsEkipmani::query()->where('firma_id', $f->id)->where('aktif', true)->whereIn('kategori', $kategoriler)->get(['id', 'sonraki_vize_tarihi', 'son_muayene_tarihi', 'sonuc']);
        if ($ekipmanlar->isEmpty()) {
            return null;
        }
        $kontroller = IsEkipmaniKontrolu::query()->whereIn('is_ekipmani_id', $ekipmanlar->pluck('id'))->whereBetween('kontrol_tarihi', [$bas, $son])->get(['is_ekipmani_id', 'kontrol_tarihi', 'sonuc']);
        $muayeneli = $ekipmanlar->filter(fn ($e) => static::arada($e->son_muayene_tarihi, $bas, $son));
        $kontrolEdilen = $kontroller->pluck('is_ekipmani_id')->merge($muayeneli->pluck('id'))->unique()->count();
        $uygunsuz = $kontroller->where('sonuc', 'uygun_degil')->count() ?: $ekipmanlar->where('sonuc', 'uygun_degil')->count();
        $bekleyen = $ekipmanlar->filter(fn ($e) => ! $e->sonraki_vize_tarihi || Carbon::parse($e->sonraki_vize_tarihi)->lt(Carbon::today()))->count();

        if ($kontrolEdilen === 0 && $bekleyen === 0) {
            return null;
        }

        return ['tarih' => static::aralik($kontroller->pluck('kontrol_tarihi')->merge($muayeneli->pluck('son_muayene_tarihi'))), 'sonuc' => sprintf($metin, $kontrolEdilen, $uygunsuz, $bekleyen)];
    }

    /*
    |--------------------------------------------------------------------------
    | Satır hesaplayıcıları (anahtar = config satır anahtarı)
    |--------------------------------------------------------------------------
    */

    /** @return array<string, callable> */
    private static function hesaplayicilar(): array
    {
        return [
            'risk' => function (Firma $f, Carbon $bas, Carbon $son) {
                $rd = RiskDegerlendirmesi::query()->where('firma_id', $f->id)->whereBetween('rapor_tarihi', [$bas, $son])->latest('rapor_tarihi')->first();
                $yenilenmedi = false;
                if (! $rd) {
                    $rd = RiskDegerlendirmesi::query()->where('firma_id', $f->id)->where('rapor_tarihi', '<', $bas)
                        ->where(fn ($q) => $q->whereNull('gecerlilik_tarihi')->orWhere('gecerlilik_tarihi', '>=', $bas))->latest('rapor_tarihi')->first();
                    $yenilenmedi = (bool) $rd;
                }
                if (! $rd || ! $rd->rapor_tarihi) {
                    return null;
                }
                $o = RiskMerkeziVerisi::ozet($rd);
                $igu = $rd->igu?->ad_soyad ?? $f->igu?->ad_soyad;

                return [
                    'tarih' => static::t($rd->rapor_tarihi),
                    'sonuc' => 'Rapor tarihi: '.static::t($rd->rapor_tarihi).'; revizyon: '.($rd->revizyon_no ?: '00')
                        .' ('.config('isg.risk_yontemleri.'.$rd->yontem, $rd->yontem).'). İncelenen risk: '.$o['toplam'].' madde'
                        .'; yüksek / çok yüksek: '.($o['yuksek_acik'] + $o['cok_yuksek_acik']).'; açık tedbir: '.$o['aksiyon_acik']
                        .($o['geciken'] ? ' (termini geçen: '.$o['geciken'].')' : '').'.'
                        .($rd->gecerlilik_tarihi ? ' Geçerlilik: '.static::t($rd->gecerlilik_tarihi).'.' : '')
                        .($yenilenmedi ? ' Dönem içinde yenilenmedi; bu rapor geçerlidir.' : ''),
                    'yapan_kisi' => $igu ? $igu.' (İGU) / Risk değerlendirme ekibi' : null,
                ];
            },

            'ortam' => function (Firma $f, Carbon $bas, Carbon $son) {
                $m = collect(OrtamOlcumu::query()->where('firma_id', $f->id)->first()?->olcumler ?? [])
                    ->filter(fn ($x) => static::arada($x['olcum_tarihi'] ?? null, $bas, $son));
                if ($m->isEmpty()) {
                    return null;
                }
                $asim = $m->where('sonuc', 'asim');

                return [
                    'tarih' => static::aralik($m->pluck('olcum_tarihi')),
                    'sonuc' => $m->count().' noktada/kişide ölçüm yapıldı. Parametreler: '.$m->pluck('parametre')->filter()->unique()->implode(', ').'. '
                        .'Sınır değeri aşan sonuç: '.($asim->isEmpty() ? 'yok' : $asim->count().' ('.$asim->pluck('parametre')->unique()->implode(', ').')').'; sınıra yakın: '.$m->where('sonuc', 'sinir')->count().'.',
                    'yapan_kisi' => $m->pluck('laboratuvar')->filter()->unique()->implode(', ') ?: null,
                ];
            },

            'ise_giris' => fn (Firma $f, Carbon $b, Carbon $s) => static::muayeneSatiri($f, $b, $s, 'ise_giris', '%d kişinin işe giriş muayenesi yapıldı. İşe uygunluk ve çalışma kısıtları değerlendirildi. Şartlı / kısıtlı uygun: %d kişi.'),
            'periyodik' => fn (Firma $f, Carbon $b, Carbon $s) => static::muayeneSatiri($f, $b, $s, 'periyodik', '%d kişinin periyodik muayenesi yapıldı. Şartlı / kısıtlı uygun: %d kişi; muayene tarihleri sağlık gözetimi takibinde izlenmektedir.'),
            'radyolojik' => fn (Firma $f, Carbon $b, Carbon $s) => static::tetkikSatiri($f, $b, $s, ['akciger_grafisi'], 'Hekim değerlendirmesine göre %d kişiye inceleme yapıldı. İleri değerlendirme/sevk: %d kişi.'),
            'biyolojik' => fn (Firma $f, Carbon $b, Carbon $s) => static::tetkikSatiri($f, $b, $s, ['kan_tahlili', 'idrar_tahlili', 'hepatit', 'portor'], '%d kişiye gerekli biyolojik analizler yapıldı. Takip/sevk edilen: %d kişi.'),
            'fizyolojik' => fn (Firma $f, Carbon $b, Carbon $s) => static::tetkikSatiri($f, $b, $s, ['odyometri', 'sft', 'goz'], '%d kişiye işe ve maruziyete uygun test yapıldı. İleri değerlendirmeye alınan: %d kişi.'),
            'psikolojik' => fn (Firma $f, Carbon $b, Carbon $s) => static::tetkikSatiri($f, $b, $s, ['psikoteknik'], 'Değerlendirilen: %d kişi; takip gereken: %d kişi.'),

            'temel_egitim' => function (Firma $f, Carbon $bas, Carbon $son) {
                $e = EgitimKatilim::query()->where('firma_id', $f->id)->where('baslik_anahtari', 'genel')->whereBetween('belge_tarihi', [$bas, $son])->get();
                if ($e->isEmpty()) {
                    return null;
                }
                $kisiler = $e->flatMap(fn ($k) => collect($k->katilimcilar ?? []))->map(fn ($k) => mb_strtolower(($k['tc'] ?? '') ?: ($k['ad_soyad'] ?? '')))->filter();
                $aktif = $f->calisanlar()->where('aktif', true)->count();

                return [
                    'tarih' => static::aralik($e->pluck('belge_tarihi')),
                    'sonuc' => $kisiler->unique()->count().' çalışana eğitim verildi (toplam katılım: '.$kisiler->count().'). Eğitim oturumu: '.$e->count()
                        .' (ilk: '.$e->where('egitim_turu', 'ilk')->count().', tekrar: '.$e->where('egitim_turu', 'tekrar')->count().')'
                        .($aktif ? '; eksik eğitimi bulunan: '.max(0, $aktif - $kisiler->unique()->count()).' kişi.' : '.'),
                ];
            },

            'isbasi_egitim' => function (Firma $f, Carbon $bas, Carbon $son) {
                $t = IsbasiEgitimTutanagi::query()->where('firma_id', $f->id)->whereBetween('egitim_tarihi', [$bas, $son])->get(['egitim_tarihi', 'calisan_ad_soyad']);

                return $t->isEmpty() ? null : [
                    'tarih' => static::aralik($t->pluck('egitim_tarihi')),
                    'sonuc' => $t->pluck('calisan_ad_soyad')->filter()->unique()->count().' kişiye işe başlama/iş değişikliği eğitimi verildi ('.$t->count().' tutanak).',
                ];
            },

            'ise_ozgu_egitim' => function (Firma $f, Carbon $bas, Carbon $son, int $yil) {
                $konular = collect(YillikPlan::query()->where('firma_id', $f->id)->where('yil', $yil)->first()?->egitimler ?? [])->where('kategori', 'ise_ozgu');
                if ($konular->isEmpty()) {
                    return null;
                }

                return [
                    'tarih' => static::donemMetni($f, $yil),
                    'sonuc' => 'Konular: '.$konular->pluck('konu')->implode(', ').'. Yıllık eğitim planında '.$konular->count().' konu; gerçekleşen (G): '
                        .$konular->filter(fn ($k) => in_array('tamamlandi', $k['aylar'] ?? [], true))->count().'.',
                ];
            },

            'acil_plan' => function (Firma $f) {
                $p = AcilDurumPlani::query()->where('firma_id', $f->id)->first();
                $ekipler = AcilEkipDurumu::ekipler($f);
                if (! $p?->rapor_tarihi && $ekipler->isEmpty()) {
                    return null;
                }
                $o = $ekipler->isNotEmpty() ? AcilEkipDurumu::ozet($f, $ekipler) : null;

                return [
                    'tarih' => static::t($p?->rapor_tarihi),
                    'sonuc' => ($p?->rapor_tarihi ? 'Plan tarihi: '.static::t($p->rapor_tarihi).($p->gecerlilik_tarihi ? '; geçerlilik: '.static::t($p->gecerlilik_tarihi) : '').'. ' : 'Acil durum planı tarihi girilmemiş. ')
                        .($o ? 'Ekip görevlendirmeleri gözden geçirildi: '.$o['ekip'].' ekip, '.$o['uye'].' destek elemanı (lider: '.$o['lider'].'). Yetersiz ekip: '.($o['ekip'] - $o['tam']).'.' : 'Ekip görevlendirmesi kaydı yok.'),
                ];
            },

            'yangin_egitim' => function (Firma $f, Carbon $bas, Carbon $son) {
                $u = static::ekipUyeleri($f, ['sondurme', 'kurtarma', 'koruma', 'tahliye']);
                if ($u->isEmpty()) {
                    return null;
                }
                $donemde = $u->filter(fn ($x) => static::arada($x->belge_tarihi, $bas, $son));

                return [
                    'tarih' => static::aralik($donemde->pluck('belge_tarihi')),
                    'sonuc' => $donemde->count().' kişiye eğitim verildi (belge tarihi dönem içinde). Söndürme, kurtarma ve koruma görevleri için eksik eğitim: '
                        .$u->filter(fn ($x) => in_array($x->belgeDurumu(), ['dolmus', 'yok'], true))->count().' kişi.',
                ];
            },

            'ilkyardim' => function (Firma $f, Carbon $bas, Carbon $son) {
                $u = static::ekipUyeleri($f, ['ilk_yardim']);
                if ($u->isEmpty()) {
                    return null;
                }
                $gerekli = AcilEkip::yasalMinimum('ilk_yardim', $f->tehlike_sinifi, AcilEkipDurumu::calisanSayisi($f));
                $gecerli = $u->filter(fn ($x) => in_array($x->belgeDurumu(), ['gecerli', 'yaklasan'], true))->count();
                $yeni = $u->filter(fn ($x) => static::arada($x->belge_tarihi, $bas, $son));

                return [
                    'tarih' => static::aralik($yeni->pluck('belge_tarihi')),
                    'sonuc' => 'Geçerli belgeli ilkyardımcı: '.$gecerli.' kişi. Dönem içinde yeni / güncellenen belge: '.$yeni->count()
                        .'; ihtiyaç: '.max(0, $gerekli - $gecerli).' kişi (asgari '.$gerekli.').',
                ];
            },

            'tatbikat' => function (Firma $f, Carbon $bas, Carbon $son) {
                $t = TatbikatTutanagi::query()->where('firma_id', $f->id)->whereIn('durum', TatbikatTutanagi::YAPILMIS)
                    ->whereBetween('tatbikat_tarihi', [$bas, $son])->orderBy('tatbikat_tarihi')->get();
                if ($t->isEmpty()) {
                    return null;
                }

                return [
                    'tarih' => static::aralik($t->pluck('tatbikat_tarihi')),
                    'sonuc' => $t->map(fn ($x) => static::t($x->tatbikat_tarihi).' tarihinde '.$x->senaryoEtiketi().' yapıldı (katılımcı: '.$x->katilimciSayisi().')')->implode('; ').'.'
                        .($t->where('durum', 'takip')->isNotEmpty() ? ' Takip gerektiren tatbikat: '.$t->where('durum', 'takip')->count().'.' : ''),
                ];
            },

            'yangin_ekipman' => fn (Firma $f, Carbon $b, Carbon $s) => static::ekipmanSatiri($f, $b, $s, ['tesisat_yangin'], '%d ekipman/sistem kontrol edildi. Eksik veya arızalı: %d; kontrol bekleyen: %d.'),
            'periyodik_kontrol' => fn (Firma $f, Carbon $b, Carbon $s) => static::ekipmanSatiri($f, $b, $s, ['kaldirma_iletme', 'basincli_kap'], '%d ekipmanın kontrolü yapıldı. Uygunsuz rapor: %d; kontrol bekleyen: %d.'),
            'elektrik' => fn (Firma $f, Carbon $b, Carbon $s) => static::ekipmanSatiri($f, $b, $s, ['elektrik_topraklama'], 'Kontrol edilen tesisat / sistem: %d. Uygunsuz: %d; kontrol bekleyen: %d.'),

            'saha' => function (Firma $f, Carbon $bas, Carbon $son) {
                $d = SahaDenetimi::query()->where('firma_id', $f->id)->whereBetween('denetim_tarihi', [$bas, $son])->get();
                if ($d->isEmpty()) {
                    return null;
                }
                $ort = $d->pluck('uygunluk_yuzdesi')->filter(fn ($v) => $v !== null)->avg();

                return [
                    'tarih' => static::aralik($d->pluck('denetim_tarihi')),
                    'sonuc' => $d->count().' saha denetimi yapıldı.'.($ort !== null ? ' Ortalama uygunluk: %'.round($ort).'.' : '')
                        .' Kritik uygunsuzluk tespit edilen denetim: '.$d->where('kritik_uygunsuzluk_var', true)->count().'.',
                ];
            },

            'dof' => function (Firma $f, Carbon $bas, Carbon $son) {
                $r = DofRaporu::query()->where('firma_id', $f->id)->whereBetween('rapor_tarihi', [$bas, $son])->get();
                $m = $r->flatMap(fn ($x) => collect($x->maddeler ?? []));
                if ($m->isEmpty()) {
                    return null;
                }
                $kapali = $m->where('durum', 'tamamlandi')->count();

                return [
                    'tarih' => static::aralik($r->pluck('rapor_tarihi')),
                    'sonuc' => $m->count().' DÖF açıldı; '.$kapali.' DÖF kapatıldı. Devreden açık DÖF: '.($m->count() - $kapali).' (ertelenen: '.$m->where('durum', 'ertelendi')->count().').',
                ];
            },

            'defter' => function (Firma $f, Carbon $bas, Carbon $son) {
                $n = OnayliDefterNushasi::query()->where('firma_id', $f->id)->whereBetween('onay_tarihi', [$bas, $son])->get(['onay_tarihi']);
                $madde = count(TespitOneriDefteri::query()->where('firma_id', $f->id)->first()?->maddeler ?? []);
                if ($n->isEmpty() && $madde === 0) {
                    return null;
                }

                return [
                    'tarih' => static::aralik($n->pluck('onay_tarihi')),
                    'sonuc' => $n->count().' onaylı defter nüshası dönem içinde kayda alındı. Tespit-öneri defterindeki kayıtlı madde: '.$madde.'.',
                ];
            },

            'is_kazasi' => function (Firma $f, Carbon $bas, Carbon $son) {
                $o = OlayKaydi::query()->where('firma_id', $f->id)->where('olay_tipi', 'is_kazasi')->whereBetween('olay_tarihi', [$bas, $son])->get();
                if ($o->isEmpty()) {
                    return null;
                }

                return [
                    'tarih' => static::aralik($o->pluck('olay_tarihi')),
                    'sonuc' => 'İş kazası: '.$o->count().'; yaralanan kişi: '.$o->pluck('etkilenen_ad_soyad')->filter()->count().'; kayıp iş günü: '.(int) $o->sum('kayip_gun_sayisi').'.'
                        .' SGK bildirimi yapılan: '.$o->where('sgk_bildirimi_yapildi', true)->count().'; kök neden analizi yapılan: '.$o->filter(fn ($x) => filled($x->kok_neden))->count().'.',
                ];
            },

            'ramak_kala' => function (Firma $f, Carbon $bas, Carbon $son) {
                $o = OlayKaydi::query()->where('firma_id', $f->id)->whereIn('olay_tipi', ['ramak_kala', 'tehlikeli_durum', 'tehlikeli_davranis'])->whereBetween('olay_tarihi', [$bas, $son])->get();
                if ($o->isEmpty()) {
                    return null;
                }

                return [
                    'tarih' => static::aralik($o->pluck('olay_tarihi')),
                    'sonuc' => $o->count().' ramak kala/tehlike bildirimi alındı (ramak kala: '.$o->where('olay_tipi', 'ramak_kala')->count().'). Kapatılan: '
                        .$o->where('durum', 'kapali')->count().'; açık konu: '.$o->where('durum', '!=', 'kapali')->count().'.',
                ];
            },

            'meslek_hastaligi' => function (Firma $f, Carbon $bas, Carbon $son) {
                $m = MeslekHastaligiBildirimi::query()->where('firma_id', $f->id)->whereBetween('ogrenme_tarihi', [$bas, $son])->get();
                $supheli = OlayKaydi::query()->where('firma_id', $f->id)->where('olay_tipi', 'meslek_hastaligi_supheli')->whereBetween('olay_tarihi', [$bas, $son])->count();
                if ($m->isEmpty() && $supheli === 0) {
                    return null;
                }

                return [
                    'tarih' => static::aralik($m->pluck('ogrenme_tarihi')),
                    'sonuc' => 'Şüphe nedeniyle sevk / kayıt: '.($supheli + $m->count()).' kişi; tanısı bildirilen: '.$m->filter(fn ($x) => filled($x->tani_tarihi))->count()
                        .' kişi (SGK bildirimi: '.$m->where('sgk_bildirimi_yapildi', true)->count().').',
                ];
            },

            'kkd' => function (Firma $f, Carbon $bas, Carbon $son) {
                $z = KkdZimmet::query()->where('firma_id', $f->id)->whereBetween('teslim_tarihi', [$bas, $son])->get();
                if ($z->isEmpty()) {
                    return null;
                }

                return [
                    'tarih' => static::aralik($z->pluck('teslim_tarihi')),
                    'sonuc' => $z->pluck('personel_ad_soyad')->filter()->unique()->count().' kişiye KKD teslimi/yenilemesi yapıldı ('.(int) $z->sum('adet').' adet; türler: '
                        .$z->pluck('tur')->filter()->unique()->take(8)->implode(', ').'). Kayıp / hasarlı: '.$z->whereIn('durum', ['kayip', 'hasarli'])->count().'.',
                ];
            },

            'kimyasal' => function (Firma $f) {
                $k = KimyasalUrun::query()->where('firma_id', $f->id)->where('aktif', true)->get();
                if ($k->isEmpty()) {
                    return null;
                }

                return [
                    'sonuc' => $k->count().' kimyasal incelendi. Eksik GBF: '.$k->filter(fn ($x) => blank($x->sds_dosya_yolu))->count()
                        .'; gözden geçirme tarihi geçen GBF: '.$k->filter(fn ($x) => $x->sonraki_gozden_gecirme && Carbon::parse($x->sonraki_gozden_gecirme)->isPast())->count().'.',
                ];
            },

            'ergonomi' => function (Firma $f) {
                $c = $f->calisanlar()->where('aktif', true)->whereNotNull('ozel_durum')->where('ozel_durum', '!=', '')->pluck('ozel_durum');

                return $c->isEmpty() ? null : [
                    'sonuc' => 'Özel politika gerektiren grupta '.$c->count().' çalışan kayıtlı ('.$c->countBy()->map(fn ($n, $d) => $d.': '.$n)->implode(', ').'). Uyarlama yapılan: … ; ergonomik veya koruyucu önlem: … .',
                ];
            },

            'kurul' => function (Firma $f, Carbon $bas, Carbon $son) {
                $k = KurulToplantisi::query()->where('firma_id', $f->id)->where('durum', 'tamamlandi')->whereBetween('tarih', [$bas, $son])->get();
                if ($k->isEmpty()) {
                    return null;
                }

                return [
                    'tarih' => static::aralik($k->pluck('tarih')),
                    'sonuc' => $k->count().' toplantı yapıldı; '.$k->flatMap(fn ($x) => collect($x->kararlar ?? []))->count().' karar alındı.',
                ];
            },

            'alt_isveren' => function (Firma $f, Carbon $bas, Carbon $son) {
                $t = Taseron::query()->where('firma_id', $f->id)->where('aktif', true)->count();
                $z = Ziyaretci::query()->where('firma_id', $f->id)->whereBetween('giris_zamani', [$bas, $son])->get(['isg_bilgilendirme']);
                if ($t === 0 && $z->isEmpty()) {
                    return null;
                }

                return ['sonuc' => 'Kayıtlı aktif alt işveren: '.$t.'. Dönem içinde giriş yapan ziyaretçi: '.$z->count().' (İSG bilgilendirmesi yapılan: '.$z->where('isg_bilgilendirme', true)->count().').'];
            },

            'gorevlendirme' => function (Firma $f, Carbon $bas, Carbon $son) {
                $a = AtamaYazisi::query()->where('firma_id', $f->id)->get(['rol_anahtari', 'tarih']);
                if ($a->isEmpty()) {
                    return null;
                }
                $donemde = $a->filter(fn ($x) => static::arada($x->tarih, $bas, $son));

                return [
                    'tarih' => static::aralik($donemde->pluck('tarih')),
                    'sonuc' => 'Görevlendirme kayıtları incelendi: '.$a->count().' yazı ('.$a->pluck('rol_anahtari')->unique()->map(fn ($r) => config('isg.atama.roller.'.$r.'.ad', $r))->implode(', ')
                        .'). Dönem içinde düzenlenen: '.$donemde->count().'.',
                ];
            },

            'gerceklesmeyen' => function (Firma $f, Carbon $bas, Carbon $son, int $yil) {
                $plan = YillikPlan::query()->where('firma_id', $f->id)->where('yil', $yil)->first();
                if (! $plan) {
                    return null;
                }
                $sonAy = Carbon::today()->year > $yil ? 11 : Carbon::today()->month - 1;
                $ilk = $bas->year === $yil ? $bas->month - 1 : 0;
                $kalan = collect($plan->faaliyetler ?? [])->filter(fn ($fa) => $sonAy >= $ilk
                    && in_array('planlandi', array_slice($fa['aylar'] ?? [], $ilk, $sonAy - $ilk + 1), true));

                return [
                    'tarih' => static::donemMetni($f, $yil),
                    'sonuc' => $kalan->isEmpty()
                        ? 'Yıllık çalışma planında geçmiş aylara ait gerçekleşmemiş (G işaretsiz) faaliyet yok.'
                        : 'Gerçekleştirilemeyen/kısmi çalışma: '.$kalan->map(fn ($x) => $x['faaliyet'] ?? $x['ana_konu'] ?? '')->filter()->take(4)->implode('; ')
                            .($kalan->count() > 4 ? ' … (toplam '.$kalan->count().')' : '').'. Gerekçe: … ; yeni termin ve sorumlu: … .',
                ];
            },
        ];
    }

    /** 7. sayfa: eğitim/sağlık, kaza ve gerçekleşmeyen satırlarının sayısal kısmı. */
    private static function genelSonuc(Firma $f, Carbon $bas, Carbon $son, array $hesap): array
    {
        $sonuc = [];

        $e = EgitimKatilim::query()->where('firma_id', $f->id)->whereBetween('belge_tarihi', [$bas, $son])->get();
        $kisiler = $e->flatMap(fn ($k) => collect($k->katilimcilar ?? []))->map(fn ($k) => mb_strtolower(($k['tc'] ?? '') ?: ($k['ad_soyad'] ?? '')))->filter();
        $giris = MuayeneFormu::query()->where('firma_id', $f->id)->where('muayene_turu', 'ise_giris')->whereBetween('muayene_tarihi', [$bas, $son])->count();
        $periyodik = MuayeneFormu::query()->where('firma_id', $f->id)->where('muayene_turu', 'periyodik')->whereBetween('muayene_tarihi', [$bas, $son])->count();
        if ($kisiler->isNotEmpty() || $giris || $periyodik) {
            $sonuc['genel_1'] = ['sonuc' => 'Eğitime katılan farklı çalışan: '.$kisiler->unique()->count().' kişi; toplam katılım: '.$kisiler->count().'. İşe giriş muayenesi: '
                .$giris.' kişi; periyodik muayene: '.$periyodik.' kişi. Tamamlanacak işlemler: … .'];
        }

        $kaza = OlayKaydi::query()->where('firma_id', $f->id)->where('olay_tipi', 'is_kazasi')->whereBetween('olay_tarihi', [$bas, $son])->get(['kayip_gun_sayisi', 'kok_neden']);
        $ramak = OlayKaydi::query()->where('firma_id', $f->id)->where('olay_tipi', 'ramak_kala')->whereBetween('olay_tarihi', [$bas, $son])->count();
        $sonuc['genel_2'] = ['sonuc' => 'İş kazası: '.$kaza->count().'; ramak kala: '.$ramak.'; kayıp iş günü: '.(int) $kaza->sum('kayip_gun_sayisi').'. Öne çıkan kök nedenler: '
            .($kaza->pluck('kok_neden')->filter()->take(3)->implode('; ') ?: '…').'. Tekrarını önlemek için kararlar: … .'];

        if (isset($hesap['gerceklesmeyen'])) {
            $sonuc['genel_4'] = ['sonuc' => $hesap['gerceklesmeyen']['sonuc']];
        }

        return $sonuc;
    }
}
