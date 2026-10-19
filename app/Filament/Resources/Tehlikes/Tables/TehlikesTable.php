<?php

namespace App\Filament\Resources\Tehlikes\Tables;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
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
                TextColumn::make('mevzuat')->label('Mevzuat')->placeholder('—')->limit(80)->toggleable(),
            ])
            ->groups([
                Group::make('kategori_bolum')->label('Kategori › Bölüm')->collapsible()
                    ->getKeyFromRecordUsing(fn (Tehlike $r): string => $r->tehlike_kategorisi_id.'|'.($r->bolum ?? ''))
                    ->getTitleFromRecordUsing(fn (Tehlike $r): string => ($r->kategori?->ad ?? 'Kategorisiz').' › '.(filled($r->bolum) ? $r->bolum : 'Bölümsüz'))
                    ->groupQueryUsing(fn ($query) => $query->groupBy('tehlikeler.tehlike_kategorisi_id', 'tehlikeler.bolum'))
                    ->orderQueryUsing(fn ($query, string $direction) => $query
                        ->orderBy(TehlikeKategorisi::select('ad')->whereColumn('tehlike_kategorileri.id', 'tehlikeler.tehlike_kategorisi_id')->limit(1), $direction)
                        ->orderBy('tehlikeler.bolum', $direction))
                    ->scopeQueryByKeyUsing(function ($query, ?string $key) {
                        [$kategori, $bolum] = array_pad(explode('|', (string) $key, 2), 2, '');

                        return $query->where('tehlikeler.tehlike_kategorisi_id', $kategori)
                            ->where(fn ($q) => $bolum === '' ? $q->whereNull('tehlikeler.bolum')->orWhere('tehlikeler.bolum', '') : $q->where('tehlikeler.bolum', $bolum));
                    }),
            ])
            ->defaultGroup('kategori_bolum')
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
