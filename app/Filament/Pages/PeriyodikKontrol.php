<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsEkipmani;
use App\Models\IsEkipmaniKontrolu;
use App\Models\PeriyodikKontrol as PeriyodikKontrolKapsayici;
use App\Filament\Support\ImzaSecenegi;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Support\EkipmanKatalogu;
use App\Support\GunlukEkipmanKontrolUretici;
use App\Support\HaftalikEkipmanKontrolUretici;
use App\Support\PeriyodikKontrolUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Ekipman & Periyodik Kontrol Motoru — isgpratik. Her iş ekipmanı kategori +
 * tip ile tanımlanır (tip seçilince yasal standart / deney / periyot otomatik
 * gelir); son muayene tarihi girilince "sonraki vize" hesaplanır. Vize durumu
 * (geçerli / yaklaşan / süresi dolan) KPI kartlarında ve listede gösterilir.
 */
class PeriyodikKontrol extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.periyodik-kontrol';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Dışı Kayıtlar';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'periyodik-kontrol';

    protected static ?string $title = 'Ekipman & Periyodik Kontrol Motoru';

    protected static ?string $navigationLabel = 'Periyodik Kontrol';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public string $kategoriFiltre = '';

    public string $arama = '';

    public string $durumFiltre = '';

    /** @var array<int, array<string, mixed>> DB'den yüklenen, satır içi düzenlenebilen ekipman listesi */
    public array $satirlar = [];

    public ?string $genelNot = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

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
    public function kapsayici(): ?PeriyodikKontrolKapsayici
    {
        return $this->firma ? PeriyodikKontrolKapsayici::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function kategoriler(): array
    {
        return config('isg.periyodik_kontrol.kategoriler', []);
    }

    #[Computed]
    public function sonuclar(): array
    {
        return config('isg.periyodik_kontrol.sonuclar', []);
    }

    #[Computed]
    public function periyotSecenekleri(): array
    {
        return config('isg.periyodik_kontrol.periyot_secenekleri', []);
    }

    /** @return \Illuminate\Support\Collection<int, IsEkipmani> Ham (filtresiz) ekipman listesi — KPI için. */
    #[Computed]
    public function tumEkipmanlar()
    {
        return $this->firma?->isEkipmanlari()->where('aktif', true)->get() ?? collect();
    }

    /** KPI kartları — filtreden bağımsız. */
    #[Computed]
    public function kpi(): array
    {
        $hepsi = $this->tumEkipmanlar;

        return [
            'toplam' => $hepsi->count(),
            'gecerli' => $hepsi->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'gecerli')->count(),
            'yaklasan' => $hepsi->filter(fn (IsEkipmani $e) => $e->vizeDurumu() === 'yaklasan')->count(),
            'dolmus' => $hepsi->filter(fn (IsEkipmani $e) => in_array($e->vizeDurumu(), ['dolmus', 'bekliyor'], true))->count(),
        ];
    }

    /** Kategori sekmelerinde gösterilecek sayaçlar. */
    #[Computed]
    public function kategoriSayaclari(): array
    {
        return $this->tumEkipmanlar->groupBy('kategori')->map->count()->all();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->kapsayici, $this->tumEkipmanlar, $this->kpi, $this->kategoriSayaclari);
        $this->satirlariYukle();
        $this->genelNot = $this->kapsayici()?->genel_not;
    }

    public function updatedKategoriFiltre(): void
    {
        $this->satirlariYukle();
    }

    public function updatedDurumFiltre(): void
    {
        unset($this->portfoyEkipmanlari);
        $this->satirlariYukle();
    }

    public function updatedArama(): void
    {
        unset($this->portfoyEkipmanlari);
        $this->satirlariYukle();
    }

    private function satirlariYukle(): void
    {
        $q = $this->firma?->isEkipmanlari()->where('aktif', true)
            ->when($this->kategoriFiltre, fn ($q) => $q->where('kategori', $this->kategoriFiltre))
            ->when(filled($this->arama), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('ekipman_adi', 'like', "%{$this->arama}%")
                ->orWhere('seri_no', 'like', "%{$this->arama}%")
                ->orWhere('marka_model', 'like', "%{$this->arama}%")))
            ->orderBy('kategori')->orderBy('ekipman_adi')
            ->get();

        $satirlar = ($q ?? collect())
            ->filter(fn (IsEkipmani $e) => ! $this->durumFiltre || $e->vizeDurumu() === $this->durumFiltre)
            ->map(fn (IsEkipmani $e) => [
                'id' => $e->id,
                'kategori' => $e->kategori,
                'kategori_adi' => $e->kategoriAdi(),
                'ekipman_adi' => $e->ekipman_adi,
                'tip' => $e->tip,
                'seri_no' => $e->seri_no,
                'marka_model' => $e->marka_model,
                'konum' => $e->konum,
                'kapasite' => $e->kapasite,
                'yasal_standart' => $e->yasal_standart,
                'muayene_periyodu_ay' => $e->muayene_periyodu_ay,
                'son_muayene_tarihi' => $e->son_muayene_tarihi?->toDateString(),
                'sonraki_vize_tarihi' => $e->sonraki_vize_tarihi?->toDateString(),
                'muayene_yapan' => $e->muayene_yapan,
                'rapor_no' => $e->rapor_no,
                'sonuc' => $e->sonuc,
                'ozel_notlar' => $e->ozel_notlar,
                'vize_durumu' => $e->vizeDurumu(),
                'kalan_gun' => $e->kalanGun(),
            ])
            ->values()
            ->all();

        $this->satirlar = $satirlar;
    }

    /** Satır içi düzenlenen alanları DB'ye yaz; sonraki vize yeniden hesaplanır. */
    public function kaydet(bool $sessiz = false): void
    {
        if (! $this->firma) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        foreach ($this->satirlar as $s) {
            $ekipman = IsEkipmani::where('firma_id', $this->firma->id)->find($s['id']);

            if (! $ekipman) {
                continue;
            }

            $ekipman->fill([
                'son_muayene_tarihi' => $s['son_muayene_tarihi'] ?: null,
                'muayene_yapan' => $s['muayene_yapan'] ?: null,
                'rapor_no' => $s['rapor_no'] ?: null,
                'sonuc' => $s['sonuc'] ?? 'bekliyor',
                'muayene_periyodu_ay' => (int) ($s['muayene_periyodu_ay'] ?: 12),
            ]);

            // Son muayene tarihi elle temizlendiyse vize de sıfırlansın.
            if (empty($s['son_muayene_tarihi'])) {
                $ekipman->sonraki_vize_tarihi = null;
            } else {
                // Periyot ya da tarih değiştiyse sonraki vizeyi yeniden türet.
                $ekipman->sonraki_vize_tarihi = Carbon::parse($s['son_muayene_tarihi'])
                    ->addMonths($ekipman->muayene_periyodu_ay);
            }

            $ekipman->save();
        }

        $this->kapsayici()?->update(['genel_not' => $this->genelNot]);

        unset($this->tumEkipmanlar, $this->kpi, $this->kategoriSayaclari);
        $this->satirlariYukle();

        if (! $sessiz) {
            Notification::make()->title('Muayene bilgileri kaydedildi')->success()->send();
        }
    }

    public function ekipmanSil(int $id): void
    {
        $this->firma?->isEkipmanlari()->find($id)?->delete();
        unset($this->tumEkipmanlar, $this->kpi, $this->kategoriSayaclari);
        $this->satirlariYukle();
        Notification::make()->title('Ekipman silindi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->firma !== null && filled($this->satirlar))
                ->action(fn () => $this->kaydet()),

            Action::make('yeniEkipman')
                ->label('Yeni Ekipman Tanımla')
                ->icon('heroicon-o-plus')
                ->visible(fn () => $this->firma !== null)
                ->modalHeading('Yeni İş Ekipmanı Tanımla')
                ->modalDescription('Periyodik kontrol ve muayene takip künyesi. Katalogdaki ekipmanlar risk değerlendirmesine ve ekipman kontrol formuna da bağlanır.')
                ->schema([
                    Select::make('kategori')
                        ->label('Ekipman Kategorisi')
                        ->options(collect(config('isg.periyodik_kontrol.kategoriler'))->map(fn ($k) => $k['ad'])->all())
                        ->live()
                        ->required(),

                    Select::make('tip')
                        ->label('Ekipman Tipi')
                        ->options(fn (Get $get) => collect(config('isg.periyodik_kontrol.tipler.'.$get('kategori'), []))
                            ->pluck('ad', 'ad')->all())
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            $t = collect(config('isg.periyodik_kontrol.tipler.'.$get('kategori'), []))->firstWhere('ad', $state);

                            if ($t) {
                                $set('ekipman_adi', $t['ad']);
                                $set('muayene_periyodu_ay', (string) $t['periyot_ay']);
                            }
                        })
                        ->helperText('Listede yoksa boş bırakıp "Ekipman Tanımı" alanını elle doldurun.'),

                    Placeholder::make('standart_bilgi')
                        ->label('Yasal Standart / Deney')
                        ->content(function (Get $get) {
                            $t = collect(config('isg.periyodik_kontrol.tipler.'.$get('kategori'), []))->firstWhere('ad', $get('tip'));

                            return $t
                                ? new \Illuminate\Support\HtmlString('<strong>'.e($t['standart']).'</strong><br><span style="color:#666">'.e($t['deney']).'</span>')
                                : 'Tip seçilince otomatik gelir.';
                        }),

                    Select::make('muayene_periyodu_ay')
                        ->label('Muayene Periyodu (Ay)')
                        ->options(config('isg.periyodik_kontrol.periyot_secenekleri'))
                        ->default('12')
                        ->visible(fn (Get $get) => EkipmanKatalogu::periyodikMi($get('kategori')))
                        ->required(),

                    TextInput::make('ekipman_adi')->label('Ekipman Tanımı / İsmi')->required(),
                    TextInput::make('seri_no')->label('Seri No / Şasi / Plaka')->placeholder('Örn: VNC-2023-8874'),
                    TextInput::make('marka_model')->label('Marka / Model / Yıl')->placeholder('Örn: Linde H30D (2021)'),
                    TextInput::make('konum')->label('Bulunduğu Konum / Bölüm')->placeholder('Örn: Ana Depo / Pres Bölümü'),
                    TextInput::make('kapasite')->label('Kapasite / İşletme Değeri')->placeholder('Örn: 5 Ton / 500 Litre 11 Bar'),
                    DatePicker::make('son_muayene_tarihi')
                        ->label('Son Yapılan Muayene Tarihi (Varsa)')
                        ->visible(fn (Get $get) => EkipmanKatalogu::periyodikMi($get('kategori')))
                        ->helperText('Girilirse sonraki vize tarihi otomatik hesaplanır.'),
                    Textarea::make('ozel_notlar')->label('Özel Notlar / Emniyet Talimatları')->rows(2)
                        ->placeholder('Operatör ehliyeti zorunludur, çift halatlı tambur vb.'),
                ])
                ->action(fn (array $data) => $this->ekipmanKaydet($data)),

            Action::make('portfoy_excel')
                ->label('Tüm Firmalar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma === null && $this->portfoyEkipmanlari->isNotEmpty())
                ->action(fn () => PeriyodikKontrolUretici::portfoyExcel($this->portfoyEkipmanlari)),

            // Firma çıktıları tek menüde — başlık dar sütuna sıkışmasın.
            ActionGroup::make([
                Action::make('gunlukKontrolFormu')
                    ->label('Günlük Kontrol Formu')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->visible(fn () => $this->firma !== null && GunlukEkipmanKontrolUretici::sayfalar($this->firma) !== [])
                    ->tooltip('Her ekipman için ayrı sayfa: kullanım öncesi Uygun / Uygun Değil / Uygulanamaz, kullanıma uygunluk kararı ve imzalar')
                    ->action(fn () => GunlukEkipmanKontrolUretici::pdf($this->firma)),

                Action::make('kontrolFormu')
                    ->label('Haftalık Kontrol Formu')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn () => $this->firma !== null && EkipmanKatalogu::kontrolFormuIndeksleri($this->firma) !== [])
                    ->tooltip('Firmanın ekipmanlarına ait boş kullanım öncesi / haftalık kontrol formları (her tip ayrı sayfa)')
                    ->action(fn () => HaftalikEkipmanKontrolUretici::pdf(EkipmanKatalogu::kontrolFormuIndeksleri($this->firma), $this->firma->unvan)),

                Action::make('pdf')
                    ->label('Müfettiş Teftiş Paketi (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn () => $this->firma !== null && $this->tumEkipmanlar->isNotEmpty())
                    ->schema([ImzaSecenegi::alan()])
                    ->action(function () {
                        $this->kaydet(sessiz: true);

                        return PeriyodikKontrolUretici::pdf($this->kapsayici());
                    }),

                Action::make('excel')
                    ->label('Müfettiş Teftiş Paketi (Excel)')
                    ->icon('heroicon-o-table-cells')
                    ->visible(fn () => $this->firma !== null && $this->tumEkipmanlar->isNotEmpty())
                    ->schema([ImzaSecenegi::alan()])
                    ->action(function () {
                        $this->kaydet(sessiz: true);

                        return PeriyodikKontrolUretici::excel($this->kapsayici());
                    }),
            ])
                ->label('Çıktı Al')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->button(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tüm firmalar termin listesi (isgsuite "Periyodik Kontrol Sicili")
    |--------------------------------------------------------------------------
    */

    /**
     * Firma seçilmeden: tüm firmalardaki aktif ekipmanlar, en yakın termin
     * önce. Durum filtresi ve arama burada da geçerli.
     *
     * @return Collection<int, IsEkipmani>
     */
    #[Computed]
    public function portfoyEkipmanlari(): Collection
    {
        $aranan = mb_strtolower(trim($this->arama));

        return IsEkipmani::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->where('aktif', true)
            ->with('firma')
            ->orderByRaw('sonraki_vize_tarihi is null')
            ->orderBy('sonraki_vize_tarihi')
            ->get()
            ->filter(fn (IsEkipmani $e) => ! $this->durumFiltre || $e->vizeDurumu() === $this->durumFiltre)
            ->filter(fn (IsEkipmani $e) => $aranan === '' || str_contains(
                mb_strtolower(implode(' ', [$e->ekipman_adi, $e->seri_no, $e->konum, $e->marka_model, $e->firma?->unvan])),
                $aranan,
            ))
            ->values();
    }

    public function firmaSec(int $id): void
    {
        $this->firmaId = $id;
        $this->updatedFirmaId();
    }

    /*
    |--------------------------------------------------------------------------
    | Kontrol girişi ve geçmişi
    |--------------------------------------------------------------------------
    */

    private function ekipman(int $id): IsEkipmani
    {
        return IsEkipmani::query()->whereIn('firma_id', array_keys($this->firmalar))->findOrFail($id);
    }

    /** Yeni periyodik kontrol: ekipmanın son kontrolü güncellenir, eskisi geçmişte kalır, rapor dosyası eklenir. */
    public function kontrolGirAction(): Action
    {
        return Action::make('kontrolGir')
            ->label('Kontrol Gir')
            ->modalHeading(fn (array $arguments) => 'Periyodik Kontrol — '.$this->ekipman($arguments['id'])->ekipman_adi)
            ->modalDescription('Önceki kontrol geçmişte saklanır. Sonraki termin boş bırakılırsa periyottan hesaplanır.')
            ->modalSubmitActionLabel('Kontrolü Kaydet')
            ->fillForm(fn (array $arguments): array => [
                'kontrol_tarihi' => now()->toDateString(),
                'kontrol_eden' => $this->ekipman($arguments['id'])->muayene_yapan,
                'sonuc' => 'uygun',
            ])
            ->schema([
                DatePicker::make('kontrol_tarihi')->label('Kontrol tarihi')->required()->maxDate(now()),
                TextInput::make('kontrol_eden')->label('Kontrol firması / yetkili kişi'),
                TextInput::make('rapor_no')->label('Rapor no'),
                Select::make('sonuc')->label('Sonuç')->options(fn () => $this->sonuclar)->required(),
                DatePicker::make('sonraki_tarih')->label('Sonraki termin (isteğe bağlı)')->afterOrEqual('kontrol_tarihi'),
                FileUpload::make('dosya')
                    ->label('Kontrol raporu (PDF / görsel)')
                    ->disk('public')
                    ->directory(fn () => 'periyodik-kontrol/'.now()->format('Y'))
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(20480),
                Textarea::make('notu')->label('Not')->rows(2),
            ])
            ->action(function (array $data, array $arguments): void {
                $e = $this->ekipman($arguments['id']);

                $e->fill([
                    'son_muayene_tarihi' => $data['kontrol_tarihi'],
                    'sonraki_vize_tarihi' => filled($data['sonraki_tarih'] ?? null)
                        ? $data['sonraki_tarih']
                        : Carbon::parse($data['kontrol_tarihi'])->addMonths($e->muayene_periyodu_ay)->toDateString(),
                    'muayene_yapan' => $data['kontrol_eden'] ?: null,
                    'rapor_no' => $data['rapor_no'] ?: null,
                    'sonuc' => $data['sonuc'],
                ]);
                $e->save();

                $kayit = $e->kontroller()->whereDate('kontrol_tarihi', $data['kontrol_tarihi'])->first();
                $kayit?->update([
                    'notu' => $data['notu'] ?: null,
                    ...(filled($data['dosya'] ?? null) ? ['dosya_yolu' => $data['dosya'], 'dosya_adi' => basename($data['dosya'])] : []),
                ]);

                unset($this->tumEkipmanlar, $this->kpi, $this->kategoriSayaclari, $this->portfoyEkipmanlari);
                $this->satirlariYukle();

                Notification::make()->title('Kontrol kaydedildi')->body($e->ekipman_adi.' — sonraki: '.$e->sonraki_vize_tarihi?->format('d.m.Y'))->success()->send();
            });
    }

    public function kontrolGecmisiAction(): Action
    {
        return Action::make('kontrolGecmisi')
            ->label('Geçmiş')
            ->modalHeading(fn (array $arguments) => 'Kontrol Geçmişi — '.$this->ekipman($arguments['id'])->ekipman_adi)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Kapat')
            ->modalContent(fn (array $arguments) => view('filament.pages.partials.ekipman-kontrol-gecmisi', [
                'kontroller' => $this->ekipman($arguments['id'])->kontroller()->get(),
            ]));
    }

    public function kontrolRaporuIndir(int $kontrolId)
    {
        $k = IsEkipmaniKontrolu::query()
            ->whereIn('is_ekipmani_id', IsEkipmani::query()->whereIn('firma_id', array_keys($this->firmalar))->select('id'))
            ->findOrFail($kontrolId);

        if (! $k->dosya_yolu || ! Storage::disk('public')->exists($k->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($k->dosya_yolu, $k->dosya_adi);
    }

    public function ekipmanKaydet(array $data): void
    {
        if (! $this->firma) {
            return;
        }

        $data['firma_id'] = $this->firma->id;
        $data['muayene_periyodu_ay'] = (int) (($data['muayene_periyodu_ay'] ?? null) ?: 12);
        $data['aktif'] = true;
        $data['kategori_adi'] = config('isg.periyodik_kontrol.kategoriler.'.$data['kategori'].'.ad');

        // Tip seçildiyse yasal standart / deney config'ten çekilir.
        $tanim = collect(config('isg.periyodik_kontrol.tipler.'.$data['kategori'], []))
            ->firstWhere('ad', $data['tip'] ?? null);

        if ($tanim) {
            $data['yasal_standart'] = $tanim['standart'];
            $data['deney_test'] = $tanim['deney'];
        }

        $ekipman = IsEkipmani::create($data);

        unset($this->tumEkipmanlar, $this->kpi, $this->kategoriSayaclari);
        $this->satirlariYukle();

        Notification::make()->title('Ekipman tanımlandı')->body($data['ekipman_adi'])->success()->send();
        $this->ekipmanBaglantiUyarisi($ekipman);
    }

    /**
     * Katalogdaki ekipman eklenince: risk değerlendirmesinde eksik madde, kontrol formu
     * ve periyodik muayene durumu hakkında kalıcı uyarı (EkipmanKatalogu).
     */
    private function ekipmanBaglantiUyarisi(IsEkipmani $ekipman): void
    {
        $tanim = EkipmanKatalogu::tanim($ekipman->tip);
        if (! $tanim || ! $this->firma) {
            return;
        }

        $satirlar = [];
        $aksiyonlar = [];
        $eksik = EkipmanKatalogu::riskEksikleri($this->firma);

        if ($eksik['toplam'] > 0 && $eksik['rd']) {
            $satirlar[] = 'Risk değerlendirmesinde '.$eksik['toplam'].' ekipman maddesi eksik.';
            $aksiyonlar[] = Action::make('riskAc')->label('Risk değerlendirmesini aç')->button()
                ->url(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $eksik['rd']]));
        } elseif ($eksik['toplam'] > 0) {
            $satirlar[] = 'Firmanın risk değerlendirmesi yok; Risk Sihirbazı ile oluşturun ('.$eksik['toplam'].' ekipman maddesi kütüphanede hazır).';
        } elseif ($tanim['risk_kategorileri'] === []) {
            $satirlar[] = 'Bu ekipman için kütüphanede henüz risk maddesi yok.';
        }

        if ($talimat = EkipmanKatalogu::talimatSablonu($ekipman->tip)) {
            $satirlar[] = 'Talimat firmanın talimatlarında: '.$talimat['baslik'].' (teslim tutanağına dahil).';
        } else {
            $satirlar[] = 'Bu ekipman için kütüphanede henüz talimat yok.';
        }

        if ($tanim['kontrol_formu']) {
            $satirlar[] = 'Kontrol formu: "Çıktı Al → Günlük / Haftalık Kontrol Formu".';
        }

        $satirlar[] = EkipmanKatalogu::periyodikMi($ekipman->kategori)
            ? ($ekipman->son_muayene_tarihi ? 'Periyodik muayene takibi başladı.' : 'Periyodik muayene tarihi girilmedi — tarih girilene kadar uyarı verilir.')
            : 'Periyodik muayene yükümlülüğü yok (kullanım öncesi kontrol formuyla takip).';

        Notification::make()
            ->title($ekipman->tip.' — bağlantılı işler')
            ->body(new \Illuminate\Support\HtmlString(collect($satirlar)->map(fn ($s) => e($s))->implode('<br>')))
            ->warning()
            ->persistent()
            ->actions($aksiyonlar)
            ->send();
    }
}
