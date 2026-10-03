<?php

namespace App\Support;

use App\Filament\Pages\AcilDurumPlani as AcilDurumPlaniSayfasi;
use App\Filament\Pages\BildirimMerkezi;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\DokumanYonetimi;
use App\Filament\Pages\EgitimYenilemeTakibi;
use App\Filament\Pages\KimyasalSicili;
use App\Filament\Pages\KkdTakip;
use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\KurulToplantisi as KurulToplantisiSayfasi;
use App\Filament\Pages\OlayKayitlari;
use App\Filament\Pages\OrtamOlcumleri;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Pages\PkdSicili;
use App\Filament\Pages\SaglikGozetimi as SaglikGozetimiSayfasi;
use App\Filament\Pages\TatbikatTutanagi as TatbikatSayfasi;
use App\Filament\Pages\YillikPlan\YillikCalismaPlani;
use App\Filament\Resources\Calisans\CalisanResource;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\AcilDurumPlani;
use App\Models\ArsivDosya;
use App\Models\Bildirim;
use App\Models\DofRaporu;
use App\Models\EgitimAtamasi;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\KimyasalUrun;
use App\Models\KkdZimmet;
use App\Models\KurulToplantisi;
use App\Models\OlayKaydi;
use App\Models\OrtamOlcumu;
use App\Models\PkdKaydi;
use App\Models\RiskDegerlendirmesi;
use App\Models\SaglikGozetimi;
use App\Models\TatbikatTutanagi;
use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * İşyeri Durum Merkezi (isgsuite "İşyeri Durum Merkezi / Firma 360"): bir
 * işyerinin tüm İSG süreçlerini gerçek kayıtlardan tek ekranda özetler —
 * süreç tablosu (durum, gerçek veri sonucu, sorumlu, modül), yükümlülük
 * takvimi (termin listesi), göstergeler, görevlendirmeler, ziyaret ve olaylar.
 * Salt okunurdur. Sağlık verisi yalnız sayı olarak verilir (ad / tetkik yok).
 */
class IsyeriDurumu
{
    public const DURUMLAR = [
        'tamamlandi' => 'Tamamlandı',
        'eksik' => 'Eksik',
        'gecikmis' => 'Gecikmiş',
        'yaklasan' => 'Yaklaşıyor',
        'bilgi' => 'Bilgi',
    ];

    public const TAKVIM_DURUMLARI = [
        'gecikmis' => 'Gecikmiş',
        'cok_yakin' => 'Çok yakın (0–7 gün)',
        'yaklasiyor' => 'Yaklaşıyor (8–30 gün)',
        'ileri' => 'İleri tarihli',
        'tamamlandi' => 'Tamamlandı',
    ];

    private const YAKIN_GUN = 30;

