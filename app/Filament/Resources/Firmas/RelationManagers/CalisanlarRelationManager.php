<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Resources\Calisans\Schemas\CalisanForm;
use App\Filament\Resources\Calisans\Tables\CalisansTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CalisanlarRelationManager extends RelationManager
{
    protected static string $relationship = 'calisanlar';

    protected static ?string $title = 'Çalışanlar';

    public function form(Schema $schema): Schema
    {
        return CalisanForm::configure($schema, firmaSecimi: false);
    }

    public function table(Table $table): Table
    {
        return CalisansTable::configure($table)
            ->headerActions([
                CreateAction::make()->label('Çalışan Ekle'),
            ]);
    }
}
