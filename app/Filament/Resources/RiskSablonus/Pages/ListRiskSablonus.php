<?php

namespace App\Filament\Resources\RiskSablonus\Pages;

use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Resources\RiskSablonus\RiskSablonuResource;
use App\Models\RiskSablonu;
use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use App\Support\RiskSkorlama;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListRiskSablonus extends ListRecords
{
    protected static string $resource = RiskSablonuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excelSektorSablonu')
                ->label('Excel\'den Sektörel Şablon Oluştur')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalDescription('Kütüphanenizdeki (veya elinizdeki) tam risk analizini bir sektöre etiketleyerek olduğu gibi saklar — hiçbir satır/puan değiştirilmez, seçilmez. Sonra aynı sektörden yeni bir firma geldiğinde Risk Sihirbazı → "Şablonlar" adımından tek tıkla uygularsınız.')
                ->modalSubmitActionLabel('Oluştur')
                ->schema([
                    TextInput::make('ad')->label('Şablon adı')->required()->maxLength(120),
                    Select::make('sektor')->label('Sektör')
                        ->options(collect(config('isg.risk_ai.sektorler'))->map(fn ($s) => $s['ad']))
                        ->native(false)->searchable(),
                    TextInput::make('sektor_adi')->label('Sektör (serbest metin)')
                        ->helperText('Sektör listede yoksa (örn. "Kazı Çalışmaları") buraya yazın'),
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
                        ->helperText('Sütun adlarınız ve sıralamanız önemli değil — başlık satırı ve olasılık/şiddet/frekans otomatik bulunur.'),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    try {
                        $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    if (! $sonuc['adaylar']) {
                        Notification::make()->title('Madde bulunamadı')
                            ->body($sonuc['hatalar'][0] ?? 'Dosyada tanınabilir bir risk tablosu bulunamadı.')
                            ->danger()->send();

                        return;
                    }

                    $yontem = RiskSkorlama::fineKinneyeUyuyorMu($sonuc['adaylar']) ? 'fine_kinney' : 'matris_5x5';

                    RiskSablonu::olustur(
                        Filament::auth()->user(),
                        $data['ad'],
                        $data['sektor'] ?: null,
                        $data['sektor_adi'] ?: null,
                        $yontem,
                        $sonuc['adaylar'],
                    );

                    Notification::make()
                        ->title(count($sonuc['adaylar']).' maddelik sektörel şablon oluşturuldu')
                        ->success()->send();
                }),

            Action::make('sihirbaz')
                ->label('Sihirbazdan oluştur')
                ->icon('heroicon-o-sparkles')
                ->url(RiskSihirbazi::getUrl()),
        ];
    }
}
