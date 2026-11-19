<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\Firma;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * e-SGK Sigortalı İşe Giriş Bildirgesi PDF okuyucu.
 * Yüklenen tekli veya çok sayfalı SGK İşe Giriş Bildirgesi PDF dosyasından
 * T.C. Kimlik No, Ad Soyad, İşe Giriş Tarihi ve Meslek/Görev bilgilerini
 * otomatik ayıklar ve firmaya çalışan olarak kaydeder.
 */
class SgkIseGirisOkuyucu
{
    /**
     * Bir veya birden fazla PDF dosyasından çalışan kayıtlarını çıkarır.
     *
     * @param  string|array<int, string>  $dosyaYollari
     * @return array<int, array{ad_soyad: string, tc: ?string, ise_giris: ?string, gorev: ?string}>
     */
    public static function dosyalardanCikar(string|array $dosyaYollari): array
    {
        $yollar = is_array($dosyaYollari) ? $dosyaYollari : [$dosyaYollari];
        $sonuclar = [];

        foreach ($yollar as $yol) {
            if (! is_string($yol) || ! is_file($yol)) {
                continue;
            }

            foreach (static::dosyadanCikar($yol) as $kayit) {
                $anahtar = ($kayit['tc'] ?: '').'|'.mb_strtolower($kayit['ad_soyad'], 'UTF-8');
                $sonuclar[$anahtar] = $kayit;
            }
        }

        return array_values($sonuclar);
    }

    /**
     * Tek bir PDF dosyasından (çok sayfalı olabilir) çalışan listesi döner.
     *
     * @return array<int, array{ad_soyad: string, tc: ?string, ise_giris: ?string, gorev: ?string}>
     */
    public static function dosyadanCikar(string $dosyaYolu): array
    {
        $sayfaMetinleri = [];

        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($dosyaYolu);
            foreach ($pdf->getPages() as $sayfa) {
                $metin = trim($sayfa->getText());
                if ($metin !== '') {
                    $sayfaMetinleri[] = $metin;
                }
            }
            if (! $sayfaMetinleri) {
                $tamMetin = trim($pdf->getText());
                if ($tamMetin !== '') {
                    $sayfaMetinleri[] = $tamMetin;
                }
            }
        } catch (Throwable) {
            // Test veya düz metin yedeklemesi
            $ham = @file_get_contents($dosyaYolu);
            if (is_string($ham) && trim($ham) !== '') {
                $sayfaMetinleri[] = $ham;
            }
        }

        $kayitlar = [];
        foreach ($sayfaMetinleri as $metin) {
            $kayit = static::metindenCikar($metin);
            if ($kayit) {
                $kayitlar[] = $kayit;
            }
        }

