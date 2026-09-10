<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\KazaIstatistigi;
use App\Support\KazaIstatistigiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Kaza İstatistikleri — firma × yıl. İş Kazası Raporları + Olay Kayıtları
 * (iş kazası tipi) otomatik hesaba katılır; aylık çalışma verisi girilince
 * Kaza Sıklık Oranı ve Kaza Ağırlık Oranı hesaplanır. Yıllık değerlendirme
 * raporunun eki olarak PDF/Excel alınır.
 */
class KazaIstatistikleri extends Page
{
    protected string $view = 'filament.pages.kaza-istatistikleri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'İş Kazaları & Olaylar';

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'kaza-istatistikleri';

    protected static ?string $title = 'Kaza İstatistikleri';

    protected static ?string $navigationLabel = 'Kaza İstatistikleri';

    public ?int $firmaId = null;

    public int $yil;

    public string $standart = 'turkiye_1m';

    /** @var array<int, array{ort_calisan: int, calisma_saati: int}> */
    public array $aylikVeriler = [];

    /** @var array<int, array{tarih: ?string, aciklama: ?string, kayip_gunu: int, olumlu: bool}> */
    public array $hariciKazalar = [];

    public ?string $not = null;

    public function mount(): void
    {
        $this->yil = (int) now()->year;

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }

        $this->yukle();
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
    public function kayit(): ?KazaIstatistigi
    {
        return $this->firma ? KazaIstatistigi::firmaYilIcin($this->firma, $this->yil) : null;
    }

    #[Computed]
    public function aylar(): array
    {
        return config('isg.kaza_istatistik.aylar');
    }

    #[Computed]
    public function standartlar(): array
    {
        return collect(config('isg.kaza_istatistik.standartlar'))->map->__get('ad')->all();
    }

    /** Ekranda anlık gösterilecek özet (kaydedilmemiş aylık veri üzerinden). */
    #[Computed]
    public function ozet(): array
    {
        $kayit = $this->kayit();

        if (! $kayit) {
            return [];
        }

        // Anlık: formdaki değerlerle geçici bir model üzerinden hesapla.
        $gecici = $kayit->replicate();
        $gecici->id = $kayit->id;
        $gecici->exists = true;
        $gecici->standart = $this->standart;
        $gecici->aylik_veriler = $this->aylikVeriler;
        $gecici->harici_kazalar = $this->hariciKazalar;
        $gecici->setRelation('firma', $kayit->firma);

        return [
            'kaza_sayisi' => $gecici->kazaSayisi(),
            'kayip_zamanli' => $gecici->kayipZamanliKazaSayisi(),
            'olumlu' => $gecici->olumluKazaSayisi(),
            'toplam_saat' => $gecici->toplamCalismaSaati(),
            'toplam_kayip_gun' => $gecici->toplamKayipGun(),
            'siklik' => $gecici->siklikOrani(),
            'agirlik' => $gecici->agirlikOrani(),
            'ort_calisan' => $gecici->toplamOrtalamaCalisan(),
            'kazalar' => $gecici->tumKazalar()->all(),
        ];
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->kayit);
        $this->yukle();
    }

    public function updatedYil(): void
    {
        unset($this->kayit);
        $this->yukle();
    }

    private function yukle(): void
    {
        $kayit = $this->kayit();

        $this->standart = $kayit?->standart ?? 'turkiye_1m';
        $this->not = $kayit?->not;
        $this->hariciKazalar = $kayit?->harici_kazalar ?? [];

        $varsayilanSaat = (int) config('isg.kaza_istatistik.aylik_kisi_saat', 175);
        $mevcut = collect($kayit?->aylik_veriler ?? []);
        $portfoyCalisan = $this->firma?->calisanlar()->count() ?? 0;

        $this->aylikVeriler = collect(range(0, 11))->map(function (int $ay) use ($mevcut, $portfoyCalisan, $varsayilanSaat) {
            $satir = $mevcut->get($ay, []);
            $ortCalisan = (int) ($satir['ort_calisan'] ?? $portfoyCalisan);

            return [
                'ort_calisan' => $ortCalisan,
                'calisma_saati' => (int) ($satir['calisma_saati'] ?? $ortCalisan * $varsayilanSaat),
            ];
        })->all();
    }

    /** Bir aya çalışan sayısı girilince çalışma saatini öner (elle değiştirilebilir). */
    public function calismaSaatiOner(int $ay): void
    {
        $varsayilanSaat = (int) config('isg.kaza_istatistik.aylik_kisi_saat', 175);
        $this->aylikVeriler[$ay]['calisma_saati'] = (int) ($this->aylikVeriler[$ay]['ort_calisan'] ?? 0) * $varsayilanSaat;
    }

    public function hariciKazaEkle(): void
    {
        $this->hariciKazalar[] = ['tarih' => null, 'aciklama' => null, 'kayip_gunu' => 0, 'olumlu' => false];
    }

    public function hariciKazaSil(int $index): void
    {
        unset($this->hariciKazalar[$index]);
        $this->hariciKazalar = array_values($this->hariciKazalar);
    }

    public function kaydet(bool $sessiz = false): void
    {
        $kayit = $this->kayit();

        if (! $kayit) {
            Notification::make()->title('Önce firma ve yıl seçin')->danger()->send();

            return;
        }

        $kayit->update([
            'standart' => $this->standart,
            'aylik_veriler' => $this->aylikVeriler,
            'harici_kazalar' => $this->hariciKazalar,
            'not' => $this->not,
        ]);

        unset($this->kayit, $this->ozet);
        $this->yukle();

        if (! $sessiz) {
            Notification::make()->title('Kaza istatistikleri kaydedildi')->success()->send();
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
                ->label('PDF Raporu')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return KazaIstatistigiUretici::pdf($this->kayit());
                }),

            Action::make('excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return KazaIstatistigiUretici::excel($this->kayit());
                }),
        ];
    }
}
