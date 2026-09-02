<?php

namespace App\Filament\Resources\Tehlikes\Pages;

use App\Filament\Resources\Tehlikes\TehlikeResource;
use App\Support\TehlikeExcelIceAktarici;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListTehlikes extends ListRecords
{
    protected static string $resource = TehlikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excelSablon')
                ->label('Şablon İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => TehlikeExcelIceAktarici::sablonIndir()),

            Action::make('excelYukle')
                ->label('Excel\'den Toplu Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalDescription('İlk satır başlık kabul edilir. "Kategori" mevcut değilse otomatik oluşturulur — böylece "Kazı Çalışmaları", "Cam Üretimi" gibi yeni iş/sektör kategorilerini de bu yolla ekleyebilirsiniz. Aynı kategoride aynı tehlike metni tekrar yüklenirse üzerine güncellenir.')
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
                        ->helperText('Beklenen sütunlar (şablonu indirin): '.implode(', ', TehlikeExcelIceAktarici::SABLON_BASLIKLARI)),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    try {
                        $sonuc = TehlikeExcelIceAktarici::iceAktar($yol);
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    $baslik = $sonuc['basarili'].' tehlike eklendi/güncellendi';
                    if ($sonuc['yeniKategori'] > 0) {
                        $baslik .= ' ('.$sonuc['yeniKategori'].' yeni kategori oluşturuldu)';
                    }
                    if ($sonuc['cakisma'] > 0) {
                        $baslik .= ' — '.$sonuc['cakisma'].' madde mevcuda çok benzediği için "Kütüphane Çakışmaları" sayfasına ayrıldı';
                    }

                    $bildirim = Notification::make()->title($baslik);

                    if ($sonuc['hatalar']) {
                        $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                    }

                    $sonuc['basarili'] > 0 || $sonuc['cakisma'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                }),

            CreateAction::make(),
        ];
    }
}
