<?php

namespace App\Filament\Pages;

use App\Models\ToolboxKonusmasi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Toolbox Konuşması (İş Başı Konuşması) — uzmanın sahada kullandığı hazır kısa
 * güvenlik brifingi metinlerinin kişisel kütüphanesi. Firma bağımsız; Word/PDF/
 * Excel olarak yüklenip listeden indirilir.
 */
class ToolboxKonusmalari extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    use WithFileUploads;

    protected string $view = 'filament.pages.toolbox-konusmalari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'toolbox-konusmalari';

    protected static ?string $title = 'Toolbox Konuşması';

    protected static ?string $navigationLabel = 'Toolbox Konuşması';

    /** @return Collection<int, ToolboxKonusmasi> */
    #[Computed]
    public function konusmalar(): Collection
    {
        return ToolboxKonusmasi::query()
            ->where('user_id', Filament::auth()->id())
            ->latest()
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('konusmaYukle')
                ->label('Toolbox Konuşması Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    TextInput::make('baslik')->label('Başlık')->required(),
                    FileUpload::make('dosya')
                        ->label('Dosya (Word, PDF veya Excel)')
                        ->disk('public')->directory('toolbox-konusmalari')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(15360)
                        ->required(),
                    Textarea::make('aciklama')->label('Açıklama (opsiyonel)')->rows(2),
                ])
                ->action(function (array $data): void {
                    $yol = $data['dosya'];

                    ToolboxKonusmasi::create([
                        'user_id' => Filament::auth()->id(),
                        'baslik' => $data['baslik'],
                        'dosya_adi' => basename($yol),
                        'dosya_yolu' => $yol,
                        'boyut' => Storage::disk('public')->exists($yol) ? Storage::disk('public')->size($yol) : 0,
                        'aciklama' => $data['aciklama'] ?: null,
                    ]);

                    unset($this->konusmalar);
                    Notification::make()->title('Toolbox konuşması yüklendi')->success()->send();
                }),
        ];
    }

    public function konusmaIndir(int $id)
    {
        $konusma = ToolboxKonusmasi::where('user_id', Filament::auth()->id())->find($id);

        if (! $konusma || ! Storage::disk('public')->exists($konusma->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($konusma->dosya_yolu, $konusma->dosya_adi);
    }

    public function konusmaSil(int $id): void
    {
        $konusma = ToolboxKonusmasi::where('user_id', Filament::auth()->id())->find($id);

        if (! $konusma) {
            return;
        }

        if (Storage::disk('public')->exists($konusma->dosya_yolu)) {
            Storage::disk('public')->delete($konusma->dosya_yolu);
        }

        $konusma->delete();
        unset($this->konusmalar);
        Notification::make()->title('Toolbox konuşması silindi')->success()->send();
    }
}
