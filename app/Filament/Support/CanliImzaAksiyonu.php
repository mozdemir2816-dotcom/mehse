<?php

namespace App\Filament\Support;

use App\Models\CanliImza;
use App\Models\Firma;
use App\Support\CanliImzaYonetici;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;

/**
 * 8 belge modülüne (Eğitim Katılım, KKD, Ceza/İhtar, EK-2, Kurul, İşbaşı, Tatbikat, Talimat)
 * tek satırda "Tablette / QR İmzalat" başlık aksiyonu ekler.
 */
class CanliImzaAksiyonu
{
    /**
     * @param  Closure(): (?Firma)  $firmaSaglayici
     */
    public static function make(string $belgeTuru, Closure $firmaSaglayici, ?string $varsayilanBaslik = null): Action
    {
        return Action::make('canliImzalat')
            ->label('Tablette / QR İmzalat')
            ->icon('heroicon-o-pencil-square')
            ->color('info')
            ->outlined()
            ->modalHeading('Tablette / QR ile Canlı Çalışan İmzası')
            ->modalDescription('Çalışan bu oturumu tablette doğrudan veya telefon kamerasıyla QR okutarak imzalayabilir; imzalar QR doğrulamalı mühürlü PDF olarak arşivlenir.')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Yeni İmza Oturumu / QR Oluştur')
            ->schema(function () use ($belgeTuru, $firmaSaglayici, $varsayilanBaslik): array {
                $firma = $firmaSaglayici();
                $mevcutlar = $firma
                    ? CanliImza::query()
                        ->where('user_id', Filament::auth()->id())
                        ->where('firma_id', $firma->id)
                        ->where('belge_turu', $belgeTuru)
                        ->latest('id')
                        ->limit(10)
                        ->get()
                    : collect();

                return [
                    TextInput::make('belge_baslik')
                        ->label('Belge Başlığı')
                        ->default($varsayilanBaslik ?: (CanliImza::BELGE_TURLERI[$belgeTuru] ?? 'İSG Belgesi'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('calisan_adi')
                        ->label('Çalışan Adı Soyadı (Opsiyonel — boşsa çalışan kendi yazar)')
                        ->maxLength(160),
                    View::make('filament.components.canli-imza-listesi')
                        ->viewData([
                            'firma' => $firma,
                            'imzalar' => $mevcutlar,
                        ]),
                ];
            })
            ->action(function (array $data) use ($belgeTuru, $firmaSaglayici): void {
                $firma = $firmaSaglayici();
                if (! $firma) {
                    Notification::make()
                        ->title('Önce işyeri / firma seçin')
                        ->warning()
                        ->send();

                    return;
                }

                $oturum = CanliImzaYonetici::oturumOlustur(
                    Filament::auth()->user(),
                    $firma,
                    $belgeTuru,
                    null,
                    $data['belge_baslik'] ?? null,
                    $data['calisan_adi'] ?? null,
                );

                $url = CanliImzaYonetici::imzaAdresi($oturum);

                Notification::make()
                    ->title('Canlı İmza Oturumu Hazır')
                    ->body('Tablette açmak veya çalışana göndermek için bağlantıyı kullanın: '.$url)
                    ->actions([
                        Action::make('imzaAc')
                            ->label('İmza Ekranını Aç')
                            ->url($url, shouldOpenInNewTab: true)
                            ->button(),
                    ])
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
