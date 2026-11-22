<?php

namespace App\Filament\Support;

use App\Models\Firma;
use App\Support\SanalMufettis;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;

/**
 * Sanal Müfettiş — 6331 Sayılı Kanun İPC (TL Ceza Riski) Simülatörü başlık aksiyonu (E1).
 */
class SanalMufettisAksiyonu
{
    /**
     * @param  Closure(): (?Firma)  $firmaSaglayici
     */
    public static function make(Closure $firmaSaglayici): Action
    {
        return Action::make('sanalMufettis')
            ->label('⚖ Sanal Müfettiş (İPC Riski)')
            ->icon('heroicon-o-scale')
            ->color('danger')
            ->outlined()
            ->modalHeading('⚖ Sanal Müfettiş — 6331 Sayılı Kanun İPC Risk Analizi')
            ->modalDescription('Seçili işyerinin mevcut İSG evrakları 6331 m.26 maddelerine göre taranır; 0–100 denetim skoru ve tahmini idari para cezası (TL) aralığı hesaplanır.')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Sanal Müfettiş Raporunu PDF İndir')
            ->schema(function () use ($firmaSaglayici): array {
                $firma = $firmaSaglayici();

                return [
                    View::make('filament.components.sanal-mufettis-ozet')
                        ->viewData([
                            'firma' => $firma,
                            'rapor' => $firma ? SanalMufettis::denetle($firma) : null,
                        ]),
                ];
            })
            ->action(function () use ($firmaSaglayici) {
                $firma = $firmaSaglayici();
                if (! $firma) {
                    Notification::make()->title('Önce bir firma seçin')->warning()->send();

                    return null;
                }

                return SanalMufettis::pdf($firma);
            });
    }
}
