<?php

namespace App\Support;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Kullanıcının ekipman risk analizi şablonu ("<Ekipman>_Fine_Kinney_N_Madde.xlsx":
 * No | Ekipman | Faaliyet | Tehlike | Risk / sonuç | … | Olasılık P | Frekans F |
 * Şiddet S | … | Açıklayıcı DÖF | Hedef P/F/S | … | Sorumlu | Önerilen termin | … |
 * Standart / Makina Emniyeti / İş Ekipmanları dayanağı) → Risk Kütüphanesi.
 *
 * Önceden her dosya sohbette betikle (seeder + canlı SQL) aktarılıyordu; bu sınıf
 * aynı dönüşümü panelden yapar (Risk Kütüphanesi → Ekipman Risk Analizi Yükle):
 *  - sütunlar başlık metniyle bulunur (sıra değişse de okunur),
 *  - "Ekipman" sütununun " — " öncesi kısmı kategoriyi belirler (çok ekipmanlı
 *    dosyada her ekipman ayrı kategori), sonrası faaliyete eklenir,
 *  - Şiddet kullanıcı ölçeğine çevrilir: 40 → 30 (ölüm), 100 → 40 (çoklu ölüm),
 *  - Açıklayıcı DÖF → oneri ("Alınacak önlem"), mevcut_onlem boş,
 *  - kod: ÖNEK-R/E/A-### (E eğitim/yetki, A acil durum, R diğer).
 *
 * Aynı kategori tekrar yüklenirse maddeler kodla güncellenir, dosyada artık olmayan
 * aynı önekli maddeler silinir (revizyonda eski madde kalmasın — kullanıcı tercihi).
 */
class EkipmanRiskKutuphanesi
{
    /** Başlık (normalize) → alan. Mobil vinç dosyasındaki F/O/Ş düzeni de tanınır. */
    private const BASLIKLAR = [
        'ekipman' => ['ekipman', 'ekipman / bölüm'],
        'faaliyet' => ['faaliyet', 'bölüm / faaliyet'],
        'tehlike' => ['tehlike', 'tehlike / uygunsuzluk'],
        'risk' => ['risk / sonuç', 'risk / olası sonuç'],
        'olasilik' => ['olasılık p', 'olasılık', 'o'],
        'frekans' => ['frekans f', 'frekans', 'f'],
        'siddet' => ['şiddet s', 'şiddet', 'ş'],
        'oneri' => ['açıklayıcı döf', 'düzeltici / önleyici faaliyet'],
        'son_olasilik' => ['hedef p', 'hedef o'],
        'son_frekans' => ['hedef f'],
        'son_siddet' => ['hedef s', 'hedef ş'],
        'sorumlu' => ['sorumlu'],
        'termin' => ['önerilen termin', 'termin'],
        'd_standart' => ['standart dayanağı', 'standart / teknik dayanak'],
        'd_makina' => ['makina emniyeti dayanağı'],
        'd_ekipman' => ['iş ekipmanları dayanağı'],
    ];

    private const ZORUNLU = ['tehlike', 'olasilik', 'frekans', 'siddet'];

    private const DAYANAKLAR = [
        'd_standart' => 'Standart: ',
        'd_makina' => 'Makina Emniyeti Yön.: ',
        'd_ekipman' => 'İş Ekipmanları Yön.: ',
    ];

    /** Kategori adı ön eki (mevcut kütüphane adlarıyla aynı biçim). */
    public const KATEGORI_ONEKI = 'Ekipman – ';

    /**
     * Dosyayı okur; ekipman başına bir grup döner.
     *
     * @return array<int, array{ekipman: string, kategori_adi: string, maddeler: array<int, array<string, mixed>>}>
     */
    public static function oku(string $dosyaYolu, ?string $dosyaAdi = null): array
    {
        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);
        $kitap = $reader->load($dosyaYolu);
        $sayfa = $kitap->getSheetByName('Risk Analizi') ?? $kitap->getSheet(0);
        $satirlar = $sayfa->toArray(null, true, false, false);

        [$baslikSatiri, $sutun] = static::baslikBul($satirlar);
        if ($baslikSatiri === null) {
            throw new RuntimeException('Ekipman risk analizi şablonu tanınmadı: "Tehlike", "Olasılık", "Frekans" ve "Şiddet" sütunları bulunamadı.');
        }

        $varsayilanEkipman = static::dosyaAdindanEkipman($dosyaAdi ?? basename($dosyaYolu));
        $gruplar = [];

