<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\CezaTebligTutanagi as CezaTebligTutanagiModel;
use App\Models\Firma;
use App\Models\IpcTebligi;
use App\Support\CezaTebligTutanagiUretici;
use App\Support\IpcTebligiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * İSG Ceza ve Tebliğ Tutanağı — isgpratik 79-80.jpg. Çalışana uygulanan
 * disiplin yaptırımını ve tebliğ/tebellüğ durumunu belgeler.
 */
class CezaTeblig extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.ceza-teblig';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Diğer Belge & Yazışma';

    protected static ?int $navigationSort = 23;

    protected static ?string $slug = 'ceza-teblig';

    protected static ?string $title = 'Ceza ve Tebliğ Tutanağı';

    protected static ?string $navigationLabel = 'Ceza ve Tebliğ Tutanağı';

    public ?int $firmaId = null;

    public ?int $calisanHizliSecId = null;

    public ?string $calisanAdSoyad = null;

    public ?string $calisanTc = null;

    public ?string $calisanGorev = null;

    public ?string $calisanBolum = null;

    public ?string $iseGirisTarihi = null;

    public string $istihdamSekli = 'kadrolu';

    public ?string $tutanakTarihi = null;

    public ?string $olayTarihi = null;

    public ?string $olaySaati = null;

    public ?string $olayYeri = null;

    public ?string $olayAciklamasi = null;

    public ?string $yeniTanikAd = null;

    public ?string $yeniTanikGorev = null;

    /** @var array<int, array{ad_soyad: string, gorev: ?string}> */
    public array $taniklar = [];

    /** @var array<int, array{madde: string, dayanak: ?string}> */
    public array $ihlaller = [];

    public ?string $serbestIhlalMetni = null;

    public ?string $serbestIhlalDayanak = null;

    public ?string $yaptirim = null;

    public ?string $tebligTarihi = null;

    public ?string $imzaDurumu = 'imzaladi';

    // Fotoğraflar (olay/ihlal kanıtı)
    /** @var array<int, TemporaryUploadedFile> */
    public array $yeniFotograflar = [];

    /*
    |--------------------------------------------------------------------------
    | İşverene İPC Tebliği (2. sekme)
    |--------------------------------------------------------------------------
    */

    public string $aktifSekme = 'tutanak';

    public ?string $tebligTarihiIpc = null;

    public ?string $denetimTarihiIpc = null;

    public ?string $tespitEdenKurum = null;

    public ?string $mufettisAdi = null;

    /** @var array<int, array{baslik: string, aciklama: ?string}> */
    public array $ihlallerIpc = [];

    public ?string $serbestIhlalMetniIpc = null;

    public ?float $cezaTutari = null;

    public bool $odemeYapildi = false;

    public bool $itirazEdildi = false;

    public ?string $itirazNotu = null;

    public function mount(): void
    {
        $this->tutanakTarihi = now()->toDateString();
        $this->tebligTarihi = now()->toDateString();
        $this->tebligTarihiIpc = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
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
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function ihlalKatalogu(): array
    {
        return config('isg.ceza_teblig.ihlal_kategorileri');
    }

    #[Computed]
    public function yaptirimlar(): array
    {
        return config('isg.ceza_teblig.yaptirimlar');
    }

    /** @return Collection<int, CezaTebligTutanagiModel> */
    #[Computed]
    public function gecmisTutanaklar(): Collection
    {
        return $this->firma?->cezaTebligTutanaklari()->latest()->get() ?? collect();
    }

    #[Computed]
    public function ipcMaddeKatalogu(): array
    {
        return config('isg.ceza_teblig.ipc_maddeleri');
    }

    /** @return Collection<int, IpcTebligi> */
    #[Computed]
    public function gecmisIpcTebligleri(): Collection
    {
        return $this->firma?->ipcTebligleri()->latest()->get() ?? collect();
    }

    public function pesinOdemeTutari(): ?float
    {
        return $this->cezaTutari !== null ? round($this->cezaTutari * 0.75, 2) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisTutanaklar, $this->gecmisIpcTebligleri);
    }

    public function updatedCalisanHizliSecId(): void
    {
        $c = $this->calisanHizliSecId ? $this->calisanlar->firstWhere('id', $this->calisanHizliSecId) : null;

        $this->calisanAdSoyad = $c?->ad_soyad;
        $this->calisanTc = $c?->tc;
        $this->calisanGorev = $c?->gorev;
        $this->iseGirisTarihi = $c?->ise_giris?->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Tanıklar
    |--------------------------------------------------------------------------
    */

    public function tanikEkle(): void
    {
        if (blank($this->yeniTanikAd)) {
            return;
        }

        $this->taniklar[] = ['ad_soyad' => $this->yeniTanikAd, 'gorev' => $this->yeniTanikGorev];
        $this->reset('yeniTanikAd', 'yeniTanikGorev');
    }

    public function tanikSil(int $index): void
    {
        unset($this->taniklar[$index]);
        $this->taniklar = array_values($this->taniklar);
    }

    /*
    |--------------------------------------------------------------------------
    | İhlaller
    |--------------------------------------------------------------------------
    */

    public function ihlalToggle(string $madde, ?string $dayanak): void
    {
        $mevcut = collect($this->ihlaller)->firstWhere('madde', $madde);

        if ($mevcut) {
            $this->ihlaller = array_values(array_filter($this->ihlaller, fn ($i) => $i['madde'] !== $madde));
        } else {
            $this->ihlaller[] = ['madde' => $madde, 'dayanak' => $dayanak];
        }
    }

    public function ihlalSeciliMi(string $madde): bool
    {
        return collect($this->ihlaller)->contains('madde', $madde);
    }

    public function serbestIhlalEkle(): void
    {
        if (blank($this->serbestIhlalMetni)) {
            return;
        }

        $this->ihlaller[] = ['madde' => $this->serbestIhlalMetni, 'dayanak' => $this->serbestIhlalDayanak ?: 'İşyeri İç Yönetmeliği'];
        $this->reset('serbestIhlalMetni', 'serbestIhlalDayanak');
    }

    public function ihlalSil(int $index): void
    {
        unset($this->ihlaller[$index]);
        $this->ihlaller = array_values($this->ihlaller);
    }

    /*
    |--------------------------------------------------------------------------
    | Fotoğraflar
    |--------------------------------------------------------------------------
    */

    public function fotoSil(int $index): void
    {
        unset($this->yeniFotograflar[$index]);
        $this->yeniFotograflar = array_values($this->yeniFotograflar);
    }

    /*
    |--------------------------------------------------------------------------
    | İPC İhlalleri
    |--------------------------------------------------------------------------
    */

    public function ihlalIpcToggle(string $baslik, ?string $aciklama): void
    {
        $mevcut = collect($this->ihlallerIpc)->firstWhere('baslik', $baslik);

        if ($mevcut) {
            $this->ihlallerIpc = array_values(array_filter($this->ihlallerIpc, fn ($i) => $i['baslik'] !== $baslik));
        } else {
            $this->ihlallerIpc[] = ['baslik' => $baslik, 'aciklama' => $aciklama];
        }
    }

    public function ihlalIpcSeciliMi(string $baslik): bool
    {
        return collect($this->ihlallerIpc)->contains('baslik', $baslik);
    }

    public function ihlalIpcSil(int $index): void
    {
        unset($this->ihlallerIpc[$index]);
        $this->ihlallerIpc = array_values($this->ihlallerIpc);
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?CezaTebligTutanagiModel
    {
        if (! $this->firma || blank($this->calisanAdSoyad)) {
            Notification::make()->title('Firma ve çalışan bilgisi zorunlu')->danger()->send();

            return null;
        }

        $t = new CezaTebligTutanagiModel([
            'firma_id' => $this->firma->id,
            'tutanak_tarihi' => $this->tutanakTarihi,
            'calisan_ad_soyad' => $this->calisanAdSoyad,
            'calisan_tc' => $this->calisanTc,
            'calisan_gorev' => $this->calisanGorev,
            'calisan_bolum' => $this->calisanBolum,
            'ise_giris_tarihi' => $this->iseGirisTarihi,
            'istihdam_sekli' => $this->istihdamSekli,
            'olay_tarihi' => $this->olayTarihi,
            'olay_saati' => $this->olaySaati,
            'olay_yeri' => $this->olayYeri,
            'olay_aciklamasi' => $this->olayAciklamasi,
            'taniklar' => $this->taniklar,
            'ihlaller' => $this->ihlaller,
            'yaptirim' => $this->yaptirim,
            'teblig_tarihi' => $this->tebligTarihi,
            'imza_durumu' => $this->imzaDurumu,
            'fotograflar' => collect($this->yeniFotograflar)->map(fn ($f) => $f->store('ceza-teblig-foto', 'public'))->all(),
        ]);
        $t->save();

        unset($this->gecmisTutanaklar);

        return $t;
    }

    private function kaydetIpc(): ?IpcTebligi
    {
        if (! $this->firma) {
            Notification::make()->title('Firma seçimi zorunlu')->danger()->send();

            return null;
        }

        $t = new IpcTebligi([
            'firma_id' => $this->firma->id,
            'teblig_tarihi' => $this->tebligTarihiIpc,
            'denetim_tarihi' => $this->denetimTarihiIpc,
            'tespit_eden_kurum' => $this->tespitEdenKurum,
            'mufettis_adi' => $this->mufettisAdi,
            'ihlaller' => $this->ihlallerIpc,
            'serbest_ihlal_metni' => $this->serbestIhlalMetniIpc,
            'ceza_tutari' => $this->cezaTutari,
            'odeme_yapildi' => $this->odemeYapildi,
            'itiraz_edildi' => $this->itirazEdildi,
            'itiraz_notu' => $this->itirazNotu,
            'hazirlayan' => $this->firma->igu?->ad_soyad,
            'hazirlayan_kase' => $this->firma->igu?->kase_gorseli,
        ]);
        $t->save();

        unset($this->gecmisIpcTebligleri);

        return $t;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null && $this->aktifSekme === 'tutanak')
                ->action(function () {
                    $t = $this->kaydet();

                    return $t ? CezaTebligTutanagiUretici::pdf($t) : null;
                }),

            Action::make('pdfIpc')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null && $this->aktifSekme === 'ipc')
                ->action(function () {
                    $t = $this->kaydetIpc();

                    return $t ? IpcTebligiUretici::pdf($t) : null;
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $t = $this->firma?->cezaTebligTutanaklari()->find($id);

        return $t ? CezaTebligTutanagiUretici::pdf($t) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->cezaTebligTutanaklari()->find($id)?->delete();
        unset($this->gecmisTutanaklar);
    }

    public function gecmisIpcPdf(int $id)
    {
        $t = $this->firma?->ipcTebligleri()->find($id);

        return $t ? IpcTebligiUretici::pdf($t) : null;
    }

    public function gecmisIpcSil(int $id): void
    {
        $this->firma?->ipcTebligleri()->find($id)?->delete();
        unset($this->gecmisIpcTebligleri);
    }
}
