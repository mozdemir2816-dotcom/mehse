<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Support\RiskAnalitigi;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Tehlike ve Risk Analitiği (isgsuite): seçili işyerinin risk kayıtlarında
 * baskın tehlike türü, dominans skoru (puan × maruz kişi), NACE'ye göre olası
 * tehlike kaynaklarının kayıtlardaki karşılığı. Tür ve maruz kişi sayısı
 * buradan da hızlıca girilebilir.
 */
class TehlikeRiskAnalitigi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.tehlike-risk-analitigi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'tehlike-risk-analitigi';

    protected static ?string $title = 'Tehlike ve Risk Analitiği';

    protected static ?string $navigationLabel = 'Tehlike ve Risk Analitiği';

    public ?int $firmaId = null;

    public ?int $rdId = null;

    public bool $sadeceEksik = false;

    public function mount(): void
    {
        $id = request()->integer('firma');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : array_key_first($this->firmalar);
        $this->rdId = $this->degerlendirmeler->first()?->id;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    /** @return Collection<int, RiskDegerlendirmesi> */
    #[Computed]
    public function degerlendirmeler(): Collection
    {
        return $this->firma
            ? RiskDegerlendirmesi::query()->where('firma_id', $this->firma->id)->orderByDesc('rapor_tarihi')->orderByDesc('id')->get()
            : collect();
    }

    #[Computed]
    public function rd(): ?RiskDegerlendirmesi
    {
        return $this->degerlendirmeler->firstWhere('id', $this->rdId) ?? $this->degerlendirmeler->first();
    }

    #[Computed]
    public function analiz(): ?array
    {
        return $this->firma ? RiskAnalitigi::analiz($this->firma, $this->rd) : null;
    }

    public function updated(string $alan): void
    {
        if ($alan === 'firmaId') {
            unset($this->firma, $this->degerlendirmeler);
            $this->rdId = $this->degerlendirmeler->first()?->id;
        }

        unset($this->rd, $this->analiz);
    }

    /** Madde tablosundan tür / maruz kişi kaydı (yalnız kullanıcının işyerleri). */
    public function maddeGuncelle(int $maddeId, string $alan, mixed $deger): void
    {
        if (! in_array($alan, ['tehlike_turu', 'maruz_kisi'], true)) {
            return;
        }

        $m = RiskMaddesi::query()
            ->whereHas('riskDegerlendirmesi', fn ($q) => $q->whereIn('firma_id', array_keys($this->firmalar)))
            ->find($maddeId);

        if (! $m) {
            return;
        }

        $deger = trim((string) $deger);

        $m->{$alan} = match ($alan) {
            'tehlike_turu' => array_key_exists($deger, RiskAnalitigi::turler()) ? $deger : null,
            'maruz_kisi' => $deger === '' ? null : max(0, min((int) $deger, 100000)),
        };
        $m->save();

        unset($this->analiz);
    }

    /** Tahmin edilen türleri maddelere kalıcı olarak yazar (uzman sonra düzeltebilir). */
    public function tahminleriKaydet(): void
    {
        if (! $this->analiz) {
            return;
        }

        $n = 0;
        foreach ($this->analiz['satirlar'] as $s) {
            if ($s['tahmin']) {
                $s['madde']->forceFill(['tehlike_turu' => $s['tur']])->saveQuietly();
                $n++;
            }
        }

        unset($this->analiz);

        \Filament\Notifications\Notification::make()->success()->title("{$n} maddenin tehlike türü kaydedildi")->send();
    }
}
