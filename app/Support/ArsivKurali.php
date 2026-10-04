<?php

namespace App\Support;

use App\Models\ArsivDosya;
use App\Models\Firma;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Arşiv kategori kuralları (config/arsiv.php). Firma × kategori için
 * "yürürlükteki belge", beklenen dönem, son tarih ve durum hesaplanır:
 *   tamam | eksik | yaklasan | gecikmis | muaf (koşul dışı) | takipsiz.
 * Tüm hesap, firmanın o kategorideki "dosyada" + aktif kayıtlarından yapılır;
 * "imza bekliyor" (şablondan üretilmiş, henüz dosyaya eklenmemiş) belgeler sayılmaz.
 */
final class ArsivKurali
{
    public const DURUMLAR = [
        'gecikmis' => 'Gecikmiş',
        'eksik' => 'Eksik',
        'yaklasan' => 'Yaklaşıyor',
        'tamam' => 'Tamam',
        'muaf' => 'Gerekmiyor',
        'takipsiz' => 'Takip yok',
    ];

    /** @return array<string, mixed> */
    public static function kategori(?string $anahtar): array
    {
        $tum = config('arsiv.kategoriler');

        return ($tum[$anahtar ?? 'diger'] ?? $tum['diger']) + ['anahtar' => isset($tum[$anahtar]) ? $anahtar : 'diger'];
    }

    public static function takipliMi(string $anahtar): bool
    {
        return self::kategori($anahtar)['kural'] !== 'kayit';
    }

    public static function yaklasanGun(): int
    {
        return KullaniciAyarlari::arsivYaklasanGun();
    }

    /** Periyodik kategoride firmanın tehlike sınıfına göre ay sayısı. */
    public static function ay(string $anahtar, ?Firma $firma): ?int
    {
        $ay = self::kategori($anahtar)['ay'] ?? null;

        if (is_array($ay)) {
            return $ay[$firma?->tehlike_sinifi] ?? max($ay);
        }

        return $ay ? (int) $ay : null;
    }

    /** Kaydın yılı: alan yoksa belge tarihinden / oluşturma tarihinden (eski kayıtlar). */
    public static function yil(ArsivDosya $d): ?int
    {
        return $d->yil ?: ($d->baslangic_tarihi?->year ?? $d->created_at?->year);
    }

    /**
     * Kural kategorilerinde geçerlilik sonu kuraldan hesaplanır; 'kayit'
     * kategorilerinde kullanıcının girdiği bitiş korunur.
     */
    public static function gecerlilikSonu(ArsivDosya $d): ?Carbon
    {
        $k = self::kategori($d->kategori);

        return match ($k['kural']) {
            'yillik_plan' => ($y = self::yil($d)) ? Carbon::create($y, 12, 31)->startOfDay() : null,
            'periyodik' => $d->baslangic_tarihi && ($ay = self::ay($k['anahtar'], $d->firma))
                ? $d->baslangic_tarihi->copy()->addMonthsNoOverflow($ay)
                : null,
            'kayit' => $d->gecerlilik_sonu,
            default => null,
        };
    }

    /** Beklenen dönem: yıllık planda bu yıl, yıllık raporda geçen yıl. */
    public static function beklenenYil(string $anahtar, ?Carbon $bugun = null): ?int
    {
        $bugun ??= Carbon::today();

        return match (self::kategori($anahtar)['kural']) {
            'yillik_plan' => $bugun->year,
            'yillik_rapor' => $bugun->year - 1,
            default => null,
        };
    }

