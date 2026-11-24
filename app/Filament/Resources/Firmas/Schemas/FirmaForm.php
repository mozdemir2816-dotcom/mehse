<?php

namespace App\Filament\Resources\Firmas\Schemas;

use App\Models\IsgProfesyoneli;
use App\Models\NaceKodu;
use App\Support\KatipSozlesmeIceAktarici;
use App\Support\SgkSicilCozumleyici;
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
                    // K1 (23.11.2026): tehlike sınıfı NACE kodundan gelir (İşyeri Tehlike Sınıfları Tebliği).
                    // Varsayılan "Az Tehlikeli" kaldırıldı — en uzun yasal süreleri sessizce seçiyordu.
                    Select::make('tehlike_sinifi')->label('Tehlike sınıfı')
                        ->options(config('isg.tehlike_siniflari'))
                        ->placeholder('NACE kodundan otomatik gelir')
                        ->required()
                        ->live()
                        ->helperText(fn (Get $get) => static::tehlikeSinifiIpucu($get('nace_kodu'), $get('tehlike_sinifi'))),
                    Select::make('risk_cikti_sablonu')->label('Risk çıktı şablonu')
                        ->options(['hastane' => 'Hastane / Sağlık Tesisi Formatı'])
                        ->placeholder('Standart (sistem çıktısı)')
                        ->helperText('Hastane formu seçilirse bu firmanın Fine-Kinney risk değerlendirmeleri Excel/PDF çıktısında hastanenin kendi formatında üretilir.'),
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
                    TextInput::make('katip_aylik_dk')->label('İSG-KATİP aylık süre (dk)')->numeric()->minValue(0)
                        ->helperText('İGU sözleşme süresi; KATİP Excel\'i yüklenince en son dosyadaki değerle güncellenir. Boşsa çalışan sayısı × tehlike sınıfından hesaplanır.'),
                    TextInput::make('nace_kodu')->label('NACE kodu')->maxLength(20)
                        ->placeholder('ör. 41.20.02')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => static::nacedenDoldur($state, $get, $set))
                        ->helperText(fn (Get $get) => static::naceIpucu($get('nace_kodu'), $get('sgk_sicil_no'))),
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
                ->description('Atanan kişilerin kaşesi bu firmanın belgelerinde (Atama Yazıları vb.) otomatik basılır. OSGB kapsamı dışında olan hizmetleri işaretleyerek yanlış eksik uyarılarını engelleyebilirsiniz.')
                ->schema([
                    Select::make('igu_id')->label('İş Güvenliği Uzmanı')
                        ->options(fn () => IsgProfesyoneli::query()
                            ->where('user_id', Filament::auth()->id())->where('tip', 'igu')
                            ->pluck('ad_soyad', 'id'))
                        ->native(false)->searchable()->preload()
                        ->rule(fn (?Model $record) => function (string $attribute, $value, \Closure $fail) use ($record): void {
                            if ($cakisan = \App\Support\OsgbKadroAnalizi::tamSureliCakisanFirma($value ? (int) $value : null, 'igu', $record?->getKey())) {
                                $fail("Bu İGU '{$cakisan->unvan}' firmasında Tam Süreli (%100 kapasite) kilitlidir; başka firmaya atanamaz.");
                            }
                        }),
                    Select::make('isyeri_hekimi_id')->label('İşyeri Hekimi')
                        ->options(fn () => IsgProfesyoneli::query()
                            ->where('user_id', Filament::auth()->id())->where('tip', 'isyeri_hekimi')
                            ->pluck('ad_soyad', 'id'))
                        ->native(false)->searchable()->preload()
                        ->rule(fn (?Model $record) => function (string $attribute, $value, \Closure $fail) use ($record): void {
                            if ($cakisan = \App\Support\OsgbKadroAnalizi::tamSureliCakisanFirma($value ? (int) $value : null, 'isyeri_hekimi', $record?->getKey())) {
                                $fail("Bu İşyeri Hekimi '{$cakisan->unvan}' firmasında Tam Süreli (%100 kapasite) kilitlidir; başka firmaya atanamaz.");
                            }
                        }),
                    Select::make('dsp_id')->label('Diğer Sağlık Personeli (DSP)')
                        ->options(fn () => IsgProfesyoneli::query()
                            ->where('user_id', Filament::auth()->id())->where('tip', 'dsp')
                            ->pluck('ad_soyad', 'id'))
                        ->native(false)->searchable()->preload(),
                    Toggle::make('igu_hizmeti_yok')->label('İGU hizmetini biz vermiyoruz')
                        ->helperText('Uyum skoru ve İPC uyarısından muaf tutulur.'),
                    Toggle::make('hekim_hizmeti_yok')->label('Hekim hizmetini biz vermiyoruz')
                        ->helperText('Uyum skoru ve İPC uyarısından muaf tutulur.'),
                    Toggle::make('dsp_hizmeti_yok')->label('DSP hizmetini biz vermiyoruz')
                        ->helperText('Kadro hesabında hariç tutulur.'),
                    Toggle::make('tam_sureli_igu')->label('Tam süreli İGU (%100 kapasite kilidi)')
                        ->helperText('Atanan İGU bu firmaya %100 ayrılır; başka firmaya atama engellenir.'),
                    Toggle::make('tam_sureli_hekim')->label('Tam süreli Hekim (%100 kapasite kilidi)')
                        ->helperText('Atanan Hekim bu firmaya %100 ayrılır; başka firmaya atama engellenir.'),
                ]),

            Section::make('Sözleşme & Diğer')
                ->columns(2)
                ->schema([
                    DatePicker::make('sozlesme_baslangic')->label('Sözleşme başlangıcı')->native(false)->displayFormat('d.m.Y'),
                    DatePicker::make('sozlesme_bitis')->label('Sözleşme bitişi')->native(false)->displayFormat('d.m.Y')
                        ->afterOrEqual('sozlesme_baslangic'),
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

            // Aylık süre KATİP'in güncel hesabıdır: her zaman en son listedeki değer yazılır.
            if ($alan === 'katip_aylik_dk' || blank($mevcut) || in_array($mevcut, $varsayilan[$alan] ?? [])) {
                $set($alan, $deger);
                $dolan++;
            }
        }

        if ($dolan > 0) {
            Notification::make()->title('İSG-KATİP listesinden dolduruldu')->body($kayit['unvan'])->success()->send();
        }
    }

    /**
     * NACE kodu girilince tebliğ listesindeki tehlike sınıfını ve açıklamayı yazar.
     * Listede olmayan kodda hiçbir şeyi değiştirmez (ipucu uyarı verir).
     */
    public static function nacedenDoldur(?string $kod, Get $get, Set $set): void
    {
        $nace = filled($kod) ? NaceKodu::bul($kod) : null;
        if (! $nace) {
            return;
        }

        $set('nace_kodu', $nace->kod);
        if (blank($get('nace_aciklama'))) {
            $set('nace_aciklama', $nace->tanim);
        }

        $onceki = $get('tehlike_sinifi');
        $set('tehlike_sinifi', $nace->tehlike_sinifi);

        $ad = config('isg.tehlike_siniflari.'.$nace->tehlike_sinifi);
        Notification::make()
            ->title("Tehlike sınıfı: {$ad}")
            ->body($onceki && $onceki !== $nace->tehlike_sinifi
                ? 'Önceki seçim ('.config('isg.tehlike_siniflari.'.$onceki).') NACE koduna göre değiştirildi.'
                : 'NACE koduna göre İşyeri Tehlike Sınıfları Tebliği listesinden alındı.')
            ->success()->send();
    }

    /** NACE alanının altındaki açıklama: listede yoksa uyarı, boşsa SGK sicilinden işkolu ipucu. */
    public static function naceIpucu(?string $kod, ?string $sgkSicil): ?string
    {
        if (filled($kod)) {
            if (NaceKodu::bul($kod)) {
                return null;
            }

            return NaceKodu::normalizeKod($kod)
                ? 'Bu kod tebliğ listesinde (2012 EK-1) bulunamadı; tehlike sınıfını İSG-KATİP\'teki değere göre elle seçin.'
                : 'NACE kodu 6 haneli olmalı (ör. 41.20.02).';
        }

        $cozum = filled($sgkSicil) ? SgkSicilCozumleyici::cozumle($sgkSicil) : null;
        if (! ($cozum['gecerli'] ?? false) || blank($cozum['nace4'])) {
            return 'Kodu girince tehlike sınıfı otomatik dolar.';
        }

        $siniflar = NaceKodu::nace4IleBul($cozum['nace4'])->pluck('tehlike_sinifi')->unique();
        $sinifMetni = $siniflar->count() === 1
            ? ' — bu işkolundaki tüm kodlar '.config('isg.tehlike_siniflari.'.$siniflar->first())
            : '';

        return "SGK sicilindeki işkolu: {$cozum['nace4']}{$sinifMetni}. 6 haneli kodu girin.";
    }

    /** Seçilen tehlike sınıfı NACE kodunun sınıfından farklıysa uyarır. */
    public static function tehlikeSinifiIpucu(?string $kod, ?string $secili): ?string
    {
        $nace = filled($kod) ? NaceKodu::bul($kod) : null;
        if (! $nace || blank($secili) || $nace->tehlike_sinifi === $secili) {
            return null;
        }

        return '⚠ NACE '.$nace->kod.' tebliğe göre '.config('isg.tehlike_siniflari.'.$nace->tehlike_sinifi)
            .'. Seçiminiz farklı — yasal süreler (RA, eğitim, sağlık, İGU dakikası) bu alana göre hesaplanır.';
    }
}