        return $kayitlar;
    }

    /**
     * Bir SGK İşe Giriş Bildirgesi sayfasının düz metninden çalışan alanlarını ayıklar.
     *
     * @return ?array{ad_soyad: string, tc: ?string, ise_giris: ?string, gorev: ?string}
     */
    public static function metindenCikar(string $metin): ?array
    {
        $temiz = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", ' '], $metin);

        $tc = static::tcBul($temiz);
        $adSoyad = static::adSoyadBul($temiz);
        $iseGiris = static::iseGirisBul($temiz);
        $gorev = static::gorevBul($temiz);

        if (! $adSoyad) {
            return null;
        }

        return [
            'ad_soyad' => $adSoyad,
            'tc' => $tc,
            'ise_giris' => $iseGiris,
            'gorev' => $gorev,
        ];
    }

    /**
     * Çıkarılan çalışan kayıtlarını seçili firmaya ekler veya günceller.
     *
     * @param  array<int, array{ad_soyad: string, tc: ?string, ise_giris: ?string, gorev: ?string}>  $kayitlar
     * @return array{eklenen: int, guncellenen: int, calisanlar: Collection<int, Calisan>}
     */
    public static function firmayaKaydet(Firma $firma, array $kayitlar): array
    {
        $eklenen = 0;
        $guncellenen = 0;
        $calisanlar = collect();

        foreach ($kayitlar as $k) {
            $adSoyad = trim((string) ($k['ad_soyad'] ?? ''));
            if ($adSoyad === '') {
                continue;
            }

            $tc = filled($k['tc'] ?? null) ? trim((string) $k['tc']) : null;

            $mevcut = null;
            if ($tc) {
                $mevcut = $firma->calisanlar()->where('tc', $tc)->first();
            }
            if (! $mevcut) {
                $mevcut = $firma->calisanlar()
                    ->whereRaw('LOWER(ad_soyad) = ?', [mb_strtolower($adSoyad, 'UTF-8')])
                    ->first();
            }

            if ($mevcut) {
                $mevcut->fill(array_filter([
                    'ad_soyad' => $adSoyad,
                    'tc' => $tc ?: $mevcut->tc,
                    'ise_giris' => $k['ise_giris'] ?: $mevcut->ise_giris?->toDateString(),
                    'gorev' => $k['gorev'] ?: $mevcut->gorev,
                ], fn ($v) => $v !== null && $v !== ''));
                $mevcut->aktif = true;
                $mevcut->save();
                $guncellenen++;
                $calisanlar->push($mevcut);
            } else {
                $yeni = $firma->calisanlar()->create([
                    'ad_soyad' => $adSoyad,
                    'tc' => $tc,
                    'ise_giris' => $k['ise_giris'] ?? null,
                    'gorev' => $k['gorev'] ?? null,
                    'aktif' => true,
                ]);
                $eklenen++;
                $calisanlar->push($yeni);
            }
        }

        return [
            'eklenen' => $eklenen,
            'guncellenen' => $guncellenen,
            'calisanlar' => $calisanlar,
        ];
    }

    private static function tcBul(string $metin): ?string
    {
        // 1. Etiketli T.C. Kimlik No
        if (preg_match('/(?:T\.?\s*C\.?\s*Kimlik\s*(?:No|Numaras[ıi])|TCKNO|Kimlik\s*No)[^\d]{0,35}([1-9]\d{10})\b/iu', $metin, $m)) {
            return $m[1];
        }

        // 2. Algoritmik doğrulamalı 11 haneli T.C. Kimlik No
        if (preg_match_all('/(?<!\d)([1-9]\d{10})(?!\d)/', $metin, $eslesmeler)) {
            foreach ($eslesmeler[1] as $aday) {
                if (static::gecerliTcMi($aday)) {
                    return $aday;
                }
            }

            return $eslesmeler[1][0] ?? null;
        }

        return null;
    }

    private static function gecerliTcMi(string $tc): bool
    {
        if (! preg_match('/^[1-9]\d{10}$/', $tc)) {
            return false;
        }

        $d = array_map('intval', str_split($tc));
        $tek = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
        $cift = $d[1] + $d[3] + $d[5] + $d[7];
        $h10 = (($tek * 7) - $cift) % 10;
        if ($h10 < 0) {
            $h10 += 10;
        }
        $h11 = array_sum(array_slice($d, 0, 10)) % 10;

        return $d[9] === $h10 && $d[10] === $h11;
    }

    private static function adSoyadBul(string $metin): ?string
    {
        // 1. "Adı Soyadı : AHMET YILMAZ" veya "Sigortalının Adı Soyadı : ..."
        if (preg_match('/(?:Sigortal[ıi]n[ıi]n\s+)?Ad[ıi]\s*Soyad[ıi]\s*[:\-]?\s*([A-ZÇĞİÖŞÜa-zçğıöşü][A-ZÇĞİÖŞÜa-zçğıöşü\s\.]{3,60})/u', $metin, $m)) {
            $aday = static::adTemizle($m[1]);
            if ($aday) {
                return $aday;
            }
        }

        // 2. Ayrı "Adı : AHMET" ve "Soyadı : YILMAZ" alanları
        $ad = null;
        $soyad = null;

        if (preg_match('/(?:^|\n|\s)Ad[ıi]\s*[:\-]\s*([A-ZÇĞİÖŞÜa-zçğıöşü][A-ZÇĞİÖŞÜa-zçğıöşü\s]{1,35}?)(?=\s+(?:Soyad[ıi]|Baba|Ana|Doğum|Cinsiyet|T\.?C)|\n|$)/u', $metin, $mAd)) {
            $ad = static::adTemizle($mAd[1]);
        }

        if (preg_match('/Soyad[ıi]\s*[:\-]\s*([A-ZÇĞİÖŞÜa-zçğıöşü][A-ZÇĞİÖŞÜa-zçğıöşü\s]{1,35}?)(?=\s+(?:Baba|Ana|Doğum|Cinsiyet|İlk|Uyruğu|T\.?C)|\n|$)/u', $metin, $mSoyad)) {
            $soyad = static::adTemizle($mSoyad[1]);
        }

        if ($ad && $soyad) {
            return trim($ad.' '.$soyad);
        }

        return $ad;
    }

    private static function adTemizle(string $ham): ?string
    {
        $satir = strtok($ham, "\n");
        $satir = preg_replace('/\s+(?:Soyad[ıi]|Baba\s*Ad[ıi]|Ana\s*Ad[ıi]|Doğum|T\.?C\.?|Cinsiyet|Medeni).*$/iu', '', (string) $satir);
        $satir = trim(preg_replace('/\s+/', ' ', (string) $satir));

        if (mb_strlen($satir, 'UTF-8') < 3) {
            return null;
        }

        return mb_convert_case($satir, MB_CASE_TITLE, 'UTF-8');
    }

    private static function iseGirisBul(string $metin): ?string
    {
        if (preg_match('/(?:İşe\s*(?:Giriş|Başlama)\s*Tarihi|Sigortal[ıi]l[ıi]ğ[ıi]n\s*Başlang[ıi]ç\s*Tarihi|Giriş\s*Tarihi)[^\d]{0,30}(\d{2}[\.\/\-]\d{2}[\.\/\-]\d{4})/iu', $metin, $m)) {
            return static::tarihCoz($m[1]);
        }

        return null;
    }

    private static function tarihCoz(string $ham): ?string
    {
        $normal = str_replace(['/', '-'], '.', trim($ham));
        try {
            return Carbon::createFromFormat('d.m.Y', $normal)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function gorevBul(string $metin): ?string
    {
        // Örn: "Meslek Adı : 7212.05 - Gazaltı Kaynakçısı" veya "Görevi : Kaynakçı"
        if (preg_match('/(?:Türk\s*Meslek\s*Ad[ıi]|Meslek\s*(?:Kodu\s*(?:ve|\/)\s*)?Ad[ıi]|Görevi|Mesleği)\s*[:\-]?\s*([^\n]{2,70})/iu', $metin, $m)) {
            $gorev = trim($m[1]);
            // Baştaki NACE/ISCO meslek kodunu (örn. "7212.05 - ") temizle
            $gorev = preg_replace('/^\d{3,4}(?:\.\d{1,3})?\s*[\-\/]?\s*/u', '', $gorev);
            $gorev = preg_replace('/\s+(?:ÇSG|İş\s*Kolu|Görev\s*Kodu|Ücret).*$/iu', '', (string) $gorev);
            $gorev = trim((string) $gorev);

            if ($gorev !== '' && $gorev !== '-' && mb_strlen($gorev, 'UTF-8') >= 2) {
                return mb_convert_case($gorev, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return null;
    }
}
