<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Filament\Support\ImzaSecenegi;
use App\Support\EkipmanKatalogu;
use App\Support\RiskDegerlendirmesiOfisUretici;
use App\Support\RiskDegerlendirmesiUretici;
use App\Support\RiskYontemDonusturucu;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditRiskDegerlendirmesi extends EditRecord
{
    use \App\Filament\Concerns\KaydetUstte;

    protected static string $resource = RiskDegerlendirmesiResource::class;

    private ?bool $pdfBuyukCache = null;

    private function pdfBuyukMu(): bool
    {
        return $this->pdfBuyukCache ??= $this->record->maddeler()->count() > RiskDegerlendirmesi::PDF_ARKA_PLAN_ESIGI;
    }

    /** @var array{rd: ?RiskDegerlendirmesi, gruplar: array<string, \Illuminate\Support\Collection<int, Tehlike>>, toplam: int}|null */
    private ?array $ekipmanEksikCache = null;

    private function ekipmanEksikleri(): array
    {
        return $this->ekipmanEksikCache ??= $this->record->firma
            ? EkipmanKatalogu::riskEksikleri($this->record->firma, $this->record)
            : ['rd' => $this->record, 'gruplar' => [], 'toplam' => 0];
    }

    /**
     * Firmaya eklenen ekipmanların (Periyodik Kontrol) kütüphane maddelerinden bu
     * değerlendirmede olmayanlar. İşareti kaldırılan "gerekmiyor" sayılır, bir daha gelmez.
     */
    private function ekipmanEksikleriAksiyonu(): Action
    {
        return Action::make('ekipmanEksikleri')
            ->label(fn () => 'Eksik Maddeler ('.$this->ekipmanEksikleri()['toplam'].')')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('danger')
            ->visible(fn () => $this->ekipmanEksikleri()['toplam'] > 0)
            ->modalHeading('Eksik Maddeler — Revize Et')
            ->modalDescription('Firmaya tanımlı ekipmanların ve her firmada beklenen ortak eğitim / acil durum setinin kütüphanede olup bu değerlendirmede bulunmayan maddeleri. İşaretli olanlar eklenir; işaretini kaldırdıklarınız bu değerlendirme için "gerekmiyor" sayılır ve bir daha önerilmez. Değerlendirmede zaten olan maddeler (elle yazdıklarınız dahil) listelenmez.')
            ->modalSubmitActionLabel('Seçilenleri Ekle')
            ->modalWidth('5xl')
            ->schema(fn () => collect(array_values($this->ekipmanEksikleri()['gruplar']))
                ->map(fn ($tehlikeler, int $i) => CheckboxList::make('grup_'.$i)
                    ->label(array_keys($this->ekipmanEksikleri()['gruplar'])[$i].' — '.$tehlikeler->count().' madde')
                    ->options($tehlikeler->mapWithKeys(fn (Tehlike $t) => [$t->id => trim(($t->faaliyet ? $t->faaliyet.' — ' : '').$t->tehlike)])->all())
                    ->descriptions($tehlikeler->mapWithKeys(fn (Tehlike $t) => [$t->id => (string) $t->risk])->all())
                    ->default($tehlikeler->pluck('id')->all())
                    ->bulkToggleable()
                    ->columns(2))
                ->all())
            ->action(function (array $data) {
                $gruplar = $this->ekipmanEksikleri()['gruplar'];
                $listelenen = collect($gruplar)->flatten()->map(fn (Tehlike $t) => $t->id)->all();
                $secilen = collect($data)->flatten()->map(fn ($id) => (int) $id)->intersect($listelenen)->values()->all();
                $ekipmanlar = collect($gruplar)->keys()->reject(fn ($ad) => in_array($ad, EkipmanKatalogu::ORTAK_GRUPLAR, true))->values()->all();

                $eklenen = EkipmanKatalogu::revizeEt($this->record, $secilen, array_values(array_diff($listelenen, $secilen)), $ekipmanlar);
                $gerekmeyen = count($listelenen) - count($secilen);

                Notification::make()
                    ->title($eklenen.' madde eklendi')
                    ->body(($eklenen ? 'Revizyon no ve nedeni güncellendi. Puanları firmaya göre gözden geçirin.' : '').($gerekmeyen ? ' '.$gerekmeyen.' madde "gerekmiyor" olarak işaretlendi.' : ''))
                    ->success()
                    ->send();

                $this->redirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $this->record]));
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->ustKaydet(),
            $this->ekipmanEksikleriAksiyonu(),
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

                    // Önceki talep 5 dk'dır bekliyorsa arka plan görevi (cron) çalışmıyor olabilir —
                    // kullanıcı sonsuza kadar "hazırlanıyor" görmesin (14.10.2026: canlıda cron yanlış PHP ile çalışıyordu).
                    $bekleyen = $this->record->pdf_talep_edildi_at;
                    if ($bekleyen && $bekleyen->between(now()->subMinutes(15), now()->subMinutes(5))) {
                        Notification::make()
                            ->title('PDF hâlâ hazırlanmadı')
                            ->body('Talep '.$bekleyen->diffForHumans().' verildi ama arka plan görevi henüz işlemedi; sunucudaki zamanlanmış görev (cron) çalışmıyor olabilir. Bu arada Excel veya Word olarak indirebilirsiniz.')
                            ->warning()
                            ->persistent()
                            ->send();

                        return null;
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
            Action::make('excel')
                ->label('Excel İndir')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn (array $data) => RiskDegerlendirmesiOfisUretici::excel($this->record, ImzaSecenegi::secili($data))),
            Action::make('word')
                ->label('Word İndir')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn (array $data) => RiskDegerlendirmesiOfisUretici::word($this->record, ImzaSecenegi::secili($data))),
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
