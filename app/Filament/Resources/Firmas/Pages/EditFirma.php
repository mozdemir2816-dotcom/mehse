<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Support\FirmaDosyaFihristiUretici;
use App\Support\FirmaEvrakZipUretici;
use App\Support\IsKalemiEvrakHazirlayici;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditFirma extends EditRecord
{
    protected static string $resource = FirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dosyaFihristi')
                ->label('Dosya Fihristi (İçindekiler)')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn (Firma $record) => FirmaDosyaFihristiUretici::pdf($record)),

            Action::make('tumEvrakIndir')
                ->label('Tüm Evrakları İndir (ZIP)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->visible(fn (Firma $record): bool => filled(FirmaEvrakZipUretici::secenekler($record)))
                ->action(fn (Firma $record) => FirmaEvrakZipUretici::zip(
                    $record,
                    array_keys(FirmaEvrakZipUretici::secenekler($record)),
                )),

            Action::make('isKalemiEvraklariniHazirla')
                ->label('İş Kalemlerine Göre Evrakları Hazırla')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Seçili iş kalemlerine göre eksik KKD satırları, çalışma talimatları ve (varsa) bir risk değerlendirmesi taslağı eklenir. Var olan kayıtların üstüne yazılmaz.')
                ->visible(fn (Firma $record): bool => filled($record->is_kalemleri))
                ->action(function (Firma $record) {
                    $ozet = IsKalemiEvrakHazirlayici::hazirla($record);

                    $satirlar = [
                        $ozet['kkd_eklenen'].' KKD satırı eklendi',
                        $ozet['talimat_eklenen'].' talimat eklendi',
                        $ozet['risk_olusturuldu']
                            ? 'yeni risk değerlendirmesi taslağı oluşturuldu'
                            : ($ozet['risk_atlandi_neden'] ?? 'risk değerlendirmesi için eşleşen tehlike bulunamadı'),
                    ];

                    if ($ozet['egitim_konu_sayisi'] > 0) {
                        $satirlar[] = $ozet['egitim_konu_sayisi'].' eğitim konusu, eğitim kayıtlarında önerilecek';
                    }

                    Notification::make()
                        ->title('Evraklar hazırlandı')
                        ->body(implode(' · ', $satirlar))
                        ->success()->send();
                }),

            DeleteAction::make(),
        ];
    }
}
