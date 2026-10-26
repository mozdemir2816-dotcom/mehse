<?php

namespace App\Support;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Kullanıcının risk analizi Excel şablonları → Risk Kütüphanesi (Risk Kütüphanesi →
 * Risk Analizi Yükle). Üç yöntem (17.10.2026):
 *
 *  - Fine-Kinney: "<Ekipman>_Fine_Kinney_N_Madde.xlsx" — No | Ekipman | Faaliyet |
 *    Tehlike | Risk / sonuç | … | Olasılık P | Frekans F | Şiddet S | … | Açıklayıcı DÖF |
 *    Hedef P/F/S | … | Sorumlu | Önerilen termin | … | 3 dayanak sütunu.
 *    Şiddet kullanıcı ölçeğine çevrilir: 40 → 30 (ölüm), 100 → 40 (çoklu ölüm).
 *  - 5x5 L Matris: aynı düzen, Frekans yok, P/Ş 1-5. Kütüphane puanları Fine-Kinney
 *    ölçeğinde tutulduğu için RiskYontemDonusturucu tablosuyla çevrilir (5x5
 *    değerlendirmeye eklenirken geri çevrilir).
 *  - HAZOP: No | Düğüm | Parametre | Kılavuz kelime | Sapma | Olası nedenler | Olası
 *    sonuçlar | Doğrulanacak korumalar | Öneri / aksiyon | Öncelik | Sorumlu rol | Hedef
 *    tarih | Kapanış kanıtı | Durum. Puansız yüklenir (kullanıcı kararı); puan firmaya
 *    eklenince verilir. Eski HAZOP başlıkları (Senaryo ID, Önerilen aksiyon …) da okunur.
 *
 * Sütunlar başlık metniyle bulunur (sıra değişse de okunur). "Ekipman" / "Düğüm"
 * sütununun " — " öncesi kısmı kategoriyi belirler (çok ekipmanlı dosyada her ekipman
 * ayrı kategori). Açıklayıcı DÖF / önerilen aksiyon → oneri ("Alınacak önlem").
 * kod: ÖNEK-R/E/A-### (E eğitim/yetki, A acil durum, R diğer).
 *
 * Aynı kategori tekrar yüklenirse maddeler kodla güncellenir, dosyada artık olmayan
 * aynı önekli maddeler silinir (revizyonda eski madde kalmasın — kullanıcı tercihi).
 */
class EkipmanRiskKutuphanesi
{
    public const YONTEMLER = [
        'fine_kinney' => 'Fine-Kinney',
        'matris_5x5' => '5x5 L Matris',
        'hazop' => 'HAZOP',
    ];

    /** Başlık (normalize) → alan. Mobil vinç dosyasındaki F/O/Ş düzeni de tanınır. */
    private const BASLIKLAR = [
        'ekipman' => ['ekipman', 'ekipman / bölüm', 'düğüm / ekipman', 'düğüm', 'düğüm / süreç'],
        'faaliyet' => ['faaliyet', 'bölüm / faaliyet'],
        'tehlike' => ['tehlike', 'tehlike / uygunsuzluk', 'tehlike / eksiklik', 'tehlike kaynağı', 'tehlike & risk konusu'],
        'risk' => ['risk / sonuç', 'risk/sonuç', 'risk / olası sonuç', 'tehlikeli durum / olası zarar ve sonuç'],
        'olasilik' => ['olasılık p', 'olasılık', 'o', 'ihtimal', 'i'],
        'frekans' => ['frekans f', 'frekans', 'f'],
        'siddet' => ['şiddet s', 'şiddet', 'ş'],
        'oneri' => ['açıklayıcı döf', 'düzeltici / önleyici faaliyet', 'önerilen aksiyon', 'öneri / aksiyon', 'alınacak önlemler', 'önerilen önlem / faaliyet', 'alınacak kontrol önlemleri (döf)'],
        'son_olasilik' => ['hedef p', 'hedef o'],
        'son_frekans' => ['hedef f'],
        'son_siddet' => ['hedef s', 'hedef ş'],
        'sorumlu' => ['sorumlu', 'sorumlu rol', 'önerilen sorumlu'],
        'termin' => ['önerilen termin', 'termin', 'hedef tarih', 'termin tarihi'],
        'd_standart' => ['standart dayanağı', 'standart / teknik dayanak', 'yasal dayanak & standart açıklaması (neye göre olduğu)'],
        'd_makina' => ['makina emniyeti dayanağı'],
        'd_ekipman' => ['iş ekipmanları dayanağı'],
        // HAZOP
        'parametre' => ['parametre', 'parametre / işlem'],
        'kilavuz' => ['kılavuz sözcük', 'kılavuz kelime'],
        'sapma' => ['sapma'],
        'neden' => ['olası nedenler', 'nedenler', 'neden'],
        'sonuc' => ['olası sonuçlar', 'sonuçlar', 'sonuç', 'olası sonuç / etkilenen'],
        'korumalar' => ['sahada araştırılacak korumalar', 'doğrulanacak korumalar', 'mevcut koruma / kanıt durumu'],
        'oncelik' => ['öncelik'],
        'kapanis' => ['kapanış kanıtı', 'kapanış / doğrulama kanıtı', 'kapanış için doğrulama'],
        'durum' => ['durum', 'faaliyet durumu'],
    ];

