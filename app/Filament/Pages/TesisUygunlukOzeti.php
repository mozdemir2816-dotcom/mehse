<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\User;
use App\Support\TesisUygunlugu;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Tesis Uygunluk Özeti · İSG Control Tower (isgsuite): seçili işyerinin
 * taşeron, PTW, periyodik kontrol, saha denetimi, birleşik aksiyon ve hizmet
 * süresi durumundan açıklanabilir 0–100 operasyon skoru ve "bugün neye
 * müdahale etmeliyim?" listesi. Salt okunur; kısayollar mevcut modülleri açar.
 */
class TesisUygunlukOzeti extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.tesis-uygunluk-ozeti';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'tesis-uygunluk-ozeti';

    protected static ?string $title = 'Tesis Uygunluk Özeti · İSG Control Tower';

    protected static ?string $navigationLabel = 'Tesis Uygunluk Özeti';

    public ?int $firmaId = null;

    public function mount(): void
    {
        $this->firmaId = request()->integer('firma') ?: array_key_first($this->firmalar);
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

    #[Computed]
    public function sonuc(): ?array
    {
        /** @var User $kullanici */
        $kullanici = Filament::auth()->user();

        return $this->firma ? TesisUygunlugu::hesapla($this->firma, $kullanici) : null;
    }

    public function updatedFirmaId(): void
    {
        $this->yenile();
    }

    public function yenile(): void
    {
        unset($this->firma, $this->sonuc);
    }
}
