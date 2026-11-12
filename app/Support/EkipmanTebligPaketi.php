<?php

namespace App\Support;

use App\Models\IsEkipmani;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Resmi İSG doküman çıktı ve tebliğ paketi (12.11.2026) — KOBİ'de sahada kâğıt çıktı alıp işverene
 * ıslak imzayla tebliğ için, her ekipmana:
 *   1) Çalışma ve güvenlik talimatı (A4 dikey, makine başına asılır)
 *   2) Aylık operatör günlük kontrol çizelgesi (A4 yatay, 1–31 gün matrisi)
 *   3) İSG ekipman dokümanları ve periyodik kontrol tebliğ tutanağı (A4 dikey)
 * Ortak antet: firma, SGK sicil / adres, ekipman künyesi, doküman kodu, revizyon tarihi, ekipman QR'ı.
 * Paket indirilince ekipman "çıktı alındı — imza bekliyor" olur; imzalı nüsha yüklenince "tebliğ edildi".
 */
class EkipmanTebligPaketi
{
    public const IMZALI_KLASOR = 'ekipman-teblig';

    /** @return array<string, string> dosya adı => PDF içeriği (talimat / form yoksa o belge atlanır) */
    public static function belgeler(IsEkipmani $e, ?Carbon $ay = null): array
    {
        $e->loadMissing('firma.igu', 'firma.user');
        $ay ??= Carbon::today()->startOfMonth();
        $ortak = static::ortak($e);
        $ad = Str::slug($e->ekipman_adi ?: 'ekipman');
        $belgeler = [];

        if ($talimat = EkipmanKatalogu::firmaTalimati($e)) {
            $belgeler["1-talimat-{$ad}.pdf"] = Pdf::loadView('pdf.ekipman.talimat', $ortak + [
                'talimat' => $talimat,
                'basligi' => static::talimatBasligi($e),
                'dokumanKodu' => $talimat->dokuman_no ?: static::kod($e, 'TLM'),
                'revizyonTarihi' => ($talimat->revizyon_tarihi ?? $talimat->yayin_tarihi ?? $talimat->updated_at)->format('d.m.Y'),
            ])->setPaper('a4')->output();
        }

        if ($form = EkipmanKatalogu::kontrolFormu($e->tip)) {
            $belgeler["2-aylik-kontrol-cizelgesi-{$ad}-".$ay->format('Y-m').'.pdf'] = Pdf::loadView('pdf.ekipman.aylik-cizelge', $ortak + [
                'form' => $form,
                'ayAdi' => $ay->translatedFormat('F Y'),
                'gunSayisi' => $ay->daysInMonth,
                'dokumanKodu' => static::kod($e, 'GKC'),
            ])->setPaper('a4', 'landscape')->output();
        }

        $belgeler["3-teblig-tutanagi-{$ad}.pdf"] = Pdf::loadView('pdf.ekipman.teblig-tutanagi', $ortak + [
            'talimat' => $talimat,
            'form' => $form,
            'dokumanKodu' => static::kod($e, 'TBT'),
        ])->setPaper('a4')->output();

        return $belgeler;
    }

    /** Üç belge tek ZIP; ekipman "imza bekliyor" olur (tebliğ edilmişse durumu bozulmaz). */
    public static function indir(IsEkipmani $e, ?Carbon $ay = null): StreamedResponse
    {
        $belgeler = static::belgeler($e, $ay);
        $gecici = tempnam(sys_get_temp_dir(), 'teblig');
        $zip = new ZipArchive;
        $zip->open($gecici, ZipArchive::OVERWRITE);
        foreach ($belgeler as $ad => $icerik) {
            $zip->addFromString($ad, $icerik);
        }
        $zip->close();

        $e->forceFill(['teblig_cikti_at' => now()])->saveQuietly();

        return response()->streamDownload(function () use ($gecici) {
            readfile($gecici);
            @unlink($gecici);
        }, 'teblig-paketi-'.Str::slug($e->ekipman_adi ?: 'ekipman').'.zip', ['Content-Type' => 'application/zip']);
    }

    /** İmzalı nüsha yüklendi: dosya arşivde (local disk), ekipman "tebliğ edildi". Eski nüsha silinir. */
    public static function imzaliKaydet(IsEkipmani $e, string $yol, ?string $tarih = null): void
    {
        $eski = $e->imzali_belge_yolu;
        $e->forceFill([
            'imzali_belge_yolu' => $yol,
            'isverene_teblig_edildi' => true,
            'teblig_tarihi' => $tarih ?: now()->toDateString(),
            'teblig_cikti_at' => $e->teblig_cikti_at ?? now(),
        ])->saveQuietly();

        if ($eski && $eski !== $yol) {
            Storage::disk('local')->delete($eski);
        }
    }

    public static function talimatBasligi(IsEkipmani $e): string
    {
        $ad = mb_strtoupper(strtr($e->ekipman_adi ?: (string) $e->tip, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');

        return str_contains($ad, 'TALİMAT') ? $ad : $ad.' GÜVENLİ ÇALIŞMA TALİMATI';
    }

    private static function kod(IsEkipmani $e, string $tur): string
    {
        return 'EKP-'.str_pad((string) $e->id, 4, '0', STR_PAD_LEFT).'-'.$tur;
    }

    /** @return array<string, mixed> */
    private static function ortak(IsEkipmani $e): array
    {
        return [
            'e' => $e,
            'firma' => $e->firma,
            'logo' => TalimatUretici::firmaLogosu($e->firma),
            'qr' => $e->qrDataUri(),
            'tebligEden' => TalimatUretici::tebligEden($e->firma),
            'revizyonTarihi' => now()->format('d.m.Y'),
        ];
    }
}