    private const ZORUNLU = [
        'fine_kinney' => ['tehlike', 'olasilik', 'frekans', 'siddet'],
        'matris_5x5' => ['tehlike', 'olasilik', 'siddet'],
        'hazop' => ['ekipman', 'kilavuz', 'sapma'],
    ];

    private const DAYANAKLAR = [
        'd_standart' => 'Standart: ',
        'd_makina' => 'Makina Emniyeti Yön.: ',
        'd_ekipman' => 'İş Ekipmanları Yön.: ',
    ];

    /** Kategori adı ön eki (mevcut kütüphane adlarıyla aynı biçim). */
    public const KATEGORI_ONEKI = 'Ekipman – ';

    public const HAZOP_ONEKI = 'HAZOP – ';

    /**
     * Dosyayı okur; ekipman (HAZOP'ta düğüm) başına bir grup döner.
     *
     * @return array<int, array{ekipman: string, kategori_adi: string, maddeler: array<int, array<string, mixed>>}>
     */
    public static function oku(string $dosyaYolu, ?string $dosyaAdi = null, string $yontem = 'fine_kinney'): array
    {
        if (! isset(self::YONTEMLER[$yontem])) {
            throw new RuntimeException("Bilinmeyen yöntem: {$yontem}");
        }

        ExcelBellek::artir();

        $reader = IOFactory::createReaderForFile($dosyaYolu);
        $reader->setReadDataOnly(true);
        $kitap = $reader->load($dosyaYolu);
        $sayfa = $kitap->getSheetByName($yontem === 'hazop' ? 'HAZOP' : 'Risk Analizi') ?? $kitap->getSheet(0);
        $satirlar = $sayfa->toArray(null, true, false, false);

        [$baslikSatiri, $sutun] = static::baslikBul($satirlar, self::ZORUNLU[$yontem], $yontem === 'matris_5x5');
        if ($baslikSatiri === null) {
            throw new RuntimeException(static::tanimadiMesaji($yontem, $satirlar));
        }

        $varsayilanEkipman = static::dosyaAdindanEkipman($dosyaAdi ?? basename($dosyaYolu));
        $gruplar = [];

        foreach (array_slice($satirlar, $baslikSatiri + 1) as $r) {
            $hucre = fn (string $alan) => isset($sutun[$alan]) ? static::temiz($r[$sutun[$alan]] ?? null) : '';

            $madde = $yontem === 'hazop' ? static::hazopMaddesi($hucre) : static::puanliMadde($hucre, $r, $sutun, $yontem);
            if (! $madde) {
                continue;
            }

            // "Kule Vinç — Raylı model — varsa" → kategori "Kule Vinç", faaliyete "(Raylı model)";
            // HAZOP "N1 — Silo dolumu" / "N1 – Dispersiyoner" → "Silo dolumu" / "Dispersiyoner".
            if ($yontem === 'hazop') {
                $parcalar = [];
                $ekipman = trim(preg_replace('/^[A-Z]{1,3}\d+\s*[–—-]\s*/u', '', $hucre('ekipman')));
            } else {
                $parcalar = explode(' — ', $hucre('ekipman'));
                $ekipman = trim(array_shift($parcalar));
            }
            $ekipman = $ekipman ?: $varsayilanEkipman;

            $tur = implode(' — ', array_filter(array_map('trim', $parcalar), fn ($p) => $p !== '' && ! in_array(mb_strtolower($p), ['varsa', 'genel'], true)));
            $faaliyet = (string) $madde['faaliyet'];
            if ($tur !== '' && ! str_contains(mb_strtolower($faaliyet), mb_strtolower($tur))) {
                $faaliyet = trim($faaliyet.' ('.$tur.')');
            }

            $gruplar[$ekipman][] = array_merge($madde, [
                'bolum' => Str::limit($yontem === 'hazop' ? $hucre('ekipman') : $ekipman, 250, ''),
                'faaliyet' => $faaliyet !== '' ? Str::limit($faaliyet, 250, '') : null,
                'harf' => static::harf($faaliyet),
            ]);
        }

        $onek = $yontem === 'hazop' ? self::HAZOP_ONEKI : self::KATEGORI_ONEKI;

        return collect($gruplar)->map(fn (array $maddeler, string $ekipman) => [
            'ekipman' => $ekipman,
            'kategori_adi' => static::kategoriAdi($ekipman, $onek),
            'maddeler' => $maddeler,
        ])->values()->all();
    }

