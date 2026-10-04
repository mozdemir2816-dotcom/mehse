<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\ArsivDosya;
use App\Models\BelgeSablonu;
use App\Models\Firma;
use App\Support\ArsivKurali;
use App\Support\ArsivUretici;
use App\Support\BelgeSablonMotoru;
use App\Support\ExcelBellek;
use App\Support\KullaniciAyarlari;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;
use ZipArchive;

/**
 * Arşiv (eski adı Doküman Yönetimi; 04.10.2026'da kullanıcının FirstİSG
 * "Arşiv" referansıyla yeniden kuruldu). Kategori kurallı belge arşivi:
 * - Kategoriler (config/arsiv.php) gruplu kartlar; firma × kategori durumu
 *   ArsivKurali'ndan (yürürlükte / eksik / yaklaşan / gecikmiş).
 * - Takip Listesi (sorunlar) ve Uyum Tablosu (firma × kategori matrisi).
 * - Yeni kayıt: Belge Yükle (çok sayfalı fotoğraflar tek PDF'e birleşir) ya da
 *   Şablondan Üret (sistem üreticileri + kullanıcının {{alan}}lı şablonları);
 *   üretilen belge "imza bekliyor" olur, "Dosyaya ekle" ile resmîleşir.
 * - Belge Şablonları ve Hatırlatma Ayarları; eski düz liste "Tüm Belgeler" sekmesinde.
 * Aynı tablo (arsiv_dosyalari) Profilim > Arşiv ile paylaşılır.
 */
