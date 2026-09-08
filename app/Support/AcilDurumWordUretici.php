<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Acil Durum Eylem Planı — kullanıcının kendi gerçek referans belgesini
 * ("resources/belge/acil-durum-plani-sablonu.docx") BİREBİR ŞABLON olarak
 * kullanan ikinci çıktı türü. `AcilDurumPlaniUretici` (dompdf, kendi
 * tasarımımız) ile karıştırılmamalı — burada amaç şablonun biçimini,
 * metnini, görsellerini olduğu gibi korumak; yalnızca firmaya özel
 * değerler (unvan, adres, SGK no, tarihler, tehlike sınıfı, çalışan
 * sayısı, hazırlayan, seçili acil durum listesi) değiştirilir.
 *
 * Word .docx'te metin genelde tek bir <w:t> içinde durur ama Word bazen
 * aynı değeri birden fazla <w:r> koşusuna böler; bu yüzden basit
 * string_replace yeterli değildir. Paragraf içindeki tüm <w:t> düğümleri
 * birleştirilip aranan değerin konumu bulunur, sonra bu aralık ilgili
 * düğümlere geri dağıtılır (biçimlendirme korunur, yalnızca metin değişir).
 */
class AcilDurumWordUretici
{
    private const SABLON_YOLU = 'belge/acil-durum-plani-sablonu.docx';

