<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Tables;

use App\Models\RiskDegerlendirmesi;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class RiskDegerlendirmesisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('firma')->withCount('maddeler'))
            ->columns([
                TextColumn::make('belge_no')->label('Belge No')->searchable()->sortable(),
                TextColumn::make('firma.unvan')->label('Firma')->searchable()->sortable()->weight('bold')->limit(45),
                TextColumn::make('yontem')->label('Yöntem')->badge()
                    ->formatStateUsing(fn (RiskDegerlendirmesi $r) => $r->yontemEtiketi()),
                TextColumn::make('maddeler_count')->label('Madde')->alignCenter()->badge()->color('gray'),
                TextColumn::make('rapor_tarihi')->label('Rapor')->date('d.m.Y')->sortable()->placeholder('—'),
                TextColumn::make('gecerlilik_tarihi')->label('Geçerlilik')->date('d.m.Y')->sortable()->placeholder('—')
                    ->color(fn (RiskDegerlendirmesi $r) => $r->gecerlilikGecti() ? 'danger' : null)
                    ->weight(fn (RiskDegerlendirmesi $r) => $r->gecerlilikGecti() ? 'bold' : null),
                TextColumn::make('durum')->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'yayinlandi' ? 'Yayınlandı' : 'Taslak')
                    ->color(fn (string $state) => $state === 'yayinlandi' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('firma_id')->label('Firma')->relationship('firma', 'unvan')->searchable()->preload(),
                SelectFilter::make('yontem')->label('Yöntem')->options(config('isg.risk_yontemleri')),
                SelectFilter::make('durum')->label('Durum')->options(['taslak' => 'Taslak', 'yayinlandi' => 'Yayınlandı']),
            ])
            ->groups([
                Group::make('firma.unvan')->label('Firma')->collapsible(),
            ])
            ->defaultGroup('firma.unvan')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
