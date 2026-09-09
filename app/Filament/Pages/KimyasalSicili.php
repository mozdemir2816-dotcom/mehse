<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsgAfis;
use App\Models\KimyasalUrun;
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

/**
 * Kimyasal Sicili — işyeri kimyasal envanteri (SDS / GHS) + İSG afiş / pano
 * kütüphanesi. Firma seçilir, kimyasallar SDS dosyasıyla eklenir, envanter PDF'i
 * alınır; afişler (GHS levhaları, uyarı posterleri) ayrıca yüklenir.
 */
class KimyasalSicili extends Page
{
    protected string $view = 'filament.pages.kimyasal-sicili';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 26;

    protected static ?string $slug = 'kimyasal-sicili';

    protected static ?string $title = 'Kimyasal Sicili';

    protected static ?string $navigationLabel = 'Kimyasal Sicili / Afiş';

    public ?int $firmaId = null;

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
            'gecikmis' => $u->filter(fn ($x) => $x->gozdenGecirmeDurumu() === 'gecikmis')->count(),
            'yaklasan' => $u->filter(fn ($x) => $x->gozdenGecirmeDurumu() === 'yaklasan')->count(),
        ];
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->urunler, $this->afisler, $this->ozet);
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
                ->acceptedFileTypes(['application/pdf'])->maxSize(15360)->columnSpanFull(),
            Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
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
                ->action(fn () => KimyasalEnvanterUretici::pdf($this->firma)),
        ];
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

        unset($this->urunler, $this->ozet);
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
            unset($this->urunler, $this->ozet);
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
