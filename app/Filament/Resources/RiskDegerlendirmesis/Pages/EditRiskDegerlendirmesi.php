<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Support\RiskDegerlendirmesiUretici;
use App\Support\RiskYontemDonusturucu;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRiskDegerlendirmesi extends EditRecord
{
    protected static string $resource = RiskDegerlendirmesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => RiskDegerlendirmesiUretici::pdf($this->record)),
            Action::make('fineKinneyeDonustur')
                ->label('Fine-Kinney\'e Dönüştür')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->yontem === 'matris_5x5')
                ->requiresConfirmation()
                ->modalDescription('Bu değerlendirmenin Fine-Kinney yöntemiyle YENİ bir kopyası oluşturulur (bu kayıt değişmez). Frekans, mevcut Olasılık değerinden tahmini olarak türetilir — yeni kayıtta puanları gözden geçirin.')
                ->action(function () {
                    $yeni = RiskYontemDonusturucu::matristenFineKinneye($this->record);

                    Notification::make()
                        ->title('Fine-Kinney kopyası oluşturuldu')
                        ->body('Puanlar tahminidir, gözden geçirip düzeltin.')
                        ->success()
                        ->send();

                    $this->redirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $yeni]));
                }),
            Action::make('matriseDonustur')
                ->label('Matris (5x5)\'e Dönüştür')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->yontem === 'fine_kinney')
                ->requiresConfirmation()
                ->modalDescription('Bu değerlendirmenin Matris (5x5) yöntemiyle YENİ bir kopyası oluşturulur (bu kayıt değişmez). Frekans ekseni Matris\'te olmadığından yok sayılır — yeni kayıtta puanları gözden geçirin.')
                ->action(function () {
                    $yeni = RiskYontemDonusturucu::fineKinneydenMatrise($this->record);

                    Notification::make()
                        ->title('Matris (5x5) kopyası oluşturuldu')
                        ->body('Puanlar tahminidir, gözden geçirip düzeltin.')
                        ->success()
                        ->send();

                    $this->redirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $yeni]));
                }),
            DeleteAction::make(),
        ];
    }
}
