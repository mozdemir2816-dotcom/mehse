<?php

namespace App\Support;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hizmet ve Kullanım Sözleşmesi PDF'i (rota: filament.admin.sozlesme.pdf). Metin
 * resources/views/yasal/sozlesme.blade.php'den — Müşteri fıkrası giriş yapan kullanıcının
 * Fatura & Profil bilgileriyle dolar. Dijital onay kaşesi users.yasal_onaylar'daki son
 * sözleşme onayından (tarih, IP, sürüm).
 */
class SozlesmePdf
{
    public static function indir(User $user): Response
    {
        $metin = YasalMetinler::aktifler()['sozlesme'] ?? null;
        abort_unless($metin, 404);

        $onay = YasalMetinler::sonOnay($user, 'sozlesme');
        $onayAt = filled($onay['onay_at'] ?? null)
            ? Carbon::parse($onay['onay_at'])->timezone('Europe/Istanbul')
            : null;

        $pdf = Pdf::loadView('pdf.hizmet_sozlesmesi', [
            'user' => $user,
            'metin' => $metin,
            'onay' => $onay,
            'onayAt' => $onayAt,
            'guncel' => YasalMetinler::guncelOnayliMi($user, 'sozlesme'),
            'saglayici' => config('isg.kayit.saglayici'),
            'olusturma' => now()->timezone('Europe/Istanbul'),
        ])->setPaper('a4');

        $ad = Str::slug($user->fatura_unvan ?: $user->name) ?: 'musteri';

        return $pdf->download("mehse-hizmet-sozlesmesi-{$ad}.pdf");
    }
}
