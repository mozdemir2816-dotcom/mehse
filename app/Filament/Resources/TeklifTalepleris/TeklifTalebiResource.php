<?php

namespace App\Filament\Resources\TeklifTalepleris;

use App\Filament\Resources\TeklifTalepleris\Pages\ListTeklifTalepleris;
use App\Models\TeklifTalebi;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
        return false;
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
                EditAction::make()->label('Durum / not')->modalHeading('Talebi güncelle')
                    ->schema([
                        Select::make('durum')->label('Durum')->options(TeklifTalebi::DURUMLAR)->required(),
                        Textarea::make('not')->label('Not')->rows(3),
                    ]),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeklifTalepleris::route('/'),
        ];
    }
}