    public static function docx(AcilDurumPlani $plan): StreamedResponse
    {
        $plan->loadMissing('firma.user');
        $firma = $plan->firma;
        $uzman = $firma?->user;

        $kaynak = resource_path(self::SABLON_YOLU);
        $gecici = tempnam(sys_get_temp_dir(), 'adep').'.docx';
        copy($kaynak, $gecici);

        $zip = new ZipArchive;
        $zip->open($gecici);
        $xml = $zip->getFromName('word/document.xml');

        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadXML($xml);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Şablondaki (ALTIN YAKUT örneği) orijinal değer => yeni firmanın değeri.
        // Şablonda her biri kendi paragrafında yalnızca bir kez geçer (doğrulandı).
        $eslemeler = [
            'ALTIN YAKUT YAPI ADİ ORTAKLIĞI' => static::turkceBuyuk($firma?->unvan ?: '—'),
            'MEHMET AKİF ERSOY MAHALLESİ 4091 SOK. 1380 ADA 9 PARSEL YALOVA MERKEZ NO:19' => $firma?->adres ?: '—',
            '44100010111205450770133000' => $firma?->sgk_sicil_no ?: '—',
            '26.03.2026' => $plan->rapor_tarihi?->format('d.m.Y') ?: '—',
            '26.03.2028' => $plan->gecerlilik_tarihi?->format('d.m.Y') ?: '—',
            'ÇOK TEHLİKELİ' => static::turkceBuyuk($firma?->tehlikeSinifiEtiketi() ?: ''),
            'MEHMET ÖZDEMİR' => static::turkceBuyuk($uzman?->name ?: '—'),
            'A Sınıfı İş Güvenliği Uzmanı' => $uzman?->unvanEtiketi() ?: '—',
        ];

        foreach ($eslemeler as $eski => $yeni) {
            static::globalDegistir($xpath, $eski, $yeni);
        }

        static::calisanSayisiniDegistir($xpath, (string) ($firma?->calisan_sayisi ?? '—'));
        static::acilDurumListesiniDegistir($xpath, $plan->konuAdlari());

        $zip->addFromString('word/document.xml', $doc->saveXML());
        $zip->close();

        $icerik = file_get_contents($gecici);
        unlink($gecici);

        $ad = 'acil-durum-plani-'.Str::slug($firma?->unvan ?: 'firma').'.docx';

        return response()->streamDownload(fn () => print ($icerik), $ad, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * mb_strtoupper Türkçe'ye özgü nokta kuralını bilmez ("tekstil" → "TEKSTIL"
     * yapar, "TEKSTİL" olması gerekirken). Küçük "i" harfini önce noktalı büyük
     * "İ"ye çevirip sonra büyütüyoruz; noktasız "ı" zaten doğru şekilde "I"ya döner.
     */
    private static function turkceBuyuk(string $metin): string
    {
        return mb_strtoupper(str_replace('i', 'İ', $metin), 'UTF-8');
    }

    /** @return int Kaç paragrafta değiştirildi. */
    private static function globalDegistir(DOMXPath $xpath, string $arama, string $yeni): int
    {
        $sayac = 0;

        foreach ($xpath->query('//w:p') as $p) {
            $birlesik = static::paragrafMetni($xpath, $p);
            $konum = mb_strpos($birlesik, $arama);

            if ($konum === false) {
                continue;
            }

            static::paragrafIcindeDegistir($xpath, $p, $konum, $konum + mb_strlen($arama), $yeni);
            $sayac++;
        }

        return $sayac;
    }

    /**
     * Şablondaki "İŞYERİ İÇİN BELİRLENEN ACİL DURUMLAR" listesi (ACİL DURUM 1..11)
     * planın seçili konularıyla değiştirilir. Şablonun 11 satırlık sabit yapısı
     * korunur: plan 11'den az konu içeriyorsa kalan satırlar "—" ile işaretlenir,
     * 11'den fazlaysa yalnız ilk 11'i bu listede görünür (şablon yapısı bozulmaz).
     *
     * @param  array<int, string>  $yeniKonular
     */
    private static function acilDurumListesiniDegistir(DOMXPath $xpath, array $yeniKonular): void
    {
        foreach ($xpath->query('//w:p') as $p) {
            $birlesik = static::paragrafMetni($xpath, $p);

            if (! preg_match('/^ACİL DURUM (\d+)[:.]\s*(.*)$/u', $birlesik, $m)) {
                continue;
            }

            $n = (int) $m[1];
            $eskiKonu = $m[2];
            $yeniKonu = static::turkceBuyuk($yeniKonular[$n - 1] ?? '—');
            $baslangic = mb_strlen($birlesik) - mb_strlen($eskiKonu);

            static::paragrafIcindeDegistir($xpath, $p, $baslangic, mb_strlen($birlesik), $yeniKonu);
        }
    }

    private static function calisanSayisiniDegistir(DOMXPath $xpath, string $yeni): void
    {
        $etiket = 'ÇALIŞAN SAYISI:';

        foreach ($xpath->query('//w:p') as $p) {
            $birlesik = static::paragrafMetni($xpath, $p);

            if (! str_starts_with($birlesik, $etiket)) {
                continue;
            }

            $baslangic = mb_strlen($etiket);
            while ($baslangic < mb_strlen($birlesik) && mb_substr($birlesik, $baslangic, 1) === ' ') {
                $baslangic++;
            }

            static::paragrafIcindeDegistir($xpath, $p, $baslangic, mb_strlen($birlesik), $yeni);

            return;
        }
    }

    private static function paragrafMetni(DOMXPath $xpath, DOMElement $paragraf): string
    {
        $metin = '';

        foreach ($xpath->query('.//w:t', $paragraf) as $t) {
            $metin .= $t->textContent;
        }

        return $metin;
    }

    /**
     * Paragrafın birleşik metnindeki [$baslangic, $bitis) karakter aralığını
     * $yeni ile değiştirir. Aralık birden fazla <w:t> koşusuna yayılıyorsa: ilk
     * koşu (baş kısmı + yeni metin) ve son koşu (kalan kısım) korunur, aradaki
     * koşular boşaltılır — biçimlendirme (kalın/italik vb.) korunmuş olur.
     */
    private static function paragrafIcindeDegistir(DOMXPath $xpath, DOMElement $paragraf, int $baslangic, int $bitis, string $yeni): void
    {
        $tNodes = iterator_to_array($xpath->query('.//w:t', $paragraf));
        $konum = 0;
        $ilkIndex = null;
        $ilkOfset = null;
        $sonIndex = null;
        $sonOfset = null;

        foreach ($tNodes as $i => $node) {
            $uzunluk = mb_strlen($node->textContent);
            $nodeBaslangic = $konum;
            $nodeBitis = $konum + $uzunluk;

            if ($ilkIndex === null && $baslangic >= $nodeBaslangic && $baslangic < $nodeBitis) {
                $ilkIndex = $i;
                $ilkOfset = $baslangic - $nodeBaslangic;
            }

            if ($bitis > $nodeBaslangic && $bitis <= $nodeBitis) {
                $sonIndex = $i;
                $sonOfset = $bitis - $nodeBaslangic;
                break;
            }

            $konum = $nodeBitis;
        }

        if ($ilkIndex === null || $sonIndex === null) {
            return; // aralık bulunamadıysa dokunma (güvenli tarafta kal)
        }

        if ($ilkIndex === $sonIndex) {
            $metin = $tNodes[$ilkIndex]->textContent;
            $tNodes[$ilkIndex]->textContent = mb_substr($metin, 0, $ilkOfset).$yeni.mb_substr($metin, $sonOfset);

            return;
        }

        $ilkMetin = $tNodes[$ilkIndex]->textContent;
        $tNodes[$ilkIndex]->textContent = mb_substr($ilkMetin, 0, $ilkOfset).$yeni;

        for ($i = $ilkIndex + 1; $i < $sonIndex; $i++) {
            $tNodes[$i]->textContent = '';
        }

        $sonMetin = $tNodes[$sonIndex]->textContent;
        $tNodes[$sonIndex]->textContent = mb_substr($sonMetin, $sonOfset);
    }
}
