<?php

namespace App\Filament\Resources\Tehlikes;

use App\Filament\Resources\Tehlikes\Pages\CreateTehlike;
use App\Filament\Resources\Tehlikes\Pages\EditTehlike;
use App\Filament\Resources\Tehlikes\Pages\ListTehlikes;
use App\Filament\Resources\Tehlikes\Schemas\TehlikeForm;
use App\Filament\Resources\Tehlikes\Tables\TehlikesTable;
use App\Models\Tehlike;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TehlikeResource extends Resource
{
    protected static ?string $model = Tehlike::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Risk Yönetimi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Tehlike';

    protected static ?string $pluralModelLabel = 'Risk Kütüphanesi';

    protected static ?string $recordTitleAttribute = 'tehlike';

    protected static ?string $slug = 'risk-kutuphanesi';

    public static function form(Schema $schema): Schema
    {
        return TehlikeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TehlikesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTehlikes::route('/'),
            'create' => CreateTehlike::route('/create'),
            'edit' => EditTehlike::route('/{record}/edit'),
        ];
    }
}
