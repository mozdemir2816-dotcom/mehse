<?php

namespace App\Filament\Resources\Calisans\Tables;

use App\Models\Calisan;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CalisansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad_soyad')->label('Ad soyad')->searchable()->sortable()->weight('bold'),
                TextColumn::make('tc')->label('T.C. Kimlik')->searchable()->placeholder('—')
                    ->formatStateUsing(fn (Calisan $record) => $record->maskeliTc())->toggleable(),
                TextColumn::make('firma.unvan')->label('Firma')->searchable()->sortable()->limit(40)->toggleable(),
                TextColumn::make('gorev')->label('Görevi')->searchable()->placeholder('—'),
                TextColumn::make('departman')->label('Departman')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('sube')->label('Şube')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('ise_giris')->label('İşe giriş')->date('d.m.Y')->sortable()->placeholder('—'),
                TextColumn::make('isten_cikis')->label('İşten çıkış')->date('d.m.Y')->sortable()->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cinsiyet')->label('Cinsiyet')->placeholder('—')
                    ->formatStateUsing(fn ($state) => Calisan::CINSIYETLER[$state] ?? $state)->toggleable(),
                TextColumn::make('ozel_durum')->label('Özel durum')->placeholder('—')->badge()->color('warning')->toggleable(),
                IconColumn::make('agir_tehlikeli_iste')->label('Ağır-tehlikeli')->boolean()->toggleable(),
                TextColumn::make('aktif')->label('Durum')->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Pasif')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('firma_id')->label('Firma')->relationship('firma', 'unvan')->searchable()->preload(),
                TernaryFilter::make('aktif')->label('Personel görünümü')
                    ->placeholder('Tüm personel')->trueLabel('Aktif personel')->falseLabel('Pasif personel')
                    ->default(true),
                SelectFilter::make('sube')->label('Şube')
                    ->options(fn () => Calisan::query()->whereHas('firma', fn (Builder $q) => $q->where('user_id', Filament::auth()->id()))->whereNotNull('sube')->distinct()->orderBy('sube')->pluck('sube', 'sube')->all()),
                SelectFilter::make('cinsiyet')->label('Cinsiyet')->options(Calisan::CINSIYETLER),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('pasifeAl')->label('Pasife Al')->icon('heroicon-o-pause-circle')->color('gray')
                    ->visible(fn (Calisan $record) => $record->aktif)
                    ->requiresConfirmation()
                    ->modalDescription('Personel listeden düşer ama kaydı ve geçmiş evrakları (eğitim, KKD, muayene) korunur. Çıkış tarihi boşsa bugün yazılır.')
                    ->action(fn (Calisan $record) => static::pasifeAl(collect([$record]))),
                Action::make('aktifEt')->label('Aktifleştir')->icon('heroicon-o-play-circle')->color('success')
                    ->visible(fn (Calisan $record) => ! $record->aktif)
                    ->action(fn (Calisan $record) => $record->update(['aktif' => true, 'isten_cikis' => null])),
                DeleteAction::make()->label('Kalıcı Sil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('topluPasif')->label('Seçilenleri Pasife Al')->icon('heroicon-o-pause-circle')
                        ->requiresConfirmation()
                        ->modalDescription('Seçilen personel pasife alınır; çıkış tarihi boş olanlara bugün yazılır.')
                        ->action(fn (Collection $records) => static::pasifeAl($records))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('topluAktif')->label('Seçilenleri Aktifleştir')->icon('heroicon-o-play-circle')
                        ->action(function (Collection $records): void {
                            Calisan::whereKey($records->modelKeys())->update(['aktif' => true, 'isten_cikis' => null]);
                            Notification::make()->title($records->count().' personel aktifleştirildi')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()->label('Seçilenleri Kalıcı Sil'),
                ]),
            ])
            ->defaultSort('ad_soyad');
    }

    /** @param  \Illuminate\Support\Collection<int, Calisan>  $calisanlar */
    public static function pasifeAl(\Illuminate\Support\Collection $calisanlar): void
    {
        $idler = $calisanlar->pluck('id')->all();

        Calisan::whereKey($idler)->whereNull('isten_cikis')->update(['isten_cikis' => now()->toDateString()]);
        Calisan::whereKey($idler)->update(['aktif' => false]);

        Notification::make()->title(count($idler).' personel pasife alındı')->success()->send();
    }
}
