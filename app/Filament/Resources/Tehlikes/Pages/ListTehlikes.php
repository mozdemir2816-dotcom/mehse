<?php

namespace App\Filament\Resources\Tehlikes\Pages;

use App\Filament\Resources\Tehlikes\TehlikeResource;
use App\Support\FineKinneyKutuphaneIceAktarici;
use App\Support\TehlikeExcelIceAktarici;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListTehlikes extends ListRecords
{
    protected static string $resource = TehlikeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
            Action::make('excelSablon')
                ->label('Excel Şablonu İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => TehlikeExcelIceAktarici::sablonIndir()),

            Action::make('excelYukle')
                ->label('Toplu Kütüphane Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalDescription('İlk satır başlık kabul edilir. "Kategori" mevcut değilse otomatik oluşturulur — böylece "Kazı Çalışmaları", "Cam Üretimi" gibi yeni iş/sektör kategorilerini de bu yolla ekleyebilirsiniz. Aynı kategoride aynı tehlike metni tekrar yüklenirse üzerine güncellenir.')
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
                        ->helperText('Beklenen sütunlar (şablonu indirin): '.implode(', ', TehlikeExcelIceAktarici::SABLON_BASLIKLARI)),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    try {
                        $sonuc = TehlikeExcelIceAktarici::iceAktar($yol);
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    $baslik = $sonuc['basarili'].' tehlike eklendi/güncellendi';
                    if ($sonuc['yeniKategori'] > 0) {
                        $baslik .= ' ('.$sonuc['yeniKategori'].' yeni kategori oluşturuldu)';
                    }
                    if ($sonuc['cakisma'] > 0) {
                        $baslik .= ' — '.$sonuc['cakisma'].' madde mevcuda çok benzediği için "Kütüphane Çakışmaları" sayfasına ayrıldı';
                    }

                    $bildirim = Notification::make()->title($baslik);

                    if ($sonuc['hatalar']) {
                        $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                    }

                    $sonuc['basarili'] > 0 || $sonuc['cakisma'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                }),

            Action::make('fineKinneyYukle')
                ->label('Fine-Kinney Aktar')
                ->icon('heroicon-o-table-cells')
                ->modalDescription('Sektörel bir Fine-Kinney risk analizi Excel\'i (ör. "İnşaat İSG Risk Analizi ve Fine-Kinney Programı"). Başlık satırı ve sütunlar otomatik bulunur; "Faaliyet Alanı / Ana Kategori" değeri kütüphane kategorisi olur, Olasılık / Frekans / Şiddet de yüklenir. Risk Sihirbazı → Manuel Seçim\'de kategoriye göre süzüp ilgili maddeleri seçersiniz. Aynı kategoride aynı tehlike tekrar yüklenirse güncellenir.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    FileUpload::make('dosya')
                        ->label('Fine-Kinney Excel dosyası')
                        ->disk('local')
                        ->directory('excel-ice-aktarim')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required()
                        ->helperText('Olasılık, Frekans ve Şiddet sütunları bulunan risk tablosu. Büyük dosyalarda yükleme birkaç dakika sürebilir.'),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    try {
                        $sonuc = FineKinneyKutuphaneIceAktarici::iceAktar($yol);
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    if ($sonuc['basarili'] === 0) {
                        Notification::make()->title('Madde bulunamadı')
                            ->body($sonuc['hatalar'][0] ?? 'Dosyada tanınabilir bir Fine-Kinney risk tablosu yok.')
                            ->danger()->send();

                        return;
                    }

                    $baslik = $sonuc['basarili'].' tehlike kütüphaneye eklendi/güncellendi';
                    if ($sonuc['yeniKategori'] > 0) {
                        $baslik .= ' ('.$sonuc['yeniKategori'].' yeni kategori)';
                    }

                    Notification::make()->title($baslik)->success()->send();
                }),

                Action::make('ekipmanRiskYukle')
                    ->label('Ekipman Risk Analizi Yükle (Excel)')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn () => EkipmanRiskYukle::getUrl()),
            ])
                ->label('İçe / Dışa Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->button(),

            CreateAction::make()->label('Yeni Tehlike / Ekipman Tanımla')->icon('heroicon-o-plus')
                ->modal()->modalWidth(Width::ThreeExtraLarge),
        ];
    }
}