    /**
     * @return array<int, array{surec: string, durum: string, sonuc: string, sorumlu: string, url: ?string}>
     */
    public static function surecler(Firma $firma): array
    {
        $bugun = Carbon::today();
        $sinir = $bugun->copy()->addDays(static::YAKIN_GUN);
        $satirlar = [];
        $ekle = function (string $surec, string $durum, string $sonuc, string $sorumlu, ?callable $url) use (&$satirlar): void {
            $satirlar[] = ['surec' => $surec, 'durum' => $durum, 'sonuc' => $sonuc, 'sorumlu' => $sorumlu, 'url' => static::url($url)];
        };
        $sayac = fn (Collection $tarihler) => [
            $tarihler->filter(fn (Carbon $t) => $t->lt($bugun))->count(),
            $tarihler->filter(fn (Carbon $t) => $t->gte($bugun) && $t->lte($sinir))->count(),
        ];
        $durumBul = fn (bool $kayitVar, int $gecikmis, int $yakin, string $yoksa = 'eksik') => match (true) {
            $gecikmis > 0 => 'gecikmis',
            $yakin > 0 => 'yaklasan',
            $kayitVar => 'tamamlandi',
            default => $yoksa,
        };

        // 1. Profesyonel görevlendirmeleri
        $gorevli = collect([$firma->igu_id, $firma->isyeri_hekimi_id, $firma->dsp_id])->filter()->count();
        $eksikRol = array_filter(['iş güvenliği uzmanı' => ! $firma->igu_id, 'işyeri hekimi' => ! $firma->isyeri_hekimi_id]);
        $ekle('İSG profesyoneli görevlendirmeleri', $eksikRol ? 'eksik' : 'tamamlandi',
            $gorevli.' aktif görevlendirme bulunuyor.'.($eksikRol ? ' Atanmamış: '.implode(', ', array_keys($eksikRol)).'.' : ''),
            'İşveren / OSGB yöneticisi', fn () => FirmaResource::getUrl('edit', ['record' => $firma]));

        // 2. Çalışan kayıtları
        $aktif = $firma->calisanlar()->where('aktif', true)->count();
        $bildirilen = (int) $firma->calisan_sayisi;
        $ekle('Çalışan kayıtları', $aktif > 0 ? 'tamamlandi' : 'eksik',
            $aktif.' aktif çalışan kayıtlı'.($bildirilen > $aktif ? " (firma kaydında {$bildirilen} çalışan bildirilmiş)" : '').'.',
            'İşveren / OSGB yöneticisi', fn () => CalisanResource::getUrl('index'));

        // 3-4. Risk değerlendirmesi + yenileme
        $rdSayi = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->count();
        $rd = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->whereNotNull('gecerlilik_tarihi')->latest('gecerlilik_tarihi')->first();
        $rdUrl = fn () => RiskDegerlendirmesiResource::getUrl('index');
        $ekle('Risk değerlendirmesi', $rdSayi ? 'tamamlandi' : 'eksik',
            $rdSayi ? $rdSayi.' risk değerlendirmesi kaydı var.' : 'Risk değerlendirmesi kaydı bulunamadı.', 'İş güvenliği uzmanı', $rdUrl);
        $yil = config('isg.risk_gecerlilik_yili.'.$firma->tehlike_sinifi, 4);
        $ekle('Risk değerlendirmesi yenileme süresi',
            ! $rd ? 'eksik' : $durumBul(true, (int) $rd->gecerlilik_tarihi->lt($bugun), (int) ($rd->gecerlilik_tarihi->gte($bugun) && $rd->gecerlilik_tarihi->lte($sinir))),
            $rd ? 'Geçerlilik '.$rd->gecerlilik_tarihi->format('d.m.Y').' ('.static::kalanMetni($rd->gecerlilik_tarihi).').'
                : "Risk değerlendirmesi tarihi girilmemiş. Bu işyeri {$yil} yılda bir yenileme kapsamındadır; belge tarihini girince yenileme takibi başlar.",
            'İş güvenliği uzmanı / İşveren', $rdUrl);

        // 5. Acil durum planı
        $adp = AcilDurumPlani::query()->where('firma_id', $firma->id)->first();
        [$g, $y] = $sayac(collect([$adp?->gecerlilik_tarihi])->filter());
        $ekle('Acil durum planı', $durumBul((bool) $adp, $g, $y),
            ($adp ? 1 : 0).' aktif plan; '.$g.' gecikmiş, '.$y.' yaklaşan revizyon.'.($adp?->gecerlilik_tarihi ? ' Geçerlilik '.$adp->gecerlilik_tarihi->format('d.m.Y').'.' : ''),
            'İş güvenliği uzmanı / İşveren', fn () => AcilDurumPlaniSayfasi::getUrl(['firma' => $firma->id]));

        // 6. Eğitimler
        $egitim = EgitimTakibi::ozet(EgitimTakibi::satirlar($firma));
        $katilim = EgitimKatilim::query()->where('firma_id', $firma->id)->count();
        $uzaktanAcik = EgitimAtamasi::query()->whereHas('calisan', fn ($q) => $q->where('firma_id', $firma->id))->where('durum', '!=', 'tamamlandi')->count();
        $ekle('İSG eğitimleri ve yenilemeler',
            match (true) {
                $egitim['dolmus'] > 0 => 'gecikmis',
                $egitim['kayit_yok'] > 0 => 'eksik',
                $egitim['yaklasan'] > 0 => 'yaklasan',
                $egitim['toplam'] > 0 => 'tamamlandi',
                default => 'bilgi',
            },
            "{$katilim} yüz yüze eğitim formu, {$uzaktanAcik} açık uzaktan eğitim; {$egitim['dolmus']} süresi dolmuş, {$egitim['yaklasan']} yaklaşan, {$egitim['kayit_yok']} kayıtsız çalışan.",
            'İş güvenliği uzmanı / İşyeri hekimi', fn () => EgitimYenilemeTakibi::getUrl(['firma' => $firma->id]));

        // 7. Sağlık gözetimi (yalnız sayı)
        $sg = collect(SaglikGozetimi::query()->where('firma_id', $firma->id)->value('satirlar') ?? []);
        [$g, $y] = $sayac($sg->pluck('sonraki_tarih')->filter()->map(fn ($t) => static::tarih($t))->filter());
        $ekle('Sağlık gözetimi', $durumBul($sg->isNotEmpty(), $g, $y),
            $sg->count().' muayene kaydı; '.$g.' gecikmiş, '.$y.' yaklaşan. Kişisel sağlık detayı gösterilmez.',
            'İşyeri hekimi', fn () => SaglikGozetimiSayfasi::getUrl(['firma' => $firma->id]));

        // 8. DÖF
        $dofMaddeler = DofRaporu::query()->where('firma_id', $firma->id)->get()->flatMap(fn (DofRaporu $r) => $r->maddeler ?? []);
        $acikDof = $dofMaddeler->where('durum', '!=', 'tamamlandi');
        [$g, $y] = $sayac($acikDof->pluck('termin')->filter()->map(fn ($t) => static::tarih($t))->filter());
        $ekle('Düzeltici ve önleyici faaliyetler', $dofMaddeler->isEmpty() ? 'bilgi' : $durumBul(true, $g, $y),
            $dofMaddeler->isEmpty() ? 'Henüz DÖF kaydı bulunmuyor.' : $acikDof->count().' açık / '.$dofMaddeler->count().' toplam madde; '.$g.' gecikmiş, '.$y.' yaklaşan termin.',
            'Kayıt sorumlusu / İşveren', fn () => DofOlustur::getUrl(['firma' => $firma->id]));

        // 9. KKD
        $zimmet = KkdZimmet::query()->where('firma_id', $firma->id)->where('durum', 'teslim_edildi')->get();
        [$g, $y] = $sayac($zimmet->map(fn (KkdZimmet $z) => $z->vade())->filter());
        $ekle('KKD değişim ve kullanım süreleri', $durumBul($zimmet->isNotEmpty(), $g, $y),
            $zimmet->count().' aktif zimmet; '.$g.' gecikmiş, '.$y.' yaklaşan değişim / son kullanım tarihi.',
            'İşveren / İş güvenliği uzmanı', fn () => KkdTakip::getUrl(['firma' => $firma->id]));

        // 10. SDS / kimyasal
        $kimyasal = KimyasalUrun::query()->where('firma_id', $firma->id)->where('aktif', true)->get(['sds_dosya_yolu', 'sonraki_gozden_gecirme']);
        [$g, $y] = $sayac($kimyasal->pluck('sonraki_gozden_gecirme')->filter()->map(fn ($t) => static::tarih($t))->filter());
        $sdsEksik = $kimyasal->whereNull('sds_dosya_yolu')->count();
        $ekle('SDS / kimyasal belge takibi', $kimyasal->isEmpty() ? 'bilgi' : ($sdsEksik > 0 && $g === 0 ? 'eksik' : $durumBul(true, $g, $y)),
            $kimyasal->count().' aktif kimyasal; '.$sdsEksik.' SDS belgesi eksik, '.$g.' gecikmiş, '.$y.' yaklaşan gözden geçirme.',
            'İşveren / İş güvenliği uzmanı', fn () => KimyasalSicili::getUrl(['firma' => $firma->id]));

        // 11. Tatbikat (yılda en az bir)
        $sonTatbikat = TatbikatTutanagi::query()->where('firma_id', $firma->id)->whereNotNull('tatbikat_tarihi')->max('tatbikat_tarihi');
        $tatbikatSayi = TatbikatTutanagi::query()->where('firma_id', $firma->id)->count();
        $sonraki = $sonTatbikat ? Carbon::parse($sonTatbikat)->addYear() : null;
        [$g, $y] = $sayac(collect([$sonraki])->filter());
        $ekle('Acil durum tatbikatları', $durumBul((bool) $sonTatbikat, $g, $y),
            $tatbikatSayi.' tatbikat kaydı'.($sonraki ? '; sonraki tatbikat '.$sonraki->format('d.m.Y').' ('.static::kalanMetni($sonraki).')' : '').'.',
            'İş güvenliği uzmanı / İşveren', fn () => TatbikatSayfasi::getUrl(['firma' => $firma->id]));

        // 12. Periyodik kontroller
        $ekipman = IsEkipmani::query()->where('firma_id', $firma->id)->where('aktif', true)->get(['sonraki_vize_tarihi']);
        [$g, $y] = $sayac($ekipman->pluck('sonraki_vize_tarihi')->filter()->map(fn ($t) => Carbon::parse($t)));
        $ekle('Periyodik kontroller', $durumBul($ekipman->isNotEmpty(), $g, $y),
            $ekipman->count().' aktif ekipman kaydı; '.$g.' gecikmiş, '.$y.' yaklaşan.',
            'İşveren / İş güvenliği uzmanı', fn () => PeriyodikKontrol::getUrl(['firma' => $firma->id]));

        // 13. Ortam ölçümleri
        $olcum = collect(OrtamOlcumu::query()->where('firma_id', $firma->id)->value('olcumler') ?? []);
        [$g, $y] = $sayac($olcum->pluck('sonraki_olcum_tarihi')->filter()->map(fn ($t) => static::tarih($t))->filter());
        $ekle('Ortam ve hijyen ölçümleri', $olcum->isEmpty() ? 'bilgi' : $durumBul(true, $g, $y),
            $olcum->count().' ölçüm kaydı; '.$g.' gecikmiş, '.$y.' yaklaşan tekrar ölçümü.',
            'İşveren / İş güvenliği uzmanı', fn () => OrtamOlcumleri::getUrl(['firma' => $firma->id]));

        // 14. İSG Kurulu (50+ çalışan)
        $kurulSayi = KurulToplantisi::query()->where('firma_id', $firma->id)->count();
        $sonKurul = KurulToplantisi::query()->where('firma_id', $firma->id)->whereNotNull('tarih')->max('tarih');
        $kurulSonraki = $sonKurul ? KurulUyeleri::sonrakiToplanti($firma, $sonKurul) : null;
        [$g, $y] = $sayac(collect([$kurulSonraki])->filter());
        $ekle('İSG Kurulu toplantıları', $bildirilen < 50 && $aktif < 50 ? 'bilgi' : $durumBul((bool) $sonKurul, $g, $y),
            $bildirilen < 50 && $aktif < 50 ? '50 çalışan altı — kurul zorunlu değil. '.$kurulSayi.' toplantı kaydı.'
                : $kurulSayi.' toplantı kaydı'.($kurulSonraki ? '; sonraki toplantı '.$kurulSonraki->format('d.m.Y').' ('.KurulUyeleri::periyotEtiketi($firma).')' : '').'.',
            'İşveren / Kurul sekreteryası', fn () => KurulToplantisiSayfasi::getUrl(['firma' => $firma->id]));

        // 15. Dokümanlar (Kontrol Merkezi kriterleri)
        $evrak = collect(PortfoyKarne::firmaChecklistDetay($firma))->where('hazir', true);
        $tamam = $evrak->where('tamam', true)->count();
        $gecVade = $evrak->where('tamam', false)->filter(fn ($k) => $k['vade_tarihi']?->lt($bugun))->count();
        $yuklu = ArsivDosya::query()->where('firma_id', $firma->id)->tarihTakipli()->get(['gecerlilik_sonu']);
        $suresiGecen = $yuklu->filter(fn ($d) => $d->gecerlilik_sonu?->lt($bugun))->count();
        $ekle('Dokümanlar (İSG dosyası)', $gecVade + $suresiGecen > 0 ? 'gecikmis' : ($tamam === $evrak->count() ? 'tamamlandi' : 'eksik'),
            $tamam.' / '.$evrak->count().' evrak mevcut; '.$gecVade.' evrakın vadesi geçti. Arşiv: '.$yuklu->count().' aktif doküman, '.$suresiGecen.' süresi geçmiş.',
            'Kayıt sorumlusu', fn () => KontrolMerkezi::getUrl());

        // 16. Yıllık çalışma planı
        $plan = YillikPlan::query()->where('firma_id', $firma->id)->where('yil', $bugun->year)->first();
        $geciken = $plan ? collect(range(0, max(-1, $bugun->month - 2)))->sum(fn (int $ay) => collect($plan->ayinYapilacaklari($ay))->where('gerceklesti', false)->count()) : 0;
        $maddeSayi = $plan ? count($plan->faaliyetler ?? []) + count($plan->egitimler ?? []) : 0;
        $ekle('Yıllık çalışma planı', ! $plan ? 'eksik' : ($geciken > 0 ? 'gecikmis' : 'tamamlandi'),
            $maddeSayi.' plan maddesi; '.$geciken.' geçmiş ay maddesi gerçekleşmedi.',
            'İSG profesyonelleri / İşveren', fn () => YillikCalismaPlani::getUrl(['firma' => $firma->id]));

        // 17. İş kazası / ramak kala
        $olaylar = OlayKaydi::query()->where('firma_id', $firma->id)->get(['olay_tipi', 'olay_tarihi', 'sgk_bildirimi_yapildi']);
        $sgkBekleyen = $olaylar->filter(fn (OlayKaydi $o) => $o->isKazasiMi() && ! $o->sgk_bildirimi_yapildi && $o->olay_tarihi);
        [$g, $y] = $sayac($sgkBekleyen->map(fn (OlayKaydi $o) => $o->sgkSonTarih()));
        $ekle('İş kazası ve ramak kala kayıtları', $olaylar->isEmpty() ? 'bilgi' : $durumBul(true, $g, $y),
            $olaylar->count().' olay, '.$olaylar->where('olay_tipi', 'ramak_kala')->count().' ramak kala; '.$g.' gecikmiş, '.$y.' yaklaşan SGK bildirim tarihi.',
            'İş güvenliği uzmanı / İşveren', fn () => OlayKayitlari::getUrl(['firma' => $firma->id]));

        // 18. Bildirimler
        $okunmamis = static::sessiz(fn () => Bildirim::query()->where('firma_id', $firma->id)->acik()->whereNull('okundu_at')->count(), 0);
        $ekle('Bildirimler', $okunmamis > 0 ? 'bilgi' : 'tamamlandi', $okunmamis.' okunmamış işyeri bildirimi.',
            'Yetkili kullanıcılar', fn () => BildirimMerkezi::getUrl(['firma' => $firma->id]));

        return $satirlar;
    }

