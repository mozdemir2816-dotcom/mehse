<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\TatbikatTutanagi as TatbikatSayfasi;
use App\Models\TatbikatTutanagi;
use App\Support\TatbikatTutanagiUretici;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Firma düzenleme sayfasında "Tatbikatlar" sekmesi (02.11.2026). Kayıt oluşturma / düzenleme
 * Tatbikat Tutanağı sayfasında yapılır (senaryo kütüphanesi, ekip, fotoğraf, DÖF); burada liste,
 * hızlı geçiş ve PDF / Word indirme bulunur.
 */
class TatbikatlarRelationManager extends RelationManager
{
    protected static string $relationship = 'tatbikatTutanaklari';

    protected static ?string $title = 'Tatbikatlar';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('tatbikat_tarihi', 'desc')
            ->emptyStateHeading('Bu işyeri için tatbikat kaydı yok')
            ->emptyStateDescription('Acil durum tatbikatı yılda en az bir kez yapılmalı ve kayıt altına alınmalıdır (İşyerlerinde Acil Durumlar Hakkında Yönetmelik Md. 13).')
            ->columns([
                TextColumn::make('tatbikat_tarihi')->label('Tarih')->date('d.m.Y')->sortable(),
                TextColumn::make('senaryo_anahtari')->label('Tatbikat Türü')
                    ->formatStateUsing(fn ($state, TatbikatTutanagi $record) => $record->senaryoEtiketi()),
                TextColumn::make('durum')->label('Durum')->badge()
                    ->formatStateUsing(fn ($state, TatbikatTutanagi $record) => $record->durumEtiketi())
                    ->color(fn (?string $state) => match ($state) {
                        'yapildi' => 'success', 'takip' => 'warning', 'planlandi' => 'info', default => 'gray',
                    }),
                TextColumn::make('tahliye_dk')->label('Tahliye')
                    ->formatStateUsing(fn ($state, TatbikatTutanagi $record) => $state.' dk'.($record->hedefTahliyeDk() ? ' / hedef '.$record->hedefTahliyeDk().' dk' : ''))
                    ->color(fn (TatbikatTutanagi $record) => match ($record->hedefTuttuMu()) {
                        true => 'success', false => 'danger', default => null,
                    }),
                TextColumn::make('katilimci_sayisi')->label('Katılımcı')
                    ->state(fn (TatbikatTutanagi $record) => $record->katilimciSayisi() ?: null),
                TextColumn::make('eksiklikler')->label('DÖF')
                    ->state(fn (TatbikatTutanagi $record) => count($record->dof_onerileri ?? []) ?: null),
            ])
            ->headerActions([
                Action::make('yeni')
                    ->label('Yeni Tatbikat Kaydı Oluştur')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => TatbikatSayfasi::getUrl(['firma' => $this->getOwnerRecord()->getKey()])),
            ])
            ->recordActions([
                Action::make('duzenle')
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (TatbikatTutanagi $record) => TatbikatSayfasi::getUrl(['firma' => $record->firma_id, 'duzenle' => $record->id])),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(fn (TatbikatTutanagi $record) => TatbikatTutanagiUretici::pdf($record)),
                Action::make('word')
                    ->label('Word')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(fn (TatbikatTutanagi $record) => TatbikatTutanagiUretici::word($record)),
            ]);
    }
}
