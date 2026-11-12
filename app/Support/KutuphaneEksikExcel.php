<?php

namespace App\Support;

use App\Models\Tehlike;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kütüphanedeki eksik alanları Excel üzerinden elle (Gemini sohbetiyle) doldurma (12.11.2026, kullanıcı fikri —
 * API kotasına takılmadan). İndir: eksik maddeler parça parça (No = tehlikeler.id) + Gemini'ye yapıştırılacak
 * hazır istem. Yükle: No ile eşleşir, YALNIZ boş alanları doldurur, puanları ölçekteki en yakın değere çeker.
 * Mevcut önlem dahil edilmez — firmaya özgü, uzman doldurur (kullanıcı kararı 12.11.2026).
 */
class KutuphaneEksikExcel
{
    public const BASLIKLAR = ['No', 'Klasör', 'Tehlike', 'Risk', 'Öneri', 'Mevzuat', 'Sorumlu',
        'Olasılık', 'Frekans', 'Şiddet', 'Son Olasılık', 'Son Frekans', 'Son Şiddet'];

    private const ALANLAR = [
        'Risk' => 'risk', 'Öneri' => 'oneri', 'Mevzuat' => 'mevzuat', 'Sorumlu' => 'sorumlu',
        'Olasılık' => 'olasilik', 'Frekans' => 'frekans', 'Şiddet' => 'siddet',
        'Son Olasılık' => 'son_olasilik', 'Son Frekans' => 'son_frekans', 'Son Şiddet' => 'son_siddet',
    ];

    private const PUANLAR = ['olasilik' => 'olasilik', 'frekans' => 'frekans', 'siddet' => 'siddet',
        'son_olasilik' => 'olasilik', 'son_frekans' => 'frekans', 'son_siddet' => 'siddet'];

    /** Onarım kapsamı (HAZOP dışı) ve mevcut önlem hariç eksik maddeler — gece onarımıyla aynı ölçüt. */
    public static function eksikSorgu(): Builder
    {
        $q = Tehlike::query()->whereNotNull('tehlike')->where('tehlike', '!=', '')
            ->whereHas('kategori', fn ($k) => $k->where(fn ($w) => $w->whereNull('yontem')->orWhere('yontem', '!=', 'hazop')));

        return RiskAlanTamamlama::eksikKosulu($q, true, mevcutOnlem: false)->orderBy('id');
    }

    public static function parcaSayisi(int $boyut): int
    {
        return max(1, (int) ceil(static::eksikSorgu()->count() / max(1, $boyut)));
    }

