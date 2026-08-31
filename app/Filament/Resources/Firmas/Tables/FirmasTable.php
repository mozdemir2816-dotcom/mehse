<?php

namespace App\Filament\Resources\Firmas\Tables;

use App\Models\Firma;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FirmasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unvan')->label('Firma')->searchable()->sortable()->weight('bold')
                    ->description(fn (Firma $r) => $r->kisa_ad ?: null)->limit(50),
                TextColumn::make('nace_kodu')->label('NACE')->searchable()->toggleable(),
                TextColumn::make('tehlike_sinifi')->label('Tehlike sınıfı')->badge()
                    ->formatStateUsing(fn (Firma $r) => $r->tehlikeSinifiEtiketi())
                    ->color(fn (string $state) => match ($state) {
                        'cok_tehlikeli' => 'danger',
                        'tehlikeli' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('calisan_sayisi')->label('Çalışan')->numeric()->sortable()->alignCenter(),
                TextColumn::make('il')->label('İl')->searchable()->toggleable(),
                TextColumn::make('sozlesme_bitis')->label('Sözleşme bitişi')->date('d.m.Y')->sortable()
                    ->placeholder('—')
                    ->color(fn (?Firma $r) => $r?->sozlesme_bitis?->isPast() ? 'danger' : null),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('tehlike_sinifi')->label('Tehlike sınıfı')->options(config('isg.tehlike_siniflari')),
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
            ->defaultSort('unvan');
    }
}
