<?php

namespace App\Filament\Resources\Firmas\Schemas;

use App\Models\IsgProfesyoneli;
use App\Support\KatipSozlesmeIceAktarici;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

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
                    TextInput::make('sgk_sicil_no')->label('SGK sicil no')->maxLength(50)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Get $get, Set $set, ?Model $record) => static::katiptenDoldur($state, $get, $set, $record))
                        ->helperText(fn () => KatipSozlesmeIceAktarici::liste((int) Filament::auth()->id())
                            ? 'SGK veya İSG-KATİP no yazın: yüklü KATİP listesinde varsa diğer bilgiler otomatik dolar.'
                            : null),
                    TextInput::make('vergi_no')->label('Vergi no')->maxLength(20),
                    TextInput::make('katip_no')->label('İSG-KATİP işyeri no')->maxLength(50)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Get $get, Set $set, ?Model $record) => static::katiptenDoldur($state, $get, $set, $record)),
                    TextInput::make('calisan_sayisi')->label('Çalışan sayısı')->numeric()->minValue(0)->default(0),
                    TextInput::make('nace_kodu')->label('NACE kodu')->maxLength(20),
                    TextInput::make('nace_aciklama')->label('NACE açıklaması')->maxLength(255)->columnSpanFull(),
                ]),

            Section::make('Yaptığı İşler (İnşaat İş Kalemleri)')
                ->collapsible()
                ->collapsed()
                ->description('Firma inşaat sektöründe faaliyet gösteriyorsa yaptığı işleri seçin — risk analizi, çalışma talimatı, KKD matrisi ve eğitim içerikleri buna göre hazırlanabilir (firma düzenleme sayfasındaki "İş Kalemlerine Göre Evrakları Hazırla" butonu).')
                ->schema([
                    CheckboxList::make('is_kalemleri')
                        ->label('İş kalemleri')
                        ->hiddenLabel()
                        ->options(fn () => collect(config('isg.kkd_matris.is_kalemleri.İnşaat / Şantiye', []))->pluck('ad', 'anahtar'))
                        ->columns(3)
                        ->bulkToggleable(),
                ]),

            Section::make('İletişim & Adres')
                ->columns(2)
                ->schema([
                    TextInput::make('isveren_ad')->label('İşveren')->maxLength(255),
                    TextInput::make('isveren_vekili')->label('İşveren vekili')->maxLength(255),
                    FileUpload::make('isveren_kase_gorseli')->label('İşveren kaşesi (opsiyonel)')
                        ->image()->disk('public')->directory('isveren-kase')->imageEditor(),
                    FileUpload::make('isveren_imza_gorseli')->label('İşveren imzası')
                        ->image()->disk('public')->directory('isveren-imza')->imageEditor(),
                    TextInput::make('telefon')->label('Telefon')->tel()->maxLength(30),
                    TextInput::make('eposta')->label('E-posta')->email()->maxLength(255),
                    TextInput::make('il')->label('İl')->maxLength(50),
                    TextInput::make('ilce')->label('İlçe')->maxLength(50),
                    Textarea::make('adres')->label('Adres')->rows(2)->columnSpanFull(),
                ]),

            Section::make('İSG Profesyonelleri')
                ->columns(3)
                ->description('Atanan kişilerin kaşesi bu firmanın belgelerinde (Atama Yazıları vb.) otomatik basılır.')
                ->schema([
                    Select::make('igu_id')->label('İş Güvenliği Uzmanı')
                        ->options(fn () => IsgProfesyoneli::query()
                            ->where('user_id', Filament::auth()->id())->where('tip', 'igu')
                            ->pluck('ad_soyad', 'id'))
                        ->native(false)->searchable()->preload(),
                    Select::make('isyeri_hekimi_id')->label('İşyeri Hekimi')
                        ->options(fn () => IsgProfesyoneli::query()
                            ->where('user_id', Filament::auth()->id())->where('tip', 'isyeri_hekimi')
                            ->pluck('ad_soyad', 'id'))
                        ->native(false)->searchable()->preload(),
                    Select::make('dsp_id')->label('Diğer Sağlık Personeli (DSP)')
                        ->options(fn () => IsgProfesyoneli::query()
                            ->where('user_id', Filament::auth()->id())->where('tip', 'dsp')
                            ->pluck('ad_soyad', 'id'))
                        ->native(false)->searchable()->preload(),
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

    /**
     * SGK / KATİP no girilince yüklü İSG-KATİP listesinden firma bilgilerini doldurur.
     * Yalnız boş alanlar yazılır, dolu alanlar kullanıcıya aittir. Yeni firmada formun varsayılanları
     * (tehlike sınıfı "az tehlikeli", çalışan 0) henüz girilmemiş sayılır.
     */
    public static function katiptenDoldur(?string $no, Get $get, Set $set, ?Model $record = null): void
    {
        $kayit = KatipSozlesmeIceAktarici::numarayaGoreBul((int) Filament::auth()->id(), $no);

        if (! $kayit) {
            return;
        }

        $varsayilan = $record ? [] : ['calisan_sayisi' => [0, '0'], 'tehlike_sinifi' => ['az_tehlikeli']];
        $dolan = 0;

        foreach (KatipSozlesmeIceAktarici::formVerisi($kayit) as $alan => $deger) {
            $mevcut = $get($alan);

            if (blank($mevcut) || in_array($mevcut, $varsayilan[$alan] ?? [])) {
                $set($alan, $deger);
                $dolan++;
            }
        }

        if ($dolan > 0) {
            Notification::make()->title('İSG-KATİP listesinden dolduruldu')->body($kayit['unvan'])->success()->send();
        }
    }
}
