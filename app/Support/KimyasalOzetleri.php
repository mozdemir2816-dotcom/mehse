<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\KimyasalUrun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Kimyasal MSDS (GBF) özeti ve kimyasal afişi (22.11.2026).
 *
 * İçerik Gemini sohbetinde (API kotası harcanmadan) üretilir: firmanın özeti olmayan
 * kimyasalları için tek istem hazırlanır, cevap (JSON dizi) yapıştırılınca her ürüne
 * yazılır ve "YZ taslağı — uzman onayı bekliyor" etiketiyle hemen kullanılır.
 * Aynı uzmanın başka firmasında özeti olan ürün (CAS ya da ad eşleşmesi) istem
 * gerekmeden kopyalanır.
 *
 * Özet biçimi (kimyasal_urunler.ozet):
 *   sinyal_kelime, ghs[], tehlikeler[], onlemler[], kkd[], ilk_yardim{soluma,cilt,goz,yutma},
 *   yangin, dokulme, depolama, maruziyet, bertaraf, fiziksel
 */
class KimyasalOzetleri
{
    /** Bir istemde en çok kaç ürün (Gemini cevabı kesilmesin). */
    public const ISTEM_BASINA = 8;

    public const METIN_ALANLARI = ['yangin', 'dokulme', 'depolama', 'maruziyet', 'bertaraf', 'fiziksel'];

    public const ILK_YARDIM = ['soluma' => 'Solunursa', 'cilt' => 'Cilde temasında', 'goz' => 'Göze temasında', 'yutma' => 'Yutulursa'];

    /**
     * MSDS'si (GBF PDF'i) yüklü ve metni okunabilen her ürün için ayrı istem (GBF metni istemin içinde);
     * MSDS'si olmayan ya da okunamayanlar 8'erli toplu istemde, ürün adına göre.
     *
     * @param  Collection<int, KimyasalUrun>  $urunler
     * @return array<int, array{baslik: string, istem: string}>
     */
    public static function istemler(Collection $urunler): array
    {
        $istemler = [];
        $msdssiz = collect();

        foreach ($urunler->values() as $u) {
            $metin = static::sdsMetni($u);
            if ($metin === null) {
                $msdssiz->push($u);

                continue;
            }
            $istemler[] = ['baslik' => $u->urun_adi.' — yüklü MSDS\'den', 'istem' => static::istem(collect([$u]), $metin)];
        }

        $parcalar = $msdssiz->chunk(static::ISTEM_BASINA)->values();
        foreach ($parcalar as $i => $p) {
            $istemler[] = [
                'baslik' => 'MSDS\'si yüklü olmayan '.$p->count().' kimyasal'.($parcalar->count() > 1 ? ' ('.($i + 1).'/'.$parcalar->count().')' : ''),
                'istem' => static::istem($p->values()),
            ];
        }

        return $istemler;
    }

    /**
     * Yüklü MSDS PDF'inin metni (Gemini'ye istemle birlikte verilir). Taranmış (resim) PDF, Word/Excel
     * ya da okunamayan dosyada null: o ürün toplu isteme düşer, sayfa dosyayı Gemini'ye ayrıca eklemeyi söyler.
     */
    public static function sdsMetni(KimyasalUrun $u): ?string
    {
        if (! $u->sdsVarMi() || strtolower(pathinfo($u->sds_dosya_yolu, PATHINFO_EXTENSION)) !== 'pdf'
            || ! Storage::disk('public')->exists($u->sds_dosya_yolu)) {
            return null;
        }

        try {
            $metin = (new PdfParser)->parseFile(Storage::disk('public')->path($u->sds_dosya_yolu))->getText();
        } catch (\Throwable) {
            return null;
        }

        $metin = trim(preg_replace(["/[ \t]+/u", "/\n{3,}/u"], [' ', "\n\n"], (string) $metin));

        // 300 harften azı taranmış PDF demektir (metin katmanı yok).
        return mb_strlen($metin) < 300 ? null : Str::limit($metin, 60000, "\n[…]");
    }