    /** @param  array<int, array<string, mixed>>  $surecler */
    public static function ozet(array $surecler): array
    {
        $say = collect($surecler)->countBy('durum');
        $olculen = collect($surecler)->where('durum', '!=', 'bilgi')->count();

        return [
            'tamamlandi' => $say['tamamlandi'] ?? 0,
            'eksik' => $say['eksik'] ?? 0,
            'gecikmis' => $say['gecikmis'] ?? 0,
            'yaklasan' => $say['yaklasan'] ?? 0,
            'yuzde' => $olculen ? (int) round(($say['tamamlandi'] ?? 0) * 100 / $olculen) : 0,
            'genel' => match (true) {
                ($say['gecikmis'] ?? 0) + ($say['eksik'] ?? 0) > 0 => 'Kritik eksikler var',
                ($say['yaklasan'] ?? 0) > 0 => 'Yaklaşan terminler var',
                default => 'Uyumlu',
            },
        ];
    }

    /**
     * Yükümlülük takvimi: termini olan tüm kayıtlar (gecikmişler önce).
     *
     * @return Collection<int, array{durum: string, kategori: string, kayit: string, alt: ?string, tarih: Carbon, kalan: int, sorumlu: string, url: ?string}>
     */
    public static function takvim(Firma $firma): Collection
    {
        $liste = collect();
        $ekle = function (string $kategori, string $kayit, ?string $alt, ?Carbon $tarih, string $sorumlu, ?callable $url, bool $tamam = false) use ($liste): void {
            if (! $tarih) {
                return;
            }
            $kalan = (int) Carbon::today()->diffInDays($tarih->copy()->startOfDay(), false);
            $liste->push([
                'durum' => match (true) {
                    $tamam => 'tamamlandi',
                    $kalan < 0 => 'gecikmis',
                    $kalan <= 7 => 'cok_yakin',
                    $kalan <= 30 => 'yaklasiyor',
                    default => 'ileri',
                },
                'kategori' => $kategori, 'kayit' => $kayit, 'alt' => $alt, 'tarih' => $tarih, 'kalan' => $kalan,
                'sorumlu' => $sorumlu, 'url' => static::url($url),
            ]);
        };

        $ekle('Sözleşme', 'Hizmet sözleşmesi bitişi', $firma->katip_no ? 'KATİP '.$firma->katip_no : null, $firma->sozlesme_bitis ? Carbon::parse($firma->sozlesme_bitis) : null, 'OSGB / İşveren', fn () => FirmaResource::getUrl('edit', ['record' => $firma]));

        $rd = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->whereNotNull('gecerlilik_tarihi')->latest('gecerlilik_tarihi')->first();
        $ekle('Risk', 'Risk değerlendirmesi yenileme', $rd?->belge_no, $rd?->gecerlilik_tarihi, 'İş güvenliği uzmanı', fn () => RiskDegerlendirmesiResource::getUrl('index'));

        $adp = AcilDurumPlani::query()->where('firma_id', $firma->id)->first();
        $ekle('Acil durum', 'Acil durum planı revizyonu', $adp?->revizyon_no ? 'Rev. '.$adp->revizyon_no : null, $adp?->gecerlilik_tarihi, 'İş güvenliği uzmanı / İşveren', fn () => AcilDurumPlaniSayfasi::getUrl(['firma' => $firma->id]));

        foreach (EgitimTakibi::satirlar($firma) as $s) {
            $ekle('Eğitim', 'Temel İSG eğitimi yenileme', $s['calisan']->ad_soyad, $s['yenileme'], 'İş güvenliği uzmanı / İşyeri hekimi', fn () => EgitimYenilemeTakibi::getUrl(['firma' => $firma->id]));
            $ekle('Eğitim', 'Temel eğitim (işe girişten 3 ay)', $s['calisan']->ad_soyad, $s['ilk_son_tarih'], 'İşveren / İş güvenliği uzmanı', fn () => EgitimYenilemeTakibi::getUrl(['firma' => $firma->id]));
        }

        foreach (IsEkipmani::query()->where('firma_id', $firma->id)->where('aktif', true)->whereNotNull('sonraki_vize_tarihi')->get() as $e) {
            $ekle('Periyodik kontrol', $e->ekipman_adi, $e->seri_no ? 'Seri '.$e->seri_no : $e->konum, $e->sonraki_vize_tarihi, 'İşveren / İş güvenliği uzmanı', fn () => PeriyodikKontrol::getUrl(['firma' => $firma->id]));
        }

        foreach (KkdZimmet::query()->where('firma_id', $firma->id)->where('durum', 'teslim_edildi')->get() as $z) {
            $ekle('KKD', $z->kategoriEtiketi(), $z->markaModel() ?: null, $z->vade(), 'İşveren / İş güvenliği uzmanı', fn () => KkdTakip::getUrl(['firma' => $firma->id]));
        }

        foreach (OrtamOlcumu::query()->where('firma_id', $firma->id)->value('olcumler') ?? [] as $m) {
            $ekle('Ortam ölçümü', (string) ($m['parametre'] ?? 'Ölçüm'), $m['bolum'] ?? null, static::tarih($m['sonraki_olcum_tarihi'] ?? null), 'İşveren / İş güvenliği uzmanı', fn () => OrtamOlcumleri::getUrl(['firma' => $firma->id]));
        }

        // Sağlık: tarih başına toplu, ad / tetkik yok
        collect(SaglikGozetimi::query()->where('firma_id', $firma->id)->value('satirlar') ?? [])
            ->pluck('sonraki_tarih')->filter()->countBy()
            ->each(fn (int $adet, string $t) => $ekle('Sağlık', 'Periyodik muayene', $adet.' kişi', static::tarih($t), 'İşyeri hekimi', fn () => SaglikGozetimiSayfasi::getUrl(['firma' => $firma->id])));

        foreach (DofRaporu::query()->where('firma_id', $firma->id)->get() as $r) {
            foreach ($r->maddeler ?? [] as $m) {
                if (filled($m['termin'] ?? null)) {
                    $ekle('DÖF', mb_strimwidth((string) ($m['tespit'] ?? 'DÖF maddesi'), 0, 80, '…'), $r->belge_no, static::tarih($m['termin']), (string) ($m['sorumlu'] ?? 'Kayıt sorumlusu'), fn () => DofOlustur::getUrl(['firma' => $firma->id]), ($m['durum'] ?? null) === 'tamamlandi');
                }
            }
        }

        foreach (KimyasalUrun::query()->where('firma_id', $firma->id)->where('aktif', true)->whereNotNull('sonraki_gozden_gecirme')->get() as $k) {
            $ekle('SDS', 'SDS gözden geçirme — '.$k->urun_adi, null, static::tarih($k->sonraki_gozden_gecirme), 'İşveren / İş güvenliği uzmanı', fn () => KimyasalSicili::getUrl(['firma' => $firma->id]));
        }
        foreach (PkdKaydi::query()->where('firma_id', $firma->id)->whereNotNull('sonraki_gozden_gecirme')->get() as $p) {
            $ekle('PKD', 'Patlamadan korunma dokümanı gözden geçirme', $p->revizyon_no ? 'Rev. '.$p->revizyon_no : null, static::tarih($p->sonraki_gozden_gecirme), 'İşveren / İş güvenliği uzmanı', fn () => PkdSicili::getUrl(['firma' => $firma->id]));
        }

        $sonTatbikat = TatbikatTutanagi::query()->where('firma_id', $firma->id)->max('tatbikat_tarihi');
        $ekle('Tatbikat', 'Yıllık acil durum tatbikatı', $sonTatbikat ? 'Son: '.Carbon::parse($sonTatbikat)->format('d.m.Y') : null, $sonTatbikat ? Carbon::parse($sonTatbikat)->addYear() : null, 'İş güvenliği uzmanı / İşveren', fn () => TatbikatSayfasi::getUrl(['firma' => $firma->id]));

        $sonKurul = KurulToplantisi::query()->where('firma_id', $firma->id)->max('tarih');
        if ($sonKurul) {
            $ekle('İSG Kurulu', 'Kurul toplantısı', KurulUyeleri::periyotEtiketi($firma), KurulUyeleri::sonrakiToplanti($firma, $sonKurul), 'İşveren / Kurul sekreteryası', fn () => KurulToplantisiSayfasi::getUrl(['firma' => $firma->id]));
        }

        foreach (OlayKaydi::query()->where('firma_id', $firma->id)->whereNotNull('olay_tarihi')->get() as $o) {
            if ($o->isKazasiMi()) {
                $ekle('Olay / SGK bildirimi', 'SGK bildirim süresi — '.($o->belge_no ?? 'İş kazası'), $o->tipEtiketi(), $o->sgkSonTarih(), 'İşveren / İşveren vekili', fn () => OlayKayitlari::getUrl(['firma' => $firma->id]), (bool) $o->sgk_bildirimi_yapildi);
            }
        }

        foreach (ArsivDosya::query()->where('firma_id', $firma->id)->tarihTakipli()->whereNotNull('gecerlilik_sonu')->get() as $d) {
            $ekle('Doküman', $d->etiket(), $d->kategoriEtiketi().($d->versiyon ? ' · v'.$d->versiyon : ''), $d->gecerlilik_sonu, 'Kayıt sorumlusu', fn () => DokumanYonetimi::getUrl(['firma' => $firma->id]));
        }

        $sira = array_flip(array_keys(static::TAKVIM_DURUMLARI));

        return $liste->sortBy(fn (array $t) => [$sira[$t['durum']], $t['tarih']->timestamp])->values();
    }

