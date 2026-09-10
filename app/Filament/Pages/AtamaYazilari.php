<?php

namespace App\Filament\Pages;

use App\Models\AtamaYazisi as AtamaYazisiModel;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Support\AtamaYazisiUretici;
use App\Support\AtamaYazisiWordUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Atama Yazıları — isgpratik 38-44.jpg. 10 görev tipinden biri seçilir;
 * 'tekli' roller tek çalışan (+ görev tarihi aralığı), 'ekip' roller çoklu
 * çalışan (+ baş üye işareti) ile doldurulur, PDF üretilir.
 */
class AtamaYazilari extends Page
{
    protected string $view = 'filament.pages.atama-yazilari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Çalışan & Kurul';

    protected static ?int $navigationSort = 14;

    protected static ?string $slug = 'atama-yazilari';

    protected static ?string $title = 'Atama Yazıları';

    protected static ?string $navigationLabel = 'Atama Yazıları';

    public ?int $firmaId = null;

    public string $rolAnahtari = 'calisan_temsilcisi';

    public ?string $tarih = null;

    public ?string $isverenVekiliAdi = null;

    // 'tekli' roller
    public ?string $tekAdSoyad = null;

    public ?string $tekTc = null;

    public ?string $tekGorev = null;

    public ?string $gorevBaslangic = null;

    public ?string $gorevBitis = null;

    public bool $basTemsilci = false;

    public ?int $tekHizliSecId = null;

    // 'ekip' roller
    /** @var array<int, int> */
    public array $secilenCalisanIdler = [];

    public ?int $basUyeId = null;

    /** İSG Kurulu: seçili çalışanın firma içi genel görevi yerine kurul içindeki
     *  görev tanımı (config isg.atama.kurul_gorevleri anahtarı), calisan_id ile keyed. */
    /** @var array<int, string> */
    public array $kurulGorevleri = [];

    /** İSG Kurulu: firmaya atanmış İGU/İşyeri Hekimi/DSP'den kurula dahil edilenler. */
    /** @var array<int, int> */
    public array $secilenProfesyonelIdler = [];

    public function mount(): void
    {
        $this->tarih = now()->toDateString();
        $this->gorevBaslangic = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
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
    public function roller(): array
    {
        return config('isg.atama.roller');
    }

    #[Computed]
    public function rol(): array
    {
        return config('isg.atama.roller.'.$this->rolAnahtari, []);
    }

    #[Computed]
    public function ekipMi(): bool
    {
        return ($this->rol()['tip'] ?? 'tekli') === 'ekip';
    }

    #[Computed]
    public function kurulGorevSecenekleri(): array
    {
        return config('isg.atama.kurul_gorevleri');
    }

    /** Firmaya atanmış İGU/İşyeri Hekimi/DSP (İSG Kurulu ekip seçiminde kullanılır). */
    #[Computed]
    public function firmaProfesyonelleri(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return collect([$this->firma->igu, $this->firma->isyeriHekimi, $this->firma->dsp])
            ->filter()
            ->values();
    }

    /** İGU ve İşyeri Hekimi otomatik seçili gelir (DSP manuel eklenir). */
    private function varsayilanProfesyonelIdler(): array
    {
        return collect([$this->firma?->igu_id, $this->firma?->isyeri_hekimi_id])
            ->filter()
            ->values()
            ->all();
    }

    /** @return Collection<int, AtamaYazisiModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->atamaYazilari()->latest('tarih')->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar, $this->firmaProfesyonelleri);
        $this->isverenVekiliAdi = $this->firma?->isveren_vekili ?: $this->firma?->isveren_ad;
        $this->secilenCalisanIdler = [];
        $this->basUyeId = null;
        $this->kurulGorevleri = [];
        $this->secilenProfesyonelIdler = $this->rolAnahtari === 'isg_kurulu' ? $this->varsayilanProfesyonelIdler() : [];
    }

    public function updatedRolAnahtari(): void
    {
        unset($this->rol, $this->ekipMi);
        $this->secilenCalisanIdler = [];
        $this->basUyeId = null;
        $this->tekAdSoyad = null;
        $this->tekTc = null;
        $this->tekGorev = null;
        $this->basTemsilci = false;
        $this->tekHizliSecId = null;
        $this->kurulGorevleri = [];
        $this->secilenProfesyonelIdler = $this->rolAnahtari === 'isg_kurulu' ? $this->varsayilanProfesyonelIdler() : [];
    }

    public function updatedTekHizliSecId(): void
    {
        $c = $this->tekHizliSecId ? $this->calisanlar->firstWhere('id', $this->tekHizliSecId) : null;

        $this->tekAdSoyad = $c?->ad_soyad;
        $this->tekTc = $c?->tc;
        $this->tekGorev = $c?->gorev;
    }

    public function calisanToggle(int $id): void
    {
        $this->secilenCalisanIdler = in_array($id, $this->secilenCalisanIdler, true)
            ? array_values(array_diff($this->secilenCalisanIdler, [$id]))
            : [...$this->secilenCalisanIdler, $id];

        if (! in_array($id, $this->secilenCalisanIdler, true)) {
            if ($this->basUyeId === $id) {
                $this->basUyeId = null;
            }
            unset($this->kurulGorevleri[$id]);
        }
    }

    public function basUyeSec(int $id): void
    {
        $this->basUyeId = $this->basUyeId === $id ? null : $id;
    }

    public function profesyonelToggle(int $id): void
    {
        $this->secilenProfesyonelIdler = in_array($id, $this->secilenProfesyonelIdler, true)
            ? array_values(array_diff($this->secilenProfesyonelIdler, [$id]))
            : [...$this->secilenProfesyonelIdler, $id];
    }

    public function firmaProfilindenDoldur(): void
    {
        $this->secilenCalisanIdler = $this->calisanlar->pluck('id')->all();
    }

    /** @return array<int, array{ad_soyad: string, tc: ?string, gorev: ?string, bas_uye: bool, kase_gorseli?: ?string, imza_gorseli?: ?string}> */
    private function uyeleriTopla(): array
    {
        if (! $this->ekipMi()) {
            if (blank($this->tekAdSoyad)) {
                return [];
            }

            return [[
                'ad_soyad' => $this->tekAdSoyad,
                'tc' => $this->tekTc,
                'gorev' => $this->tekGorev,
                'bas_uye' => $this->basTemsilci,
            ]];
        }

        $kurulMu = $this->rolAnahtari === 'isg_kurulu';

        $calisanUyeler = $this->calisanlar
            ->whereIn('id', $this->secilenCalisanIdler)
            ->map(function (Calisan $c) use ($kurulMu) {
                $kurulGorevi = $kurulMu ? ($this->kurulGorevleri[$c->id] ?? null) : null;

                return [
                    'ad_soyad' => $c->ad_soyad,
                    'tc' => $c->tc,
                    'gorev' => filled($kurulGorevi)
                        ? config('isg.atama.kurul_gorevleri.'.$kurulGorevi, $c->gorev)
                        : $c->gorev,
                    'bas_uye' => $c->id === $this->basUyeId,
                ];
            });

        $profesyonelUyeler = $kurulMu
            ? $this->firmaProfesyonelleri
                ->whereIn('id', $this->secilenProfesyonelIdler)
                ->map(fn (IsgProfesyoneli $p) => [
                    'ad_soyad' => $p->ad_soyad,
                    'tc' => null,
                    'gorev' => $p->unvan ?: $p->tipEtiketi(),
                    'bas_uye' => false,
                    'kase_gorseli' => $p->kase_gorseli,
                    'imza_gorseli' => $p->imza_gorseli,
                ])
            : collect();

        return $calisanUyeler->concat($profesyonelUyeler)->values()->all();
    }

    private function kaydet(): ?AtamaYazisiModel
    {
        if (! $this->firma || ! $this->tarih) {
            Notification::make()->title('Firma ve tarih zorunlu')->danger()->send();

            return null;
        }

        $uyeler = $this->uyeleriTopla();

        if (! $uyeler) {
            Notification::make()->title('En az bir üye/çalışan girin')->danger()->send();

            return null;
        }

        $kayit = new AtamaYazisiModel([
            'firma_id' => $this->firma->id,
            'rol_anahtari' => $this->rolAnahtari,
            'tarih' => $this->tarih,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'gorev_baslangic' => $this->ekipMi() ? null : $this->gorevBaslangic,
            'gorev_bitis' => $this->ekipMi() ? null : $this->gorevBitis,
            'uyeler' => $uyeler,
        ]);
        $kayit->save();

        unset($this->gecmisKayitlar);

        return $kayit;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    Notification::make()->title('Atama yazısı kaydedildi')->body($kayit->dokuman_no)->success()->send();

                    return AtamaYazisiUretici::pdf($kayit);
                }),

