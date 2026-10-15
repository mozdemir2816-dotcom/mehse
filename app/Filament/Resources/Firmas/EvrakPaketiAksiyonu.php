<?php

namespace App\Filament\Resources\Firmas;

use App\Models\Firma;
use App\Support\FirmaEvrakPaketi;
use App\Support\FirmaEvrakZipUretici;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

/**
 * "Evrakları İndir" — kayıtlı evraklar + firmaya hazır boş (elle doldurulacak) formlar;
 * kullanıcı istediklerini seçer, tek ZIP iner (bkz. FirmaEvrakPaketi). Firma listesinde ve
 * firma düzenleme sayfasında ortak kullanılır.
 */
class EvrakPaketiAksiyonu
{
    public static function make(string $ad = 'evrakIndir'): Action
    {
        $bosFormlar = FirmaEvrakPaketi::bosFormlar();

        return Action::make($ad)
            ->label('Evrakları İndir')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('gray')
            ->visible(fn (Firma $record) => ! $record->trashed())
            ->modalHeading(fn (Firma $record) => 'Evrak Paketi — '.$record->unvan)
            ->modalDescription('İndirmek istediklerinizi işaretleyin. Boş formlarda firma adı basılır; ad soyad, T.C., imza ve katılımcı yerleri firmada kalemle doldurulur.')
            ->modalSubmitActionLabel('ZIP Olarak İndir')
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->schema(fn (Firma $record) => [
                Section::make('Kayıtlı evraklar')
                    ->description(filled(FirmaEvrakZipUretici::secenekler($record)) ? null : 'Bu firma için henüz kaydedilmiş evrak yok.')
                    ->collapsible()
                    ->schema([
                        CheckboxList::make('kayitli')->hiddenLabel()
                            ->options(FirmaEvrakZipUretici::secenekler($record))
                            ->default(array_keys(FirmaEvrakZipUretici::secenekler($record)))
                            ->searchable()->bulkToggleable()->columns(1),
                    ]),
                ...collect(FirmaEvrakPaketi::GRUPLAR)->map(fn (string $baslik, string $grup) => Section::make($baslik)
                    ->collapsible()
                    ->schema([
                        CheckboxList::make($grup)->hiddenLabel()
                            ->options($bosFormlar[$grup])
                            ->bulkToggleable()->columns(2),
                    ]))->values()->all(),
            ])
            ->action(function (Firma $record, array $data) {
                if (! collect($data)->flatten()->filter()->count()) {
                    Notification::make()->title('En az bir evrak seçin')->warning()->send();

                    return null;
                }

                @set_time_limit(300);

                return FirmaEvrakPaketi::zip($record, $data);
            });
    }
}
