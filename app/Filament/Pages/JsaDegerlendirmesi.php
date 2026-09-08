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
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    /** @var array<int, string> toplu çıktı için işaretlenen JSA id'leri */
    public array $secili = [];

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
            ->with('firmalar:id,unvan')
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
        $this->secili = array_values(array_diff($this->secili, [(string) $id]));
        unset($this->sablonlar);
        Notification::make()->title('JSA kütüphaneden silindi')->success()->send();
    }

    /**
     * Kütüphanedeki bir JSA'nın künyesini (başlık, doküman ref, revizyon, tarih,
     * kapsam) düzenler. Excel'den gelen künye eksik/yanlışsa buradan elle
     * düzeltilir; iş adımları, notlar ve imza bloğu bu aksiyonla değişmez.
     */
    public function kunyeDuzenleAction(): Action
    {
        return Action::make('kunyeDuzenle')
            ->label('Künyeyi Düzenle')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('JSA Künyesini Düzenle')
            ->modalDescription('Bu bilgiler PDF/Word çıktısının üst künyesinde görünür. İş adımlarına, notlara ve imza bloğuna dokunulmaz.')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn (array $arguments): array => JsaSablonu::sahip((int) Filament::auth()->id())
                ->findOrFail($arguments['id'])
                ->only(['baslik', 'dokuman_ref', 'revizyon', 'belge_tarihi', 'kapsam']))
            ->schema([
                TextInput::make('baslik')
                    ->label('Başlık')
                    ->required()
                    ->maxLength(255),
                TextInput::make('dokuman_ref')
                    ->label('Doküman Ref / No')
                    ->maxLength(255),
                TextInput::make('revizyon')
                    ->label('Revizyon No')
                    ->maxLength(255)
                    ->helperText('Örn. 00, 01, A'),
                TextInput::make('belge_tarihi')
                    ->label('Belge / Revizyon Tarihi')
                    ->maxLength(255)
                    ->helperText('Serbest metin — örn. “31 Temmuz 2026” veya “31.07.2026”. Boşsa çıktıda o günün tarihi yazılır.'),
                Textarea::make('kapsam')
                    ->label('Kapsam')
                    ->rows(2),
            ])
            ->action(function (array $data, array $arguments): void {
                $sablon = JsaSablonu::sahip((int) Filament::auth()->id())->findOrFail($arguments['id']);

                $sablon->update([
                    'baslik' => trim($data['baslik']),
                    'dokuman_ref' => filled($data['dokuman_ref']) ? trim($data['dokuman_ref']) : null,
                    'revizyon' => filled($data['revizyon']) ? trim($data['revizyon']) : null,
                    'belge_tarihi' => filled($data['belge_tarihi']) ? trim($data['belge_tarihi']) : null,
                    'kapsam' => filled($data['kapsam']) ? trim($data['kapsam']) : null,
                ]);

                unset($this->sablonlar);

                Notification::make()->title('JSA künyesi güncellendi')->success()->send();
            });
    }

    /**
     * Bir JSA'yı bir veya birden çok firmaya atar (örn. "Kazı" analizini Düzyaka
     * + diğer şantiyelere). Atanan JSA, firmanın "Evrakları İndir" toplu ZIP'inde
     * ve Profilim > Raporlar'da çıkar. İşareti kaldırılan firmadan çıkarılır.
     */
    public function firmalaraEkleAction(): Action
    {
        return Action::make('firmalaraEkle')
            ->label('Firmalara Ekle')
            ->icon('heroicon-o-building-office-2')
            ->modalHeading('Bu JSA’yı Firmalara Ekle')
            ->modalDescription('Seçtiğiniz firmaların evrakına bu JSA eklenir; firma sayfasındaki “Evrakları İndir” ile toplu ZIP’e ve Raporlar listesine dahil olur. İşaretini kaldırdığınız firmadan çıkarılır — üretilmiş belge silinmez, bağ kaldırılır.')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn (array $arguments): array => [
                'firmalar' => JsaSablonu::sahip((int) Filament::auth()->id())
                    ->findOrFail($arguments['id'])
                    ->firmalar()->pluck('firmalar.id')->all(),
            ])
            ->schema([
                CheckboxList::make('firmalar')
                    ->label('Firmalar')
                    ->options(fn (): array => $this->firmalar)
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1)
                    ->noSearchResultsMessage('Firma bulunamadı.')
                    ->helperText('Liste boşsa önce Firmalar sayfasından firma ekleyin.'),
            ])
            ->action(function (array $data, array $arguments): void {
                $sablon = JsaSablonu::sahip((int) Filament::auth()->id())->findOrFail($arguments['id']);

                // Yalnız kullanıcının kendi firmaları — yabancı id enjeksiyonuna karşı.
                $gecerli = array_map('intval', array_keys($this->firmalar));
                $secilen = array_values(array_intersect(array_map('intval', $data['firmalar'] ?? []), $gecerli));

                $sonuc = $sablon->firmalar()->sync($secilen);

                unset($this->sablonlar);

                $eklenen = count($sonuc['attached']);
                $cikarilan = count($sonuc['detached']);

                Notification::make()
                    ->title('Firma ataması güncellendi')
                    ->body(trim(
                        ($eklenen ? "+{$eklenen} firma eklendi. " : '').
                        ($cikarilan ? "-{$cikarilan} firma çıkarıldı." : '')
                    ) ?: 'Değişiklik yok.')
                    ->success()
                    ->send();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Toplu seçim (firmada birden çok iş: kazı + elektrik tesisatı gibi)
    |--------------------------------------------------------------------------
    */

    public function tumunuSec(): void
    {
        $this->secili = $this->sablonlar->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function secimiTemizle(): void
    {
        $this->secili = [];
    }

    /** @return Collection<int, JsaSablonu> */
    private function seciliKayitlar(): Collection
    {
        return JsaSablonu::query()
            ->sahip((int) Filament::auth()->id())
            ->whereIn('id', array_filter($this->secili))
            ->latest()
            ->get();
    }

    public function topluPdf()
    {
        $kayitlar = $this->seciliKayitlar();

        if ($kayitlar->isEmpty()) {
            Notification::make()->title('Önce en az bir JSA işaretleyin')->warning()->send();

            return null;
        }

        return JsaUretici::topluPdf($kayitlar, $this->firma);
    }

    public function topluWord()
    {
        $kayitlar = $this->seciliKayitlar();

        if ($kayitlar->isEmpty()) {
            Notification::make()->title('Önce en az bir JSA işaretleyin')->warning()->send();

            return null;
        }

        return JsaWordUretici::topluWord($kayitlar, $this->firma);
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
