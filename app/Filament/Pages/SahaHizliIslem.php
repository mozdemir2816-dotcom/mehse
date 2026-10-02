<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\OlayKaydi;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Saha Hızlı İşlem (isgsuite "Saha Hızlı İşlem") — telefonda tek ekrandan üç
 * saha işi: (1) aktif iş iznini sahada kapatma + kamera kanıtı, (2) ramak
 * kala olayını fotoğrafla kaydetme (Olay Kayıtları'na düşer), (3) saha
 * denetimi kısayolları. Kayıtlar mevcut modüllerin tablolarına yazılır.
 */
class SahaHizliIslem extends Page
{
    use \App\Filament\Concerns\SinirliErisim;
    use WithFileUploads;

    /** İş izni sahada bu durumlardayken kapatılabilir. */
    public const KAPATILABILIR = ['onaylandi', 'is_tamamlandi'];

    protected string $view = 'filament.pages.saha-hizli-islem';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Kontrolleri';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'saha-hizli-islem';

    protected static ?string $title = 'Saha Hızlı İşlem';

    public string $sekme = 'ptw';

    public ?int $firmaId = null;

    // --- PTW kapat ---
    public ?int $izinId = null;

    public ?string $kapanisNotu = null;

    public bool $sahaTeslim = true;

    public $kapanisFoto = null;

    // --- Ramak kala ---
    public ?string $tarih = null;

    public ?string $yer = null;

    public ?string $siniflandirma = null;

    public ?string $ozet = null;

    public ?string $detay = null;

    /** @var array<int, mixed> */
    public array $ramakFotolar = [];

    public function mount(): void
    {
        $this->tarih = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }

        if (in_array(request()->query('sekme'), ['ptw', 'ramak', 'denetim'], true)) {
            $this->sekme = request()->query('sekme');
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
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    /** @return Collection<int, IsIzinFormu> sahada kapatılabilecek izinler */
    #[Computed]
    public function aktifIzinler(): Collection
    {
        return $this->firma
            ? IsIzinFormu::query()->where('firma_id', $this->firma->id)->whereIn('durum', self::KAPATILABILIR)->latest('baslangic')->latest('id')->get()
            : collect();
    }

    /** @return Collection<int, OlayKaydi> */
    #[Computed]
    public function sonRamakKalalar(): Collection
    {
        return $this->firma
            ? $this->firma->olayKayitlari()->where('olay_tipi', 'ramak_kala')->latest('id')->limit(5)->get()
            : collect();
    }

    public function updatedFirmaId(): void
    {
        $this->izinId = null;
        unset($this->firma, $this->aktifIzinler, $this->sonRamakKalalar);
    }

    /*
    |--------------------------------------------------------------------------
    | PTW kapat
    |--------------------------------------------------------------------------
    */

    public function izniKapat(): void
    {
        $this->validate([
            'firmaId' => ['required', 'integer'],
            'izinId' => ['required', 'integer'],
            'kapanisFoto' => ['nullable', 'image', 'max:15360'],
        ], [], ['firmaId' => 'işyeri', 'izinId' => 'aktif izin', 'kapanisFoto' => 'kamera kanıtı']);

        $izin = $this->aktifIzinler->firstWhere('id', $this->izinId);

        if (! $izin) {
            Notification::make()->title('Bu izin sahada kapatılamaz')->body('Yalnız onaylanmış veya işi tamamlanmış izinler kapatılır.')->danger()->send();

            return;
        }

        $izin->update([
            'durum' => 'kapatildi',
            'is_bitis_tarihi' => $izin->is_bitis_tarihi ?? now(),
            'saha_teslim_alindi' => $this->sahaTeslim,
            'kapanis_notu' => $this->kapanisNotu,
            'kapatan' => $this->firma?->igu?->ad_soyad ?? Filament::auth()->user()?->name,
            'kapanis_fotografi' => $this->kapanisFoto?->store('is-izin-kapanis', 'public'),
        ]);

        $this->reset('izinId', 'kapanisNotu', 'kapanisFoto');
        $this->sahaTeslim = true;
        unset($this->aktifIzinler);

        Notification::make()->title(($izin->izin_no ?: 'İş izni').' sahada kapatıldı')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Ramak kala + foto
    |--------------------------------------------------------------------------
    */

    public function ramakKalaKaydet(): void
    {
        $this->validate([
            'firmaId' => ['required', 'integer'],
            'tarih' => ['required', 'date'],
            'ozet' => ['required', 'string', 'min:20'],
            'detay' => ['nullable', 'string', 'min:30'],
            'ramakFotolar' => ['array', 'max:3'],
            'ramakFotolar.*' => ['image', 'max:15360'],
        ], [], ['firmaId' => 'işyeri', 'ozet' => 'kısa özet', 'detay' => 'detay', 'ramakFotolar' => 'fotoğraf']);

        abort_unless($this->firma !== null, 403);

        $o = OlayKaydi::create([
            'firma_id' => $this->firma->id,
            'olay_tipi' => 'ramak_kala',
            'durum' => 'acik',
            'olay_tarihi' => $this->tarih,
            'olay_saati' => now()->format('H:i'),
            'olay_yeri' => $this->yer,
            'siniflandirma' => $this->siniflandirma,
            'olay_ozeti' => trim($this->ozet),
            'olay_detayi' => $this->detay ? trim($this->detay) : null,
            'etkiler' => ['ramak_kala'],
            'sonuc_turu' => 'yaralanmasiz',
            'bildiren_ad_soyad' => Filament::auth()->user()?->name,
            'bildirim_tarihi' => now()->toDateString(),
            'rapor_hazirlayan' => $this->firma->igu?->ad_soyad,
            'rapor_hazirlayan_kase' => $this->firma->igu?->kase_gorseli,
            'isyeri_hekimi' => $this->firma->isyeriHekimi?->ad_soyad,
            'isveren_vekili' => $this->firma->isveren_vekili ?: $this->firma->isveren_ad,
            'fotograflar' => collect($this->ramakFotolar)->map(fn ($f) => $f->store('olay-kaydi-foto', 'public'))->all(),
        ]);

        $this->reset('yer', 'siniflandirma', 'ozet', 'detay', 'ramakFotolar');
        $this->tarih = now()->toDateString();
        unset($this->sonRamakKalalar);

        Notification::make()
            ->title('Ramak kala kaydı oluşturuldu')
            ->body($o->belge_no.' — kök neden ve DÖF için Olay Kayıtları\'ndan tamamlayabilirsiniz.')
            ->success()
            ->send();
    }
}