    /** @param  Collection<int, KimyasalUrun>  $urunler */
    public static function istem(Collection $urunler, ?string $sdsMetni = null): string
    {
        $liste = $urunler->map(fn (KimyasalUrun $u) => '- id '.$u->id.': '.collect([
            $u->urun_adi,
            $u->cas_no ? 'CAS '.$u->cas_no : null,
            $u->tedarikci ? 'Üretici/tedarikçi: '.$u->tedarikci : null,
            'Kullanıldığı alan / iş: '.($u->kullanim_alani ?: 'belirtilmemiş'),
        ])->filter()->implode(' | '))->implode("\n");

        $kkd = implode(', ', config('isg.kimyasal.kkd', []));
        $kaynak = $sdsMetni
            ? "Özeti YALNIZ aşağıdaki Güvenlik Bilgi Formu (MSDS/GBF) metnine dayandır; formda olmayan bilgiyi ekleme, formdaki H/P ifadelerini, ilk yardımı, söndürücüyü ve KKD'yi aynen esas al."
            : "Bu ürünlerin MSDS'si sisteme yüklenmemiş: bilinen Güvenlik Bilgi Formu (KKDİK, CLP/GHS) bilgilerine göre yaz; ticari üründe tipik bileşimi esas al, emin olmadığın bilgiyi uydurma, o alanı kısa ve genel güvenli ifadeyle doldur.";
        $metinBolumu = $sdsMetni ? "\n\nMSDS METNİ (başı):\n<<<\n{$sdsMetni}\n>>>" : '';

        return <<<TXT
Sen Türkiye'de çalışan bir iş güvenliği uzmanısın. Aşağıdaki kimyasal için tek sayfalık MSDS özeti afişi hazırlayacaksın. Afiş, kimyasalın KULLANILDIĞI ALANA asılacak ve oradaki çalışan tarafından ayakta, birkaç saniyede okunacak. Bu yüzden bilgiyi o alandaki işe göre yorumla: çalışanın kimyasalı kullanırken, dökülünce, sıçrayınca, yangında ne yapacağını kısa ve somut yaz; laboratuvar dili ve gereksiz ayrıntı kullanma.
{$kaynak}

Ürünler:
{$liste}{$metinBolumu}

Cevabı YALNIZ geçerli bir JSON dizi olarak ver (açıklama yazma). Her ürün için bir nesne:
{
  "id": ürün id (sayı, yukarıdaki listeden),
  "urun_adi": "listedeki ad",
  "sinyal_kelime": "Tehlike" ya da "Uyarı" ya da "",
  "ghs": ["ghs02", "ghs07"]  (yalnız ghs01…ghs09 kodları),
  "tehlikeler": en çok 5 madde, her biri ≤70 karakter, H kodu başta: "H225 Kolay alevlenir sıvı ve buhar",
  "onlemler": en çok 5 madde, her biri ≤70 karakter, P kodu başta: "P210 Isı ve kıvılcımdan uzak tutun",
  "kkd": yalnız şu listeden seç: {$kkd},
  "ilk_yardim": {"soluma": "≤160 karakter", "cilt": "≤160", "goz": "≤160", "yutma": "≤160"},
  "yangin": "uygun ve uygun olmayan söndürücü, ≤160 karakter",
  "dokulme": "döküntü/sızıntıda yapılacaklar, ≤200 karakter",
  "depolama": "bu alanda kullanırken ve depolarken uyulacak kurallar, ≤200 karakter",
  "maruziyet": "maruziyet sınırı (varsa) ve havalandırma vb. önlem, ≤200 karakter",
  "bertaraf": "atık bertarafı, ≤140 karakter",
  "fiziksel": "görünüm, koku, parlama noktası gibi kısa bilgi, ≤120 karakter"
}
Dil: sade Türkçe, emir kipi ("Bol suyla yıkayın"). Tıbbi tavsiyede "112'yi arayın" ve "Ulusal Zehir Danışma Merkezi 114" ifadelerini gerektiğinde kullan.
TXT;
    }

