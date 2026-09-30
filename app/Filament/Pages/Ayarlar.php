<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\KullaniciAyarlari;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Kullanıcı ayarları: görünüm (tema/yoğunluk/yazı boyutu), uyarı eşikleri ve
 * Kontrol Merkezi'nde takip edilecek kriterler. Hepsi users.ayarlar JSON'unda,
 * okuma/varsayılanlar App\Support\KullaniciAyarlari'da.
 */
class Ayarlar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'ayarlar';

    protected static ?string $title = 'Ayarlar';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $ayar = KullaniciAyarlari::hepsi($this->kullanici());
        $tumKriterler = array_column(config('isg.kontrol_merkezi.kriterler', []), 'anahtar');

        $this->form->fill([
            'tema' => $ayar['tema'],
            'yogunluk' => $ayar['yogunluk'],
            'yazi_boyutu' => $ayar['yazi_boyutu'],
            'esikler' => $ayar['esikler'],
            'kontrol_takip' => array_values(array_diff($tumKriterler, (array) $ayar['kontrol_haric'])),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Görünüm')
                    ->description('Panelin görünümü. Hesabınıza kaydedilir, her cihazda aynı gelir.')
                    ->icon('heroicon-o-swatch')
                    ->columns(3)
                    ->schema([
                        ToggleButtons::make('tema')
                            ->label('Tema')
                            ->options(KullaniciAyarlari::TEMALAR)
                            ->icons([
                                'klasik' => 'heroicon-o-building-office-2',
                                'material' => 'heroicon-o-squares-2x2',
                                'windows11' => 'heroicon-o-window',
                                'saha' => 'heroicon-o-wrench-screwdriver',
                            ])
                            ->inline()
                            ->required()
                            ->columnSpanFull(),
                        ToggleButtons::make('yogunluk')
                            ->label('Yoğunluk')
                            ->helperText('Kompakt: tablolarda ve menüde daha çok satır sığar.')
                            ->options(KullaniciAyarlari::YOGUNLUKLAR)
                            ->icons(['rahat' => 'heroicon-o-bars-3', 'kompakt' => 'heroicon-o-bars-4'])
                            ->inline()
                            ->required(),
                        ToggleButtons::make('yazi_boyutu')
                            ->label('Yazı boyutu')
                            ->options(KullaniciAyarlari::YAZI_BOYUTLARI)
                            ->inline()
                            ->required(),
                    ]),

                Section::make('Uyarı eşikleri')
                    ->description('Süresi dolmak üzere olan kayıtlar kaç gün kala "yaklaşıyor" olarak işaretlensin. 1–365 gün.')
                    ->icon('heroicon-o-bell-alert')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])
                            ->schema(array_map(
                                fn (string $anahtar, array $tanim): TextInput => TextInput::make("esikler.{$anahtar}")
                                    ->label($tanim[0])
                                    ->helperText($tanim[1])
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(365)
                                    ->suffix('gün')
                                    ->required(),
                                array_keys(KullaniciAyarlari::ESIKLER),
                                KullaniciAyarlari::ESIKLER,
                            )),
                    ]),

                Section::make('Kontrol Merkezi kriterleri')
                    ->description('İşaretli kriterler Kontrol Merkezi, Firma Takip ve Excel panosunda sayılır. Takip etmediğiniz kriterlerin işaretini kaldırın; tamamlanma yüzdeleri buna göre hesaplanır.')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->collapsible()
                    ->schema([
                        CheckboxList::make('kontrol_takip')
                            ->hiddenLabel()
                            ->options(fn (): array => collect(config('isg.kontrol_merkezi.kriterler', []))
                                ->mapWithKeys(fn (array $k): array => [$k['anahtar'] => $k['ad']])
                                ->all())
                            ->descriptions(fn (): array => collect(config('isg.kontrol_merkezi.kriterler', []))
                                ->mapWithKeys(fn (array $k): array => [$k['anahtar'] => $k['kategori'] ?? ''])
                                ->all())
                            ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                            ->searchable()
                            ->bulkToggleable()
                            ->minItems(1)
                            ->validationMessages(['min' => 'En az bir kriter takip edilmeli.']),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('kaydet')
                ->footer([
                    Actions::make([
                        Action::make('kaydet')
                            ->label('Ayarları Kaydet')
                            ->icon('heroicon-o-check')
                            ->submit('kaydet')
                            ->keyBindings(['mod+s']),
                    ])->sticky()->key('form-actions'),
                ]),
        ]);
    }

    public function kaydet(): void
    {
        $veri = $this->form->getState();
        $tumKriterler = array_column(config('isg.kontrol_merkezi.kriterler', []), 'anahtar');

        KullaniciAyarlari::kaydet($this->kullanici(), [
            'tema' => $veri['tema'],
            'yogunluk' => $veri['yogunluk'],
            'yazi_boyutu' => $veri['yazi_boyutu'],
            'esikler' => array_map('intval', $veri['esikler']),
            'kontrol_haric' => array_values(array_diff($tumKriterler, $veri['kontrol_takip'] ?? [])),
        ]);

        Notification::make()->title('Ayarlar kaydedildi')->success()->send();

        // Görünüm ayarları <html> attribute'larıyla ilk boyamada uygulanıyor
        // (AdminPanelProvider HEAD_START) — yeni değerlerin gelmesi için yenile.
        $this->redirect(static::getUrl(), navigate: false);
    }

    private function kullanici(): User
    {
        /** @var User */
        return Filament::auth()->user();
    }
}
