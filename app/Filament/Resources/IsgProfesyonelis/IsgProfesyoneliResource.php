<?php

namespace App\Filament\Resources\IsgProfesyonelis;

use App\Filament\Resources\IsgProfesyonelis\Pages\CreateIsgProfesyoneli;
use App\Filament\Resources\IsgProfesyonelis\Pages\EditIsgProfesyoneli;
use App\Filament\Resources\IsgProfesyonelis\Pages\ListIsgProfesyonelis;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * İSG Profesyonelleri — İş Güvenliği Uzmanı / İşyeri Hekimi / DSP kayıtları.
 * Firmalara atanınca kaşe/imza görseli belgelerde (Atama Yazıları vb.) otomatik basılır.
 */
class IsgProfesyoneliResource extends Resource
{
    protected static ?string $model = IsgProfesyoneli::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'İSG Profesyoneli';

    protected static ?string $pluralModelLabel = 'İSG Profesyonelleri';

    protected static ?string $navigationLabel = 'İSG Profesyonelleri';

    protected static ?string $recordTitleAttribute = 'ad_soyad';

    protected static ?string $slug = 'isg-profesyonelleri';

    /** Uzman yalnızca kendi profesyonellerini görür. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Filament::auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('tip')->label('Tip')
                    ->options(config('isg.isg_profesyonelleri.tipler'))
                    ->required()->native(false),
                TextInput::make('ad_soyad')->label('Ad Soyad')->required()->maxLength(255),
                TextInput::make('unvan')->label('Unvan / Sınıf')->maxLength(255)
                    ->helperText('Örn: A Sınıfı İş Güvenliği Uzmanı, İşyeri Hekimi'),
                TextInput::make('sertifika_no')->label('Sertifika / Diploma No')->maxLength(100),
                TextInput::make('telefon')->label('Telefon')->tel()->maxLength(30),
                Toggle::make('aktif')->label('Aktif')->default(true),
                FileUpload::make('kase_gorseli')->label('Kaşe görseli')
                    ->image()->disk('public')->directory('isg-profesyonel-kase')->imageEditor(),
                FileUpload::make('imza_gorseli')->label('İmza görseli')
                    ->image()->disk('public')->directory('isg-profesyonel-imza')->imageEditor(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad_soyad')->label('Ad Soyad')->searchable()->weight('bold'),
                TextColumn::make('tip')->label('Tip')->badge()
                    ->formatStateUsing(fn (IsgProfesyoneli $r) => $r->tipEtiketi()),
                TextColumn::make('unvan')->label('Unvan')->toggleable(),
                TextColumn::make('sertifika_no')->label('Sertifika No')->toggleable(),
                IconColumn::make('kase_var')->label('Kaşe')->boolean()
                    ->state(fn (IsgProfesyoneli $r) => filled($r->kase_gorseli)),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                Action::make('firmalaraAta')
                    ->label('Firmalara Ata')
                    ->icon('heroicon-o-building-office-2')
                    ->color('gray')
                    ->modalHeading(fn (IsgProfesyoneli $record) => $record->ad_soyad.' — Firmalara Toplu Ata')
                    ->modalDescription('Seçilen firmaların ilgili alanına bu kişi atanır; işaretini kaldırdığınız firmalardan atama silinir.')
                    ->modalSubmitActionLabel('Ata')
                    ->schema(fn (IsgProfesyoneli $record) => [
                        Select::make('firma_idler')
                            ->label('Firmalar')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn () => Firma::query()
                                ->where('user_id', Filament::auth()->id())
                                ->orderBy('unvan')
                                ->pluck('unvan', 'id'))
                            ->helperText($record->firmaAlani()
                                ? null
                                : 'Bu tip için atama alanı tanımlı değil.'),
                    ])
                    ->fillForm(fn (IsgProfesyoneli $record): array => [
                        'firma_idler' => $record->firmaAlani()
                            ? Firma::query()->where('user_id', Filament::auth()->id())
                                ->where($record->firmaAlani(), $record->id)->pluck('id')->all()
                            : [],
                    ])
                    ->action(function (IsgProfesyoneli $record, array $data): void {
                        $alan = $record->firmaAlani();

                        if (! $alan) {
                            Notification::make()->title('Bu tip için atama yapılamıyor')->danger()->send();

                            return;
                        }

                        $userId = Filament::auth()->id();
                        $secilenler = $data['firma_idler'] ?? [];

                        Firma::query()->where('user_id', $userId)->where($alan, $record->id)
                            ->whereNotIn('id', $secilenler)->update([$alan => null]);

                        Firma::query()->where('user_id', $userId)->whereIn('id', $secilenler)
                            ->update([$alan => $record->id]);

                        Notification::make()->title(count($secilenler).' firmaya atandı')->success()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('ad_soyad');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIsgProfesyonelis::route('/'),
            'create' => CreateIsgProfesyoneli::route('/create'),
            'edit' => EditIsgProfesyoneli::route('/{record}/edit'),
        ];
    }
}