    /** Göstergeler (isgsuite alt kartlar). */
    public static function gostergeler(Firma $firma): array
    {
        $sg = collect(SaglikGozetimi::query()->where('firma_id', $firma->id)->value('satirlar') ?? []);
        $rd = RiskDegerlendirmesi::query()->where('firma_id', $firma->id)->latest('id')->first();

        return [
            'personel' => $firma->calisanlar()->where('aktif', true)->count(),
            'sube' => $firma->calisanlar()->where('aktif', true)->whereNotNull('sube')->where('sube', '!=', '')->distinct()->count('sube'),
            'gorevlendirme' => collect([$firma->igu_id, $firma->isyeri_hekimi_id, $firma->dsp_id])->filter()->count(),
            'evrak_uyum' => static::evrakUyum($firma),
            'risk_maddesi' => $rd ? $rd->maddeler()->count() : 0,
            'acik_dof' => DofRaporu::query()->where('firma_id', $firma->id)->get()->flatMap(fn ($r) => $r->maddeler ?? [])->where('durum', '!=', 'tamamlandi')->count(),
            'gecikmis_muayene' => $sg->pluck('sonraki_tarih')->filter()->filter(fn ($t) => static::tarih($t)?->lt(Carbon::today()))->count(),
            'egitim_kaydi' => $firma->calisanlar()->withCount('egitimKayitlari')->get()->sum('egitim_kayitlari_count') + EgitimKatilim::query()->where('firma_id', $firma->id)->count(),
        ];
    }

