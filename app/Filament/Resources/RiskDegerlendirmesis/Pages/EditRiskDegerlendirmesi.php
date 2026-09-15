<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\RiskDegerlendirmesi;
use App\Filament\Support\ImzaSecenegi;
use App\Support\RiskDegerlendirmesiUretici;
use App\Support\RiskYontemDonusturucu;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditRiskDegerlendirmesi extends EditRecord
{
    protected static string $resource = RiskDegerlendirmesiResource::class;

    private ?bool $pdfBuyukCache = null;

    private function pdfBuyukMu(): bool
    {
        return $this->pdfBuyukCache ??= $this->record->maddeler()->count() > RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label(fn () => $this->pdfBuyukMu() && ! $this->record->pdfHazirMi() ? 'PDF Hazırla' : 'PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    // Küçük/orta raporlar: tıklama anında üretilip doğrudan indirilir (eskisi gibi).
                    if (! $this->pdfBuyukMu()) {
                        return RiskDegerlendirmesiUretici::pdf($this->record, ImzaSecenegi::secili($data));
                    }

                    // Büyük rapor + önbellekte güncel bir PDF hazır: diskten doğrudan indir.
                    if ($this->record->pdfHazirMi()) {
                        $ad = 'risk-degerlendirmesi-'.Str::slug($this->record->firma_unvan ?: 'firma').'.pdf';

                        return response()->download(Storage::disk('local')->path($this->record->pdf_yolu), $ad);
                    }

                    // Büyük rapor + henüz üretilmemiş: arka plana talep bırak, tıklama anında indirme yok.
                    $this->record->forceFill(['pdf_talep_edildi_at' => now()])->saveQuietly();

                    Notification::make()
                        ->title('PDF hazırlanıyor')
                        ->body('Bu rapor '.RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI.' maddeden büyük olduğu için hazırlanması yaklaşık 1 dakika sürüyor. Birazdan tekrar "PDF Hazırla"ya tıklayın.')
                        ->info()
                        ->send();

                    return null;
                }),
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
