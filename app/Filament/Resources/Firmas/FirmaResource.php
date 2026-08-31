<?php

namespace App\Filament\Resources\Firmas;

use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Filament\Resources\Firmas\Pages\EditFirma;
use App\Filament\Resources\Firmas\Pages\ListFirmas;
use App\Filament\Resources\Firmas\RelationManagers\CalisanlarRelationManager;
use App\Filament\Resources\Firmas\Schemas\FirmaForm;
use App\Filament\Resources\Firmas\Tables\FirmasTable;
use App\Models\Firma;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FirmaResource extends Resource
{
    protected static ?string $model = Firma::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Firma';

    protected static ?string $pluralModelLabel = 'Firmalar';

    protected static ?string $recordTitleAttribute = 'unvan';

    protected static ?string $slug = 'firmalar';

    public static function form(Schema $schema): Schema
    {
        return FirmaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FirmasTable::configure($table);
    }

    /** Uzman yalnızca kendi portföyündeki firmaları görür. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Filament::auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->where('aktif', true)->count();
    }

    public static function getRelations(): array
    {
        return [
            CalisanlarRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFirmas::route('/'),
            'create' => CreateFirma::route('/create'),
            'edit' => EditFirma::route('/{record}/edit'),
        ];
    }
}
