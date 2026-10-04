<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\Talimat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "YYYY YILI İSG DEĞERLENDİRME RAPORU" — İSG Plan ve Değerlendirme Formu
 * (kullanıcının verdiği Excel: Downloads/Yeni klasör/2026-yillik-degerlendirme-
 * raporu.xlsx; deneme verileri temizlenmiş kopyası resources/belge/
 * yillik-degerlendirme-form.xlsx). Birebir doldurulur:
 * - Künye: unvan, SGK sicil, işkolu (NACE), tehlike sınıfı, telefon / e-posta,
 *   adres, çalışan sayıları (erkek / kadın / genç / çocuk / toplam).
 * - 19 sabit çalışma satırı: sistemde o yıl kaydı olanların TARİH sütunu ve
 *   SONUÇ / YORUM metni kayıtlardan (YillikDegerlendirmeVerisi) yazılır;
 *   kaydı olmayan satır şablon metniyle kalır.
 * - Görevli (İGU / hekim / işveren) adı yazılmaz; imzalıda kaşeleri basılır.
 */
final class YillikDegerlendirmeFormUretici
{
    private const SABLON = 'belge/yillik-degerlendirme-form.xlsx';

    /**
     * Şablon satırı → YillikDegerlendirmeVerisi::hesapla anahtarı (null: özel hesap
     * ya da sistemde karşılığı yok).
     */
    private const SATIRLAR = [
        16 => 'katip', 17 => 'kurul', 18 => 'gorevlendirme', 19 => 'temsilci', 20 => 'calisma_plani',
        21 => 'defter', 22 => 'ise_giris', 23 => 'periyodik', 24 => 'talimat', 25 => 'acil_plan',
        26 => 'tatbikat', 27 => 'temel_egitim', 28 => 'risk', 29 => 'ortam', 30 => 'periyodik_kontrol',
        31 => 'mesleki_belge', 32 => 'kkd', 33 => null, 34 => 'saha',
    ];

    public static function excel(Firma $firma, int $yil, ?Carbon $raporTarihi = null, bool $imzali = true): StreamedResponse
    {
        $kitap = self::doldur($firma, $yil, $raporTarihi, $imzali);
        $gecici = tempnam(sys_get_temp_dir(), 'ydf').'.xlsx';
        IOFactory::createWriter($kitap, 'Xlsx')->save($gecici);

        return response()->streamDownload(function () use ($gecici) {
            echo file_get_contents($gecici);
            @unlink($gecici);
        }, self::dosyaAdi($firma, $yil));
    }

    public static function dosyaAdi(Firma $firma, int $yil): string
    {
        return $yil.'-yillik-degerlendirme-raporu-'.Str::slug($firma->kisa_ad ?: $firma->unvan).'.xlsx';
    }

