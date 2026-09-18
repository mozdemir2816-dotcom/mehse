<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Models\ZiyaretProgrami as ZiyaretProgramiModel;
use App\Support\GeminiZiyaretDanismani;
use App\Support\ZiyaretProgramiUretici;
use App\Support\ZiyaretTakvimi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Ziyaret Programı — isgpratik'te ekran görüntüsü yok; kullanıcı onayıyla
 * BASİTLEŞTİRİLMİŞ liste (firma+yıl başına 12 aylık satır) olarak kuruldu.
 * isgpratik'teki tam takvim/sürükle-bırak/çoklu-firma-OSGB arayüzü kapsam
 * dışı bırakıldı.
 */
class ZiyaretProgrami extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.ziyaret-programi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'ziyaret-programi';

    protected static ?string $title = 'Ziyaret Programı';

    protected static ?string $navigationLabel = 'Ziyaret Programı';

    public ?int $firmaId = null;

    public int $yil;

    public ?string $takvimAy = null;

    public ?string $takvimSeciliTarih = null;

    public function mount(): void
    {
        $this->yil = (int) now()->format('Y');
        $this->takvimSeciliTarih = now()->toDateString();

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
    public function program(): ?ZiyaretProgramiModel
    {
        return $this->firma ? ZiyaretProgramiModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    #[Computed]
    public function amacKategorileri(): array
    {
        return config('isg.ziyaret_programi.amac_kategorileri');
    }

    /**
     * Seçili firmanın tarihli ziyaret günleri — Profilim > Firma Ziyaretleri
     * takvimiyle AYNI kaynaktan (ZiyaretTakvimi) türetilir, ayrı bir
     * gruplama mantığı tekrarlanmaz; sadece bu firmaya süzülür.
     *
     * @return array<string, array<int, array{firma: Firma, amac: ?string, sure_saat: mixed, durum: string}>>
     */
    #[Computed]
    public function takvimGunler(): array
    {
        if (! $this->firmaId) {
            return [];
        }

        $gunler = [];

        foreach (ZiyaretTakvimi::gunlukGruplar(Filament::auth()->id()) as $tarih => $oGunkuZiyaretler) {
            $buFirmaninkiler = array_values(array_filter(
                $oGunkuZiyaretler,
                fn (array $z) => $z['firma']?->id === $this->firmaId
            ));

            if ($buFirmaninkiler !== []) {
                $gunler[$tarih] = $buFirmaninkiler;
            }
        }

        return $gunler;
    }

    #[Computed]
    public function takvimGosterilenAy(): string
    {
        return $this->takvimAy ?: $this->yil.'-01';
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->program, $this->takvimGunler);
        $this->takvimAy = null;
    }

    public function updatedYil(): void
    {
        unset($this->program, $this->takvimGunler);
        $this->takvimAy = null;
    }

    public function takvimAyDegistir(int $fark): void
    {
        $this->takvimAy = \Illuminate\Support\Carbon::parse($this->takvimGosterilenAy().'-01')->addMonths($fark)->format('Y-m');
    }

    public function takvimGunSec(string $tarih): void
    {
        $this->takvimSeciliTarih = $tarih;
    }

    /*
    |--------------------------------------------------------------------------
    | Ay satırı düzenleme
    |--------------------------------------------------------------------------
    */

    public function ayGuncelle(int $ayIndex, int $satirIndex, string $alan, mixed $deger): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);

        if (! isset($girdiler[$satirIndex])) {
            return;
        }

        $girdiler[$satirIndex][$alan] = $deger;
        $aylar[$ayIndex] = $girdiler;
        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    public function durumDegistir(int $ayIndex, int $satirIndex): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);

        if (! isset($girdiler[$satirIndex])) {
            return;
        }

        $mevcut = $girdiler[$satirIndex]['durum'] ?? 'bos';
        $siraIndex = array_search($mevcut, ZiyaretProgramiModel::DURUM_SIRASI, true);
        $girdiler[$satirIndex]['durum'] = ZiyaretProgramiModel::DURUM_SIRASI[($siraIndex + 1) % count(ZiyaretProgramiModel::DURUM_SIRASI)];

        $aylar[$ayIndex] = $girdiler;
        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    public function ziyaretEkle(int $ayIndex): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);
        $girdiler[] = ZiyaretProgramiModel::bosGirdi();
        $aylar[$ayIndex] = $girdiler;

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    public function ziyaretSil(int $ayIndex, int $satirIndex): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);

        if (count($girdiler) <= 1 || ! isset($girdiler[$satirIndex])) {
            return;
        }

        unset($girdiler[$satirIndex]);
        $aylar[$ayIndex] = array_values($girdiler);

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    public function aiAmacOner(int $ayIndex, int $satirIndex): void
    {
        $p = $this->program();

        if (! $p || ! $this->firma) {
            return;
        }

        $ayAdi = ZiyaretProgramiModel::AYLAR[$ayIndex] ?? null;

        if (! $ayAdi) {
            return;
        }

        $oneri = GeminiZiyaretDanismani::oner($ayAdi, $this->firma->nace_aciklama, $this->firma->tehlikeSinifiEtiketi());

        if ($oneri) {
            $this->ayGuncelle($ayIndex, $satirIndex, 'amac', $oneri);
        } else {
            Notification::make()->title('AI önerisi alınamadı')->warning()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Çıktı İndir (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->program() !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => ZiyaretProgramiUretici::pdf($this->program())),
        ];
    }
}
