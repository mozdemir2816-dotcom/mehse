<?php

namespace App\Support;

use App\Models\TatbikatTutanagi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory as WordIO;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Acil Durum Tatbikat Tutanağı — PDF (dompdf) ve Word (.docx). İki çıktı aynı bölüm düzenini izler:
 * künye, tatbikat bilgileri, kronometre, senaryo, ekipler, kontrol listesi, gözlem, eksiklik + DÖF,
 * katılımcılar, imza kutuları ve fotoğraflar (İşyerlerinde Acil Durumlar Hakkında Yönetmelik Md.13).
 */
class TatbikatTutanagiUretici
{
    private const KIRMIZI = 'B91C1C';

    private const GRI = 'F3F4F6';

    public static function pdf(TatbikatTutanagi $t): StreamedResponse
    {
        $t->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.tatbikat-tutanagi', [
            'tutanak' => $t,
            'firma' => $t->firma,
        ])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), self::dosyaAdi($t, 'pdf'));
    }

    public static function word(TatbikatTutanagi $t): StreamedResponse
    {
        $gecici = tempnam(sys_get_temp_dir(), 'mehse-tat').'.docx';
        WordIO::createWriter(self::wordBelgesi($t), 'Word2007')->save($gecici);

        return response()->streamDownload(function () use ($gecici) {
            echo file_get_contents($gecici);
            @unlink($gecici);
        }, self::dosyaAdi($t, 'docx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public static function wordBelgesi(TatbikatTutanagi $t): PhpWord
    {
        $t->loadMissing('firma');
        $firma = $t->firma;

        $word = new PhpWord;
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(9.5);
        $b = $word->addSection(['marginTop' => 600, 'marginBottom' => 600, 'marginLeft' => 720, 'marginRight' => 720]);

        $b->addText('ACİL DURUM TATBİKAT TUTANAĞI', ['bold' => true, 'size' => 14, 'color' => '7F1D1D'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $b->addText((string) $firma?->unvan, ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $b->addText(TatbikatTutanagi::buyukHarf($t->senaryoEtiketi()), ['size' => $t->bilesikMi() ? 11 : 10, 'bold' => $t->bilesikMi(), 'color' => $t->bilesikMi() ? '7F1D1D' : null], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $b->addText('İşyerlerinde Acil Durumlar Hakkında Yönetmelik Md. 13 (Tatbikat) uyarınca düzenlenmiştir.', ['italic' => true, 'size' => 8, 'color' => '555555'], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);

        $tarih = fn ($d) => filled($d) ? Carbon::parse($d)->format('d.m.Y') : '—';

        self::baslik($b, '1. İŞYERİ KÜNYESİ');
        self::kunye($b, [
            ['İşyeri Unvanı', $firma?->unvan, null, null],
            ['Adres', $firma?->adres, null, null],
            ['SGK Sicil No', $firma?->sgk_sicil_no, 'Tehlike Sınıfı', $firma?->tehlike_sinifi ? $firma->tehlikeSinifiEtiketi() : null],
            ['Çalışan Sayısı', $firma?->calisan_sayisi, 'Belge Tarihi', $tarih($t->belge_tarihi)],
        ]);

        self::baslik($b, '2. TATBİKAT BİLGİLERİ');
        self::kunye($b, [
            ['Tatbikat Türü', $t->senaryoEtiketi(), 'Tatbikat Tarihi', $tarih($t->tatbikat_tarihi)],
            ['Tatbikat Yeri', $t->tatbikat_yeri, 'Toplanma Alanı', $t->toplanma_alani],
            ['Niteliği', ($t->haberli_tatbikat ? 'Haberli' : 'Habersiz').', '.($t->yillik_plan_dahilinde ? 'yıllık plan dahilinde' : 'plan dışı'),
                'Katılımcı Sayısı', $t->katilimciSayisi() ? $t->katilimciSayisi().' kişi' : null],
            ...($t->bilesikMi() ? [['Senaryo Akışı', $t->senaryoAkisi(), null, null]] : []),
            ['Tatbikat Koordinatörü', $t->tatbikat_koordinatoru, null, null],
        ]);

        self::baslik($b, '3. KRONOMETRE VE SÜRELER');
        $toplam = $t->toplamSureDk();
        $hedef = $t->hedefTahliyeDk();
        $tuttu = $t->hedefTuttuMu();
        $tahliye = $t->gerceklesenTahliyeDk();
        $krono = $b->addTable(['borderSize' => 4, 'borderColor' => '999999', 'cellMargin' => 60]);
        $krono->addRow();
        foreach ([
            ['BAŞLAMA', $t->baslama_saati ?: '—', null],
            ['BİTİŞ', $t->bitis_saati ?: '—', null],
            ['TOPLAM SÜRE', $toplam ? $toplam.' dk' : '—', null],
            ['HEDEF TAHLİYE', $hedef ? $hedef.' dk' : '—', null],
            ['GERÇEKLEŞEN TAHLİYE', $tahliye ? $tahliye.' dk' : '—', null],
            ['SONUÇ', $tuttu === null ? '—' : ($tuttu ? 'Hedef içinde' : ($tahliye - $hedef).' dk aşıldı'), $tuttu === null ? null : ($tuttu ? '15803D' : self::KIRMIZI)],
        ] as [$etiket, $deger, $renk]) {
            $h = $krono->addCell(1740);
            $h->addText($etiket, ['size' => 7, 'color' => '555555'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $h->addText($deger, ['bold' => true, 'size' => 11, 'color' => $renk], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        self::baslik($b, '4. TATBİKAT SENARYOSU');
        self::paragraflar($b, $t->raporSenaryoMetni());

        self::baslik($b, '5. GÖREV ALAN ACİL DURUM EKİPLERİ');
        self::liste($b, [['#', 500], ['Ad Soyad', 6000], ['Ekip', 3900]],
            collect($t->raporEkipleri())->map(fn ($e, $i) => [$i + 1, $e['ad_soyad'] ?? '', $e['ekip'] ?? '—'])->all(),
            'Ekip üyesi eklenmedi.');
        if ($eksikEkip = $t->eksikEkipler()) {
            $b->addText('Uyarı: Bu senaryoda '.implode(', ', $eksikEkip).' görev almalıdır — firmada tanımlı değil (Acil Durum Ekipleri).', ['size' => 8.5, 'color' => self::KIRMIZI]);
        }

        self::baslik($b, '6. DEĞERLENDİRME KONTROL LİSTESİ');
        $sec = config('isg.tatbikat.degerlendirme_secenekleri');
        self::liste($b, [['#', 500], ['Kontrol Sorusu', 8400], ['Sonuç', 1500]],
            collect($t->degerlendirmeler ?? [])->values()->map(fn ($d, $i) => [$i + 1, $d['soru'] ?? '', $sec[$d['cevap'] ?? ''] ?? '—'])->all(),
            'Değerlendirme yapılmadı.');

        self::baslik($b, '7. GÖZLEMLER VE GENEL DEĞERLENDİRME');
        self::paragraflar($b, $t->gozlem);

        self::baslik($b, '8. TESPİT EDİLEN EKSİKLİKLER VE DÜZELTİCİ / ÖNLEYİCİ FAALİYETLER (DÖF)');
        self::liste($b, [['#', 500], ['Tespit Edilen Eksiklik', 9900]],
            collect($t->eksiklikler ?? [])->values()->map(fn ($e, $i) => [$i + 1, $e])->all(),
            'Eksiklik tespit edilmedi.');
        $b->addTextBreak(1, ['size' => 3]);
        self::liste($b, [['#', 500], ['Yapılacak Düzeltici / Önleyici Faaliyet', 5300], ['Sorumlu', 2200], ['Termin', 1300], ['Durum', 1100]],
            collect($t->dof_onerileri ?? [])->values()->map(fn ($d, $i) => [$i + 1, $d['faaliyet'] ?? '', $d['sorumlu'] ?: '—', $tarih($d['tarih'] ?? null), ''])->all(),
            'Düzeltici / önleyici faaliyet önerisi eklenmedi.');

        self::baslik($b, '9. KATILIMCILAR');
        self::liste($b, [['#', 500], ['Ad Soyad', 3800], ['T.C. No', 1800], ['Görevi', 2500], ['İmza', 1800]],
            collect($t->katilimcilar ?? [])->values()->map(fn ($k, $i) => [$i + 1, $k['ad_soyad'] ?? '', $k['tc'] ?? '—', $k['gorev'] ?? '—', ''])->all(),
            'Katılımcı listesi eklenmedi.');

        // İmza kutuları
        $imzalar = array_values(array_filter([
            ['İşveren / İşveren Vekili', $t->isverenAdi(), 'İmza – Kaşe'],
            ['İş Güvenliği Uzmanı', $t->iguAdi(), 'İmza'],
            $t->isyeri_hekimi_imzasi ? ['İşyeri Hekimi', $t->hekimAdi(), 'İmza'] : null,
            ['Tatbikat Koordinatörü', $t->tatbikat_koordinatoru, 'İmza'],
        ]));
        $b->addTextBreak(1);
        $imza = $b->addTable(['borderSize' => 4, 'borderColor' => '999999', 'cellMargin' => 80]);
        $imza->addRow(1100);
        $gen = (int) floor(10400 / count($imzalar));
        foreach ($imzalar as [$rol, $ad, $not]) {
            $h = $imza->addCell($gen, ['valign' => 'bottom']);
            $h->addText($rol, ['bold' => true, 'size' => 8.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $h->addText($ad ?: '.............................', ['size' => 8.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $h->addText('('.$not.')', ['size' => 7.5, 'color' => '666666'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        // Fotoğraflar — yeni sayfada ikişerli
        $fotolar = collect($t->raporFotograflari());
        if ($fotolar->isNotEmpty()) {
            $b->addPageBreak();
            $b->addText('TATBİKAT FOTOĞRAFLARI', ['bold' => true, 'size' => 13, 'color' => '7F1D1D'], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);
            $tablo = $b->addTable(['borderSize' => 4, 'borderColor' => 'DDDDDD', 'cellMargin' => 60]);
            foreach ($fotolar->chunk(2) as $cift) {
                $tablo->addRow();
                foreach ($cift as $i => $yol) {
                    $h = $tablo->addCell(5200);
                    $h->addImage($yol, ['width' => 230, 'alignment' => Jc::CENTER]);
                    $h->addText('Fotoğraf '.($i + 1), ['size' => 7.5, 'color' => '555555'], ['alignment' => Jc::CENTER]);
                }
                if ($cift->count() === 1) {
                    $tablo->addCell(5200);
                }
            }
        }

        return $word;
    }

    /** Çok satırlı metni (senaryo, gözlem) satır satır paragraf olarak yazar. */
    private static function paragraflar(Section $b, ?string $metin): void
    {
        $satirlar = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $metin)), fn ($s) => $s !== ''));

        foreach ($satirlar ?: ['—'] as $satir) {
            if (preg_match('/^(\d+\.\s[^:]{3,90}):\s(.+)$/su', $satir, $asama)) {   // bileşik senaryo aşaması
                $p = $b->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 60, 'indentation' => ['left' => 160]]);
                $p->addText(self::temiz($asama[1]).': ', ['bold' => true, 'color' => '7F1D1D']);
                $p->addText(self::temiz($asama[2]));

                continue;
            }
            $b->addText(self::temiz($satir), [], ['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
        }
    }

    private static function baslik(Section $b, string $metin): void
    {
        $tablo = $b->addTable(['cellMargin' => 50]);
        $tablo->addRow();
        $tablo->addCell(10400, ['bgColor' => self::KIRMIZI])
            ->addText($metin, ['bold' => true, 'size' => 9.5, 'color' => 'FFFFFF'], ['spaceAfter' => 0]);
        $b->addTextBreak(1, ['size' => 2]);
    }

    /** @param array<int, array{0: string, 1: mixed, 2: ?string, 3: mixed}> $satirlar */
    private static function kunye(Section $b, array $satirlar): void
    {
        $tablo = $b->addTable(['borderSize' => 4, 'borderColor' => '999999', 'cellMargin' => 50]);
        foreach ($satirlar as [$e1, $d1, $e2, $d2]) {
            $tablo->addRow();
            $tablo->addCell(2000, ['bgColor' => self::GRI])->addText($e1, ['bold' => true, 'size' => 8.5], ['spaceAfter' => 0]);
            if ($e2 === null) {
                $tablo->addCell(8400, ['gridSpan' => 3])->addText(self::temiz(filled($d1) ? (string) $d1 : '—'), ['size' => 8.5], ['spaceAfter' => 0]);

                continue;
            }
            $tablo->addCell(3200)->addText(self::temiz(filled($d1) ? (string) $d1 : '—'), ['size' => 8.5], ['spaceAfter' => 0]);
            $tablo->addCell(2000, ['bgColor' => self::GRI])->addText($e2, ['bold' => true, 'size' => 8.5], ['spaceAfter' => 0]);
            $tablo->addCell(3200)->addText(self::temiz(filled($d2) ? (string) $d2 : '—'), ['size' => 8.5], ['spaceAfter' => 0]);
        }
    }

    /** @param array<int, array{0: string, 1: int}> $basliklar */
    private static function liste(Section $b, array $basliklar, array $satirlar, string $bos): void
    {
        $tablo = $b->addTable(['borderSize' => 4, 'borderColor' => '999999', 'cellMargin' => 50]);
        $tablo->addRow(null, ['tblHeader' => true]);
        foreach ($basliklar as [$etiket, $gen]) {
            $tablo->addCell($gen, ['bgColor' => self::GRI])->addText($etiket, ['bold' => true, 'size' => 8.5], ['spaceAfter' => 0]);
        }

        if ($satirlar === []) {
            $tablo->addRow();
            $tablo->addCell(array_sum(array_column($basliklar, 1)), ['gridSpan' => count($basliklar)])
                ->addText($bos, ['size' => 8.5, 'color' => '888888'], ['spaceAfter' => 0]);

            return;
        }

        foreach ($satirlar as $satir) {
            $tablo->addRow();
            foreach (array_values($satir) as $i => $deger) {
                $tablo->addCell($basliklar[$i][1])->addText(self::temiz((string) $deger), ['size' => 8.5], ['spaceAfter' => 0]);
            }
        }
    }

    /** Kontrol karakterlerini ayıklar (XML kaçışı AppServiceProvider'da global açık: setOutputEscapingEnabled). */
    private static function temiz(string $metin): string
    {
        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $metin);
    }

    private static function dosyaAdi(TatbikatTutanagi $t, string $uzanti): string
    {
        return 'tatbikat-tutanagi-'.Str::slug($t->senaryoEtiketi()).'-'.Str::slug($t->firma?->unvan ?? 'firma').'-'
            .($t->tatbikat_tarihi?->format('Y-m-d') ?? 'tarihsiz').'.'.$uzanti;
    }
}
