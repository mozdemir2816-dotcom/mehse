<?php

namespace App\Filament\Resources\EgitimPaketis;

use App\Filament\Resources\EgitimPaketis\Pages\CreateEgitimPaketi;
use App\Filament\Resources\EgitimPaketis\Pages\EditEgitimPaketi;
use App\Filament\Resources\EgitimPaketis\Pages\ListEgitimPaketis;
use App\Models\EgitimPaketi;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Uzaktan Eğitim Paketi — video dersleri + final sınav havuzu. Paket "Uzaktan
 * Eğitim Atama" sayfasından çalışanlara atanır.
 */
class EgitimPaketiResource extends Resource
{
    protected static ?string $model = EgitimPaketi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 31;

    protected static ?string $modelLabel = 'Eğitim Paketi';

    protected static ?string $pluralModelLabel = 'Eğitim Paketleri';

    protected static ?string $navigationLabel = 'Uzaktan Eğitim Paketleri';

    protected static ?string $recordTitleAttribute = 'ad';

    protected static ?string $slug = 'uzaktan-egitim-paketleri';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->gorunur(Filament::auth()->id());
    }

    public static function canEdit(Model $record): bool
    {
        return $record->user_id === Filament::auth()->id();
    }

    public static function canDelete(Model $record): bool
    {
        return $record->user_id === Filament::auth()->id();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('ad')->label('Eğitim adı')->required()->maxLength(160)
                    ->placeholder('İnşaat Sektörü Uzaktan İSG Eğitimi'),
                Select::make('sektor')->label('Sektör')
                    ->options(collect(config('isg.risk_ai.sektorler'))->map(fn ($s) => $s['ad']))
                    ->native(false)->searchable(),
                Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull(),
                TextInput::make('gecme_puani')->label('Geçme puanı (%)')->numeric()->default(70)->minValue(0)->maxValue(100),
                TextInput::make('video_zorunlu_yuzde')->label('Videonun izlenmesi gereken oran (%)')
                    ->numeric()->default(90)->minValue(0)->maxValue(100)
                    ->helperText('Ders bu orana ulaşınca "izlendi" sayılır.'),
                TextInput::make('sinav_soru_sayisi')->label('Sınav soru sayısı')->numeric()->default(20)->minValue(5)->maxValue(50),
                Toggle::make('aktif')->label('Aktif')->default(true),
                Toggle::make('paylasildi')->label('Diğer uzmanlar da atayabilsin'),
            ]),

            Section::make('Video Dersleri')
                ->description('Çalışan bu videoları portalda SIRAYLA izler. YouTube veya Vimeo (gizli) linki yapıştırın.')
                ->schema([
                    Repeater::make('dersler')
                        ->relationship()
                        ->label('')
                        ->schema([
                            TextInput::make('baslik')->label('Ders başlığı')->required()->columnSpan(2),
                            TextInput::make('video_url')->label('Video linki (YouTube / Vimeo)')->required()->url()->columnSpan(2),
                            TextInput::make('sure_sn')->label('Süre (saniye)')->numeric()->helperText('Opsiyonel'),
                            Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->orderColumn('sira')
                        ->itemLabel(fn (array $state): ?string => $state['baslik'] ?? null)
                        ->addActionLabel('Ders Ekle')
                        ->collapsible()
                        ->reorderableWithButtons()
                        ->defaultItems(0),
                ]),

            Section::make('Final Sınav Soruları')
                ->description('Sınavda bu havuzdan rastgele soru seçilir. En az 5 soru gerekir.')
                ->schema([
                    Repeater::make('sorular')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Textarea::make('soru')->label('Soru metni')->rows(2)->required()->columnSpanFull(),
                            Repeater::make('secenekler')
                                ->label('Şıklar')
                                ->simple(TextInput::make('secenek')->required())
                                ->minItems(2)->maxItems(5)->defaultItems(4)
                                ->columnSpanFull(),
                            Radio::make('dogru_index')->label('Doğru şık (sıra no, 0\'dan başlar)')
                                ->options(fn () => [0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E'])
                                ->inline()->required()->default(0),
                            Textarea::make('aciklama')->label('Doğru cevabın gerekçesi (opsiyonel)')->rows(2)->columnSpanFull(),
                        ])
                        ->itemLabel(fn (array $state): ?string => Str::limit($state['soru'] ?? '', 60))
                        ->addActionLabel('Soru Ekle')
                        ->collapsible()
                        ->collapsed()
                        ->defaultItems(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kod')->label('Kod')->badge()->color('gray'),
                TextColumn::make('ad')->label('Eğitim')->searchable()->weight('bold')->wrap(),
                TextColumn::make('sektor')->label('Sektör')
                    ->formatStateUsing(fn (EgitimPaketi $r) => $r->sektorEtiketi() ?: '—'),
                TextColumn::make('dersler_count')->counts('dersler')->label('Ders')->alignCenter(),
                TextColumn::make('sorular_count')->counts('sorular')->label('Soru')->alignCenter(),
                TextColumn::make('atamalar_count')->counts('atamalar')->label('Atama')->alignCenter(),
                IconColumn::make('paylasildi')->label('Paylaşık')->boolean(),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEgitimPaketis::route('/'),
            'create' => CreateEgitimPaketi::route('/create'),
            'edit' => EditEgitimPaketi::route('/{record}/edit'),
        ];
    }
}
