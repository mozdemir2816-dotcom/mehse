<?php

namespace App\Filament\Resources\Calisans\Schemas;

use App\Models\Firma;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalisanForm
{
    /**
     * @param  bool  $firmaSecimi  Standalone kaynakta true; relation manager'da false
     *                             (firma ilişkiden gelir).
     */
    public static function configure(Schema $schema, bool $firmaSecimi = true): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    ...($firmaSecimi ? [
                        Select::make('firma_id')->label('Firma')
                            ->options(fn () => Firma::query()
                                ->where('user_id', Filament::auth()->id())
                                ->orderBy('unvan')->pluck('unvan', 'id'))
                            ->searchable()->preload()->required(),
                    ] : []),
                    TextInput::make('ad_soyad')->label('Ad soyad')->required()->maxLength(255),
                    TextInput::make('tc')->label('T.C. Kimlik No')->maxLength(11)->rule('digits:11')->nullable(),
                    TextInput::make('gorev')->label('Görevi')->maxLength(255),
                    TextInput::make('departman')->label('Departman')->maxLength(255),
                    DatePicker::make('ise_giris')->label('İşe giriş')->native(false)->displayFormat('d.m.Y'),
                    DatePicker::make('isten_cikis')->label('İşten çıkış')->native(false)->displayFormat('d.m.Y'),
                    DatePicker::make('dogum_tarihi')->label('Doğum tarihi')->native(false)->displayFormat('d.m.Y'),
                    Select::make('kan_grubu')->label('Kan grubu')
                        ->options(collect(['0 Rh+', '0 Rh-', 'A Rh+', 'A Rh-', 'B Rh+', 'B Rh-', 'AB Rh+', 'AB Rh-'])
                            ->mapWithKeys(fn ($v) => [$v => $v])),
                    TextInput::make('telefon')->label('Telefon')->tel()->maxLength(30),
                    TextInput::make('eposta')->label('E-posta')->email()->maxLength(255),
                    Toggle::make('agir_tehlikeli_iste')->label('Ağır ve tehlikeli işte çalışıyor'),
                    Toggle::make('aktif')->label('Aktif')->default(true),
                    Textarea::make('notlar')->label('Notlar')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }
}
