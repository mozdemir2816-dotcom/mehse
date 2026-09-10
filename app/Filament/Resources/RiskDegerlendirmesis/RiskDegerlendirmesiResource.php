<?php

namespace App\Filament\Resources\RiskDegerlendirmesis;

use App\Filament\Resources\RiskDegerlendirmesis\Pages\CreateRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\EditRiskDegerlendirmesi;
use App\Filament\Resources\RiskDegerlendirmesis\Pages\ListRiskDegerlendirmesis;
use App\Filament\Resources\RiskDegerlendirmesis\RelationManagers\MaddelerRelationManager;
use App\Filament\Resources\RiskDegerlendirmesis\Schemas\RiskDegerlendirmesiForm;
use App\Filament\Resources\RiskDegerlendirmesis\Tables\RiskDegerlendirmesisTable;
use App\Models\RiskDegerlendirmesi;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RiskDegerlendirmesiResource extends Resource
{
    protected static ?string $model = RiskDegerlendirmesi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Risk Değerlendirmesi';

    protected static ?string $pluralModelLabel = 'Risk Değerlendirmeleri';

    protected static ?string $navigationLabel = 'Kayıtlı Değerlendirmeler';

    protected static ?string $recordTitleAttribute = 'belge_no';

    protected static ?string $slug = 'risk-degerlendirmelerim';

    public static function form(Schema $schema): Schema
    {
        return RiskDegerlendirmesiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RiskDegerlendirmesisTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('firma', fn (Builder $q) => $q->where('user_id', Filament::auth()->id()));
    }

    public static function getRelations(): array
    {
        return [
            MaddelerRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiskDegerlendirmesis::route('/'),
            'create' => CreateRiskDegerlendirmesi::route('/create'),
            'edit' => EditRiskDegerlendirmesi::route('/{record}/edit'),
        ];
    }
}
