<?php

namespace App\Support;

use App\Models\AtamaYazisi;
use App\Models\Firma;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Görevlendirme (atama) yazısı Word (.docx) üretimi — kullanıcının isgpratik'ten
 * indirdiği GERÇEK referans belgelerini ("resources/belge/atama-yazisi/*.docx")
 * BİREBİR ŞABLON olarak kullanır (AcilDurumWordUretici ile aynı yöntem: paragraf
 * içindeki <w:t> koşuları birleştirilip aranan metin bulunur, yalnız o aralık
 * değiştirilir — genel/yasal metne DOKUNULMAZ, yalnız firma/çalışan'a özel
 * değerler değişir). "Ekip" şablonlarında (Söndürme/Kurtarma/Koruma/İlkyardım/
 * İSG Kurulu) örnek üye satırı gerçek üye sayısı kadar KLONLANIR.
 *
 * "acil_durum_koordinatoru" için isgpratik'te hiçbir referans belge (docx/pdf)
 * bulunamadı — bu rol için Word ÜRETİLMEZ (bkz. sablonVarMi()); PDF çıktısı
 * (dompdf, kendi tasarımımız) her rol için zaten mevcut.
 */
class AtamaYazisiWordUretici
{
    private const SABLONLAR = [
        'calisan_temsilcisi' => 'calisan_temsilcisi.docx',
        'isveren_vekili' => 'isveren_vekili.docx',
        'risk_degerlendirme_ekibi' => 'risk_degerlendirme_ekibi.docx',
        'bilgi_sahibi' => 'bilgi_sahibi.docx',
        'sondurme_ekibi' => 'sondurme_ekibi.docx',
        'kurtarma_ekibi' => 'kurtarma_ekibi.docx',
        'koruma_ekibi' => 'koruma_ekibi.docx',
        'ilkyardim_ekibi' => 'ilkyardim_ekibi.docx',
        'isg_kurulu' => 'isg_kurulu.docx',
    ];

    // Tehlike sınıfına göre destek elemanı (söndürme/kurtarma/koruma/ilkyardım
    // ekibi) asgari görevlendirme oranı — İşyerlerinde Acil Durumlar Hakkında
    // Yönetmelik md.11: her 30/40/50 çalışan için en az 1 kişi.
    private const DESTEK_ESIGI = [
        'az_tehlikeli' => 30,
        'tehlikeli' => 40,
        'cok_tehlikeli' => 50,
    ];

    public static function sablonVarMi(string $rolAnahtari): bool
    {
        return isset(self::SABLONLAR[$rolAnahtari]);
    }

