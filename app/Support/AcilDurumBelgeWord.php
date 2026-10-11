<?php

namespace App\Support;

use App\Models\AcilDurumPlani;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Acil Durum Planı — FOM düzeni Word çıktısı. Kullanıcının FOM MAKİNA ADEP
 * belgesi (resources/belge/acil-durum-plani-fom.docx) şablondur: kapak
 * sayfası, üst bilgi tablosu, alt imza şeridi, sayfa çerçevesi ve stiller
 * olduğu gibi korunur; yalnız firma değerleri yamanır. Gövde, PDF ile aynı
 * Blade görünümünün (pdf.acil-durum-plani) HTML'inden üretilir — iki çıktı
 * tek kaynaktan beslenir, metinler birbirinden sapmaz.
 *
 * HTML → OOXML eşlemesi: h2/h3/h4 → Balk1/2/3, p → normal paragraf,
 * li → ListeParagraf (madde işaretli), table → kenarlıklı tablo,
 * img → satır içi resim, .yeni-sayfa → sayfa sonu.
 */
class AcilDurumBelgeWord
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const SAYFA_GENISLIK = 10466; // twip — A4 eksi FOM kenar boşlukları (720+720)

    /** @var array<string, array{rid: string, ad: string}> */
    private array $resimler = [];

    private int $resimNo = 100;

    private bool $ilkSayfaSonu = true;

    public static function docx(AcilDurumPlani $plan, string $sablonDosya, bool $imzali = true): StreamedResponse
    {
        $icerik = (new static)->uret($plan, $sablonDosya, $imzali);
        $ad = 'acil-durum-plani-'.Str::slug($plan->firma?->unvan ?: 'firma').'.docx';

        return response()->streamDownload(fn () => print ($icerik), $ad, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function uret(AcilDurumPlani $plan, string $sablonDosya, bool $imzali = true): string
    {
        $plan->loadMissing('firma');
        $d = AcilDurumBelgeVerisi::hazirla($plan);

        $html = view('pdf.acil-durum-plani', [
            'plan' => $plan, 'firma' => $plan->firma, 'imzali' => $imzali, 'd' => $d,
        ])->render();

        $gecici = tempnam(sys_get_temp_dir(), 'adep').'.docx';
        copy($sablonDosya, $gecici);
        $zip = new ZipArchive;
        $zip->open($gecici);

        // Gövde: kapak bölümü (ilk Balk1'e kadar) korunur, gerisi HTML'den yazılır.
        $belge = $zip->getFromName('word/document.xml');
        $dom = new DOMDocument;
        $dom->loadXML($belge);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);
        $this->kapagiYama($xp, $d);

        $govde = $xp->query('/w:document/w:body')->item(0);
        $kapak = '';
        foreach ($govde->childNodes as $dugum) {
            if ($dugum instanceof DOMElement && $xp->query('./w:pPr/w:pStyle[@w:val="Balk1"]', $dugum)->length) {
                break;
            }
            $kapak .= $dom->saveXML($dugum);
        }
        $sonBolum = $dom->saveXML($govde->lastChild);

        $yeniGovde = $kapak.$this->htmldenOoxml($html).$sonBolum;
        $xml = preg_replace('#<w:body>.*</w:body>#s', '<w:body>'.str_replace(['\\', '$'], ['\\\\', '\\$'], $yeniGovde).'</w:body>', $dom->saveXML());
        $zip->addFromString('word/document.xml', $xml);

        // Üst bilgi: firma adı + doküman no / yayın / revizyon değerleri.
        $ust = $zip->getFromName('word/header1.xml');
        if ($ust !== false) {
            $zip->addFromString('word/header1.xml', $this->ustBilgiYama($ust, $d));
        }

        $this->resimleriEkle($zip);
        $zip->close();

        $icerik = file_get_contents($gecici);
        @unlink($gecici);

        return $icerik;
    }

    // ── Kapak / üst bilgi yamaları ───────────────────────────────────────

    private function kapagiYama(DOMXPath $xp, array $d): void
    {
        $hz = $d['hazirlayan'];

        // Kapaktaki ilk iki metin: firma unvanı ve SGK sicil no.
        $kapakMetinleri = [];
        foreach ($xp->query('/w:document/w:body/*') as $dugum) {
            if ($xp->query('./w:pPr/w:pStyle[@w:val="Balk1"]', $dugum)->length) {
                break;
            }
            foreach ($xp->query('.//w:t', $dugum) as $t) {
                $kapakMetinleri[] = $t;
            }
        }
        if (isset($kapakMetinleri[0])) {
            $kapakMetinleri[0]->textContent = (string) $d['unvan'];
        }
        if (isset($kapakMetinleri[1])) {
            $kapakMetinleri[1]->textContent = (string) ($d['sgk'] ?? '');
        }

        // Hazırlayan / onaylayan / tarihler — metne göre bul (sıra bağımsız).
        $degistir = function (string $eski, string $yeni) use ($xp): void {
            foreach ($xp->query('//w:t') as $t) {
                if (trim($t->textContent) === $eski) {
                    $t->textContent = $yeni;

                    return;
                }
            }
        };
        $degistir('Mehmet Özdemir', (string) $hz['ad']);
        $degistir('A Sınıfı İş Güvenliği Uzmanı', (string) ($hz['unvan'] ?? ''));
        $degistir('Otomotiv Mühendisi', '');
        $degistir('Adı Soyadı: ................', 'Adı Soyadı: '.($d['isveren'] ?? $d['isveren_vekili'] ?? '................'));
        $degistir('11.10.2026', (string) $d['yayin']);
        $degistir('11.10.2030', (string) $d['gecerlilik']);
    }

    private function ustBilgiYama(string $xml, array $d): string
    {
        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);

        $degerler = [
            'Doküman No' => $d['dokuman_no'], 'Yayın Tarihi' => $d['yayin'],
            'Revizyon Tarihi' => $d['revizyon_tarihi'], 'Revizyon No' => $d['revizyon_no'],
        ];

        foreach ($xp->query('//w:t') as $t) {
            if (str_contains($t->textContent, 'MAKİNA') || str_contains($t->textContent, 'ŞİRKETİ')) {
                $t->textContent = (string) $d['unvan'];
            }
        }

        foreach ($xp->query('//w:tc') as $tc) {
            $etiket = trim($tc->textContent);
            if (! array_key_exists($etiket, $degerler)) {
                continue;
            }
            $hedef = $tc->nextSibling;
            while ($hedef && ! ($hedef instanceof DOMElement && $hedef->localName === 'tc')) {
                $hedef = $hedef->nextSibling;
            }
            if (! $hedef) {
                continue;
            }
            $p = $xp->query('.//w:p', $hedef)->item(0);
            foreach (iterator_to_array($xp->query('./w:r', $p)) as $r) {
                $p->removeChild($r);
            }
            $kaynakRpr = $xp->query('.//w:r/w:rPr', $tc)->item(0);
            $r = $dom->createElementNS(self::W, 'w:r');
            if ($kaynakRpr) {
                $r->appendChild($kaynakRpr->cloneNode(true));
            }
            $tEl = $dom->createElementNS(self::W, 'w:t', htmlspecialchars((string) ($degerler[$etiket] ?? ''), ENT_XML1));
            $tEl->setAttribute('xml:space', 'preserve');
            $r->appendChild($tEl);
            $p->appendChild($r);
        }

        return $dom->saveXML();
    }

    // ── HTML → OOXML ─────────────────────────────────────────────────────

    private function htmldenOoxml(string $html): string
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $body = $dom->getElementsByTagName('body')->item(0);

        return $this->bloklar($body);
    }

    private function bloklar(DOMNode $ust): string
    {
        $cikti = '';
        foreach ($ust->childNodes as $n) {
            if (! $n instanceof DOMElement) {
                if (trim($n->textContent) !== '' && $n->nodeType === XML_TEXT_NODE) {
                    $cikti .= $this->paragraf($this->satirIci($n), []);
                }

                continue;
            }
            $sinif = ' '.$n->getAttribute('class').' ';
            if (preg_match('/ (cerceve|ust|alt|kapak) /', $sinif)) {
                continue;
            }
            $cikti .= match ($n->tagName) {
                'h2' => $this->baslik($n, 'Balk1', 24, 240, 160),
                'h3' => $this->baslik($n, 'Balk2', 22, 200, 120),
                'h4' => $this->baslik($n, 'Balk3', 20, 120, 80),
                'p' => $this->paragraf($this->satirIci($n), $this->hizala($n)),
                'ul', 'ol' => $this->liste($n),
                'table' => $this->tablo($n),
                'img' => $this->resimParagraf($n),
                'div' => str_contains($sinif, ' yeni-sayfa ') ? $this->sayfaSonu() : $this->bloklar($n),
                default => $this->bloklar($n),
            };
        }

        return $cikti;
    }

    private function baslik(DOMElement $n, string $stil, int $boyut, int $once, int $sonra): string
    {
        $metin = $this->kacir(trim(preg_replace('/\s+/u', ' ', $n->textContent)));

        return '<w:p><w:pPr><w:pStyle w:val="'.$stil.'"/><w:keepNext/><w:spacing w:before="'.$once.'" w:after="'.$sonra.'"/></w:pPr>'
            .'<w:r><w:rPr><w:b/><w:bCs/><w:sz w:val="'.$boyut.'"/><w:szCs w:val="'.$boyut.'"/></w:rPr><w:t xml:space="preserve">'.$metin.'</w:t></w:r></w:p>';
    }

    /** @param  array<int, string>  $hiza  ['center'] ise ortalı, değilse iki yana yaslı */
    private function paragraf(string $kosular, array $hiza): string
    {
        if ($kosular === '') {
            return '';
        }
        $jc = in_array('center', $hiza, true) ? 'center' : 'both';

        return '<w:p><w:pPr><w:spacing w:after="120"/><w:jc w:val="'.$jc.'"/></w:pPr>'.$kosular.'</w:p>';
    }

    private function hizala(DOMElement $n): array
    {
        return str_contains($n->getAttribute('style'), 'text-align:center') || str_contains(' '.$n->getAttribute('class').' ', ' orta ') ? ['center'] : [];
    }

    private function liste(DOMElement $ul): string
    {
        $cikti = '';
        foreach ($ul->childNodes as $li) {
            if ($li instanceof DOMElement && $li->tagName === 'li') {
                $cikti .= '<w:p><w:pPr><w:pStyle w:val="ListeParagraf"/><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr><w:spacing w:after="80"/><w:jc w:val="both"/></w:pPr>'
                    .$this->satirIci($li).'</w:p>';
            }
        }

        return $cikti;
    }

    private function sayfaSonu(): string
    {
        // Kapak kendi bölüm sonuyla bittiği için gövdedeki ilk sayfa sonu atlanır.
        if ($this->ilkSayfaSonu) {
            $this->ilkSayfaSonu = false;

            return '';
        }

        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    /** Satır içi içerik → w:r dizisi (b = kalın, br = satır sonu, .metin = satır sonlarını koru). */
    private function satirIci(DOMNode $n, bool $kalin = false, bool $koru = false, ?int $boyut = null): string
    {
        if ($n->nodeType === XML_TEXT_NODE) {
            $metin = $n->textContent;
            if ($koru) {
                $satirlar = preg_split('/\r?\n/u', trim($metin));

                return implode('<w:r><w:br/></w:r>', array_map(fn ($s) => $this->kosu($s, $kalin, $boyut), $satirlar));
            }
            $metin = preg_replace('/\s+/u', ' ', $metin);
            // Blok başı / sonundaki girinti boşlukları Word'de görünür boşluk olur.
            if (! $n->previousSibling && in_array($n->parentNode?->nodeName, ['td', 'th', 'li', 'p'], true)) {
                $metin = ltrim($metin);
            }
            if (! $n->nextSibling && in_array($n->parentNode?->nodeName, ['td', 'th', 'li', 'p'], true)) {
                $metin = rtrim($metin);
            }

            return $metin === '' ? '' : $this->kosu($metin, $kalin, $boyut);
        }
        if (! $n instanceof DOMElement) {
            return '';
        }
        if ($n->tagName === 'br') {
            return '<w:r><w:br/></w:r>';
        }
        if ($n->tagName === 'img') {
            return $this->resimKosusu($n);
        }
        $kalin = $kalin || in_array($n->tagName, ['b', 'strong', 'th'], true);
        $koru = $koru || str_contains(' '.$n->getAttribute('class').' ', ' metin ');
        $cikti = '';
        foreach ($n->childNodes as $c) {
            $cikti .= $this->satirIci($c, $kalin, $koru, $boyut);
        }

        return $cikti;
    }

    private function kosu(string $metin, bool $kalin, ?int $boyut): string
    {
        if ($metin === '') {
            return '';
        }
        $rpr = ($kalin ? '<w:b/><w:bCs/>' : '').($boyut ? '<w:sz w:val="'.$boyut.'"/><w:szCs w:val="'.$boyut.'"/>' : '');

        return '<w:r>'.($rpr ? '<w:rPr>'.$rpr.'</w:rPr>' : '').'<w:t xml:space="preserve">'.$this->kacir($metin).'</w:t></w:r>';
    }

    private function tablo(DOMElement $tablo): string
    {
        $satirlar = [];
        foreach ($tablo->getElementsByTagName('tr') as $tr) {
            $hucreler = [];
            foreach ($tr->childNodes as $h) {
                if ($h instanceof DOMElement && in_array($h->tagName, ['td', 'th'], true)) {
                    $hucreler[] = $h;
                }
            }
            $satirlar[] = $hucreler;
        }
        $kolon = max(array_map(fn ($s) => array_sum(array_map(fn ($h) => max(1, (int) $h->getAttribute('colspan')), $s)), $satirlar ?: [[]]) ?: [1]);

        // Kolon genişlikleri: ilk tam satırdaki "width:N%" değerleri, kalan eşit pay.
        $oranlar = array_fill(0, $kolon, null);
        foreach ($satirlar as $s) {
            if (count($s) === $kolon) {
                foreach ($s as $i => $h) {
                    if (preg_match('/width:\s*([\d.]+)%/', $h->getAttribute('style'), $m)) {
                        $oranlar[$i] = (float) $m[1];
                    }
                }
                break;
            }
        }
        $tablo_oran = preg_match('/width:\s*([\d.]+)%/', $tablo->getAttribute('style'), $m) ? (float) $m[1] / 100 : 1;
        $kalan = max(0, 100 - array_sum(array_filter($oranlar)));
        $bos = count(array_filter($oranlar, fn ($o) => $o === null)) ?: 1;
        $genislik = array_map(fn ($o) => (int) round(self::SAYFA_GENISLIK * $tablo_oran * ($o ?? $kalan / $bos) / 100), $oranlar);

        $kenar = '';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $k) {
            $kenar .= '<w:'.$k.' w:val="single" w:sz="4" w:space="0" w:color="555555"/>';
        }
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="'.array_sum($genislik).'" w:type="dxa"/>'
            .($tablo_oran < 1 ? '<w:jc w:val="center"/>' : '')
            .'<w:tblBorders>'.$kenar.'</w:tblBorders><w:tblCellMar><w:left w:w="70" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
        foreach ($genislik as $g) {
            $xml .= '<w:gridCol w:w="'.$g.'"/>';
        }
        $xml .= '</w:tblGrid>';

        foreach ($satirlar as $s) {
            $xml .= '<w:tr>';
            $i = 0;
            foreach ($s as $h) {
                $yayil = max(1, (int) $h->getAttribute('colspan'));
                $w = array_sum(array_slice($genislik, $i, $yayil));
                $i += $yayil;
                $baslik = $h->tagName === 'th';
                $jc = str_contains(' '.$h->getAttribute('class').' ', ' orta ') ? 'center' : 'left';
                $boyut = preg_match('/font-size:\s*([\d.]+)pt/', $h->getAttribute('style'), $m) ? (int) round((float) $m[1] * 2) : 17;
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.$w.'" w:type="dxa"/>'.($yayil > 1 ? '<w:gridSpan w:val="'.$yayil.'"/>' : '')
                    .($baslik ? '<w:shd w:val="clear" w:color="auto" w:fill="DBE5F1"/>' : '').'</w:tcPr>'
                    .'<w:p><w:pPr><w:spacing w:before="20" w:after="20"/><w:jc w:val="'.$jc.'"/></w:pPr>'.$this->satirIci($h, $baslik, false, $boyut).'</w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl><w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
    }

    // ── Resimler ─────────────────────────────────────────────────────────

    private function resimParagraf(DOMElement $img): string
    {
        $kosu = $this->resimKosusu($img);

        return $kosu === '' ? '' : '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'.$kosu.'</w:p>';
    }

    private function resimKosusu(DOMElement $img): string
    {
        $yol = $img->getAttribute('src');
        if (! is_file($yol) || ! ($boyut = @getimagesize($yol))) {
            return '';
        }
        [$gPx, $yPx] = $boyut;
        $stil = $img->getAttribute('style');
        $emu = 12700; // 1 pt

        if (str_contains($yol, 'acil-durum-akis')) {
            // FOM'daki akış şeması boyutu (14,55 × 18,52 cm).
            $cx = 5238750;
            $cy = (int) round($cx * $yPx / $gPx);
        } else {
            $maxG = preg_match('/max-width:\s*([\d.]+)pt/', $stil, $m) ? (float) $m[1] : 510;
            $maxY = preg_match('/(?:max-)?height:\s*([\d.]+)pt/', $stil, $m) ? (float) $m[1] : 460;
            $olcek = min($maxG / $gPx, $maxY / $yPx);
            $cx = (int) round($gPx * $olcek * $emu);
            $cy = (int) round($yPx * $olcek * $emu);
        }

        if (! isset($this->resimler[$yol])) {
            $this->resimNo++;
            $uzanti = strtolower(pathinfo($yol, PATHINFO_EXTENSION)) ?: 'png';
            $this->resimler[$yol] = ['rid' => 'rIdAdep'.$this->resimNo, 'ad' => 'adep'.$this->resimNo.'.'.$uzanti];
        }
        $rid = $this->resimler[$yol]['rid'];
        $no = ++$this->resimNo;

        return '<w:r><w:rPr><w:noProof/></w:rPr><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:docPr id="'.$no.'" name="Resim '.$no.'"/>'
            .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="'.$no.'" name="Resim '.$no.'"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }

    private function resimleriEkle(ZipArchive $zip): void
    {
        if ($this->resimler === []) {
            return;
        }
        $iliskiler = $zip->getFromName('word/_rels/document.xml.rels');
        $turler = $zip->getFromName('[Content_Types].xml');
        $ek = '';
        foreach ($this->resimler as $yol => $r) {
            $zip->addFile($yol, 'word/media/'.$r['ad']);
            $ek .= '<Relationship Id="'.$r['rid'].'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$r['ad'].'"/>';
            $uzanti = pathinfo($r['ad'], PATHINFO_EXTENSION);
            if (! str_contains($turler, 'Extension="'.$uzanti.'"')) {
                $tur = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'][$uzanti] ?? 'image/png';
                $turler = str_replace('<Default Extension="xml"', '<Default Extension="'.$uzanti.'" ContentType="'.$tur.'"/><Default Extension="xml"', $turler);
            }
        }
        $zip->addFromString('word/_rels/document.xml.rels', str_replace('</Relationships>', $ek.'</Relationships>', $iliskiler));
        $zip->addFromString('[Content_Types].xml', $turler);
    }

    private function kacir(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
