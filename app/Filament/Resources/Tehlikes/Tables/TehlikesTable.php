<?php

namespace App\Filament\Resources\Tehlikes\Tables;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Support\EkipmanRiskKutuphanesi;
use App\Support\NaceGrubu;
use App\Support\RiskAlanTamamlama;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
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
                TextColumn::make('bolum')->label('Bölüm')->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('tehlike')->label('Tehlike')->searchable()->wrap()->limit(90),
                TextColumn::make('risk')->label('Risk')->limit(60)->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('mevzuat')->label('Mevzuat')->placeholder('—')->limit(80)->toggleable(),
            ])
            ->groups([
                Group::make('kategori_bolum')->label('Kategori › Bölüm')->collapsible()
                    ->getKeyFromRecordUsing(fn (Tehlike $r): string => $r->tehlike_kategorisi_id.'|'.($r->bolum ?? ''))
                    ->getTitleFromRecordUsing(fn (Tehlike $r): string => '['.(EkipmanRiskKutuphanesi::YONTEMLER[$r->kategori?->yontem ?? 'fine_kinney'] ?? 'Fine-Kinney').'] '.($r->kategori?->ad ?? 'Kategorisiz').' › '.(filled($r->bolum) ? $r->bolum : 'Bölümsüz'))
                    ->getDescriptionFromRecordUsing(fn (Tehlike $r): string => (static::grupSayilari()[$r->tehlike_kategorisi_id.'|'.($r->bolum ?? '')] ?? 0).' madde')
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
            ->collapsedGroupsByDefault()
            // Kütüphane binlerce madde; "Tümü" her satırı aksiyonlarıyla birlikte basıp
            // sayfayı kilitliyordu — kategori filtresi / arama ile daraltılır.
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->filtersLayout(FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('grupSil')
                    ->label('Grubu Toplu Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Kategori / bölüm maddelerini toplu sil')
                    ->modalDescription('Seçilen kategorideki (ve bölümdeki) tüm kütüphane maddeleri silinir. Daha önce oluşturulmuş risk değerlendirmeleri etkilenmez. Geri alınamaz.')
                    ->modalSubmitActionLabel('Sil')
                    ->fillForm(function ($livewire): array {
                        // Filtrelenen listede tek kategori varsa onu önceden seç.
                        $kategoriler = $livewire->getFilteredTableQuery()->reorder()->pluck('tehlikeler.tehlike_kategorisi_id')->unique();

                        return $kategoriler->count() === 1 ? ['kategori' => $kategoriler->first()] : [];
                    })
                    ->schema([
                        Select::make('kategori')->label('Kategori')->required()->searchable()->live()
                            ->options(fn () => TehlikeKategorisi::withCount('tehlikeler')->orderBy('ad')->get()
                                ->mapWithKeys(fn ($k) => [$k->id => '['.(EkipmanRiskKutuphanesi::YONTEMLER[$k->yontem ?? 'fine_kinney'] ?? 'Fine-Kinney').'] '.$k->ad.' ('.$k->tehlikeler_count.' madde)'])->all())
                            ->afterStateUpdated(fn ($set) => $set('bolum', null)),
                        Select::make('bolum')->label('Bölüm (boş = kategorinin tamamı)')->searchable()
                            ->options(fn ($get) => $get('kategori')
                                ? Tehlike::where('tehlike_kategorisi_id', $get('kategori'))->whereNotNull('bolum')->where('bolum', '!=', '')->distinct()->orderBy('bolum')->pluck('bolum', 'bolum')->all()
                                : []),
                        Checkbox::make('kategoriyiSil')->label('Kategorinin kendisini de sil')
                            ->visible(fn ($get) => filled($get('kategori')) && blank($get('bolum'))),
                    ])
                    ->action(function (array $data): void {
                        $sorgu = Tehlike::where('tehlike_kategorisi_id', $data['kategori']);
                        if (filled($data['bolum'] ?? null)) {
                            $sorgu->where('bolum', $data['bolum']);
                        }
                        $adet = $sorgu->delete();

                        if (blank($data['bolum'] ?? null) && ($data['kategoriyiSil'] ?? false)) {
                            TehlikeKategorisi::whereKey($data['kategori'])->delete();
                        }

                        Notification::make()->title($adet.' madde silindi')->success()->send();
                    }),
            ])
            ->filters([
                SelectFilter::make('yontem')->label('Risk değerlendirme yöntemi')
                    ->options(EkipmanRiskKutuphanesi::YONTEMLER)
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('kategori', fn ($k) => $k->where(fn ($q) => $q->where('yontem', $data['value'])
                            ->when($data['value'] === 'fine_kinney', fn ($q) => $q->orWhereNull('yontem'))))
                        : $query),
                SelectFilter::make('nace_grubu')->label('Sektör / NACE')
                    ->options(fn () => NaceGrubu::secenekler())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('kategori', fn ($k) => $k->whereJsonContains('nace_gruplari', $data['value']))
                        : $query),
                SelectFilter::make('ekipman')->label('Ekipman')->searchable()
                    ->options(fn () => once(fn () => TehlikeKategorisi::query()->whereNotNull('ekipman_tipleri')->pluck('ekipman_tipleri')
                        ->flatten()->filter()->unique()->sort()->mapWithKeys(fn ($e) => [$e => $e])->all()))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('kategori', fn ($k) => $k->whereJsonContains('ekipman_tipleri', $data['value']))
                        : $query),
                SelectFilter::make('tehlike_kategorisi_id')->label('Kategori')->relationship('kategori', 'ad')->searchable(),
                // 08.11.2026: boş alanlı kütüphane maddelerini süz — elle doldurmak için (kütüphane her zaman O/F/Ş tutar).
                SelectFilter::make('bos_alan')->label('Boş alan')
                    ->options(RiskAlanTamamlama::bosAlanSecenekleri(mevzuat: true))
                    ->query(fn ($query, array $data) => RiskAlanTamamlama::bosAlanSuz($query, $data['value'] ?? null, true, true)),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('kod');
    }

    /**
     * Grup başlıklarındaki "N madde" sayıları — her grup için ayrı COUNT yerine tek sorgu.
     * Bölümü boş (null / '') maddeler aynı grupta sayılır.
     *
     * @return array<string, int> "kategori_id|bolum" => adet
     */
    private static function grupSayilari(): array
    {
        return once(fn () => Tehlike::query()
            ->selectRaw("tehlike_kategorisi_id, COALESCE(bolum, '') AS grup_bolum, COUNT(*) AS adet")
            ->groupBy('tehlike_kategorisi_id', 'grup_bolum')
            ->toBase()->get()
            ->mapWithKeys(fn ($s) => [$s->tehlike_kategorisi_id.'|'.$s->grup_bolum => (int) $s->adet])
            ->all());
    }
}