    public static function doldur(Firma $firma, int $yil, ?Carbon $raporTarihi = null, bool $imzali = true): Spreadsheet
    {
        ExcelBellek::artir();
        $firma->loadMissing('igu', 'isyeriHekimi', 'user');

        $kitap = IOFactory::load(resource_path(self::SABLON));
        $s = $kitap->getActiveSheet();
        $kunye = YillikDegerlendirmeVerisi::kunye($firma, $yil);
        $buyuk = fn (?string $v) => filled($v) ? TurkceMetin::buyuk($v) : null;

        $s->setCellValue('C1', $yil.' YILI İSG DEĞERLENDİRME RAPORU');
        $s->setCellValue('N1', ($raporTarihi ?? Carbon::today())->format('d.m.Y'));

        $s->setCellValue('C6', $buyuk($firma->unvan));
        $s->setCellValueExplicit('I7', (string) $firma->sgk_sicil_no, DataType::TYPE_STRING);
        // "41.20" sayıya dönüşüp "41,2" görünmesin diye metin olarak; açıklama yoksa firmadaki NACE açıklaması.
        $isKolu = str_contains(trim((string) $kunye['nace']), ' ')
            ? $kunye['nace']
            : trim(implode(' — ', array_filter([$firma->nace_kodu, $firma->nace_aciklama])));
        $s->setCellValueExplicit('C8', (string) $buyuk($isKolu), DataType::TYPE_STRING);
        $s->setCellValue('I8', $buyuk($firma->tehlikeSinifiEtiketi()));
        $s->setCellValue('E9', $firma->telefon);
        $s->setCellValue('L9', $firma->eposta);
        $s->setCellValue('C10', $buyuk(trim(implode(' ', array_filter([$firma->adres, $firma->ilce, $firma->il ?: $firma->sehir])))));
        $s->setCellValue('E11', $kunye['erkek']);
        $s->setCellValue('G11', $kunye['kadin']);
        $s->setCellValue('J11', $kunye['genc']);
        $s->setCellValue('L11', $kunye['cocuk']);
        $s->setCellValue('O11', $kunye['toplam']);

        $hesap = YillikDegerlendirmeVerisi::hesapla($firma, $yil) + self::ozelSatirlar($firma, $yil);

        foreach (self::SATIRLAR as $satir => $anahtar) {
            $veri = $anahtar ? ($hesap[$anahtar] ?? null) : null;

            if (! $veri) {
                continue;
            }

            if (filled($veri['tarih'] ?? null)) {
                $s->setCellValue('D'.$satir, $veri['tarih']);
            }

            if (filled($veri['sonuc'] ?? null)) {
                $s->setCellValue('K'.$satir, $buyuk($veri['sonuc']));
            }
        }

        if ($imzali) {
            self::kase($s, 'B36', $firma->igu?->kase_gorseli ?: $firma->user?->kase_gorseli);
            self::kase($s, 'D36', $firma->isveren_kase_gorseli ?: $firma->isveren_imza_gorseli);
            self::kase($s, 'J36', $firma->isyeriHekimi?->kase_gorseli);
        }

        return $kitap;
    }

    /**
     * Ortak hesapta olmayan satırlar: İSG-KATİP sözleşmesi (atama tarihi),
     * çalışan temsilcisi seçimi, yıllık çalışma planı, talimatlar.
     *
     * @return array<string, array{tarih?: ?string, sonuc?: ?string}>
     */
    private static function ozelSatirlar(Firma $firma, int $yil): array
    {
        [$bas, $son] = YillikDegerlendirmeVerisi::donem($firma, $yil);
        $t = fn ($d) => $d ? Carbon::parse($d)->format('d.m.Y') : null;
        $sonuc = [];

        if ($firma->sozlesme_baslangic) {
            $sonuc['katip'] = ['tarih' => $t($firma->sozlesme_baslangic)];
        }

        $temsilci = $firma->calisanTemsilcisiSecimi;
        $temsilciTarih = $temsilci?->secim_tarihi ?? $temsilci?->gorevlendirme_tarihi;
        if ($temsilciTarih && Carbon::parse($temsilciTarih)->lte($son)) {
            $sonuc['temsilci'] = ['tarih' => $t($temsilciTarih)];
        }

        if ($firma->yillikPlanlar()->where('yil', $yil)->exists()) {
            $sonuc['calisma_plani'] = ['tarih' => $t($bas)];
        }

        $talimatlar = Talimat::query()->where('firma_id', $firma->id)->whereBetween('created_at', [$bas, $son])->get(['created_at']);
        if ($talimatlar->isNotEmpty()) {
            $sonuc['talimat'] = [
                'tarih' => $t($talimatlar->min('created_at')).($talimatlar->count() > 1 ? '–'.$t($talimatlar->max('created_at')) : ''),
                'sonuc' => $talimatlar->count().' talimat oluşturularak personele eğitim olarak verildi.',
            ];
        }

        return $sonuc;
    }

    private static function kase(Worksheet $s, string $hucre, ?string $yol): void
    {
        $tam = $yol ? ExcelGorsel::yol(storage_path('app/public/'.$yol)) : null;

        if (! $tam) {
            return;
        }

        $cizim = new Drawing;
        $cizim->setName('Kaşe');
        $cizim->setPath($tam);
        $cizim->setHeight(70);
        $cizim->setCoordinates($hucre);
        $cizim->setOffsetY(4);
        $cizim->setWorksheet($s);
    }
}
