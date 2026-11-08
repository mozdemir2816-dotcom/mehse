<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DbSchema;
use UnitEnum;

/**
 * Fatura & Profil — kullanıcı kendi fatura / kurumsal bilgilerini girer (users.fatura_*).
 * Bireysel: ad soyad + 11 haneli TC kimlik no; kurumsal: ünvan + vergi dairesi + 10 haneli
 * vergi no (şahıs şirketi için 11 haneli TC de kabul). Herkes yalnız kendi hesabını düzenler —
 * yetki kısıtı (SinirliErisim) bilinçli olarak yok. Süresi dolan kullanıcı da açabilir
 * (CheckTrialPeriod::MUAF_ROTALAR) — paket alırken fatura bilgisini girebilsin.
 */
class FaturaProfil extends Page
{
    protected string $view = 'filament.pages.fatura-profil';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|UnitEnum|null $navigationGroup = 'Hesap & Arşiv';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Fatura & Profil';

    protected static ?string $slug = 'fatura-profil';

    protected static ?string $title = 'Fatura & Profil';

    public const ALANLAR = ['fatura_tipi', 'fatura_unvan', 'vergi_dairesi', 'vergi_no', 'fatura_adresi', 'fatura_il', 'fatura_ilce'];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Canlıda sütunlar phpMyAdmin SQL'iyle eklenene kadar sayfa gizli kalır (deploy/canli-fatura-bilgileri-01-11-2026.sql). */
    private static ?bool $sutunlarVar = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->aktif
            && (self::$sutunlarVar ??= DbSchema::hasColumn('users', 'fatura_tipi'));
    }

    public function mount(): void
    {
        $veri = $this->kullanici()->only(self::ALANLAR);
        $veri['fatura_tipi'] ??= 'bireysel';

        $this->form->fill($veri);
    }

    public function form(Schema $schema): Schema
    {
        $kurumsal = fn (Get $get): bool => $get('fatura_tipi') === 'kurumsal';

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Fatura Bilgileri')
                    ->description('Paket faturanız bu bilgilerle kesilir. Bilgileriniz yalnızca faturalama için kullanılır.')
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        ToggleButtons::make('fatura_tipi')
                            ->label('Fatura tipi')
                            ->options(User::FATURA_TIPLERI)
                            ->icons(['bireysel' => 'heroicon-o-user', 'kurumsal' => 'heroicon-o-building-office-2'])
                            ->inline()
                            ->required()
                            ->live()
                            ->validationMessages(['required' => 'Fatura tipini seçin.']),
                        Grid::make(2)->schema([
                            TextInput::make('fatura_unvan')
                                ->label(fn (Get $get): string => $kurumsal($get) ? 'Şirket ünvanı' : 'Ad soyad')
                                ->placeholder(fn (Get $get): string => $kurumsal($get) ? 'Örn. Örnek İnşaat San. ve Tic. Ltd. Şti.' : 'Örn. Ayşe Yılmaz')
                                ->required()
                                ->maxLength(191)
                                ->validationMessages(['required' => 'Bu alan zorunludur.'])
                                ->columnSpanFull(),
                            TextInput::make('vergi_dairesi')
                                ->label('Vergi dairesi')
                                ->placeholder('Örn. Uludağ Vergi Dairesi')
                                ->required($kurumsal)
                                ->visible($kurumsal)
                                ->maxLength(100)
                                ->validationMessages(['required' => 'Kurumsal faturada vergi dairesi zorunludur.']),
                            TextInput::make('vergi_no')
                                ->label(fn (Get $get): string => $kurumsal($get) ? 'Vergi numarası' : 'TC kimlik numarası')
                                ->helperText(fn (Get $get): string => $kurumsal($get)
                                    ? '10 haneli vergi numarası (şahıs şirketlerinde 11 haneli TC kimlik no da olur).'
                                    : '11 haneli TC kimlik numaranız.')
                                ->required()
                                ->inputMode('numeric')
                                ->maxLength(11)
                                ->regex(fn (Get $get): string => $kurumsal($get) ? '/^\d{10,11}$/' : '/^\d{11}$/')
                                ->validationMessages([
                                    'required' => 'Bu alan zorunludur.',
                                    'regex' => 'Yalnız rakam girin: vergi no 10, TC kimlik no 11 hanelidir.',
                                ]),
                        ]),
                        Textarea::make('fatura_adresi')
                            ->label('Fatura adresi')
                            ->placeholder('Mahalle, cadde/sokak, bina no, daire no')
                            ->rows(3)
                            ->required()
                            ->maxLength(500)
                            ->validationMessages(['required' => 'Fatura adresi zorunludur.']),
                        Grid::make(2)->schema([
                            TextInput::make('fatura_il')->label('İl')->placeholder('Örn. Bursa')
                                ->required()->maxLength(50)
                                ->validationMessages(['required' => 'İl zorunludur.']),
                            TextInput::make('fatura_ilce')->label('İlçe')->placeholder('Örn. Nilüfer')
                                ->required()->maxLength(50)
                                ->validationMessages(['required' => 'İlçe zorunludur.']),
                        ]),
                    ]),
            ]);
    }

    public function kaydet(): void
    {
        $veri = $this->form->getState();
        $veri['vergi_dairesi'] = $veri['fatura_tipi'] === 'kurumsal' ? ($veri['vergi_dairesi'] ?? null) : null;

        $this->kullanici()->update(collect(self::ALANLAR)->mapWithKeys(fn ($a) => [$a => $veri[$a] ?? null])->all());

        Notification::make()->title('Fatura bilgileriniz kaydedildi')->success()->send();
    }

    public function kullanici(): User
    {
        /** @var User */
        return auth()->user();
    }
}
