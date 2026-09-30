<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImzaSecenegi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KurulToplantisi as KurulToplantisiModel;
use App\Models\KurulUyesi;
use App\Support\GeminiKararDanismani;
use App\Support\KurulToplantisiUretici;
use App\Support\KurulUyeleri;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İSG Kurulu — isgsuite.tr "İSG Kurulu" modülü düzeni:
 * işyeri bağlamı → metrikler → kalıcı kurul üyeleri (Üye Yönet) → toplantılar
 * (Toplantı Planla) → seçili toplantının katılım / gündem / kararları → PDF.
 * Üyelik kuralları ve Md.6/Md.9: App\Support\KurulUyeleri.
 */
class KurulToplantisi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.kurul-toplantisi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Çalışan & Kurul';

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'kurul-toplantisi';

    protected static ?string $title = 'İSG Kurulu';

    protected static ?string $navigationLabel = 'İSG Kurulu';

    public ?int $firmaId = null;

    public ?int $toplantiId = null;

    // Seçili toplantının künye alanları (detay bölümünde düzenlenir)
    public ?string $toplantiNo = null;

    public ?string $belgeNo = null;

    public ?string $revizyonNo = null;

    public ?string $tarih = null;

    public ?string $saat = null;

    public ?string $bitisSaati = null;

    public ?string $yer = null;

    public ?string $baskan = null;

    public ?string $tur = 'olagan';

    public ?string $durum = 'taslak';

    public ?string $sonrakiToplanti = null;

    public ?string $notlar = null;

    // Üye Yönet modalı
    public string $uyeArama = '';

    public string $uyeRol = 'diger';

    public ?string $elleUyeAd = null;

    public ?string $elleUyeGorev = null;

    // Katılımcı ekleme formu
    public ?string $yeniKatilimciAd = null;

    public ?string $yeniKatilimciGorev = null;

    // Gündem ekleme
    public ?string $yeniGundemMaddesi = null;

    // Karar ekleme / düzenleme
    public ?int $kararGundemIndex = null;

    /** Kayıtlı bir kararı düzenlerken o kararın kararlar[] içindeki indeksi. */
    public ?int $duzenlenenKararIndex = null;

    public ?string $yeniKararMetni = null;

    public ?string $yeniKararSorumlu = null;

    public ?string $yeniKararTermin = null;

    public function mount(): void
    {
        $this->firmaId = request()->integer('firma') ?: (int) array_key_first($this->firmalar()) ?: null;
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
        return $this->firma?->calisanlar()->where('aktif', true)->orderBy('ad_soyad')->get() ?? collect();
    }

    /** @return Collection<int, KurulUyesi> */
    #[Computed]
    public function uyeler(): Collection
    {
        return $this->firma ? KurulUyeleri::aktifUyeler($this->firma) : collect();
    }

    /** @return array<string, string> */
    #[Computed]
    public function eksikZorunlular(): array
    {
        return $this->firma ? KurulUyeleri::eksikZorunlular($this->firma) : [];
    }

    /** @return array<string, array{ad_soyad: string, gorev: ?string}|null> */
    #[Computed]
    public function oneriler(): array
    {
        return $this->firma ? KurulUyeleri::oneriler($this->firma) : [];
    }

    /** @return Collection<int, Calisan> Üye Yönet: aramaya uyan, henüz üye olmayan çalışanlar */
    #[Computed]
    public function adayCalisanlar(): Collection
    {
        $uyeAdlari = $this->uyeler->pluck('ad_soyad')->map(fn ($a) => mb_strtolower($a))->all();
        $arama = mb_strtolower(trim($this->uyeArama));

        return $this->calisanlar
            ->reject(fn (Calisan $c) => in_array(mb_strtolower($c->ad_soyad), $uyeAdlari, true))
            ->filter(fn (Calisan $c) => $arama === ''
                || str_contains(mb_strtolower($c->ad_soyad.' '.$c->gorev.' '.$c->departman), $arama))
            ->values();
    }

    /** @return Collection<int, KurulToplantisiModel> */
    #[Computed]
    public function toplantilar(): Collection
    {
        return $this->firma?->kurulToplantilari()->latest('tarih')->latest()->get() ?? collect();
    }

    /** @return array{aktif_uye: int, planli: int, toplam: int, eksik: int} */
    #[Computed]
    public function metrikler(): array
    {
        return [
            'aktif_uye' => $this->uyeler->count(),
            'planli' => $this->toplantilar->filter(fn (KurulToplantisiModel $t) => $t->tarih?->gte(today()))->count(),
            'toplam' => $this->toplantilar->count(),
            'eksik' => count($this->eksikZorunlular),
        ];
    }

    #[Computed]
    public function toplanti(): ?KurulToplantisiModel
    {
        return $this->toplantiId
            ? $this->firma?->kurulToplantilari()->find($this->toplantiId)
            : null;
    }

    #[Computed]
    public function hazirGundemMaddeleri(): array
    {
        return config('isg.kurul_toplantisi.hazir_gundem_maddeleri', []);
    }

    #[Computed]
    public function katilimciGorevleri(): array
    {
        return config('isg.kurul_toplantisi.katilimci_gorevleri', []);
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiKararDanismani::aktifMi();
    }

    private function onbellekTemizle(): void
    {
        unset($this->firma, $this->calisanlar, $this->uyeler, $this->eksikZorunlular, $this->oneriler,
            $this->adayCalisanlar, $this->toplantilar, $this->metrikler, $this->toplanti);
    }

    public function updatedFirmaId(): void
    {
        $this->onbellekTemizle();
        $this->toplantiId = null;
    }

    public function updatedUyeArama(): void
    {
        unset($this->adayCalisanlar);
    }

    /*
    |--------------------------------------------------------------------------
    | Kurul üyeleri (Üye Yönet modalı)
    |--------------------------------------------------------------------------
    */

    /** Firma kaydından önerilen kişiyi o rolle üye yapar (işveren/İGU/hekim/temsilci). */
    public function oneriyiEkle(string $rol): void
    {
        $oneri = $this->oneriler[$rol] ?? null;

        if (! $this->firma || ! $oneri) {
            return;
        }

        $this->uyeKaydet($rol, $oneri['ad_soyad'], $oneri['gorev']);
    }

    public function calisaniEkle(int $calisanId): void
    {
        $c = $this->calisanlar->firstWhere('id', $calisanId);

        if (! $c || ! array_key_exists($this->uyeRol, KurulUyeleri::roller())) {
            return;
        }

        $this->uyeKaydet($this->uyeRol, $c->ad_soyad, $c->gorev, $c->id);
    }

    public function elleUyeEkle(): void
    {
        if (blank($this->elleUyeAd) || ! array_key_exists($this->uyeRol, KurulUyeleri::roller())) {
            Notification::make()->title('Ad soyad ve kurul rolü gerekli')->warning()->send();

            return;
        }

        $this->uyeKaydet($this->uyeRol, trim($this->elleUyeAd), $this->elleUyeGorev);
        $this->reset('elleUyeAd', 'elleUyeGorev');
    }

    private function uyeKaydet(string $rol, string $adSoyad, ?string $gorev, ?int $calisanId = null): void
    {
        $this->firma?->kurulUyeleri()->create([
            'rol' => $rol,
            'ad_soyad' => $adSoyad,
            'gorev' => $gorev,
            'calisan_id' => $calisanId,
            'aktif' => true,
        ]);

        $this->onbellekTemizle();
        Notification::make()->title("{$adSoyad} kurula eklendi")->success()->send();
    }

    public function uyeKaldir(int $id): void
    {
        $this->firma?->kurulUyeleri()->whereKey($id)->delete();
        $this->onbellekTemizle();
    }

    /*
    |--------------------------------------------------------------------------
    | Toplantılar
    |--------------------------------------------------------------------------
    */

    public function toplantiSec(int $id): void
    {
        $this->toplantiId = $id;
        unset($this->toplanti);

        $t = $this->toplanti();

        if ($t) {
            $this->toplantiNo = $t->toplanti_no;
            $this->belgeNo = $t->belge_no;
            $this->revizyonNo = $t->revizyon_no;
            $this->tarih = $t->tarih?->toDateString();
            $this->saat = $t->saat;
            $this->bitisSaati = $t->bitis_saati;
            $this->yer = $t->yer;
            $this->baskan = $t->baskan;
            $this->tur = $t->tur ?: 'olagan';
            $this->durum = $t->durum ?: 'taslak';
            $this->sonrakiToplanti = $t->sonraki_toplanti?->toDateString();
            $this->notlar = $t->notlar;
        }
    }

    public function toplantiKapat(): void
    {
        $this->toplantiId = null;
        unset($this->toplanti);
    }

    /** Zorunlu üye eksikken resmî durum (planlandı/tamamlandı) verilemez. */
    private function izinliDurum(string $durum): string
    {
        return $this->eksikZorunlular && $durum !== 'taslak' ? 'taslak' : $durum;
    }

    public function toplantiBilgileriniKaydet(): void
    {
        $t = $this->toplanti();

        if (! $t) {
            return;
        }

        $durum = $this->izinliDurum((string) $this->durum);

        $t->update([
            'toplanti_no' => $this->toplantiNo,
            'belge_no' => $this->belgeNo,
            'revizyon_no' => $this->revizyonNo,
            'tarih' => $this->tarih,
            'saat' => $this->saat,
            'bitis_saati' => $this->bitisSaati,
            'yer' => $this->yer,
            'baskan' => $this->baskan,
            'tur' => $this->tur ?: 'olagan',
            'durum' => $durum,
            'sonraki_toplanti' => $this->sonrakiToplanti ?: null,
            'notlar' => $this->notlar,
        ]);

        unset($this->toplantilar, $this->metrikler, $this->toplanti);

        if ($durum !== $this->durum) {
            $this->durum = $durum;
            Notification::make()->title('Toplantı taslak olarak kaydedildi')
                ->body('Eksik zorunlu kurul üyeleri tamamlanmadan toplantı "Planlandı" / "Tamamlandı" yapılamaz.')
                ->warning()->send();

            return;
        }

        Notification::make()->title('Toplantı bilgileri kaydedildi')->success()->send();
    }

    public function toplantiSil(int $id): void
    {
        $this->firma?->kurulToplantilari()->find($id)?->delete();

        if ($this->toplantiId === $id) {
            $this->toplantiId = null;
        }

        unset($this->toplantilar, $this->metrikler, $this->toplanti);
    }

    /*
    |--------------------------------------------------------------------------
    | Katılımcılar (toplantıya ait snapshot)
    |--------------------------------------------------------------------------
    */

    /** Güncel kurul üyelerini toplantıya yeniden aktarır; mevcut katılım işaretleri korunur. */
    public function uyeleriAktar(): void
    {
        $t = $this->toplanti();

        if (! $t || ! $this->firma) {
            return;
        }

        $eski = collect($t->katilimcilar ?? [])->keyBy(fn ($k) => mb_strtolower($k['ad_soyad'] ?? ''));
        $yeni = collect(KurulUyeleri::katilimciSnapshot($this->firma))
            ->map(fn (array $k) => [...$k, 'katildi' => $eski->get(mb_strtolower($k['ad_soyad']))['katildi'] ?? true]);
        $elle = $eski->reject(fn ($k, $ad) => $yeni->contains(fn ($y) => mb_strtolower($y['ad_soyad']) === $ad))->values();

        $t->update(['katilimcilar' => $yeni->concat($elle)->values()->all()]);
        unset($this->toplanti);
    }

    public function katilimciEkle(): void
    {
        $t = $this->toplanti();

        if (! $t || blank($this->yeniKatilimciAd)) {
            return;
        }

        $katilimcilar = $t->katilimcilar ?? [];
        $katilimcilar[] = [
            'ad_soyad' => $this->yeniKatilimciAd,
            'gorev' => $this->yeniKatilimciGorev,
            'rol' => null,
            'katildi' => true,
        ];
        $t->update(['katilimcilar' => $katilimcilar]);

        $this->reset('yeniKatilimciAd', 'yeniKatilimciGorev');
    }

    /** Kurul dışı bir firma çalışanını bu toplantıya katılımcı olarak ekler. */
    public function katilimHizliEkle(int $calisanId): void
    {
        $t = $this->toplanti();
        $c = $this->calisanlar->firstWhere('id', $calisanId);

        if (! $t || ! $c) {
            return;
        }

        $katilimcilar = $t->katilimcilar ?? [];
        $katilimcilar[] = ['ad_soyad' => $c->ad_soyad, 'gorev' => $c->gorev, 'rol' => null, 'katildi' => true];
        $t->update(['katilimcilar' => $katilimcilar]);
    }

    public function katilimToggle(int $index): void
    {
        $t = $this->toplanti();
        $katilimcilar = $t?->katilimcilar ?? [];

        if (! $t || ! isset($katilimcilar[$index])) {
            return;
        }

        $katilimcilar[$index]['katildi'] = ! ($katilimcilar[$index]['katildi'] ?? false);
        $t->update(['katilimcilar' => $katilimcilar]);
    }

    public function katilimciSil(int $index): void
    {
        $t = $this->toplanti();

        if (! $t) {
            return;
        }

        $katilimcilar = $t->katilimcilar ?? [];
        unset($katilimcilar[$index]);
        $t->update(['katilimcilar' => array_values($katilimcilar)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Gündem
    |--------------------------------------------------------------------------
    */

    public function gundemEkle(): void
    {
        $t = $this->toplanti();

        if (! $t || blank($this->yeniGundemMaddesi)) {
            return;
        }

        $gundem = $t->gundem ?? [];
        $gundem[] = $this->yeniGundemMaddesi;
        $t->update(['gundem' => $gundem]);

        $this->reset('yeniGundemMaddesi');
    }

    public function hazirGundemEkle(string $madde): void
    {
        $t = $this->toplanti();

        if (! $t) {
            return;
        }

        $gundem = $t->gundem ?? [];

        if (! in_array($madde, $gundem, true)) {
            $gundem[] = $madde;
            $t->update(['gundem' => $gundem]);
        }
    }

    public function gundemSil(int $index): void
    {
        $t = $this->toplanti();

        if (! $t) {
            return;
        }

        $gundem = $t->gundem ?? [];
        unset($gundem[$index]);
        $t->update(['gundem' => array_values($gundem)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Kararlar
    |--------------------------------------------------------------------------
    */

    public function kararFormuAc(int $gundemIndex): void
    {
        $this->kararGundemIndex = $gundemIndex;
        $this->duzenlenenKararIndex = null;
        $this->yeniKararMetni = null;
        $this->yeniKararSorumlu = null;
        $this->yeniKararTermin = null;
    }

    /** Kayıtlı bir kararı düzenlemeye açar — metin/sorumlu/termin alanları doldurulur. */
    public function kararDuzenle(int $index): void
    {
        $karar = $this->toplanti()?->kararlar[$index] ?? null;

        if (! $karar) {
            return;
        }

        $this->kararGundemIndex = null;
        $this->duzenlenenKararIndex = $index;
        $this->yeniKararMetni = $karar['karar_metni'] ?? null;
        $this->yeniKararSorumlu = $karar['sorumlu'] ?? null;
        $this->yeniKararTermin = $karar['termin'] ?? null;
    }

    public function kararDuzenlemeIptal(): void
    {
        $this->duzenlenenKararIndex = null;
        $this->reset('yeniKararMetni', 'yeniKararSorumlu', 'yeniKararTermin');
    }

    /** Düzenlenen kararın metin/sorumlu/termin alanlarını günceller (gündem + durum korunur). */
    public function kararGuncelle(): void
    {
        $t = $this->toplanti();
        $kararlar = $t?->kararlar ?? [];

        if (! $t || $this->duzenlenenKararIndex === null
            || ! isset($kararlar[$this->duzenlenenKararIndex])
            || blank($this->yeniKararMetni)) {
            return;
        }

        $kararlar[$this->duzenlenenKararIndex] = [
            ...$kararlar[$this->duzenlenenKararIndex],
            'karar_metni' => $this->yeniKararMetni,
            'sorumlu' => $this->yeniKararSorumlu,
            'termin' => $this->yeniKararTermin,
        ];
        $t->update(['kararlar' => $kararlar]);

        $this->kararDuzenlemeIptal();
        Notification::make()->title('Karar güncellendi')->success()->send();
    }

    public function kararAiOner(): void
    {
        $t = $this->toplanti();
        $gundem = $t?->gundem ?? [];

        if ($this->kararGundemIndex === null || ! isset($gundem[$this->kararGundemIndex])) {
            return;
        }

        $oneri = GeminiKararDanismani::oner($gundem[$this->kararGundemIndex], $this->firma?->nace_aciklama);

        if ($oneri) {
            $this->yeniKararMetni = $oneri;
        } else {
            Notification::make()->title('AI önerisi alınamadı')->warning()->send();
        }
    }

    public function kararEkle(): void
    {
        $t = $this->toplanti();
        $gundem = $t?->gundem ?? [];

        if (! $t || $this->kararGundemIndex === null || ! isset($gundem[$this->kararGundemIndex]) || blank($this->yeniKararMetni)) {
            return;
        }

        $kararlar = $t->kararlar ?? [];
        $kararlar[] = [
            'gundem_maddesi' => $gundem[$this->kararGundemIndex],
            'karar_metni' => $this->yeniKararMetni,
            'sorumlu' => $this->yeniKararSorumlu,
            'termin' => $this->yeniKararTermin,
            'durum' => 'beklemede',
        ];
        $t->update(['kararlar' => $kararlar]);

        $this->kararGundemIndex = null;
        $this->reset('yeniKararMetni', 'yeniKararSorumlu', 'yeniKararTermin');
    }

    public function kararDurumGuncelle(int $index, string $durum): void
    {
        $t = $this->toplanti();
        $kararlar = $t?->kararlar ?? [];

        if (! $t || ! isset($kararlar[$index])) {
            return;
        }

        $kararlar[$index]['durum'] = $durum;
        $t->update(['kararlar' => $kararlar]);
    }

    public function kararSil(int $index): void
    {
        $t = $this->toplanti();

        if (! $t) {
            return;
        }

        $kararlar = $t->kararlar ?? [];
        unset($kararlar[$index]);
        $t->update(['kararlar' => array_values($kararlar)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Başlık aksiyonları: Üye Yönet · Toplantı Planla · PDF · Excel
    |--------------------------------------------------------------------------
    */

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uyeYonet')
                ->label('Üye Yönet')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->disabled(fn () => ! $this->firma)
                ->modalHeading(fn () => 'Kurul Üyesi Seçimi — '.($this->firma?->unvan ?? ''))
                ->modalDescription('Zorunlu üyeler işyeri görevlendirmelerinden önerilir; diğer üyeleri aktif personel arasından ya da elle ekleyin.')
                ->modalWidth(Width::FiveExtraLarge)
                ->modalContent(fn () => view('filament.pages.partials.kurul-uye-yonet'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            $this->toplantiPlanlaAction(),

            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->toplanti() !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => KurulToplantisiUretici::pdf($this->toplanti())),

            Action::make('excel')
                ->label('Excel İndir')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->toplanti() !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => KurulToplantisiUretici::excel($this->toplanti())),
        ];
    }

    private function toplantiPlanlaAction(): Action
    {
        return Action::make('toplantiPlanla')
            ->label('Toplantı Planla')
            ->icon('heroicon-o-calendar-days')
            ->disabled(fn () => ! $this->firma)
            ->modalHeading(fn () => 'Yeni İSG Kurulu Toplantısı — '.($this->firma?->unvan ?? ''))
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitActionLabel('Toplantıyı Kaydet')
            ->fillForm(function (): array {
                $tarih = now()->toDateString();

                return [
                    'tarih' => $tarih,
                    'toplanti_no' => KurulToplantisiModel::sonrakiNo($this->firma, $tarih),
                    'revizyon_no' => '00',
                    'saat' => '14:00',
                    'tur' => 'olagan',
                    'durum' => 'taslak',
                    'sonraki_toplanti' => KurulUyeleri::sonrakiToplanti($this->firma, $tarih)->toDateString(),
                    'gundem_hazir' => ['Bir önceki toplantı kararlarının gözden geçirilmesi'],
                ];
            })
            ->schema([
                Callout::make('Resmî durum engeli')
                    ->description(fn () => 'Eksik zorunlu üyeler ('.implode(', ', $this->eksikZorunlular)
                        .') tamamlanmadan toplantı yalnız "Taslak" olarak kaydedilebilir. "Üye Yönet" ile tamamlayın.')
                    ->status('warning')
                    ->visible(fn () => (bool) $this->eksikZorunlular),

                Grid::make(['default' => 1, 'md' => 2])->schema([
                    DatePicker::make('tarih')->label('Toplantı tarihi')->required()->native(false)->displayFormat('d.m.Y')
                        ->live()
                        ->afterStateUpdated(fn (?string $state, Set $set) => $state
                            ? $set('sonraki_toplanti', KurulUyeleri::sonrakiToplanti($this->firma, $state)->toDateString())
                            : null),
                    TextInput::make('toplanti_no')->label('Toplantı no')->maxLength(50),
                    TextInput::make('belge_no')->label('Belge no')->maxLength(50),
                    TextInput::make('revizyon_no')->label('Revizyon')->maxLength(10),
                    TimePicker::make('saat')->label('Başlangıç')->seconds(false),
                    TimePicker::make('bitis_saati')->label('Bitiş')->seconds(false),
                    TextInput::make('yer')->label('Toplantı yeri')->placeholder('Örn: Toplantı salonu')->maxLength(255),
                    DatePicker::make('sonraki_toplanti')->label('Sonraki toplantı')->native(false)->displayFormat('d.m.Y')
                        ->helperText(fn () => 'Yönetmelik Md.9 — '.KurulUyeleri::periyotEtiketi($this->firma).' ('
                            .config('isg.tehlike_siniflari.'.$this->firma?->tehlike_sinifi, '—').')'),
                    Select::make('tur')->label('Toplantı türü')->options(config('isg.kurul_toplantisi.turler'))->required(),
                    Select::make('durum')->label('Durum')->required()
                        ->options(fn () => $this->eksikZorunlular
                            ? ['taslak' => 'Taslak']
                            : config('isg.kurul_toplantisi.durumlar')),
                ]),

                Section::make('Gündem')->compact()->schema([
                    CheckboxList::make('gundem_hazir')->hiddenLabel()
                        ->options(fn () => collect(config('isg.kurul_toplantisi.hazir_gundem_maddeleri', []))
                            ->flatten()->mapWithKeys(fn (string $m) => [$m => $m])->all())
                        ->columns(2)->searchable()->bulkToggleable(),
                    Textarea::make('gundem_ek')->label('Ek gündem maddeleri')->rows(3)
                        ->helperText('Her satır ayrı bir gündem maddesi olur.'),
                ]),

                Textarea::make('notlar')->label('Notlar')->rows(2),
            ])
            ->action(function (array $data): void {
                $firma = $this->firma;

                if (! $firma) {
                    return;
                }

                $gundem = collect($data['gundem_hazir'] ?? [])
                    ->concat(preg_split('/\r\n|\r|\n/', (string) ($data['gundem_ek'] ?? '')) ?: [])
                    ->map(fn ($m) => trim((string) $m))->filter()->unique()->values()->all();

                $baskan = $this->uyeler->firstWhere('rol', 'baskan');
                $durum = $this->izinliDurum((string) $data['durum']);

                $toplanti = $firma->kurulToplantilari()->create([
                    'toplanti_no' => $data['toplanti_no'] ?: KurulToplantisiModel::sonrakiNo($firma, $data['tarih']),
                    'belge_no' => $data['belge_no'] ?? null,
                    'revizyon_no' => $data['revizyon_no'] ?? null,
                    'tarih' => $data['tarih'],
                    'saat' => $data['saat'] ? substr((string) $data['saat'], 0, 5) : null,
                    'bitis_saati' => $data['bitis_saati'] ? substr((string) $data['bitis_saati'], 0, 5) : null,
                    'yer' => $data['yer'] ?? null,
                    'baskan' => $baskan?->ad_soyad,
                    'tur' => $data['tur'],
                    'durum' => $durum,
                    'sonraki_toplanti' => $data['sonraki_toplanti'] ?? null,
                    'katilimcilar' => KurulUyeleri::katilimciSnapshot($firma),
                    'gundem' => $gundem,
                    'kararlar' => [],
                    'notlar' => $data['notlar'] ?? null,
                ]);

                unset($this->toplantilar, $this->metrikler);
                $this->toplantiSec($toplanti->id);

                Notification::make()
                    ->title('Toplantı kaydedildi')
                    ->body('Katılımcılar güncel kurul üyelerinden tarihsel kopya olarak sabitlendi.')
                    ->success()->send();
            });
    }
}
