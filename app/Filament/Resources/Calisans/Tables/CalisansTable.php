<?php

namespace App\Filament\Resources\Calisans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CalisansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad_soyad')->label('Ad soyad')->searchable()->sortable()->weight('bold'),
                TextColumn::make('firma.unvan')->label('Firma')->searchable()->sortable()->limit(40)->toggleable(),
                TextColumn::make('gorev')->label('Görevi')->searchable()->placeholder('—'),
                TextColumn::make('departman')->label('Departman')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('ise_giris')->label('İşe giriş')->date('d.m.Y')->sortable()->placeholder('—'),
                IconColumn::make('agir_tehlikeli_iste')->label('Ağır-tehlikeli')->boolean()->toggleable(),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('firma_id')->label('Firma')->relationship('firma', 'unvan')->searchable()->preload(),
                TernaryFilter::make('aktif')->label('Aktif')->default(true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ad_soyad');
    }
}
