<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Resources\Calisans\Schemas\CalisanForm;
use App\Filament\Resources\Calisans\Tables\CalisansTable;
use App\Support\CalisanExcelIceAktarici;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CalisanlarRelationManager extends RelationManager
{
    protected static string $relationship = 'calisanlar';

    protected static ?string $title = 'Çalışanlar';

    public function form(Schema $schema): Schema
    {
        return CalisanForm::configure($schema, firmaSecimi: false);
    }

    public function table(Table $table): Table
    {
        return CalisansTable::configure($table)
            ->headerActions([
                Action::make('excelSablon')
                    ->label('Şablon İndir')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(fn () => CalisanExcelIceAktarici::sablonIndir()),

                Action::make('excelYukle')
                    ->label('Excel\'den Toplu Yükle')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->modalDescription('İlk satır başlık kabul edilir; sütun adları şablondaki gibi olmalıdır (sırası önemli değil). Yalnızca "Ad Soyad" zorunlu.')
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
                            ->helperText('Beklenen sütunlar (şablonu indirin): '.implode(', ', CalisanExcelIceAktarici::SABLON_BASLIKLARI)),
                    ])
                    ->action(function (array $data): void {
                        $yol = Storage::disk('local')->path($data['dosya']);
                        $firmaId = $this->getOwnerRecord()->id;

                        try {
                            $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $firmaId);
                        } catch (Throwable $e) {
                            Storage::disk('local')->delete($data['dosya']);
                            Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        Storage::disk('local')->delete($data['dosya']);

                        $bildirim = Notification::make()->title($sonuc['basarili'].' çalışan eklendi/güncellendi');

                        if ($sonuc['hatalar']) {
                            $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                        }

                        $sonuc['basarili'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                    }),

                CreateAction::make()->label('Çalışan Ekle'),
            ]);
    }
}
