<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eğitim Katılım Formu PDF ve Excel üretimi. isgpratik EĞİTİM ekranları.
 * Klasik düzen: konular → katılımcı imza listesi → eğitmenler formun ALTINDA;
 * 10 kişilik form tek A4 sayfaya sığar (küçük punto, sıkı düzen).
 */
class EgitimKatilimUretici
{
    public static function pdf(EgitimKatilim $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.egitim-katilim', [
            'kayit' => $kayit,
            'firma' => $kayit->firma,
            'icerik' => $kayit->konu_secimleri,
        ])->setPaper('a4');

        $ad = 'egitim-katilim-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->belge_no.'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /**
     * Firma/katılımcı seçmeden, yalnız konu içeriğiyle boş imza formu — eğitime
     * gelenlerin kendi el yazısıyla ad/T.C./imza atması için (en az 10 satır,
     * bkz. pdf.egitim-katilim şablonundaki satır tamamlama mantığı).
     */
    public static function bosFormPdf(string $baslikAnahtari, ?string $sektorAnahtari, string $tehlikeSinifi, string $egitimTuru): StreamedResponse
    {
        $icerik = EgitimIcerikOlusturucu::olustur($baslikAnahtari, $sektorAnahtari, $tehlikeSinifi, $egitimTuru);

        $kayit = new EgitimKatilim([
            'baslik_anahtari' => $baslikAnahtari,
            'egitim_turu' => $egitimTuru,
            'sure_gun' => EgitimIcerikOlusturucu::planlananGun($icerik),
            'isg_uzmani_var' => true,
            'isyeri_hekimi_var' => false,
            'katilimcilar' => [],
        ]);
        $kayit->belge_no = 'BOŞ FORM';

        $pdf = Pdf::loadView('pdf.egitim-katilim', [
            'kayit' => $kayit,
            'firma' => null,
            'icerik' => $icerik,
        ])->setPaper('a4');

        $ad = 'bos-egitim-katilim-'.Str::slug($kayit->basliklarEtiketi()).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }

    /**
     * Kayıtlı bir eğitim katılım formunu Excel (.xlsx) olarak dışa aktarır —
     * künye + konular + katılımcı imza listesi + eğitmenler. Kullanıcı elle
     * düzenleyip/yazdırıp kullanabilir (tek yönlü çıktı).
     */
    public static function excel(EgitimKatilim $kayit): StreamedResponse
    {
        $kayit->loadMissing('firma');
        ExcelBellek::artir();

        $icerik = $kayit->konu_secimleri ?? [];
        $ikiGun = ($kayit->sure_gun ?? 1) >= 2;

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Eğitim Katılım');
        $s->getColumnDimension('A')->setWidth(6);
        $s->getColumnDimension('B')->setWidth(34);
        $s->getColumnDimension('C')->setWidth(16);
        $s->getColumnDimension('D')->setWidth(22);
        $s->getColumnDimension('E')->setWidth(20);
        $s->getColumnDimension('F')->setWidth(20);

        $r = 1;
        $s->setCellValue("A{$r}", 'EĞİTİM KATILIM FORMU');
        $s->mergeCells("A{$r}:F{$r}");
        $s->getStyle("A{$r}")->getFont()->setBold(true)->setSize(14);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $r += 2;

        $kunye = [
            ['Firma', $kayit->firma?->unvan ?? '—'],
            ['Eğitim Konusu', $kayit->basliklarEtiketi()],
            ['Belge No', $kayit->belge_no],
            ['Eğitim Yeri', $kayit->egitim_yeri ?: '—'],
            ['Tarih', $kayit->belge_tarihi?->format('d.m.Y') ?? '—'],
            ['Süre', (($icerik['saat'] ?? null) ? "{$icerik['saat']} Ders Saati · " : '').($kayit->sure_gun ?? 1).' gün'.($ikiGun ? ' (1. ve 2. gün)' : '')],
            ['Eğitim Türü', ($kayit->egitim_turu ?? 'ilk') === 'tekrar' ? 'Tekrar (Yenileme)' : 'İlk Defa'],
            ['Eğitim Şekli', config('isg.sertifika.sekiller.'.($kayit->egitim_sekli ?? 'yuz_yuze'), 'Yüz Yüze')],
            ['Eğitimciler', static::egitmenMetni($kayit)],
        ];

        foreach ($kunye as [$etiket, $deger]) {
            $s->setCellValue("A{$r}", $etiket);
            $s->setCellValue("B{$r}", $deger);
            $s->getStyle("A{$r}")->getFont()->setBold(true);
            $s->mergeCells("B{$r}:F{$r}");
            $r++;
        }
        $r++;

        // --- Eğitim Konuları ---
        $s->setCellValue("A{$r}", 'EĞİTİM KONULARI');
        $s->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;

        foreach (static::konuBloklari($icerik) as $blok) {
            $s->setCellValue("A{$r}", $blok['baslik']);
            $s->getStyle("A{$r}")->getFont()->setBold(true)->setItalic(true);
            $r++;

            foreach ($blok['maddeler'] as $m) {
                $s->setCellValue("B{$r}", $m['madde']);
                $s->setCellValue("C{$r}", $m['dakika'].' dk');
                $r++;
            }
        }
        $r++;

        // --- Katılımcı listesi ---
        $s->setCellValue("A{$r}", 'KATILIMCI LİSTESİ VE İMZALARI');
        $s->getStyle("A{$r}")->getFont()->setBold(true);
        $r++;

        $baslikSatir = $r;
        $sutunlar = $ikiGun
            ? ['#', 'Ad Soyad', 'T.C. No', 'Görevi', 'İmza (1. Gün)', 'İmza (2. Gün)']
            : ['#', 'Ad Soyad', 'T.C. No', 'Görevi', 'İmza'];

        $s->fromArray($sutunlar, null, "A{$r}");
        $s->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $r++;

        $katilimcilar = $kayit->katilimcilar ?? [];
        $satirSayisi = max(count($katilimcilar), 10);

        for ($i = 0; $i < $satirSayisi; $i++) {
            $s->setCellValue("A{$r}", $i + 1);
            $s->setCellValue("B{$r}", $katilimcilar[$i]['ad_soyad'] ?? '');
            $s->setCellValueExplicit("C{$r}", $katilimcilar[$i]['tc'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $s->setCellValue("D{$r}", $katilimcilar[$i]['gorev'] ?? '');
            $r++;
        }

        $sonSatir = $r - 1;
        $s->getStyle("A{$baslikSatir}:".($ikiGun ? 'F' : 'E')."{$sonSatir}")
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $r += 2;

        // --- Eğitimciler ---
        if ($kayit->isg_uzmani_var || $kayit->isyeri_hekimi_var) {
            $s->setCellValue("A{$r}", 'EĞİTİMİ VERENLER');
            $s->getStyle("A{$r}")->getFont()->setBold(true);
            $r++;

            if ($kayit->isg_uzmani_var) {
                $s->setCellValue("A{$r}", 'İş Güvenliği Uzmanı');
                $s->setCellValue("B{$r}", $kayit->isg_uzmani_adi ?: '');
                $s->setCellValue("C{$r}", 'Kaşe / İmza: ______');
                $r++;
            }

            if ($kayit->isyeri_hekimi_var) {
                $s->setCellValue("A{$r}", 'İşyeri Hekimi');
                $s->setCellValue("B{$r}", $kayit->isyeri_hekimi_adi ?: '');
                $s->setCellValue("C{$r}", 'Kaşe / İmza: ______');
                $r++;
            }
            $r++;
        }

        $s->setCellValue("A{$r}", '6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 17 uyarınca düzenlenmiştir.');
        $s->getStyle("A{$r}")->getFont()->setSize(8)->setItalic(true);

        $tmp = tempnam(sys_get_temp_dir(), 'egt').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        $ad = 'egitim-katilim-'.Str::slug($kayit->firma?->unvan ?? 'firma').'-'.$kayit->belge_no.'.xlsx';

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, $ad);
    }

    private static function egitmenMetni(EgitimKatilim $kayit): string
    {
        $parcalar = [];

        if ($kayit->isg_uzmani_var) {
            $parcalar[] = 'İş Güvenliği Uzmanı'.($kayit->isg_uzmani_adi ? " ({$kayit->isg_uzmani_adi})" : '');
        }

        if ($kayit->isyeri_hekimi_var) {
            $parcalar[] = 'İşyeri Hekimi'.($kayit->isyeri_hekimi_adi ? " ({$kayit->isyeri_hekimi_adi})" : '');
        }

        return $parcalar ? implode(' · ', $parcalar) : '—';
    }

    /**
     * konu_secimleri'ni {baslik, maddeler:[{madde,dakika}]} bloklarına düzler —
     * yalnız dahil edilen maddeler. Hem "genel" (4 blok) hem "özel" (tek blok).
     *
     * @return array<int, array{baslik: string, maddeler: array<int, array{madde: string, dakika: int}>}>
     */
    private static function konuBloklari(array $icerik): array
    {
        $dahil = fn (array $maddeler) => collect($maddeler)
            ->where('dahil', true)
            ->map(fn ($m) => ['madde' => $m['madde'], 'dakika' => $m['dakika']])
            ->values()
            ->all();

        if (($icerik['tip'] ?? null) !== 'genel') {
            return [[
                'baslik' => $icerik['ad'] ?? 'Eğitim Konuları',
                'maddeler' => $dahil($icerik['maddeler'] ?? []),
            ]];
        }

        $bloklar = [
            ['baslik' => 'Genel Konular', 'maddeler' => $dahil($icerik['genel_konular'] ?? [])],
            ['baslik' => 'Sağlık Konuları', 'maddeler' => $dahil($icerik['saglik_konulari'] ?? [])],
            ['baslik' => 'Teknik Konular', 'maddeler' => $dahil($icerik['teknik_konular'] ?? [])],
        ];

        if ($icerik['isyerine_ozgu'] ?? null) {
            $bloklar[] = [
                'baslik' => 'İşyerine Özgü Riskler — '.$icerik['isyerine_ozgu']['sektor'],
                'maddeler' => $dahil($icerik['isyerine_ozgu']['maddeler']),
            ];
        }

        return $bloklar;
    }
}