    /**
     * Gemini cevabını firmanın ürünlerine yazar (yalnız o firmanın id'leri; id yoksa ad eşleşmesi).
     *
     * @return array{kaydedilen: array<int, string>, hatalar: array<int, string>}
     */
    public static function iceAktar(Firma $firma, string $metin): array
    {
        $veri = OzelEkipmanTipleri::jsonAyikla($metin);
        if ($veri === null) {
            return ['kaydedilen' => [], 'hatalar' => ['Cevapta JSON dizi bulunamadı. Köşeli parantezle başlayan bölümün tamamını yapıştırın.']];
        }

        $urunler = $firma->kimyasalUrunler()->get();
        $kaydedilen = [];
        $hatalar = [];

        foreach ($veri as $i => $o) {
            if (! is_array($o)) {
                continue;
            }
            $u = $urunler->firstWhere('id', (int) ($o['id'] ?? 0))
                ?? $urunler->first(fn (KimyasalUrun $u) => static::norm($u->urun_adi) === static::norm((string) ($o['urun_adi'] ?? '')));

            if (! $u) {
                $hatalar[] = ($o['urun_adi'] ?? ('Kayıt '.($i + 1))).': bu firmada bulunamadı, atlandı.';

                continue;
            }

            $ozet = static::temizle($o);
            if (! filled($ozet['tehlikeler']) && ! filled(array_filter($ozet['ilk_yardim']))) {
                $hatalar[] = $u->urun_adi.': tehlike ve ilk yardım bilgisi boş geldi, atlandı.';

                continue;
            }

            static::yaz($u, $ozet, true);
            $kaydedilen[] = $u->urun_adi;
        }

        return ['kaydedilen' => $kaydedilen, 'hatalar' => $hatalar];
    }

    /**
     * Aynı uzmanın başka firmasında özeti olan ürünleri (CAS ya da ad eşleşmesi) bu firmaya kopyalar.
     *
     * @return array<int, string> kopyalanan ürün adları
     */
    public static function hazirOzetleriUygula(Firma $firma): array
    {
        $eksikler = $firma->kimyasalUrunler()->get()->reject->ozetVarMi();
        if ($eksikler->isEmpty()) {
            return [];
        }

        $kaynaklar = KimyasalUrun::query()
            ->whereNotNull('ozet')
            ->where('firma_id', '!=', $firma->id)
            ->whereIn('firma_id', Firma::query()->where('user_id', $firma->user_id)->select('id'))
            ->latest('updated_at')
            ->get()
            ->filter->ozetVarMi();

        $kopyalanan = [];
        foreach ($eksikler as $u) {
            $kaynak = $kaynaklar->first(fn (KimyasalUrun $k) => filled($u->cas_no) && static::norm($k->cas_no) === static::norm($u->cas_no))
                ?? $kaynaklar->first(fn (KimyasalUrun $k) => static::norm($k->urun_adi) === static::norm($u->urun_adi));

            if ($kaynak) {
                $u->forceFill([
                    'ozet' => $kaynak->ozet,
                    'ozet_yz_taslak' => $kaynak->ozet_yz_taslak,
                    'ozet_onaylandi_at' => $kaynak->ozet_onaylandi_at,
                ])->save();
                $kopyalanan[] = $u->urun_adi;
            }
        }

        return $kopyalanan;
    }

    public static function onayla(KimyasalUrun $u): void
    {
        $u->forceFill(['ozet_onaylandi_at' => now()])->save();
    }

    /** @param  array<string, mixed>  $ozet */
    public static function yaz(KimyasalUrun $u, array $ozet, bool $yz): void
    {
        $u->forceFill([
            // Hangi MSDS dosyasından üretildiği: MSDS sonradan değişirse özet "yenilenmeli" görünür.
            'ozet' => ['kaynak_sds' => $u->sds_dosya_yolu] + $ozet,
            'ozet_yz_taslak' => $yz,
            'ozet_onaylandi_at' => $yz ? null : now(),
            // Üründe GHS işaretlenmemişse özetteki piktogramlar envantere de geçer.
            'ghs' => filled($u->ghs) ? $u->ghs : ($ozet['ghs'] ?: $u->ghs),
        ])->save();
    }

