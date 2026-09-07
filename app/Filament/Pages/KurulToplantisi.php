<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KurulToplantisi as KurulToplantisiModel;
use App\Support\GeminiKararDanismani;
use App\Support\KurulToplantisiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İSG Kurulu Toplantısı — isgpratik yardım/kurul-toplantisi rehberi.
 * Toplantı oluşturma → katılımcı yönetimi → gündem → kararlar → PDF tutanak.
 */
class KurulToplantisi extends Page
{
    protected string $view = 'filament.pages.kurul-toplantisi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'kurul-toplantisi';

    protected static ?string $title = 'İSG Kurul Toplantısı';

    protected static ?string $navigationLabel = 'Kurul Toplantısı';

    public ?int $firmaId = null;

    public ?int $toplantiId = null;

    public ?string $toplantiNo = null;

    public ?string $tarih = null;

    public ?string $saat = '14:00';

    public ?string $yer = null;

    public ?string $baskan = null;

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
        $this->tarih = now()->toDateString();

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

    /** @return Collection<int, KurulToplantisiModel> */
    #[Computed]
    public function toplantilar(): Collection
    {
        return $this->firma?->kurulToplantilari()->latest('tarih')->latest()->get() ?? collect();
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

    /*
    |--------------------------------------------------------------------------
    | Toplantı listesi
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->toplantilar);
        $this->toplantiId = null;
    }

    public function yeniToplanti(): void
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $tarih = $this->tarih ?: now()->toDateString();

        $toplanti = $this->firma->kurulToplantilari()->create([
            'toplanti_no' => KurulToplantisiModel::sonrakiNo($this->firma, $tarih),
            'tarih' => $tarih,
            'saat' => $this->saat,
            'yer' => $this->yer,
            'baskan' => $this->baskan,
            'katilimcilar' => [],
            'gundem' => [],
            'kararlar' => [],
        ]);

        unset($this->toplantilar);
        $this->toplantiSec($toplanti->id);
    }

    public function toplantiSec(int $id): void
    {
        $this->toplantiId = $id;
        unset($this->toplanti);

        $t = $this->toplanti();

        if ($t) {
            $this->toplantiNo = $t->toplanti_no;
            $this->tarih = $t->tarih?->toDateString();
            $this->saat = $t->saat;
            $this->yer = $t->yer;
            $this->baskan = $t->baskan;
        }
    }

    public function toplantiBilgileriniKaydet(): void
    {
        $this->toplanti()?->update([
            'toplanti_no' => $this->toplantiNo,
            'tarih' => $this->tarih,
            'saat' => $this->saat,
            'yer' => $this->yer,
            'baskan' => $this->baskan,
        ]);

        unset($this->toplantilar);
        Notification::make()->title('Toplantı bilgileri kaydedildi')->success()->send();
    }

    public function toplantiSil(int $id): void
    {
        $this->firma?->kurulToplantilari()->find($id)?->delete();

        if ($this->toplantiId === $id) {
            $this->toplantiId = null;
            unset($this->toplanti);
        }

        unset($this->toplantilar);
    }

    /*
    |--------------------------------------------------------------------------
    | Katılımcı yönetimi
    |--------------------------------------------------------------------------
    */

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
            'katildi' => true,
        ];
        $t->update(['katilimcilar' => $katilimcilar]);

        $this->reset('yeniKatilimciAd', 'yeniKatilimciGorev');
    }

    public function katilimHizliEkle(int $calisanId): void
    {
        $t = $this->toplanti();
        $c = $this->calisanlar->firstWhere('id', $calisanId);

        if (! $t || ! $c) {
            return;
        }

        $katilimcilar = $t->katilimcilar ?? [];
        $katilimcilar[] = ['ad_soyad' => $c->ad_soyad, 'gorev' => $c->gorev, 'katildi' => true];
        $t->update(['katilimcilar' => $katilimcilar]);
    }

    public function katilimToggle(int $index): void
    {
        $t = $this->toplanti();

        if (! $t) {
            return;
        }

        $katilimcilar = $t->katilimcilar ?? [];

        if (! isset($katilimcilar[$index])) {
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

        if (! $t) {
            return;
        }

        $kararlar = $t->kararlar ?? [];

        if (! isset($kararlar[$index])) {
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
    | PDF
    |--------------------------------------------------------------------------
    */

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->toplanti() !== null)
                ->action(fn () => KurulToplantisiUretici::pdf($this->toplanti())),

            Action::make('excel')
                ->label('Excel İndir')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->toplanti() !== null)
                ->action(fn () => KurulToplantisiUretici::excel($this->toplanti())),
        ];
    }
}
