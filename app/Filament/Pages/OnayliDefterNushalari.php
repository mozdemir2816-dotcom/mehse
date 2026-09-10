<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\OnayliDefterNushasi;
use App\Support\TespitOneriDefteriUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Onaylı Defter Nüshaları — mehse'de yazılan Tespit ve Öneri Defteri'nin
 * imzalanmış / onaylı, İSG-KATİP'e yüklenen nüshalarının taranmış hâli.
 * Firma + defter türü başına nüsha numarası otomatik artar (1, 2, 3...).
 */
class OnayliDefterNushalari extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.onayli-defter-nushalari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 19;

    protected static ?string $slug = 'onayli-defter-nushalari';

    protected static ?string $title = 'Onaylı Defter Nüshaları';

    protected static ?string $navigationLabel = 'Onaylı Defter Nüshaları';

    public ?int $firmaId = null;

    public string $defterTuru = 'tespit_oneri';

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<string, Collection<int, OnayliDefterNushasi>> defter türü etiketi => nüshalar */
    #[Computed]
    public function nushalar(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return $this->firma->onayliDefterNushalari()
            ->orderBy('defter_turu')
            ->orderBy('nusha_no')
            ->get()
            ->groupBy(fn (OnayliDefterNushasi $n) => $n->defterTuruEtiketi());
    }

    #[Computed]
    public function sonrakiNo(): int
    {
        return $this->firma
            ? OnayliDefterNushasi::sonrakiNo($this->firma->id, $this->defterTuru)
            : 1;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->nushalar);
    }

    public function updatedDefterTuru(): void
    {
        unset($this->nushalar);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tespitOneriPdf')
                ->label('Tespit-Öneri Defterini İndir (imzalanmak üzere)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => filled($this->firma?->tespitOneriDefteri?->maddeler))
                ->action(fn () => TespitOneriDefteriUretici::pdf($this->firma->tespitOneriDefteri)),

            Action::make('nushaYukle')
                ->label('Onaylı Nüsha Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('İmzalanmış / onaylanmış defter nüshasının taranmış hâlini (PDF veya görsel) yükleyin. Nüsha numarası firma ve defter türüne göre otomatik verilir.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    Select::make('defter_turu')
                        ->label('Defter türü')
                        ->options(config('isg.onayli_defter.turleri'))
                        ->default(fn () => $this->defterTuru)
                        ->native(false)
                        ->required()
                        ->live(),
                    TextInput::make('nusha_no_onizleme')
                        ->label('Nüsha numarası')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state, $get) => OnayliDefterNushasi::sonrakiNo(
                            $this->firma->id,
                            $get('defter_turu') ?: 'tespit_oneri',
                        ).'. Nüsha'),
                    DatePicker::make('onay_tarihi')->label('Onay / imza tarihi')->default(now()),
                    TextInput::make('donem')->label('Dönem (opsiyonel)')->placeholder('örn. 2026 / 1. çeyrek'),
                    FileUpload::make('dosya')
                        ->label('Taranmış nüsha')
                        ->disk('public')
                        ->directory(fn () => 'onayli-defter/'.$this->firmaId)
                        ->preserveFilenames()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(20480)
                        ->required(),
                    Textarea::make('aciklama')->label('Açıklama (opsiyonel)')->rows(2),
                ])
                ->action(function (array $data): void {
                    $firma = Firma::where('user_id', Filament::auth()->id())->find($this->firmaId);

                    if (! $firma) {
                        Notification::make()->title('Önce bir firma seçin')->danger()->send();

                        return;
                    }

                    $yol = $data['dosya'];
                    $turu = $data['defter_turu'] ?: 'tespit_oneri';

                    $nusha = OnayliDefterNushasi::create([
                        'firma_id' => $firma->id,
                        'tespit_oneri_defteri_id' => $turu === 'tespit_oneri' ? $firma->tespitOneriDefteri?->id : null,
                        'defter_turu' => $turu,
                        'onay_tarihi' => $data['onay_tarihi'] ?: null,
                        'donem' => $data['donem'] ?: null,
                        'dosya_adi' => basename($yol),
                        'dosya_yolu' => $yol,
                        'boyut' => Storage::disk('public')->exists($yol) ? Storage::disk('public')->size($yol) : 0,
                        'aciklama' => $data['aciklama'] ?: null,
                    ]);

                    unset($this->nushalar);
                    Notification::make()->title($nusha->baslik().' kaydedildi')->success()->send();
                }),
        ];
    }

    public function nushaIndir(int $id)
    {
        $nusha = $this->firma?->onayliDefterNushalari()->find($id);

        if (! $nusha || ! $nusha->dosya_yolu || ! Storage::disk('public')->exists($nusha->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($nusha->dosya_yolu, $nusha->dosya_adi);
    }

    public function nushaSil(int $id): void
    {
        $nusha = $this->firma?->onayliDefterNushalari()->find($id);

        if (! $nusha) {
            return;
        }

        if ($nusha->dosya_yolu && Storage::disk('public')->exists($nusha->dosya_yolu)) {
            Storage::disk('public')->delete($nusha->dosya_yolu);
        }

        $nusha->delete();
        unset($this->nushalar);
        Notification::make()->title('Nüsha silindi')->success()->send();
    }
}
