<?php

namespace App\Filament\Resources\EgitimPaketis\RelationManagers;

use App\Models\EgitimDersi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Paketin videoları (06.11.2026): her video "Video Ekle" ile ANINDA kaydedilir — sayfadan çıkınca
 * kaydedilmemiş videolar kaybolmaz; bir video silmek ya da değiştirmek diğerlerine dokunmaz.
 * Konu başlığı (modül) ve sıra numarası listede doğrudan yazılarak değiştirilir (1, 2, 3 …).
 * Bir konuya birden fazla video (07.11.2026): satırdaki "Bu Konuya Video Ekle" aynı konu başlığı +
 * sıra no ile yeni video açar; listede 3.1, 3.2 … diye görünür, sıra / konu başlığı değişince
 * konunun tüm videoları birlikte taşınır.
 */
class DerslerRelationManager extends RelationManager
{
    protected static string $relationship = 'tumDersler';

    protected static ?string $title = 'Videolar (konu başlığına göre)';

    /** 4. başlık şimdilik kapalı; eski kayıtlarda varsa listede yine görünür. */
    private const SECILEBILIR_MODULLER = [
        1 => 'Genel Konular',
        2 => 'Sağlık Konuları',
        3 => 'Teknik Konular',
    ];

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $adet = $ownerRecord->tumDersler()->count();

        return $adet > 0 ? (string) $adet : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('modul')->label('Konu başlığı')->options(self::SECILEBILIR_MODULLER)
                ->required()->native(false)->default(1),
            TextInput::make('sira')->label('Sıra no (1, 2, 3 …)')->numeric()->minValue(1)
                ->helperText('Boş bırakırsanız bu konu başlığının sonuna eklenir.'),
            TextInput::make('baslik')->label('Video başlığı')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('video_url')->label('Video linki (YouTube / Vimeo)')->required()->url()->columnSpanFull(),
            TextInput::make('sure_sn')->label('Süre (saniye)')->numeric()
                ->helperText('Boşsa YouTube videosu ilk izlendiğinde otomatik dolar.'),
            Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->reorder()->orderByRaw('COALESCE(modul, 0)')->orderBy('sira')->orderBy('id'))
            ->groups([
                Group::make('modul')
                    ->label('Konu başlığı')
                    ->getTitleFromRecordUsing(fn (EgitimDersi $r) => $r->modul
                        ? $r->modul.'. '.(EgitimDersi::MODULLER[$r->modul] ?? '')
                        : 'Modülsüz (eski dersler)')
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderByRaw('COALESCE(modul, 0) '.$direction))
                    ->collapsible(),
            ])
                        ->defaultGroup('modul')
            ->paginated(false)
            ->emptyStateHeading('Henüz video yok')
            ->emptyStateDescription('"Video Ekle" ile konu başlığını seçip videoyu ekleyin; her video hemen kaydedilir.')
            ->columns([
                TextInputColumn::make('sira')->label('Sıra')->type('number')
                    ->rules(['required', 'integer', 'min:1'])->width('90px')
                    ->updateStateUsing(fn ($state, EgitimDersi $record) => $this->konuyuTasi($record, ['sira' => (int) $state])),
                SelectColumn::make('modul')->label('Konu başlığı')
                    ->options(EgitimDersi::MODULLER)->selectablePlaceholder(false)
                    ->updateStateUsing(fn ($state, EgitimDersi $record) => $this->konuyuTasi($record, ['modul' => (int) $state])),
                TextColumn::make('baslik')->label('Video')->wrap()->searchable()
                    ->description(fn (EgitimDersi $r) => $r->konuIciNo() > 1 ? 'Konunun '.$r->konuIciNo().'. videosu ('.$r->siraEtiketi().')' : null),
                TextColumn::make('video_url')->label('Link')->limit(32)->url(fn (EgitimDersi $r) => $r->video_url ?: null, true)->color('gray')
                    ->placeholder('Video bekleniyor')
                    ->badge(fn (EgitimDersi $r) => blank($r->video_url))
                    ->color(fn (EgitimDersi $r) => blank($r->video_url) ? 'warning' : 'gray'),
                TextColumn::make('sure_sn')->label('Süre')->state(fn (EgitimDersi $r) => $r->sureEtiketi())->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Video Ekle')->icon('heroicon-o-plus')
                    ->mutateDataUsing(fn (array $data) => $this->siraBelirle($data)),
            ])
            ->recordActions([
                Action::make('konuyaVideoEkle')
                    ->label('Bu Konuya Video Ekle')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (EgitimDersi $r) => filled($r->video_url) && $r->konuIciNo() === 1)
                    ->modalHeading(fn (EgitimDersi $r) => 'Konuya video ekle: '.$r->siraEtiketi().'. '.$r->baslik)
                    ->modalDescription('Video bu konunun altına eklenir; çalışan konunun tüm videolarını sırayla izler.')
                    ->schema([
                        TextInput::make('baslik')->label('Video başlığı')->required()->maxLength(255)
                            ->default(fn (EgitimDersi $record) => $record->baslik.' ('.($record->konuVideolari()->count() + 1).'. video)'),
                        TextInput::make('video_url')->label('Video linki (YouTube / Vimeo)')->required()->url(),
                        TextInput::make('sure_sn')->label('Süre (saniye)')->numeric()
                            ->helperText('Boşsa YouTube videosu ilk izlendiğinde otomatik dolar.'),
                    ])
                    ->action(function (array $data, EgitimDersi $record): void {
                        $this->getOwnerRecord()->tumDersler()->create([
                            ...$data,
                            'modul' => $record->modul,
                            'sira' => $record->sira,
                        ]);
                    })
                    ->successNotificationTitle('Video konuya eklendi'),
                EditAction::make()->label(fn (EgitimDersi $r) => blank($r->video_url) ? 'Video Bağla' : 'Düzenle'),
                DeleteAction::make()->label('Sil'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * Konunun ilk videosunun sırası / konu başlığı değişince konunun ek videoları da
     * birlikte taşınır; ek videonun kendisi değişirse yalnız o taşınır (başka konuya geçer).
     */
    private function konuyuTasi(EgitimDersi $record, array $yeni): mixed
    {
        if ($record->konuIciNo() === 1) {
            $record->konuVideolari()->whereKeyNot($record->getKey())->get()
                ->each(fn (EgitimDersi $d) => $d->update($yeni));
        }

        $record->update($yeni);

        return reset($yeni);
    }

    /** Sıra boşsa seçilen konu başlığının sonuna ekler. */
    private function siraBelirle(array $data): array
    {
        if (blank($data['sira'] ?? null)) {
            $data['sira'] = (int) $this->getOwnerRecord()->tumDersler()->where('modul', $data['modul'] ?? null)->max('sira') + 1;
        }

        return $data;
    }
}
