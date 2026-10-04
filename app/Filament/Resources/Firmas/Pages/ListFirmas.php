<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use App\Support\FirmaExcelIceAktarici;
use App\Support\KatipSozlesmeIceAktarici;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListFirmas extends ListRecords
{
    protected static string $resource = FirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excelSablon')
                ->label('Şablon İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => FirmaExcelIceAktarici::sablonIndir()),

            Action::make('katipAktar')
                ->label('İSG-KATİP\'ten Aktar')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('primary')
                ->modalHeading('İSG-KATİP sözleşme listesinden firma aktar')
                ->modalDescription('İSG-KATİP → Sözleşme İşlemleri → "Dışa Aktar" ile indirilen ISG_HIZMET_SOZLESME_SURECI_DISA_AKTAR_….xlsx dosyasını yükleyin. Yeni işyerleri eklenir, kayıtlı olanların çalışan sayısı, tehlike sınıfı, NACE kodu ve sözleşme tarihleri güncellenir. Sona ermiş sözleşmeler için yeni firma açılmaz.')
                ->modalSubmitActionLabel('Aktar')
                ->schema([
                    FileUpload::make('dosya')
                        ->label('İSG-KATİP Excel dosyası')
                        ->disk('local')
                        ->directory('excel-ice-aktarim')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    try {
                        $sonuc = KatipSozlesmeIceAktarici::iceAktar($yol, (int) Filament::auth()->id());
                    } catch (Throwable $e) {
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['dosya']);
                    }

                    static::katipSonucBildir($sonuc);
                }),

            Action::make('excelYukle')
                ->label('Excel\'den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalDescription('İlk satır başlık kabul edilir; sütun adları şablondaki gibi olmalıdır (sırası önemli değil). Yalnızca "Unvan" zorunludur.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
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
                        ->helperText('Beklenen sütunlar (şablonu indirin): '.implode(', ', FirmaExcelIceAktarici::SABLON_BASLIKLARI)),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    // KATİP sözleşme dışa aktarımı bu butondan yüklenirse de doğru içe aktarıcıya gitsin.
                    if (KatipSozlesmeIceAktarici::katipDosyasiMi($yol)) {
                        try {
                            static::katipSonucBildir(KatipSozlesmeIceAktarici::iceAktar($yol, (int) Filament::auth()->id()));
                        } finally {
                            Storage::disk('local')->delete($data['dosya']);
                        }

                        return;
                    }

                    try {
                        $sonuc = FirmaExcelIceAktarici::iceAktar($yol, (int) Filament::auth()->id());
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    $bildirim = Notification::make()->title($sonuc['basarili'].' firma eklendi');

                    if ($sonuc['hatalar']) {
                        $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                    }

                    $sonuc['basarili'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                }),

            CreateAction::make()->label('Firma Ekle')->icon('heroicon-o-plus')
                ->modal()->modalWidth(Width::FiveExtraLarge)->modalHeading('Yeni Firma'),
        ];
    }

    /** @param  array{eklenen: array<int, string>, guncellenen: array<int, string>, atlanan: array<int, string>, hatalar: array<int, string>}  $sonuc */
    public static function katipSonucBildir(array $sonuc): void
    {
        $liste = fn (string $baslik, array $adlar) => $adlar
            ? $baslik.' ('.count($adlar).'): '.implode(', ', array_slice($adlar, 0, 8)).(count($adlar) > 8 ? ' …' : '')
            : null;

        $govde = array_filter([
            $liste('Eklendi', $sonuc['eklenen']),
            $liste('Güncellendi', $sonuc['guncellenen']),
            $liste('Atlandı', $sonuc['atlanan']),
            $liste('Hata', $sonuc['hatalar']),
        ]);

        $bildirim = Notification::make()
            ->title(count($sonuc['eklenen']).' firma eklendi, '.count($sonuc['guncellenen']).' firma güncellendi')
            ->body($govde ? implode("\n\n", $govde) : 'Tüm firmalar zaten güncel.')
            ->persistent();

        $sonuc['hatalar'] && ! $sonuc['eklenen'] && ! $sonuc['guncellenen'] ? $bildirim->danger()->send() : $bildirim->success()->send();
    }
}
