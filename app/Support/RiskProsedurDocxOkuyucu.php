<?php

namespace App\Support;

use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

/**
 * Kullanıcının kendi .docx prosedür belgesini (Risk Analizi Prosedürü Yükle)
 * RiskProsedur.icerik şekline ([{tip:'baslik'|'paragraf', metin}]) çevirir.
 * Başlık sezgisi: kısa (≤70 karakter) ve büyük harf oranı yüksek satırlar
 * başlık sayılır (AMAÇ, KAPSAM, 5.1 RİSK DEĞERLENDİRMESİ gibi) — gerçek
 * belgenin biçimini (kalın/başlık stili yerine) sağlam biçimde yakalar.
 */
class RiskProsedurDocxOkuyucu
{
    /** @return array<int, array{tip: string, metin: string}> */
    public static function oku(string $dosyaYolu): array
    {
        $phpWord = IOFactory::load($dosyaYolu);
        $bloklar = [];

        foreach ($phpWord->getSections() as $section) {
            static::elemanlariGez($section, $bloklar);
        }

        return $bloklar;
    }

    /** @param  array<int, array{tip: string, metin: string}>  $bloklar */
    private static function elemanlariGez(AbstractContainer $container, array &$bloklar): void
    {
        foreach ($container->getElements() as $eleman) {
            if ($eleman instanceof Text) {
                static::satirEkle($eleman->getText(), $bloklar);
            } elseif ($eleman instanceof TextRun) {
                $metin = '';

                foreach ($eleman->getElements() as $parca) {
                    if ($parca instanceof Text) {
                        $metin .= $parca->getText();
                    }
                }

                static::satirEkle($metin, $bloklar);
            } elseif ($eleman instanceof AbstractContainer) {
                static::elemanlariGez($eleman, $bloklar);
            }
        }
    }

    /** @param  array<int, array{tip: string, metin: string}>  $bloklar */
    private static function satirEkle(string $metin, array &$bloklar): void
    {
        $metin = trim($metin);

        if ($metin === '') {
            return;
        }

        $bloklar[] = [
            'tip' => static::baslikMi($metin) ? 'baslik' : 'paragraf',
            'metin' => $metin,
        ];
    }

    private static function baslikMi(string $metin): bool
    {
        if (mb_strlen($metin) > 70) {
            return false;
        }

        $harfler = preg_replace('/[^\p{L}]/u', '', $metin);

        if ($harfler === '' || mb_strlen($harfler) < 2) {
            return false;
        }

        return mb_strtoupper($harfler, 'UTF-8') === $harfler;
    }
}
