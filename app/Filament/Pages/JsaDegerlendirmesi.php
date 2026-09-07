<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\JsaSablonu;
use App\Support\JsaExcelOkuyucu;
use App\Support\JsaUretici;
use App\Support\JsaWordUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

/**
 * JSA — İşe Özgü Risk Değerlendirmesi kütüphanesi. Kullanıcı "İş Güvenliği
 * Analizi (JSA)" formatındaki Excel'lerini (bkz. örnek DUVAR ÖRME) yükleyip
 * biriktirir; ilerde bir firmaya lazım olduğunda buradan seçip PDF/Word alır.
 * Şablon bölünmez — her kayıt başlık + tüm iş adımları + notlar + imza bloğu.
 */
class JsaDegerlendirmesi extends Page
{
    protected string $view = 'filament.pages.jsa-degerlendirmesi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Yönetimi';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'jsa';

    protected static ?string $title = 'JSA — İşe Özgü Risk Değerlendirmesi';

    protected static ?string $navigationLabel = 'JSA (İşe Özgü Risk)';

    /** Çıktı alınırken künyeye eklenecek firma (opsiyonel). */
    public ?int $firmaId = null;

    public string $arama = '';

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<int, JsaSablonu> */
    #[Computed]
    public function sablonlar(): Collection
    {
        return JsaSablonu::query()
            ->sahip((int) Filament::auth()->id())
            ->when($this->arama !== '', fn ($q) => $q->where('baslik', 'like', '%'.$this->arama.'%'))
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Kütüphane işlemleri
    |--------------------------------------------------------------------------
    */

    public function pdf(int $id)
    {
        $sablon = JsaSablonu::sahip((int) Filament::auth()->id())->find($id);

        return $sablon ? JsaUretici::pdf($sablon, $this->firma) : null;
    }

    public function word(int $id)
    {
        $sablon = JsaSablonu::sahip((int) Filament::auth()->id())->find($id);

        return $sablon ? JsaWordUretici::word($sablon, $this->firma) : null;
    }

    public function sil(int $id): void
    {
        JsaSablonu::sahip((int) Filament::auth()->id())->find($id)?->delete();
        unset($this->sablonlar);
        Notification::make()->title('JSA kütüphaneden silindi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sablonIndir')
                ->label('Boş Şablon İndir (Excel)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => JsaExcelOkuyucu::sablonIndir()),

            Action::make('excelYukle')
                ->label('Excel’den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalDescription('“İş Güvenliği Analizi (JSA)” formatında bir Excel yükleyin (örnek: DUVAR ÖRME). Dosya bölünmeden kütüphaneye eklenir — tüm iş adımları, notlar ve imza bloğu birlikte.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    FileUpload::make('dosya')
                        ->label('JSA Excel dosyası (.xlsx)')
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
                        $sonuc = JsaExcelOkuyucu::oku($yol);
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    if (! $sonuc['ok']) {
                        Notification::make()->title('JSA çözümlenemedi')->body($sonuc['hata'])->danger()->send();

                        return;
                    }

                    $sablon = JsaSablonu::create([
                        'user_id' => Filament::auth()->id(),
                        'baslik' => $sonuc['baslik'],
                        'dokuman_ref' => $sonuc['dokuman_ref'],
                        'revizyon' => $sonuc['revizyon'],
                        'belge_tarihi' => $sonuc['belge_tarihi'],
                        'kapsam' => $sonuc['kapsam'],
                        'adimlar' => $sonuc['adimlar'],
                        'notlar' => $sonuc['notlar'],
                        'imza_rolleri' => $sonuc['imza_rolleri'],
                    ]);

                    unset($this->sablonlar);

                    Notification::make()
                        ->title('JSA kütüphaneye eklendi')
                        ->body($sablon->baslik.' — '.count($sablon->adimlar).' iş adımı')
                        ->success()
                        ->send();
                }),
        ];
    }
}