        foreach (array_slice($satirlar, $baslikSatiri + 1) as $r) {
            $hucre = fn (string $alan) => isset($sutun[$alan]) ? static::temiz($r[$sutun[$alan]] ?? null) : '';

            if ($hucre('tehlike') === '' || ! is_numeric(RiskSkorlama::sayi($r[$sutun['olasilik']] ?? null))) {
                continue;
            }

            $parcalar = explode(' — ', $hucre('ekipman'));
            $ekipman = trim(array_shift($parcalar)) ?: $varsayilanEkipman;
            $tur = implode(' — ', array_filter(array_map('trim', $parcalar), fn ($p) => $p !== '' && ! in_array(mb_strtolower($p), ['varsa', 'genel'], true)));
            $faaliyet = $hucre('faaliyet');
            if ($tur !== '' && ! str_contains(mb_strtolower($faaliyet), mb_strtolower($tur))) {
                $faaliyet = trim($faaliyet.' ('.$tur.')');
            }

            $dayanak = collect(self::DAYANAKLAR)
                ->map(fn ($etiket, $alan) => $hucre($alan) !== '' ? $etiket.$hucre($alan) : null)
                ->filter()->implode('; ');

            $gruplar[$ekipman][] = [
                'bolum' => Str::limit($ekipman, 250, ''),
                'faaliyet' => $faaliyet !== '' ? Str::limit($faaliyet, 250, '') : null,
                'tehlike' => $hucre('tehlike'),
                'risk' => $hucre('risk') ?: null,
                'mevcut_onlem' => null,
                'oneri' => $hucre('oneri') ?: null,
                'sorumlu' => $hucre('sorumlu') ? Str::limit($hucre('sorumlu'), 250, '') : null,
                'termin' => $hucre('termin') ? Str::limit($hucre('termin'), 250, '') : null,
                'mevzuat' => $dayanak ?: null,
                'olasilik' => static::puan($r, $sutun, 'olasilik'),
                'frekans' => static::puan($r, $sutun, 'frekans'),
                'siddet' => static::siddet(static::puan($r, $sutun, 'siddet')),
                'son_olasilik' => static::puan($r, $sutun, 'son_olasilik'),
                'son_frekans' => static::puan($r, $sutun, 'son_frekans'),
                'son_siddet' => static::siddet(static::puan($r, $sutun, 'son_siddet')),
                'harf' => static::harf($faaliyet),
            ];
        }

