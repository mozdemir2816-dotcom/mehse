<?php

namespace App\Filament\Resources\Firmas\Pages;

use App\Filament\Resources\Firmas\FirmaResource;
use App\Support\FirmaExcelIceAktarici;
use App\Support\KatipSozlesmeIceAktarici;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
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

            ActionGroup::make([
                Action::make('katipAktar')
                    ->label('Listeyi Yükle')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('İSG-KATİP sözleşme listesini yükle')
                    ->modalDescription('İSG-KATİP → Sözleşme İşlemleri → "Dışa Aktar" ile indirilen ISG_HIZMET_SOZLESME_SURECI_DISA_AKTAR_….xlsx dosyasını yükleyin. Liste saklanır: firma eklerken SGK veya KATİP no yazmanız yeterli olur. Aynı unvanlı işyerleri SGK no ile ayrı firma olarak tutulur.')
                    ->modalSubmitActionLabel('Yükle')
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
                        Radio::make('kapsam')
                            ->label('Ne yapılsın?')
                            ->options([
                                'sec' => 'Listeyi yükle, aktarılacak firmaları ben seçeyim',
                                'tumu' => 'Tümünü aktar (yeniler eklenir, kayıtlılar güncellenir)',
                                'kaydet' => 'Yalnız listeyi sakla (firma eklerken numarayla dolduracağım)',
                            ])
                            ->default('sec')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $userId = (int) Filament::auth()->id();

                        try {
                            $kayitlar = KatipSozlesmeIceAktarici::dosyaOku(Storage::disk('local')->path($data['dosya']));
                        } catch (Throwable $e) {
                            Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                            return;
                        } finally {
                            Storage::disk('local')->delete($data['dosya']);
                        }

                        KatipSozlesmeIceAktarici::listeKaydet($userId, $kayitlar);

                        match ($data['kapsam'] ?? 'sec') {
                            'tumu' => static::katipSonucBildir(KatipSozlesmeIceAktarici::aktar($kayitlar, $userId)),
                            'kaydet' => Notification::make()->title('İSG-KATİP listesi yüklendi')
                                ->body(count($kayitlar).' işyeri. Firma eklerken SGK veya KATİP no yazınca bilgiler otomatik dolar.')
                                ->success()->send(),
                            default => $this->replaceMountedAction('katipSec'),
                        };
                    }),

                Action::make('katipSec')
                    ->label('Listeden Firma Seç')
                    ->icon('heroicon-o-queue-list')
                    ->visible(fn () => KatipSozlesmeIceAktarici::liste((int) Filament::auth()->id()) !== null)
                    ->modalHeading('İSG-KATİP listesinden firma aktar')
                    ->modalDescription(fn () => 'Liste yüklenme: '
                        .Carbon::parse(KatipSozlesmeIceAktarici::liste((int) Filament::auth()->id())['yuklenme'] ?? now())->format('d.m.Y H:i')
                        .'. Yeni firmalar işaretli gelir; kayıtlı firmaları işaretlerseniz çalışan sayısı, tehlike sınıfı, NACE ve sözleşme tarihleri güncellenir.')
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitActionLabel('Seçilenleri Aktar')
                    ->schema([
                        CheckboxList::make('secilenler')
                            ->label('İşyerleri')
                            ->options(fn () => collect(static::katipListesi())->map(fn ($k) => $k['unvan'])->all())
                            ->descriptions(fn () => collect(static::katipListesi())->map(fn ($k) => static::katipAciklama($k))->all())
                            ->default(fn () => collect(static::katipListesi())
                                ->filter(fn ($k) => ! $k['bitti'] && ! KatipSozlesmeIceAktarici::mevcutFirma($k))
                                ->keys()->map(fn ($a) => (string) $a)->values()->all())
                            ->searchable()
                            ->bulkToggleable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $secilen = array_intersect_key(static::katipListesi(), array_flip(array_map('strval', $data['secilenler'] ?? [])));

                        static::katipSonucBildir(KatipSozlesmeIceAktarici::aktar($secilen, (int) Filament::auth()->id()));
                    }),
            ])
                ->label('İSG-KATİP')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('primary')
                ->button(),

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
    /** @return array<string, array<string, mixed>> anahtar string (CheckboxList değerleriyle eşleşsin) */
    private static function katipListesi(): array
    {
        $kayitlar = KatipSozlesmeIceAktarici::liste((int) Filament::auth()->id())['kayitlar'] ?? [];

        return collect($kayitlar)->mapWithKeys(fn ($k, $a) => [(string) $a => $k])->sortBy('unvan')->all();
    }

    private static function katipAciklama(array $kayit): string
    {
        $durum = match (true) {
            $kayit['bitti'] => 'Sözleşme sona ermiş',
            KatipSozlesmeIceAktarici::mevcutFirma($kayit) !== null => 'Kayıtlı firma (güncellenir)',
            default => 'YENİ',
        };

        return implode(' · ', array_filter([
            $durum,
            $kayit['sgk_sicil_no'] ? 'SGK '.$kayit['sgk_sicil_no'] : null,
            $kayit['il'],
            $kayit['calisan_sayisi'] !== null ? $kayit['calisan_sayisi'].' çalışan' : null,
            $kayit['nace_kodu'] ? 'NACE '.$kayit['nace_kodu'] : null,
            ($kayit['onaylayan'] ?? null) ? 'Onaylayan: '.$kayit['onaylayan'] : null,
        ]));
    }
}
