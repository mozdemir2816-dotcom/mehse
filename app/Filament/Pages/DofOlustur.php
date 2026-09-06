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
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * DÖF Oluştur (Çoklu DÖF) — isgpratik 158.jpg. Saha gözetimi sonucu birden
 * çok Düzeltici Önleyici Faaliyet maddesini tek raporda toplar; "AI ile Öneri
 * Al" GeminiOneriDanismani'yı (Tespit Öneri Defteri ile aynı) kullanır.
 */
class DofOlustur extends Page
{
    use WithFileUploads;

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

    /** Sahada tespit edilen uygunsuzluğun fotoğraf kanıtı (yeni madde eklerken). */
    public $yeniFoto = null;

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();

        if ($aktarim = session()->pull('dof_aktarim')) {
            $this->firmaId = $aktarim['firma_id'];
            $this->updatedFirmaId();
            $this->maddeler = [...$this->maddeler, ...$aktarim['maddeler']];

            Notification::make()
                ->title(count($aktarim['maddeler']).' bulgu AI Saha Analizi\'nden aktarıldı')
                ->success()
                ->send();

            return;
        }

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
    | Portföy Geneli Takip — isgpratik DÖF ana ekranı (24.jpg)
    |--------------------------------------------------------------------------
    */

    public string $takipArama = '';

    public string $takipOncelikFiltre = '';

    /** @var array<string, string> "raporId-maddeIndex" => kapatma notu taslağı */
    public array $kapatmaNotlari = [];

    /** Kullanıcının tüm firmalarındaki tüm DÖF maddeleri, firma bilgisiyle birlikte düz liste. */
    #[Computed]
    public function tumMaddeler(): Collection
    {
        return DofRaporu::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->with('firma')
            ->get()
            ->flatMap(fn (DofRaporu $rapor) => collect($rapor->maddeler ?? [])->map(fn (array $m, int $i) => [
                ...$m,
                'anahtar' => $rapor->id.'-'.$i,
                'rapor_id' => $rapor->id,
                'madde_index' => $i,
                'firma' => $rapor->firma?->unvan ?? '—',
                'belge_no' => $rapor->belge_no,
            ]));
    }

    #[Computed]
    public function takipOzeti(): array
    {
        $tumu = $this->tumMaddeler;
        $acik = $tumu->where('durum', '!=', 'tamamlandi');
        $kapanmis = $tumu->where('durum', 'tamamlandi');

        return [
            'toplam_dof' => DofRaporu::query()->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))->count(),
            'acik_madde' => $acik->count(),
            'kapanmis_madde' => $kapanmis->count(),
            'kapatma_orani' => $tumu->isEmpty() ? 0 : round(($kapanmis->count() / $tumu->count()) * 100),
            'oncelik_dagilimi' => $acik->countBy('oncelik'),
        ];
    }

    /** @return Collection<int, array> arama/filtre uygulanmış açık maddeler */
    #[Computed]
    public function acikMaddelerFiltreli(): Collection
    {
        $terim = mb_strtolower(trim($this->takipArama));

        return $this->tumMaddeler
            ->where('durum', '!=', 'tamamlandi')
            ->when($this->takipOncelikFiltre !== '', fn ($q) => $q->where('oncelik', $this->takipOncelikFiltre))
            ->when($terim !== '', fn ($q) => $q->filter(
                fn (array $m) => str_contains(mb_strtolower($m['tespit'] ?? ''), $terim)
                    || str_contains(mb_strtolower($m['firma'] ?? ''), $terim)
                    || str_contains(mb_strtolower($m['belge_no'] ?? ''), $terim),
            ))
            ->sortByDesc(fn (array $m) => array_search($m['oncelik'] ?? 'dusuk', ['dusuk', 'orta', 'yuksek', 'kritik']))
            ->values();
    }

    /** Portföydeki (kaydedilmiş) bir DÖF maddesini "Tamamlandı" yapıp kapatma notu ekler. */
    public function maddeKapat(int $raporId, int $maddeIndex): void
    {
        $rapor = DofRaporu::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($raporId);

        if (! $rapor || ! isset($rapor->maddeler[$maddeIndex])) {
            return;
        }

        $maddeler = $rapor->maddeler;
        $maddeler[$maddeIndex]['durum'] = 'tamamlandi';
        $maddeler[$maddeIndex]['kapatma_notu'] = $this->kapatmaNotlari[$raporId.'-'.$maddeIndex] ?? null;
        $maddeler[$maddeIndex]['kapatma_tarihi'] = now()->toDateString();
        $rapor->update(['maddeler' => $maddeler]);

        unset($this->kapatmaNotlari[$raporId.'-'.$maddeIndex], $this->tumMaddeler, $this->takipOzeti, $this->acikMaddelerFiltreli);
        unset($this->gecmisKayitlar);

        Notification::make()->title('Madde kapatıldı')->success()->send();
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
            'foto_yolu' => $this->yeniFoto?->store('dof-foto', 'public'),
        ];

        $this->reset('yeniTespit', 'yeniOneri', 'yeniSorumlu', 'yeniTermin', 'yeniFoto');
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