    /**
     * Gemini çıktısını güvenli, sınırlı biçime getirir (fazla madde/uzun metin kırpılır).
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    public static function temizle(array $o): array
    {
        $metin = fn ($x, int $max) => Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $x)), $max, '…');
        $liste = fn ($l, int $adet, int $max) => array_values(array_slice(array_filter(array_map(fn ($x) => $metin($x, $max), (array) $l)), 0, $adet));

        $izinliKkd = config('isg.kimyasal.kkd', []);
        $kkd = collect((array) ($o['kkd'] ?? []))
            ->map(fn ($k) => static::kkdEsle((string) $k, $izinliKkd))
            ->filter()->unique()->values()->all();

        $sinyal = static::norm((string) ($o['sinyal_kelime'] ?? ''));
        $ozet = [
            'sinyal_kelime' => str_starts_with($sinyal, 'tehlike') ? 'Tehlike' : (str_starts_with($sinyal, 'uyar') ? 'Uyarı' : ''),
            'ghs' => collect((array) ($o['ghs'] ?? []))->map(fn ($k) => strtolower(preg_replace('/\s+/', '', (string) $k)))
                ->filter(fn ($k) => preg_match('/^ghs0[1-9]$/', $k))->unique()->values()->all(),
            'tehlikeler' => $liste($o['tehlikeler'] ?? [], 5, 90),
            'onlemler' => $liste($o['onlemler'] ?? [], 5, 90),
            'kkd' => $kkd,
            'ilk_yardim' => collect(array_keys(static::ILK_YARDIM))
                ->mapWithKeys(fn ($k) => [$k => $metin(((array) ($o['ilk_yardim'] ?? []))[$k] ?? '', 200)])->all(),
        ];
        foreach (static::METIN_ALANLARI as $alan) {
            $ozet[$alan] = $metin($o[$alan] ?? '', 240);
        }

        return $ozet;
    }

    /**
     * Afişin A4'e (alt bilgi 262 mm'de) sığması için yazı ölçeği: içerik yüksekliği kabaca tahmin edilir,
     * sığmıyorsa yazı ve boşluklar orantılı küçülür (en az 0,7). Metin uzunlukları temizle() ile sınırlı.
     *
     * @param  array<string, mixed>  $o
     */
    public static function sikilik(array $o, int $adUzunluk, float $adPunto, int $piktogram): float
    {
        $satir = fn (?string $m, int $genislik) => max(1, (int) ceil(mb_strlen((string) $m) / $genislik));
        $liste = fn (array $l) => array_sum(array_map(fn ($m) => $satir($m, 44), $l));

        $mm = 0.0;
        $mm += $satir(str_repeat('x', $adUzunluk), (int) (188 / ($adPunto * 0.24))) * $adPunto * 0.39 + 12;   // ad + alt satır + bant
        $mm += $piktogram ? 46 : 0;                                                                              // piktogram + etiket
        $mm += 12 + max($liste($o['tehlikeler']), $liste($o['onlemler']), 1) * 4.6 + max(count($o['tehlikeler']), count($o['onlemler'])) * 1.7;
        $mm += $o['kkd'] ? 8 + ceil(count($o['kkd']) / 4) * 6.5 : 0;
        $iy = $o['ilk_yardim'];
        $mm += array_filter($iy) ? 13 + (max($satir($iy['soluma'] ?? '', 50), $satir($iy['cilt'] ?? '', 50)) + max($satir($iy['goz'] ?? '', 50), $satir($iy['yutma'] ?? '', 50))) * 4.4 + 12 : 0;
        $mm += filled($o['yangin'] ?? null) || filled($o['dokulme'] ?? null) ? 12 + max($satir($o['yangin'] ?? '', 50), $satir($o['dokulme'] ?? '', 50)) * 4.4 : 0;
        $mm += filled($o['depolama'] ?? null) ? 11 + $satir($o['depolama'], 100) * 4.4 : 0;

        return round(max(0.7, min(1, 244 / $mm)), 2);
    }

    /** Gemini'nin serbest KKD adını sabit listeye eşler ("gaz maskesi" → Solunum maskesi). */
    private static function kkdEsle(string $k, array $izinli): ?string
    {
        $n = static::norm($k);
        $esanlam = ['gozluk' => 'Koruyucu gözlük', 'siper' => 'Yüz siperi', 'eldiven' => 'Kimyasal koruyucu eldiven',
            'toz' => 'Toz maskesi', 'maske' => 'Solunum maskesi (filtreli)', 'solunum' => 'Solunum maskesi (filtreli)',
            'respirator' => 'Solunum maskesi (filtreli)', 'onluk' => 'Kimyasal koruyucu önlük / tulum', 'tulum' => 'Kimyasal koruyucu önlük / tulum',
            'elbise' => 'Kimyasal koruyucu önlük / tulum', 'ayakkabi' => 'İş ayakkabısı / çizme', 'cizme' => 'İş ayakkabısı / çizme', 'bot' => 'İş ayakkabısı / çizme'];

        foreach ($izinli as $i) {
            if (static::norm($i) === $n) {
                return $i;
            }
        }
        foreach ($esanlam as $kok => $ad) {
            if (str_contains($n, $kok)) {
                return $ad;
            }
        }

        return null;
    }