    /** Kontrol Merkezi evrak kriterlerinden karşılananların oranı (%). */
    public static function evrakUyum(Firma $firma): int
    {
        $evrak = collect(PortfoyKarne::firmaChecklistDetay($firma))->where('hazir', true);

        return $evrak->isEmpty() ? 0 : (int) round($evrak->where('tamam', true)->count() * 100 / $evrak->count());
    }

    /** @return array<int, array{ad: string, rol: string, aylik_dk: int}> */
    public static function gorevlendirmeler(Firma $firma): array
    {
        $calisan = max((int) $firma->calisan_sayisi, $firma->calisanlar()->where('aktif', true)->count());
        $sinif = $firma->tehlike_sinifi;
        $roller = [
            ['iliski' => 'igu', 'rol' => 'İş Güvenliği Uzmanı', 'dk' => (int) config("isg.igu_aylik_dk.{$sinif}", 10)],
            ['iliski' => 'isyeriHekimi', 'rol' => 'İşyeri Hekimi', 'dk' => (int) config("isg.hekim_aylik_dk.{$sinif}", 5)],
            ['iliski' => 'dsp', 'rol' => 'Diğer Sağlık Personeli', 'dk' => (int) config("isg.dsp_aylik_dk.{$sinif}", 0)],
        ];

        return collect($roller)
            ->map(fn (array $r) => ['p' => $firma->{$r['iliski']}, ...$r])
            ->filter(fn (array $r) => $r['p'] !== null)
            ->map(fn (array $r) => ['ad' => $r['p']->ad_soyad, 'rol' => $r['rol'], 'aylik_dk' => $calisan * $r['dk'], 'sertifika' => $r['p']->sertifika_no])
            ->values()->all();
    }

