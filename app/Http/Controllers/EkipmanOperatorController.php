<?php

namespace App\Http\Controllers;

use App\Models\IsEkipmani;
use App\Models\Talimat;
use App\Support\EkipmanGunlukKontrol;
use App\Support\EkipmanKatalogu;
use App\Support\TalimatUretici;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * QR operatör ekranı (12.11.2026) — makinenin üstündeki QR okutulunca giriş yapmadan açılır:
 * makinenin çalışma talimatını okur, günlük kontrol formunu doldurur. Ekipmana PIN tanımlıysa
 * önce PIN sorulur (oturumda 12 saat hatırlanır). Anahtar 32 karakter rastgele; bulunamazsa 404.
 */
class EkipmanOperatorController extends Controller
{
    public function goster(Request $request, string $anahtar): Response
    {
        $ekipman = $this->ekipman($anahtar);

        if (! $this->pinGecti($request, $ekipman)) {
            return response()->view('ekipman-operator.pin', ['ekipman' => $ekipman]);
        }

        return response()->view('ekipman-operator.ekran', [
            'ekipman' => $ekipman,
            'talimat' => $this->talimat($ekipman),
            'form' => EkipmanKatalogu::kontrolFormu($ekipman->tip),
            'sonKontrol' => $ekipman->gunlukKontroller()->first(),
            'acikAriza' => $ekipman->bakimlar()->acik()->first(),
        ]);
    }

    public function pin(Request $request, string $anahtar): RedirectResponse
    {
        $ekipman = $this->ekipman($anahtar);
        $kilit = 'ekipman-pin:'.$ekipman->id.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($kilit, 5)) {
            return back()->withErrors(['pin' => 'Çok fazla deneme. '.ceil(RateLimiter::availableIn($kilit) / 60).' dakika sonra tekrar deneyin.']);
        }
        if (! $ekipman->pinDogruMu((string) $request->input('pin'))) {
            RateLimiter::hit($kilit, 900);

            return back()->withErrors(['pin' => 'PIN hatalı.']);
        }

        RateLimiter::clear($kilit);
        $request->session()->put('ekipman_pin.'.$ekipman->id, now()->addHours(12)->timestamp);

        return redirect()->route('ekipman.operator', $anahtar);
    }

    public function kontrol(Request $request, string $anahtar): RedirectResponse
    {
        $ekipman = $this->ekipman($anahtar);
        abort_unless($this->pinGecti($request, $ekipman), 403);
        abort_unless(EkipmanKatalogu::kontrolFormu($ekipman->tip), 404);

        $veri = $request->validate([
            'operator' => ['required', 'string', 'max:120'],
            'cevap' => ['required', 'array'],
            'cevap.*' => ['required', 'in:U,UD,Y'],
            'not' => ['nullable', 'array'],
            'not.*' => ['nullable', 'string', 'max:500'],
            'genel_not' => ['nullable', 'string', 'max:1000'],
        ], [
            'operator.required' => 'Adınızı soyadınızı yazın.',
            'cevap.*.required' => 'Her maddeyi işaretleyin.',
        ]);

        $form = EkipmanKatalogu::kontrolFormu($ekipman->tip);
        if (count($veri['cevap']) < count($form['sorular'])) {
            return back()->withInput()->withErrors(['cevap' => 'Her maddeyi işaretleyin ('.count($veri['cevap']).' / '.count($form['sorular']).').']);
        }

        $cevaplar = collect($veri['cevap'])->map(fn ($c, $i) => ['cevap' => $c, 'not' => $veri['not'][$i] ?? null])->all();
        $kontrol = EkipmanGunlukKontrol::kaydet($ekipman, $veri['operator'], $cevaplar, $veri['genel_not'] ?? null, 'qr', $request->ip());

        $request->session()->put('ekipman_operator', $veri['operator']);

        return redirect()->route('ekipman.operator', $anahtar)->with('kaydedildi', $kontrol->kullanima_uygun ? 'uygun' : 'uygunsuz');
    }

    public function talimatPdf(Request $request, string $anahtar)
    {
        $ekipman = $this->ekipman($anahtar);
        abort_unless($this->pinGecti($request, $ekipman), 403);
        $talimat = $this->talimat($ekipman);
        abort_unless($talimat, 404);

        return TalimatUretici::pdf($talimat);
    }

    private function ekipman(string $anahtar): IsEkipmani
    {
        return IsEkipmani::query()->with('firma')->where('qr_anahtar', $anahtar)->where('aktif', true)->firstOrFail();
    }

    private function pinGecti(Request $request, IsEkipmani $ekipman): bool
    {
        return ! $ekipman->pinGerekliMi() || (int) $request->session()->get('ekipman_pin.'.$ekipman->id) > now()->timestamp;
    }

    private function talimat(IsEkipmani $ekipman): ?Talimat
    {
        return EkipmanKatalogu::firmaTalimati($ekipman);
    }
}
