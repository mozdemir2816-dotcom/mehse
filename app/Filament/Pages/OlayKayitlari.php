<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\OlayKaydi;
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

/**
 * Olay Kayıtları / Ramak Kala — İş Kazası Raporu'ndan AYRI bir olay defteri.
 * Yaralanma olmasa da her İSG olayını (ramak kala, tehlikeli durum/davranış,
 * ilk yardım, maddi hasar, çevre) kaydeder; her kayıtta sınıflandırma +
 * potansiyel risk (olasılık × şiddet) + 5 Neden (5N) kök neden zinciri +
 * düzeltici faaliyet tutulur. Düzeltici faaliyet tek tıkla DÖF'e aktarılır.
 */
class OlayKayitlari extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.olay-kayitlari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'olay-kayitlari';

    protected static ?string $title = 'Olay Kayıtları / Ramak Kala';

    protected static ?string $navigationLabel = 'Olay Kayıtları / Ramak Kala';

    public ?int $firmaId = null;

    public ?string $tipFiltre = null;

    // --- Yeni kayıt formu ---
    public ?string $olayTipi = 'ramak_kala';

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
        $this->raporHazirlayan = $this->firma?->igu?->ad_soyad;
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

        $o = new OlayKaydi([
            'firma_id' => $this->firma->id,
            'calisan_id' => $this->etkilenenHizliSecId,
            'olay_tipi' => $this->olayTipi,
            'olay_tarihi' => $this->olayTarihi,
            'olay_saati' => $this->olaySaati,
            'olay_yeri' => $this->olayYeri,
            'bildiren_ad_soyad' => $this->bildirenAdSoyad,
            'bildirim_tarihi' => $this->bildirimTarihi,
            'etkilenen_ad_soyad' => $this->etkilenenAdSoyad,
            'etkilenen_gorev' => $this->etkilenenGorev,
            'olay_ozeti' => $this->olayOzeti,
            'sonuc_turu' => $this->sonucTuru,
            'etkilenen_kategorileri' => $this->etkilenenKategorileri,
            'olasilik' => $this->olasilik,
            'siddet' => $this->siddet,
            'bes_neden' => $this->besNeden,
            'kok_neden' => $this->kokNeden,
            'kok_neden_kategorileri' => $this->kokNedenKategorileri,
            'balik_kilcigi' => collect($this->balikKilcigi)
                ->map(fn (array $n) => array_values(array_filter(array_map('trim', $n), fn ($x) => $x !== '')))
                ->all(),
            'duzeltici_faaliyet' => $this->duzelticiFaaliyet,
            'kayip_gun_sayisi' => $this->kayipGunSayisi,
            'sgk_bildirimi_yapildi' => $this->sgkBildirimiYapildi,
            'sgk_bildirim_tarihi' => $this->sgkBildirimiYapildi ? $this->sgkBildirimTarihi : null,
            'kolluk_bildirimi_yapildi' => $this->kollukBildirimiYapildi,
            'taniklar' => $this->taniklar,
            'fotograflar' => collect($this->yeniFotograflar)->map(fn ($f) => $f->store('olay-kaydi-foto', 'public'))->all(),
            'rapor_hazirlayan' => $this->raporHazirlayan,
            'rapor_hazirlayan_kase' => $this->firma->igu?->kase_gorseli,
        ]);
        $o->save();

        unset($this->gecmisKayitlar);

        return $o;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kaydet_pdf')
                ->label('Kaydı Oluştur (Kaydet ve PDF İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $o = $this->kaydet();

                    if (! $o) {
                        return null;
                    }

                    Notification::make()->title('Olay kaydı oluşturuldu')->body($o->belge_no)->success()->send();

                    return OlayKaydiUretici::pdf($o);
                }),

            Action::make('balik_kilcigi_pdf')
                ->label('Balık Kılçığı Analizi (Kaydet ve PDF)')
                ->icon('heroicon-o-share')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $o = $this->kaydet();

                    if (! $o) {
                        return null;
                    }

                    Notification::make()->title('Olay kaydı oluşturuldu')->body($o->belge_no.' — Balık Kılçığı')->success()->send();

                    return OlayKaydiUretici::balikKilcigiPdf($o);
                }),

            Action::make('defter_excel')
                ->label('Olay Kayıt Defteri (Excel)')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma !== null && $this->gecmisKayitlar->isNotEmpty())
                ->action(fn () => OlayKaydiUretici::defterExcel($this->firma)),
        ];
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
