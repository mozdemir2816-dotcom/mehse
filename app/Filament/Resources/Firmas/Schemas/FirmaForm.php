<?php

namespace App\Filament\Resources\Firmas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FirmaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Künye')
                ->columns(2)
                ->schema([
                    TextInput::make('unvan')->label('Ticari unvan')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('kisa_ad')->label('Kısa ad')->maxLength(255),
                    Select::make('tehlike_sinifi')->label('Tehlike sınıfı')
                        ->options(config('isg.tehlike_siniflari'))->default('az_tehlikeli')->required(),
                    TextInput::make('sgk_sicil_no')->label('SGK sicil no')->maxLength(50),
                    TextInput::make('vergi_no')->label('Vergi no')->maxLength(20),
                    TextInput::make('katip_no')->label('İSG-KATİP işyeri no')->maxLength(50),
                    TextInput::make('calisan_sayisi')->label('Çalışan sayısı')->numeric()->minValue(0)->default(0),
                    TextInput::make('nace_kodu')->label('NACE kodu')->maxLength(20),
                    TextInput::make('nace_aciklama')->label('NACE açıklaması')->maxLength(255)->columnSpanFull(),
                ]),

            Section::make('İletişim & Adres')
                ->columns(2)
                ->schema([
                    TextInput::make('isveren_ad')->label('İşveren')->maxLength(255),
                    TextInput::make('isveren_vekili')->label('İşveren vekili')->maxLength(255),
                    TextInput::make('telefon')->label('Telefon')->tel()->maxLength(30),
                    TextInput::make('eposta')->label('E-posta')->email()->maxLength(255),
                    TextInput::make('il')->label('İl')->maxLength(50),
                    TextInput::make('ilce')->label('İlçe')->maxLength(50),
                    Textarea::make('adres')->label('Adres')->rows(2)->columnSpanFull(),
                ]),

            Section::make('Sözleşme & Diğer')
                ->columns(2)
                ->schema([
                    DatePicker::make('sozlesme_baslangic')->label('Sözleşme başlangıcı')->native(false)->displayFormat('d.m.Y'),
                    DatePicker::make('sozlesme_bitis')->label('Sözleşme bitişi')->native(false)->displayFormat('d.m.Y'),
                    FileUpload::make('logo')->label('Firma logosu')->image()
                        ->disk('public')->directory('firma-logo')->imageEditor(),
                    Toggle::make('aktif')->label('Aktif')->default(true),
                    Textarea::make('notlar')->label('Notlar')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }
}
