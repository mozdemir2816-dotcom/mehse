<?php

namespace App\Support;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskProsedur;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
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
    /**
     * Bu sayıdan çok maddeli raporlarda dosya adı sabit tutulur ve PDF diskten
     * (önbellekten) sunulur — bkz. RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI.
     */
    /**
     * PDF'te "Hazırlayan" olarak gösterilecek kişi. Bu değerlendirmeye özel
     * seçilmiş bir İGU varsa (kaşe/imza dahil) o kullanılır; yoksa geriye
     * dönük uyumluluk için hesap sahibinin kendi adı/unvanı/kaşesi/imzası
     * gösterilir (RiskDegerlendirmesi::booted() zaten firmanın atanmış
     * İGU'sunu otomatik dolduruyor — bu yalnız hiç İGU kaydı olmayan
     * eski/manuel senaryolar için devrededir).
     */
    public static function hazirlayan(RiskDegerlendirmesi $rd): object
    {
        $igu = $rd->igu;
        $hesapSahibi = $rd->firma?->user;

        return (object) [
            'name' => $igu?->ad_soyad ?: $hesapSahibi?->name,
            'unvan' => $igu ? $igu->unvan : ($hesapSahibi?->unvan ? $hesapSahibi->unvanEtiketi() : null),
            'kase_gorseli' => $igu?->kase_gorseli ?: $hesapSahibi?->kase_gorseli,
            'imza_gorseli' => $igu?->imza_gorseli ?: $hesapSahibi?->imza_gorseli,
        ];
    }

    public static function onbellekYolu(RiskDegerlendirmesi $rd): string
    {
        return 'risk-pdf/'.$rd->id.'.pdf';
    }

    /**
     * Arka planda (cron/RiskPdfUret) çağrılır: PDF'i render edip diske yazar,
     * göreli storage yolunu döndürür. Kullanıcıya hiçbir HTTP yanıtı üretmez.
     */
    public static function uretVeKaydet(RiskDegerlendirmesi $rd): string
    {
        $pdf = self::olustur($rd);
        $yol = self::onbellekYolu($rd);

        Storage::disk('local')->put($yol, $pdf);

        return $yol;
    }

    public static function pdf(RiskDegerlendirmesi $rd, bool $imzali = true): StreamedResponse
    {
        $pdf = self::olustur($rd, $imzali);
        $ad = 'risk-degerlendirmesi-'.Str::slug($rd->firma_unvan ?: 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf), $ad);
    }

    /**
     * Rapor verisi (PDF görünümü + Excel / Word çıktısı ortak): firma, hazırlayan,
     * hekim, imza sahipleri (ekip + Atama Yazıları), ekip tablosu, metodoloji, prosedür.
     *
     * @return array<string, mixed>
     */
    public static function veri(RiskDegerlendirmesi $rd, bool $imzali = true): array
    {
        $rd->loadMissing('maddeler', 'firma.user', 'firma.isyeriHekimi', 'igu');
        // Çıktıda maddeler puana göre yüksekten düşüğe (eşitte kayıt sırası) — kullanıcı 14.10.2026.
        $rd->setRelation('maddeler', $rd->maddeler->sort(fn ($a, $b) => ((float) $b->puan <=> (float) $a->puan) ?: ($a->sira <=> $b->sira) ?: ($a->id <=> $b->id))->values());

        $yontemAnahtari = 'risk_'.$rd->yontem;
        $userId = $rd->firma?->user_id;
        $ekip = collect($rd->ekip ?? []);

        // Ekip listesinde elle girilmeyen roller (temsilci / destek / bilgi sahibi)
        // firmanın Atama Yazıları'ndan otomatik gelir.
        if ($rd->firma) {
            $rolu = fn (array $u) => match (true) {
                str_contains(mb_strtolower($u['unvan'] ?? ''), 'temsilci') => 'temsilci',
                str_contains(mb_strtolower($u['unvan'] ?? ''), 'destek') => 'destek',
                str_contains(mb_strtolower($u['unvan'] ?? ''), 'bilgi sahibi') => 'bilgi',
                default => null,
            };
            $girilen = $ekip->map($rolu)->filter()->unique()->all();
            $ekip = $ekip->merge(collect(RiskEkibiOtomatik::imzaSahipleri($rd->firma))
                ->reject(fn (array $u) => in_array($rolu($u), $girilen, true)))->values();
        }

        $uzman = self::hazirlayan($rd);
        $hekim = $rd->firma?->isyeriHekimi;
        $temsilci = $ekip->first(fn (array $u) => str_contains(mb_strtolower($u['unvan'] ?? ''), 'temsilci'));
        $destekElemani = $ekip->first(fn (array $u) => str_contains(mb_strtolower($u['unvan'] ?? ''), 'destek'));
        $bilgiSahibiCalisanlar = $ekip->filter(fn (array $u) => str_contains(mb_strtolower($u['unvan'] ?? ''), 'bilgi sahibi'))->values();
        $bilgiSahibiCalisan = $bilgiSahibiCalisanlar->first();

        // "4. RİSK DEĞERLENDİRME EKİBİ" tablosu — ekip Repeater'ı hiç
        // doldurulmamış olsa bile 6331 SK m.6'daki 6 sabit rolü daima listeler
        // (son rol "bilgi sahibi çalışan" birden fazla kişi olabileceğinden
        // ekip'te kaç tane varsa o kadar satır açılır, hiç yoksa tek boş satır);
        // ad soyad bilgisi bilinen kaynaktan (firma/uzman/hekim/ekip) gelirse
        // doldurulur, gelmezse satır boş bırakılır (kağıt çıktıda elle yazılabilsin
        // diye "—" DEĞİL boş). Sonda ayrıca tamamen boş bir satır bırakılır —
        // sonradan elle bir isim eklenmesi gerekirse şablonu bozmadan yazılabilsin.
        $ekipGosterim = [
            ['unvan' => 'İşveren', 'ad' => ''], // kaşeli görevlilerin adı basılmaz
            ['unvan' => 'İş Güvenliği Uzmanı', 'ad' => ''],
            ['unvan' => 'İşyeri Hekimi', 'ad' => ''],
            ['unvan' => 'Çalışan Temsilcisi', 'ad' => $temsilci['ad'] ?? ''],
            ['unvan' => 'Destek Elemanı', 'ad' => $destekElemani['ad'] ?? ''],
            ...($bilgiSahibiCalisanlar->isNotEmpty()
                ? $bilgiSahibiCalisanlar->map(fn (array $u) => ['unvan' => 'Bilgi Sahibi Çalışan', 'ad' => $u['ad'] ?? ''])->all()
                : [['unvan' => 'Bilgi Sahibi Çalışan', 'ad' => '']]),
            ['unvan' => '', 'ad' => ''],
        ];

        return [
            'rd' => $rd,
            'firma' => $rd->firma,
            'uzman' => $uzman,
            'hekim' => $hekim,
            'temsilci' => $temsilci,
            'destekElemani' => $destekElemani,
            'bilgiSahibiCalisan' => $bilgiSahibiCalisan,
            'ekipGosterim' => $ekipGosterim,
            'metodoloji' => config('isg.'.$yontemAnahtari),
            'eksenEtiketleri' => \App\Support\RiskSkorlama::eksenEtiketleri($rd->yontem),
            'prosedur' => $userId ? RiskProsedur::aktifIcin($userId, $rd->yontem) : null,
            'imzali' => $imzali,
        ];
    }

    private static function olustur(RiskDegerlendirmesi $rd, bool $imzali = true): string
    {
        // Büyük raporlar (300+ risk maddesi) dompdf'te hem belleği hem süreyi zorlar:
        //  - 2026-09-04: iki kez render (önizleme + gerçek) 512M memory_limit'i
        //    aşıp "Allowed memory size exhausted" ile indirmeyi düşürüyordu.
        //  - Sonra da tek render ~113 sn sürüp web'in 120 sn max_execution_time
        //    sınırına takılıyordu (352 maddelik gerçek rapor, XAMPP) — paylaşımlı
        //    hosting'te bu süre sınırı PHP'nin kendi ayarından bağımsız, web
        //    sunucusu (LiteSpeed) seviyesinde daha da sıkı olabiliyor; bu yüzden
        //    RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI üstü raporlar artık HTTP
        //    isteği içinde değil, cron ile arka planda (RiskPdfUret) üretiliyor.
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

        $veri = self::veri($rd, $imzali);

        // İki parça (kullanıcı 14.10.2026): kapak + prosedür + künye/yöntem DİKEY A4,
        // risk tablosu (yeni sayfadan) + ekip/onay YATAY A4. dompdf tek belgede
        // sayfa yönünü değiştiremediği için ayrı render edilip FPDI ile birleştirilir.
        $dikey = Pdf::loadView('pdf.risk-degerlendirmesi', $veri + ['bolum' => 'dikey'])->setPaper('a4');
        $yatay = Pdf::loadView('pdf.risk-degerlendirmesi', $veri + ['bolum' => 'yatay'])->setPaper('a4', 'landscape');

        // Kapaktaki "Toplam sayfa sayısı" hücresinin konumu çizim sırasında yakalanır
        // (dompdf render bitince kutu ağacını boşaltır); sayı aşağıda page_script ile
        // o hücreye yazılır — çift render yok (300+ maddede süreyi ikiye katlıyordu).
        $toplamHucre = null;
        self::konumYakala($dikey->getDomPDF(), 'kapak-toplam-sayfa', $toplamHucre);
        $dikey->render();
        $yatay->render();

        $dikeySayfa = $dikey->getDomPDF()->getCanvas()->get_page_count();
        $toplam = $dikeySayfa + $yatay->getDomPDF()->getCanvas()->get_page_count();

        self::sayfaNumaralari($dikey->getDomPDF(), 0, $toplam, $toplamHucre);
        self::sayfaNumaralari($yatay->getDomPDF(), $dikeySayfa, $toplam, null);

        return self::birlestir([$dikey->output(), $yatay->output()]);
    }

    /**
     * Sayfa numarası kapakta (1. sayfa) gösterilmez, diğer sayfalarda devam eder.
     * Üstte DEĞİL, alt marjda (tablo bittikten SONRA, imza şeridinden ÖNCE)
     * konumlanır — üstte tabloya çok yakın olduğu için üstüne biniyordu.
     * @page alt marjı 78px, imza şeridi (.sayfa-alt) tam o marjın altına
     * yaslanmış durumda (bottom:-78px). $oncekiSayfa: birleştirilen belgede bu
     * parçadan önceki sayfa sayısı; $toplam: tüm belgenin sayfa sayısı.
     *
     * @param  array{0: float, 1: float, 2: float, 3: float}|null  $toplamHucre
     */
    private static function sayfaNumaralari(\Dompdf\Dompdf $dompdf, int $oncekiSayfa, int $toplam, ?array $toplamHucre): void
    {
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');

        $canvas->page_script(function (int $pageNumber) use ($canvas, $font, $fontMetrics, $toplamHucre, $oncekiSayfa, $toplam): void {
            $no = $oncekiSayfa + $pageNumber;

            if ($no === 1) {
                if ($toplamHucre) {
                    [$x, $y, , $h] = $toplamHucre;
                    $canvas->text($x + 6, $y + ($h - 7.5) / 2 - 1.5, "{$toplam} sayfa", $font, 7.5, [0, 0, 0]);
                }

                // Şablondaki kapak alt bilgisi (sağ alt).
                $metin = 'Risk Değerlendirmesi  |  Kapak';
                $genislik = $fontMetrics->getTextWidth($metin, $font, 7);
                $canvas->text($canvas->get_width() - 45 - $genislik, $canvas->get_height() - 40, $metin, $font, 7, [0, 0, 0]);

                return;
            }

            $canvas->text($canvas->get_width() - 90, $canvas->get_height() - 64, "Sayfa {$no} / {$toplam}", $font, 8, [0.4, 0.4, 0.4]);
        });
    }

    /**
     * PDF parçalarını sırayla tek belgede birleştirir; her sayfa kendi boyut/yönüyle.
     *
     * @param  array<int, string>  $parcalar  PDF içerikleri
     */
    private static function birlestir(array $parcalar): string
    {
        $fpdi = new \setasign\Fpdi\Fpdi;
        $fpdi->SetAutoPageBreak(false);

        foreach ($parcalar as $icerik) {
            $sayfaSayisi = $fpdi->setSourceFile(\setasign\Fpdi\PdfParser\StreamReader::createByString($icerik));
            for ($i = 1; $i <= $sayfaSayisi; $i++) {
                $sablon = $fpdi->importPage($i);
                $boyut = $fpdi->getTemplateSize($sablon);
                $fpdi->AddPage($boyut['orientation'], [$boyut['width'], $boyut['height']]);
                $fpdi->useTemplate($sablon);
            }
        }

        return $fpdi->Output('S');
    }

    /**
     * Render sırasında id'si verilen öğenin kutusunu [x, y, genişlik, yükseklik] (pt, sayfa
     * koordinatı) $konum'a yazar. Render'dan ÖNCE çağrılmalı: dompdf iş bitince kutu ağacını
     * boşalttığı için konum sonradan okunamaz.
     *
     * @param  array{0: float, 1: float, 2: float, 3: float}|null  $konum
     */
    public static function konumYakala(\Dompdf\Dompdf $dompdf, string $id, ?array &$konum): void
    {
        $dompdf->setCallbacks([[
            'event' => 'end_frame',
            'f' => function ($kutu) use ($id, &$konum): void {
                if ($konum !== null) {
                    return;
                }

                $dugum = $kutu->get_node();

                if ($dugum instanceof \DOMElement && $dugum->getAttribute('id') === $id) {
                    $b = $kutu->get_padding_box();
                    $konum = [(float) $b['x'], (float) $b['y'], (float) $b['w'], (float) $b['h']];
                }
            },
        ]]);
    }
}
