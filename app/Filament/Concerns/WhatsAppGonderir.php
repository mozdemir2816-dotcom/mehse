<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Sayfalardaki "📲 WhatsApp ile Gönder" düğmeleri (02.11.2026): bağlantı varsa yeni sekmede açılır
 * (tarayıcı açılır pencereyi engellerse bildirimdeki düğmeyle açılır); işverenin cep telefonu yoksa uyarı.
 */
trait WhatsAppGonderir
{
    protected function whatsappAc(?string $url, string $ne = 'Mesaj'): void
    {
        if (! $url) {
            Notification::make()
                ->title('Firma yetkili telefonu tanımlı değil')
                ->body('İşverenin cep telefonunu Firmalar ekranındaki "Telefon" alanına ya da Acil Durum Planı\'ndaki işveren / işveren vekili telefonuna girin (05xx… biçiminde).')
                ->warning()->persistent()->send();

            return;
        }

        Notification::make()
            ->title($ne.' WhatsApp\'ta hazırlandı')
            ->body('WhatsApp yeni sekmede açılıyor; açılmazsa aşağıdaki düğmeyi kullanın.')
            ->success()
            ->actions([Action::make('ac')->label('WhatsApp\'ı aç')->url($url, shouldOpenInNewTab: true)->button()->color('success')])
            ->send();

        $this->js('window.open('.json_encode($url).', "_blank")');
    }
}
