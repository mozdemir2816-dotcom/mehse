<?php

namespace App\Support;

use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\IsIzinFormu as IsIzinSayfasi;
use App\Filament\Pages\KurulToplantisi as KurulSayfasi;
use App\Filament\Pages\PeriyodikKontrol;
use App\Filament\Pages\SahaDenetimi as SahaDenetimiSayfasi;
use App\Filament\Pages\TaseronYonetimi;
use App\Filament\Pages\ZiyaretProgrami as ZiyaretSayfasi;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\IsIzinFormu;
use App\Models\KurulToplantisi;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use App\Models\Taseron;
use App\Models\User;
use App\Models\ZiyaretProgrami;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Tesis Uygunluk Özeti / İSG Control Tower (isgsuite): kayıt değiştirmez;
 * taşeron, iş izni (PTW), periyodik kontrol, saha denetimi, birleşik
 * DÖF / aksiyon ve profesyonel hizmet süresi kapasitesini toplayıp
 * "bugün neye müdahale etmeliyim?" önceliği ile açıklanabilir 0–100
 * operasyon skoru üretir. Her puan düşüşü `dokum` içinde nedeniyle verilir.
 * Sağlık / klinik veri puana dahil edilmez.
 */
class TesisUygunlugu
{
    /** Kalem başına düşülen puan ve kalemin toplam üst sınırı. */
    public const CEZALAR = [
        'taseron_belge' => [4, 16],
        'taseron_sozlesme' => [6, 12],
        'ptw' => [5, 20],
        'periyodik' => [5, 25],
        'denetim' => [2, 12],
        'kritik_denetim' => [8, 8],
        'gecikmis_aksiyon' => [3, 18],
        'kapasite_kritik' => [12, 12],
        'kapasite_uyari' => [5, 5],
        'kapasite_asimi' => [5, 5],
    ];

    /** Açık kurul kararı / DÖF sayılmayan durumlar. */
    private const KAPALI = ['tamamlandi', 'uygulandi', 'kapandi', 'iptal'];

    public static function seviye(int $skor): string
    {
        return match (true) {
            $skor >= 90 => 'Uygun',
            $skor >= 75 => 'İzlenmeli',
            $skor >= 50 => 'Müdahale gerekli',
            default => 'Kritik',
        };
    }

