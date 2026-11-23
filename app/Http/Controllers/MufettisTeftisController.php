<?php

namespace App\Http\Controllers;

use App\Models\TeftisPaylasimi;
use App\Support\PortfoyKarne;
use Illuminate\Http\Request;

/**
 * Çalışma Bakanlığı Müfettişi / Denetçi için süreli, salt-okunur ve erişim loglu teftiş ekranı (C21).
 */
class MufettisTeftisController extends Controller
{
    public function goster(string $token, Request $request)
    {
        $paylasim = TeftisPaylasimi::query()
            ->with([
                'firma' => fn ($q) => $q->withoutGlobalScopes()->with(['igu', 'isyeriHekimi', 'dsp']),
                'user',
            ])
            ->where('token', $token)
            ->first();

        if (! $paylasim || ! $paylasim->gecerliMi() || ! $paylasim->firma) {
            return response()->view('mufettis-teftis', [
                'paylasim' => $paylasim,
                'gecerli' => false,
            ], 403);
        }

        // Erişimi logla (son 30 erişim tutulur)
        $kayitlar = array_slice((array) ($paylasim->erisim_kayitlari ?? []), -29);
        $kayitlar[] = [
            'tarih' => now()->timezone('Europe/Istanbul')->format('d.m.Y H:i:s'),
            'ip' => $request->ip(),
            'ajan' => mb_substr((string) $request->userAgent(), 0, 120),
        ];

        $paylasim->forceFill([
            'erisim_sayisi' => $paylasim->erisim_sayisi + 1,
            'son_erisim_at' => now(),
            'erisim_kayitlari' => $kayitlar,
        ])->save();

        $firma = $paylasim->firma;
        $checklist = PortfoyKarne::firmaChecklistDetay($firma);

        return view('mufettis-teftis', [
            'paylasim' => $paylasim,
            'gecerli' => true,
            'firma' => $firma,
            'checklist' => $checklist,
            'riskler' => $firma->riskDegerlendirmeleri()->latest('id')->limit(5)->get(),
            'egitimler' => $firma->egitimKatilimlari()->latest('id')->limit(10)->get(),
            'ekipmanlar' => $firma->isEkipmanlari()->orderBy('ekipman_adi')->limit(30)->get(),
            'kurullar' => $firma->kurulToplantilari()->latest('id')->limit(6)->get(),
            'arsivler' => $firma->arsivDosyalari()->where('aktif', true)->latest('id')->limit(25)->get(),
        ]);
    }
}
