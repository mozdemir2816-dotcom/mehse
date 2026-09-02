<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\MuayeneFormu as MuayeneFormuModel;
use App\Support\MuayeneFormuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Muayene Formu (EK-2) — İşyeri Hekimi ve Diğer Sağlık Personelinin Görev,
 * Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik EK-2'ye göre. isgpratik'te
 * ekran görüntüsü yok — mevzuattaki standart forma göre kuruldu.
 */
class MuayeneFormu extends Page
{
    protected string $view = 'filament.pages.muayene-formu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 26;

    protected static ?string $slug = 'muayene-formu';

    protected static ?string $title = 'Muayene Formu (EK-2)';

    protected static ?string $navigationLabel = 'Muayene Form (EK-2)';

    public ?int $firmaId = null;

    public ?int $calisanHizliSecId = null;

    public ?string $calisanAdSoyad = null;

    public ?string $calisanTc = null;

    public ?string $calisanDogumTarihi = null;

    public ?string $calisanGorevi = null;

    public ?string $iseGirisTarihi = null;

    public ?string $muayeneTarihi = null;

    public string $muayeneTuru = 'periyodik';

    public ?string $meslekOykusu = null;

    public ?string $maruzKalinanRiskler = null;

    public ?string $ozgecmis = null;

    public ?string $soygecmis = null;

    /** @var array<int, array{baslik: string, sonuc: string, not: ?string}> */
    public array $sistemikMuayene = [];

    /** @var array<int, array{anahtar: string, yapildi: bool, sonuc: ?string, not: ?string}> */
    public array $tetkikler = [];

    public ?string $sonucKanaati = null;

    public ?string $sartAciklamasi = null;

    public ?string $onerilenKontrolTarihi = null;

    public ?string $hekimAdi = null;

    public function mount(): void
    {
        $this->muayeneTarihi = now()->toDateString();

        $this->sistemikMuayene = collect(config('isg.muayene.sistemik_muayene_basliklari'))
            ->map(fn (string $baslik) => ['baslik' => $baslik, 'sonuc' => 'normal', 'not' => null])
            ->all();

        $this->tetkikler = collect(config('isg.muayene.tetkikler'))
            ->map(fn (string $ad, string $anahtar) => ['anahtar' => $anahtar, 'yapildi' => false, 'sonuc' => null, 'not' => null])
            ->values()
            ->all();

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

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function muayeneTurleri(): array
    {
        return config('isg.muayene.muayene_turleri');
    }

    #[Computed]
    public function sonucKanaatleri(): array
    {
        return config('isg.muayene.sonuc_kanaatleri');
    }

    /** @return Collection<int, MuayeneFormuModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->muayeneFormlari()->latest('muayene_tarihi')->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->hekimAdi = $this->firma?->isyeriHekimi?->ad_soyad;
    }

    public function updatedCalisanHizliSecId(): void
    {
        $c = $this->calisanHizliSecId ? $this->calisanlar->firstWhere('id', $this->calisanHizliSecId) : null;

        $this->calisanAdSoyad = $c?->ad_soyad;
        $this->calisanTc = $c?->tc;
        $this->calisanGorevi = $c?->gorev;
        $this->iseGirisTarihi = $c?->ise_giris?->toDateString();
    }

    public function kontrolTarihiOner(): void
    {
        if (! $this->firma || blank($this->muayeneTarihi)) {
            return;
        }

        $yil = config('isg.muayene.periyot_yili.'.$this->firma->tehlike_sinifi, 1);
        $this->onerilenKontrolTarihi = Carbon::parse($this->muayeneTarihi)->addYears($yil)->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?MuayeneFormuModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (blank($this->calisanAdSoyad) || blank($this->sonucKanaati)) {
            Notification::make()->title('Çalışan adı ve sonuç/kanaat zorunlu')->danger()->send();

            return null;
        }

        $m = new MuayeneFormuModel([
            'firma_id' => $this->firma->id,
            'calisan_id' => $this->calisanHizliSecId,
            'calisan_ad_soyad' => $this->calisanAdSoyad,
            'calisan_tc' => $this->calisanTc,
            'calisan_dogum_tarihi' => $this->calisanDogumTarihi,
            'calisan_gorevi' => $this->calisanGorevi,
            'ise_giris_tarihi' => $this->iseGirisTarihi,
            'muayene_tarihi' => $this->muayeneTarihi,
            'muayene_turu' => $this->muayeneTuru,
            'meslek_oykusu' => $this->meslekOykusu,
            'maruz_kalinan_riskler' => $this->maruzKalinanRiskler,
            'ozgecmis' => $this->ozgecmis,
            'soygecmis' => $this->soygecmis,
            'sistemik_muayene' => $this->sistemikMuayene,
            'tetkikler' => $this->tetkikler,
            'sonuc_kanaati' => $this->sonucKanaati,
            'sart_aciklamasi' => $this->sonucKanaati === 'sartli_uygun' ? $this->sartAciklamasi : null,
            'onerilen_kontrol_tarihi' => $this->onerilenKontrolTarihi,
            'hekim_adi' => $this->hekimAdi,
            'hekim_kase' => $this->firma->isyeriHekimi?->kase_gorseli,
        ]);
        $m->save();

        unset($this->gecmisKayitlar);

        return $m;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Formu Oluştur (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $m = $this->kaydet();

                    if (! $m) {
                        return null;
                    }

                    Notification::make()->title('Muayene formu kaydedildi')->body($m->belge_no)->success()->send();

                    return MuayeneFormuUretici::pdf($m);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $m = $this->firma?->muayeneFormlari()->find($id);

        return $m ? MuayeneFormuUretici::pdf($m) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->muayeneFormlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
