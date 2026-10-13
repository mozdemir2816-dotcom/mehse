<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\IsEkipmani;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Günlük Ekipman Kontrol Formu (13.10.2026) — operatörün her gün/vardiya işe
 * başlamadan doldurduğu BOŞ form: her soru için Uygun / Uygun Değil / Uygulanamaz
 * + açıklama, "kullanıma uygun mu" kararı ve imzalar. Firmanın kayıtlı her
 * ekipmanı (Periyodik Kontrol) için ayrı sayfa; seri no / marka / konum kayıttan
 * dolar. Sorular ekipman kataloğundaki kontrol formundan gelir
 * (config isg.haftalik_ekipman_kontrol.tipler — EkipmanKatalogu::tanim()['kontrol_formu']).
 */
class GunlukEkipmanKontrolUretici
{
    /**
     * Firmanın kontrol formu olan aktif ekipmanlarından sayfa listesi.
     *
     * @return array<int, array{ekipman: IsEkipmani, form: array{ad: string, sorular: array<int, string>}}>
     */
    public static function sayfalar(Firma $firma): array
    {
        $formlar = collect(config('isg.haftalik_ekipman_kontrol.tipler', []))->keyBy('ad');

        return EkipmanKatalogu::ekipmanlar($firma)
            ->sortBy(fn (IsEkipmani $e) => [$e->tip, $e->ekipman_adi])
            ->map(function (IsEkipmani $e) use ($formlar) {
                $form = $formlar->get(EkipmanKatalogu::tanim($e->tip)['kontrol_formu'] ?? '');

                return $form ? ['ekipman' => $e, 'form' => $form] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function pdf(Firma $firma): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.gunluk-ekipman-kontrol', [
            'sayfalar' => static::sayfalar($firma),
            'firmaUnvan' => $firma->unvan,
        ])->setPaper('a4');

        $ad = 'gunluk-ekipman-kontrol-'.Str::slug($firma->unvan ?: 'firma').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
