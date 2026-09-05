<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\EgitimKaydi;
use App\Models\EgitimTuru;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelTarih;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Profilim > Eğitimler — Çalışan × eğitim türü (kullanıcının EgitimTuru::
 * aktifListe listesi — "Konu Ekle" ile büyür) matrisinin Excel ile toplu
 * yüklenmesi (isgpratik 139-140.jpg). Şablon, kullanıcının portföyündeki
 * aktif çalışanlarla ve var olan tarihlerle önceden doldurularak indirilir;
 * aynı dosya düzenlenip geri yüklenebilir. Satır eşleştirme: T.C. Kimlik No
 * doluysa TC ile, boşsa Ad Soyad + Firma ile.
 */
class EgitimKayitExcelIceAktarici
{
    private const KIMLIK_BASLIKLARI = ['Çalışan', 'Firma', 'Departman', 'Pozisyon', 'Tehlike Sınıfı', 'T.C. Kimlik No'];

    /**
     * @return array{basarili: int, hatalar: array<int, string>}
     */
    public static function iceAktar(string $dosyaYolu, int $userId): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);

        $satirlar = $reader->load($dosyaYolu)
            ->getActiveSheet()
            ->toArray(null, true, false, false);

        if (count($satirlar) < 2) {
            return ['basarili' => 0, 'hatalar' => ['Dosyada veri satırı bulunamadı.']];
        }

        $baslikSatiri = array_shift($satirlar);
        $sutunlar = static::sutunEslestir($baslikSatiri, EgitimTuru::aktifListe($userId));

        if (! in_array('calisan', $sutunlar, true)) {
            return ['basarili' => 0, 'hatalar' => ['"Çalışan" sütunu bulunamadı. Şablonu indirip sütun adlarını kontrol edin.']];
        }

        $calisanlar = Calisan::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->with('firma:id,unvan')
            ->get();

        $basarili = 0;
        $hatalar = [];

        foreach ($satirlar as $i => $satir) {
            $satirNo = $i + 2;

            if (static::satirBosMu($satir)) {
                continue;
            }

            $kimlik = [];
            $turDegerleri = [];

            foreach ($sutunlar as $idx => $alan) {
                if ($alan === null) {
                    continue;
                }

                $deger = is_string($satir[$idx] ?? null) ? trim($satir[$idx]) : ($satir[$idx] ?? null);

                if (in_array($alan, ['calisan', 'firma', 'tc'], true)) {
                    $kimlik[$alan] = $deger;
                } else {
                    $turDegerleri[$alan] = $deger;
                }
            }

            if (blank($kimlik['calisan'] ?? null)) {
                $hatalar[] = "Satır {$satirNo}: Çalışan boş, atlandı.";

                continue;
            }

            $calisan = static::calisanBul($calisanlar, $kimlik['tc'] ?? null, (string) $kimlik['calisan'], (string) ($kimlik['firma'] ?? ''));

            if (! $calisan) {
                $hatalar[] = "Satır {$satirNo}: \"{$kimlik['calisan']}\" çalışanı bulunamadı (T.C./Ad Soyad + Firma eşleşmedi).";

                continue;
            }

            foreach ($turDegerleri as $tur => $deger) {
                if ($deger === '' || $deger === null || static::bosDegerMi((string) $deger)) {
                    continue;
                }

                $tarih = static::tarihCoz($deger);

                if (! $tarih) {
                    continue;
                }

                try {
                    EgitimKaydi::updateOrCreate(
                        ['calisan_id' => $calisan->id, 'tur' => $tur],
                        ['tarih' => $tarih]
                    );
                } catch (Throwable $e) {
                    $hatalar[] = "Satır {$satirNo}: {$e->getMessage()}";
                }
            }

            $basarili++;
        }

        return ['basarili' => $basarili, 'hatalar' => $hatalar];
    }

    public static function sablonIndir(int $userId): StreamedResponse
    {
        $kitap = static::sablonUret($userId);
        $yazici = new Xlsx($kitap);

        return response()->streamDownload(function () use ($yazici) {
            $yazici->save('php://output');
        }, 'egitim-kayitlari-sablonu.xlsx');
    }

    private static function sablonUret(int $userId): Spreadsheet
    {
        $turler = EgitimTuru::aktifListe($userId);
        $baslik = array_merge(self::KIMLIK_BASLIKLARI, $turler->pluck('ad')->all());

        $kitap = new Spreadsheet();
        $sayfa = $kitap->getActiveSheet();
        $sayfa->setTitle('Eğitim Kayıtları');
        $sayfa->fromArray($baslik, null, 'A1');

        $calisanlar = Calisan::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
            ->where('aktif', true)
            ->with(['firma:id,unvan,tehlike_sinifi', 'egitimKayitlari'])
            ->orderBy('ad_soyad')
            ->get();

        $satir = 2;

        foreach ($calisanlar as $calisan) {
            $kayitlar = $calisan->egitimKayitlari->keyBy('tur');

            $satirVerisi = [
                $calisan->ad_soyad,
                $calisan->firma?->unvan,
                $calisan->departman,
                $calisan->gorev,
                $calisan->firma?->tehlikeSinifiEtiketi(),
                $calisan->tc,
            ];

            foreach ($turler as $tur) {
                $kayit = $kayitlar->get($tur->anahtar);
                $satirVerisi[] = $kayit ? $kayit->tarih->format('d.m.Y') : '';
            }

            $sayfa->fromArray($satirVerisi, null, 'A'.$satir);
            $satir++;
        }

        foreach (range('A', 'F') as $harf) {
            $sayfa->getColumnDimension($harf)->setAutoSize(true);
        }

        return $kitap;
    }

    /** @param  Collection<int, Calisan>  $calisanlar  Kullanıcının portföyündeki çalışanlar (önceden yüklenmiş) */
    private static function calisanBul(Collection $calisanlar, ?string $tc, string $adSoyad, string $firmaAdi): ?Calisan
    {
        $tcTemiz = $tc !== null ? preg_replace('/\D/', '', $tc) : '';

        if (filled($tcTemiz)) {
            $calisan = $calisanlar->firstWhere('tc', $tcTemiz);

            if ($calisan) {
                return $calisan;
            }
        }

        $adNorm = static::normalize($adSoyad);
        $firmaNorm = static::normalize($firmaAdi);

        return $calisanlar->first(fn (Calisan $c) => static::normalize($c->ad_soyad) === $adNorm
            && static::normalize((string) $c->firma?->unvan) === $firmaNorm);
    }

    /**
     * @param  Collection<int, EgitimTuru>  $turler
     * @return array<int, string|null> sütun indeksi => 'calisan'|'firma'|'tc'|egitim türü anahtarı
     */
    private static function sutunEslestir(array $baslikSatiri, Collection $turler): array
    {
        $turEslesme = $turler
            ->mapWithKeys(fn (EgitimTuru $t) => [static::normalize($t->ad) => $t->anahtar])
            ->all();

        $kimlikEslesme = [
            'calisan' => 'calisan',
            'adsoyad' => 'calisan',
            'firma' => 'firma',
            'firmaadi' => 'firma',
            'tckimlikno' => 'tc',
            'tc' => 'tc',
        ];

        $sutunlar = [];

        foreach ($baslikSatiri as $i => $baslik) {
            $norm = static::normalize((string) $baslik);
            $sutunlar[$i] = $kimlikEslesme[$norm] ?? $turEslesme[$norm] ?? null;
        }

        return $sutunlar;
    }

    private static function bosDegerMi(string $deger): bool
    {
        return in_array(trim($deger), ['', '—', '-', 'yok'], true);
    }

    private static function tarihCoz(mixed $deger): ?string
    {
        try {
            if (is_numeric($deger)) {
                return ExcelTarih::excelToDateTimeObject((float) $deger)->format('Y-m-d');
            }

            return Carbon::parse((string) $deger)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function satirBosMu(array $satir): bool
    {
        return collect($satir)->every(fn ($h) => blank(is_string($h) ? trim($h) : $h));
    }

    private static function normalize(string $metin): string
    {
        $metin = strtr($metin, [
            'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'ı' => 'i',
            'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u',
        ]);
        $metin = mb_strtolower($metin);

        return preg_replace('/[^a-z0-9]/', '', $metin) ?? '';
    }
}
