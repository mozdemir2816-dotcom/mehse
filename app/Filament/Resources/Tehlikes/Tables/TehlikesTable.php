<?php

namespace App\Filament\Resources\Tehlikes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class TehlikesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('kategori'))
            ->columns([
                TextColumn::make('kod')->label('Kod')->width(64),
                TextColumn::make('bolum')->label('Bölüm')->placeholder('—')->toggleable(),
                TextColumn::make('tehlike')->label('Tehlike')->searchable()->wrap()->limit(90),
                TextColumn::make('risk')->label('Risk')->limit(60)->placeholder('—')->toggleable(),
                TextColumn::make('mevzuat')->label('Mevzuat')->placeholder('—')->toggleable(),
            ])
            ->groups([
                Group::make('kategori.ad')->label('Kategori')->collapsible(),
            ])
            ->defaultGroup('kategori.ad')
            ->filters([
                SelectFilter::make('tehlike_kategorisi_id')->label('Kategori')->relationship('kategori', 'ad'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('kod');
    }
}
