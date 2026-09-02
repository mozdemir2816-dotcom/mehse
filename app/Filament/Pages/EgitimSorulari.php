<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\EgitimSinavi as EgitimSinaviModel;
use App\Models\Firma;
use App\Support\EgitimSinaviUretici;
use App\Support\GeminiSoruUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Eğitim Soruları — isgpratik 69-70.jpg. Sektör + zorluğa göre Gemini ile
 * 10 soruluk çoktan seçmeli sınav üretilir, katılımcı listesine PDF verilir.
 */
class EgitimSorulari extends Page
{
    protected string $view = 'filament.pages.egitim-sorulari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'egitim-sorulari';

    protected static ?string $title = 'Eğitim Soruları';

    protected static ?string $navigationLabel = 'Eğitim Soruları';

    public ?int $firmaId = null;

    public ?string $sektorAnahtari = null;

    public string $zorluk = 'karisik';

    public bool $cevapAnahtariDahil = true;

    /** @var array<int, array{soru: string, secenekler: array<int, string>, dogru_index: int}> */
    public array $sorular = [];

    // manuel soru ekleme
    public ?string $yeniSoruMetni = null;

    /** @var array<int, string> */
    public array $yeniSecenekler = ['', '', '', ''];

    public int $yeniDogruIndex = 0;

    // katılımcı ekleme
    public ?string $yeniKatilimciAd = null;

    public ?string $yeniKatilimciTc = null;

    public ?string $yeniSinavTarihi = null;

    /** @var array<int, array{ad_soyad: string, tc: ?string, sinav_tarihi: ?string}> */
    public array $katilimcilar = [];

    public function mount(): void
    {
        $this->yeniSinavTarihi = now()->toDateString();

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
    public function sektorler(): array
    {
        return ['' => 'Genel'] + collect(config('isg.risk_ai.sektorler'))->map(fn ($s) => $s['ad'])->all();
    }

    #[Computed]
    public function zorluklar(): array
    {
        return config('isg.egitim_sorulari.zorluklar');
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiSoruUretici::aktifMi();
    }

    /** @return Collection<int, EgitimSinaviModel> */
    #[Computed]
    public function gecmisSinavlar(): Collection
    {
        return $this->firma?->egitimSinavlari()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisSinavlar);
    }

    /*
    |--------------------------------------------------------------------------
    | Soru üretimi & yönetimi
    |--------------------------------------------------------------------------
    */

    public function aiIleUret(): void
    {
        $sektorAdi = $this->sektorAnahtari ? config('isg.risk_ai.sektorler.'.$this->sektorAnahtari.'.ad') : 'Genel';
        $zorlukEtiketi = $this->zorluklar()[$this->zorluk] ?? 'Karışık';

        $sorular = GeminiSoruUretici::uret($sektorAdi, $zorlukEtiketi);

        if (! $sorular) {
            Notification::make()->title('Soru üretilemedi')->body('AI servisi yanıt vermedi veya devre dışı; elle soru ekleyebilirsiniz.')->warning()->send();

            return;
        }

        $this->sorular = $sorular;
        Notification::make()->title(count($sorular).' soru üretildi')->success()->send();
    }

    public function soruEkle(): void
    {
        $secenekler = array_values(array_filter($this->yeniSecenekler, fn ($s) => filled($s)));

        if (blank($this->yeniSoruMetni) || count($secenekler) !== 4) {
            Notification::make()->title('Soru metni ve 4 şık gerekli')->danger()->send();

            return;
        }

        $this->sorular[] = [
            'soru' => $this->yeniSoruMetni,
            'secenekler' => $secenekler,
            'dogru_index' => $this->yeniDogruIndex,
        ];

        $this->reset('yeniSoruMetni', 'yeniSecenekler', 'yeniDogruIndex');
        $this->yeniSecenekler = ['', '', '', ''];
    }

    public function soruSil(int $index): void
    {
        unset($this->sorular[$index]);
        $this->sorular = array_values($this->sorular);
    }

    public function sifirla(): void
    {
        $this->sorular = [];
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
            $this->katilimcilar[] = ['ad_soyad' => $c->ad_soyad, 'tc' => $c->tc, 'sinav_tarihi' => $this->yeniSinavTarihi];
        }
    }

    public function katilimciEkle(): void
    {
        if (blank($this->yeniKatilimciAd)) {
            return;
        }

        $this->katilimcilar[] = [
            'ad_soyad' => $this->yeniKatilimciAd,
            'tc' => $this->yeniKatilimciTc,
            'sinav_tarihi' => $this->yeniSinavTarihi,
        ];

        $this->reset('yeniKatilimciAd', 'yeniKatilimciTc');
    }

    public function katilimciSil(int $index): void
    {
        unset($this->katilimcilar[$index]);
        $this->katilimcilar = array_values($this->katilimcilar);
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?EgitimSinaviModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (! $this->sorular) {
            Notification::make()->title('Soru eklenmedi')->danger()->send();

            return null;
        }

        $sinav = new EgitimSinaviModel([
            'firma_id' => $this->firma->id,
            'sektor_anahtari' => $this->sektorAnahtari ?: null,
            'zorluk' => $this->zorluk,
            'cevap_anahtari_dahil' => $this->cevapAnahtariDahil,
            'sorular' => $this->sorular,
            'katilimcilar' => $this->katilimcilar,
        ]);
        $sinav->save();

        unset($this->gecmisSinavlar);

        return $sinav;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir (Kaydet)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $sinav = $this->kaydet();

                    return $sinav ? EgitimSinaviUretici::pdf($sinav) : null;
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $sinav = $this->firma?->egitimSinavlari()->find($id);

        return $sinav ? EgitimSinaviUretici::pdf($sinav) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->egitimSinavlari()->find($id)?->delete();
        unset($this->gecmisSinavlar);
    }
}
