<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Firmalar listesindeki "Evrakları İndir" aksiyonu — bir firma için üretilmiş
 * TÜM belgeleri (RaporKayitlari ile aynı kaynak: config isg.raporlar.kaynaklar)
 * kullanıcı seçtiği alt kümeyle tek bir ZIP'te toplar. Tek tek indirmek
 * yerine — "toplu indirmiş olayım, tek tek uğraşmayayım" isteğiyle eklendi.
 */
class FirmaEvrakZipUretici
{
    /** @return array<string, string> "ModelSınıfı:id" => "Tip — dd.mm.Y HH:mm" (checkbox seçenekleri) */
    public static function secenekler(Firma $firma): array
    {
        return static::firmaSatirlari($firma)
            ->mapWithKeys(fn (array $s) => [static::anahtar($s) => $s['tip'].' — '.$s['tarih']->format('d.m.Y H:i')])
            ->all();
    }

    public static function zip(Firma $firma, array $secilenAnahtarlar): StreamedResponse
    {
        $secilenler = static::firmaSatirlari($firma)
            ->filter(fn (array $s) => in_array(static::anahtar($s), $secilenAnahtarlar, true));

        $zipYolu = tempnam(sys_get_temp_dir(), 'mehse-evrak').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipYolu, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $kullanilanAdlar = [];

        foreach ($secilenler as $s) {
            $kaynak = $s['kaynak'];
            $uretici = $kaynak['uretici'];
            $metod = $kaynak['pdf_metod'];

            // RaporKayitlari::hepsi() firma'yı yalnız id/unvan ile (liste görünümü
            // için) eager-load eder — PDF üreticiler firmanın TÜM alanlarına
            // (tehlike_sinifi vb.) ihtiyaç duyar, bu yüzden burada tazelenir.
            $s['kayit']->load('firma');

            /** @var StreamedResponse $yanit */
            $yanit = $uretici::{$metod}($s['kayit']);

            ob_start();
            $yanit->sendContent();
            $icerik = ob_get_clean();

            $temelAd = Str::slug($s['tip'].'-'.$s['tarih']->format('Y-m-d-His'));
            $ad = $temelAd.'.pdf';
            $sira = 1;
            while (in_array($ad, $kullanilanAdlar, true)) {
                $ad = $temelAd.'-'.(++$sira).'.pdf';
            }
            $kullanilanAdlar[] = $ad;

            $zip->addFromString($ad, $icerik);
        }

        $zip->close();

        $dosyaAdi = Str::slug($firma->unvan).'-evraklar.zip';

        return response()->streamDownload(function () use ($zipYolu) {
            echo file_get_contents($zipYolu);
            @unlink($zipYolu);
        }, $dosyaAdi);
    }

    /** @return Collection<int, array> */
    private static function firmaSatirlari(Firma $firma): Collection
    {
        return collect(RaporKayitlari::hepsi($firma->user_id))
            ->filter(fn (array $s) => $s['kayit']->firma_id === $firma->id);
    }

    private static function anahtar(array $satir): string
    {
        return get_class($satir['kayit']).':'.$satir['kayit']->id;
    }
}
