<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\IsbasiEgitimTutanagi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Çalışan bazlı temel İSG eğitimi yenileme takibi (isgsuite "Yenileme Takibi"
 * + "Bugün ne yapmalıyım"). Son temel eğitim tarihi üç kaynaktan bulunur:
 *  - Eğitim kayıtları (Profilim > Eğitimler, tür is_sagligi_guvenligi_egitimi)
 *  - Yüz yüze Eğitim Katılım formları (genel başlık) — TC, yoksa ad soyad eşleşmesi
 * Yenileme = son tarih + tehlike sınıfı periyodu (isg.egitim_yenileme_yili).
 * İşbaşı eğitimi ayrıca İşbaşı Eğitim Tutanakları'ndan kontrol edilir.
 * Hiçbir kayıt otomatik "tamamlandı" yapılmaz; yalnız okunur.
 */
class EgitimTakibi
{
    public const TEMEL_TUR = 'is_sagligi_guvenligi_egitimi';

    /** Ad soyad karşılaştırması için Türkçe uyumlu normalleştirme. */
    public static function adAnahtari(?string $ad): string
    {
        $ad = str_replace(['İ', 'I'], ['i', 'ı'], (string) $ad);

        return preg_replace('/\s+/u', ' ', trim(mb_strtolower($ad))) ?? '';
    }

    public static function tcAnahtari(?string $tc): string
    {
        return preg_replace('/\D/', '', (string) $tc) ?? '';
    }

    /**
     * @return Collection<int, array{calisan: Calisan, son_egitim: ?Carbon, kaynak: ?string, yenileme: ?Carbon, kalan_gun: ?int, durum: string, isbasi: bool}>
     */
    public static function satirlar(Firma $firma): Collection
    {
        $calisanlar = $firma->calisanlar()->where('aktif', true)->with('egitimKayitlari')->orderBy('ad_soyad')->get();
        $yil = config('isg.egitim_yenileme_yili.'.$firma->tehlike_sinifi);
        $esik = KullaniciAyarlari::esik('egitim');

        // Yüz yüze katılım formlarından: tc / ad anahtarı → en son tarih
        $katilimTc = [];
        $katilimAd = [];
        foreach (EgitimKatilim::query()->where('firma_id', $firma->id)->where('baslik_anahtari', 'genel')->get() as $k) {
            $tarih = static::katilimTarihi($k);
            if (! $tarih) {
                continue;
            }
            foreach ($k->belgeAlacakKatilimcilar() as $kisi) {  // katılmayan / başarısız sayılmaz
                $tc = static::tcAnahtari($kisi['tc'] ?? null);
                $ad = static::adAnahtari($kisi['ad_soyad'] ?? null);
                if ($tc !== '' && (! isset($katilimTc[$tc]) || $tarih->gt($katilimTc[$tc]))) {
                    $katilimTc[$tc] = $tarih;
                }
                if ($ad !== '' && (! isset($katilimAd[$ad]) || $tarih->gt($katilimAd[$ad]))) {
                    $katilimAd[$ad] = $tarih;
                }
            }
        }

        // İşbaşı tutanakları
        $isbasiTc = [];
        $isbasiAd = [];
        foreach (IsbasiEgitimTutanagi::query()->where('firma_id', $firma->id)->get(['calisan_ad_soyad', 'calisan_tc']) as $t) {
            if (($tc = static::tcAnahtari($t->calisan_tc)) !== '') {
                $isbasiTc[$tc] = true;
            }
            $isbasiAd[static::adAnahtari($t->calisan_ad_soyad)] = true;
        }

        return $calisanlar->map(function (Calisan $c) use ($katilimTc, $katilimAd, $isbasiTc, $isbasiAd, $yil, $esik): array {
            $tc = static::tcAnahtari($c->tc);
            $ad = static::adAnahtari($c->ad_soyad);

            $adaylar = collect([
                ['tarih' => $c->egitimKayitlari->firstWhere('tur', static::TEMEL_TUR)?->tarih, 'kaynak' => 'Eğitim kaydı'],
                ['tarih' => ($tc !== '' ? ($katilimTc[$tc] ?? null) : null) ?? ($katilimAd[$ad] ?? null), 'kaynak' => 'Yüz yüze katılım'],
            ])->filter(fn ($a) => $a['tarih'] !== null)->sortByDesc(fn ($a) => $a['tarih']->timestamp);

            $son = $adaylar->first();
            $yenileme = $son && $yil ? $son['tarih']->copy()->addYears($yil) : null;
            $kalan = $yenileme ? (int) now()->startOfDay()->diffInDays($yenileme, false) : null;

            return [
                'calisan' => $c,
                'son_egitim' => $son['tarih'] ?? null,
                'kaynak' => $son['kaynak'] ?? null,
                'yenileme' => $yenileme,
                'kalan_gun' => $kalan,
                'durum' => match (true) {
                    ! $son => 'kayit_yok',
                    $kalan !== null && $kalan < 0 => 'dolmus',
                    $kalan !== null && $kalan <= $esik => 'yaklasan',
                    default => 'gecerli',
                },
                'isbasi' => ($tc !== '' && isset($isbasiTc[$tc])) || isset($isbasiAd[$ad]),
            ];
        });
    }

    /** Katılım formunun eğitim tarihi — gün tarihlerinin sonuncusu, yoksa belge tarihi. */
    public static function katilimTarihi(EgitimKatilim $k): ?Carbon
    {
        $gunler = collect($k->gun_tarihleri ?? [])->filter()->map(fn ($g) => Carbon::parse($g))->sort();

        return $gunler->last() ?? $k->belge_tarihi;
    }

    /** @param  Collection<int, array<string, mixed>>  $satirlar */
    public static function ozet(Collection $satirlar): array
    {
        $toplam = $satirlar->count();
        $say = $satirlar->countBy('durum');
        $uygun = ($say['gecerli'] ?? 0) + ($say['yaklasan'] ?? 0);

        return [
            'toplam' => $toplam,
            'kayit_yok' => $say['kayit_yok'] ?? 0,
            'dolmus' => $say['dolmus'] ?? 0,
            'yaklasan' => $say['yaklasan'] ?? 0,
            'gecerli' => $say['gecerli'] ?? 0,
            'isbasi_eksik' => $satirlar->where('isbasi', false)->count(),
            'uyum' => $toplam ? (int) round($uygun * 100 / $toplam) : null,
        ];
    }
}