    public static function indir(int $parca = 1, int $boyut = 100): StreamedResponse
    {
        $boyut = max(10, min(1000, $boyut));   // 300 / 500: Gemini yanıtı kesebilir, kaydırmaya karşı yüklemede Tehlike kontrolü var
        $maddeler = static::eksikSorgu()->with('kategori')->skip(($parca - 1) * $boyut)->take($boyut)->get();

        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet()->setTitle('Maddeler');
        $sayfa->fromArray(static::BASLIKLAR, null, 'A1');
        $satir = 2;
        foreach ($maddeler as $t) {
            $sayfa->fromArray([
                $t->id, $t->kategori?->ad, $t->tehlike,
                RiskAlanTamamlama::riskEksikMi($t) ? null : $t->risk,
                $t->oneri, $t->mevzuat,
                RiskAlanTamamlama::sorumluEksikMi($t) ? null : $t->sorumlu,
                $t->olasilik, $t->frekans, $t->siddet, $t->son_olasilik, $t->son_frekans, $t->son_siddet,
            ], null, 'A'.$satir++);
        }
        $sayfa->getStyle('A1:M1')->getFont()->setBold(true);
        $sayfa->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBE4EE');
        foreach (['A' => 7, 'B' => 26, 'C' => 46, 'D' => 30, 'E' => 46, 'F' => 32, 'G' => 20] as $s => $g) {
            $sayfa->getColumnDimension($s)->setWidth($g);
        }
        foreach (range('H', 'M') as $s) {
            $sayfa->getColumnDimension($s)->setWidth(11);
        }
        $sayfa->getStyle('A2:G'.max(2, $satir))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sayfa->freezePane('D2');

        $nasil = $kitap->createSheet()->setTitle('Gemini istemi');
        $nasil->getColumnDimension('A')->setWidth(130);
        $satirlar = explode("\n", static::istem($maddeler->count()));
        foreach ($satirlar as $i => $s) {
            $nasil->setCellValue('A'.($i + 1), $s);
        }
        $nasil->getStyle('A1:A'.count($satirlar))->getAlignment()->setWrapText(true);
        $kitap->setActiveSheetIndex(0);

        $toplam = static::parcaSayisi($boyut);

        return response()->streamDownload(function () use ($kitap) {
            (new Xlsx($kitap))->save('php://output');
        }, "kutuphane-eksik-parca-{$parca}-{$toplam}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Gemini sohbetine "Maddeler" sayfasıyla birlikte yapıştırılacak istem. */
    public static function istem(int $adet): string
    {
        $olcek = fn (string $e) => implode(' / ', array_map(fn ($v) => str_replace('.', ',', (string) $v), array_keys(RiskSkorlama::olcek('fine_kinney', $e))));

        return <<<METIN
        NASIL KULLANILIR
        1) "Maddeler" sayfasındaki tabloyu (başlık satırı dahil) seçip kopyalayın.
        2) Gemini'ye önce aşağıdaki metni, ardından tabloyu yapıştırın.
        3) Gemini'nin verdiği tabloyu kopyalayıp bu dosyanın "Maddeler" sayfasına A1 hücresinden yapıştırın, kaydedin.
           Gemini tabloyu bloklar hâlinde verirse ("devam" yazarak) her bloğu bir öncekinin altına yapıştırın; araya gelen başlık satırları sorun değil.
        4) mehse → Risk Kütüphanesi → İçe / Dışa Aktar → "Eksikleri doldurulmuş Excel'i yükle".
        Not: Dolu hücreler değiştirilmez; yalnız boş olanlar kütüphaneye yazılır. Satır silmek / sıra değiştirmek sorun olmaz, No sütunu kalsın.

        ----- GEMINI'YE YAPIŞTIRILACAK METİN -----
        Sen 6331 sayılı İş Sağlığı ve Güvenliği Kanunu ve ilgili yönetmeliklere hâkim, deneyimli bir A sınıfı İş Güvenliği Uzmanısın.
        Aşağıda {$adet} satırlık bir risk kütüphanesi tablosu var. Her satırda YALNIZ BOŞ hücreleri doldur; dolu hücreleri, No ve Tehlike sütunlarını DEĞİŞTİRME, satır ekleme / silme.
        - Risk: tehlikenin doğurabileceği somut sonuç (yaralanma, hastalık, ölüm), kısa; tehlike metninin kopyası olmasın.
        - Öneri: somut, uygulanabilir düzeltici / önleyici faaliyet (1-2 cümle).
        - Mevzuat: ilgili yönetmelik / standart adı; emin olmadığın madde numarasını yazma.
        - Sorumlu: rol (İşveren / İşveren Vekili, Bakım Sorumlusu, Ustabaşı, Operatör, İSG Uzmanı vb.); kişi adı yazma.
        - Puanlar Fine-Kinney ölçeğinde, YALNIZ şu değerlerle: Olasılık: {$olcek('olasilik')} · Frekans: {$olcek('frekans')} · Şiddet: {$olcek('siddet')} (bu ölçekte 100 YOK — en yüksek şiddet 40).
        - Son Olasılık / Son Frekans / Son Şiddet: öneri uygulandıktan sonraki puanlar; ilk değerlerden büyük olamaz (şiddet genelde aynı kalır, olasılık düşer).
        Yanıtı aynı sütun sırasıyla (No, Klasör, Tehlike, Risk, Öneri, Mevzuat, Sorumlu, Olasılık, Frekans, Şiddet, Son Olasılık, Son Frekans, Son Şiddet) başlık satırı dahil TABLO olarak ver; açıklama ekleme.
        Tablo uzunsa 100'er satırlık bloklar hâlinde ver (her blokta başlık satırı olsun); ben "devam" yazınca sonraki bloğu ver. Satır atlama, No ve Tehlike metnini aynen koru.
        METIN;
    }

    /**
     * Doldurulmuş Excel'i okur, boş alanları kütüphaneye yazar.
     *
     * @return array{satir: int, dolan: int, alan: int, bulunamayan: int, uyusmayan: int}
     */
    public static function yukle(string $yol): array
    {
        $tablo = IOFactory::load($yol)->getSheet(0)->toArray(null, true, false, false);
        $baslik = array_map(fn ($h) => trim((string) $h), array_shift($tablo) ?? []);
        $sutun = array_flip($baslik);
        if (! isset($sutun['No'])) {
            throw new \RuntimeException('"No" sütunu bulunamadı — indirilen dosyanın başlık satırını koruyun.');
        }

        $sonuc = ['satir' => 0, 'dolan' => 0, 'alan' => 0, 'bulunamayan' => 0, 'uyusmayan' => 0];
        foreach ($tablo as $r) {
            $id = (int) ($r[$sutun['No']] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $sonuc['satir']++;
            $t = Tehlike::find($id);
            if (! $t) {
                $sonuc['bulunamayan']++;

                continue;
            }
            // Uzun tablolarda Gemini satırları kaydırabilir: No'nun tehlikesi tutmuyorsa satır yazılmaz (12.11.2026).
            if (isset($sutun['Tehlike']) && EkipmanKatalogu::norm((string) ($r[$sutun['Tehlike']] ?? '')) !== EkipmanKatalogu::norm($t->tehlike)) {
                $sonuc['uyusmayan']++;

                continue;
            }

            $p = [];
            foreach (self::ALANLAR as $etiket => $alan) {
                $deger = isset($sutun[$etiket]) ? ($r[$sutun[$etiket]] ?? null) : null;
                if (isset(self::PUANLAR[$alan])) {
                    $p[$alan] = static::olcekDegeri($deger, self::PUANLAR[$alan]);
                } else {
                    $p[$alan] = filled($deger) ? trim((string) $deger) : null;
                }
            }
            // Önlem sonrası puan ilkinden büyük olamaz.
            foreach (['olasilik', 'frekans', 'siddet'] as $e) {
                $ilk = $t->{$e} ?? $p[$e];
                if ($ilk !== null && $p['son_'.$e] !== null) {
                    $p['son_'.$e] = min((float) $ilk, $p['son_'.$e]);
                }
            }

            $degisen = RiskAlanTamamlama::uygula($t, $p, true);
            if (RiskAlanTamamlama::bosMu($t->termin) && ($termin = RiskAlanTamamlama::kutuphaneTermini($t))) {
                $t->termin = $termin;
                $degisen[] = 'termin';
            }
            if ($degisen) {
                $t->save();
                $sonuc['dolan']++;
                $sonuc['alan'] += count($degisen);
            }
        }

        return $sonuc;
    }

    /** "0,5" / 0.5 / "3" → ölçekteki en yakın değer; boş ya da sayı değilse null. */
    private static function olcekDegeri(mixed $deger, string $eksen): ?float
    {
        $sayi = RiskSkorlama::sayi($deger);
        if ($sayi === null) {
            return null;
        }
        $izinli = array_map('floatval', array_keys(RiskSkorlama::olcek('fine_kinney', $eksen)));
        usort($izinli, fn (float $a, float $b) => abs($a - $sayi) <=> abs($b - $sayi));

        return $izinli[0] ?? null;
    }
}
