<?php

namespace App\Filament\Portal\Pages;

use App\Models\EgitimAtamasi;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Çalışan portalı ana sayfa — kendisine atanmış uzaktan eğitimler ve durumları.
 */
class Egitimlerim extends Page
{
    protected string $view = 'filament.portal.pages.egitimlerim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $title = 'Eğitimlerim';

    protected static ?string $slug = '/';

    public static function getNavigationLabel(): string
    {
        return 'Eğitimlerim';
    }

    /** @return Collection<int, EgitimAtamasi> */
    #[Computed]
    public function atamalar(): Collection
    {
        return EgitimAtamasi::query()
            ->where('calisan_id', Filament::auth()->id())
            ->with(['paket.dersler', 'sinavSonuclari'])
            ->latest()
            ->get();
    }
}
