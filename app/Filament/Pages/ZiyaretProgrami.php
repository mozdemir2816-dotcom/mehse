<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Pages\Concerns\PortfoyAjandasi;
use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami as ZiyaretProgramiModel;
use App\Support\GeminiZiyaretDanismani;
use App\Support\KomutaFirma;
use App\Support\ZiyaretOtomatikPlanlayici;
use App\Support\ZiyaretProgramiUretici;
use App\Support\YasalZiyaretSuresi;
use App\Support\ZiyaretTakvimi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Saha & Ziyaret Planlama Merkezi (29.10.2026): eski "Ziyaret Programı" (firma bazlı 12 aylık plan) ile eski "Saha Takvimi"
 * (portföy ajandası) tek ekranda, üstte görünüm seçici. Yasal asgari süre firma seçilince otomatik hesaplanır.
 *
 * Ziyaret Programı — isgpratik'te ekran görüntüsü yok; kullanıcı onayıyla
 * BASİTLEŞTİRİLMİŞ liste (firma+yıl başına 12 aylık satır) olarak kuruldu.
 * isgpratik'teki tam takvim/sürükle-bırak/çoklu-firma-OSGB arayüzü kapsam
 * dışı bırakıldı.
 */
class ZiyaretProgrami extends Page
{
    use PortfoyAjandasi;
    use SinirliErisim;

    protected string $view = 'filament.pages.ziyaret-programi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Günlük Saha İşi';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'ziyaret-programi';

    protected static ?string $title = 'Saha & Ziyaret Planlama Merkezi';

    protected static ?string $navigationLabel = 'Ziyaret Planlama';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** Görünüm seçici: 'ajanda' = Genel Ajanda (portföy takvimi), 'plan' = Firma Bazlı Yıllık Plan (12 ay). */
    #[Url(as: 'gorunum')]
    public string $gorunum = 'plan';

    public int $yil;

    public ?string $takvimAy = null;

    public ?string $takvimSeciliTarih = null;

    /** "Ziyarette Yapılacaklar" listesinin gösterdiği ay (0–11). */
    public int $yapilacakAy = 0;

    /** Yapılacaklar penceresi (modal) açık mı. */
    public bool $yapilacakModalAcik = false;

    /** Bir ay için öne çıkarılabilecek kritik görev sayısı. */
    public const KRITIK_GOREV_SINIRI = 2;