    public static function docx(AtamaYazisi $kayit): ?StreamedResponse
    {
        $kayit->loadMissing('firma');
        $firma = $kayit->firma;

        if (! $firma || ! self::sablonVarMi($kayit->rol_anahtari)) {
            return null;
        }

        $kaynak = resource_path('belge/atama-yazisi/'.self::SABLONLAR[$kayit->rol_anahtari]);
        $gecici = tempnam(sys_get_temp_dir(), 'atm').'.docx';
        copy($kaynak, $gecici);

        $zip = new ZipArchive();
        $zip->open($gecici);
        $xml = $zip->getFromName('word/document.xml');

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadXML($xml);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        static::globalDegistir($xpath, 'NİL UNLU MAMULLER GIDA PASTACILIK SANAYİ VE TİCARET ANONİM ŞİRKETİ', static::turkceBuyuk($firma->unvan ?: '—'));
        static::globalDegistir($xpath, '02.09.2026', $kayit->tarih?->format('d.m.Y') ?: '—');

        $uyeler = $kayit->uyeler ?: [];

        if ($kayit->ekipMi()) {
            static::ekipDoldur($xpath, $doc, $firma, $uyeler);
        } else {
            static::tekliDoldur($xpath, $uyeler[0] ?? []);
        }

        $zip->addFromString('word/document.xml', $doc->saveXML());
        $zip->close();

        $icerik = file_get_contents($gecici);
        unlink($gecici);

        $ad = 'atama-yazisi-'.Str::slug($kayit->rolEtiketi()).'-'.Str::slug($firma->unvan ?: 'firma').'.docx';

        return response()->streamDownload(fn () => print($icerik), $ad, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /** @param array{ad_soyad?: string, tc?: ?string, gorev?: ?string} $uye */
    private static function tekliDoldur(DOMXPath $xpath, array $uye): void
    {
        static::globalDegistir($xpath, 'MEHMET ÖZDEMİR', static::turkceBuyuk($uye['ad_soyad'] ?? '—'));
        static::globalDegistir($xpath, '35479473338', $uye['tc'] ?: '—');
        static::globalDegistir($xpath, 'İŞCİ', static::turkceBuyuk($uye['gorev'] ?: '—'));
    }

    /** @param array<int, array{ad_soyad?: string, tc?: ?string, gorev?: ?string, bas_uye?: bool}> $uyeler */
    private static function ekipDoldur(DOMXPath $xpath, DOMDocument $doc, Firma $firma, array $uyeler): void
    {
        $calisanSayisi = $firma->calisanlar()->count();
        $tehlikeSinifi = $firma->tehlike_sinifi ?? 'az_tehlikeli';
        $esik = self::DESTEK_ESIGI[$tehlikeSinifi] ?? 30;
        $asgari = max(1, (int) ceil($calisanSayisi / $esik));
        $etiket = $firma->tehlikeSinifiEtiketi() ?: '';

        static::globalDegistir($xpath, 'Tehlike Sınıfı: Az Tehlikeli', 'Tehlike Sınıfı: '.$etiket);
        static::globalDegistir($xpath, 'Çalışan Sayısı: 15', 'Çalışan Sayısı: '.$calisanSayisi);
        static::globalDegistir($xpath, 'Asgari Görevlendirme: 1 kişi', 'Asgari Görevlendirme: '.$asgari.' kişi');
        static::globalDegistir($xpath, 'tehlike sınıfı "Az Tehlikeli" olup', 'tehlike sınıfı "'.$etiket.'" olup');
        static::globalDegistir($xpath, 'asgari 1 kişinin görevlendirilmesi', 'asgari '.$asgari.' kişinin görevlendirilmesi');

        static::uyeSatirlariniDoldur($xpath, $doc, $uyeler);
    }

    /**
     * Şablondaki örnek üye satır(lar)ını gerçek üye sayısı kadar klonlar.
     * Tablo, "MEHMET ÖZDEMİR" veya "DFDFDF" (İSG Kurulu örneği) içeren
     * ilk satırın ebeveyni olarak bulunur; başlık satırı (ilk `<w:tr>`)
     * korunur, altındaki örnek satır(lar) silinip yerine gerçek üyeler
     * için klonlanmış satırlar eklenir.
     *
     * @param  array<int, array{ad_soyad?: string, tc?: ?string, gorev?: ?string, bas_uye?: bool}>  $uyeler
     */
    private static function uyeSatirlariniDoldur(DOMXPath $xpath, DOMDocument $doc, array $uyeler): void
    {
        $ornekSatir = null;
        $tablo = null;

        foreach ($xpath->query('//w:tbl') as $tbl) {
            foreach ($xpath->query('.//w:tr', $tbl) as $tr) {
                $metin = static::hucreMetinleriniBirlestir($xpath, $tr);

                if (str_contains($metin, 'MEHMET ÖZDEMİR') || str_contains($metin, 'DFDFDF')) {
                    $ornekSatir = $tr;
                    $tablo = $tbl;
                    break 2;
                }
            }
        }

        if (! $tablo || ! $ornekSatir) {
            return;
        }

        $sablonSatir = $ornekSatir->cloneNode(true);

        // Başlık satırı hariç tüm örnek veri satırlarını sil.
        foreach (iterator_to_array($xpath->query('.//w:tr', $tablo)) as $i => $tr) {
            if ($i > 0) {
                $tablo->removeChild($tr);
            }
        }

        if (! $uyeler) {
            $uyeler = [['ad_soyad' => '—', 'tc' => null, 'gorev' => null, 'bas_uye' => false]];
        }

        foreach ($uyeler as $i => $u) {
            $yeniSatir = $sablonSatir->cloneNode(true);
            $hucreler = iterator_to_array($xpath->query('.//w:tc', $yeniSatir));

            static::hucreyeYaz($xpath, $hucreler[0] ?? null, (string) ($i + 1));
            static::hucreyeYaz($xpath, $hucreler[1] ?? null, static::turkceBuyuk($u['ad_soyad'] ?? '—'));
            static::hucreyeYaz($xpath, $hucreler[2] ?? null, $u['tc'] ?: '—');
            static::hucreyeYaz($xpath, $hucreler[3] ?? null, static::turkceBuyuk($u['gorev'] ?: '—'));
            static::hucreyeYaz($xpath, $hucreler[4] ?? null, ($u['bas_uye'] ?? false) ? 'Ekip Başı' : 'Üye');

            $tablo->appendChild($yeniSatir);
        }
    }

    private static function hucreMetinleriniBirlestir(DOMXPath $xpath, DOMElement $satir): string
    {
        $metin = '';

        foreach ($xpath->query('.//w:t', $satir) as $t) {
            $metin .= $t->textContent;
        }

        return $metin;
    }

    private static function hucreyeYaz(DOMXPath $xpath, ?DOMElement $hucre, string $deger): void
    {
        if (! $hucre) {
            return;
        }

        $tNodes = iterator_to_array($xpath->query('.//w:t', $hucre));

        if (! $tNodes) {
            return;
        }

        $tNodes[0]->textContent = $deger;

        for ($i = 1; $i < count($tNodes); $i++) {
            $tNodes[$i]->textContent = '';
        }
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