            Action::make('word')
                ->label('Word İndir')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn () => $this->firma !== null && AtamaYazisiWordUretici::sablonVarMi($this->rolAnahtari))
                ->action(function () {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    $docx = AtamaYazisiWordUretici::docx($kayit);

                    if (! $docx) {
                        Notification::make()->title('Bu görev tipi için Word şablonu mevcut değil')->warning()->send();

                        return null;
                    }

                    Notification::make()->title('Atama yazısı kaydedildi')->body($kayit->dokuman_no)->success()->send();

                    return $docx;
                }),

            Action::make('egitimFormu')
                ->label('Eğitim Katılım Formu Oluştur')
                ->icon('heroicon-o-academic-cap')
                ->color('success')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => $this->egitimFormuOlustur()),
        ];
    }

    public function egitimFormuOlustur()
    {
        $kayit = $this->kaydet();

        if (! $kayit) {
            return null;
        }

        $katilimcilar = collect($kayit->uyeler)
            ->reject(fn (array $u) => array_key_exists('kase_gorseli', $u))
            ->map(fn (array $u) => [
                'ad_soyad' => $u['ad_soyad'] ?? '',
                'tc' => $u['tc'] ?? null,
                'gorev' => $u['gorev'] ?? null,
            ])
            ->values()
            ->all();

        $baslikAnahtari = array_key_exists($this->rolAnahtari, config('isg.egitim.ozel_basliklar'))
            ? $this->rolAnahtari
            : 'genel';

        session(['egitim_katilim_aktarim' => [
            'firma_id' => $this->firma->id,
            'baslik_anahtari' => $baslikAnahtari,
            'katilimcilar' => $katilimcilar,
        ]]);

        return $this->redirect(EgitimKatilim::getUrl());
    }

    public function gecmisPdf(int $id)
    {
        $kayit = $this->firma?->atamaYazilari()->find($id);

        return $kayit ? AtamaYazisiUretici::pdf($kayit) : null;
    }

    public function gecmisWord(int $id)
    {
        $kayit = $this->firma?->atamaYazilari()->find($id);
        $docx = $kayit ? AtamaYazisiWordUretici::docx($kayit) : null;

        if ($kayit && ! $docx) {
            Notification::make()->title('Bu görev tipi için Word şablonu mevcut değil')->warning()->send();
        }

        return $docx;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->atamaYazilari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
