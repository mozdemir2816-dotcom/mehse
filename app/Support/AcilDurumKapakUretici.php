<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Acil Durum Eylem Planı — kapak sayfası. Kullanıcının gerçek referans
 * PowerPoint slaytını ("ADEP KAPAK") BİREBİR ŞABLON olarak kullanır (OSGB
 * logosu + illüstrasyon sabit kalır); yalnızca firmaya özel metinler
 * (unvan, adres, SGK sicil no, NACE kodu, tarihler) değişir. `.docx` gövde
 * planından AYRI indirilir — fiziksel dosyada kapak + plan ayrı ayrı
 * yazdırılıp klasöre birlikte konur (afiş çıktılarıyla aynı yaklaşım).
 *
 * Aynı OOXML metin-yama mantığı `AcilDurumWordUretici` ile paylaşılır
 * (`OoxmlMetinYamasi`) — yalnızca isim uzayı/etiket `a:p`/`a:t`'tir (Word'ün
 * `w:p`/`w:t`'i yerine).
 */
class AcilDurumKapakUretici
{
    public static function pptx(AcilDurumPlani $plan, string $sablonId = 'orijinal'): StreamedResponse
    {
        $plan->loadMissing('firma');
        $firma = $plan->firma;

        $sablon = config('isg.acil_durum.word_sablonlari.'.$sablonId)
            ?? config('isg.acil_durum.word_sablonlari.orijinal');

        abort_if(empty($sablon['kapak_dosya']), 404, 'Bu şablonda kapak sayfası yok');

        $kaynak = resource_path('belge/'.$sablon['kapak_dosya']);
        $gecici = tempnam(sys_get_temp_dir(), 'adep-kapak').'.pptx';
        copy($kaynak, $gecici);

        $zip = new ZipArchive;
        $zip->open($gecici);
        $xml = $zip->getFromName('ppt/slides/slide1.xml');

        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadXML($xml);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

        foreach ($sablon['kapak'] ?? [] as $anahtar => $eskiDeger) {
            OoxmlMetinYamasi::globalDegistir($xpath, '//a:p', './/a:t', $eskiDeger, AcilDurumWordUretici::degerCoz($anahtar, $plan, $firma, $firma?->user));
        }

        $zip->addFromString('ppt/slides/slide1.xml', $doc->saveXML());
        $zip->close();

        $icerik = file_get_contents($gecici);
        unlink($gecici);

        $ad = 'acil-durum-plani-kapak-'.Str::slug($firma?->unvan ?: 'firma').'.pptx';

        return response()->streamDownload(fn () => print ($icerik), $ad, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }
}
