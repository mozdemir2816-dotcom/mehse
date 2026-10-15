<?php

namespace App\Filament\Resources\Firmas\Tables;

use App\Filament\Pages\AcilDurumPlani;
use App\Filament\Resources\Firmas\EvrakPaketiAksiyonu;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\Firmas\IsyeriGirisAksiyonu;
use App\Models\Firma;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FirmasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Çöp Kutusu: silinenler de sorguya girsin; hangi sekmede ne görüneceğini
            // ListFirmas::getTabs() deleted_at'e göre süzer.
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            // Telefonda yalnız ana sütunlar görünür; diğerleri ->visibleFrom('md') (13.10.2026).
            // Çöp Kutusu sekmesinde yalnız firma + silinme tarihi (Geri Al telefonda da görünsün).
            ->columns([
                TextColumn::make('unvan')->label('Firma')->searchable()->sortable()->weight('bold')
                    ->description(fn (Firma $r) => $r->kisa_ad ?: null)->limit(50),
                TextColumn::make('nace_kodu')->hidden(fn ($livewire) => static::copSekmesi($livewire))->visibleFrom('md')->label('NACE')->searchable()->toggleable(),
                TextColumn::make('tehlike_sinifi')->hidden(fn ($livewire) => static::copSekmesi($livewire))->label('Tehlike sınıfı')->badge()
                    ->formatStateUsing(fn (Firma $r) => $r->tehlikeSinifiEtiketi())
                    ->color(fn (string $state) => match ($state) {
                        'cok_tehlikeli' => 'danger',
                        'tehlikeli' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('calisan_sayisi')->hidden(fn ($livewire) => static::copSekmesi($livewire))->label('Çalışan')->numeric()->sortable()->alignCenter(),
                TextColumn::make('isveren_ad')->hidden(fn ($livewire) => static::copSekmesi($livewire))->visibleFrom('md')->label('İşveren / vekili')->searchable(['isveren_ad', 'isveren_vekili'])
                    ->state(fn (Firma $r) => $r->isveren_ad ?: $r->isveren_vekili)->placeholder('—')->toggleable(),
                TextColumn::make('telefon')->hidden(fn ($livewire) => static::copSekmesi($livewire))->visibleFrom('md')->label('Telefon')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('adres')->hidden(fn ($livewire) => static::copSekmesi($livewire))->visibleFrom('md')->label('Adres')->limit(40)->tooltip(fn (Firma $r) => $r->adres)->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('il')->hidden(fn ($livewire) => static::copSekmesi($livewire))->visibleFrom('md')->label('İl')->searchable()->toggleable(),
                TextColumn::make('sozlesme_bitis')->hidden(fn ($livewire) => static::copSekmesi($livewire))->visibleFrom('md')->label('Sözleşme bitişi')->date('d.m.Y')->sortable()
                    ->placeholder('—')
                    ->color(fn (?Firma $r) => $r?->sozlesme_bitis?->isPast() ? 'danger' : null),
                IconColumn::make('aktif')->visibleFrom('md')->label('Aktif')->boolean()
                    ->hidden(fn ($livewire) => static::copSekmesi($livewire)),
                TextColumn::make('deleted_at')->label('Silinme')->dateTime('d.m.Y H:i')
                    ->description(fn (Firma $r) => 'Kalıcı silinmesine '.$r->copteKalanGun().' gün')
                    ->visible(fn ($livewire) => static::copSekmesi($livewire)),
            ])
            ->filters([
                SelectFilter::make('tehlike_sinifi')->label('Tehlike sınıfı')->options(config('isg.tehlike_siniflari')),
                TernaryFilter::make('aktif')->label('Firma görünümü')
                    ->placeholder('Tüm firmalar')->trueLabel('Aktif firmalar')->falseLabel('Pasif firmalar')
                    // Çöp Kutusu sekmesinde aktif/pasif ayrımı yok — silinen her firma görünsün.
                    ->queries(
                        true: fn (Builder $query) => $query->whereRaw('(firmalar.aktif = 1 OR firmalar.deleted_at IS NOT NULL)'),
                        false: fn (Builder $query) => $query->whereRaw('(firmalar.aktif = 0 OR firmalar.deleted_at IS NOT NULL)'),
                    )
                    ->default(true),
            ])
            // Çöp Kutusu'ndaki firma açılmaz; yalnız Geri Al / Kalıcı Sil.
            ->recordUrl(fn (Firma $record) => $record->trashed() ? null : FirmaResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                RestoreAction::make()->label('Geri Al')->button()->color('success')
                    ->successNotificationTitle('Firma tüm kayıtlarıyla geri alındı'),
                ForceDeleteAction::make()->label('Kalıcı Sil')
                    ->modalDescription('Firma ve bağlı TÜM kayıtları (çalışanlar, eğitimler, risk değerlendirmeleri...) kalıcı silinir. Bu işlem geri alınamaz.'),
                Action::make('acilDurumPlani')
                    ->label('Acil Durum Planı')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->hidden(fn (Firma $record) => $record->trashed())
                    ->url(fn (Firma $record) => AcilDurumPlani::getUrl(['firma' => $record->id])),
                EvrakPaketiAksiyonu::make(),
                EditAction::make()->hidden(fn (Firma $record) => $record->trashed()),
                ActionGroup::make([
                    IsyeriGirisAksiyonu::make(),
                    Action::make('pasifeAl')->label('Pasife Al')->icon('heroicon-o-pause-circle')
                        ->visible(fn (Firma $record) => $record->aktif)
                        ->requiresConfirmation()
                        ->modalDescription('Firma listeden düşer ve işyeri girişi çalışmaz; tüm kayıt ve evrakları korunur. İstediğiniz zaman tekrar aktifleştirebilirsiniz.')
                        ->action(fn (Firma $record) => $record->update(['aktif' => false])),
                    Action::make('aktifEt')->label('Aktifleştir')->icon('heroicon-o-play-circle')->color('success')
                        ->visible(fn (Firma $record) => ! $record->aktif)
                        ->action(fn (Firma $record) => $record->update(['aktif' => true])),
                    static::silAksiyonu(DeleteAction::make()),
                ])->hidden(fn (Firma $record) => $record->trashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri Çöp Kutusuna Taşı')
                        ->modalDescription('Seçilen firmalar Çöp Kutusu\'na taşınır; '.Firma::COP_SURESI_AY.' ay içinde geri alınabilir.'),
                    RestoreBulkAction::make()->label('Seçilenleri Geri Al'),
                    ForceDeleteBulkAction::make()->label('Seçilenleri Kalıcı Sil'),
                ]),
            ])
            ->defaultSort('unvan');
    }

    /** Firmalar listesinde "Çöp Kutusu" sekmesi açık mı (ListFirmas::getTabs). */
    public static function copSekmesi(mixed $livewire): bool
    {
        return ($livewire->activeTab ?? null) === 'cop';
    }

    /** Liste ve düzenleme sayfasındaki Sil: Çöp Kutusu'na taşır, kalıcı silmez. */
    public static function silAksiyonu(DeleteAction $aksiyon): DeleteAction
    {
        return $aksiyon
            ->label('Çöp Kutusuna Taşı')
            ->modalHeading('Firma Çöp Kutusu\'na taşınsın mı?')
            ->modalDescription('Firma ve bağlı tüm kayıtları listelerden kalkar. Firmalar > Çöp Kutusu sekmesinden '
                .Firma::COP_SURESI_AY.' ay içinde tek tıkla geri alabilirsiniz; süre dolunca kalıcı silinir.')
            ->modalSubmitActionLabel('Çöp Kutusuna Taşı')
            ->successNotificationTitle('Firma Çöp Kutusu\'na taşındı');
    }
}