class DokumanYonetimi extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.dokuman-yonetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 18;

    protected static ?string $slug = 'dokuman-yonetimi';

    protected static ?string $title = 'Arşiv';

    protected static ?string $navigationLabel = 'Arşiv';

    public const SEKMELER = ['kategoriler' => 'Kategoriler', 'takip' => 'Takip Listesi', 'uyum' => 'Uyum Tablosu', 'liste' => 'Tüm Belgeler'];

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** Açık kategori sayfası (null = genel bakış). */
    #[Url(as: 'kategori')]
    public ?string $kategoriAnahtari = null;

    public string $sekme = 'kategoriler';

    // "Tüm Belgeler" sekmesi filtreleri
    public string $kategori = '';

    public string $durum = '';

    public string $arama = '';

    public ?int $goruntulenenId = null;

    public function mount(): void
    {
        if ($this->firmaId && ! array_key_exists($this->firmaId, $this->firmalar)) {
            $this->firmaId = null;
        }

        if ($this->kategoriAnahtari && ! isset(config('arsiv.kategoriler')[$this->kategoriAnahtari])) {
            $this->kategoriAnahtari = null;
        }
    }

    public function getTitle(): string
    {
        return $this->kategoriAnahtari ? ArsivKurali::kategori($this->kategoriAnahtari)['ad'] : 'Arşiv';
    }

    /*
    |--------------------------------------------------------------------------
    | Veri
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** @return Collection<int, Firma> kapsamdaki firmalar (seçiliyse yalnız o) */
    #[Computed]
    public function kapsamFirmalari(): Collection
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->when($this->firmaId, fn ($q) => $q->whereKey($this->firmaId))
            ->withCount(['calisanlar as aktif_calisan' => fn ($q) => $q->where('aktif', true)])
            ->orderBy('unvan')
            ->get();
    }

    /** @return Collection<int, ArsivDosya> */
    #[Computed]
    public function tumu(): Collection
    {
        return ArsivDosya::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->with('firma:id,unvan,tehlike_sinifi,calisan_sayisi')
            ->latest('id')
            ->get();
    }

    #[Computed]
    public function haric(): array
    {
        return KullaniciAyarlari::arsivHaric();
    }

    /** @return array<int, array<string, array<string, mixed>>> [firma_id][kategori] */
    #[Computed]
    public function matris(): array
    {
        return ArsivKurali::matris($this->kapsamFirmalari, $this->tumu, $this->haric);
    }

    /** @return array<string, array{kayit: int, gecikmis: int, eksik: int, yaklasan: int, imza: int}> kategori kartları */
    #[Computed]
    public function kartlar(): array
    {
        $kayitlar = $this->tumu->groupBy(fn (ArsivDosya $d) => ArsivKurali::kategori($d->kategori)['anahtar']);
        $sonuc = [];

        foreach (array_keys(config('arsiv.kategoriler')) as $k) {
            $durumlar = collect($this->matris)->map(fn ($satir) => $satir[$k]['durum']);
            $grup = $kayitlar->get($k, collect());
            $sonuc[$k] = [
                'kayit' => $grup->filter(fn (ArsivDosya $d) => $d->aktif && ! $d->imzaBekliyorMu())->count(),
                'imza' => $grup->filter(fn (ArsivDosya $d) => $d->imzaBekliyorMu())->count(),
                'gecikmis' => $durumlar->filter(fn ($d) => $d === 'gecikmis')->count(),
                'eksik' => $durumlar->filter(fn ($d) => $d === 'eksik')->count(),
                'yaklasan' => $durumlar->filter(fn ($d) => $d === 'yaklasan')->count(),
            ];
        }

        return $sonuc;
    }

    /** @return Collection<int, array<string, mixed>> Takip Listesi — gecikmiş, yaklaşan, eksik (aciliyet sırasıyla) */
    #[Computed]
    public function sorunlar(): Collection
    {
        $sira = ['gecikmis' => 0, 'yaklasan' => 1, 'eksik' => 2];
        $firmalar = $this->kapsamFirmalari->keyBy('id');
        $liste = collect();

        foreach ($this->matris as $firmaId => $kategoriler) {
            foreach ($kategoriler as $k => $d) {
                if (isset($sira[$d['durum']])) {
                    $liste->push($d + ['firma' => $firmalar[$firmaId], 'kategori' => $k]);
                }
            }
        }

        return $liste->sortBy([
            fn ($a, $b) => $sira[$a['durum']] <=> $sira[$b['durum']],
            fn ($a, $b) => ($a['son_tarih']?->timestamp ?? PHP_INT_MAX) <=> ($b['son_tarih']?->timestamp ?? PHP_INT_MAX),
        ])->values();
    }

    /** @return Collection<int, Firma> aktif çalışan listesi boş firmalar */
    #[Computed]
    public function calisanListesiEksik(): Collection
    {
        return $this->kapsamFirmalari->where('aktif_calisan', 0)->values();
    }

    /** @return array{durum: ?array, dosyada: Collection, uretilmis: Collection, firmalar: array} açık kategori sayfası */
    #[Computed]
    public function kategoriDetay(): array
    {
        $k = $this->kategoriAnahtari;
        $kayitlar = $this->tumu->filter(fn (ArsivDosya $d) => ArsivKurali::kategori($d->kategori)['anahtar'] === $k);

        return [
            'durum' => $this->firmaId ? ($this->matris[$this->firmaId][$k] ?? null) : null,
            'dosyada' => $kayitlar->filter(fn (ArsivDosya $d) => ! $d->imzaBekliyorMu())
                ->sortByDesc(fn (ArsivDosya $d) => ($d->baslangic_tarihi?->format('Ymd') ?? '0').'-'.str_pad((string) $d->id, 10, '0', STR_PAD_LEFT))->values(),
            'uretilmis' => $kayitlar->filter(fn (ArsivDosya $d) => $d->imzaBekliyorMu())->values(),
            'firmalar' => collect($this->matris)->map(fn ($satir) => $satir[$k])->all(),
        ];
    }

    /** @return Collection<int, BelgeSablonu> */
    #[Computed]
    public function sablonlar(): Collection
    {
        return BelgeSablonu::query()->where('user_id', Filament::auth()->id())->orderBy('ad')->get();
    }

    #[Computed]
    public function goruntulenen(): ?ArsivDosya
    {
        return $this->bul($this->goruntulenenId);
    }

    /** @return Collection<int, ArsivDosya> "Tüm Belgeler" sekmesi (eski Doküman Yönetimi listesi) */
    #[Computed]
    public function dokumanlar(): Collection
    {
        $aranan = mb_strtolower(trim($this->arama));

        return $this->tumu
            ->when($this->kategori !== '', fn ($c) => $c->filter(fn (ArsivDosya $d) => ArsivKurali::kategori($d->kategori)['anahtar'] === $this->kategori))
            ->when($this->durum === 'aktif', fn ($c) => $c->where('aktif', true))
            ->when($this->durum === 'pasif', fn ($c) => $c->where('aktif', false))
            ->when($this->durum === 'imza', fn ($c) => $c->filter(fn (ArsivDosya $d) => $d->imzaBekliyorMu()))
            ->when(in_array($this->durum, ['dolmus', 'yaklasan'], true), fn ($c) => $c->filter(fn (ArsivDosya $d) => $d->aktif && $d->gecerlilikDurumu() === $this->durum))
            ->when($aranan !== '', fn ($c) => $c->filter(fn (ArsivDosya $d) => str_contains(
                mb_strtolower(implode(' ', [$d->etiket(), $d->dosya_adi, $d->aciklama, $d->kategoriEtiketi(), $d->firma?->unvan, $d->kisi_adi, $d->versiyon])),
                $aranan,
            )))
            ->values();
    }

    /** @return array<string, array<string, string>> kategori seçimi, gruplu (OSGB arşiv evrakları önce) */
    public function kategoriSecenekleri(): array
    {
        return collect(config('arsiv.gruplar'))
            ->mapWithKeys(fn ($ad, $grup) => [$ad => collect(config('arsiv.kategoriler'))->where('grup', $grup)->map(fn ($k) => $k['ad'])->all()])
            ->all();
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'kategori', 'durum', 'arama'], true)) {
            $this->yenile();
        }
    }

    private function yenile(): void
    {
        unset($this->kapsamFirmalari, $this->tumu, $this->matris, $this->kartlar, $this->sorunlar,
            $this->calisanListesiEksik, $this->kategoriDetay, $this->dokumanlar, $this->sablonlar, $this->goruntulenen, $this->haric);
    }

    public function kategoriAc(?string $anahtar): void
    {
        $this->kategoriAnahtari = $anahtar && isset(config('arsiv.kategoriler')[$anahtar]) ? $anahtar : null;
        $this->goruntulenenId = null;
        unset($this->kategoriDetay);
    }

    /** Takip listesi / uyum tablosundan: firmayı seç ve kategoriyi aç. */
    public function firmaKategoriAc(int $firmaId, string $anahtar): void
    {
        $this->firmaId = array_key_exists($firmaId, $this->firmalar) ? $firmaId : null;
        $this->yenile();
        $this->kategoriAc($anahtar);
    }

    private function bul(?int $id): ?ArsivDosya
    {
        return $id ? ArsivDosya::query()->whereIn('firma_id', array_keys($this->firmalar))->find($id) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Yeni kayıt (Belge Yükle / Şablondan Üret)
    |--------------------------------------------------------------------------
    */

    /** @return array<string, string> kategori için şablon seçenekleri: 'sistem' ve 'kullanici:{id}' */
    public function sablonSecenekleri(?string $kategori): array
    {
        $secenekler = [];

        if ($kategori && ArsivUretici::varMi($kategori)) {
            $secenekler['sistem'] = ArsivUretici::sablonlar()[$kategori];
        }

        foreach ($this->sablonlar->filter(fn (BelgeSablonu $s) => ! $s->kategori || $s->kategori === $kategori) as $s) {
            $secenekler['kullanici:'.$s->id] = $s->ad.' ('.strtoupper($s->tur).')';
        }

        return $secenekler;
    }

    private function kullaniciSablonu(?string $secim): ?BelgeSablonu
    {
        return $secim && str_starts_with($secim, 'kullanici:')
            ? $this->sablonlar->firstWhere('id', (int) Str::after($secim, 'kullanici:'))
            : null;
    }

    /** @return array<int, int> yıl düğmeleri — planda geçen yıl…gelecek yıl, raporda geçmiş yıllar */
    private function yilSecenekleri(string $kategori): array
    {
        $y = (int) now()->year;
        $yillar = ArsivKurali::kategori($kategori)['kural'] === 'yillik_rapor' ? range($y - 3, $y) : range($y - 2, $y + 1);

        return array_combine($yillar, $yillar);
    }

    /** Kategoriye özel alanlar (kişi, yıl, tarih, bitiş) — yeni kayıt ve düzelt formlarında ortak. */
    private function kategoriAlanlari(): array
    {
        $alan = fn (Get $get, string $ad) => ArsivKurali::kategori($get('kategori'))['alanlar'][$ad] ?? null;

        return [
            TextInput::make('kisi_adi')
                ->label(fn (Get $get) => $alan($get, 'kisi') ?? 'Kişi')
                ->placeholder('Ad Soyad')
                ->visible(fn (Get $get) => (bool) $alan($get, 'kisi'))
                ->required(fn (Get $get) => (bool) $alan($get, 'kisi') && ArsivKurali::kategori($get('kategori'))['kural'] === 'suresiz')
                ->maxLength(120),
            ToggleButtons::make('yil')
                ->label(fn (Get $get) => $alan($get, 'yil') ?? 'Yıl')
                ->options(fn (Get $get) => $this->yilSecenekleri((string) $get('kategori')))
                ->inline()
                ->live()
                ->visible(fn (Get $get) => (bool) $alan($get, 'yil'))
                ->required(fn (Get $get) => (bool) $alan($get, 'yil'))
                ->helperText(fn (Get $get) => match (ArsivKurali::kategori($get('kategori'))['kural']) {
                    'yillik_plan' => filled($get('yil')) ? '31 Aralık '.$get('yil').' tarihine kadar geçerli.' : null,
                    'yillik_rapor' => filled($get('yil')) ? '31 Ocak '.((int) $get('yil') + 1).' tarihine kadar hazırlanmalı.' : null,
                    default => null,
                }),
            DatePicker::make('baslangic_tarihi')
                ->label(fn (Get $get) => $alan($get, 'tarih') ?? 'Belge Tarihi')
                ->native(false)->displayFormat('d.m.Y')
                ->live()
                ->required(fn (Get $get) => (bool) $alan($get, 'tarih'))
                ->helperText(function (Get $get) {
                    $k = ArsivKurali::kategori($get('kategori'));
                    $firma = $get('firma_id') ? Firma::query()->where('user_id', Filament::auth()->id())->find($get('firma_id')) : null;
                    $ay = $k['kural'] === 'periyodik' ? ArsivKurali::ay($k['anahtar'], $firma) : null;

                    return 'Takip bu tarihe göre yapılır; genellikle belgenin hazırlandığı tarihle aynıdır.'
                        .($ay && filled($get('baslangic_tarihi')) ? ' Sonraki tarih: '.Carbon::parse($get('baslangic_tarihi'))->addMonthsNoOverflow($ay)->format('d.m.Y').'.' : '');
                }),
            DatePicker::make('gecerlilik_sonu')
                ->label(fn (Get $get) => $alan($get, 'bitis') ?? 'Geçerlilik Sonu')
                ->native(false)->displayFormat('d.m.Y')
                ->visible(fn (Get $get) => (bool) $alan($get, 'bitis')),
        ];
    }

    public function yeniKayitAction(): Action
    {
        return Action::make('yeniKayit')
            ->label('Yeni Kayıt')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni kayıt')
            ->modalDescription('Belgenin PDF\'ini seçin ya da sayfalarının fotoğrafını çekin; veya şablondan üretin.')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments) {
                $kategori = $arguments['kategori'] ?? $this->kategoriAnahtari ?? 'diger';

                return [
                    'yontem' => $arguments['yontem'] ?? 'yukle',
                    'kategori' => $kategori,
                    'firma_id' => $arguments['firma'] ?? $this->firmaId,
                    'yil' => ArsivKurali::beklenenYil($kategori),
                    'baslangic_tarihi' => now()->toDateString(),
                    'sablon' => array_key_first($this->sablonSecenekleri($kategori)),
                ];
            })
            ->schema(fn () => [
                ToggleButtons::make('yontem')
                    ->hiddenLabel()
                    ->options(['yukle' => 'Belge Yükle', 'sablon' => 'Şablondan Üret'])
                    ->inline()->live()->required(),
                Grid::make(2)->schema([
                    Select::make('firma_id')->label('Firma')->options($this->firmalar)->required()->searchable()->live()
                        ->helperText('Kayıt bu işyerinin arşivine eklenecek.'),
                    Select::make('kategori')->label('Kategori')->required()->live()
                        ->options($this->kategoriSecenekleri()),
                ]),
                FileUpload::make('dosyalar')->storeFileNamesIn('dosya_adlari')
                    ->label('Belge')
                    ->multiple()
                    ->disk('public')->directory('arsiv/gecici')
                    ->acceptedFileTypes([
                        'application/pdf', 'image/jpeg', 'image/png', 'image/webp',
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'application/zip',
                    ])
                    ->maxSize(20480)
                    ->maxFiles(30)
                    ->reorderable()
                    ->helperText('Belge birden fazla sayfaysa sayfaları tek tek çekebilir veya hepsini birlikte seçebilirsiniz; fotoğraflar sırasıyla tek bir PDF\'te birleştirilir.')
                    ->visible(fn (Get $get) => $get('yontem') !== 'sablon')
                    ->required(fn (Get $get) => $get('yontem') !== 'sablon'),
                ...$this->kategoriAlanlari(),
                Textarea::make('aciklama')->label('Not (opsiyonel)')->placeholder('Kısa bir not ekleyebilirsiniz')->rows(2),
                Section::make('Şablon')
                    ->visible(fn (Get $get) => $get('yontem') === 'sablon')
                    ->schema(fn (Get $get) => $this->sablonAlanlari($get)),
            ])
            ->action(fn (array $data) => $this->yeniKayitKaydet($data));
    }

    /** "Şablondan Üret" bölümünün dinamik alanları. */
    private function sablonAlanlari(Get $get): array
    {
        $secenekler = $this->sablonSecenekleri($get('kategori'));
        $alanlar = [
            Select::make('sablon')
                ->label('Şablon')
                ->options($secenekler)
                ->live()
                ->required(fn (Get $get) => $get('yontem') === 'sablon')
                ->helperText($secenekler ? null : 'Bu kategori için şablon yok. "Belge Şablonları"ndan kendi Word / Excel şablonunuzu yükleyin.'),
            TextInput::make('baslik')->label('Belge Başlığı')->maxLength(160)
                ->placeholder(trim(($get('yil') ? $get('yil').' ' : '').ArsivKurali::kisaAd(ArsivKurali::kategori($get('kategori'))))),
        ];

        $sablon = $this->kullaniciSablonu($get('sablon'));

        if (! $sablon) {
            return $alanlar;
        }

        $firma = $get('firma_id') ? Firma::query()->where('user_id', Filament::auth()->id())->find($get('firma_id')) : null;

        if (! $firma) {
            return [...$alanlar, TextEntry::make('firma_uyari')->hiddenLabel()->state('Şablondan üretmek için önce işyerini seçin.')];
        }

        $sistem = BelgeSablonMotoru::sistemDegerleri($firma, ['yil' => $get('yil'), 'tarih' => $get('baslangic_tarihi')]);
        $gelen = collect($sablon->yer_tutucular ?? [])->filter(fn ($a) => array_key_exists($a, $sistem));

        if ($gelen->isNotEmpty()) {
            $alanlar[] = TextEntry::make('sistem_bilgileri')
                ->label('Sistemden gelecek bilgiler')
                ->state($gelen->map(fn ($a) => BelgeSablonMotoru::etiket($a).': '.($sistem[$a] ?? 'boş, sistemde kayıtlı değil'))->values()->all())
                ->listWithLineBreaks();
        }

        foreach ($sablon->yer_tutucular ?? [] as $a) {
            if (! filled($sistem[$a] ?? null)) {
                $alanlar[] = TextInput::make('degerler.'.BelgeSablonMotoru::formAnahtari($a))->label(BelgeSablonMotoru::etiket($a))->maxLength(500);
            }
        }

        return $alanlar;
    }

    public function yeniKayitKaydet(array $data): void
    {
        $firma = Firma::query()->where('user_id', Filament::auth()->id())->find($data['firma_id'] ?? null);

        if (! $firma) {
            return;
        }

        $kategori = ArsivKurali::kategori($data['kategori'] ?? 'diger');
        $ortak = [
            'firma_id' => $firma->id,
            'kategori' => $kategori['anahtar'],
            'aciklama' => $data['aciklama'] ?? null,
            'kisi_adi' => $data['kisi_adi'] ?? null,
            'yil' => filled($data['yil'] ?? null) ? (int) $data['yil'] : null,
            'baslangic_tarihi' => $data['baslangic_tarihi'] ?? null,
            'gecerlilik_sonu' => $data['gecerlilik_sonu'] ?? null,
            'aktif' => true,
        ];
        $varsayilanBaslik = $kategori['kural'] === 'suresiz' && $ortak['kisi_adi']
            ? $kategori['ad'].' — '.$ortak['kisi_adi']
            : trim(($ortak['yil'] ? $ortak['yil'].' ' : '').ArsivKurali::kisaAd($kategori));

        if (($data['yontem'] ?? 'yukle') === 'sablon') {
            $this->sablondanUret($firma, $data, $ortak, $varsayilanBaslik);

            return;
        }

        $dosya = $this->yuklenenleriBirlestir((array) ($data['dosyalar'] ?? []), $firma, $varsayilanBaslik, (array) ($data['dosya_adlari'] ?? []));

        if (! $dosya) {
            Notification::make()->title('Belge yüklenmedi')->danger()->send();

            return;
        }

        ArsivDosya::create($ortak + $dosya + ['baslik' => $varsayilanBaslik, 'asama' => ArsivDosya::DOSYADA, 'kaynak' => 'yukleme']);

        $this->yenile();
        Notification::make()->title('Belge arşive eklendi')->success()->send();
    }

    private function sablondanUret(Firma $firma, array $data, array $ortak, string $varsayilanBaslik): void
    {
        $secim = $data['sablon'] ?? null;
        $kullanici = $this->kullaniciSablonu($secim);
        $baslik = filled($data['baslik'] ?? null) ? $data['baslik'] : $varsayilanBaslik;

        if ($secim === 'sistem') {
            $sonuc = ArsivUretici::uret($ortak['kategori'], $firma, $ortak['yil'], $ortak['baslangic_tarihi']);
            $sablonAdi = ArsivUretici::sablonlar()[$ortak['kategori']] ?? 'Sistem';
            $alanlar = null;
        } elseif ($kullanici && Storage::disk('public')->exists($kullanici->dosya_yolu)) {
            $degerler = BelgeSablonMotoru::sistemDegerleri($firma, ['yil' => $ortak['yil'], 'tarih' => $ortak['baslangic_tarihi']]);

            foreach ($kullanici->yer_tutucular ?? [] as $a) {
                $girilen = $data['degerler'][BelgeSablonMotoru::formAnahtari($a)] ?? null;
                $degerler[$a] = filled($girilen) ? $girilen : ($degerler[$a] ?? null);
            }

            $sonuc = [
                'icerik' => BelgeSablonMotoru::doldur(Storage::disk('public')->path($kullanici->dosya_yolu), $kullanici->tur, $degerler),
                'dosya_adi' => Str::slug($baslik).'.'.$kullanici->tur,
            ];
            $sablonAdi = $kullanici->ad;
            $alanlar = array_intersect_key($degerler, array_flip($kullanici->yer_tutucular ?? []));
        } else {
            Notification::make()->title('Şablon seçin')->danger()->send();

            return;
        }

        if (is_string($sonuc)) {
            Notification::make()->title('Belge üretilemedi')->body($sonuc)->danger()->persistent()->send();

            return;
        }

        $yol = 'arsiv/'.$firma->id.'/'.now()->format('Y').'/'.Str::random(8).'-'.$sonuc['dosya_adi'];
        Storage::disk('public')->put($yol, $sonuc['icerik']);

        ArsivDosya::create($ortak + [
            'baslik' => $baslik,
            'dosya_adi' => $sonuc['dosya_adi'],
            'dosya_yolu' => $yol,
            'boyut' => strlen($sonuc['icerik']),
            'asama' => ArsivDosya::IMZA_BEKLIYOR,
            'kaynak' => $secim === 'sistem' ? 'sistem' : 'sablon',
            'sablon_adi' => $sablonAdi,
            'alanlar' => $alanlar,
        ]);

        $this->yenile();
        Notification::make()->title('Belge üretildi; imza bekliyor.')->body('İmzalandıktan sonra "Dosyaya ekle" ile arşive alın.')->success()->send();
    }

    /**
     * Yüklenen dosyaları arşiv klasörüne taşır: birden çok görsel (çok sayfalı
     * belge fotoğrafları) sırasıyla tek PDF'te birleşir; görsel dışı karışık
     * çoklu seçim tek ZIP olur; tek dosya olduğu gibi kalır.
     *
     * @param  array<int, string>  $yollar  public diskteki geçici yollar
     * @return array{dosya_adi: string, dosya_yolu: string, boyut: int}|null
     */
    private function yuklenenleriBirlestir(array $yollar, Firma $firma, string $baslik, array $adlar = []): ?array
    {
        $disk = Storage::disk('public');
        $yollar = array_values(array_filter($yollar, fn ($y) => is_string($y) && $disk->exists($y)));

        if (! $yollar) {
            return null;
        }

        $klasor = 'arsiv/'.$firma->id.'/'.now()->format('Y');
        $ad = Str::slug($baslik ?: 'belge') ?: 'belge';
        $gorselMi = fn (string $y) => in_array(strtolower(pathinfo($y, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);

        if (count($yollar) > 1 && collect($yollar)->every($gorselMi)) {
            $icerik = Pdf::loadView('pdf.arsiv-gorseller', [
                'baslik' => $baslik,
                'gorseller' => array_map(fn ($y) => $disk->path($y), $yollar),
            ])->setPaper('a4')->output();
            $ad .= '.pdf';
        } elseif (count($yollar) > 1) {
            $zipYolu = tempnam(sys_get_temp_dir(), 'arz').'.zip';
            $zip = new ZipArchive;
            $zip->open($zipYolu, ZipArchive::CREATE);
            foreach ($yollar as $i => $y) {
                $zip->addFile($disk->path($y), ($i + 1).'-'.($adlar[$y] ?? basename($y)));
            }
            $zip->close();
            $icerik = (string) file_get_contents($zipYolu);
            @unlink($zipYolu);
            $ad .= '.zip';
        } else {
            $ozgun = (string) (($adlar[$yollar[0]] ?? null) ?: basename($yollar[0]));
            $yol = $klasor.'/'.Str::random(8).'-'.(Str::slug(pathinfo($ozgun, PATHINFO_FILENAME)) ?: 'belge').'.'.strtolower(pathinfo($yollar[0], PATHINFO_EXTENSION));
            $disk->move($yollar[0], $yol);

            return ['dosya_adi' => $ozgun, 'dosya_yolu' => $yol, 'boyut' => $disk->size($yol)];
        }

        $yol = $klasor.'/'.Str::random(8).'-'.$ad;
        $disk->put($yol, $icerik);
        $disk->delete($yollar);

        return ['dosya_adi' => $ad, 'dosya_yolu' => $yol, 'boyut' => strlen($icerik)];
    }

    /*
    |--------------------------------------------------------------------------
    | Kayıt işlemleri
    |--------------------------------------------------------------------------
    */

    public function goruntule(?int $id): void
    {
        $this->goruntulenenId = $this->bul($id)?->id;
        unset($this->goruntulenen);
    }

    /** "İmza bekliyor" belgeyi resmî kayda çevirir; imzalı nüsha yüklenirse dosya onunla değişir. */
    public function dosyayaEkleAction(): Action
    {
        return Action::make('dosyayaEkle')
            ->label('Dosyaya ekle')
            ->modalHeading('Dosyaya ekle')
            ->modalDescription('Belge imzalandıysa imzalı nüshasının fotoğrafını / PDF\'ini yükleyin; yüklemezseniz üretilen dosya arşive alınır.')
            ->modalSubmitActionLabel('Dosyaya ekle')
            ->fillForm(fn (array $arguments) => ['baslangic_tarihi' => $this->bul($arguments['id'] ?? null)?->baslangic_tarihi?->toDateString() ?? now()->toDateString()])
            ->schema([
                FileUpload::make('dosyalar')->storeFileNamesIn('dosya_adlari')->label('İmzalı nüsha (isteğe bağlı)')->multiple()->disk('public')->directory('arsiv/gecici')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])->maxSize(20480)->maxFiles(30)->reorderable(),
                DatePicker::make('baslangic_tarihi')->label('Belge Tarihi')->native(false)->displayFormat('d.m.Y')->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $d = $this->bul($arguments['id'] ?? null);

                if (! $d) {
                    return;
                }

                $guncelle = ['asama' => ArsivDosya::DOSYADA, 'baslangic_tarihi' => $data['baslangic_tarihi']];

                if ($yeni = $this->yuklenenleriBirlestir((array) ($data['dosyalar'] ?? []), $d->firma, $d->etiket(), (array) ($data['dosya_adlari'] ?? []))) {
                    Storage::disk('public')->delete($d->dosya_yolu);
                    $guncelle += $yeni;
                }

                $d->update($guncelle);
                $this->yenile();
                Notification::make()->title('Belge dosyaya eklendi')->success()->send();
            });
    }

    public function duzenleAction(): Action
    {
        return Action::make('duzenle')
            ->modalHeading('Kaydı düzelt')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments): array {
                $d = $this->bul($arguments['id'] ?? null);

                return $d ? [
                    'firma_id' => $d->firma_id, 'kategori' => ArsivKurali::kategori($d->kategori)['anahtar'], 'baslik' => $d->etiket(),
                    'kisi_adi' => $d->kisi_adi, 'yil' => $d->yil, 'versiyon' => $d->versiyon, 'aciklama' => $d->aciklama,
                    'baslangic_tarihi' => $d->baslangic_tarihi?->toDateString(), 'gecerlilik_sonu' => $d->gecerlilik_sonu?->toDateString(),
                ] : [];
            })
            ->schema(fn () => [
                Grid::make(2)->schema([
                    Select::make('firma_id')->label('Firma')->options($this->firmalar)->required()->searchable()->live(),
                    Select::make('kategori')->label('Kategori')->required()->live()
                        ->options($this->kategoriSecenekleri()),
                    TextInput::make('baslik')->label('Başlık')->required()->maxLength(160),
                    TextInput::make('versiyon')->label('Versiyon')->maxLength(20),
                ]),
                ...$this->kategoriAlanlari(),
                Textarea::make('aciklama')->label('Not')->rows(2),
                FileUpload::make('dosya')->storeFileNamesIn('dosya_adi_ozgun')->label('Yeni dosya (boş bırakılırsa mevcut dosya korunur)')
                    ->disk('public')->directory('arsiv/gecici')->maxSize(20480),
            ])
            ->action(function (array $data, array $arguments): void {
                $d = $this->bul($arguments['id'] ?? null);

                if (! $d || ! array_key_exists((int) $data['firma_id'], $this->firmalar)) {
                    return;
                }

                $alanlar = collect($data)->only(['firma_id', 'kategori', 'baslik', 'kisi_adi', 'versiyon', 'aciklama', 'baslangic_tarihi', 'gecerlilik_sonu'])->all();
                $alanlar['yil'] = filled($data['yil'] ?? null) ? (int) $data['yil'] : null;

                $firma = Firma::find((int) $data['firma_id']);

                if (filled($data['dosya'] ?? null) && ($yeni = $this->yuklenenleriBirlestir([$data['dosya']], $firma, $data['baslik'], [$data['dosya'] => $data['dosya_adi_ozgun'] ?? null]))) {
                    Storage::disk('public')->delete($d->dosya_yolu);
                    $alanlar += $yeni;
                }

                $d->update($alanlar);
                $this->yenile();
                Notification::make()->title('Kayıt güncellendi')->success()->send();
            });
    }

    public function durumDegistir(int $id): void
    {
        $d = $this->bul($id);
        $d?->update(['aktif' => ! $d->aktif]);
        $this->yenile();
    }

    public function sil(int $id): void
    {
        $d = $this->bul($id);

        if ($d) {
            Storage::disk('public')->delete($d->dosya_yolu);
            $d->delete();
        }

        if ($this->goruntulenenId === $id) {
            $this->goruntulenenId = null;
        }

        $this->yenile();
        Notification::make()->title('Kayıt silindi')->success()->send();
    }

    public function indir(int $id)
    {
        $d = $this->bul($id);

        if (! $d || ! Storage::disk('public')->exists($d->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($d->dosya_yolu, $d->dosya_adi);
    }

    /*
    |--------------------------------------------------------------------------
    | Belge Şablonları / Hatırlatma Ayarları
    |--------------------------------------------------------------------------
    */

    public function sablonlarAction(): Action
    {
        return Action::make('sablonlar')
            ->label('Belge Şablonları')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->modalHeading('Belge Şablonları')
            ->modalDescription('Kendi Word (.docx) veya Excel (.xlsx) şablonunuzu yükleyin. Belgede {{isyeri.unvan}} gibi yer tutucular kullanın; sistemde karşılığı olanlar otomatik dolar, diğerleri üretirken sorulur.')
            ->modalContent(fn () => view('filament.pages.partials.arsiv-sablonlar', ['sablonlar' => $this->sablonlar]))
            ->modalSubmitActionLabel('Şablonu Yükle')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('ad')->label('Şablon adı')->required()->maxLength(120),
                    Select::make('kategori')->label('Kategori')->placeholder('Tüm kategoriler')
                        ->options($this->kategoriSecenekleri()),
                ]),
                FileUpload::make('dosya')->label('Şablon dosyası (.docx / .xlsx)')->required()
                    ->disk('public')->directory('belge-sablonlari')->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240),
            ])
            ->action(function (array $data): void {
                $disk = Storage::disk('public');
                $tur = BelgeSablonMotoru::tur((string) $data['dosya']);

                if (! $tur) {
                    $disk->delete($data['dosya']);
                    Notification::make()->title('Yalnız .docx ve .xlsx şablonlar desteklenir')->danger()->send();

                    return;
                }

                $yerTutucular = BelgeSablonMotoru::yerTutucular($disk->path($data['dosya']), $tur);

                BelgeSablonu::create([
                    'user_id' => Filament::auth()->id(),
                    'ad' => $data['ad'],
                    'kategori' => $data['kategori'] ?? null,
                    'dosya_adi' => basename($data['dosya']),
                    'dosya_yolu' => $data['dosya'],
                    'tur' => $tur,
                    'yer_tutucular' => $yerTutucular,
                ]);

                $this->yenile();
                Notification::make()
                    ->title('Şablon yüklendi')
                    ->body($yerTutucular ? count($yerTutucular).' alan bulundu: '.implode(', ', $yerTutucular) : 'Şablonda {{alan}} biçiminde yer tutucu bulunamadı; belge olduğu gibi kopyalanır.')
                    ->success()->send();
            });
    }

    public function sablonSil(int $id): void
    {
        $s = BelgeSablonu::query()->where('user_id', Filament::auth()->id())->find($id);

        if ($s) {
            Storage::disk('public')->delete($s->dosya_yolu);
            $s->delete();
        }

        $this->yenile();
    }

    public function sablonIndir(int $id)
    {
        $s = BelgeSablonu::query()->where('user_id', Filament::auth()->id())->find($id);

        return $s && Storage::disk('public')->exists($s->dosya_yolu) ? Storage::disk('public')->download($s->dosya_yolu, $s->dosya_adi) : null;
    }

    public function hatirlatmaAction(): Action
    {
        $takipli = fn () => collect(config('arsiv.kategoriler'))->filter(fn ($k) => $k['kural'] !== 'kayit');

        return Action::make('hatirlatma')
            ->label('Hatırlatma Ayarları')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('gray')
            ->modalHeading('Hatırlatma Ayarları')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn () => [
                'yaklasan_gun' => KullaniciAyarlari::arsivYaklasanGun(),
                'takip' => $takipli()->keys()->diff(KullaniciAyarlari::arsivHaric())->values()->all(),
            ])
            ->schema([
                TextInput::make('yaklasan_gun')->label('Kaç gün kala "yaklaşıyor" uyarısı çıksın?')->numeric()->minValue(1)->maxValue(365)->required()->suffix('gün'),
                CheckboxList::make('takip')
                    ->label('Takip edilen kategoriler')
                    ->helperText('İşareti kaldırılan kategoride eksik / gecikmiş uyarısı çıkmaz (belgeler arşivde kalır).')
                    ->options(fn () => $takipli()->map(fn ($k) => $k['ad'])->all())
                    ->columns(2),
            ])
            ->action(function (array $data) use ($takipli): void {
                KullaniciAyarlari::kaydet(Filament::auth()->user(), ['arsiv' => [
                    'yaklasan_gun' => max(1, min(365, (int) $data['yaklasan_gun'])),
                    'haric' => $takipli()->keys()->diff($data['takip'] ?? [])->values()->all(),
                ]]);

                $this->yenile();
                Notification::make()->title('Hatırlatma ayarları kaydedildi')->success()->send();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Excel rapor (Tüm Belgeler)
    |--------------------------------------------------------------------------
    */

    public function excelRapor()
    {
        ExcelBellek::artir();
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Arşiv');
        $s->fromArray(['Firma', 'Belge', 'Kategori', 'Kişi', 'Yıl', 'Dosya adı', 'Versiyon', 'Belge tarihi', 'Geçerlilik sonu', 'Durum', 'Not', 'Boyut'], null, 'A1');
        $s->getStyle('A1:L1')->getFont()->setBold(true);
        $s->getStyle('A1:L1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        foreach ($this->dokumanlar->values() as $n => $d) {
            $s->fromArray([
                $d->firma?->unvan, $d->etiket(), $d->kategoriEtiketi(), $d->kisi_adi, $d->yil, $d->dosya_adi, $d->versiyon,
                $d->baslangic_tarihi?->format('d.m.Y'), $d->gecerlilik_sonu?->format('d.m.Y'), $d->durumEtiketi(), $d->aciklama, $d->boyutEtiketi(),
            ], null, 'A'.($n + 2));
        }
        foreach (range('A', 'L') as $c) {
            $s->getColumnDimension($c)->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'dok').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'arsiv-raporu.xlsx');
    }
}
