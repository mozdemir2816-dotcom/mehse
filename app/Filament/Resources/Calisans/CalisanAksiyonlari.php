<?php

namespace App\Filament\Resources\Calisans;

use App\Models\Firma;
use App\Support\CalisanDosyasiUretici;
use App\Support\CalisanExcelIceAktarici;
use App\Support\CalisanListesiUretici;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Personel listesi başlık aksiyonları — Excel/PDF rapor, örnek şablon, toplu yükleme.
 * Firma → Çalışanlar sekmesinde firma sabittir ($sabitFirma); bağımsız Çalışanlar
 * sayfasında modalda firma seçilir (varsayılan: tablodaki firma filtresi).
 */
class CalisanAksiyonlari
{
    /**
     * @param  Closure(): ?Firma  $sabitFirma
     * @param  Closure(): mixed  $varsayilanFirmaId
     * @return array<int, Action>
     */
    public static function hepsi(?Closure $sabitFirma = null, ?Closure $varsayilanFirmaId = null): array
    {
        return [
            static::rapor('excelRapor', 'Excel Rapor', 'heroicon-o-table-cells', $sabitFirma, $varsayilanFirmaId,
                fn (Firma $f, array $d) => CalisanListesiUretici::excel($f, $d['gorunum'], $d['sube'] ?? null)),
            static::rapor('pdfRapor', 'PDF Rapor', 'heroicon-o-document-text', $sabitFirma, $varsayilanFirmaId,
                fn (Firma $f, array $d) => CalisanListesiUretici::pdf($f, $d['gorunum'], $d['sube'] ?? null)),
            static::rapor('personelDosyasi', 'Toplu Personel Dosyası', 'heroicon-o-folder-arrow-down', $sabitFirma, $varsayilanFirmaId,
                fn (Firma $f, array $d) => CalisanDosyasiUretici::excel($f, $d['gorunum'], $d['sube'] ?? null))
                ->color('primary')
                ->modalDescription('Tüm çalışanlar tek Excel dosyasında: Özet · Personel · Eğitimler (eğitim kayıtları, katılım formları, uzaktan eğitim) · '
                    .'Sağlık (muayene formları, sağlık gözetimi tetkikleri, kan grubu). Sağlık bilgileri KVKK kapsamında özel nitelikli veridir; yalnız yetkili kişilerle paylaşın.'),

            Action::make('excelSablon')
                ->label('Örnek Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => CalisanExcelIceAktarici::sablonIndir()),

            Action::make('excelYukle')
                ->label('Doldurulan Excel\'i Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalDescription('İlk satır başlık kabul edilir, sütun sırası önemli değil; yalnızca "Ad Soyad" zorunlu. '
                    .'Boş hücreler mevcut bilgileri silmez; dolu alanlar işlenir. T.C. No (yoksa ad soyad) eşleşen kayıt güncellenir. '
                    .'İşten çıkış tarihi girilen kayıtlar otomatik pasife alınır. Excel Rapor çıktısı da düzenlenip geri yüklenebilir.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    ...static::firmaAlani($sabitFirma, $varsayilanFirmaId),
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
                        ->helperText('Beklenen sütunlar (Örnek Excel\'i indirin): '.implode(', ', CalisanExcelIceAktarici::SABLON_BASLIKLARI)),
                ])
                ->action(function (array $data) use ($sabitFirma): void {
                    $yol = Storage::disk('local')->path($data['dosya']);
                    $firma = static::firmaBul($sabitFirma, $data);

                    try {
                        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $firma->id);
                    } catch (Throwable $e) {
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['dosya']);
                    }

                    $bildirim = Notification::make()->title($sonuc['basarili'].' çalışan eklendi/güncellendi');

                    if ($sonuc['hatalar']) {
                        $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                    }

                    $sonuc['basarili'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                }),
        ];
    }

    private static function rapor(string $ad, string $etiket, string $ikon, ?Closure $sabitFirma, ?Closure $varsayilanFirmaId, Closure $uret): Action
    {
        return Action::make($ad)
            ->label($etiket)
            ->icon($ikon)
            ->color('gray')
            ->modalWidth('md')
            ->modalSubmitActionLabel('İndir')
            ->schema([
                ...static::firmaAlani($sabitFirma, $varsayilanFirmaId),
                Select::make('gorunum')->label('Personel görünümü')
                    ->options(CalisanListesiUretici::GORUNUMLER)->default('aktif')->required(),
                TextInput::make('sube')->label('Şube (isteğe bağlı)')
                    ->helperText('Boş bırakılırsa tüm şubeler'),
            ])
            ->action(fn (array $data) => $uret(static::firmaBul($sabitFirma, $data), $data));
    }

    /** @return array<int, Select> */
    private static function firmaAlani(?Closure $sabitFirma, ?Closure $varsayilanFirmaId): array
    {
        if ($sabitFirma) {
            return [];
        }

        return [
            Select::make('firma_id')->label('Firma')
                ->options(fn () => Firma::query()
                    ->where('user_id', Filament::auth()->id())
                    ->orderBy('unvan')->pluck('unvan', 'id'))
                ->default(fn () => $varsayilanFirmaId ? $varsayilanFirmaId() : null)
                ->searchable()->required(),
        ];
    }

    private static function firmaBul(?Closure $sabitFirma, array $data): Firma
    {
        return $sabitFirma
            ? $sabitFirma()
            : Firma::query()->where('user_id', Filament::auth()->id())->findOrFail($data['firma_id']);
    }
}
