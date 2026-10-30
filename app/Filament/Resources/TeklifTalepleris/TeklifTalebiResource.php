<?php

namespace App\Filament\Resources\TeklifTalepleris;

use App\Filament\Resources\TeklifTalepleris\Pages\ListTeklifTalepleris;
use App\Models\TeklifTalebi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Herkese açık tanıtım sayfasından gelen teklif talepleri (üye olmayan ziyaretçiler).
 * Talepler kullanıcıya bağlı değildir; yalnız sahip hesap görür.
 */
class TeklifTalebiResource extends Resource
{
    protected static ?string $model = TeklifTalebi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Firmalar & Çalışanlar';

    /** Menüde gizli — "Pazarlama & Talepler" sekme şeridinden açılır (App\Support\SayfaAileleri, 'pazarlama'). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'Teklif Talebi';

    protected static ?string $pluralModelLabel = 'Teklif Talepleri';

    protected static ?string $navigationLabel = 'Teklif Talepleri';

    protected static ?string $slug = 'teklif-talepleri';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->aktif && $user->sahipMi());
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $yeni = TeklifTalebi::where('durum', 'yeni')->count();

        return $yeni ? (string) $yeni : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-inbox-arrow-down')
            ->emptyStateHeading('Bekleyen Teklif Talebi Bulunmuyor')
            ->emptyStateDescription('Web siteniz üzerinden (mehse.com) gelen teklif formları otomatik olarak buraya düşer.')
            ->emptyStateActions([static::formBaglantisiAksiyonu(), static::yeniGirisAksiyonu()])
            // Boş ekranda aynı düğmeler orta alanda çıkar; liste doluyken üstte durur.
            ->headerActions([
                static::formBaglantisiAksiyonu()->hidden(fn () => ! TeklifTalebi::exists()),
                static::yeniGirisAksiyonu()->hidden(fn () => ! TeklifTalebi::exists()),
            ])
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('firma_adi')->label('Firma')->weight('bold')->searchable(),
                TextColumn::make('kisi')->label('İletişim kişisi')->searchable(),
                TextColumn::make('telefon')->label('Telefon')->url(fn (TeklifTalebi $r) => 'tel:'.preg_replace('/[^0-9+]/', '', $r->telefon))->searchable(),
                TextColumn::make('calisan_sayisi')->label('Çalışan')->numeric()->sortable(),
                TextColumn::make('tehlike_sinifi')->label('Tehlike sınıfı')->badge(),
                TextColumn::make('durum')->label('Durum')->badge()
                    ->formatStateUsing(fn ($state) => TeklifTalebi::DURUMLAR[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'yeni' => 'danger',
                        'arandi' => 'warning',
                        'teklif_verildi' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('not')->label('Not')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('durum')->label('Durum')->options(TeklifTalebi::DURUMLAR),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->label('Durum / not')->modalHeading('Talebi güncelle')
                        ->schema([
                            Select::make('durum')->label('Durum')->options(TeklifTalebi::DURUMLAR)->required(),
                            Textarea::make('not')->label('Not')->rows(3),
                        ]),
                    DeleteAction::make(),
                ]),
            ]);
    }

    /** Tanıtım sayfasındaki teklif formunun herkese açık adresi. */
    public static function formBaglantisiAksiyonu(): Action
    {
        return Action::make('formBaglantisi')
            ->label('Web Formu Bağlantısını Al')
            ->icon('heroicon-o-link')
            ->color('gray')
            ->modalHeading('Teklif formu bağlantısı')
            ->modalDescription('Müşterilere gönderebileceğiniz herkese açık tanıtım sayfası ve teklif formu adresi:')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Kapat')
            ->schema([
                TextInput::make('adres')->label('Bağlantı')->readOnly()->default(fn () => url('/'))
                    ->extraInputAttributes(['onclick' => 'this.select()']),
            ]);
    }

    /** Telefonla / yüz yüze gelen talebi elle kaydetmek için. */
    public static function yeniGirisAksiyonu(): CreateAction
    {
        return CreateAction::make('yeniGiris')
            ->label('Yeni Teklif Girişi Yap')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni teklif talebi')
            ->createAnother(false)
            ->schema([
                TextInput::make('firma_adi')->label('Firma')->required()->maxLength(255),
                TextInput::make('kisi')->label('İletişim kişisi')->required()->maxLength(255),
                TextInput::make('telefon')->label('Telefon')->tel()->required()->maxLength(30),
                TextInput::make('calisan_sayisi')->label('Çalışan sayısı')->numeric()->minValue(1)->required(),
                Select::make('tehlike_sinifi')->label('Tehlike sınıfı')->options(array_combine(TeklifTalebi::TEHLIKE_SINIFLARI, TeklifTalebi::TEHLIKE_SINIFLARI))->required(),
                Select::make('durum')->label('Durum')->options(TeklifTalebi::DURUMLAR)->default('yeni')->required(),
                Textarea::make('not')->label('Not')->rows(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeklifTalepleris::route('/'),
        ];
    }
}
