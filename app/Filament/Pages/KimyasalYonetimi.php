<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsgAfis;
use App\Models\KimyasalRiskDegerlendirmesi;
use App\Models\KimyasalUrun;
use App\Models\PkdKaydi;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Kimyasal Yönetimi — kimyasal modüllerin tek giriş noktası: SDS / GBF Sicili
 * (Kimyasal Sicili), Kimyasal Risk Değerlendirmesi, Kimyasal Afişleri ve PKD
 * Sicili. Her biri özet sayılarıyla kart + düğme; alt sayfalar menüde ayrıca
 * görünmez (isgsuite "SDS / PKD" tek merkez yaklaşımı).
 */
class KimyasalYonetimi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.kimyasal-yonetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'kimyasal-yonetimi';

    protected static ?string $title = 'Kimyasal Yönetimi — SDS / Risk / Afiş / PKD';

    protected static ?string $navigationLabel = 'Kimyasal Yönetimi (SDS / PKD)';

    public ?int $firmaId = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** Seçili firma ya da kullanıcının tüm firmaları. @return array<int, int> */
    private function kapsam(): array
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar)
            ? [$this->firmaId]
            : array_keys($this->firmalar);
    }

    #[Computed]
    public function sds(): array
    {
        $u = KimyasalUrun::query()->whereIn('firma_id', $this->kapsam())->where('aktif', true)->get();

        return [
            'toplam' => $u->count(),
            'sds_yok' => $u->reject->sdsVarMi()->count(),
            'etiketli' => $u->filter(fn (KimyasalUrun $x) => filled($x->ghs))->count(),
            'gecikmis' => $u->filter(fn (KimyasalUrun $x) => $x->gozdenGecirmeDurumu() === 'gecikmis')->count(),
        ];
    }

    #[Computed]
    public function risk(): array
    {
        $kayitlar = KimyasalRiskDegerlendirmesi::query()->whereIn('firma_id', $this->kapsam())->get();

        return [
            'firma' => $kayitlar->filter->doluMu()->count(),
            'satir' => $kayitlar->sum(fn ($k) => $k->ozet()['toplam']),
            'yuksek' => $kayitlar->sum(fn ($k) => $k->ozet()['yaklasim3_4']),
            'cmr' => $kayitlar->sum(fn ($k) => $k->ozet()['cmr']),
        ];
    }

    #[Computed]
    public function afis(): int
    {
        return IsgAfis::query()
            ->where('user_id', Filament::auth()->id())
            ->where('kategori', 'kimyasal')
            ->where(fn ($q) => $q->whereNull('firma_id')->orWhereIn('firma_id', $this->kapsam()))
            ->count();
    }

    #[Computed]
    public function pkd(): array
    {
        $k = PkdKaydi::query()->whereIn('firma_id', $this->kapsam())->where('durum', '!=', 'arsiv')->get();

        return [
            'toplam' => $k->count(),
            'dosyasiz' => $k->reject->dosyaVarMi()->count(),
            'takip' => $k->filter->takipGerekiyorMu()->count(),
        ];
    }

    public function updatedFirmaId(): void
    {
        unset($this->sds, $this->risk, $this->afis, $this->pkd);
    }

    /** Alt sayfa adresi — seçili firma varsa taşınır. */
    public function adres(string $sayfa, ?string $parca = null): string
    {
        return $sayfa::getUrl(array_filter(['firma' => $this->firmaId])).($parca ? '#'.$parca : '');
    }
}
