<?php

namespace App\Filament\Resources\Calisans;

use App\Filament\Resources\Calisans\Pages\CreateCalisan;
use App\Filament\Resources\Calisans\Pages\EditCalisan;
use App\Filament\Resources\Calisans\Pages\ListCalisans;
use App\Filament\Resources\Calisans\Schemas\CalisanForm;
use App\Filament\Resources\Calisans\Tables\CalisansTable;
use App\Models\Calisan;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CalisanResource extends Resource
{
    protected static ?string $model = Calisan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Çalışan';

    protected static ?string $pluralModelLabel = 'Çalışanlar';

    protected static ?string $recordTitleAttribute = 'ad_soyad';

    protected static ?string $slug = 'calisanlar';

    public static function form(Schema $schema): Schema
    {
        return CalisanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CalisansTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('firma', fn (Builder $q) => $q->where('user_id', Filament::auth()->id()));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalisans::route('/'),
            'create' => CreateCalisan::route('/create'),
            'edit' => EditCalisan::route('/{record}/edit'),
        ];
    }
}