    /**
     * Dosya HAZOP şablonu mu? (Sapma + Kılavuz sözcük + Düğüm sütunları.) Risk Sihirbazı
     * Excel akışında "bu HAZOP" demek için; puan sütunu olmadığı için 5x5 / Fine-Kinney
     * okuyucusuna gitmemeli.
     */
    public static function hazopMi(string $dosyaYolu): bool
    {
        try {
            $reader = IOFactory::createReaderForFile($dosyaYolu);
            $reader->setReadDataOnly(true);
            $kitap = $reader->load($dosyaYolu);
            $sayfa = $kitap->getSheetByName('HAZOP') ?? $kitap->getSheet(0);

            return static::baslikBul($sayfa->toArray(null, true, false, false), self::ZORUNLU['hazop'])[0] !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * HAZOP dosyasını Risk Sihirbazı'nın aday biçimine çevirir (puansız, hücre metinleri
     * olduğu gibi). Her aday `hazop` alanını taşır; çıktı bu alandan basılır.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function hazopAdaylari(string $dosyaYolu, ?string $dosyaAdi = null): array
    {
        $adaylar = [];

        foreach (static::oku($dosyaYolu, $dosyaAdi, 'hazop') as $grup) {
            foreach ($grup['maddeler'] as $madde) {
                $adaylar[] = $madde + [
                    'anahtar' => 'hz-'.substr(md5(($madde['tehlike'] ?? '').uniqid('', true)), 0, 10),
                    'kaynak' => 'excel',
                    'tehlike_id' => null,
                    'aciklama' => null,
                ];
            }
        }

        return $adaylar;
    }

    /**
     * Fine-Kinney / 5x5 satırı. Puanı olmayan satır (not, ara başlık) atlanır.
     *
     * @param  array<string, int>  $sutun
     * @return array<string, mixed>|null
     */
    private static function puanliMadde(\Closure $hucre, array $r, array $sutun, string $yontem): ?array
    {
        if ($hucre('tehlike') === '' || static::puan($r, $sutun, 'olasilik') === null) {
            return null;
        }

        [$o, $f, $s] = [static::puan($r, $sutun, 'olasilik'), static::puan($r, $sutun, 'frekans'), static::puan($r, $sutun, 'siddet')];
        [$so, $sf, $ss] = [static::puan($r, $sutun, 'son_olasilik'), static::puan($r, $sutun, 'son_frekans'), static::puan($r, $sutun, 'son_siddet')];

        $matris = null;

        if ($yontem === 'matris_5x5') {
            foreach ([$o, $s, $so, $ss] as $deger) {
                if ($deger !== null && ($deger < 1 || $deger > 5)) {
                    throw new RuntimeException('5x5 L Matris puanları 1-5 arasında olmalı ("'.Str::limit($hucre('tehlike'), 60).'" satırında '.$deger.' var). Dosya Fine-Kinney ise yöntemi "Fine-Kinney" seçin.');
                }
            }
            // FK'ye çevirme kayıplı geri döner; kaynak 5x5 değerleri aynen saklanır.
            $matris = ['olasilik' => $o, 'siddet' => $s, 'son_olasilik' => $so, 'son_siddet' => $ss];
            [$o, $f, $s] = RiskYontemDonusturucu::matrisDegeriniFineKinneye($o, $s);
            [$so, $sf, $ss] = RiskYontemDonusturucu::matrisDegeriniFineKinneye($so, $ss);
        } else {
            [$s, $ss] = [static::siddet($s), static::siddet($ss)];
        }

        $dayanak = collect(self::DAYANAKLAR)
            ->map(fn ($etiket, $alan) => $hucre($alan) !== '' ? $etiket.$hucre($alan) : null)
            ->filter()->implode('; ');

        return [
            'faaliyet' => $hucre('faaliyet'),
            'tehlike' => $hucre('tehlike'),
            'risk' => $hucre('risk') ?: null,
            'mevcut_onlem' => null,
            'oneri' => $hucre('oneri') ?: null,
            'sorumlu' => $hucre('sorumlu') ? Str::limit($hucre('sorumlu'), 250, '') : null,
            'termin' => $hucre('termin') ? Str::limit($hucre('termin'), 250, '') : null,
            'mevzuat' => $dayanak ?: null,
            'olasilik' => $o,
            'frekans' => $f,
            'siddet' => $s,
            'son_olasilik' => $so,
            'son_frekans' => $sf,
            'son_siddet' => $ss,
            'matris' => $matris,
        ];
    }

    /**
     * HAZOP senaryosu: Sapma → tehlike, Parametre / Kılavuz sözcük → faaliyet,
     * Neden + Sonuç → risk, [Öncelik] Önerilen aksiyon + doğrulanacak korumalar → oneri.
     *
     * @return array<string, mixed>|null
     */
    private static function hazopMaddesi(\Closure $hucre): ?array
    {
        if ($hucre('sapma') === '') {
            return null;
        }

        $risk = collect(['Neden: ' => $hucre('neden'), 'Sonuç: ' => $hucre('sonuc')])
            ->filter()->map(fn ($v, $etiket) => $etiket.$v)->implode("\n");
        $oneri = collect([
            ($hucre('oncelik') !== '' ? '['.$hucre('oncelik').'] ' : '').$hucre('oneri'),
            $hucre('korumalar') !== '' ? 'Sahada doğrulanacak korumalar: '.$hucre('korumalar') : '',
        ])->map(fn ($v) => trim($v))->filter()->implode("\n");

        return [
            'faaliyet' => collect([$hucre('parametre'), $hucre('kilavuz')])->filter()->implode(' / '),
            'tehlike' => $hucre('sapma'),
            'risk' => $risk ?: null,
            'mevcut_onlem' => null,
            'oneri' => $oneri ?: null,
            'sorumlu' => $hucre('sorumlu') ? Str::limit($hucre('sorumlu'), 250, '') : null,
            'termin' => $hucre('termin') ? Str::limit($hucre('termin'), 250, '') : null,
            'mevzuat' => 'IEC 61882 (HAZOP)',
            'olasilik' => null,
            'frekans' => null,
            'siddet' => null,
            'son_olasilik' => null,
            'son_frekans' => null,
            'son_siddet' => null,
            // Kaynak Excel'deki hücre metinleri olduğu gibi (çıktı bunlardan basılır).
            'hazop' => [
                'dugum' => $hucre('ekipman'),
                'parametre' => $hucre('parametre'),
                'kilavuz' => $hucre('kilavuz'),
                'sapma' => $hucre('sapma'),
                'neden' => $hucre('neden'),
                'sonuc' => $hucre('sonuc'),
                'korumalar' => $hucre('korumalar'),
                'oneri' => $hucre('oneri'),
                'oncelik' => $hucre('oncelik'),
                'sorumlu' => $hucre('sorumlu'),
                'termin' => $hucre('termin'),
                'kapanis' => $hucre('kapanis'),
                'durum' => $hucre('durum'),
            ],
        ];
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
    public static function kategoriAdi(string $ekipman, string $onek = self::KATEGORI_ONEKI): string
    {
        $kucuk = mb_strtolower(strtr($ekipman, ['I' => 'ı', 'İ' => 'i']));
        $ilk = mb_substr($kucuk, 0, 1);
        $ilk = $ilk === 'i' ? 'İ' : ($ilk === 'ı' ? 'I' : mb_strtoupper($ilk));

        return $onek.$ilk.mb_substr($kucuk, 1);
    }

    /** Kategorinin mevcut kod öneki; yoksa addan türetilir ("Ekipman – Kazıcı yükleyici" → "KAZ"). */
    private static function onek(TehlikeKategorisi $kategori): string
    {
        $mevcut = Tehlike::query()->where('tehlike_kategorisi_id', $kategori->id)
            ->where('kod', 'like', '%-_-___')->value('kod');
        if ($mevcut && preg_match('/^([A-Z0-9]+)-[REA]-\d{3}$/', $mevcut, $e)) {
            return $e[1];
        }

        $ad = preg_replace('/^.*?\s–\s/u', '', $kategori->ad);
        $harfler = strtoupper(preg_replace('/[^a-z]/i', '', Str::ascii($ad)));

        return str_pad(substr($harfler, 0, 3), 3, 'X');
    }

    /**
     * @param  array<int, array<int, mixed>>  $satirlar
     * @param  array<int, string>  $zorunlu
     * @return array{0: ?int, 1: array<string, int>}
     */
    private static function baslikBul(array $satirlar, array $zorunlu, bool $ikinciBlok = false): array
    {
        foreach (array_slice($satirlar, 0, 20, true) as $i => $r) {
            $sutun = static::sutunlar($r, $ikinciBlok);

            if (count(array_intersect($zorunlu, array_keys($sutun))) === count($zorunlu)) {
                return [$i, $sutun];
            }

            // İki satırlık başlık: "MEVCUT DURUM" / "HEDEF DURUM" üst başlığının altında
            // İ / F / Ş alt başlıkları. Alt satırda değer varsa o, yoksa üst satır geçerli;
            // ikinci puan bloğu önlem sonrası (hedef) risk olur. Veri alt satırdan sonra başlar.
            $alt = $satirlar[$i + 1] ?? null;
            if ($alt === null) {
                continue;
            }

            $birlesik = [];
            foreach ($r + $alt as $c => $_) {
                $birlesik[$c] = static::temiz($alt[$c] ?? null) !== '' ? $alt[$c] : ($r[$c] ?? null);
            }
            $sutun = static::sutunlar($birlesik, true);

            if (count(array_intersect($zorunlu, array_keys($sutun))) === count($zorunlu)) {
                return [$i + 1, $sutun];
            }
        }

        return [null, []];
    }

    /**
     * Başlık "O (1–5)" gibi ölçek ifadesi taşıyabilir; ölçek kırpılarak eşlenir.
     * $ikinciBlok: 5x5 şablonunda ikinci O/Ş bloğu önlem sonrası (artık) risk olur.
     *
     * @param  array<int, mixed>  $r
     * @return array<string, int>
     */
    private static function sutunlar(array $r, bool $ikinciBlok = false): array
    {
        $sutun = [];
        foreach ($r as $c => $deger) {
            $n = mb_strtolower(strtr(static::temiz($deger), ['I' => 'ı', 'İ' => 'i']));
            $n = trim(preg_replace('/\s*\(\s*\d+\s*[–-]\s*\d+\s*\)$/u', '', $n));
            foreach (self::BASLIKLAR as $alan => $esler) {
                if (! in_array($n, $esler, true)) {
                    continue;
                }

                if (! isset($sutun[$alan])) {
                    $sutun[$alan] = $c;
                } elseif ($ikinciBlok && in_array($alan, ['olasilik', 'frekans', 'siddet'], true) && ! isset($sutun['son_'.$alan])) {
                    $sutun['son_'.$alan] = $c;
                }

                continue 2;
            }
        }

        return $sutun;
    }

    /** Seçilen yönteme uymayan dosyada, dosyanın hangi yönteme benzediğini söyler. */
    private static function tanimadiMesaji(string $yontem, array $satirlar): string
    {
        foreach (self::ZORUNLU as $diger => $zorunlu) {
            if ($diger !== $yontem && static::baslikBul($satirlar, $zorunlu)[0] !== null) {
                return 'Bu dosya '.self::YONTEMLER[$yontem].' şablonuna uymuyor; '.self::YONTEMLER[$diger].' dosyasına benziyor — yöntemi "'.self::YONTEMLER[$diger].'" seçin.';
            }
        }

        $gerekli = ['fine_kinney' => '"Tehlike", "Olasılık", "Frekans" ve "Şiddet"', 'matris_5x5' => '"Tehlike", "Olasılık" ve "Şiddet"', 'hazop' => '"Düğüm / ekipman", "Kılavuz sözcük" ve "Sapma"'][$yontem];

        return self::YONTEMLER[$yontem].' şablonu tanınmadı: '.$gerekli.' sütunları bulunamadı.';
    }

    private static function dosyaAdindanEkipman(string $dosyaAdi): string
    {
        $ad = preg_replace('/(_(Fine_Kinney|HAZOP|5x5|Matris).*)?\.xlsx?$/i', '', $dosyaAdi);

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
