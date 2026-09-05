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
 * (İşveren/İGU/Hekim/Temsilci/Destek) + sayfa numarası (kapak hariç); kapakta
 * "Hazırlayan"ın altında toplam sayfa sayısı.
 */
class RiskDegerlendirmesiUretici
{
    public static function pdf(RiskDegerlendirmesi $rd): StreamedResponse
    {
        // Sayfa sayısını öğrenmek için rapor İKİ KEZ render ediliyor (önizleme + gerçek).
        // Büyük raporlarda (200+ risk maddesi) dompdf bu iki render'la varsayılan
        // 512M memory_limit'i aşıp "Allowed memory size exhausted" hatasıyla
        // indirmeyi sessizce (500) düşürüyordu — bkz. laravel.log 2026-09-04.
        $mevcutLimit = ini_get('memory_limit');
        if ($mevcutLimit !== '-1' && (int) $mevcutLimit < 1536) {
            ini_set('memory_limit', '1536M');
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

        // Kapakta "Toplam Sayfa" normal HTML akışında (Hazırlayan'ın altında)
        // gösterilebilsin diye önce sayfa sayısını öğrenmek için bir kez render
        // edilir; toplam sayfa sayısı ancak render'dan SONRA bilinebilir.
        $onIzleme = Pdf::loadView('pdf.risk-degerlendirmesi', $veri + ['toplamSayfa' => null])->setPaper('a4');
        $onIzleme->render();
        $toplamSayfa = $onIzleme->getDomPDF()->getCanvas()->get_page_count();

        $pdf = Pdf::loadView('pdf.risk-degerlendirmesi', $veri + ['toplamSayfa' => $toplamSayfa])->setPaper('a4');
        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');

        // Sayfa numarası kapakta (1. sayfa) gösterilmez, diğer sayfalarda devam eder.
        // Üstte DEĞİL, alt marjda (tablo bittikten SONRA, imza şeridinden ÖNCE)
        // konumlanır — üstte tabloya çok yakın olduğu için üstüne biniyordu.
        // @page alt marjı 78px, imza şeridi (.sayfa-alt) tam o marjın altına
        // yaslanmış durumda (bottom:-78px) — içerik kutusunun bitişiyle şerit
        // arasında sayfa numarası için boşluk kalıyor.
        $canvas->page_script(function (int $pageNumber, int $pageCount) use ($canvas, $font): void {
            if ($pageNumber === 1) {
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
