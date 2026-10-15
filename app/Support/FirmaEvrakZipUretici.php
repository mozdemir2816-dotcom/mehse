<?php

namespace App\Support;

use App\Models\Firma;
use Carbon\CarbonInterface;
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

    /** @return array<int, array{anahtar: string, tip: string, tarih: CarbonInterface}> en yeni üstte (İşyeri paneli listesi) */
    public static function liste(Firma $firma): array
    {
        return static::firmaSatirlari($firma)
            ->sortByDesc(fn (array $s) => $s['tarih'])
            ->map(fn (array $s) => ['anahtar' => static::anahtar($s), 'tip' => $s['tip'], 'tarih' => $s['tarih']])
            ->values()->all();
    }

    /** Tek evrakın PDF'i — anahtar bu firmaya ait değilse null (başka firmanın evrakı indirilemez). */
    public static function pdf(Firma $firma, string $anahtar): ?StreamedResponse
    {
        $s = static::firmaSatirlari($firma)->first(fn (array $s) => static::anahtar($s) === $anahtar);

        if (! $s) {
            return null;
        }

        $s['kayit']->load('firma');

        return $s['kaynak']['uretici']::{$s['kaynak']['pdf_metod']}($s['kayit']);
    }

    public static function zip(Firma $firma, array $secilenAnahtarlar): StreamedResponse
    {
        $zipYolu = tempnam(sys_get_temp_dir(), 'mehse-evrak').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipYolu, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (static::icerikler($firma, $secilenAnahtarlar) as $ad => $icerik) {
            $zip->addFromString($ad, $icerik);
        }

        $zip->close();

        $dosyaAdi = Str::slug($firma->unvan).'-evraklar.zip';

        return response()->streamDownload(function () use ($zipYolu) {
            echo file_get_contents($zipYolu);
            @unlink($zipYolu);
        }, $dosyaAdi);
    }

    /**
     * Seçilen kayıtlı evrakların PDF içerikleri (FirmaEvrakPaketi de kullanır).
     *
     * @return array<string, string> dosya adı => PDF
     */
    public static function icerikler(Firma $firma, array $secilenAnahtarlar): array
    {
        $secilenler = static::firmaSatirlari($firma)
            ->filter(fn (array $s) => in_array(static::anahtar($s), $secilenAnahtarlar, true));

        $cikti = [];
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

            $cikti[$ad] = $icerik;
        }

        return $cikti;
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
