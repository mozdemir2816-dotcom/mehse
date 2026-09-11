<?php

namespace App\Support;

use DOMElement;
use DOMXPath;

/**
 * Word (.docx, `w:p`/`w:t`) ve PowerPoint (.pptx, `a:p`/`a:t`) belgelerinde
 * gerçek referans şablonun BİÇİMİNİ bozmadan metin yamalamak için ortak
 * yardımcı. `AcilDurumWordUretici` ve `AcilDurumKapakUretici` tarafından
 * kullanılır — ikisi de aynı OOXML mantığına sahiptir (paragraf → koşu
 * ağacı), yalnızca eleman isim uzayı (namespace) ve etiket adı farklıdır.
 *
 * OOXML'de bir metin genelde tek bir `<w:t>`/`<a:t>` koşusunda durur ama
 * Word/PowerPoint bazen aynı değeri (özellikle otomatik düzeltme sonrası)
 * birden fazla koşuya böler — SGK sicil no gibi uzun rakam dizileri bunun
 * tipik örneğidir. Bu yüzden basit string_replace yeterli değildir:
 * paragraf içindeki tüm metin düğümleri birleştirilip aranan değerin
 * konumu bulunur, sonra bu aralık ilgili düğümlere geri dağıtılır
 * (biçimlendirme korunur, yalnızca metin değişir).
 */
class OoxmlMetinYamasi
{
    /**
     * @param  string  $paragrafSorgu  örn. '//w:p' veya '//a:p'
     * @param  string  $metinSorgu  paragraf içi göreli sorgu, örn. './/w:t' veya './/a:t'
     * @return int Kaç paragrafta değiştirildi.
     */
    public static function globalDegistir(DOMXPath $xpath, string $paragrafSorgu, string $metinSorgu, string $arama, string $yeni): int
    {
        $sayac = 0;

        foreach ($xpath->query($paragrafSorgu) as $p) {
            $birlesik = static::paragrafMetni($xpath, $p, $metinSorgu);
            $konum = mb_strpos($birlesik, $arama);

            if ($konum === false) {
                continue;
            }

            static::paragrafIcindeDegistir($xpath, $p, $metinSorgu, $konum, $konum + mb_strlen($arama), $yeni);
            $sayac++;
        }

        return $sayac;
    }

    public static function paragrafMetni(DOMXPath $xpath, DOMElement $paragraf, string $metinSorgu): string
    {
        $metin = '';

        foreach ($xpath->query($metinSorgu, $paragraf) as $t) {
            $metin .= $t->textContent;
        }

        return $metin;
    }

    /**
     * Paragrafın birleşik metnindeki [$baslangic, $bitis) karakter aralığını
     * $yeni ile değiştirir. Aralık birden fazla metin koşusuna yayılıyorsa: ilk
     * koşu (baş kısmı + yeni metin) ve son koşu (kalan kısım) korunur, aradaki
     * koşular boşaltılır — biçimlendirme (kalın/italik vb.) korunmuş olur.
     */
    public static function paragrafIcindeDegistir(DOMXPath $xpath, DOMElement $paragraf, string $metinSorgu, int $baslangic, int $bitis, string $yeni): void
    {
        $tNodes = iterator_to_array($xpath->query($metinSorgu, $paragraf));
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

    /**
     * mb_strtoupper Türkçe'ye özgü nokta kuralını bilmez ("tekstil" → "TEKSTIL"
     * yapar, "TEKSTİL" olması gerekirken). Küçük "i" harfini önce noktalı büyük
     * "İ"ye çevirip sonra büyütüyoruz; noktasız "ı" zaten doğru şekilde "I"ya döner.
     */
    public static function turkceBuyuk(string $metin): string
    {
        return mb_strtoupper(str_replace('i', 'İ', $metin), 'UTF-8');
    }
}
