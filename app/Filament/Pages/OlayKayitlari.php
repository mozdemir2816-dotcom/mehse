<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\OlayKaydi;
use App\Filament\Support\ImzaSecenegi;
use App\Support\OlayKaydiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Olay Kayıtları / Ramak Kala — İş Kazası Raporu'ndan AYRI bir olay defteri.
 * Yaralanma olmasa da her İSG olayını (ramak kala, tehlikeli durum/davranış,
 * ilk yardım, maddi hasar, çevre) kaydeder; her kayıtta sınıflandırma +
 * potansiyel risk (olasılık × şiddet) + 5 Neden (5N) kök neden zinciri +
 * düzeltici faaliyet tutulur. Düzeltici faaliyet tek tıkla DÖF'e aktarılır.
 */
class OlayKayitlari extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    use WithFileUploads;

    protected string $view = 'filament.pages.olay-kayitlari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'İş Kazaları & Olaylar';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'olay-kayitlari';

    protected static ?string $title = 'Olay Kayıtları / Ramak Kala';

    protected static ?string $navigationLabel = 'Olay Kayıtları / Ramak Kala';

    public ?int $firmaId = null;

    public ?string $tipFiltre = null;

    /** Düzenlenmekte olan kayıt (null = yeni kayıt). */
    public ?int $duzenlenenId = null;

    // --- Yeni kayıt formu ---
    public ?string $olayTipi = 'ramak_kala';

    public string $durum = 'acik';

    public ?string $bolum = null;

    public ?string $alan = null;

    public ?string $yapilanIs = null;

    public ?string $ekipman = null;

    public ?string $kimyasal = null;

    public ?string $siniflandirma = null;

    public ?string $olayDetayi = null;

    /** @var array<int, string> Olay etkileri kutucukları (config isg.olay.etkiler) */
    public array $etkiler = [];

    public ?string $riskAnalizinde = null;

    public ?string $riskAnaliziNotu = null;

    public ?string $acilDurumIliskisi = null;

    public ?string $acilDurumNotu = null;

    public ?string $kazaTuru = null;

    public ?string $yaralanmaTuru = null;

    public ?string $mudahaleDetayi = null;

    public ?string $sistemselEksiklik = null;

    public ?string $genelDegerlendirme = null;

    public ?string $isyeriHekimi = null;

    public ?string $isverenVekili = null;

    public ?int $etkilenenHizliSecId = null;

    public ?string $etkilenenAdSoyad = null;

    public ?string $etkilenenGorev = null;

    public ?string $olayTarihi = null;

    public ?string $olaySaati = null;

    public ?string $olayYeri = null;

    public ?string $bildirenAdSoyad = null;

    public ?string $bildirimTarihi = null;

    public ?string $olayOzeti = null;

    public ?string $sonucTuru = 'yaralanmasiz';

    /** @var array<int, string> */
    public array $etkilenenKategorileri = [];

    public ?string $olasilik = null;

    public ?string $siddet = null;

    /** @var array<int, string> 5 sabit adım (index 0-4) */
    public array $besNeden = ['', '', '', '', ''];

    public ?string $kokNeden = null;

    /** @var array<int, string> */
    public array $kokNedenKategorileri = [];

    /** @var array<string, array<int, string>> Balık kılçığı 6M — kategori => neden listesi */
    public array $balikKilcigi = ['insan' => [], 'makine' => [], 'metot' => [], 'malzeme' => [], 'olcum' => [], 'cevre' => []];

    public ?string $duzelticiFaaliyet = null;

    public ?int $kayipGunSayisi = null;

    public bool $sgkBildirimiYapildi = false;

    public ?string $sgkBildirimTarihi = null;

    public bool $kollukBildirimiYapildi = false;

    /** @var array<int, array{ad_soyad: string, gorev: ?string}> */
    public array $taniklar = [];

    public ?string $yeniTanikAd = null;

    public ?string $yeniTanikGorev = null;

    public ?string $raporHazirlayan = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $yeniFotograflar = [];

    public function mount(): void
    {
        $this->olayTarihi = now()->toDateString();
        $this->bildirimTarihi = now()->toDateString();

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
    public function tipler(): array
    {
        return config('isg.olay.tipler');
    }

    #[Computed]
    public function sonucTurleri(): array
    {
        return config('isg.olay.sonuc_turleri');
    }

    #[Computed]
    public function etkilenenSecenekleri(): array
    {
        return config('isg.olay.etkilenen_kategorileri');
    }

    #[Computed]
    public function olasiliklar(): array
    {
        return config('isg.olay.olasiliklar');
    }

    #[Computed]
    public function siddetler(): array
    {
        return config('isg.olay.siddetler');
    }

    #[Computed]
    public function durumlar(): array
    {
        return config('isg.olay.durumlar');
    }

    #[Computed]
    public function siniflandirmalar(): array
    {
        return config('isg.olay.siniflandirmalar');
    }

    #[Computed]
    public function etkiSecenekleri(): array
    {
        return config('isg.olay.etkiler');
    }

    #[Computed]
    public function riskAnaliziDurumlari(): array
    {
        return config('isg.olay.risk_analizi_durumlari');
    }

    #[Computed]
    public function acilDurumIliskileri(): array
    {
        return config('isg.olay.acil_durum_iliskileri');
    }

    #[Computed]
    public function kazaTurleri(): array
    {
        return config('isg.is_kazasi.kaza_turleri');
    }

    #[Computed]
    public function kokNedenSecenekleri(): array
    {
        return config('isg.is_kazasi.kok_neden_kategorileri');
    }

    #[Computed]
    public function potansiyelOnizleme(): ?array
    {
        $skor = OlayKaydi::skorHesapla($this->olasilik, $this->siddet);

        if ($skor === null) {
            return null;
        }

        $seviye = match (true) {
            $skor <= 4 => 'Düşük',
            $skor <= 9 => 'Orta',
            $skor <= 15 => 'Yüksek',
            default => 'Çok Yüksek',
        };

        return ['skor' => $skor, 'seviye' => $seviye];
    }

    /** @return Collection<int, OlayKaydi> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma
            ? $this->firma->olayKayitlari()
                ->when($this->tipFiltre, fn ($q) => $q->where('olay_tipi', $this->tipFiltre))
                ->with('dofRaporu')
                ->latest('olay_tarihi')->latest()->get()
            : collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Etkileşim
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->duzenlenenId = null;
        $this->raporHazirlayan = $this->firma?->igu?->ad_soyad;
        $this->isyeriHekimi = $this->firma?->isyeriHekimi?->ad_soyad;
        $this->isverenVekili = $this->firma?->isveren_vekili ?: $this->firma?->isveren_ad;
    }

    /** Geçmiş bir kaydı forma yükler — "Kaydet" artık bu kaydı günceller. */
    public function duzenle(int $id): void
    {
        $o = $this->firma?->olayKayitlari()->find($id);

        if (! $o) {
            return;
        }

        $this->duzenlenenId = $o->id;
        $this->olayTipi = $o->olay_tipi;
        $this->durum = $o->durum ?: 'acik';
        $this->etkilenenHizliSecId = $o->calisan_id;
        $this->olayTarihi = $o->olay_tarihi?->toDateString();
        $this->olaySaati = $o->olay_saati;
        $this->olayYeri = $o->olay_yeri;
        $this->bolum = $o->bolum;
        $this->alan = $o->alan;
        $this->yapilanIs = $o->yapilan_is;
        $this->ekipman = $o->ekipman;
        $this->kimyasal = $o->kimyasal;
        $this->siniflandirma = $o->siniflandirma;
        $this->bildirenAdSoyad = $o->bildiren_ad_soyad;
        $this->bildirimTarihi = $o->bildirim_tarihi?->toDateString();
        $this->etkilenenAdSoyad = $o->etkilenen_ad_soyad;
        $this->etkilenenGorev = $o->etkilenen_gorev;
        $this->olayOzeti = $o->olay_ozeti;
        $this->olayDetayi = $o->olay_detayi;
        $this->etkiler = $o->etkiler ?? [];
        $this->sonucTuru = $o->sonuc_turu;
        $this->etkilenenKategorileri = $o->etkilenen_kategorileri ?? [];
        $this->olasilik = $o->olasilik;
        $this->siddet = $o->siddet;
        $this->riskAnalizinde = $o->risk_analizinde;
        $this->riskAnaliziNotu = $o->risk_analizi_notu;
        $this->acilDurumIliskisi = $o->acil_durum_iliskisi;
        $this->acilDurumNotu = $o->acil_durum_notu;
        $this->besNeden = array_pad(array_slice($o->bes_neden ?? [], 0, 5), 5, '');
        $this->kokNeden = $o->kok_neden;
        $this->kokNedenKategorileri = $o->kok_neden_kategorileri ?? [];
        $this->sistemselEksiklik = $o->sistemsel_eksiklik;
        $this->balikKilcigi = array_merge(
            array_fill_keys(array_keys($this->balikKilcigi), []),
            $o->balik_kilcigi ?? [],
        );
        $this->duzelticiFaaliyet = $o->duzeltici_faaliyet;
        $this->genelDegerlendirme = $o->genel_degerlendirme;
        $this->kayipGunSayisi = $o->kayip_gun_sayisi;
        $this->kazaTuru = $o->kaza_turu;
        $this->yaralanmaTuru = $o->yaralanma_turu;
        $this->mudahaleDetayi = $o->mudahale_detayi;
        $this->sgkBildirimiYapildi = (bool) $o->sgk_bildirimi_yapildi;
        $this->sgkBildirimTarihi = $o->sgk_bildirim_tarihi?->toDateString();
        $this->kollukBildirimiYapildi = (bool) $o->kolluk_bildirimi_yapildi;
        $this->taniklar = $o->taniklar ?? [];
        $this->raporHazirlayan = $o->rapor_hazirlayan;
        $this->isyeriHekimi = $o->isyeri_hekimi;
        $this->isverenVekili = $o->isveren_vekili;
        $this->yeniFotograflar = [];

        Notification::make()->title($o->belge_no.' düzenleniyor')->body('Değişiklikleri yapıp "Kaydet" ile güncelleyin.')->info()->send();
    }

    /** Düzenlemeyi bırakıp boş yeni kayıt formuna döner. */
    public function yeniKayit(): void
    {
        $firmaId = $this->firmaId;
        $tipFiltre = $this->tipFiltre;

        $this->reset();
        $this->resetErrorBag();

        $this->firmaId = $firmaId;
        $this->tipFiltre = $tipFiltre;
        $this->olayTarihi = now()->toDateString();
        $this->bildirimTarihi = now()->toDateString();
        $this->updatedFirmaId();
    }

    /** Formdaki verilerden genel değerlendirme taslağı üretir (kaydetmeden). */
    public function genelDegerlendirmeOlustur(): void
    {
        $this->genelDegerlendirme = (new OlayKaydi($this->formVerisi()))->otomatikDegerlendirme();
    }

    public function updatedTipFiltre(): void
    {
        unset($this->gecmisKayitlar);
    }

    public function updatedEtkilenenHizliSecId(): void
    {
        $c = $this->etkilenenHizliSecId ? $this->calisanlar->firstWhere('id', $this->etkilenenHizliSecId) : null;

        $this->etkilenenAdSoyad = $c?->ad_soyad;
        $this->etkilenenGorev = $c?->gorev;
    }

    public function tanikEkle(): void
    {
        if (blank($this->yeniTanikAd)) {
            return;
        }

        $this->taniklar[] = ['ad_soyad' => $this->yeniTanikAd, 'gorev' => $this->yeniTanikGorev];
        $this->reset('yeniTanikAd', 'yeniTanikGorev');
    }

    public function tanikSil(int $index): void
    {
        unset($this->taniklar[$index]);
        $this->taniklar = array_values($this->taniklar);
    }

    public function fotoSil(int $index): void
    {
        unset($this->yeniFotograflar[$index]);
        $this->yeniFotograflar = array_values($this->yeniFotograflar);
    }

    /*
    |--------------------------------------------------------------------------
    | Balık Kılçığı (Ishikawa) — 6M kök neden
    |--------------------------------------------------------------------------
    */

    public function balikNedenEkle(string $kategori): void
    {
        if (! array_key_exists($kategori, $this->balikKilcigi)) {
            return;
        }

        $this->balikKilcigi[$kategori][] = '';
    }

    public function balikNedenSil(string $kategori, int $index): void
    {
        unset($this->balikKilcigi[$kategori][$index]);
        $this->balikKilcigi[$kategori] = array_values($this->balikKilcigi[$kategori]);
    }

    /** 5N zinciri + seçili kök neden kategorilerini balık kılçığına dağıt. */
    public function balikKilcigiOtomatik(): void
    {
        $harita = config('isg.balik_kilcigi.kok_neden_6m', []);

        foreach ($this->kokNedenKategorileri as $kat) {
            $hedef = $harita[$kat] ?? 'metot';
            $etiket = config('isg.is_kazasi.kok_neden_kategorileri.'.$kat, $kat);

            if (! in_array($etiket, $this->balikKilcigi[$hedef], true)) {
                $this->balikKilcigi[$hedef][] = $etiket;
            }
        }

        // 5N son adımı (kök neden) → Metot tarafına ipucu olarak ekle (kullanıcı düzenler).
        $zincir = array_values(array_filter(array_map('trim', $this->besNeden), fn ($n) => $n !== ''));

        if ($zincir && ! in_array(end($zincir), $this->balikKilcigi['metot'], true)) {
            $this->balikKilcigi['metot'][] = end($zincir);
        }

        Notification::make()->title('Balık kılçığı 5N ve kök nedenlerden dolduruldu — düzenleyin')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet / PDF / DÖF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?OlayKaydi
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (blank($this->olayTipi) || blank($this->olayOzeti)) {
            Notification::make()->title('Olay tipi ve olay özeti zorunlu')->danger()->send();

            return null;
        }

        if (mb_strlen(trim($this->olayOzeti)) < 20) {
            Notification::make()->title('Olay özeti en az 20 karakter olmalı')->body('Anlamlı, kısa bir açıklama yazın.')->danger()->send();

            return null;
        }

        if (filled($this->olayDetayi) && mb_strlen(trim($this->olayDetayi)) < 30) {
            Notification::make()->title('Olay detayı en az 30 karakter olmalı')->body('Olayın nasıl geliştiğini yazın.')->danger()->send();

            return null;
        }

        $mevcut = $this->duzenlenenId ? $this->firma->olayKayitlari()->find($this->duzenlenenId) : null;
        $o = $mevcut ?? new OlayKaydi;

        $o->fill($this->formVerisi());
        $o->fotograflar = [
            ...($mevcut?->fotograflar ?? []),
            ...collect($this->yeniFotograflar)->map(fn ($f) => $f->store('olay-kaydi-foto', 'public'))->all(),
        ];
        $o->save();

        $this->duzenlenenId = $o->id;
        $this->yeniFotograflar = [];
        unset($this->gecmisKayitlar);

        return $o;
    }

    /** Formun anlık hâli — model öznitelikleri (kaydet + genel değerlendirme taslağı ortak). */
    private function formVerisi(): array
    {
        return [
            'firma_id' => $this->firma?->id,
            'calisan_id' => $this->etkilenenHizliSecId,
            'olay_tipi' => $this->olayTipi,
            'durum' => $this->durum ?: 'acik',
            'olay_tarihi' => $this->olayTarihi,
            'olay_saati' => $this->olaySaati,
            'olay_yeri' => $this->olayYeri,
            'bolum' => $this->bolum,
            'alan' => $this->alan,
            'yapilan_is' => $this->yapilanIs,
            'ekipman' => $this->ekipman,
            'kimyasal' => $this->kimyasal,
            'siniflandirma' => $this->siniflandirma,
            'bildiren_ad_soyad' => $this->bildirenAdSoyad,
            'bildirim_tarihi' => $this->bildirimTarihi,
            'etkilenen_ad_soyad' => $this->etkilenenAdSoyad,
            'etkilenen_gorev' => $this->etkilenenGorev,
            'olay_ozeti' => $this->olayOzeti,
            'olay_detayi' => $this->olayDetayi,
            'etkiler' => array_values($this->etkiler),
            'sonuc_turu' => $this->sonucTuru,
            'etkilenen_kategorileri' => $this->etkilenenKategorileri,
            'olasilik' => $this->olasilik,
            'siddet' => $this->siddet,
            'potansiyel_skor' => OlayKaydi::skorHesapla($this->olasilik, $this->siddet),
            'risk_analizinde' => $this->riskAnalizinde,
            'risk_analizi_notu' => $this->riskAnaliziNotu,
            'acil_durum_iliskisi' => $this->acilDurumIliskisi,
            'acil_durum_notu' => $this->acilDurumNotu,
            'bes_neden' => $this->besNeden,
            'kok_neden' => $this->kokNeden,
            'kok_neden_kategorileri' => $this->kokNedenKategorileri,
            'sistemsel_eksiklik' => $this->sistemselEksiklik,
            'balik_kilcigi' => collect($this->balikKilcigi)
                ->map(fn (array $n) => array_values(array_filter(array_map('trim', $n), fn ($x) => $x !== '')))
                ->all(),
            'duzeltici_faaliyet' => $this->duzelticiFaaliyet,
            'genel_degerlendirme' => $this->genelDegerlendirme,
            'kayip_gun_sayisi' => $this->kayipGunSayisi,
            'kaza_turu' => $this->kazaTuru,
            'yaralanma_turu' => $this->yaralanmaTuru,
            'mudahale_detayi' => $this->mudahaleDetayi,
            'sgk_bildirimi_yapildi' => $this->sgkBildirimiYapildi,
            'sgk_bildirim_tarihi' => $this->sgkBildirimiYapildi ? $this->sgkBildirimTarihi : null,
            'kolluk_bildirimi_yapildi' => $this->kollukBildirimiYapildi,
            'taniklar' => $this->taniklar,
            'rapor_hazirlayan' => $this->raporHazirlayan,
            'rapor_hazirlayan_kase' => $this->firma?->igu?->kase_gorseli,
            'isyeri_hekimi' => $this->isyeriHekimi,
            'isveren_vekili' => $this->isverenVekili,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet')
                ->label(fn () => $this->duzenlenenId ? 'Değişiklikleri Kaydet' : 'Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    if ($o = $this->kaydet()) {
                        $this->kayitBildirimi($o);
                    }
                }),

            Action::make('kaydet_pdf')
                ->label(fn () => $this->duzenlenenId ? 'Kaydet ve PDF İndir' : 'Kaydı Oluştur (Kaydet ve PDF İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $o = $this->kaydet();

                    if (! $o) {
                        return null;
                    }

                    $this->kayitBildirimi($o);

                    return OlayKaydiUretici::pdf($o, ImzaSecenegi::secili($data));
                }),

            Action::make('balik_kilcigi_pdf')
                ->label('Balık Kılçığı Analizi (Kaydet ve PDF)')
                ->icon('heroicon-o-share')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function () {
                    $o = $this->kaydet();

                    if (! $o) {
                        return null;
                    }

                    $this->kayitBildirimi($o);

                    return OlayKaydiUretici::balikKilcigiPdf($o);
                }),

            Action::make('defter_excel')
                ->label('Olay Kayıt Defteri (Excel)')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma !== null && $this->gecmisKayitlar->isNotEmpty())
                ->action(fn () => OlayKaydiUretici::defterExcel($this->firma)),

            Action::make('yeni_kayit')
                ->label('Yeni Kayıt')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->visible(fn () => $this->duzenlenenId !== null)
                ->action(fn () => $this->yeniKayit()),
        ];
    }

    private function kayitBildirimi(OlayKaydi $o): void
    {
        Notification::make()
            ->title($o->wasRecentlyCreated ? 'Olay kaydı oluşturuldu' : 'Olay kaydı güncellendi')
            ->body($o->belge_no)
            ->success()
            ->send();
    }

    public function gecmisPdf(int $id)
    {
        $o = $this->firma?->olayKayitlari()->find($id);

        return $o ? OlayKaydiUretici::pdf($o) : null;
    }

    public function gecmisBalikKilcigi(int $id)
    {
        $o = $this->firma?->olayKayitlari()->find($id);

        return $o ? OlayKaydiUretici::balikKilcigiPdf($o) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->olayKayitlari()->find($id)?->delete();

        if ($this->duzenlenenId === $id) {
            $this->yeniKayit();
        }

        unset($this->gecmisKayitlar);
    }

    /** Geçmiş bir kaydın düzeltici faaliyetini DÖF Oluştur'a taşır. */
    public function dofeAktar(int $id)
    {
        $o = $this->firma?->olayKayitlari()->find($id);

        if (! $o) {
            return null;
        }

        if (blank($o->duzeltici_faaliyet)) {
            Notification::make()->title('Bu kayıtta düzeltici faaliyet yazılmamış')->danger()->send();

            return null;
        }

        $tespit = "[{$o->belge_no} — {$o->tipEtiketi()}] ".$o->olay_ozeti;

        session(['dof_aktarim' => [
            'firma_id' => $o->firma_id,
            'kaynak' => 'Olay Kaydı '.$o->belge_no,
            'olay_kaydi_id' => $o->id,
            'maddeler' => [[
                'tespit' => $tespit,
                'oncelik' => match ($o->potansiyelSeviye()) {
                    'Çok Yüksek' => 'kritik',
                    'Yüksek' => 'yuksek',
                    'Orta' => 'orta',
                    default => 'dusuk',
                },
                'oneri' => $o->duzeltici_faaliyet
                    .(filled($o->kok_neden) ? "\n\nKök neden: {$o->kok_neden}" : ''),
                'sorumlu' => null,
                'termin' => null,
                'durum' => 'acik',
            ]],
        ]]);

        return $this->redirect(DofOlustur::getUrl());
    }
}
