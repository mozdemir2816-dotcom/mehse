<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\TatbikatTutanagi as TatbikatTutanagiModel;
use App\Support\TatbikatTutanagiUretici;
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
 * Tatbikat Tutanağı — isgpratik 61-65.jpg. Senaryo seçimi otomatik metin
 * doldurur; görev alan ekipler + değerlendirme kontrol listesi + DÖF
 * önerileri + katılımcılar ile tam tutanak PDF üretir.
 */
class TatbikatTutanagi extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.tatbikat-tutanagi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 17;

    protected static ?string $slug = 'tatbikat';

    protected static ?string $title = 'Tatbikat Tutanağı';

    protected static ?string $navigationLabel = 'Tatbikat Tutanağı';

    public ?int $firmaId = null;

    public ?string $senaryoAnahtari = null;

    public ?string $senaryoMetni = null;

    public ?string $tatbikatTarihi = null;

    public ?string $tatbikatYeri = null;

    public ?string $baslamaSaati = null;

    public ?string $bitisSaati = null;

    public ?int $tahliyeDk = null;

    public bool $haberliTatbikat = true;

    public bool $yillikPlanDahilinde = true;

    public ?string $isverenVekili = null;

    public ?string $isGuvenligiUzmani = null;

    public ?string $tatbikatKoordinatoru = null;

    public bool $isyeriHekimiImzasi = false;

    public ?string $belgeTarihi = null;

    // Ekipler
    public ?string $yeniEkipAd = null;

    public ?string $yeniEkipTipi = null;

    /** @var array<int, array{ad_soyad: string, ekip: ?string}> */
    public array $ekipler = [];

    // Değerlendirmeler
    /** @var array<int, array{soru: string, cevap: ?string}> */
    public array $degerlendirmeler = [];

    public ?string $yeniOzelSoru = null;

    public ?string $gozlem = null;

    // Eksiklikler
    public ?string $yeniEksiklik = null;

    /** @var array<int, string> */
    public array $eksiklikler = [];

    // DÖF önerileri
    public ?string $yeniDofFaaliyet = null;

    public ?string $yeniDofSorumlu = null;

    public ?string $yeniDofTarih = null;

    /** @var array<int, array{faaliyet: string, sorumlu: ?string, tarih: ?string}> */
    public array $dofOnerileri = [];

    // Katılımcılar
    public ?string $yeniKatilimciAd = null;

    public ?string $yeniKatilimciTc = null;

    public ?string $yeniKatilimciGorev = null;

    /** @var array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    public array $katilimcilar = [];

    // Fotoğraflar
    /** @var array<int, TemporaryUploadedFile> */
    public array $yeniFotograflar = [];

    public function mount(): void
    {
        $this->tatbikatTarihi = now()->toDateString();
        $this->belgeTarihi = now()->toDateString();
        $this->degerlendirmeler = collect(config('isg.tatbikat.degerlendirme_sorulari'))
            ->map(fn ($s) => ['soru' => $s, 'cevap' => null])
            ->all();

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

    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function senaryolar(): array
    {
        return config('isg.tatbikat.senaryolar');
    }

    #[Computed]
    public function ekipSecenekleri(): array
    {
        return config('isg.tatbikat.ekip_secenekleri');
    }

    #[Computed]
    public function degerlendirmeSecenekleri(): array
    {
        return config('isg.tatbikat.degerlendirme_secenekleri');
    }

    /** @return Collection<int, TatbikatTutanagiModel> */
    #[Computed]
    public function gecmisTutanaklar(): Collection
    {
        return $this->firma?->tatbikatTutanaklari()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisTutanaklar);
    }

    /*
    |--------------------------------------------------------------------------
    | Senaryo
    |--------------------------------------------------------------------------
    */

    public function senaryoSec(string $anahtar): void
    {
        $senaryo = $this->senaryolar()[$anahtar] ?? null;

        if (! $senaryo) {
            return;
        }

        $this->senaryoAnahtari = $anahtar;
        $this->senaryoMetni = $senaryo['metin'];
    }

    /*
    |--------------------------------------------------------------------------
    | Ekipler
    |--------------------------------------------------------------------------
    */

    public function ekipHizliEkle(int $calisanId, string $ekipTipi): void
    {
        $c = $this->calisanlar->firstWhere('id', $calisanId);

        if ($c) {
            $this->ekipler[] = ['ad_soyad' => $c->ad_soyad, 'ekip' => $ekipTipi];
        }
    }

    public function ekipEkle(): void
    {
        if (blank($this->yeniEkipAd)) {
            return;
        }

        $this->ekipler[] = ['ad_soyad' => $this->yeniEkipAd, 'ekip' => $this->yeniEkipTipi];
        $this->reset('yeniEkipAd', 'yeniEkipTipi');
    }

    public function ekipSil(int $index): void
    {
        unset($this->ekipler[$index]);
        $this->ekipler = array_values($this->ekipler);
    }

    /*
    |--------------------------------------------------------------------------
    | Değerlendirmeler
    |--------------------------------------------------------------------------
    */

    public function degerlendirmeCevapla(int $index, string $cevap): void
    {
        if (! isset($this->degerlendirmeler[$index])) {
            return;
        }

        $this->degerlendirmeler[$index]['cevap'] = $cevap;
    }

    public function ozelSoruEkle(): void
    {
        if (blank($this->yeniOzelSoru)) {
            return;
        }

        $this->degerlendirmeler[] = ['soru' => $this->yeniOzelSoru, 'cevap' => null];
        $this->reset('yeniOzelSoru');
    }

    public function degerlendirmeSil(int $index): void
    {
        unset($this->degerlendirmeler[$index]);
        $this->degerlendirmeler = array_values($this->degerlendirmeler);
    }

    /*
    |--------------------------------------------------------------------------
    | Eksiklikler
    |--------------------------------------------------------------------------
    */

    public function eksiklikEkle(): void
    {
        if (blank($this->yeniEksiklik)) {
            return;
        }

        $this->eksiklikler[] = $this->yeniEksiklik;
        $this->reset('yeniEksiklik');
    }

    public function eksiklikSil(int $index): void
    {
        unset($this->eksiklikler[$index]);
        $this->eksiklikler = array_values($this->eksiklikler);
    }

    /*
    |--------------------------------------------------------------------------
    | DÖF önerileri
    |--------------------------------------------------------------------------
    */

    public function dofOnerisiEkle(): void
    {
        if (blank($this->yeniDofFaaliyet)) {
            return;
        }

        $this->dofOnerileri[] = ['faaliyet' => $this->yeniDofFaaliyet, 'sorumlu' => $this->yeniDofSorumlu, 'tarih' => $this->yeniDofTarih];
        $this->reset('yeniDofFaaliyet', 'yeniDofSorumlu', 'yeniDofTarih');
    }

    public function dofOnerisiSil(int $index): void
    {
        unset($this->dofOnerileri[$index]);
        $this->dofOnerileri = array_values($this->dofOnerileri);
    }

    /*
    |--------------------------------------------------------------------------
    | Katılımcılar
    |--------------------------------------------------------------------------
    */

    public function katilimciHizliEkle(int $calisanId): void
    {
        $c = $this->calisanlar->firstWhere('id', $calisanId);

        if ($c) {
            $this->katilimcilar[] = ['ad_soyad' => $c->ad_soyad, 'tc' => $c->tc, 'gorev' => $c->gorev];
        }
    }

    public function katilimciEkle(): void
    {
        if (blank($this->yeniKatilimciAd)) {
            return;
        }

        $this->katilimcilar[] = ['ad_soyad' => $this->yeniKatilimciAd, 'tc' => $this->yeniKatilimciTc, 'gorev' => $this->yeniKatilimciGorev];
        $this->reset('yeniKatilimciAd', 'yeniKatilimciTc', 'yeniKatilimciGorev');
    }

    public function katilimciSil(int $index): void
    {
        unset($this->katilimcilar[$index]);
        $this->katilimcilar = array_values($this->katilimcilar);
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
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?TatbikatTutanagiModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        $t = new TatbikatTutanagiModel([
            'firma_id' => $this->firma->id,
            'senaryo_anahtari' => $this->senaryoAnahtari,
            'senaryo_metni' => $this->senaryoMetni,
            'tatbikat_tarihi' => $this->tatbikatTarihi,
            'tatbikat_yeri' => $this->tatbikatYeri,
            'baslama_saati' => $this->baslamaSaati,
            'bitis_saati' => $this->bitisSaati,
            'tahliye_dk' => $this->tahliyeDk,
            'haberli_tatbikat' => $this->haberliTatbikat,
            'yillik_plan_dahilinde' => $this->yillikPlanDahilinde,
            'isveren_vekili' => $this->isverenVekili,
            'is_guvenligi_uzmani' => $this->isGuvenligiUzmani,
            'tatbikat_koordinatoru' => $this->tatbikatKoordinatoru,
            'isyeri_hekimi_imzasi' => $this->isyeriHekimiImzasi,
            'belge_tarihi' => $this->belgeTarihi,
            'ekipler' => $this->ekipler,
            'degerlendirmeler' => $this->degerlendirmeler,
            'gozlem' => $this->gozlem,
            'eksiklikler' => $this->eksiklikler,
            'dof_onerileri' => $this->dofOnerileri,
            'katilimcilar' => $this->katilimcilar,
            'fotograflar' => collect($this->yeniFotograflar)->map(fn ($f) => $f->store('tatbikat-foto', 'public'))->all(),
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

                    return $t ? TatbikatTutanagiUretici::pdf($t) : null;
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $t = $this->firma?->tatbikatTutanaklari()->find($id);

        return $t ? TatbikatTutanagiUretici::pdf($t) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->tatbikatTutanaklari()->find($id)?->delete();
        unset($this->gecmisTutanaklar);
    }
}
