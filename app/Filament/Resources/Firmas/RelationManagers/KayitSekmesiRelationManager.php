<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Firma düzenleme sayfasındaki kayıt sekmelerinin ortak tabanı (05.11.2026): firmaya ait TÜM kayıtlar
 * (risk, eğitim, sertifika, DÖF…) modül modül gezmeden firmanın kendi sayfasında listelenir. PDF üreticisi ve tür
 * etiketi `isg.raporlar.kaynaklar` yapılandırmasından gelir; "Modülde Aç" kaydın modül sayfasına götürür.
 */
abstract class KayitSekmesiRelationManager extends RelationManager
{
    /** Kayıt modeli (isg.raporlar.kaynaklar içindeki 'model'). */
    abstract protected static function kayitModeli(): string;

    /** Sekme başlığında kayıt sayısı rozeti. */
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $adet = $ownerRecord->{static::$relationship}()->count();

        return $adet > 0 ? (string) $adet : null;
    }

    /** Kaydın modül sayfası adresi (null → "Modülde Aç" gösterilmez). */
    protected function modulUrl(Model $kayit): ?string
    {
        return null;
    }

    /** "Yeni" düğmesinin gideceği adres (null → düğme yok). */
    protected function yeniUrl(): ?string
    {
        return null;
    }

    /** @return array<string, mixed>|null isg.raporlar.kaynaklar girdisi */
    private function kaynak(): ?array
    {
        return collect(config('isg.raporlar.kaynaklar', []))->firstWhere('model', static::kayitModeli());
    }

    public function table(Table $table): Table
    {
        $kaynak = $this->kaynak();
        $tipMetod = $kaynak['tip_metod'] ?? null;

        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Bu işyeri için kayıt yok')
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('tur')->label('Kayıt')
                    ->state(fn (Model $r) => ($tipMetod && method_exists($r, $tipMetod)) ? $r->{$tipMetod}() : ($kaynak['ad'] ?? class_basename($r)))
                    ->wrap(),
                TextColumn::make('belge_no')->label('Belge No')
                    ->state(fn (Model $r) => $r->getAttribute('belge_no'))
                    ->placeholder('—'),
            ])
            ->headerActions(array_filter([
                $this->yeniUrl()
                    ? Action::make('yeni')->label('Yeni Kayıt')->icon('heroicon-o-plus')->url(fn () => $this->yeniUrl())
                    : null,
            ]))
            ->recordActions(array_filter([
                Action::make('modulde')
                    ->label('Modülde Aç')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Model $r) => $this->modulUrl($r) !== null)
                    ->url(fn (Model $r) => $this->modulUrl($r)),
                $kaynak
                    ? Action::make('pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->action(function (Model $r) use ($kaynak) {
                            $r->loadMissing('firma');

                            return $kaynak['uretici']::{$kaynak['pdf_metod']}($r);
                        })
                    : null,
            ]));
    }
}