    public function mount(): void
    {
        $this->yil = (int) now()->format('Y');
        $this->takvimSeciliTarih = now()->toDateString();
        $this->yapilacakAy = now()->month - 1;

        $this->ajandaBaslat();

        if (! in_array($this->gorunum, ['ajanda', 'plan', 'harita'], true)) {
            $this->gorunum = 'plan';
        }

        // ?firma= yoksa ortak firma bağlamı (App\Support\KomutaFirma) açılır.
        if ($firmaId = request()->integer('firma') ?: KomutaFirma::baslangic($this->firmalar)) {
            $this->firmaId = $firmaId;
            KomutaFirma::sec($firmaId);
        }

        // Ana sayfa widget'ından gelen bağlantı: ?yil=2026&ay=10 (1–12).
        if ($yil = request()->integer('yil')) {
            $this->yil = $yil;
        }
        if ($ay = request()->integer('ay')) {
            $this->yapilacakAy = max(0, min(11, $ay - 1));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function program(): ?ZiyaretProgramiModel
    {
        return $this->firma ? ZiyaretProgramiModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    #[Computed]
    public function amacKategorileri(): array
    {
        return config('isg.ziyaret_programi.amac_kategorileri');
    }

    /**
     * Seçili firmanın tarihli ziyaret günleri — Saha Takvimi sayfasıyla AYNI
     * kaynaktan (ZiyaretTakvimi) türetilir, ayrı bir
     * gruplama mantığı tekrarlanmaz; sadece bu firmaya süzülür.
     *
     * @return array<string, array<int, array{firma: Firma, amac: ?string, sure_saat: mixed, durum: string}>>
     */
    #[Computed]
    public function takvimGunler(): array
    {
        if (! $this->firmaId) {
            return [];
        }

        $gunler = [];

        foreach (ZiyaretTakvimi::gunlukGruplar(Filament::auth()->id()) as $tarih => $oGunkuZiyaretler) {
            $buFirmaninkiler = array_values(array_filter(
                $oGunkuZiyaretler,
                fn (array $z) => $z['firma']?->id === $this->firmaId
            ));

            if ($buFirmaninkiler !== []) {
                $gunler[$tarih] = $buFirmaninkiler;
            }
        }

        return $gunler;
    }

    /**
     * Firmanın aynı yıla ait Yıllık Çalışma/Eğitim Planı — yoksa standart
     * şablonla açılır, böylece ziyaret yapılacaklar listesi hep dolu gelir.
     */
    #[Computed]
    public function yillikPlan(): ?YillikPlan
    {
        return $this->firma ? YillikPlan::firmaYilIcin($this->firma, $this->yil) : null;
    }

    /** Seçili ayın ziyaret yapılacakları (yıllık planda o ay P/G olan maddeler). */
    #[Computed]
    public function yapilacaklar(): array
    {
        return $this->yillikPlan?->ayinYapilacaklari($this->yapilacakAy) ?? [];
    }

    /** @return array<int, array{toplam: int, yapilan: int}> ay indeksi => sayılar */
    #[Computed]
    public function aylikYapilacakSayilari(): array
    {
        return collect(range(0, 11))
            ->mapWithKeys(function (int $ay): array {
                $liste = collect($this->yillikPlan?->ayinYapilacaklari($ay) ?? []);

                return [$ay => ['toplam' => $liste->count(), 'yapilan' => $liste->where('gerceklesti', true)->count()]];
            })
            ->all();
    }

    /** Ayı seçer ve "Yapılacaklar" penceresini (modal) açar — uzun liste artık sayfada değil, pencerede. */
    public function yapilacakAySec(int $ay): void
    {
        $this->yapilacakAy = max(0, min(11, $ay));
        $this->yapilacakModalAcik = true;
        unset($this->yapilacaklar);
    }

    public function yapilacakModalKapat(): void
    {
        $this->yapilacakModalAcik = false;
    }

    /** Ajanda gün kartındaki "Yıllık planı aç": firma seçilip 12 aylık plan görünümüne geçilir. */
    public function planAc(int $firmaId): void
    {
        if (! array_key_exists($firmaId, $this->firmalar)) {
            return;
        }

        $this->firmaId = $firmaId;
        $this->gorunum = 'plan';
        $this->updatedFirmaId();
    }

    /**
     * Ayın öne çıkan (kritik) görevleri: o ayın ilk ziyaret satırında "alan:index" olarak en fazla 2 tane saklanır.
     * Satırda kompakt etiket olarak görünür; tüm liste pencerede.
     */
    public function kritikGorevToggle(string $alan, int $index): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! in_array($alan, ['faaliyetler', 'egitimler'], true) || ! isset($aylar[$this->yapilacakAy])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$this->yapilacakAy]);
        $anahtar = $alan.':'.$index;
        $kritik = array_values(array_filter((array) ($girdiler[0]['kritik'] ?? [])));

        if (in_array($anahtar, $kritik, true)) {
            $kritik = array_values(array_diff($kritik, [$anahtar]));
        } elseif (count($kritik) >= self::KRITIK_GOREV_SINIRI) {
            Notification::make()->title('En fazla '.self::KRITIK_GOREV_SINIRI.' öne çıkan görev')->body('Önce birini kaldırın.')->warning()->send();

            return;
        } else {
            $kritik[] = $anahtar;
        }

        $girdiler[0]['kritik'] = $kritik;
        $aylar[$this->yapilacakAy] = $girdiler;
        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->ayKritikGorevleri);
    }

    /**
     * Her ayın seçili öne çıkan görevleri: ay indeksi => [{alan, index, baslik, gerceklesti}].
     *
     * @return array<int, array<int, array{alan: string, index: int, baslik: string, gerceklesti: bool}>>
     */
    #[Computed]
    public function ayKritikGorevleri(): array
    {
        $sonuc = [];

        foreach ($this->program?->ziyaretler ?? [] as $ayIndex => $ay) {
            $kritik = (array) (ZiyaretProgramiModel::ayGirdileri($ay)[0]['kritik'] ?? []);

            if (! $kritik) {
                continue;
            }

            $liste = collect($this->yillikPlan?->ayinYapilacaklari((int) $ayIndex) ?? []);

            foreach ($kritik as $anahtar) {
                [$alan, $index] = array_pad(explode(':', (string) $anahtar, 2), 2, null);
                $madde = $liste->first(fn (array $m) => $m['alan'] === $alan && (string) $m['index'] === (string) $index);

                if ($madde) {
                    $sonuc[(int) $ayIndex][] = ['alan' => $alan, 'index' => (int) $index, 'baslik' => $madde['baslik'], 'gerceklesti' => (bool) $madde['gerceklesti']];
                }
            }
        }

        return $sonuc;
    }

    /** Listeden "Gerçekleşti" işareti — Yıllık Plan'daki ilgili ayın G hücresine yazılır. */
    public function yapilacakGerceklesti(string $alan, int $index): void
    {
        $this->yillikPlan?->gerceklestiDegistir($alan, $index, $this->yapilacakAy);
        unset($this->yillikPlan, $this->yapilacaklar, $this->aylikYapilacakSayilari, $this->ayKritikGorevleri);
    }

    #[Computed]
    public function takvimGosterilenAy(): string
    {
        return $this->takvimAy ?: $this->yil.'-01';
    }

    public function updatedFirmaId(): void
    {
        KomutaFirma::sec($this->firmaId);
        unset($this->firma, $this->yasalDakika, $this->ayKritikGorevleri, $this->program, $this->takvimGunler, $this->yillikPlan, $this->yapilacaklar, $this->aylikYapilacakSayilari);
        $this->takvimAy = null;
    }

    public function updatedYil(): void
    {
        unset($this->ayKritikGorevleri, $this->program, $this->takvimGunler, $this->yillikPlan, $this->yapilacaklar, $this->aylikYapilacakSayilari);
        $this->takvimAy = null;
    }

    public function takvimAyDegistir(int $fark): void
    {
        $this->takvimAy = Carbon::parse($this->takvimGosterilenAy().'-01')->addMonths($fark)->format('Y-m');
    }

    public function takvimGunSec(string $tarih): void
    {
        $this->takvimSeciliTarih = $tarih;
    }

    /*
    |--------------------------------------------------------------------------
    | Ay satırı düzenleme
    |--------------------------------------------------------------------------
    */

    public function ayGuncelle(int $ayIndex, int $satirIndex, string $alan, mixed $deger): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);

        if (! isset($girdiler[$satirIndex])) {
            return;
        }

        $girdiler[$satirIndex][$alan] = $deger;

        // Tarih girilince süre boşsa yasal asgari süre varsayılan olarak yazılır (elle değiştirilebilir).
        if ($alan === 'tarih' && filled($deger) && blank($girdiler[$satirIndex]['sure_saat'] ?? null) && $this->yasalDakika > 0) {
            $girdiler[$satirIndex]['sure_saat'] = YasalZiyaretSuresi::saat($this->yasalDakika);
        }

        $aylar[$ayIndex] = $girdiler;
        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);

        if ($alan === 'tarih' && filled($deger)) {
            $this->takvimAy = Carbon::parse($deger)->format('Y-m');
            $this->takvimSeciliTarih = $deger;
        }
    }

    public function gorunumSec(string $gorunum): void
    {
        if (in_array($gorunum, ['ajanda', 'plan', 'harita'], true)) {
            $this->gorunum = $gorunum;
        }
    }

    /** Harita & Rota görünümü için seçili günün (boşsa tüm aktif portföyün) optimize edilmiş durakları. */
    #[Computed]
    public function haritaRotasi(): array
    {
        $tarih = $this->takvimSeciliTarih ?: now()->toDateString();
        $gruplar = ZiyaretTakvimi::gunlukGruplar(Filament::auth()->id());
        $gununFirmalari = collect($gruplar[$tarih] ?? [])->pluck('firma')->filter()->unique('id');

        $kaynak = $gununFirmalari->isNotEmpty()
            ? $gununFirmalari
            : Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->get();

        $duraklar = $kaynak->map(function (Firma $f): array {
            $k = \App\Support\HaritaRotaPlanlayici::firmaKoordinati($f);

            return [
                'firma_id' => $f->id,
                'unvan' => $f->secimEtiketi(),
                'adres' => trim(implode(', ', array_filter([$f->adres, $f->ilce, $f->il]))),
                'lat' => $k['lat'],
                'lng' => $k['lng'],
                'bolge' => $k['bolge'],
            ];
        })->all();

        $sirali = \App\Support\HaritaRotaPlanlayici::rotayiSirala($duraklar);

        return [
            'tarih_etiket' => $gununFirmalari->isNotEmpty()
                ? Carbon::parse($tarih)->format('d.m.Y').' planlı ziyaretler'
                : 'Bu günde plan yok — tüm aktif portföy',
            'duraklar' => $sirali,
            'gmaps_url' => \App\Support\HaritaRotaPlanlayici::googleMapsRotaUrl($sirali),
        ];
    }

    /** Seçili firmanın aylık yasal asgari süresi (dakika): KATİP süresi varsa o, yoksa çalışan × tehlike sınıfı dakikası. */
    #[Computed]
    public function yasalDakika(): int
    {
        return $this->firma ? YasalZiyaretSuresi::dakika($this->firma) : 0;
    }

    /**
     * Yasal süreyi planın tüm aylarının İLK ziyaret satırına yazar — yalnız süresi boş olanlara; elle girilmiş
     * süreye dokunmaz. Süre saat cinsinden saklanır (200 dk → 3.33).
     */
    public function yasalSureyiYaz(): void
    {
        $p = $this->program();

        if (! $p || $this->yasalDakika <= 0) {
            Notification::make()->title('Önce çalışan sayısı olan bir firma seçin')->warning()->send();

            return;
        }

        $aylar = $p->ziyaretler ?? [];
        $yazilan = 0;

        foreach ($aylar as $ayIndex => $ay) {
            $girdiler = ZiyaretProgramiModel::ayGirdileri($ay);

            if (isset($girdiler[0]) && blank($girdiler[0]['sure_saat'] ?? null)) {
                $girdiler[0]['sure_saat'] = YasalZiyaretSuresi::saat($this->yasalDakika);
                $aylar[$ayIndex] = $girdiler;
                $yazilan++;
            }
        }

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);

        Notification::make()->title($yazilan.' ayın süresine '.YasalZiyaretSuresi::etiket($this->yasalDakika).' yazıldı')
            ->body('Elle girilmiş sürelere dokunulmadı.')->success()->send();
    }

    public function durumDegistir(int $ayIndex, int $satirIndex): void
    {
        $this->program()?->durumIlerlet($ayIndex, $satirIndex);
        unset($this->program, $this->takvimGunler);
    }

    public function ziyaretEkle(int $ayIndex): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);
        $girdiler[] = ZiyaretProgramiModel::bosGirdi();
        $aylar[$ayIndex] = $girdiler;

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    public function ziyaretSil(int $ayIndex, int $satirIndex): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);

        if (count($girdiler) <= 1 || ! isset($girdiler[$satirIndex])) {
            return;
        }

        unset($girdiler[$satirIndex]);
        $aylar[$ayIndex] = array_values($girdiler);

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    /**
     * Kutular değişince zaten tek tek kaydedilir (ayGuncelle); buton, son
     * düzenlenen kutunun değişikliği işlendikten sonra kaydı teyit eder.
     */
    public function kaydet(): void
    {
        if (! $this->program()) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        unset($this->program, $this->takvimGunler);

        Notification::make()->title('Ziyaret programı kaydedildi')->success()->send();
    }

    public function aiAmacOner(int $ayIndex, int $satirIndex): void
    {
        $p = $this->program();

        if (! $p || ! $this->firma) {
            return;
        }

        $ayAdi = ZiyaretProgramiModel::AYLAR[$ayIndex] ?? null;

        if (! $ayAdi) {
            return;
        }

        $oneri = GeminiZiyaretDanismani::oner($ayAdi, $this->firma->nace_aciklama, $this->firma->tehlikeSinifiEtiketi());

        if ($oneri) {
            $this->ayGuncelle($ayIndex, $satirIndex, 'amac', $oneri);
        } else {
            Notification::make()->title('AI önerisi alınamadı')->warning()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ajandaZiyaretEkle')
                ->label('+ Seçili Güne Firma Ziyareti Ekle')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->visible(fn () => $this->gorunum === 'ajanda')
                ->modalHeading(fn () => 'Ajandaya Ziyaret Ekle — '.Carbon::parse($this->seciliTarih ?: now()->toDateString())->format('d.m.Y'))
                ->modalSubmitActionLabel('Ziyareti Ekle')
                ->schema([
                    \Filament\Forms\Components\Select::make('firma_id')
                        ->label('Firma Seçin')
                        ->options(fn () => $this->firmalar)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('tarih')
                        ->label('Ziyaret Tarihi')
                        ->default(fn () => $this->seciliTarih ?: now()->toDateString())
                        ->required(),
                    \Filament\Forms\Components\Select::make('amac')
                        ->label('Ziyaret Amacı')
                        ->options(fn () => collect($this->amacKategorileri)->mapWithKeys(fn ($v, $k) => is_int($k) ? [$v => $v] : [$k => $v])->all())
                        ->default('Genel Saha Gözetimi'),
                ])
                ->action(function (array $data): void {
                    $firma = Firma::where('user_id', Filament::auth()->id())->find($data['firma_id']);
                    if (! $firma) {
                        return;
                    }

                    $tarih = Carbon::parse($data['tarih']);
                    $program = ZiyaretProgramiModel::firmaYilIcin($firma, $tarih->year);
                    $aylar = $program->ziyaretler ?? array_fill(0, 12, [ZiyaretProgramiModel::bosGirdi()]);
                    $ayIndex = $tarih->month - 1;
                    $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex] ?? null);

                    $yeniGirdi = [
                        'tarih' => $tarih->toDateString(),
                        'amac' => $data['amac'] ?? 'Genel Saha Gözetimi',
                        'durum' => 'planlandi',
                        'sure_saat' => YasalZiyaretSuresi::saat(YasalZiyaretSuresi::dakika($firma)) ?: 2.0,
                        'notlar' => null,
                    ];

                    if (count($girdiler) === 1 && blank($girdiler[0]['tarih'] ?? null)) {
                        $girdiler[0] = $yeniGirdi;
                    } else {
                        $girdiler[] = $yeniGirdi;
                    }

                    $aylar[$ayIndex] = $girdiler;
                    $program->update(['ziyaretler' => $aylar]);

                    $this->seciliTarih = $tarih->toDateString();
                    $this->gosterilenAy = $tarih->format('Y-m');
                    unset($this->gunlukGruplar, $this->ozet, $this->gunlukListe, $this->ayRozetleri, $this->program, $this->takvimGunler);

                    Notification::make()->title($firma->unvan.' ziyaret takvimine eklendi')->body($tarih->format('d.m.Y'))->success()->send();
                }),

            Action::make('otomatikDoldur')
                ->label('Boş Ayları Otomatik Doldur')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->modalHeading('Boş ayları otomatik doldur (Kapasite & Meşgul Gün Filtreli)')
                ->modalDescription('Seçtiğiniz yıl için elle veya daha önce doldurulmuş aylara dokunulmaz. Hâlâ boş olan aylar İSG-KATİP süresine, günlük kapasite sınırına, Cumartesi tercihine ve meşgul/tatil günlerine göre otomatik dağıtılır.')
                ->modalSubmitActionLabel('Doldur')
                ->schema([
                    \Filament\Forms\Components\Select::make('gunluk_max_saat')
                        ->label('Günlük Maksimum Ziyaret Kapasitesi')
                        ->options([
                            '4' => '4 Saat / Gün (Yarım Gün)',
                            '6' => '6 Saat / Gün',
                            '8' => '8 Saat / Gün (1 Tam İş Günü)',
                            '10' => '10 Saat / Gün (Varsayılan)',
                            '12' => '12 Saat / Gün',
                        ])
                        ->default('10')
                        ->required(),
                    \Filament\Forms\Components\Toggle::make('cumartesi_dahil')
                        ->label('Cumartesi Günlerini Ziyaret Takvimine Dahil Et')
                        ->default(false),
                    \Filament\Forms\Components\TextInput::make('mesgul_gunler')
                        ->label('Meşgul / Kapalı / Tatil Günleri (İsteğe bağlı, virgülle ayırın)')
                        ->placeholder('Örn: 29.10.2026, 01.01.2026'),
                ])
                ->action(function (array $data): void {
                    $mesgul = collect(explode(',', (string) ($data['mesgul_gunler'] ?? '')))
                        ->map(fn ($t) => trim($t))
                        ->filter()
                        ->map(function (string $t): ?string {
                            try {
                                return Carbon::parse($t)->toDateString();
                            } catch (\Throwable) {
                                return null;
                            }
                        })
                        ->filter()
                        ->values()
                        ->all();

                    $sonuc = ZiyaretOtomatikPlanlayici::calistir(
                        $this->yil,
                        null,
                        (bool) ($data['cumartesi_dahil'] ?? false),
                        $mesgul,
                        (float) ($data['gunluk_max_saat'] ?? 10.0),
                    );

                    unset($this->program, $this->gunlukGruplar, $this->ozet, $this->gunlukListe, $this->ayRozetleri, $this->takvimGunler);

                    Notification::make()
                        ->title($sonuc['firma'].' firmada '.$sonuc['doldurulan_ay'].' ay dolduruldu')
                        ->body($sonuc['atlanan_ay'].' ay zaten planlıydı, dokunulmadı.'.($sonuc['detay'] ? ' Doldurulan: '.implode(', ', array_slice($sonuc['detay'], 0, 6)).(count($sonuc['detay']) > 6 ? '…' : '.') : ''))
                        ->success()
                        ->send();
                }),
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->gorunum === 'plan' && $this->program() !== null)
                ->action(fn () => $this->kaydet()),
            Action::make('pdf')
                ->label('Çıktı İndir (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->gorunum === 'plan' && $this->program() !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => ZiyaretProgramiUretici::pdf($this->program())),
        ];
    }
}
