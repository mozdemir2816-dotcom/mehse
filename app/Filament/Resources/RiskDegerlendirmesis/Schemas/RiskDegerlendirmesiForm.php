<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Schemas;

use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RiskDegerlendirmesiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Firma & Yöntem')
                ->columns(2)
                ->schema([
                    Select::make('firma_id')->label('Firma')
                        ->options(fn () => Firma::query()
                            ->where('user_id', Filament::auth()->id())
                            ->orderBy('unvan')->pluck('unvan', 'id'))
                        ->searchable()->preload()->required()
                        ->disabledOn('edit'),
                    // Yöntem seçilmeden kayıt yok; kütüphane seçicisi bu yönteme göre süzülür.
                    // Madde eklenmiş değerlendirmede yöntem kilitlenir (puanlar eski yönteme göre).
                    Select::make('yontem')->label('Yöntem')
                        ->options(config('isg.risk_yontemleri'))->placeholder('Yöntem seçin')->required()
                        ->disabled(fn ($record): bool => (bool) $record?->maddeler()->exists())
                        ->helperText(function (Get $get, $record): string {
                            if ($record?->maddeler()->exists()) {
                                return 'Maddeler eklendiği için yöntem değiştirilemez.';
                            }

                            return match ($get('yontem')) {
                                'fine_kinney' => 'Kütüphanede Fine-Kinney kategorileri listelenir (firma NACE grubuna uygun olanlar).',
                                'matris_5x5' => 'Kütüphanede 5×5 matris kategorileri listelenir; kütüphane puanları Fine-Kinney ölçeğinde tutulur.',
                                'hazop' => 'Kütüphanede HAZOP düğümleri listelenir; maddeler puansız eklenir.',
                                default => 'Yöntem seçin; kütüphane bu yönteme göre süzülür.',
                            };
                        }),
                    Select::make('igu_id')->label('Hazırlayan İş Güvenliği Uzmanı')
                        ->options(fn () => IsgProfesyoneli::query()
                            ->where('user_id', Filament::auth()->id())->where('tip', 'igu')
                            ->pluck('ad_soyad', 'id'))
                        ->native(false)->searchable()->preload()
                        ->helperText('Boş bırakılırsa firmanın atanmış İGU\'su kullanılır.'),
                    TextInput::make('belge_no')->label('Belge no')->placeholder('Kaydedince otomatik (RD-…)')
                        ->disabled()->dehydrated(false),
                    TextInput::make('revizyon_no')->label('Revizyon no')->default('00')->maxLength(10),
                    DatePicker::make('rapor_tarihi')->label('Rapor tarihi')->native(false)->displayFormat('d.m.Y')
                        ->default(now()),
                    DatePicker::make('gecerlilik_tarihi')->label('Geçerlilik tarihi')
                        ->native(false)->displayFormat('d.m.Y')
                        ->helperText('Boş bırakılırsa tehlike sınıfına göre otomatik (6 / 4 / 2 yıl).'),
                    Select::make('durum')->label('Durum')
                        ->options(['taslak' => 'Taslak', 'yayinlandi' => 'Yayınlandı'])->default('taslak')->required(),
                    TextInput::make('proje_adi')->label('İşyeri / proje adı')->maxLength(191)
                        ->placeholder('Örn. X Şantiyesi ve Kampı')
                        ->helperText('Kapakta yazılır; firmanın ayrı şantiye / işyerleri için.'),
                    Textarea::make('kapsam_notu')->label('Kapsam notu')->rows(2),
                    Textarea::make('revizyon_nedeni')->label('Revizyon nedeni')->rows(2),
                ]),

            Section::make('Risk Değerlendirme Ekibi')
                ->description('Çalışan temsilcisi, destek elemanı ve bilgi sahibi çalışan burada girilmezse firmanın Atama Yazıları\'ndan otomatik alınır ve PDF\'teki imza alanlarına basılır.')
                ->collapsed()
                ->schema([
                    Repeater::make('ekip')->hiddenLabel()
                        ->addActionLabel('Kişi ekle')
                        ->columns(2)
                        ->schema([
                            TextInput::make('ad')->label('Adı Soyadı')->required(),
                            TextInput::make('unvan')->label('Unvanı / Görevi')
                                ->placeholder('İşveren / İGU / Çalışan temsilcisi / Destek elemanı / Bilgi sahibi çalışan'),
                            FileUpload::make('imza_gorseli')->label('İmza (opsiyonel)')
                                ->image()->disk('public')->directory('risk-ekip-imza')->imageEditor()
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