    /** @return array<int, array{tarih: string, amac: ?string, sure_saat: mixed, durum: string}> */
    public static function sonZiyaretler(Firma $firma, int $adet = 6): array
    {
        $program = ZiyaretProgrami::query()->where('firma_id', $firma->id)->get();

        return $program->flatMap(fn (ZiyaretProgrami $p) => collect($p->ziyaretler ?? [])->flatMap(fn ($ay) => ZiyaretProgrami::ayGirdileri($ay)))
            ->filter(fn ($z) => filled($z['tarih'] ?? null) && $z['tarih'] <= now()->toDateString())
            ->sortByDesc('tarih')->take($adet)
            ->map(fn ($z) => ['tarih' => $z['tarih'], 'amac' => $z['amac'] ?? null, 'sure_saat' => $z['sure_saat'] ?? null, 'durum' => $z['durum'] ?? 'bos'])
            ->values()->all();
    }

    /** @return Collection<int, OlayKaydi> */
    public static function sonOlaylar(Firma $firma, int $adet = 5): Collection
    {
        return OlayKaydi::query()->where('firma_id', $firma->id)->latest('olay_tarihi')->latest('id')->take($adet)->get();
    }

    public static function kalanMetni(Carbon $tarih): string
    {
        $kalan = (int) Carbon::today()->diffInDays($tarih->copy()->startOfDay(), false);

        return $kalan < 0 ? abs($kalan).' gün geçti' : ($kalan === 0 ? 'bugün' : $kalan.' gün kaldı');
    }

    private static function url(?callable $uret): ?string
    {
        return $uret ? static::sessiz($uret, null) : null;
    }

    private static function sessiz(callable $f, mixed $varsayilan): mixed
    {
        try {
            return $f();
        } catch (Throwable) {
            return $varsayilan;
        }
    }

    private static function tarih(mixed $deger): ?Carbon
    {
        if (blank($deger)) {
            return null;
        }

        try {
            return Carbon::parse($deger);
        } catch (Throwable) {
            return null;
        }
    }
}
