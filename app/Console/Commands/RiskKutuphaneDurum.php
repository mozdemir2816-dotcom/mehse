<?php

namespace App\Console\Commands;

use App\Models\Tehlike;
use App\Support\RiskAlanTamamlama;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Kütüphane onarımının durumu — salt okunur (12.11.2026). Gece onarımı (risk:repair-library-all-fields)
 * neden ilerlemiyor sorusu için: alan bazında eksik sayısı, kota / öncelik kuyruğu engeli, günlük istek
 * sayısı, 30 gün tekrar denenmeyecek madde sayısı.
 *
 *   node deploy/onbellek.mjs --komut "risk:kutuphane-durum"
 */
class RiskKutuphaneDurum extends Command
{
    protected $signature = 'risk:kutuphane-durum';

    protected $description = 'Risk kütüphanesi onarım durumunu raporlar (veri değiştirmez)';

    public function handle(): int
    {
        $kapsam = fn (): Builder => Tehlike::query()->whereNotNull('tehlike')->where('tehlike', '!=', '')
            ->whereHas('kategori', fn ($k) => $k->where(fn ($w) => $w->whereNull('yontem')->orWhere('yontem', '!=', 'hazop')));
        $bos = fn (string $a) => fn ($w) => $w->whereNull($a)->orWhereRaw('TRIM('.$a.') = ?', ['']);

        $this->line('Kapsam (HAZOP dışı): '.$kapsam()->count().' madde · eksik (mevcut önlem hariç — onarım hedefi): '.RiskAlanTamamlama::eksikKosulu($kapsam(), true, mevcutOnlem: false)->count());
        foreach (['risk', 'mevcut_onlem', 'oneri', 'mevzuat', 'sorumlu', 'termin'] as $a) {
            $this->line("  {$a} boş: ".$kapsam()->where($bos($a))->count());
        }
        $this->line('  puan eksik: '.$kapsam()->where(fn ($w) => $w->whereNull('olasilik')->orWhereNull('siddet')->orWhereNull('son_olasilik')->orWhereNull('son_siddet'))->count());
        $this->line('  frekans eksik: '.$kapsam()->where(fn ($w) => $w->whereNull('frekans')->orWhereNull('son_frekans'))->count());

        $this->line('Kota bekleme: '.(Cache::has(RiskKutuphaneOnar::KOTA_ANAHTARI) ? 'EVET (onarım duruyor)' : 'hayır'));
        $this->line('Öncelik kuyruğu — puan: '.json_encode(Cache::get(RiskPuanTamamla::ONCELIK_ANAHTARI, []))
            .' · öneri: '.json_encode(Cache::get(RiskOneriTamamla::ONCELIK_ANAHTARI, [])).' (dolu kuyruk onarımı durdurur)');
        foreach ([now()->subDay(), now()] as $g) {
            $this->line('İstek '.$g->toDateString().': '.(int) Cache::get('risk-kutuphane-onar-istek-'.$g->toDateString(), 0).' / '.config('isg.risk_ai.kutuphane_onarim_gunluk_istek', 25));
        }
        $this->line('30 gün tekrar denenmeyecek madde: '.count(Cache::get('risk-kutuphane-onar-denenen', [])));

        return self::SUCCESS;
    }
}
