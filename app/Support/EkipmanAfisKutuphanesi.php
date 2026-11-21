<?php

namespace App\Support;

use App\Models\EkipmanAfisi;
use App\Models\Firma;
use App\Models\IsEkipmani;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

/**
 * Ekipman afiş kütüphanesi (21.11.2026).
 *
 * Akış (API kullanılmaz): Gemini sohbetinde her makine için yazısız resim + "Afiş Yazıları"
 * istemleriyle YAPIN / YAPMAYIN / KKD tablosu üretilir. Resimler anahtar.png adıyla (ör.
 * forklift.png), yazılar Excel'in "Afiş Yazıları Sonuç" sekmesiyle (Dosya adı | Makine | YAPIN |
 * YAPMAYIN | KKD) yüklenir. Anahtarlar config('ekipman_afis.tipler').
 *
 * Afiş PDF'i A4 dikey: sarı başlık bandında makine adı, resim, YAPIN (yeşil) / YAPMAYIN
 * (kırmızı) sütunları, zorunlu KKD (mavi, TS EN ISO 7010 emredici renk), ekipman QR'ı ve firma.
 */
class EkipmanAfisKutuphanesi
{
    public const KLASOR = 'ekipman-afis';

    public const AYRAC = '/\s*[•|]\s*/u';

    /** Config'teki her makine için kayıt yoksa boş kayıt açar (ilk açılışta). */
    public static function kayitlariHazirla(): void
    {
        $mevcut = EkipmanAfisi::query()->pluck('anahtar')->all();
        foreach (config('ekipman_afis.tipler', []) as $anahtar => $t) {
            if (! in_array($anahtar, $mevcut, true)) {
                EkipmanAfisi::create(['anahtar' => $anahtar, 'ad' => $t['ad'], 'grup' => $t['grup'] ?? null]);
            }
        }
    }

    /** Dosya adından anahtar: "Forklift.PNG", "forklift (1).png" → "forklift". */
    public static function anahtar(string $dosyaAdi): string
    {
        $ad = pathinfo($dosyaAdi, PATHINFO_FILENAME);
        $ad = preg_replace('/\s*\(\d+\)$/', '', $ad);
        $ad = preg_replace('/^afis-/', '', Str::slug($ad));

        return $ad;
    }

    /**
     * Resimleri yükler: tek tek resim dosyaları ya da içinde resim olan ZIP.
     *
     * @param  array<int, UploadedFile|string>  $dosyalar  UploadedFile ya da yerel yol
     * @return array{yuklenen: array<int, string>, eslesmeyen: array<int, string>}
     */
    public static function gorselleriYukle(array $dosyalar): array
    {
        static::kayitlariHazirla();
        $sonuc = ['yuklenen' => [], 'eslesmeyen' => []];

        foreach ($dosyalar as $dosya) {
            $yol = $dosya instanceof UploadedFile ? $dosya->getRealPath() : (string) $dosya;
            $ad = $dosya instanceof UploadedFile ? $dosya->getClientOriginalName() : basename($yol);

            if (strtolower(pathinfo($ad, PATHINFO_EXTENSION)) === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($yol) !== true) {
                    $sonuc['eslesmeyen'][] = $ad.' (ZIP açılamadı)';

                    continue;
                }
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $ic = $zip->getNameIndex($i);
                    if (str_ends_with($ic, '/') || str_starts_with(basename($ic), '.') || str_contains($ic, '__MACOSX')) {
                        continue;
                    }
                    static::gorselKaydet(basename($ic), (string) $zip->getFromIndex($i), $sonuc);
                }
                $zip->close();

                continue;
            }

