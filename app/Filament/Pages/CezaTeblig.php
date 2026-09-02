<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\CezaTebligTutanagi as CezaTebligTutanagiModel;
use App\Models\Firma;
use App\Support\CezaTebligTutanagiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İSG Ceza ve Tebliğ Tutanağı — isgpratik 79-80.jpg. Çalışana uygulanan
 * disiplin yaptırımını ve tebliğ/tebellüğ durumunu belgeler.
 */
class CezaTeblig extends Page
{
    protected string $view = 'filament.pages.ceza-teblig';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

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

    public function mount(): void
    {
        $this->tutanakTarihi = now()->toDateString();
        $this->tebligTarihi = now()->toDateString();

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

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisTutanaklar);
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
        ]);
        $t->save();

        unset($this->gecmisTutanaklar);

        return $t;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $t = $this->kaydet();

                    return $t ? CezaTebligTutanagiUretici::pdf($t) : null;
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
}