    public static function norm(?string $s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::ascii(mb_strtolower(strtr((string) $s, ['İ' => 'i', 'I' => 'ı']), 'UTF-8'))));
    }

    /** Kimyasal afişi — A4 dikey, kimyasalın kullanıldığı/depolandığı yere asılır. */
    public static function afisPdf(KimyasalUrun $u): string
    {
        // Tahmini ölçekle basılır; yine de taşarsa (alışılmadık uzun metin) kademeli küçültülüp yeniden basılır.
        $veri = static::gorunumVerisi($u);
        foreach ([1, 0.9, 0.8, 0.72] as $kat) {
            $k = round($veri['k'] * $kat, 2);
            $pdf = Pdf::loadView('pdf.kimyasal.afis', ['k' => $k, 'piktogramMm' => (int) round($veri['piktogramMm'] * min(1, $kat + 0.1))] + $veri)->setPaper('a4')->output();
            if ((new Fpdi)->setSourceFile(StreamReader::createByString($pdf)) === 1) {
                break;
            }
        }

        return $pdf;
    }

    /** MSDS (GBF) özeti — A4 dikey, çalışana tebliğ edilir / SDS klasörünün önüne konur. */
    public static function ozetPdf(KimyasalUrun $u): string
    {
        $veri = static::gorunumVerisi($u);
        foreach ([1, 0.9, 0.8] as $k) {
            $pdf = Pdf::loadView('pdf.kimyasal.msds-ozeti', ['k' => $k] + $veri)->setPaper('a4')->output();
            if ((new Fpdi)->setSourceFile(StreamReader::createByString($pdf)) === 1) {
                break;
            }
        }

        return $pdf;
    }

    /** @return array<string, mixed> */
    public static function gorunumVerisi(KimyasalUrun $u): array
    {
        $u->loadMissing('firma.igu', 'firma.user');
        $ozet = $u->ozet ?? [];
        $ghs = collect($u->ghsKodlari())->mapWithKeys(fn ($k) => [$k => resource_path("images/ghs/{$k}.png")])
            ->filter(fn ($yol) => is_file($yol))->all();
        $ad = mb_strtoupper(strtr($u->urun_adi, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
        $uzunluk = mb_strlen($ad);

        return [
            'u' => $u,
            'ozet' => $ozet + ['tehlikeler' => [], 'onlemler' => [], 'kkd' => [], 'ilk_yardim' => [], 'sinyal_kelime' => ''],
            'firma' => $u->firma,
            'logo' => TalimatUretici::firmaLogosu($u->firma),
            'ghs' => $ghs,
            'ghsAdlari' => config('isg.kimyasal.ghs', []),
            'baslik' => $ad,
            'adPunto' => $adPunto = ($uzunluk <= 16 ? 30 : ($uzunluk <= 26 ? 24 : 19)),
            'k' => static::sikilik($ozet + ['tehlikeler' => [], 'onlemler' => [], 'kkd' => [], 'ilk_yardim' => []], $uzunluk, $adPunto, count($ghs)),
            // Piktogramlar sinyal kelimeyle tek satıra sığar; azsa uzaktan görünsün diye büyür (en çok 38 mm).
            'piktogramMm' => $ghs ? (int) min(38, floor((186 - (filled($ozet['sinyal_kelime'] ?? null) ? 46 : 0)) / count($ghs)) - 4) : 0,
            'ilkYardimBasliklari' => static::ILK_YARDIM,
            'taslak' => $u->ozetOnayBekliyorMu(),
            'kaynak' => $u->sdsVarMi()
                ? 'Kaynak: '.($u->tedarikci ? $u->tedarikci.' ' : '').'MSDS'.($u->sds_tarihi ? ', '.$u->sds_tarihi->format('d.m.Y') : '').'. Ayrıntı için MSDS klasörüne bakın.'
                : 'Ayrıntılı bilgi için ürünün güvenlik bilgi formuna (MSDS) bakın.',
            'dokumanKodu' => 'KMY-'.str_pad((string) $u->id, 4, '0', STR_PAD_LEFT),
            'tarih' => ($u->ozet_onaylandi_at ?? $u->updated_at ?? now())->format('d.m.Y'),
        ];
    }
}
