<?php

namespace App\Filament\Resources\RiskProsedurs;

use App\Filament\Resources\RiskProsedurs\Pages\ListRiskProsedurs;
use App\Models\RiskProsedur;
use App\Support\ExcelBellek;
use App\Support\RiskProsedurDocxOkuyucu;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Throwable;
use UnitEnum;

/**
 * Risk Analizi Prosedürü kütüphanesi — Risk Değerlendirmesi PDF'inde
 * Kapak'tan sonra, Form'dan önce basılan yöntem prosedürü. Kullanıcının
 * paylaştığı gerçek "RİSK ANALİZİ" referans belgelerinden alınan metinle
 * başlar (RiskProsedur::varsayilanlariSeedEt); "Prosedür Yükle" ile kendi
 * .docx'ini yükleyip bir yöntemin prosedürünü değiştirebilir.
 */
class RiskProsedurResource extends Resource
{
    protected static ?string $model = RiskProsedur::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Risk Analizi Prosedürü';

    protected static ?string $pluralModelLabel = 'Risk Analizi Prosedürleri';

    protected static ?string $navigationLabel = 'Risk Prosedürleri';

    protected static ?string $recordTitleAttribute = 'ad';

    protected static ?string $slug = 'risk-prosedurleri';

    public static function getEloquentQuery(): Builder
    {
        RiskProsedur::varsayilanlariSeedEt(Filament::auth()->id());

        return parent::getEloquentQuery()->where('user_id', Filament::auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('yontem')->label('Yöntem')
                    ->formatStateUsing(fn ($state) => config('isg.risk_yontemleri.'.$state, $state))
                    ->badge(),
                TextColumn::make('ad')->label('Prosedür')->weight('bold'),
                TextColumn::make('dosya_adi')->label('Kaynak dosya')->default('—')->toggleable(),
                TextColumn::make('updated_at')->label('Güncelleme')->since()->sortable(),
            ])
            ->headerActions([
                Action::make('yukle')
                    ->label('Prosedür Yükle')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Risk Analizi Prosedürü Yükle')
                    ->modalDescription('Yüklediğiniz .docx içeriği paragraf/başlık olarak ayrıştırılıp seçtiğiniz yöntemin prosedürünün yerine geçer.')
                    ->modalSubmitActionLabel('Yükle')
                    ->schema([
                        Select::make('yontem')->label('Yöntem')
                            ->options(config('isg.risk_yontemleri'))
                            ->native(false)->required(),
                        FileUpload::make('dosya')->label('Prosedür (.docx)')
                            ->disk('local')->directory('risk-prosedur-yukleme')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        ExcelBellek::artir();
                        $yol = Storage::disk('local')->path($data['dosya']);

                        try {
                            $bloklar = RiskProsedurDocxOkuyucu::oku($yol);
                        } catch (Throwable $e) {
                            Storage::disk('local')->delete($data['dosya']);
                            Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                            return;
                        }

                        if (empty($bloklar)) {
                            Storage::disk('local')->delete($data['dosya']);
                            Notification::make()->title('Belgede metin bulunamadı')->danger()->send();

                            return;
                        }

                        RiskProsedur::updateOrCreate(
                            ['user_id' => Filament::auth()->id(), 'yontem' => $data['yontem']],
                            [
                                'ad' => 'Risk Analizi Prosedürü ('.config('isg.risk_yontemleri.'.$data['yontem']).')',
                                'icerik' => $bloklar,
                                'dosya_adi' => basename($data['dosya']),
                            ]
                        );

                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Prosedür yüklendi')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('goster')
                    ->label('İçeriği Görüntüle')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (RiskProsedur $record) => $record->ad)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->modalContent(fn (RiskProsedur $record) => new HtmlString(
                        collect($record->icerik)
                            ->map(fn (array $b) => $b['tip'] === 'baslik'
                                ? '<div style="font-weight:700;margin-top:.8rem">'.e($b['metin']).'</div>'
                                : '<div style="font-size:.85rem;white-space:pre-line;margin-top:.2rem">'.e($b['metin']).'</div>')
                            ->implode('')
                    )),
                DeleteAction::make()
                    ->modalDescription('Bu prosedür silinir ve yöntem prosedürsüz kalır (varsayılan metin otomatik geri gelmez) — Risk Değerlendirmesi PDF\'inde bu yöntem için prosedür sayfası basılmaz. Yeni bir .docx yükleyerek yeniden ekleyebilirsiniz.'),
            ])
            ->defaultSort('yontem');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiskProsedurs::route('/'),
        ];
    }
}