        return collect($gruplar)->map(fn (array $maddeler, string $ekipman) => [
            'ekipman' => $ekipman,
            'kategori_adi' => static::kategoriAdi($ekipman),
            'maddeler' => $maddeler,
        ])->values()->all();
    }

    /**
     * Grubu kütüphaneye yazar.
     *
     * @param  array<int, array<string, mixed>>  $maddeler  oku()'nun döndürdüğü maddeler
     * @param  array<int, string>  $ekipmanTipleri  bağlanacak isg.periyodik_kontrol.tipler 'ad' değerleri
     * @return array{kategori: TehlikeKategorisi, yeni_kategori: bool, eklenen: int, guncellenen: int, silinen: int}
     */
    public static function kaydet(string $kategoriAdi, array $maddeler, array $ekipmanTipleri = []): array
    {
        $kategoriAdi = trim($kategoriAdi);
        if ($kategoriAdi === '' || ! $maddeler) {
            throw new RuntimeException('Kategori adı ve en az bir madde gerekli.');
        }

        $anahtar = Str::slug($kategoriAdi, '_') ?: 'ekipman';
        $kategori = TehlikeKategorisi::firstOrNew(['anahtar' => $anahtar]);
        $yeni = ! $kategori->exists;
        if ($yeni) {
            $kategori->fill(['ad' => $kategoriAdi, 'sira' => (int) TehlikeKategorisi::max('sira') + 1]);
        }
        $tipler = array_values(array_unique(array_filter([...($kategori->ekipman_tipleri ?? []), ...$ekipmanTipleri])));
        $kategori->ekipman_tipleri = $tipler ?: null;
        $kategori->save();

        $onek = static::onek($kategori);
        $eklenen = $guncellenen = 0;
        $kodlar = [];

        foreach (array_values($maddeler) as $i => $m) {
            $kod = sprintf('%s-%s-%03d', $onek, $m['harf'] ?? 'R', $i + 1);
            $kodlar[] = $kod;
            unset($m['harf']);

            $tehlike = Tehlike::firstOrNew(['tehlike_kategorisi_id' => $kategori->id, 'kod' => $kod]);
            $tehlike->exists ? $guncellenen++ : $eklenen++;
            $tehlike->fill($m)->save();
        }

        $silinen = Tehlike::query()
            ->where('tehlike_kategorisi_id', $kategori->id)
            ->where('kod', 'like', $onek.'-%')
            ->whereNotIn('kod', $kodlar)
            ->delete();

        return ['kategori' => $kategori, 'yeni_kategori' => $yeni, 'eklenen' => $eklenen, 'guncellenen' => $guncellenen, 'silinen' => $silinen];
    }

    /** "Kazıcı Yükleyici" → "Ekipman – Kazıcı yükleyici" (mevcut kategori adlarıyla aynı yazım). */
    public static function kategoriAdi(string $ekipman): string
    {
        $kucuk = mb_strtolower(strtr($ekipman, ['I' => 'ı', 'İ' => 'i']));
        $ilk = mb_substr($kucuk, 0, 1);
        $ilk = $ilk === 'i' ? 'İ' : ($ilk === 'ı' ? 'I' : mb_strtoupper($ilk));

        return self::KATEGORI_ONEKI.$ilk.mb_substr($kucuk, 1);
    }

    /** Kategorinin mevcut kod öneki; yoksa addan türetilir ("Kazıcı yükleyici" → "KAZ"). */
    private static function onek(TehlikeKategorisi $kategori): string
    {
        $mevcut = Tehlike::query()->where('tehlike_kategorisi_id', $kategori->id)
            ->where('kod', 'like', '%-_-___')->value('kod');
        if ($mevcut && preg_match('/^([A-Z0-9]+)-[REA]-\d{3}$/', $mevcut, $e)) {
            return $e[1];
        }

        $ad = str_starts_with($kategori->ad, self::KATEGORI_ONEKI) ? mb_substr($kategori->ad, mb_strlen(self::KATEGORI_ONEKI)) : $kategori->ad;
        $harfler = strtoupper(preg_replace('/[^a-z]/i', '', Str::ascii($ad)));

        return str_pad(substr($harfler, 0, 3), 3, 'X');
    }

    /**
     * @param  array<int, array<int, mixed>>  $satirlar
     * @return array{0: ?int, 1: array<string, int>}
     */
    private static function baslikBul(array $satirlar): array
    {
        foreach (array_slice($satirlar, 0, 20, true) as $i => $r) {
            $sutun = [];
            foreach ($r as $c => $deger) {
                $n = mb_strtolower(strtr(static::temiz($deger), ['I' => 'ı', 'İ' => 'i']));
                foreach (self::BASLIKLAR as $alan => $esler) {
                    if (! isset($sutun[$alan]) && in_array($n, $esler, true)) {
                        $sutun[$alan] = $c;

                        continue 2;
                    }
                }
            }

            if (count(array_intersect(self::ZORUNLU, array_keys($sutun))) === count(self::ZORUNLU)) {
                return [$i, $sutun];
            }
        }

        return [null, []];
    }

    private static function dosyaAdindanEkipman(string $dosyaAdi): string
    {
        $ad = preg_replace('/(_Fine_Kinney.*)?\.xlsx?$/i', '', $dosyaAdi);

        return trim(str_replace('_', ' ', $ad)) ?: 'Ekipman';
    }

    private static function temiz(mixed $v): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $v));
    }

    /** @param  array<string, int>  $sutun */
    private static function puan(array $r, array $sutun, string $alan): ?float
    {
        return isset($sutun[$alan]) ? RiskSkorlama::sayi($r[$sutun[$alan]] ?? null) : null;
    }

    /** Standart Fine-Kinney → kullanıcı ölçeği: 40 ölüm → 30, 100 çoklu ölüm → 40. */
    private static function siddet(?float $v): ?float
    {
        return match ($v) {
            40.0 => 30.0,
            100.0 => 40.0,
            default => $v,
        };
    }

    private static function harf(string $faaliyet): string
    {
        $k = mb_strtolower($faaliyet);

        return match (true) {
            str_contains($k, 'eğitim') || str_contains($k, 'yetki') || str_contains($k, 'belge') => 'E',
            str_contains($k, 'acil') || str_contains($k, 'yangın') || str_contains($k, 'kurtarma') || str_contains($k, 'ilk yardım') => 'A',
            default => 'R',
        };
    }
}
