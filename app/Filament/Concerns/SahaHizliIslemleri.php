<?php

namespace App\Filament\Concerns;

use App\Models\IsIzinFormu;
use App\Models\OlayKaydi;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Sahada telefonla yapılan iki hızlı iş (önce ayrı "Saha Hızlı İşlem" sayfası,
 * isgsuite; saha kontrolleri 5. aşama 08.10.2026'dan beri Ziyaret Modu'nda):
 *  - aktif iş iznini (PTW) sahada kapatma + kamera kanıtı,
 *  - ramak kala olayını fotoğrafla kaydetme (Olay Kayıtları'na düşer).
 * Kullanan sayfa $this->firma (seçili Firma) computed'ını sağlar ve
 * WithFileUploads kullanır. Görünüm: filament.pages.partials.saha-hizli-islemler.
 */
trait SahaHizliIslemleri
{
    /** İş izni sahada bu durumlardayken kapatılabilir (trait sabiti PHP 8.2 ister). @return array<int, string> */
    public static function ptwKapatilabilirDurumlar(): array
    {
        return ['onaylandi', 'is_tamamlandi'];
    }

    /** Açık panel: null | 'ptw' | 'ramak' */
    public ?string $hizliIslem = null;

    // --- PTW kapat ---
    public ?int $ptwIzinId = null;

    public ?string $ptwKapanisNotu = null;

    public bool $ptwSahaTeslim = true;

    public $ptwKapanisFoto = null;

    // --- Ramak kala ---
    public ?string $ramakTarih = null;

    public ?string $ramakYer = null;

    public ?string $ramakSiniflandirma = null;

    public ?string $ramakOzet = null;

    public ?string $ramakDetay = null;

    /** @var array<int, mixed> */
    public array $ramakFotolar = [];

    public function hizliIslemAc(?string $panel): void
    {
        $this->hizliIslem = $this->hizliIslem === $panel ? null : $panel;
        $this->ramakTarih ??= now()->toDateString();
    }

    /** @return Collection<int, IsIzinFormu> sahada kapatılabilecek izinler */
    #[Computed]
    public function aktifIzinler(): Collection
    {
        return $this->firma
            ? IsIzinFormu::query()->where('firma_id', $this->firma->id)->whereIn('durum', static::ptwKapatilabilirDurumlar())->latest('baslangic')->latest('id')->get()
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

    public function izniKapat(): void
    {
        $this->validate([
            'ptwIzinId' => ['required', 'integer'],
            'ptwKapanisFoto' => ['nullable', 'image', 'max:15360'],
        ], [], ['ptwIzinId' => 'aktif izin', 'ptwKapanisFoto' => 'kamera kanıtı']);

        $izin = $this->aktifIzinler->firstWhere('id', $this->ptwIzinId);

        if (! $izin) {
            Notification::make()->title('Bu izin sahada kapatılamaz')->body('Yalnız onaylanmış veya işi tamamlanmış izinler kapatılır.')->danger()->send();

            return;
        }

        $izin->update([
            'durum' => 'kapatildi',
            'is_bitis_tarihi' => $izin->is_bitis_tarihi ?? now(),
            'saha_teslim_alindi' => $this->ptwSahaTeslim,
            'kapanis_notu' => $this->ptwKapanisNotu,
            'kapatan' => $this->firma?->igu?->ad_soyad ?? Filament::auth()->user()?->name,
            'kapanis_fotografi' => $this->ptwKapanisFoto?->store('is-izin-kapanis', 'public'),
        ]);

        $this->reset('ptwIzinId', 'ptwKapanisNotu', 'ptwKapanisFoto');
        $this->ptwSahaTeslim = true;
        unset($this->aktifIzinler);

        Notification::make()->title(($izin->izin_no ?: 'İş izni').' sahada kapatıldı')->success()->send();
    }

    public function ramakKalaKaydet(): void
    {
        $this->ramakTarih ??= now()->toDateString();

        $this->validate([
            'ramakTarih' => ['required', 'date'],
            'ramakOzet' => ['required', 'string', 'min:20'],
            'ramakDetay' => ['nullable', 'string', 'min:30'],
            'ramakFotolar' => ['array', 'max:3'],
            'ramakFotolar.*' => ['image', 'max:15360'],
        ], [], ['ramakTarih' => 'tarih', 'ramakOzet' => 'kısa özet', 'ramakDetay' => 'detay', 'ramakFotolar' => 'fotoğraf']);

        abort_unless($this->firma !== null, 403);

        $o = OlayKaydi::create([
            'firma_id' => $this->firma->id,
            'olay_tipi' => 'ramak_kala',
            'durum' => 'acik',
            'olay_tarihi' => $this->ramakTarih,
            'olay_saati' => now()->format('H:i'),
            'olay_yeri' => $this->ramakYer,
            'siniflandirma' => $this->ramakSiniflandirma,
            'olay_ozeti' => trim($this->ramakOzet),
            'olay_detayi' => $this->ramakDetay ? trim($this->ramakDetay) : null,
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

        $this->reset('ramakYer', 'ramakSiniflandirma', 'ramakOzet', 'ramakDetay', 'ramakFotolar');
        $this->ramakTarih = now()->toDateString();
        unset($this->sonRamakKalalar);

        Notification::make()
            ->title('Ramak kala kaydı oluşturuldu')
            ->body($o->belge_no.' — kök neden ve DÖF için Olay Kayıtları\'ndan tamamlayabilirsiniz.')
            ->success()
            ->send();
    }
}
