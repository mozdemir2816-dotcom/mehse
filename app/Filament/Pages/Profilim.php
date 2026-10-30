<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Models\ArsivDosya;
use App\Models\Calisan;
use App\Models\EgitimTuru;
use App\Models\Firma;
use App\Models\FirmaChecklistVadesi;
use App\Support\EgitimKayitExcelIceAktarici;
use App\Support\PortfoyExcelPanosuUretici;
use App\Support\PortfoyKarne;
use App\Support\RaporKayitlari;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Throwable;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Profilim — isgpratik 5, 137-147.jpg. Uzmanın komuta ekranı: künye + sayaçlar +
 * sekmeler (Genel Bakış / Firmalar / Çalışanlar / Firma Takip / Risklerim / Diğer).
 * Hesap düzenleme Filament'ın kendi profil sayfasında (kullanıcı menüsü).
 */
class Profilim extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.profilim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    protected static ?int $navigationSort = 2;

    /**
     * Profilim, Kontrol Merkezi > "Portföy Genel Bakış" sekmesinin alt sayfasıdır (App\Support\SayfaAileleri, 'kontrol').
     * Kontrol Merkezi'ne yetkisi olmayan kullanıcı sayfayı kaybetmesin diye menüde yalnız o durumda görünür.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return ! KontrolMerkezi::canAccess();
    }

    protected static ?string $slug = 'profilim';

    protected static ?string $title = 'Profilim';

    public const EGITIM_KATEGORI_ETIKETLERI = [
        'genel' => 'Genel',
        'saglik' => 'Sağlık',
        'teknik' => 'Teknik',
        'risk_bazli' => 'Risk Bazlı',
        'gorev' => 'Görev/Sertifika',
        'ozel' => 'Özel',
    ];

    public const SEKMELER = [
        'genel' => 'Genel Bakış',
        'firmalar' => 'Firmalar',
        'calisanlar' => 'Çalışanlar',
        'egitimler' => 'Eğitimler',
        'firma_takip' => 'Firma Takip',
        'risklerim' => 'Risklerim',
        'arsiv' => 'Arşiv',
        'raporlar' => 'Raporlar',
    ];

    /** Sekme şeridinde gösterilmeyenler: Arşiv sol menüdeki "Arşiv" sayfasında (29.10.2026); eski ?sekme=arsiv adresi çalışır. */
    public const GIZLI_SEKMELER = ['arsiv'];

    #[Url(as: 'sekme')]
    public string $sekme = 'genel';

    public int $arsivSeciliFirmaId = 0;

    public string $raporArama = '';

    public string $raporTipFiltre = '';

    public string $egitimArama = '';

    public int $egitimFirmaId = 0;

    public string $egitimKategoriFiltre = '';

    public string $egitimDurumFiltre = '';

    public string $egitimDurumTab = 'aktif';

    public int $firmaTakipSeciliId = 0;

    public function mount(): void
    {
        $this->arsivSeciliFirmaId = (int) (Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->value('id') ?? 0);
    }

    public function sekmeSec(string $sekme): void
    {
        if (array_key_exists($sekme, self::SEKMELER)) {
            $this->sekme = $sekme;
        }
    }

    /** Hero kartındaki düğmeler görünümde `{{ $this->xAction }}` ile çizilir; sayfa başlığında düğme yok. */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function excelPanosuAction(): Action
    {
        return Action::make('excelPanosu')
            ->label('Excel Portföy Raporu İndir')
            ->icon('heroicon-o-table-cells')
            ->color('gray')
            ->outlined()
            ->size('sm')
            ->action(fn () => PortfoyExcelPanosuUretici::indir(Filament::auth()->user()));
    }

    public function unvanAyariAction(): Action
    {
        return Action::make('unvanAyari')
                ->label('Ünvan & İletişim Düzenle')
                ->icon('heroicon-o-identification')
                ->color('gray')
                ->outlined()
                ->size('sm')
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
                });
    }

    public function kaseBilgisiAction(): Action
    {
        return Action::make('kaseBilgisi')
                ->label('Kaşe / İmza Yükle')
                ->icon('heroicon-o-finger-print')
                ->color('primary')
                ->size('sm')
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
                });
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

    /** @return array<int, array{anahtar:string, ad:string, hazir:bool, ozel:bool}> Firma Takip'in tam sütun listesi */
    #[Computed]
    public function firmaTakipKriterleri(): array
    {
        return PortfoyKarne::firmaTakipKriterleri(Filament::auth()->id());
    }

    #[Computed]
    public function firmaTakipSecili(): ?Firma
    {
        return $this->firmaTakipSeciliId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaTakipSeciliId)
            : null;
    }

    /**
     * Firma Takip'te seçili tek firmanın vade tarihli checklist detayı
     * (isgpratik "Firma Checklist" referansı).
     *
     * @return array<int, array{anahtar: string, ad: string, tamam: bool, vade_tarihi: ?Carbon, durum: string}>
     */
    #[Computed]
    public function firmaChecklistDetay(): array
    {
        return $this->firmaTakipSecili ? PortfoyKarne::firmaChecklistDetay($this->firmaTakipSecili) : [];
    }

    public function firmaChecklistVadeGuncelle(string $anahtar, ?string $tarih): void
    {
        if (! $this->firmaTakipSecili) {
            return;
        }

        FirmaChecklistVadesi::updateOrCreate(
            ['firma_id' => $this->firmaTakipSecili->id, 'kriter_anahtari' => $anahtar],
            ['vade_tarihi' => filled($tarih) ? $tarih : null],
        );

        unset($this->firmaChecklistDetay);
    }

    /** @return array<string, int> firma → aktif çalışan */
    #[Computed]
    public function calisanDagilimi(): array
    {
        return PortfoyKarne::calisanDagilimi(Filament::auth()->id());
    }

    /** @return array<int, array{id: int, unvan: string, calisan: int, kritik_risk: int}> en çok çalışanlı ve kritik riskli ilk 5 firma */
    #[Computed]
    public function oncelikliFirmalar(): array
    {
        return PortfoyKarne::oncelikliFirmalar(Filament::auth()->id(), 5);
    }

    /**
     * Kaşe / imza durumu: 'yuklu' (dosya var) | 'dosya_yok' (kayıtta var ama dosya bulunamadı — yeniden yüklenmeli) | 'yok'.
     *
     * @return array{kase: string, imza: string}
     */
    public function gorselDurumu(): array
    {
        $disk = Storage::disk('public');
        $durum = fn (?string $yol) => ! $yol ? 'yok' : ($disk->exists($yol) ? 'yuklu' : 'dosya_yok');

        return ['kase' => $durum($this->kullanici->kase_gorseli), 'imza' => $durum($this->kullanici->imza_gorseli)];
    }

    /** @return array<string, int> son 90 gün: 'Y-m-d' => aktivite adedi */
    #[Computed]
    public function aktivite(): array
    {
        return PortfoyKarne::aktiviteGunluk(Filament::auth()->id(), 90);
    }

    #[Computed]
    public function ilkyardimciIhtiyaci(): int
    {
        return PortfoyKarne::ilkyardimciIhtiyaci(Filament::auth()->id());
    }

    /** @return array<string, int> 5 eksenli performans profili — eksen adı => yüzde */
    #[Computed]
    public function performansEksenleri(): array
    {
        return PortfoyKarne::performansEksenleri(Filament::auth()->id());
    }

    /** @return Collection<int, EgitimTuru> Kullanıcının fiilen takip ettiği eğitim konuları */
    #[Computed]
    public function egitimTurleri()
    {
        return EgitimTuru::aktifListe(Filament::auth()->id());
    }

    /** @return Collection<int, EgitimTuru> Kategori filtresine göre görünecek sütunlar */
    #[Computed]
    public function egitimTurleriGorunen()
    {
        return $this->egitimTurleri->when(
            filled($this->egitimKategoriFiltre),
            fn ($q) => $q->filter(fn (EgitimTuru $t) => ($t->kategori() ?? 'ozel') === $this->egitimKategoriFiltre)->values(),
        );
    }

    /** @return array<int, string> firma id => unvan (Eğitimler sekmesi firma filtresi) */
    #[Computed]
    public function egitimFirmalari(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    /**
     * Profilim > Eğitimler — Çalışan × eğitim türü matrisi (isgpratik 139-140.jpg).
     * Sütunlar sabit değil — kullanıcı "Konu Ekle" ile büyütebilir. Arama/firma/
     * kategori/durum ve Aktif-İşten Ayrılan filtreleri burada uygulanır.
     *
     * @return array<int, array{calisan: Calisan, hucreler: array<string, array{tarih: ?Carbon, durum: ?string}>}>
     */
    #[Computed]
    public function egitimMatrisi(): array
    {
        $turler = $this->egitimTurleri;
        $gorunenAnahtarlar = $this->egitimTurleriGorunen->pluck('anahtar')->all();

        return Calisan::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->where('aktif', $this->egitimDurumTab === 'aktif')
            ->when($this->egitimFirmaId, fn ($q) => $q->where('firma_id', $this->egitimFirmaId))
            ->when(filled($this->egitimArama), fn ($q) => $q->where('ad_soyad', 'like', '%'.$this->egitimArama.'%'))
            ->with(['firma:id,unvan,tehlike_sinifi', 'egitimKayitlari'])
            ->orderBy('ad_soyad')
            ->get()
            ->map(function (Calisan $calisan) use ($turler): array {
                $kayitlar = $calisan->egitimKayitlari->keyBy('tur');
                $hucreler = [];

                foreach ($turler as $tur) {
                    $kayit = $kayitlar->get($tur->anahtar);
                    $hucreler[$tur->anahtar] = [
                        'tarih' => $kayit?->tarih,
                        'durum' => $kayit?->durum($tur),
                    ];
                }

                return ['calisan' => $calisan, 'hucreler' => $hucreler];
            })
            ->when(
                filled($this->egitimDurumFiltre),
                fn ($rows) => $rows->filter(function (array $satir) use ($gorunenAnahtarlar): bool {
                    foreach ($gorunenAnahtarlar as $anahtar) {
                        $durum = $satir['hucreler'][$anahtar]['durum'] ?? 'eksik';
                        if (($durum ?? 'eksik') === $this->egitimDurumFiltre) {
                            return true;
                        }
                    }

                    return false;
                }),
            )
            ->values()
            ->all();
    }

    /** @return array{toplam: int, eksik: int} Eğitimler sekmesi özet sayaçları (görünen sütunlar üzerinden) */
    #[Computed]
    public function egitimOzet(): array
    {
        $gorunenAnahtarlar = $this->egitimTurleriGorunen->pluck('anahtar')->all();

        $eksik = collect($this->egitimMatrisi)->sum(
            fn (array $satir) => collect($gorunenAnahtarlar)->filter(fn ($a) => blank($satir['hucreler'][$a]['tarih'] ?? null))->count(),
        );

        return ['toplam' => count($this->egitimMatrisi), 'eksik' => $eksik];
    }

    /** Profilim > Arşiv — kullanıcının firma listesi (sol panel). */
    #[Computed]
    public function arsivFirmalar()
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->get(['id', 'unvan']);
    }

    #[Computed]
    public function arsivSeciliFirma(): ?Firma
    {
        return $this->arsivFirmalar->firstWhere('id', $this->arsivSeciliFirmaId);
    }

    /** @return Collection<int, ArsivDosya> */
    #[Computed]
    public function arsivDosyalar()
    {
        if (! $this->arsivSeciliFirmaId) {
            return collect();
        }

        return ArsivDosya::query()
            ->where('firma_id', $this->arsivSeciliFirmaId)
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->latest()
            ->get();
    }

    /** Portföy genelinde kullanılan alan (MB). */
    #[Computed]
    public function arsivKullanilanMb(): float
    {
        $toplamBayt = (int) ArsivDosya::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->sum('boyut');

        return round($toplamBayt / 1024 / 1024, 1);
    }

    /**
     * Profilim > Raporlar — mehse'de üretilen tüm belgelerin tek listesi (isgpratik 146.jpg).
     *
     * @return array<int, array{kayit: Model, kaynak: array, baslik: string, tip: string, firma: ?string, tarih: ?Carbon}>
     */
    #[Computed]
    public function raporlar(): array
    {
        return RaporKayitlari::hepsi(Filament::auth()->id(), $this->raporArama ?: null, $this->raporTipFiltre ?: null);
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function raporTipSecenekleri()
    {
        return RaporKayitlari::tipSecenekleri();
    }

    public function egitimSablonIndirAction(): Action
    {
        return Action::make('egitimSablonIndir')
            ->label('Şablon İndir')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(fn () => EgitimKayitExcelIceAktarici::sablonIndir(Filament::auth()->id()));
    }

    public function egitimYukleAction(): Action
    {
        return Action::make('egitimYukle')
            ->label('Excel\'den Toplu Yükle')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->modalDescription('Şablonu indirip eğitim tarihlerini doldurun, aynı dosyayı geri yükleyin. T.C. Kimlik No doluysa onunla, boşsa Ad Soyad + Firma ile eşleştirilir.')
            ->modalSubmitActionLabel('Yükle')
            ->schema([
                FileUpload::make('dosya')
                    ->label('Excel / CSV dosyası')
                    ->disk('local')
                    ->directory('excel-ice-aktarim')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->required(),
            ])
            ->action(function (array $data): void {
                $yol = Storage::disk('local')->path($data['dosya']);

                try {
                    $sonuc = EgitimKayitExcelIceAktarici::iceAktar($yol, Filament::auth()->id());
                } catch (Throwable $e) {
                    Storage::disk('local')->delete($data['dosya']);
                    Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                    return;
                }

                Storage::disk('local')->delete($data['dosya']);
                unset($this->egitimMatrisi);

                $bildirim = Notification::make()->title($sonuc['basarili'].' çalışan satırı işlendi');

                if ($sonuc['hatalar']) {
                    $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                }

                $sonuc['basarili'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
            });
    }

    public function egitimTuruEkleAction(): Action
    {
        $userId = Filament::auth()->id();
        $eklenmisAnahtarlar = EgitimTuru::where('user_id', $userId)->pluck('anahtar')->all();

        $katalog = collect(config('isg.egitim_kayit_turleri', []))
            ->reject(fn (array $t) => in_array($t['anahtar'], $eklenmisAnahtarlar, true))
            ->values();

        return Action::make('egitimTuruEkle')
            ->label('Konu Ekle')
            ->icon('heroicon-o-plus-circle')
            ->color('gray')
            ->modalHeading('Eğitim Konusu Ekle')
            ->modalDescription('Hazır katalogdan seçin veya kendi konunuzu yazın; eklenen konu tüm çalışanların matrisinde yeni bir sütun olarak görünür.')
            ->modalSubmitActionLabel('Ekle')
            ->schema([
                Select::make('katalog')
                    ->label('Hazır konu')
                    ->options($katalog->pluck('ad', 'anahtar'))
                    ->native(false)
                    ->placeholder('— özel konu yazacağım —')
                    ->live(),
                TextInput::make('ozel_ad')
                    ->label('Özel konu adı')
                    ->visible(fn ($get) => blank($get('katalog')))
                    ->requiredWithout('katalog'),
                TextInput::make('gecerlilik_ay')
                    ->label('Geçerlilik süresi (ay)')
                    ->numeric()
                    ->minValue(1)
                    ->default(12)
                    ->required()
                    ->helperText('Süre dolunca çalışan matrisinde "yakında/dolmuş" olarak işaretlenir.'),
            ])
            ->action(function (array $data) use ($userId, $katalog): void {
                if (filled($data['katalog'] ?? null)) {
                    $secilen = $katalog->firstWhere('anahtar', $data['katalog']);
                    $anahtar = $secilen['anahtar'];
                    $ad = $secilen['ad'];
                } else {
                    $ad = trim((string) $data['ozel_ad']);
                    $anahtar = Str::slug($ad, '_');
                }

                EgitimTuru::firstOrCreate(
                    ['user_id' => $userId, 'anahtar' => $anahtar],
                    [
                        'ad' => $ad,
                        'gecerlilik_ay' => (int) $data['gecerlilik_ay'],
                        'sira' => (int) EgitimTuru::where('user_id', $userId)->max('sira') + 1,
                    ]
                );

                unset($this->egitimTurleri, $this->egitimMatrisi);
                Notification::make()->title('Eğitim konusu eklendi')->success()->send();
            });
    }

    /** "Konu Ekle"nin karşılığı: takip listesinden bir konuyu kaldırır (geçmiş eğitim kayıtları silinmez). */
    public function egitimTuruKaldir(string $anahtar): void
    {
        EgitimTuru::where('user_id', Filament::auth()->id())->where('anahtar', $anahtar)->delete();

        unset($this->egitimTurleri, $this->egitimMatrisi);
        Notification::make()->title('Eğitim konusu kaldırıldı')->success()->send();
    }

    public function arsivFirmaSec(int $firmaId): void
    {
        $kendiFirmasiMi = Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->where('id', $firmaId)
            ->exists();

        if ($kendiFirmasiMi) {
            $this->arsivSeciliFirmaId = $firmaId;
            unset($this->arsivDosyalar);
        }
    }

    public function arsivYukleAction(): Action
    {
        return Action::make('arsivYukle')
            ->label('Dosya Yükle')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalSubmitActionLabel('Yükle')
            ->schema([
                FileUpload::make('dosya')
                    ->label('Dosya')
                    ->disk('public')
                    ->directory(fn () => 'arsiv/'.$this->arsivSeciliFirmaId)
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg',
                        'image/png',
                    ])
                    ->maxSize(20480)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $firma = Firma::query()
                    ->where('user_id', Filament::auth()->id())
                    ->where('id', $this->arsivSeciliFirmaId)
                    ->first();

                if (! $firma) {
                    Notification::make()->title('Önce bir firma seçin')->danger()->send();

                    return;
                }

                $yol = $data['dosya'];

                ArsivDosya::create([
                    'firma_id' => $firma->id,
                    'dosya_adi' => basename($yol),
                    'dosya_yolu' => $yol,
                    'boyut' => Storage::disk('public')->size($yol),
                ]);

                unset($this->arsivDosyalar, $this->arsivKullanilanMb);
                Notification::make()->title('Dosya yüklendi')->success()->send();
            });
    }

    public function arsivDosyaSil(int $id): void
    {
        $dosya = ArsivDosya::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($id);

        if (! $dosya) {
            return;
        }

        Storage::disk('public')->delete($dosya->dosya_yolu);
        $dosya->delete();

        unset($this->arsivDosyalar, $this->arsivKullanilanMb);
        Notification::make()->title('Dosya silindi')->success()->send();
    }

    public function raporIndirAction(): Action
    {
        return Action::make('raporIndir')
            ->label('İndir')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (array $arguments) {
                $kaynak = collect(config('isg.raporlar.kaynaklar', []))->firstWhere('model', $arguments['model'] ?? null);

                if (! $kaynak) {
                    Notification::make()->title('Kaynak bulunamadı')->danger()->send();

                    return;
                }

                $kayit = $kaynak['model']::query()
                    ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
                    ->find($arguments['id'] ?? null);

                if (! $kayit) {
                    Notification::make()->title('Kayıt bulunamadı')->danger()->send();

                    return;
                }

                $ikincil = ($arguments['format'] ?? null) === 'ikincil';
                $ureticiSinif = $ikincil ? $kaynak['ikincil_uretici'] : $kaynak['uretici'];
                $metod = $ikincil ? $kaynak['ikincil_metod'] : $kaynak['pdf_metod'];

                return $ureticiSinif::{$metod}($kayit);
            });
    }
}
