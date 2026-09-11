<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use App\Models\Firma;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Acil Durum Eylem Planı — kullanıcının kendi gerçek referans belgesini
 * ("resources/belge/<config'teki dosya>") BİREBİR ŞABLON olarak kullanan
 * ikinci çıktı türü. `AcilDurumPlaniUretici` (dompdf, kendi tasarımımız) ile
 * karıştırılmamalı — burada amaç şablonun biçimini, metnini, görsellerini
 * olduğu gibi korumak; yalnızca firmaya özel değerler (unvan, adres, SGK no,
 * tarihler, tehlike sınıfı, çalışan sayısı, hazırlayan, seçili acil durum
 * listesi) değiştirilir.
 *
 * Birden fazla gerçek şablon desteklenir (`config isg.acil_durum.
 * word_sablonlari`, bkz. `AcilDurumKapakUretici`) — kullanıcı zamanla farklı
 * biçimli örnekler ekleyecek, `$sablonId` ile seçilir.
 */
class AcilDurumWordUretici
{
    public static function docx(AcilDurumPlani $plan, string $sablonId = 'orijinal'): StreamedResponse
    {
        $plan->loadMissing('firma.user');
        $firma = $plan->firma;
        $uzman = $firma?->user;

        $sablon = config('isg.acil_durum.word_sablonlari.'.$sablonId)
            ?? config('isg.acil_durum.word_sablonlari.orijinal');

        $kaynak = resource_path('belge/'.$sablon['dosya']);
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

        // Şablondaki (örnek firma) orijinal değer => yeni firmanın değeri.
        // Şablonda her biri kendi paragrafında yalnızca bir kez geçer (doğrulandı).
        foreach ($sablon['docx'] ?? [] as $anahtar => $eskiDeger) {
            OoxmlMetinYamasi::globalDegistir($xpath, '//w:p', './/w:t', $eskiDeger, static::degerCoz($anahtar, $plan, $firma, $uzman));
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

    /** Semantik eşleme anahtarından (unvan/adres/...) güncel firma değerini üretir. */
    public static function degerCoz(string $anahtar, AcilDurumPlani $plan, ?Firma $firma, ?User $uzman): string
    {
        return match ($anahtar) {
            'unvan' => OoxmlMetinYamasi::turkceBuyuk($firma?->unvan ?: '—'),
            'adres' => $firma?->adres ?: '—',
            'sgk_sicil_no' => $firma?->sgk_sicil_no ?: '—',
            'rapor_tarihi' => $plan->rapor_tarihi?->format('d.m.Y') ?: '—',
            'gecerlilik_tarihi' => $plan->gecerlilik_tarihi?->format('d.m.Y') ?: '—',
            'tehlike_sinifi' => OoxmlMetinYamasi::turkceBuyuk($firma?->tehlikeSinifiEtiketi() ?: ''),
            'uzman_adi' => OoxmlMetinYamasi::turkceBuyuk($uzman?->name ?: '—'),
            'uzman_unvani' => $uzman?->unvanEtiketi() ?: '—',
            'nace_kodu' => $firma?->nace_kodu ?: '—',
            default => '—',
        };
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
            $birlesik = OoxmlMetinYamasi::paragrafMetni($xpath, $p, './/w:t');

            if (! preg_match('/^ACİL DURUM (\d+)[:.]\s*(.*)$/u', $birlesik, $m)) {
                continue;
            }

            $n = (int) $m[1];
            $eskiKonu = $m[2];
            $yeniKonu = OoxmlMetinYamasi::turkceBuyuk($yeniKonular[$n - 1] ?? '—');
            $baslangic = mb_strlen($birlesik) - mb_strlen($eskiKonu);

            OoxmlMetinYamasi::paragrafIcindeDegistir($xpath, $p, './/w:t', $baslangic, mb_strlen($birlesik), $yeniKonu);
        }
    }

    private static function calisanSayisiniDegistir(DOMXPath $xpath, string $yeni): void
    {
        $etiket = 'ÇALIŞAN SAYISI:';

        foreach ($xpath->query('//w:p') as $p) {
            $birlesik = OoxmlMetinYamasi::paragrafMetni($xpath, $p, './/w:t');

            if (! str_starts_with($birlesik, $etiket)) {
                continue;
            }

            $baslangic = mb_strlen($etiket);
            while ($baslangic < mb_strlen($birlesik) && mb_substr($birlesik, $baslangic, 1) === ' ') {
                $baslangic++;
            }

            OoxmlMetinYamasi::paragrafIcindeDegistir($xpath, $p, './/w:t', $baslangic, mb_strlen($birlesik), $yeni);

            return;
        }
    }
}
