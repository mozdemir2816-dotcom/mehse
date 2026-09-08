<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\PeriyodikKontrol as PeriyodikKontrolModel;
use App\Support\PeriyodikKontrolUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İş Ekipmanları Periyodik Kontrol — kapasite raporundan belirlenen ekipman
 * listesi girilir; her ekipman için yönetmeliğe (EK-3) göre kontrolün yapılıp
 * yapılmadığı, tarih ve sonuç işaretlenir. Sonraki kontrol tarihi periyoda göre
 * otomatik hesaplanır.
 */
class PeriyodikKontrol extends Page
{
    protected string $view = 'filament.pages.periyodik-kontrol';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 28;

    protected static ?string $slug = 'periyodik-kontrol';

    protected static ?string $title = 'İş Ekipmanları Periyodik Kontrol';

    protected static ?string $navigationLabel = 'Periyodik Kontrol';

    public ?int $firmaId = null;

    /** @var array<int, array<string, mixed>> */
    public array $satirlar = [];

    public ?string $genelNot = null;

    // Serbest ekipman ekleme
    public ?string $yeniAd = null;

    public ?string $yeniKategori = null;

    public int $yeniPeriyot = 12;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

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
    public function kontrol(): ?PeriyodikKontrolModel
    {
        return $this->firma ? PeriyodikKontrolModel::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function katalog(): array
    {
        return config('isg.periyodik_kontrol.katalog', []);
    }

    #[Computed]
    public function sonuclar(): array
    {
        return config('isg.periyodik_kontrol.sonuclar', []);
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->kontrol);

        $kontrol = $this->kontrol();
        $this->satirlar = $kontrol?->ekipmanlar ?? [];
        $this->genelNot = $kontrol?->genel_not;
    }

    public function katalogdanEkle(string $kategori, string $ad): void
    {
        $madde = collect(config('isg.periyodik_kontrol.katalog.'.$kategori, []))->firstWhere('ad', $ad);

        $this->satirlar[] = [
            'ad' => $ad,
            'kategori' => $kategori,
            'adet' => 1,
            'tanim' => null,
            'periyot_ay' => $madde['periyot_ay'] ?? 12,
            'son_kontrol_tarihi' => null,
            'kontrol_eden' => null,
            'rapor_no' => null,
            'sonuc' => 'bekliyor',
            'sonraki_kontrol_tarihi' => null,
            'not' => null,
        ];
    }

    public function serbestEkipmanEkle(): void
    {
        if (blank($this->yeniAd)) {
            Notification::make()->title('Ekipman adı gerekli')->danger()->send();

            return;
        }

        $this->satirlar[] = [
            'ad' => $this->yeniAd,
            'kategori' => $this->yeniKategori ?: 'Diğer',
            'adet' => 1,
            'tanim' => null,
            'periyot_ay' => max(1, $this->yeniPeriyot),
            'son_kontrol_tarihi' => null,
            'kontrol_eden' => null,
            'rapor_no' => null,
            'sonuc' => 'bekliyor',
            'sonraki_kontrol_tarihi' => null,
            'not' => null,
        ];

        $this->reset('yeniAd', 'yeniKategori', 'yeniPeriyot');
        $this->yeniPeriyot = 12;
    }

    public function ekipmanSil(int $index): void
    {
        unset($this->satirlar[$index]);
        $this->satirlar = array_values($this->satirlar);
        $this->kaydet(sessiz: true);
    }

    public function kaydet(bool $sessiz = false): void
    {
        $kontrol = $this->kontrol();

        if (! $kontrol) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        // Elle girilen tarih değiştiyse "sonraki" yeniden hesaplansın diye temizle.
        $satirlar = array_map(function (array $s): array {
            if (empty($s['son_kontrol_tarihi'])) {
                $s['sonraki_kontrol_tarihi'] = null;
            }

            return $s;
        }, $this->satirlar);

        $kontrol->update(['ekipmanlar' => $satirlar, 'genel_not' => $this->genelNot]);

        $this->satirlar = $kontrol->fresh()->ekipmanlar ?? [];

        if (! $sessiz) {
            Notification::make()->title('Periyodik kontrol listesi kaydedildi')->success()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => $this->kaydet()),

            Action::make('pdf')
                ->label('PDF (Takip Listesi)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->kontrol()?->ekipmanlar))
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return PeriyodikKontrolUretici::pdf($this->kontrol());
                }),
        ];
    }
}