            static::gorselKaydet($ad, (string) file_get_contents($yol), $sonuc);
        }

        return $sonuc;
    }

    /** @param  array{yuklenen: array<int, string>, eslesmeyen: array<int, string>}  $sonuc */
    private static function gorselKaydet(string $ad, string $icerik, array &$sonuc): void
    {
        $uzanti = strtolower(pathinfo($ad, PATHINFO_EXTENSION));
        if (! in_array($uzanti, ['png', 'jpg', 'jpeg', 'webp'], true) || @getimagesizefromstring($icerik) === false) {
            $sonuc['eslesmeyen'][] = $ad.' (resim değil)';

            return;
        }

        $afis = EkipmanAfisi::query()->where('anahtar', static::anahtar($ad))->first();
        if (! $afis) {
            $sonuc['eslesmeyen'][] = $ad;

            return;
        }

        $yeni = self::KLASOR.'/'.$afis->anahtar.'.'.($uzanti === 'jpeg' ? 'jpg' : $uzanti);
        if ($afis->gorsel_yolu && $afis->gorsel_yolu !== $yeni) {
            Storage::disk('public')->delete($afis->gorsel_yolu);
        }
        Storage::disk('public')->put($yeni, $icerik);
        $afis->forceFill(['gorsel_yolu' => $yeni])->save();
        $afis->touch();
        $sonuc['yuklenen'][] = $afis->ad;
    }

    /**
     * Gemini'nin verdiği yazı tablosunu içe alır. Excel'de "Afiş Yazıları Sonuç" sekmesi
     * (yoksa başlıkları uyan ilk sekme) okunur; başlık satırı tekrar ederse atlanır.
     *
     * @return array{guncellenen: array<int, string>, eslesmeyen: array<int, string>, atlanan_kkd: array<int, string>}
     */
    public static function yazilariIceAktar(string $yol): array
    {
        static::kayitlariHazirla();
        $kitap = IOFactory::load($yol);
        $sayfa = $kitap->getSheetByName('Afiş Yazıları Sonuç');
        $satirlar = [];

        foreach ($sayfa ? [$sayfa] : $kitap->getAllSheets() as $s) {
            $veri = $s->toArray(null, true, false, false);
            $bas = static::basliklar($veri[0] ?? []);
            if ($bas) {
                $satirlar = array_map(fn ($r) => static::satirOku($r, $bas), array_slice($veri, 1));
                break;
            }
        }

        return static::yazilariKaydet(array_filter($satirlar));
    }

    /**
     * Markdown tablo ya da sekmeyle ayrılmış metin (Gemini'den kopyalanan) — Excel'e
     * yapıştırmadan doğrudan içe almak için.
     *
     * @return array{guncellenen: array<int, string>, eslesmeyen: array<int, string>, atlanan_kkd: array<int, string>}
     */
    public static function metniIceAktar(string $metin): array
    {
        static::kayitlariHazirla();
        $satirlar = [];
        $bas = null;

        foreach (preg_split('/\R/u', $metin) as $s) {
            $s = trim($s);
            if ($s === '' || preg_match('/^\|?\s*:?-{3,}/', $s)) {
                continue;
            }
            $hucreler = str_contains($s, "\t") ? explode("\t", $s) : explode('|', trim($s, '| '));
            $hucreler = array_map('trim', $hucreler);
            if ($b = static::basliklar($hucreler)) {
                $bas = $b;

                continue;
            }
            if ($bas) {
                $satirlar[] = static::satirOku($hucreler, $bas);
            }
        }

        return static::yazilariKaydet(array_filter($satirlar));
    }

    /** @return array<string, int>|null başlık => sütun sırası */
    private static function basliklar(array $satir): ?array
    {
        $bas = [];
        foreach ($satir as $i => $h) {
            $n = EkipmanKatalogu::norm((string) $h);
            $anahtar = match (true) {
                str_starts_with($n, 'dosya') => 'dosya',
                $n === 'makine' => 'makine',
                $n === 'yapın' || $n === 'yapin' => 'yapin',
                $n === 'yapmayın' || $n === 'yapmayin' => 'yapmayin',
                $n === 'kkd' => 'kkd',
                default => null,
            };
            if ($anahtar) {
                $bas[$anahtar] = $i;
            }
        }

        return isset($bas['yapin'], $bas['yapmayin']) && (isset($bas['dosya']) || isset($bas['makine'])) ? $bas : null;
    }

    /** @return array<string, mixed>|null */
    private static function satirOku(array $r, array $bas): ?array
    {
        $al = fn (string $k) => isset($bas[$k]) ? trim((string) ($r[$bas[$k]] ?? '')) : '';
        $liste = fn (string $k) => array_values(array_filter(array_map(
            fn ($m) => trim(preg_replace('/^(?:[-*]|\d+[.)])\s*/u', '', trim($m))),
            preg_split(self::AYRAC, $al($k))
        )));

        $satir = ['dosya' => $al('dosya'), 'makine' => $al('makine'), 'yapin' => $liste('yapin'), 'yapmayin' => $liste('yapmayin'),
            'kkd' => array_values(array_filter(array_map('trim', preg_split('/[,;•]/u', $al('kkd')))))];

        return ($satir['dosya'] !== '' || $satir['makine'] !== '') && $satir['yapin'] ? $satir : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $satirlar
     * @return array{guncellenen: array<int, string>, eslesmeyen: array<int, string>, atlanan_kkd: array<int, string>}
     */
    private static function yazilariKaydet(array $satirlar): array
    {
        $kkdListe = array_keys(config('ekipman_afis.kkd', []));
        $kkdNorm = array_combine(array_map([EkipmanKatalogu::class, 'norm'], $kkdListe), $kkdListe);
        $afisler = EkipmanAfisi::query()->get();
        $sonuc = ['guncellenen' => [], 'eslesmeyen' => [], 'atlanan_kkd' => []];

        foreach ($satirlar as $s) {
            $afis = ($s['dosya'] !== '' ? $afisler->firstWhere('anahtar', static::anahtar($s['dosya'])) : null)
                ?? $afisler->first(fn ($a) => EkipmanKatalogu::norm($a->ad) === EkipmanKatalogu::norm($s['makine']));

            if (! $afis) {
                $sonuc['eslesmeyen'][] = $s['dosya'] ?: $s['makine'];

                continue;
            }

            $kkd = [];
            foreach ($s['kkd'] as $k) {
                if ($ad = $kkdNorm[EkipmanKatalogu::norm($k)] ?? null) {
                    $kkd[] = $ad;
                } else {
                    $sonuc['atlanan_kkd'][] = $k;
                }
            }

            $afis->forceFill([
                'yapin' => array_slice($s['yapin'], 0, 8),
                'yapmayin' => array_slice($s['yapmayin'], 0, 6),
                'kkd' => array_values(array_unique($kkd)),
                'yz_taslak' => true,
                'onaylandi_at' => null,
                'onaylayan_id' => null,
            ])->save();
            $sonuc['guncellenen'][] = $afis->ad;
        }

        $sonuc['atlanan_kkd'] = array_values(array_unique($sonuc['atlanan_kkd']));

        return $sonuc;
    }

    /**
     * Firmadaki ekipmanın afişi: kontrol formu adı → katalog tipi → ekipman tanımı sırasıyla
     * eşleşen ilk hazır (resim + yazı) afiş.
     */
    public static function ekipmanAfisi(IsEkipmani $e, ?Collection $hazirlar = null): ?EkipmanAfisi
    {
        $adaylar = array_filter([
            EkipmanKatalogu::tanim($e->tip)['kontrol_formu'] ?? null,
            EkipmanKatalogu::tipBul($e->tip),
            $e->tip,
            $e->ekipman_adi,
        ]);
        $normlar = array_map([EkipmanKatalogu::class, 'norm'], $adaylar);
        $afisler = $hazirlar ?? static::hazirAfisler();

        foreach ($normlar as $n) {
            if ($a = $afisler->first(fn ($x) => EkipmanKatalogu::norm($x->ad) === $n)) {
                return $a;
            }
        }

        return null;
    }

    /** Resmi ve yazısı olan afişler (bir listede çok ekipman eşlenirken bir kez okunur). */
    public static function hazirAfisler(): Collection
    {
        return EkipmanAfisi::query()->whereNotNull('gorsel_yolu')->whereNotNull('yapin')->get()->filter->hazirMi()->values();
    }

    /** A4 dikey afiş PDF içeriği. Ekipman verilirse QR + künye, firma verilirse logo + unvan basılır. */
    public static function pdfIcerik(EkipmanAfisi $afis, ?IsEkipmani $e = null, ?Firma $firma = null): string
    {
        $firma ??= $e?->firma;
        $gorsel = $afis->gorselVarMi() ? Storage::disk('public')->path($afis->gorsel_yolu) : null;

        return Pdf::loadView('pdf.ekipman.afis', [
            'afis' => $afis,
            'e' => $e,
            'firma' => $firma,
            'logo' => TalimatUretici::firmaLogosu($firma),
            'qr' => $e?->qrDataUri(),
            'gorsel' => $gorsel,
            'baslik' => $baslik = static::baslik($e?->ekipman_adi ?: $afis->ad),
            'olcu' => static::olcu($baslik, $afis),
            'kkdKodlari' => config('ekipman_afis.kkd', []),
        ])->setPaper('a4')->output();
    }

    /**
     * Uzun ad tek satıra, çok/uzun madde tek sayfaya sığsın diye yazı ve resim boyu (pt / mm).
     * Ölçü 6+5 kısa madde ve ~14 harflik ada göre; alt bilgi sayfanın dibine sabit.
     *
     * @return array{ad: float, madde: float, resim: int}
     */
    public static function olcu(string $baslik, EkipmanAfisi $afis): array
    {
        $uzunluk = mb_strlen($baslik);
        $satir = max(
            collect($afis->yapin ?? [])->sum(fn ($m) => (int) ceil(mb_strlen($m) / 38)),
            collect($afis->yapmayin ?? [])->sum(fn ($m) => (int) ceil(mb_strlen($m) / 38)),
        );

        $kkdSatiri = (int) ceil(count($afis->kkd ?? []) / 4);

        return [
            'ad' => $uzunluk <= 14 ? 31 : ($uzunluk <= 22 ? 25 : 20),
            'madde' => $satir <= 10 ? 11.2 : 10,
            'resim' => max(50, 92 - max(0, $satir - 10) * 6 - max(0, $kkdSatiri - 2) * 9),
        ];
    }

    /** Başlık: parantez içi açıklamalar atılır, Türkçe büyük harf ("Forklift / Akülü…" → "FORKLİFT"). */
    public static function baslik(string $ad): string
    {
        $ad = trim(preg_replace('/\s*\(.*?\)\s*/u', ' ', $ad));
        $ad = trim(explode(' / ', $ad)[0]);

        return mb_strtoupper(strtr($ad, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
    }

    public static function dosyaAdi(EkipmanAfisi $afis, ?IsEkipmani $e = null): string
    {
        return 'afis-'.Str::slug($e?->ekipman_adi ?: $afis->ad).'.pdf';
    }
}
