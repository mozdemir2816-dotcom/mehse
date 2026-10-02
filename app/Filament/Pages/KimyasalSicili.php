<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsgAfis;
use App\Models\KimyasalUrun;
use App\Filament\Support\ImzaSecenegi;
use App\Support\KimyasalEnvanterUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

use App\Filament\Concerns\SinirliErisim;
/**
 * Kimyasal Sicili — işyeri kimyasal envanteri (SDS / GHS) + İSG afiş / pano
 * kütüphanesi. Firma seçilir, kimyasallar SDS dosyasıyla eklenir, envanter PDF'i
 * alınır; afişler (GHS levhaları, uyarı posterleri) ayrıca yüklenir.
 */
class KimyasalSicili extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.kimyasal-sicili';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static ?int $navigationSort = 26;

    protected static ?string $slug = 'kimyasal-sicili';

    protected static ?string $title = 'Kimyasal Sicili';

    protected static ?string $navigationLabel = 'Kimyasal Sicili / Afiş';

    // Kimyasal Yönetimi sayfasından açılır.
    protected static bool $shouldRegisterNavigation = false;

    public ?int $firmaId = null;

    public ?string $arama = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    /** @return Collection<int, KimyasalUrun> */
    #[Computed]
    public function urunler(): Collection
    {
        return $this->firma?->kimyasalUrunler()->orderBy('urun_adi')->get() ?? collect();
    }

    /** @return Collection<int, KimyasalUrun> ürün adı / CAS / tedarikçi araması */
    #[Computed]
    public function gosterilenUrunler(): Collection
    {
        $aranan = mb_strtolower(trim((string) $this->arama));

        return $aranan === '' ? $this->urunler : $this->urunler->filter(fn (KimyasalUrun $u) => str_contains(
            mb_strtolower(implode(' ', [$u->urun_adi, $u->cas_no, $u->tedarikci, $u->kullanim_alani])),
            $aranan,
        ))->values();
    }

    public function updatedArama(): void
    {
        unset($this->gosterilenUrunler);
    }

    /** @return Collection<string, Collection<int, IsgAfis>> */
    #[Computed]
    public function afisler(): Collection
    {
        return IsgAfis::query()
            ->where('user_id', Filament::auth()->id())
            ->where(fn ($q) => $q->whereNull('firma_id')->when($this->firmaId, fn ($q) => $q->orWhere('firma_id', $this->firmaId)))
            ->latest()
            ->get()
            ->groupBy(fn (IsgAfis $a) => $a->kategoriEtiketi());
    }

    #[Computed]
    public function ozet(): array
    {
        $u = $this->urunler;

        return [
            'toplam' => $u->count(),
            'sds_var' => $u->filter->sdsVarMi()->count(),
            'sds_yok' => $u->reject->sdsVarMi()->count(),
            'etiketli' => $u->filter(fn ($x) => filled($x->ghs))->count(),
            'gecikmis' => $u->filter(fn ($x) => $x->gozdenGecirmeDurumu() === 'gecikmis')->count(),
            'yaklasan' => $u->filter(fn ($x) => $x->gozdenGecirmeDurumu() === 'yaklasan')->count(),
        ];
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->urunler, $this->gosterilenUrunler, $this->afisler, $this->ozet);
    }

    private function kimyasalForm(): array
    {
        return [
            TextInput::make('urun_adi')->label('Ürün adı')->required(),
            TextInput::make('cas_no')->label('CAS No')->placeholder('örn. 64-17-5'),
            TextInput::make('tedarikci')->label('Tedarikçi / Üretici'),
            Select::make('fiziksel_hal')->label('Fiziksel hal')->options(config('isg.kimyasal.fiziksel_hal'))->native(false),
            TextInput::make('kullanim_alani')->label('Kullanım alanı / bölüm'),
            TextInput::make('miktar')->label('Yaklaşık miktar')->placeholder('örn. 200 L/ay'),
            Textarea::make('depolama')->label('Depolama koşulları')->rows(2)->columnSpanFull(),
            CheckboxList::make('ghs')->label('GHS / CLP tehlike sınıfları')
                ->options(config('isg.kimyasal.ghs'))->columns(2)->columnSpanFull(),
            DatePicker::make('sds_tarihi')->label('SDS düzenlenme / revizyon tarihi'),
            DatePicker::make('sonraki_gozden_gecirme')->label('Sonraki gözden geçirme')
                ->helperText('Boş bırakılırsa SDS tarihinden +1 yıl.'),
            FileUpload::make('sds')->label('Malzeme Güvenlik Bilgi Formu (SDS / GBF)')
                ->disk('public')->directory(fn () => 'sds/'.$this->firmaId)->preserveFilenames()
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->maxSize(15360)->columnSpanFull(),
            Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('merkez')
                ->label('Kimyasal Yönetimi')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => KimyasalYonetimi::getUrl(array_filter(['firma' => $this->firmaId]))),

            Action::make('kimyasalEkle')
                ->label('Kimyasal Ekle')
                ->icon('heroicon-o-plus')
                ->visible(fn () => $this->firma !== null)
                ->schema($this->kimyasalForm())
                ->action(fn (array $data) => $this->kimyasalKaydet($data)),

            Action::make('afisEkle')
                ->label('Afiş / Pano Ekle')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->schema([
                    TextInput::make('baslik')->label('Afiş başlığı')->required(),
                    Select::make('kategori')->label('Kategori')->options(config('isg.afis.kategoriler'))
                        ->default('genel')->native(false)->required(),
                    Select::make('firma_id')->label('İşyeri')
                        ->options(fn () => ['' => 'Genel (tüm işyerleri)'] + $this->firmalar())
                        ->default(fn () => $this->firmaId)->native(false),
                    FileUpload::make('dosya')->label('Afiş dosyası (PDF veya görsel)')
                        ->disk('public')->directory('afis')->preserveFilenames()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(15360)->required(),
                    Textarea::make('aciklama')->label('Açıklama')->rows(2),
                ])
                ->action(fn (array $data) => $this->afisKaydet($data)),

            Action::make('envanterPdf')
                ->label('Envanter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->urunler->isNotEmpty())
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => KimyasalEnvanterUretici::pdf($this->firma)),

            Action::make('envanterExcel')
                ->label('Excel Rapor')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->urunler->isNotEmpty())
                ->action(fn () => KimyasalEnvanterUretici::excel($this->firma)),
        ];
    }

    /** Kayıtlı ürünü düzenler; yeni SDS yüklenirse eskisinin yerine geçer. */
    public function kimyasalDuzenleAction(): Action
    {
        return Action::make('kimyasalDuzenle')
            ->label('Düzenle')
            ->modalHeading(fn (array $arguments) => 'Kimyasal — '.$this->urun($arguments['id'])->urun_adi)
            ->fillForm(function (array $arguments): array {
                $u = $this->urun($arguments['id']);

                return [
                    ...$u->only(['urun_adi', 'cas_no', 'tedarikci', 'fiziksel_hal', 'kullanim_alani', 'miktar', 'depolama', 'aciklama']),
                    'ghs' => $u->ghs ?? [],
                    'sds_tarihi' => $u->sds_tarihi?->toDateString(),
                    'sonraki_gozden_gecirme' => $u->sonraki_gozden_gecirme?->toDateString(),
                ];
            })
            ->schema(fn () => $this->kimyasalForm())
            ->action(function (array $data, array $arguments): void {
                $u = $this->urun($arguments['id']);
                $yol = $data['sds'] ?? null;
                unset($data['sds']);

                if ($yol) {
                    if ($u->sds_dosya_yolu && $u->sds_dosya_yolu !== $yol && Storage::disk('public')->exists($u->sds_dosya_yolu)) {
                        Storage::disk('public')->delete($u->sds_dosya_yolu);
                    }
                    $data['sds_dosya_yolu'] = $yol;
                    $data['sds_dosya_adi'] = basename($yol);
                }

                $u->update($data);
                unset($this->urunler, $this->gosterilenUrunler, $this->ozet);

                Notification::make()->title('Kimyasal güncellendi')->success()->send();
            });
    }

    private function urun(int $id): KimyasalUrun
    {
        return $this->firma?->kimyasalUrunler()->findOrFail($id) ?? abort(404);
    }

    public function kimyasalKaydet(array $data): void
    {
        if (! $this->firma) {
            return;
        }

        $yol = $data['sds'] ?? null;
        unset($data['sds']);

        if ($yol) {
            $data['sds_dosya_yolu'] = $yol;
            $data['sds_dosya_adi'] = basename($yol);
        }

        $this->firma->kimyasalUrunler()->create($data + ['aktif' => true]);

        unset($this->urunler, $this->gosterilenUrunler, $this->ozet);
        Notification::make()->title('Kimyasal eklendi')->success()->send();
    }

    public function afisKaydet(array $data): void
    {
        $yol = $data['dosya'];

        IsgAfis::create([
            'user_id' => Filament::auth()->id(),
            'firma_id' => $data['firma_id'] ?: null,
            'baslik' => $data['baslik'],
            'kategori' => $data['kategori'],
            'dosya_adi' => basename($yol),
            'dosya_yolu' => $yol,
            'boyut' => Storage::disk('public')->exists($yol) ? Storage::disk('public')->size($yol) : 0,
            'aciklama' => $data['aciklama'] ?: null,
        ]);

        unset($this->afisler);
        Notification::make()->title('Afiş eklendi')->success()->send();
    }

    public function kimyasalSil(int $id): void
    {
        $urun = $this->firma?->kimyasalUrunler()->find($id);

        if ($urun) {
            if ($urun->sds_dosya_yolu && Storage::disk('public')->exists($urun->sds_dosya_yolu)) {
                Storage::disk('public')->delete($urun->sds_dosya_yolu);
            }
            $urun->delete();
            unset($this->urunler, $this->gosterilenUrunler, $this->ozet);
        }
    }

    public function sdsIndir(int $id)
    {
        $urun = $this->firma?->kimyasalUrunler()->find($id);

        if (! $urun || ! $urun->sds_dosya_yolu || ! Storage::disk('public')->exists($urun->sds_dosya_yolu)) {
            Notification::make()->title('SDS dosyası bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($urun->sds_dosya_yolu, $urun->sds_dosya_adi);
    }

    public function afisIndir(int $id)
    {
        $afis = IsgAfis::where('user_id', Filament::auth()->id())->find($id);

        if (! $afis || ! Storage::disk('public')->exists($afis->dosya_yolu)) {
            return null;
        }

        return Storage::disk('public')->download($afis->dosya_yolu, $afis->dosya_adi);
    }

    public function afisSil(int $id): void
    {
        $afis = IsgAfis::where('user_id', Filament::auth()->id())->find($id);

        if ($afis) {
            if (Storage::disk('public')->exists($afis->dosya_yolu)) {
                Storage::disk('public')->delete($afis->dosya_yolu);
            }
            $afis->delete();
            unset($this->afisler);
        }
    }
}