    /**
     * @return array{skor: int, seviye: string, sayilar: array<string, int>, dokum: array<int, array{neden: string, puan: int}>, oneriler: array<int, array<string, mixed>>, kapasite: array<string, int>}
     */
    public static function hesapla(Firma $firma, User $kullanici): array
    {
        $bugun = Carbon::today();

        // Taşeron
        $taseronlar = Taseron::query()->where('firma_id', $firma->id)->where('aktif', true)->with('belgeler')->get();
        $belgeEksik = $taseronlar->filter(fn (Taseron $t) => $t->eksikZorunluBelgeler() !== [])->count();
        $sozlesmeBitti = $taseronlar->filter(fn (Taseron $t) => $t->sozlesmeDurumu() === 'bitti')->count();

        // PTW: süresi geçip kapatılmamış ya da uygunsuz kontrolle bekleyen izinler
        $izinler = IsIzinFormu::query()->where('firma_id', $firma->id)->whereIn('durum', ['onaylandi', 'onay_bekliyor'])->get();
        $ptw = $izinler->filter(fn (IsIzinFormu $i) => $i->suresiGectiMi() || $i->kontrolAdlari('uygun_degil') !== [])->count();

        // Periyodik kontrol
        $periyodik = IsEkipmani::query()->where('firma_id', $firma->id)->where('aktif', true)->whereNotNull('sonraki_vize_tarihi')
            ->whereDate('sonraki_vize_tarihi', '<', $bugun)->count();

        // Saha denetimi: son tamamlanan denetimin uygunsuz maddeleri + açık saha bulguları
        $sonDenetim = SahaDenetimi::query()->where('firma_id', $firma->id)->where('durum', 'tamamlandi')->latest('denetim_tarihi')->latest('id')->first();
        // Bulguya bağlanan "uygun değil" madde açık bulgu olarak sayılır (çift sayılmaz)
        $uygunsuz = collect($sonDenetim?->cevaplar ?? [])->where('sonuc', 'uygun_degil')->filter(fn ($c) => empty($c['bulgu_id']))->count();
        $acikBulgular = SahaBulgusu::query()->where('firma_id', $firma->id)->whereIn('durum', ['acik', 'devam_ediyor'])->get(['termin', 'durum']);
        $denetim = $uygunsuz + $acikBulgular->count();

        // Birleşik aksiyon: DÖF maddeleri + saha bulguları + kurul kararları.
        // Bulguya bağlı DÖF maddesi bulgu olarak sayılır (çift sayılmaz).
        $termler = collect();
        foreach (DofRaporu::query()->where('firma_id', $firma->id)->get() as $r) {
            foreach ($r->maddeler ?? [] as $m) {
                if (empty($m['bulgu_id']) && ! in_array($m['durum'] ?? 'acik', static::KAPALI, true)) {
                    $termler->push(static::tarih($m['termin'] ?? null));
                }
            }
        }
        foreach ($acikBulgular as $b) {
            $termler->push($b->termin);
        }
        foreach (KurulToplantisi::query()->where('firma_id', $firma->id)->get(['kararlar']) as $k) {
            foreach ($k->kararlar ?? [] as $karar) {
                if (! in_array($karar['durum'] ?? 'beklemede', static::KAPALI, true) && filled($karar['karar_metni'] ?? null)) {
                    $termler->push(static::tarih($karar['termin'] ?? null));
                }
            }
        }
        $acikAksiyon = $termler->count();
        $gecikmisAksiyon = $termler->filter(fn (?Carbon $t) => $t?->lt($bugun))->count();

        // Kapasite: bu ay yapılan + planlı ziyaret süresi / yasal ihtiyaç (İGU)
        $kapasite = static::kapasite($firma);
        $portfoy = GorevDurumu::sureOzeti($kullanici);
        $asim = $portfoy['kullanilan_dk'] > $portfoy['kapasite_dk'] ? 1 : 0;
        $kapasiteKritik = $kapasite['gerekli'] > 0 && $kapasite['karsilanan'] * 2 < $kapasite['gerekli'] ? 1 : 0;
        $kapasiteUyari = ! $kapasiteKritik && $kapasite['gerekli'] > 0 && $kapasite['karsilanan'] < $kapasite['gerekli'] ? 1 : 0;

        // Puan dökümü
        $dokum = [];
        $dus = function (string $anahtar, int $adet, string $neden) use (&$dokum): void {
            if ($adet <= 0) {
                return;
            }
            [$birim, $ust] = static::CEZALAR[$anahtar];
            $dokum[] = ['neden' => $neden, 'puan' => min($ust, $birim * $adet)];
        };
        $dus('taseron_belge', $belgeEksik, "{$belgeEksik} taşeronun zorunlu belgesi eksik");
        $dus('taseron_sozlesme', $sozlesmeBitti, "{$sozlesmeBitti} taşeronun sözleşmesi bitmiş");
        $dus('ptw', $ptw, "{$ptw} iş izni süresi geçtiği hâlde kapatılmamış ya da uygunsuz kontrolle bekliyor");
        $dus('periyodik', $periyodik, "{$periyodik} ekipmanın periyodik kontrol süresi geçmiş");
        $dus('denetim', $denetim, "{$denetim} açık denetim bulgusu / uygunsuzluk");
        $dus('kritik_denetim', (int) ($sonDenetim?->kritik_uygunsuzluk_var ?? false), 'Son saha denetiminde kritik uygunsuzluk var');
        $dus('gecikmis_aksiyon', $gecikmisAksiyon, "{$gecikmisAksiyon} aksiyonun termini geçmiş");
        $dus('kapasite_kritik', $kapasiteKritik, 'Bu ayki hizmet süresi yasal ihtiyacın yarısının altında');
        $dus('kapasite_uyari', $kapasiteUyari, 'Bu ayki hizmet süresi yasal ihtiyacın altında');
        $dus('kapasite_asimi', $asim, 'Uzmanın toplam görevlendirme süresi aylık kapasiteyi aşıyor');

        $skor = max(0, 100 - array_sum(array_column($dokum, 'puan')));

        // "Bugün neye müdahale etmeliyim?" — öncelik sırasıyla
        $oneriler = [];
        $oner = function (int $adet, string $seviye, string $baslik, string $aciklama, ?callable $url, string $urlAd) use (&$oneriler): void {
            if ($adet > 0) {
                $oneriler[] = ['seviye' => $seviye, 'baslik' => $baslik, 'aciklama' => $aciklama, 'adet' => $adet, 'url' => static::url($url), 'url_ad' => $urlAd];
            }
        };
        $f = ['firma' => $firma->id];
        $oner($kapasiteKritik + $kapasiteUyari, $kapasiteKritik ? 'kritik' : 'uyari', 'Hizmet süresi açığını kapat',
            "Bu ay {$kapasite['karsilanan']} dk hizmet var (yapılan {$kapasite['yapilan']} + planlı {$kapasite['planli']}); yasal ihtiyaç {$kapasite['gerekli']} dk.",
            fn () => ZiyaretSayfasi::getUrl($f), 'Ziyaret programını aç');
        $oner($periyodik, 'kritik', 'Süresi geçen periyodik kontrolleri yaptır', "{$periyodik} ekipman kontrol süresi geçmiş hâlde kullanılıyor olabilir.", fn () => PeriyodikKontrol::getUrl($f), 'Periyodik kontrolü aç');
        $oner($ptw, 'kritik', 'Açık kalan iş izinlerini kapat', "{$ptw} iş izni süresi dolmuş ya da uygunsuz kontrolle duruyor.", fn () => IsIzinSayfasi::getUrl($f), 'İş izinlerini aç');
        $oner($sozlesmeBitti + $belgeEksik, $sozlesmeBitti ? 'kritik' : 'uyari', 'Taşeron belgelerini tamamla',
            "{$belgeEksik} taşeronda zorunlu belge eksik, {$sozlesmeBitti} taşeronun sözleşmesi bitmiş.", fn () => TaseronYonetimi::getUrl($f), 'Taşeronları aç');
        $oner($gecikmisAksiyon, 'kritik', 'Gecikmiş aksiyonları kapat', "{$gecikmisAksiyon} DÖF / bulgu / kurul kararının termini geçti.", fn () => DofOlustur::getUrl($f), 'DÖF ekranını aç');
        $oner($denetim, $sonDenetim?->kritik_uygunsuzluk_var ? 'kritik' : 'uyari', 'Denetim bulgularını kapat',
            "Son denetimde {$uygunsuz} uygunsuz madde, {$acikBulgular->count()} açık saha bulgusu.", fn () => $acikBulgular->isNotEmpty() ? HizliSahaBulgusu::getUrl($f) : SahaDenetimiSayfasi::getUrl($f), 'Denetimi aç');
        $oner(max(0, $acikAksiyon - $gecikmisAksiyon), 'bilgi', 'Açık aksiyonları takip et', ($acikAksiyon - $gecikmisAksiyon).' açık aksiyon henüz gecikmedi.', fn () => DofOlustur::getUrl($f), 'DÖF ekranını aç');
        $oner($asim, 'uyari', 'Toplam görevlendirme süresi kapasiteyi aşıyor', GorevDurumu::saatDk($portfoy['kullanilan_dk']).' görevlendirme / '.GorevDurumu::saatDk($portfoy['kapasite_dk']).' kapasite.', fn () => \App\Filament\Pages\AnaSayfa::getUrl(), 'Ana sayfayı aç');

        $sira = ['kritik' => 0, 'uyari' => 1, 'bilgi' => 2];
        usort($oneriler, fn ($a, $b) => $sira[$a['seviye']] <=> $sira[$b['seviye']]);

        return [
            'skor' => $skor,
            'seviye' => static::seviye($skor),
            'sayilar' => [
                'toplam_dikkat' => count(array_filter($oneriler, fn ($o) => $o['seviye'] !== 'bilgi')),
                'aktif_taseron' => $taseronlar->count(),
                'taseron_belge' => $belgeEksik,
                'ptw' => $ptw,
                'periyodik' => $periyodik,
                'denetim' => $denetim,
                'acik_aksiyon' => $acikAksiyon,
                'gecikmis_aksiyon' => $gecikmisAksiyon,
                'kapasite_kritik' => $kapasiteKritik,
                'kapasite_uyari' => $kapasiteUyari,
                'kapasite_asimi' => $asim,
            ],
            'dokum' => $dokum,
            'oneriler' => $oneriler,
            'kapasite' => $kapasite,
        ];
    }

