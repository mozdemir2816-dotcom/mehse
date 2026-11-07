<?php

namespace App\Filament\Resources\EgitimPaketis;

use App\Filament\Resources\EgitimPaketis\Pages\CreateEgitimPaketi;
use App\Filament\Resources\EgitimPaketis\Pages\EditEgitimPaketi;
use App\Filament\Resources\EgitimPaketis\Pages\ListEgitimPaketis;
use App\Filament\Resources\EgitimPaketis\RelationManagers\DerslerRelationManager;
use App\Models\EgitimPaketi;
use BackedEnum;
use Closure;
use App\Models\EgitimDersi;
use Filament\Actions\ActionGroup;
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

use App\Filament\Concerns\SinirliErisim;
/**
 * Uzaktan Eğitim Paketi — video dersleri + final sınav havuzu. Paket "Uzaktan
 * Eğitim Atama" sayfasından çalışanlara atanır.
 */
class EgitimPaketiResource extends Resource
{
    use \App\Filament\Concerns\AileAnaSayfasi;

    use \App\Filament\Concerns\SinirliErisim;

    protected static ?string $model = EgitimPaketi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    /** Menüde gizli — Eğitim Yönetim Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Eğitim Paketi';

    protected static ?string $pluralModelLabel = 'Eğitim Paketleri';

    protected static ?string $navigationLabel = 'Uzaktan Eğitim';

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
                TextInput::make('gecme_puani')->label('Geçme puanı (100 üzerinden)')->numeric()->default(60)->minValue(60)->maxValue(100)->helperText('Yönetmelik (02.04.2026) Md.16/3: en az 60.'),
                TextInput::make('video_zorunlu_yuzde')->label('Videonun izlenmesi gereken oran (%)')
                    ->numeric()->default(90)->minValue(0)->maxValue(100)
                    ->helperText('Ders bu orana ulaşınca "izlendi" sayılır.'),
                TextInput::make('sinav_soru_sayisi')->label('Sınav soru sayısı')->numeric()->default(20)->minValue(5)->maxValue(50),
                Toggle::make('aktif')->label('Aktif')->default(true),
                Toggle::make('paylasildi')->label('Diğer uzmanlar da atayabilsin'),
            ]),

            ...self::modulBolumleri(),

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

    /**
     * Yönetmelik Ek-1'in 4 ana konu grubu (modül) için ayrı ders bölümleri + eski (modülsüz) dersler.
     * Her modülde dersler 1, 2, 3 … diye numaralanır; sıra numarası kayıtlıdır.
     *
     * @return array<int, Section>
     */
    public static function modulBolumleri(): array
    {
        $aciklamalar = [
            1 => 'Çalışma mevzuatı, yasal hak ve sorumluluklar, işyeri temizliği ve düzeni, iş kazası/meslek hastalığının hukuki sonuçları.',
            2 => 'Meslek hastalıkları, korunma, biyolojik/psikososyal etmenler, ilk yardım, bağımlılık.',
            3 => 'Risk etmenleri, yangın, iş ekipmanları, elektrik, işaretler, KKD, acil durum ve tahliye.',
            4 => 'Az tehlikelide online verilebilir. Tehlikeli ve Çok Tehlikeli sınıfta bu modül YÜZ YÜZE verilir; online video eklemeyin.',
        ];

        $bolumler = [];

        foreach (EgitimDersi::MODULLER as $n => $ad) {
            $bolumler[] = Section::make("Modül {$n}: {$ad}".($n === 4 ? ' (Tehlikeli/Çok Tehlikeli: Yüz Yüze)' : ''))
                ->description($aciklamalar[$n].' YouTube önerilir (liste dışı) — izleme süresi ölçülür, ileri sarma engellenir.')
                ->collapsible()
                // Yeni pakette burada eklenir; düzenlemede videolar "Videolar" sekmesinden anında kaydedilir.
                // 4. başlık şimdilik kapalı.
                ->visible(fn (string $operation) => $operation === 'create' && $n < 4)
                ->schema([
                    self::dersTekrarlayici("modul{$n}_dersler", fn ($query) => $query->where('modul', $n), $n),
                ]);
        }

        $bolumler[] = Section::make('Modülsüz dersler (eski paketler)')
            ->description('Modül atanmadan eklenmiş dersler. Yeni videoları yukarıdaki modüllere ekleyin.')
            ->collapsible()->collapsed()
            ->visible(false)
            ->schema([
                self::dersTekrarlayici('eski_dersler', fn ($query) => $query->whereNull('modul'), null),
            ]);

        return $bolumler;
    }

    private static function dersTekrarlayici(string $ad, Closure $sorgu, ?int $modul): Repeater
    {
        return Repeater::make($ad)
            ->relationship('dersler', $sorgu)
            ->label('')
            ->schema([
                TextInput::make('baslik')->label('Ders başlığı')->required()->columnSpan(2),
                TextInput::make('video_url')->label('Video linki (YouTube / Vimeo)')->required()->url()->columnSpan(2),
                TextInput::make('sure_sn')->label('Süre (saniye)')->numeric()->helperText('Boşsa YouTube videosu ilk izlendiğinde otomatik dolar.'),
                Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull(),
            ])
            ->columns(2)
            ->orderColumn('sira')
            ->itemNumbers()
            ->itemLabel(fn (array $state): ?string => $state['baslik'] ?? null)
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => [...$data, 'modul' => $modul])
            ->addActionLabel('Ders Ekle')
            ->collapsible()
            ->reorderableWithButtons()
            ->defaultItems(0);
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
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            DerslerRelationManager::class,
        ];
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
