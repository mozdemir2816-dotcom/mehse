<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use App\Support\FirmaExcelIceAktarici;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListFirmas extends ListRecords
{
    protected static string $resource = FirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excelSablon')
                ->label('Şablon İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => FirmaExcelIceAktarici::sablonIndir()),

            Action::make('excelYukle')
                ->label('Excel\'den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalDescription('İlk satır başlık kabul edilir; sütun adları şablondaki gibi olmalıdır (sırası önemli değil). Yalnızca "Unvan" zorunludur.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    FileUpload::make('dosya')
                        ->label('Excel / CSV dosyası')
                        ->disk('local')
                        ->directory('excel-ice-aktarim')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->helperText('Beklenen sütunlar (şablonu indirin): '.implode(', ', FirmaExcelIceAktarici::SABLON_BASLIKLARI)),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    try {
                        $sonuc = FirmaExcelIceAktarici::iceAktar($yol, (int) Filament::auth()->id());
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    $bildirim = Notification::make()->title($sonuc['basarili'].' firma eklendi');

                    if ($sonuc['hatalar']) {
                        $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                    }

                    $sonuc['basarili'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                }),

            CreateAction::make()->label('Firma Ekle')->icon('heroicon-o-plus'),
        ];
    }
}
