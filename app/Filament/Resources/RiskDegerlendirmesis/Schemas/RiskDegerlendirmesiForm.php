<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Schemas;

use App\Models\Firma;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                    Select::make('yontem')->label('Yöntem')
                        ->options(config('isg.risk_yontemleri'))->default('matris_5x5')->required()
                        ->helperText('5×5 Matris varsayılandır. Yöntem değişince madde puanları yeniden hesaplanmalıdır.'),
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
                    Textarea::make('kapsam_notu')->label('Kapsam notu')->rows(2),
                    Textarea::make('revizyon_nedeni')->label('Revizyon nedeni')->rows(2),
                ]),

            Section::make('Risk Değerlendirme Ekibi')
                ->collapsed()
                ->schema([
                    Repeater::make('ekip')->hiddenLabel()
                        ->addActionLabel('Kişi ekle')
                        ->columns(2)
                        ->schema([
                            TextInput::make('ad')->label('Adı Soyadı')->required(),
                            TextInput::make('unvan')->label('Unvanı / Görevi')
                                ->placeholder('İşveren / İGU / Çalışan temsilcisi / Destek elemanı'),
                        ]),
                ]),
        ]);
    }
}
