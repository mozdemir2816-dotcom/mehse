<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\KaydetSecenegi;
use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\ImzaSecenegi;
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
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Muayene Formu (EK-2) — İşyeri Hekimi ve Diğer Sağlık Personelinin Görev,
 * Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik EK-2'ye göre. isgpratik'te
 * ekran görüntüsü yok — mevzuattaki standart forma göre kuruldu.
 */
class MuayeneFormu extends Page
{
    use KaydetSecenegi;
    use SinirliErisim;

    protected string $view = 'filament.pages.muayene-formu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = 'Sağlık & Olaylar';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'muayene-formu';

    protected static ?string $title = 'Muayene Formu (EK-2)';

    protected static ?string $navigationLabel = 'Muayene Form (EK-2)';

    #[Url(as: 'firma')]
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

    /** EK-2 referans şablonunun yeni alanları — bkz. MuayeneFormuModel::ek2Sablon(). */
    public array $ek2 = [];

    public function mount(): void
    {
        $this->muayeneTarihi = now()->toDateString();
        $this->ek2 = MuayeneFormuModel::ek2Sablon();
        $this->ek2['beyan_tarih'] = $this->muayeneTarihi;

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
            ->secenekler()
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
        $this->kayitUnut();

        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->hekimAdi = $this->firma?->isyeriHekimi?->ad_soyad;
        $this->ek2['isyeri_tel'] = $this->firma?->telefon;
        $this->ek2['isyeri_eposta'] = $this->firma?->eposta;
        $this->ek2['hekim']['belge_no'] = $this->firma?->isyeriHekimi?->sertifika_no;
    }

    public function updatedCalisanHizliSecId(): void
    {
        $c = $this->calisanHizliSecId ? $this->calisanlar->firstWhere('id', $this->calisanHizliSecId) : null;

        $this->calisanAdSoyad = $c?->ad_soyad;
        $this->calisanTc = $c?->tc;
        $this->calisanGorevi = $c?->gorev;
        $this->iseGirisTarihi = $c?->ise_giris?->toDateString();
        $this->calisanDogumTarihi = $c?->dogum_tarihi?->toDateString();

        // Çalışan kaydındaki bilgiler EK-2'ye önceden yazılır (boşsa boş kalır).
        $this->ek2['kan_grubu'] = $c?->kan_grubu;
        $this->ek2['cinsiyet'] = $c?->cinsiyet ? (Calisan::CINSIYETLER[$c->cinsiyet] ?? $c->cinsiyet) : null;
        $this->ek2['calisan_tel'] = $c?->telefon;
        $this->ek2['meslegi'] = $c?->gorev;
        $this->ek2['calistigi_bolum'] = $c?->departman;
        $this->ek2['yaptigi_is'] = $c?->gorev;
    }

    public function oncekiIsEkle(): void
    {
        $this->ek2['onceki'][] = ['isyeri' => null, 'is' => null, 'baslama' => null, 'ayrilma' => null];
    }

    public function oncekiIsSil(int $index): void
    {
        if (count($this->ek2['onceki']) > 1) {
            unset($this->ek2['onceki'][$index]);
            $this->ek2['onceki'] = array_values($this->ek2['onceki']);
        }
    }

    /** Tıbbi anamnez sorularının tamamını "Hayır" yapar (referans şablondaki hızlı doldurma). */
    public function anamnezHayir(): void
    {
        foreach (array_keys(MuayeneFormuModel::OZGECMIS_SORULARI) as $no) {
            $this->ek2['oz'][$no]['cevap'] = 'Hayır';
        }
    }

    /** Boş fizik muayene sistemlerini "Normal" bulgu metniyle doldurur; dolu olana dokunmaz. */
    public function fizikNormalDoldur(): void
    {
        foreach (MuayeneFormuModel::FIZIK_SISTEMLERI as $anahtar => [, , $normal]) {
            if (blank($this->ek2['fizik'][$anahtar] ?? null)) {
                $this->ek2['fizik'][$anahtar] = $normal;
            }
        }
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

        $m = $this->kayitIcin(MuayeneFormuModel::class, [
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
            'ek2' => MuayeneFormuModel::ek2Birlestir($this->ek2),
        ]);
        $m->save();

        unset($this->gecmisKayitlar);

        return $m;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->kaydetAction(),
            $this->yeniKayitAction(),
            Action::make('pdf')
                ->label('Formu Oluştur (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $m = $this->kaydet();

                    if (! $m) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    Notification::make()->title('Muayene formu kaydedildi')->body($m->belge_no)->success()->send();

                    return MuayeneFormuUretici::pdf($m, ImzaSecenegi::secili($data));
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