    /** @return array{gerekli: int, yapilan: int, planli: int, karsilanan: int} bu ayın İGU hizmet süresi (dk) */
    public static function kapasite(Firma $firma): array
    {
        $ay = now()->format('Y-m');
        $bugun = now()->toDateString();
        $calisan = max((int) $firma->calisan_sayisi, $firma->calisanlar()->where('aktif', true)->count());
        $gerekli = $firma->iguAylikDk($calisan);

        $girdiler = ZiyaretProgrami::query()->where('firma_id', $firma->id)->get()
            ->flatMap(fn (ZiyaretProgrami $p) => collect($p->ziyaretler ?? [])->flatMap(fn ($a) => ZiyaretProgrami::ayGirdileri($a)))
            ->filter(fn ($z) => str_starts_with((string) ($z['tarih'] ?? ''), $ay));
        $dk = fn ($liste) => (int) round(collect($liste)->sum(fn ($z) => (float) str_replace(',', '.', (string) ($z['sure_saat'] ?? 0))) * 60);

        $yapilan = $dk($girdiler->where('durum', 'tamamlandi'));
        $planli = $dk($girdiler->where('durum', '!=', 'tamamlandi')->filter(fn ($z) => $z['tarih'] >= $bugun));

        return ['gerekli' => $gerekli, 'yapilan' => $yapilan, 'planli' => $planli, 'karsilanan' => $yapilan + $planli];
    }

    private static function url(?callable $uret): ?string
    {
        try {
            return $uret ? $uret() : null;
        } catch (Throwable) {
            return null;
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
