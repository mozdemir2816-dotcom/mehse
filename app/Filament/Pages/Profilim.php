<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Support\PortfoyKarne;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Profilim — isgpratik 5, 137-147.jpg. Uzmanın komuta ekranı: künye + sayaçlar +
 * sekmeler (Genel Bakış / Firmalar / Çalışanlar / Firma Takip / Risklerim / Diğer).
 * Hesap düzenleme Filament'ın kendi profil sayfasında (kullanıcı menüsü).
 */
class Profilim extends Page
{
    protected string $view = 'filament.pages.profilim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'profilim';

    protected static ?string $title = 'Profilim';

    public const SEKMELER = [
        'genel' => 'Genel Bakış',
        'firmalar' => 'Firmalar',
        'calisanlar' => 'Çalışanlar',
        'firma_takip' => 'Firma Takip',
        'risklerim' => 'Risklerim',
        'diger' => 'Diğer',
    ];

    /** Henüz kurulmamış sekmeler (isgpratik'te var, mehse'de modül bekliyor). */
    public const BEKLEYEN_SEKMELER = [
        'Eğitimler' => 'isgpratik 139-140.jpg — eğitim kaydet sihirbazı + eğitim kayıtları',
        'Evrak Takip' => 'isgpratik 141.jpg — firma × evrak türü matrisi (Firma Takip sekmesi kısmen karşılıyor)',
        'Pazarlama' => 'isgpratik 143.jpg — Saha CRM, aday firma takibi',
        'Arşiv' => 'isgpratik 144.jpg — dosya arşivi (firma bazlı klasör + kota)',
        'Raporlar' => 'isgpratik 146.jpg — üretilen tüm belgelerin kaydı (indir/düzenle/sil)',
        'Firma Ziyaretleri' => 'isgpratik 147.jpg — ziyaret takvimi',
    ];

    public string $sekme = 'genel';

    public function sekmeSec(string $sekme): void
    {
        if (array_key_exists($sekme, self::SEKMELER)) {
            $this->sekme = $sekme;
        }
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('unvanAyari')
                ->label('Ünvan & İletişim')
                ->icon('heroicon-o-identification')
                ->color('gray')
                ->fillForm(fn (): array => [
                    'unvan' => $this->kullanici->unvan,
                    'telefon' => $this->kullanici->telefon,
                    'sertifika_no' => $this->kullanici->sertifika_no,
                    'sertifika_gecerlilik' => $this->kullanici->sertifika_gecerlilik,
                ])
                ->schema([
                    Select::make('unvan')
                        ->label('Ünvan / Sınıf')
                        ->options(config('isg.uzman_unvanlari'))
                        ->native(false)
                        ->required(),
                    TextInput::make('telefon')->label('Telefon')->tel(),
                    TextInput::make('sertifika_no')->label('Sertifika No'),
                    DatePicker::make('sertifika_gecerlilik')->label('Sertifika Geçerlilik'),
                ])
                ->action(function (array $data): void {
                    Filament::auth()->user()->forceFill($data)->save();
                    unset($this->kullanici);
                    Notification::make()->title('Ünvan bilgileri güncellendi')->success()->send();
                }),

            Action::make('kaseBilgisi')
                ->label('Kaşe Bilgisi')
                ->icon('heroicon-o-finger-print')
                ->color('gray')
                ->modalDescription('Kaşe ve imza görselleri PDF / Word belge çıktılarında kullanılır.')
                ->fillForm(fn (): array => [
                    'kase_gorseli' => $this->kullanici->kase_gorseli,
                    'imza_gorseli' => $this->kullanici->imza_gorseli,
                ])
                ->schema([
                    FileUpload::make('kase_gorseli')->label('Kaşe görseli')
                        ->image()->imageEditor()
                        ->disk('public')->directory('uzman-kase')->maxSize(2048)
                        ->helperText('PNG / JPG; şeffaf zeminli görsel en iyi sonucu verir.'),
                    FileUpload::make('imza_gorseli')->label('İmza görseli')
                        ->image()->imageEditor()
                        ->disk('public')->directory('uzman-imza')->maxSize(2048),
                ])
                ->action(function (array $data): void {
                    Filament::auth()->user()->forceFill($data)->save();
                    unset($this->kullanici);
                    Notification::make()->title('Kaşe bilgisi kaydedildi')->success()->send();
                }),
        ];
    }

    #[Computed]
    public function kullanici()
    {
        return Filament::auth()->user();
    }

    #[Computed]
    public function ozet(): array
    {
        return PortfoyKarne::profilOzeti(Filament::auth()->id());
    }

    #[Computed]
    public function firmalar()
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->withCount('calisanlar')
            ->orderBy('unvan')
            ->get();
    }

    #[Computed]
    public function calisanlar()
    {
        return Calisan::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->with('firma:id,unvan')
            ->orderByDesc('aktif')
            ->orderBy('ad_soyad')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function firmaMatrisi(): array
    {
        return PortfoyKarne::firmaKriterMatrisi(Filament::auth()->id());
    }

    /** @return array<string, int> firma → aktif çalışan */
    #[Computed]
    public function calisanDagilimi(): array
    {
        return PortfoyKarne::calisanDagilimi(Filament::auth()->id());
    }

    /** @return array<string, int> son 90 gün: 'Y-m-d' => aktivite adedi */
    #[Computed]
    public function aktivite(): array
    {
        return PortfoyKarne::aktiviteGunluk(Filament::auth()->id(), 90);
    }
}
