<?php

namespace App\Filament\Resources\Firmas;

use App\Models\Firma;
use App\Models\IsyeriHesabi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * "İşyeri Girişi" — firmaya kalıcı e-posta + şifre tanımlar/gösterir (isgsuite
 * "İşyeri Kiosk Giriş Bilgileri"). İlk açılışta hesap oluşur; sonraki açılışlarda
 * aynı bilgiler gösterilir. Şifre yalnız "Şifreyi Sıfırla" ile değişir.
 * Firma listesinde ve firma düzenleme sayfasında ortak kullanılır.
 */
class IsyeriGirisAksiyonu
{
    public static function make(): Action
    {
        return Action::make('isyeriGirisi')
            ->label('İşyeri Girişi')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->modalHeading('İşyeri Giriş Bilgileri')
            ->modalWidth('lg')
            ->modalContent(fn (Firma $record) => view('filament.firma.isyeri-giris', [
                'hesap' => IsyeriHesabi::firmaIcin($record),
                'firma' => $record,
                'adres' => url('/isyeri'),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tamam')
            ->extraModalFooterActions(fn (Firma $record) => [
                Action::make('isyeriSifreSifirla')
                    ->label('Şifreyi Sıfırla')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Eski şifre geçersiz olur; işyerine yeni şifreyi iletmeniz gerekir.')
                    ->action(function () use ($record): void {
                        IsyeriHesabi::firmaIcin($record)->sifreyiSifirla();
                        Notification::make()->title('Yeni şifre oluşturuldu')->body('"İşyeri Girişi"ni tekrar açarak görebilirsiniz.')->success()->send();
                    }),
                Action::make('isyeriGirisDurum')
                    ->label(fn () => IsyeriHesabi::firmaIcin($record)->aktif ? 'Girişi Kapat' : 'Girişi Aç')
                    ->color(fn () => IsyeriHesabi::firmaIcin($record)->aktif ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function () use ($record): void {
                        $hesap = IsyeriHesabi::firmaIcin($record);
                        $hesap->update(['aktif' => ! $hesap->aktif]);
                        Notification::make()->title($hesap->aktif ? 'İşyeri girişi açıldı' : 'İşyeri girişi kapatıldı')->success()->send();
                    }),
            ]);
    }
}