    /**
     * @param  Collection<int, ArsivDosya>  $kayitlar  firmanın bu kategorideki tüm kayıtları
     * @return array{durum: string, mesaj: string, son_tarih: ?Carbon, gecerli: ?ArsivDosya, baslik: string}
     */
    public static function durum(Firma $firma, string $anahtar, Collection $kayitlar, ?Carbon $bugun = null, array $haric = []): array
    {
        $bugun ??= Carbon::today();
        $k = self::kategori($anahtar);
        $dosyada = $kayitlar->filter(fn (ArsivDosya $d) => $d->aktif && $d->asama !== 'imza_bekliyor');
        $yaklasan = self::yaklasanGun();
        $sonuc = fn (string $durum, string $mesaj, ?Carbon $son = null, ?ArsivDosya $gecerli = null, ?string $baslik = null) => [
            'durum' => $durum, 'mesaj' => $mesaj, 'son_tarih' => $son, 'gecerli' => $gecerli, 'baslik' => $baslik ?? $k['ad'],
        ];
        $enSon = fn (Collection $c) => $c->sortByDesc(fn (ArsivDosya $d) => ($d->baslangic_tarihi?->format('Ymd') ?? '00000000').str_pad((string) $d->id, 10, '0', STR_PAD_LEFT))->first();

        if (in_array($anahtar, $haric, true)) {
            return $sonuc('takipsiz', 'Hatırlatma ayarlarında takip dışı bırakıldı.');
        }

        if (($k['kosul'] ?? null) === 'elli_calisan' && (int) $firma->calisan_sayisi < 50) {
            $gecerli = $enSon($dosyada);

            return $sonuc('muaf', '50 çalışandan az — zorunlu değil.', null, $gecerli);
        }

        switch ($k['kural']) {
            case 'suresiz':
                $gecerli = $enSon($dosyada);

                return $gecerli
                    ? $sonuc('tamam', 'Yürürlükte.', null, $gecerli)
                    : $sonuc($k['yoksa'] ?? 'eksik', 'Yüklenmiş bir belge yok, eklemek için dokunun.');

            case 'yillik_plan':
                $yil = self::beklenenYil($anahtar, $bugun);
                $gecerli = $enSon($dosyada->filter(fn (ArsivDosya $d) => self::yil($d) === $yil));
                $baslik = $yil.' '.self::kisaAd($k);
                $son = Carbon::create($yil, 12, 31);

                return $gecerli
                    ? $sonuc('tamam', '31 Aralık '.$yil.' tarihine kadar geçerli.', $son, $gecerli, $baslik)
                    : $sonuc($k['yoksa'] ?? 'eksik', 'Bu yılın planı yüklenmemiş, eklemek için dokunun.', $son, null, $baslik);

            case 'yillik_rapor':
                $yil = self::beklenenYil($anahtar, $bugun);
                $gecerli = $enSon($dosyada->filter(fn (ArsivDosya $d) => self::yil($d) === $yil));
                $son = Carbon::create($yil + 1, 1, 31);
                $baslik = $yil.' '.self::kisaAd($k);

                if ($gecerli) {
                    return $sonuc('tamam', $yil.' raporu hazırlandı.', $son, $gecerli, $baslik);
                }

                return $bugun->gt($son)
                    ? $sonuc('gecikmis', '31 Ocak '.($yil + 1).' tarihine kadar hazırlanmalıydı.', $son, null, $baslik)
                    : $sonuc('yaklasan', '31 Ocak '.($yil + 1).' tarihine kadar hazırlanmalı.', $son, null, $baslik);

            case 'periyodik':
                $gecerli = $enSon($dosyada);

                if (! $gecerli) {
                    return $sonuc($k['yoksa'] ?? 'eksik', 'Kayıt yok, eklemek için dokunun.');
                }

                $son = self::gecerlilikSonu($gecerli);

                if (! $son) {
                    return $sonuc('tamam', 'Belge tarihi girilmemiş.', null, $gecerli);
                }

                $kalan = (int) $bugun->diffInDays($son, false);
                // Kısa periyotlarda (aylık defter / saha raporu) eşik periyodun üçte birini geçmez;
                // yoksa 30 günlük eşikle aylık belge yüklendiği gün "yaklaşıyor" görünürdü.
                $esik = min($yaklasan, max(3, intdiv((int) self::ay($k['anahtar'], $firma) * 30, 3)));

                return match (true) {
                    $kalan < 0 => $sonuc('gecikmis', 'Yenileme tarihi '.$son->format('d.m.Y').' geçti.', $son, $gecerli),
                    $kalan <= $esik => $sonuc('yaklasan', 'Yenileme tarihi '.$son->format('d.m.Y').'.', $son, $gecerli),
                    default => $sonuc('tamam', 'Sonraki: '.$son->format('d.m.Y').'.', $son, $gecerli),
                };

            default: // kayit — yalnız kullanıcının girdiği bitiş tarihleri takip edilir
                $tarihli = $dosyada->filter(fn (ArsivDosya $d) => $d->gecerlilik_sonu);
                $gecen = $tarihli->filter(fn (ArsivDosya $d) => $d->gecerlilik_sonu->lt($bugun));
                $yakin = $tarihli->filter(fn (ArsivDosya $d) => $d->gecerlilik_sonu->gte($bugun) && $bugun->diffInDays($d->gecerlilik_sonu) <= $yaklasan);

                return match (true) {
                    $gecen->isNotEmpty() => $sonuc('gecikmis', $gecen->count().' belgenin süresi geçti.', $gecen->min('gecerlilik_sonu')),
                    $yakin->isNotEmpty() => $sonuc('yaklasan', $yakin->count().' belgenin süresi yaklaşıyor.', $yakin->min('gecerlilik_sonu')),
                    $dosyada->isNotEmpty() => $sonuc('tamam', $dosyada->count().' kayıt.'),
                    default => $sonuc('takipsiz', 'Kayıt yok.'),
                };
        }
    }

    /** "Yıllık Çalışma Planı" → "Çalışma Planı" (beklenen belge başlığında yıl önde). */
    public static function kisaAd(array $k): string
    {
        return trim(preg_replace('/^Yıllık\s+/u', '', $k['ad']));
    }

    /**
     * Firma × kategori matrisi.
     *
     * @param  Collection<int, Firma>  $firmalar
     * @param  Collection<int, ArsivDosya>  $kayitlar  bu firmaların tüm kayıtları
     * @return array<int, array<string, array<string, mixed>>> [firma_id][kategori]
     */
    public static function matris(Collection $firmalar, Collection $kayitlar, array $haric = []): array
    {
        $grup = $kayitlar->groupBy(fn (ArsivDosya $d) => $d->firma_id.'|'.self::kategori($d->kategori)['anahtar']);
        $sonuc = [];

        foreach ($firmalar as $firma) {
            foreach (array_keys(config('arsiv.kategoriler')) as $anahtar) {
                $sonuc[$firma->id][$anahtar] = self::durum($firma, $anahtar, $grup->get($firma->id.'|'.$anahtar, collect()), null, $haric);
            }
        }

        return $sonuc;
    }
}
