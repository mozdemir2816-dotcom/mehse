<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\YanginGuvenligiDegerlendirmesi as YanginModel;
use App\Support\YanginGuvenligiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Yangın Güvenliği Genel Durum Değerlendirmesi — bina kullanımı, kat/kullanıcı
 * yükü, bölümler ve faaliyet/depolama risklerinden bina yangın tehlike sınıfı
 * belirlenir; kütüphaneden tespitler seçilip rapor (PDF) üretilir.
 */
class YanginGuvenligi extends Page
{
    protected string $view = 'filament.pages.yangin-guvenligi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static string|UnitEnum|null $navigationGroup = 'Acil Durum & Yangın';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'yangin-guvenligi';

    protected static ?string $title = 'Yangın Güvenliği Değerlendirmesi';

    protected static ?string $navigationLabel = 'Yangın Güvenliği';

    public ?int $firmaId = null;

    public ?string $incelemeYeri = null;

    public ?string $yapiDurumu = null;

    public ?string $kullanimAciklamasi = null;

    public ?string $kullanimTuru = null;

    public ?string $tabanAlaniM2 = null;

    public ?int $katSayisi = null;

    public ?int $kullaniciYuku = null;

    /** @var array<string, bool> */
    public array $ozelKullanimlar = [];

    /** @var array<int, string> */
    public array $bolumler = [];

    /** @var array<int, string> */
    public array $riskler = [];

    /** @var array<int, array{madde: string, oncelik: string}> */
    public array $tespitler = [];

    public bool $sinifElle = false;

    public ?string $elleSinif = null;

    public ?string $genelNot = null;

    public ?string $degerlendirmeTarihi = null;

    public function mount(): void
    {
        $this->degerlendirmeTarihi = now()->toDateString();

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
    public function kayit(): ?YanginModel
    {
        return $this->firma ? YanginModel::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function kullanimTurleri(): array
    {
        return config('isg.yangin_guvenligi.kullanim_turleri');
    }

    #[Computed]
    public function yapiDurumlari(): array
    {
        return config('isg.yangin_guvenligi.yapi_durumlari');
    }

    #[Computed]
    public function bolumKutuphanesi(): array
    {
        return config('isg.yangin_guvenligi.bolum_kutuphanesi');
    }

    #[Computed]
    public function riskKutuphanesi(): array
    {
        return config('isg.yangin_guvenligi.risk_kutuphanesi');
    }

    #[Computed]
    public function tespitKutuphanesi(): array
    {
        return config('isg.yangin_guvenligi.tespit_kutuphanesi');
    }

    #[Computed]
    public function siniflar(): array
    {
        return config('isg.yangin_guvenligi.tehlike_siniflari');
    }

    /** Anlık hesaplanan tehlike sınıfı (kaydetmeden). */
    #[Computed]
    public function anlikSinif(): string
    {
        $gecici = new YanginModel([
            'kullanim_turu' => $this->kullanimTuru,
            'kullanici_yuku' => $this->kullaniciYuku,
            'riskler' => $this->riskler,
        ]);

        return $this->sinifElle && $this->elleSinif
            ? $this->elleSinif
            : $gecici->hesaplaTehlikeSinifi();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->kayit);

        $k = $this->kayit();
        $this->incelemeYeri = $k?->inceleme_yeri;
        $this->yapiDurumu = $k?->yapi_durumu;
        $this->kullanimAciklamasi = $k?->kullanim_aciklamasi;
        $this->kullanimTuru = $k?->kullanim_turu;
        $this->tabanAlaniM2 = $k?->taban_alani_m2 ? (string) $k->taban_alani_m2 : null;
        $this->katSayisi = $k?->kat_sayisi;
        $this->kullaniciYuku = $k?->kullanici_yuku;
        $this->ozelKullanimlar = $k?->ozel_kullanimlar ?? [];
        $this->bolumler = $k?->bolumler ?? [];
        $this->riskler = $k?->riskler ?? [];
        $this->tespitler = $k?->tespitler ?? [];
        $this->sinifElle = (bool) ($k?->sinif_elle);
        $this->elleSinif = $k?->sinif_elle ? $k->belirlenen_tehlike_sinifi : null;
        $this->genelNot = $k?->genel_not;
        $this->degerlendirmeTarihi = $k?->degerlendirme_tarihi?->toDateString() ?? now()->toDateString();
    }

    public function tespitEkle(int $index): void
    {
        $madde = $this->tespitKutuphanesi[$index] ?? null;

        if (! $madde) {
            return;
        }

        if (collect($this->tespitler)->contains(fn ($t) => $t['madde'] === $madde['madde'])) {
            return;
        }

        $this->tespitler[] = $madde;
    }

    public function tespitSil(int $index): void
    {
        unset($this->tespitler[$index]);
        $this->tespitler = array_values($this->tespitler);
    }

    public function serbestTespitEkle(string $metin): void
    {
        if (trim($metin) === '') {
            return;
        }

        $this->tespitler[] = ['madde' => trim($metin), 'oncelik' => 'orta'];
    }

    public function kaydet(bool $sessiz = false): void
    {
        $k = $this->kayit();

        if (! $k) {
            Notification::make()->title('Önce bir firma seçin')->danger()->send();

            return;
        }

        $k->fill([
            'inceleme_yeri' => $this->incelemeYeri,
            'yapi_durumu' => $this->yapiDurumu,
            'kullanim_aciklamasi' => $this->kullanimAciklamasi,
            'kullanim_turu' => $this->kullanimTuru,
            'taban_alani_m2' => $this->tabanAlaniM2 ?: null,
            'kat_sayisi' => $this->katSayisi,
            'kullanici_yuku' => $this->kullaniciYuku,
            'ozel_kullanimlar' => $this->ozelKullanimlar,
            'bolumler' => array_values($this->bolumler),
            'riskler' => array_values($this->riskler),
            'tespitler' => $this->tespitler,
            'sinif_elle' => $this->sinifElle,
            'genel_not' => $this->genelNot,
            'degerlendirme_tarihi' => $this->degerlendirmeTarihi,
        ]);

        if ($this->sinifElle && $this->elleSinif) {
            $k->belirlenen_tehlike_sinifi = $this->elleSinif;
        }

        $k->save();

        unset($this->kayit);

        if (! $sessiz) {
            Notification::make()->title('Yangın güvenliği değerlendirmesi kaydedildi')->success()->send();
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
                ->label('Değerlendirme Raporu (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $this->kaydet(sessiz: true);

                    return YanginGuvenligiUretici::pdf($this->kayit());
                }),
        ];
    }
}
