<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IseOzguEgitimKonusu;
use App\Support\IseOzguEgitimKutuphanesi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İşe Özgü Eğitim Konuları — yıllık eğitim planının 4. bölümünü ("İşe ve
 * İşyerine Özgü Riskler") besleyen kütüphane. Konu, NACE ön eki ve/veya iş
 * kalemiyle eşlenir; plan oluşturulurken firmanın iş kalemine (yoksa NACE
 * koduna) uyan konular otomatik gelir. Sistem konuları düzenlenebilir
 * (kendi kopyanız geçerli olur) ya da gizlenebilir.
 */
class IseOzguEgitimKonulari extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.ise-ozgu-egitim-konulari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'ise-ozgu-egitim-konulari';

    protected static ?string $title = 'İşe Özgü Eğitim Konuları';

    protected static ?string $navigationLabel = 'İşe Özgü Eğitim Konuları';

    public string $arama = '';

    public string $kaynakFiltre = '';

    public ?int $firmaId = null;

    public function mount(): void
    {
        $id = request()->integer('firmaId');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : null;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    /** @return Collection<string, array<string, mixed>> */
    #[Computed]
    public function konular(): Collection
    {
        $q = mb_strtolower(trim($this->arama));
        $onerilen = $this->firma ? IseOzguEgitimKutuphanesi::firmaIcin($this->firma)->keys()->all() : null;

        return IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id())
            ->filter(fn ($k, $a) => ($onerilen === null || in_array($a, $onerilen, true))
                && match ($this->kaynakFiltre) {
                    'kendi' => ! $k['sistem'] || $k['duzenlendi'],
                    'kalem' => str_starts_with($a, 'kalem_'),
                    'nace' => ! str_starts_with($a, 'kalem_') && ! str_starts_with($a, 'ozel_'),
                    default => true,
                }
                && ($q === '' || str_contains(mb_strtolower($k['ad'].' '.$k['hedef'].' '.$k['kaynak'].' '.IseOzguEgitimKutuphanesi::naceGoster($k['nace'])), $q)));
    }

    #[Computed]
    public function gizlenenler(): Collection
    {
        return IseOzguEgitimKutuphanesi::gizlenenler(Filament::auth()->id());
    }

    public function updated(): void
    {
        unset($this->firma, $this->konular, $this->gizlenenler);
    }

    /** Plan sayfasındaki "Kütüphaneye kaydet" ile ortak form. */
    public static function konuSemasi(): array
    {
        $kalemler = collect(config('isg.kkd_matris.is_kalemleri', []))->flatten(1)
            ->filter(fn ($k) => filled($k['anahtar'] ?? null))->pluck('ad', 'anahtar')->all();

        return [
            TextInput::make('ad')->label('Eğitimin adı')->required()->maxLength(255),
            Textarea::make('hedef')->label('Eğitimin hedefi')->rows(3),
            Grid::make(2)->schema([
                TextInput::make('egitici')->label('Eğitici')->default(IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI)->maxLength(255),
                TextInput::make('nace')->label('NACE kapsamı')->placeholder('Örn. 41, 43.21')
                    ->helperText('Alt faaliyetleri kapsar: "43.21" → 43.21 ile başlayan tüm kodlar.'),
            ]),
            Select::make('is_kalemleri')->label('İş kalemleri (yapılan işe göre)')->multiple()->options($kalemler)
                ->helperText('Firmada bu iş kalemlerinden biri seçiliyse konu otomatik önerilir.'),
        ];
    }

    private function veri(array $data): array
    {
        return [
            'ad' => trim($data['ad']),
            'hedef' => filled($data['hedef'] ?? null) ? trim($data['hedef']) : null,
            'egitici' => filled($data['egitici'] ?? null) ? trim($data['egitici']) : null,
            'nace_onekleri' => IseOzguEgitimKutuphanesi::naceHazirla($data['nace'] ?? null),
            'is_kalemleri' => array_values($data['is_kalemleri'] ?? []) ?: null,
        ];
    }

    public function yeniKonuAction(): Action
    {
        return Action::make('yeniKonu')
            ->label('Yeni Konu')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni işe özgü eğitim konusu')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn () => ['egitici' => IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI])
            ->schema(fn () => static::konuSemasi())
            ->action(function (array $data): void {
                IseOzguEgitimKonusu::create([...$this->veri($data), 'user_id' => Filament::auth()->id()]);
                $this->updated();
                Notification::make()->success()->title('Konu kaydedildi')->send();
            });
    }

    public function konuDuzenleAction(): Action
    {
        return Action::make('konuDuzenle')
            ->modalHeading('Konuyu düzenle')
            ->modalDescription(fn (array $arguments) => str_starts_with($arguments['anahtar'] ?? '', 'ozel_') ? null : 'Sistem konusu: değişiklik yalnız sizin kütüphanenizde geçerli olur.')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments): array {
                $k = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id())->get($arguments['anahtar'] ?? '');

                return $k ? ['ad' => $k['ad'], 'hedef' => $k['hedef'], 'egitici' => $k['egitici'], 'nace' => IseOzguEgitimKutuphanesi::naceGoster($k['nace']), 'is_kalemleri' => $k['is_kalemleri']] : [];
            })
            ->schema(fn () => static::konuSemasi())
            ->action(function (array $data, array $arguments): void {
                $anahtar = $arguments['anahtar'] ?? '';
                $userId = Filament::auth()->id();

                if (str_starts_with($anahtar, 'ozel_')) {
                    IseOzguEgitimKonusu::query()->where('user_id', $userId)->whereKey((int) substr($anahtar, 5))->first()?->update($this->veri($data));
                } elseif (IseOzguEgitimKutuphanesi::sistemKonulari()->has($anahtar)) {
                    IseOzguEgitimKonusu::query()->updateOrCreate(['user_id' => $userId, 'sistem_anahtari' => $anahtar], [...$this->veri($data), 'gizli' => false]);
                }

                $this->updated();
                Notification::make()->success()->title('Konu güncellendi')->send();
            });
    }

    /** Kendi konusunu siler; sistem konusunu kütüphaneden gizler. */
    public function kaldir(string $anahtar): void
    {
        $userId = Filament::auth()->id();

        if (str_starts_with($anahtar, 'ozel_')) {
            IseOzguEgitimKonusu::query()->where('user_id', $userId)->whereKey((int) substr($anahtar, 5))->delete();
        } elseif ($s = IseOzguEgitimKutuphanesi::sistemKonulari()->get($anahtar)) {
            IseOzguEgitimKonusu::query()->updateOrCreate(['user_id' => $userId, 'sistem_anahtari' => $anahtar], ['ad' => $s['ad'], 'gizli' => true]);
        }

        $this->updated();
    }

    /** Gizlenen ya da düzenlenen sistem konusunu orijinal haline döndürür. */
    public function sistemeDondur(string $anahtar): void
    {
        IseOzguEgitimKonusu::query()->where('user_id', Filament::auth()->id())->where('sistem_anahtari', $anahtar)->delete();
        $this->updated();
    }
}
