<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\Firma;
use App\Support\FirmaIsgDosyasi as Dosya;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/** Firma Dijital İSG Dosyası — evrakların tek ekranda özeti, PDF ve düzenleme kısayolları, Teftiş Paketi. */
class FirmaIsgDosyasi extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.firma-isg-dosyasi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static string|UnitEnum|null $navigationGroup = 'Firmalar';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'firma-isg-dosyasi';

    protected static ?string $title = 'İSG Dosyası & Evrak Takip';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    #[Computed]
    public function gruplar(): array
    {
        return $this->firma ? Dosya::gruplar($this->firma) : [];
    }

    public function pdf(string $anahtar)
    {
        $yanit = $this->firma ? Dosya::pdf($this->firma, $anahtar) : null;

        if (! $yanit) {
            Notification::make()->title('Belge bulunamadı')->warning()->send();
        }

        return $yanit;
    }

    public function teftisPaketi()
    {
        $yanit = $this->firma ? Dosya::teftisPaketi($this->firma) : null;

        if (! $yanit) {
            Notification::make()->title('Pakete eklenecek evrak yok')->warning()->send();
        }

        return $yanit;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('teftis')
                ->label('📦 Teftiş Paketi (ZIP)')->tooltip('📦 Tüm İSG Evraklarını ZIP İndir (Teftiş Paketi)')
                ->color('success')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => $this->teftisPaketi()),
        ];
    }
}
