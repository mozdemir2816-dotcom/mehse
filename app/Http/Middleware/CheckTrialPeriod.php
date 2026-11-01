<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deneme / abonelik süresi dolan kullanıcıyı panelin operasyonel sayfalarından
 * "Süreniz doldu" ekranına yönlendirir. Sahip hesap ve bitiş tarihi olmayan
 * hesaplar muaftır (User::hasActiveTrial). Veriler silinmez; süre uzatılınca
 * (Kullanıcı Yetkileri ekranı) erişim kendiliğinden geri gelir.
 */
class CheckTrialPeriod
{
    /** Süre dolsa da açık kalan panel rotaları. */
    public const MUAF_ROTALAR = [
        'filament.admin.deneme-bitti',
        'filament.admin.paketler',
        'filament.admin.pages.fatura-profil',
        'filament.admin.auth.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasActiveTrial() && ! $request->routeIs(...self::MUAF_ROTALAR)) {
            return redirect()->route('filament.admin.deneme-bitti');
        }

        return $next($request);
    }
}
