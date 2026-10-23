<?php

namespace App\Support;

use App\Models\KkdMatrisi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KKD Seçim Matrisi PDF'i (dompdf, A4 yatay).
 */
class KkdMatrisiUretici
{
    /**
     * Katalogdan ("grup" altındaki "ad" ile eşleşen madde) KKD matrisi satırı
     * kurar — varsayılan KKD gereklilikleriyle önceden doldurulmuş. Katalogdaki
     * eski biçim ("✔ mekanik", "Gerekirse (toz)") durum + nota ayrıştırılır.
     *
     * @return array<string, mixed>
     */
    public static function satirOlustur(string $grup, string $ad): array
    {
        $madde = collect(config('isg.kkd_matris.is_kalemleri.'.$grup, []))->firstWhere('ad', $ad);
        $degerler = $madde['v'] ?? [];

        $satir = ['is_kalemi' => $ad, 'grup' => $grup];
        foreach (array_keys(config('isg.kkd_matris.sutunlar', [])) as $sutun) {
            [$durum, $not] = static::hamDegeriAyristir((string) ($degerler[$sutun] ?? ''));
            $satir[$sutun] = $durum;
            $satir[$sutun.'_not'] = $not;
        }

        return $satir;
    }

    /**
     * Katalogdaki eski serbest metin biçimini (ör. "✔ mekanik", "Gerekirse (toz)")
     * yeni {durum, not} çiftine çevirir.
     *
     * @return array{0: string, 1: ?string}
     */
    protected static function hamDegeriAyristir(string $ham): array
    {
        $ham = trim($ham);
        if ($ham === '') {
            return ['gerekmez', null];
        }

        if (str_starts_with($ham, '✔')) {
            return ['zorunlu', static::notTemizle(Str::after($ham, '✔'))];
        }

        if (str_starts_with($ham, 'Gerekirse')) {
            return ['duruma_gore', static::notTemizle(Str::after($ham, 'Gerekirse'))];
        }

        return ['duruma_gore', static::notTemizle($ham)];
    }

    protected static function notTemizle(string $s): ?string
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }
        if (str_starts_with($s, '(') && str_ends_with($s, ')')) {
            $s = trim(substr($s, 1, -1));
        }

        return $s !== '' ? $s : null;
    }

    public static function pdf(KkdMatrisi $matris): StreamedResponse
    {
        $matris->loadMissing('firma');

        $pdf = Pdf::loadView('pdf.kkd-matris', [
            'matris' => $matris,
            'firma' => $matris->firma,
            'sutunlar' => config('isg.kkd_matris.sutunlar'),
            'durumlar' => config('isg.kkd_matris.durumlar'),
        ])->setPaper('a4', 'landscape');

        $ad = 'kkd-secim-matrisi-'.Str::slug($matris->firma?->unvan ?? 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
