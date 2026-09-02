<?php

namespace App\Filament\Pages;

use App\Models\DofRaporu;
use App\Models\Firma;
use App\Support\DofRaporuUretici;
use App\Support\GeminiOneriDanismani;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * DÖF Oluştur (Çoklu DÖF) — isgpratik 158.jpg. Saha gözetimi sonucu birden
 * çok Düzeltici Önleyici Faaliyet maddesini tek raporda toplar; "AI ile Öneri
 * Al" GeminiOneriDanismani'yı (Tespit Öneri Defteri ile aynı) kullanır.
 */
class DofOlustur extends Page
{
    protected string $view = 'filament.pages.dof-olustur';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'dof';

    protected static ?string $title = 'DÖF Oluştur';

    protected static ?string $navigationLabel = 'DÖF Oluştur';

    public ?int $firmaId = null;

    public ?string $alanBolge = null;

    public ?string $gozetimTarihAraligi = null;

    public ?string $raporTarihi = null;

    public ?string $gozetimYapan = null;

    public ?string $gozetimYapanSertifikaNo = null;

    public ?string $sorumluKisi = 'İşveren / İşveren Vekili';

    public ?string $isverenVekiliAdi = null;

    /** @var array<int, array{tespit: string, oncelik: string, oneri: ?string, sorumlu: ?string, termin: ?string, durum: string}> */
    public array $maddeler = [];

    public ?string $yeniTespit = null;

    public string $yeniOncelik = 'orta';

    public ?string $yeniOneri = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniTermin = null;

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();

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

    #[Computed]
    public function oncelikler(): array
    {
        return config('isg.dof.oncelikler');
    }

    #[Computed]
    public function durumlar(): array
    {
        return config('isg.dof.durumlar');
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiOneriDanismani::aktifMi();
    }

    /** @return Collection<int, DofRaporu> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->dofRaporlari()->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->gecmisKayitlar);

        $this->gozetimYapan = $this->firma?->igu?->ad_soyad;
        $this->gozetimYapanSertifikaNo = $this->firma?->igu?->sertifika_no;
        $this->isverenVekiliAdi = $this->firma?->isveren_vekili ?: $this->firma?->isveren_ad;
    }

    public function maddeEkle(): void
    {
        if (blank($this->yeniTespit)) {
            return;
        }

        $this->maddeler[] = [
            'tespit' => $this->yeniTespit,
            'oncelik' => $this->yeniOncelik,
            'oneri' => $this->yeniOneri,
            'sorumlu' => $this->yeniSorumlu,
            'termin' => $this->yeniTermin,
            'durum' => 'acik',
        ];

        $this->reset('yeniTespit', 'yeniOneri', 'yeniSorumlu', 'yeniTermin');
        $this->yeniOncelik = 'orta';
    }

    public function maddeSil(int $index): void
    {
        unset($this->maddeler[$index]);
        $this->maddeler = array_values($this->maddeler);
    }

    public function durumGuncelle(int $index, string $durum): void
    {
        if (isset($this->maddeler[$index])) {
            $this->maddeler[$index]['durum'] = $durum;
        }
    }

    public function aiOnerisiAl(): void
    {
        $oneri = GeminiOneriDanismani::oner((string) $this->yeniTespit);

        if ($oneri) {
            $this->yeniOneri = $oneri;
        } else {
            Notification::make()->title('Öneri alınamadı')->body('Yapay zeka şu an kullanılamıyor.')->warning()->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?DofRaporu
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (! $this->maddeler) {
            Notification::make()->title('En az bir DÖF maddesi ekleyin')->danger()->send();

            return null;
        }

        $d = new DofRaporu([
            'firma_id' => $this->firma->id,
            'alan_bolge' => $this->alanBolge,
            'gozetim_tarih_araligi' => $this->gozetimTarihAraligi,
            'rapor_tarihi' => $this->raporTarihi,
            'gozetim_yapan' => $this->gozetimYapan,
            'gozetim_yapan_sertifika_no' => $this->gozetimYapanSertifikaNo,
            'gozetim_yapan_kase' => $this->firma->igu?->kase_gorseli,
            'sorumlu_kisi' => $this->sorumluKisi,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'maddeler' => $this->maddeler,
        ]);
        $d->save();

        unset($this->gecmisKayitlar);

        return $d;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('DÖF Raporu (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $d = $this->kaydet();

                    if (! $d) {
                        return null;
                    }

                    Notification::make()->title('DÖF raporu kaydedildi')->body($d->belge_no)->success()->send();

                    return DofRaporuUretici::pdf($d);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $d = $this->firma?->dofRaporlari()->find($id);

        return $d ? DofRaporuUretici::pdf($d) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->dofRaporlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
