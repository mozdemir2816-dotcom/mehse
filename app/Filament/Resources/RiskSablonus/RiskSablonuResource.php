<?php

namespace App\Filament\Resources\RiskSablonus;

use App\Filament\Resources\RiskSablonus\Pages\EditRiskSablonu;
use App\Filament\Resources\RiskSablonus\Pages\ListRiskSablonus;
use App\Models\RiskSablonu;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Sektörel risk şablonları — Risk Sihirbazında oluşturulur, burada yönetilir.
 * Create yok; şablon sihirbazın "Sektör şablonu olarak kaydet" akışından doğar.
 */
class RiskSablonuResource extends Resource
{
    protected static ?string $model = RiskSablonu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Risk Yönetimi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Risk Şablonu';

    protected static ?string $pluralModelLabel = 'Risk Şablonları';

    protected static ?string $navigationLabel = 'Sektör Şablonları';

    protected static ?string $recordTitleAttribute = 'ad';

    protected static ?string $slug = 'risk-sablonlari';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->gorunur(Filament::auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('ad')->label('Şablon adı')->required()->maxLength(120),
                Select::make('sektor')->label('Sektör')
                    ->options(collect(config('isg.risk_ai.sektorler'))->map(fn ($s) => $s['ad']))
                    ->native(false)->searchable(),
                TextInput::make('sektor_adi')->label('Sektör (serbest metin)')
                    ->helperText('Sektör listede yoksa buraya yazın'),
                Select::make('yontem')->label('Puanlama yöntemi')
                    ->options(config('isg.risk_yontemleri'))->required(),
                Toggle::make('paylasildi')->label('Diğer uzmanlarla paylaş')
                    ->helperText('Açıksa tüm kullanıcılar bu şablonu görüp uygulayabilir'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')->label('Şablon')->searchable()->weight('bold'),
                TextColumn::make('sektor_etiketi')->label('Sektör')
                    ->state(fn (RiskSablonu $r) => $r->sektorEtiketi())->badge(),
                TextColumn::make('madde_sayisi')->label('Madde')
                    ->state(fn (RiskSablonu $r) => $r->maddeSayisi())->alignCenter(),
                TextColumn::make('yontem')->label('Yöntem')
                    ->formatStateUsing(fn ($s) => config('isg.risk_yontemleri.'.$s, $s)),
                TextColumn::make('kullanim_sayisi')->label('Kullanım')->alignCenter()->sortable(),
                IconColumn::make('paylasildi')->label('Paylaşıldı')->boolean(),
                TextColumn::make('user.name')->label('Sahibi')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Güncelleme')->since()->sortable(),
            ])
            ->groups([
                Group::make('sektor')->label('Sektör')
                    ->getTitleFromRecordUsing(fn (RiskSablonu $r) => $r->sektorEtiketi())
                    ->collapsible(),
            ])
            ->defaultGroup('sektor')
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn (RiskSablonu $r) => $r->user_id === Filament::auth()->id()),
            ])
            ->defaultSort('kullanim_sayisi', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiskSablonus::route('/'),
            'edit' => EditRiskSablonu::route('/{record}/edit'),
        ];
    }
}
