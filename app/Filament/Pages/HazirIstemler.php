<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Support\HazirIstemler as Istemler;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Hazır İstemler (13.11.2026) — yalnız sahip hesap. Gemini sohbetine yapıştırılacak
 * istemi firma bilgisiyle doldurur; çıktı sistemin mevcut Excel / JSON içe aktarma
 * biçimindedir (App\Support\HazirIstemler). API kotası dolduğunda ya da elle
 * çalışmak istendiğinde kullanılır; "Gemini'de elle yap" penceresinin tamamlayıcısı.
 */
class HazirIstemler extends Page
{
    protected string $view = 'filament.pages.hazir-istemler';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar ve Kütüphaneler';

    /** Menüde gizli — Sistem & Ayarlar Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hazir-istemler';

    protected static ?string $title = 'Hazır İstemler';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->sahipMi();
    }

    public function mount(): void
    {
        $this->form->fill(['tur' => 'risk_analizi', 'yontem' => 'fine_kinney', 'parca' => 0, 'adet' => Istemler::TURLER['risk_analizi']['adet']]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                ToggleButtons::make('tur')
                    ->label('Ne hazırlanacak?')
                    ->options(array_map(fn ($t) => $t['ad'], Istemler::TURLER))
                    ->inline()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, $set) => $set('adet', Istemler::TURLER[$state]['adet'] ?? 20)),
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                    Select::make('firma_id')
                        ->label(fn (Get $get) => ($get('tur') === 'risk_analizi') ? 'Firma' : 'Firma (isteğe bağlı)')
                        ->helperText(fn (Get $get) => $get('tur') === 'risk_analizi'
                            ? 'Unvan, NACE, tehlike sınıfı, çalışan sayısı ve iş kalemleri isteme eklenir.'
                            : 'Seçilirse firmanın sektör bilgisi isteme eklenir.')
                        ->options(fn () => Firma::query()->orderBy('unvan')->pluck('unvan', 'id')->all())
                        ->searchable()
                        ->live(),
                    ToggleButtons::make('yontem')
                        ->label('Yöntem')
                        ->options(['fine_kinney' => 'Fine-Kinney', 'matris_5x5' => '5×5 Matris'])
                        ->inline()
                        ->visible(fn (Get $get) => $get('tur') === 'risk_analizi')
                        ->live(),
                    TextInput::make('kategori')
                        ->label('Kategori (kütüphane klasörü)')
                        ->placeholder('ör. Matbaa – Ofset baskı')
                        ->helperText('Boş bırakılırsa firmanın NACE açıklaması kullanılır.')
                        ->maxLength(150)
                        ->visible(fn (Get $get) => $get('tur') === 'tehlike_kutuphanesi')
                        ->live(onBlur: true),
                    Select::make('konu')
                        ->label('Konu')
                        ->placeholder('Karışık konular')
                        ->options(config('isg.soru_bankasi.konular', []))
                        ->visible(fn (Get $get) => $get('tur') === 'soru_bankasi')
                        ->live(),
                    ToggleButtons::make('parca')
                        ->label('Gemini cevabı')
                        ->options([0 => 'Tek seferde', 1 => 'Parça parça'])
                        ->helperText('Tablo yarıda kesilirse "Parça parça" seçin.')
                        ->inline()
                        ->visible(fn (Get $get) => in_array($get('tur'), ['risk_analizi', 'tehlike_kutuphanesi'], true))
                        ->live(),
                    TextInput::make('adet')
                        ->label(fn (Get $get) => match ($get('tur')) {
                            'talimat' => 'Talimat sayısı',
                            'soru_bankasi' => 'Soru sayısı',
                            default => 'Madde sayısı',
                        })
                        ->numeric()->minValue(1)->maxValue(200)
                        ->live(onBlur: true),
                ]),
                Textarea::make('ek')
                    ->label('Ek bilgi (isteğe bağlı)')
                    ->placeholder(fn (Get $get) => match ($get('tur')) {
                        'talimat' => 'ör. Hazırlanacak talimatlar: hidrolik pres, şerit testere, akülü istif makinesi',
                        'soru_bankasi' => 'ör. Sorular gıda üretiminde çalışan işçiler için; okuma düzeyi düşük, kısa cümleler',
                        default => 'ör. Bölümler: kesimhane, dikimhane, ütü-paket, depo. Makineler: overlok, buhar kazanı, kompresör.',
                    })
                    ->rows(3)
                    ->maxLength(2000)
                    ->live(onBlur: true),
            ]);
    }

    /** Ekranda gösterilen hazır istem (form her değiştiğinde yeniden oluşur). */
    public function getIstemProperty(): string
    {
        $d = $this->data ?? [];
        $tur = (string) ($d['tur'] ?? 'risk_analizi');

        if (! isset(Istemler::TURLER[$tur])) {
            return '';
        }

        $firma = filled($d['firma_id'] ?? null) ? Firma::query()->find($d['firma_id']) : null;

        return Istemler::istem($tur, $firma, $d);
    }

    public function getFirmaEksikProperty(): bool
    {
        return ($this->data['tur'] ?? null) === 'risk_analizi' && blank($this->data['firma_id'] ?? null) && blank($this->data['ek'] ?? null);
    }
}
