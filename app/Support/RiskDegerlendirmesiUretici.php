<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskProsedur;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Risk Değerlendirmesi PDF üretimi (dompdf) — sırasıyla: Kapak → seçilen Risk
 * Analizi Prosedürü (RiskProsedur, yönteme göre) → Form (künye + metodoloji
 * 5x5/Fine-Kinney ölçekleri + risk tablosu + ekip listesi + onay). Her sayfada
 * tekrarlanan başlık satırı (tablonun kendi <thead>'ı) + alt bilgi imza şeridi
 * (İşveren/İGU/Hekim/Temsilci/Destek) + sayfa numarası (kapak hariç); kapağın
 * altına page_script ile toplam sayfa sayısı damgalanır (rapor tek render'da
 * üretilir — büyük raporlarda çift render süre sınırını aşıyordu).
 */
class RiskDegerlendirmesiUretici
{
    public static function pdf(RiskDegerlendirmesi $rd): StreamedResponse
    {
        // Büyük raporlar (300+ risk maddesi) dompdf'te hem belleği hem süreyi zorlar:
        //  - 2026-09-04: iki kez render (önizleme + gerçek) 512M memory_limit'i
        //    aşıp "Allowed memory size exhausted" ile indirmeyi düşürüyordu.
        //  - Sonra da tek render ~113 sn sürüp web'in 120 sn max_execution_time
        //    sınırına takılıyordu (352 maddelik gerçek rapor, XAMPP).
        // Çözüm: (a) belleği yükselt, (b) süre sınırını kaldır, (c) raporu TEK
        // KEZ render et — kapaktaki "Toplam Sayfa" bilgisi artık ön render yerine
        // page_script ile damgalanıyor (aşağıya bakınız).
        $mevcutLimit = ini_get('memory_limit');
        if ($mevcutLimit !== '-1' && (int) $mevcutLimit < 1536) {
            ini_set('memory_limit', '1536M');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $rd->loadMissing('maddeler', 'firma.user', 'firma.isyeriHekimi');

        $yontemAnahtari = $rd->yontem === 'fine_kinney' ? 'risk_fine_kinney' : 'risk_matris_5x5';
        $userId = $rd->firma?->user_id;
        $ekip = collect($rd->ekip ?? []);

        $uzman = $rd->firma?->user;
        $hekim = $rd->firma?->isyeriHekimi;
        $temsilci = $ekip->first(fn (array $u) => str_contains(mb_strtolower($u['unvan'] ?? ''), 'temsilci'));
        $destekElemani = $ekip->first(fn (array $u) => str_contains(mb_strtolower($u['unvan'] ?? ''), 'destek'));

        // "4. RİSK DEĞERLENDİRME EKİBİ" tablosu — ekip Repeater'ı hiç
        // doldurulmamış olsa bile 6331 SK m.6'daki 5 sabit rolü daima listeler;
        // ad soyad bilgisi bilinen kaynaktan (firma/uzman/hekim/ekip) gelirse
        // doldurulur, gelmezse satır boş bırakılır (kağıt çıktıda elle yazılabilsin
        // diye "—" DEĞİL boş). Sonda ayrıca tamamen boş bir satır bırakılır —
        // sonradan elle bir isim eklenmesi gerekirse şablonu bozmadan yazılabilsin.
        $ekipGosterim = [
            ['unvan' => 'İşveren', 'ad' => $rd->firma?->isveren_ad ?: $rd->firma?->isveren_vekili ?: ''],
            ['unvan' => 'İş Güvenliği Uzmanı', 'ad' => $uzman?->name ?: ''],
            ['unvan' => 'İşyeri Hekimi', 'ad' => $hekim?->ad_soyad ?: ''],
            ['unvan' => 'Çalışan Temsilcisi', 'ad' => $temsilci['ad'] ?? ''],
            ['unvan' => 'Destek Elemanı', 'ad' => $destekElemani['ad'] ?? ''],
            ['unvan' => '', 'ad' => ''],
        ];

        $veri = [
            'rd' => $rd,
            'firma' => $rd->firma,
            'uzman' => $uzman,
            'hekim' => $hekim,
            'temsilci' => $temsilci,
            'destekElemani' => $destekElemani,
            'ekipGosterim' => $ekipGosterim,
            'metodoloji' => config('isg.'.$yontemAnahtari),
            'prosedur' => $userId ? RiskProsedur::aktifIcin($userId, $rd->yontem) : null,
        ];

        $pdf = Pdf::loadView('pdf.risk-degerlendirmesi', $veri)->setPaper('a4');
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');

        // Sayfa numarası kapakta (1. sayfa) gösterilmez, diğer sayfalarda devam eder.
        // Üstte DEĞİL, alt marjda (tablo bittikten SONRA, imza şeridinden ÖNCE)
        // konumlanır — üstte tabloya çok yakın olduğu için üstüne biniyordu.
        // @page alt marjı 78px, imza şeridi (.sayfa-alt) tam o marjın altına
        // yaslanmış durumda (bottom:-78px) — içerik kutusunun bitişiyle şerit
        // arasında sayfa numarası için boşluk kalıyor.
        $canvas->page_script(function (int $pageNumber, int $pageCount) use ($canvas, $font, $fontMetrics): void {
            if ($pageNumber === 1) {
                // Kapaktaki "Toplam Sayfa" — çift render'dan kaçınmak için
                // (300+ maddede süreyi ikiye katlıyordu) sayfa alt-ortasına,
                // kapağın çift çerçevesi içine damgalanır.
                $metin = "Toplam Sayfa: {$pageCount}";
                $genislik = $fontMetrics->getTextWidth($metin, $font, 10);
                $canvas->text(
                    ($canvas->get_width() - $genislik) / 2,
                    $canvas->get_height() - 92,
                    $metin,
                    $font,
                    10,
                    [0.27, 0.27, 0.27],
                );

                return;
            }

            $x = $canvas->get_width() - 90;
            $y = $canvas->get_height() - 64;
            $canvas->text($x, $y, "Sayfa {$pageNumber} / {$pageCount}", $font, 8, [0.4, 0.4, 0.4]);
        });

        $ad = 'risk-degerlendirmesi-'.Str::slug($rd->firma_unvan ?: 'firma').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $ad);
    }
}
