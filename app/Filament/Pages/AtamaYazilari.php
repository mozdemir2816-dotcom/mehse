<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\ImzaSecenegi;
use App\Models\AtamaYazisi as AtamaYazisiModel;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use App\Support\AcilEkipAtamaSenkron;
use App\Support\AtamaYazisiUretici;
use App\Support\AtamaYazisiWordUretici;
use App\Support\RiskEkibiOtomatik;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Atama Yazıları — isgpratik 38-44.jpg. 10 görev tipinden biri seçilir;
 * 'tekli' roller tek çalışan (+ görev tarihi aralığı), 'ekip' roller çoklu
 * çalışan (+ baş üye işareti) ile doldurulur, PDF üretilir. 'toplu' rol (Risk
 * Değerlendirme Ekibi) üyeleri sistemden otomatik toplar (RiskEkibiOtomatik),
 * yalnız destek elemanı elle girilir; her üyeye ayrı sayfa basılır.
 */
class AtamaYazilari extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.atama-yazilari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Belgeler & Yazışmalar';

    protected static ?int $navigationSort = 3;

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
    public function topluMu(): bool
    {
        return ($this->rol()['tip'] ?? 'tekli') === 'toplu';
    }

    /** Risk Değerlendirme Ekibi: sistemden otomatik gelen üyeler (ekip görevi => üyeler). */
    #[Computed]
    public function otomatikEkip(): array
    {
        return $this->firma && $this->topluMu() ? RiskEkibiOtomatik::uyeler($this->firma) : [];
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
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar, $this->firmaProfesyonelleri, $this->otomatikEkip);
        $this->isverenVekiliAdi = $this->firma?->isveren_vekili ?: $this->firma?->isveren_ad;
        $this->secilenCalisanIdler = [];
        $this->basUyeId = null;
        $this->kurulGorevleri = [];
        $this->secilenProfesyonelIdler = $this->rolAnahtari === 'isg_kurulu' ? $this->varsayilanProfesyonelIdler() : [];

        if ($this->rolAnahtari === 'isg_kurulu') {
            $this->kurulOtomatikSec();
        }
    }

    public function updatedRolAnahtari(): void
    {
        unset($this->rol, $this->ekipMi, $this->topluMu, $this->otomatikEkip);
        $this->secilenCalisanIdler = [];
        $this->basUyeId = null;
        $this->tekAdSoyad = null;
        $this->tekTc = null;
        $this->tekGorev = null;
        $this->basTemsilci = false;
        $this->tekHizliSecId = null;
        $this->kurulGorevleri = [];
        $this->secilenProfesyonelIdler = $this->rolAnahtari === 'isg_kurulu' ? $this->varsayilanProfesyonelIdler() : [];

        if ($this->rolAnahtari === 'isg_kurulu') {
            $this->kurulOtomatikSec();
        }
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

    /**
     * İSG Kurulu: sistemde kayıtlı kurul üyeleri (Kurul Üyeleri), son çalışan
     * temsilcisi ve işveren vekili atama yazıları firmanın çalışan kaydıyla ad
     * eşleşmesiyle seçili + kurul görevi atanmış gelir; uzman sonra serbestçe
     * ekler/çıkarır.
     */
    public function kurulOtomatikSec(): void
    {
        if (! $this->firma) {
            return;
        }

        $anahtar = fn (?string $ad): string => preg_replace('/\s+/u', ' ', trim(mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], (string) $ad))));
        $calisanIdleri = $this->calisanlar->mapWithKeys(fn (Calisan $c) => [$anahtar($c->ad_soyad) => $c->id]);
        $gorevler = [];

        $ekle = function (?int $calisanId, ?string $ad, string $gorev) use (&$gorevler, $calisanIdleri, $anahtar): void {
            $id = $calisanId && $this->calisanlar->contains('id', $calisanId) ? $calisanId : ($calisanIdleri[$anahtar($ad)] ?? null);

            if ($id && ! isset($gorevler[$id])) {
                $gorevler[$id] = $gorev;
            }
        };

        // Kurul Üyeleri rolü => atama yazısı kurul görevi
        $rolEsleme = ['baskan' => 'baskan', 'sekreter' => 'igu', 'hekim' => 'isyeri_hekimi', 'ik' => 'insan_kaynaklari',
            'calisan_temsilcisi' => 'calisan_temsilcisi', 'sivil_savunma' => 'sivil_savunma', 'formen' => 'usta_formen', 'diger' => 'diger'];

        foreach ($this->firma->kurulUyeleri()->where('aktif', true)->get() as $u) {
            $ekle($u->calisan_id, $u->ad_soyad, $rolEsleme[$u->rol] ?? 'diger');
        }

        foreach (['isveren_vekili' => 'baskan', 'calisan_temsilcisi' => 'calisan_temsilcisi'] as $atamaRolu => $gorev) {
            $son = $this->firma->atamaYazilari()->where('rol_anahtari', $atamaRolu)->latest('tarih')->latest('id')->first();

            foreach ((array) ($son?->uyeler ?? []) as $u) {
                $ekle(null, $u['ad_soyad'] ?? null, $gorev);
            }
        }

        if ($aday = $this->firma->calisanTemsilcisiSecimi?->secilenAday()) {
            $ekle(null, $aday['ad_soyad'] ?? null, 'calisan_temsilcisi');
        }

        $this->secilenCalisanIdler = array_values(array_unique([...$this->secilenCalisanIdler, ...array_keys($gorevler)]));
        $this->kurulGorevleri = $gorevler + $this->kurulGorevleri;
    }

    public function firmaProfilindenDoldur(): void
    {
        $this->secilenCalisanIdler = $this->calisanlar->pluck('id')->all();
    }

    /** @return array<int, array{ad_soyad: string, tc: ?string, gorev: ?string, bas_uye: bool, kase_gorseli?: ?string, imza_gorseli?: ?string}> */
    private function uyeleriTopla(): array
    {
        if ($this->topluMu()) {
            $ekip = $this->otomatikEkip();
            $destek = filled($this->tekAdSoyad) ? [[
                'ad_soyad' => $this->tekAdSoyad,
                'tc' => $this->tekTc,
                'gorev' => $this->tekGorev,
                'bas_uye' => false,
                'ekip_gorevi' => 'Destek Elemanı',
            ]] : [];

            return [
                ...($ekip['İş Güvenliği Uzmanı'] ?? []),
                ...($ekip['İşyeri Hekimi'] ?? []),
                ...($ekip['Çalışan Temsilcisi'] ?? []),
                ...$destek,
                ...($ekip['Bilgi Sahibi Çalışan'] ?? []),
            ];
        }

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
                    'calisan_id' => $c->id,
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

        $alanlar = [
            'tarih' => $this->tarih,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'gorev_baslangic' => $this->ekipMi() ? null : $this->gorevBaslangic,
            'gorev_bitis' => $this->ekipMi() ? null : $this->gorevBitis,
            'uyeler' => $uyeler,
        ];

        // Kaydet → PDF → Word art arda basılınca aynı yazı tekrar tekrar kaydedilmesin.
        $son = $this->firma->atamaYazilari()->where('rol_anahtari', $this->rolAnahtari)->latest('id')->first();
        $ayni = $son
            && $son->tarih?->toDateString() === $alanlar['tarih']
            && $son->isveren_vekili_adi === $alanlar['isveren_vekili_adi']
            && $son->gorev_baslangic?->toDateString() === $alanlar['gorev_baslangic']
            && $son->gorev_bitis?->toDateString() === $alanlar['gorev_bitis']
            && $son->uyeler == $uyeler;

        $kayit = $ayni ? $son : AtamaYazisiModel::create([
            'firma_id' => $this->firma->id,
            'rol_anahtari' => $this->rolAnahtari,
            ...$alanlar,
        ]);

        $eklenen = AcilEkipAtamaSenkron::aktar($kayit);

        if ($eklenen > 0) {
            Notification::make()
                ->title($eklenen.' kişi Acil Durum Ekipleri listesine eklendi')
                ->body('Acil Durum Planı ekip listesi güncellendi.')
                ->success()->send();
        }

        unset($this->gecmisKayitlar);

        return $kayit;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->visible(fn () => $this->firma !== null)
                ->action(function (): void {
                    if ($kayit = $this->kaydet()) {
                        Notification::make()->title('Atama yazısı kaydedildi')->body($kayit->dokuman_no)->success()->send();
                    }
                }),

            Action::make('bosForm')
                ->label('Boş Form (Elle Doldurulacak)')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('Kişi kaydetmeden, ad soyad / T.C. / görev yerleri boş (noktalı) çıktı — firmada kalemle doldurulur. Kayıt oluşturulmaz.')
                ->modalSubmitActionLabel('PDF İndir')
                ->schema([
                    TextInput::make('adet')
                        ->label('Kaç kişilik (sayfa)')
                        ->numeric()->minValue(1)->maxValue(30)
                        ->default(fn () => $this->ekipMi() ? 4 : 1)
                        ->hidden(fn () => $this->topluMu())
                        ->required(fn () => ! $this->topluMu()),
                    Toggle::make('tarihsiz')->label('Tarihi de boş bırak'),
                    ImzaSecenegi::alan(),
                ])
                ->action(fn (array $data) => AtamaYazisiUretici::pdf($this->bosFormKaydi($data), ImzaSecenegi::secili($data))),

            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    Notification::make()->title('Atama yazısı kaydedildi')->body($kayit->dokuman_no)->success()->send();

                    return AtamaYazisiUretici::pdf($kayit, ImzaSecenegi::secili($data));
                }),

            Action::make('word')
                ->label('Word İndir')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn () => $this->firma !== null && AtamaYazisiWordUretici::sablonVarMi($this->rolAnahtari))
                ->schema([ImzaSecenegi::alan()])
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

    /** Kaydedilmeyen, kişi alanları noktalı boş atama yazısı (elle doldurmak için). */
    private function bosFormKaydi(array $data): AtamaYazisiModel
    {
        $bos = AtamaYazisiUretici::BOS_ALAN;
        $uye = fn (?string $ekipGorevi = null) => array_filter([
            'ad_soyad' => $bos, 'tc' => $bos, 'gorev' => $bos, 'bas_uye' => false, 'ekip_gorevi' => $ekipGorevi,
        ], fn ($v) => $v !== null);

        $uyeler = $this->topluMu()
            ? array_map($uye, ['İş Güvenliği Uzmanı', 'İşyeri Hekimi', 'Çalışan Temsilcisi', 'Destek Elemanı', 'Bilgi Sahibi Çalışan'])
            : array_fill(0, max(1, min(30, (int) ($data['adet'] ?? 1))), $uye());

        $kayit = new AtamaYazisiModel([
            'firma_id' => $this->firma->id,
            'rol_anahtari' => $this->rolAnahtari,
            'tarih' => ($data['tarihsiz'] ?? false) ? null : $this->tarih,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'uyeler' => $uyeler,
        ]);
        $kayit->dokuman_no = 'BOŞ FORM';
        $kayit->setRelation('firma', $this->firma);

        return $kayit;
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
