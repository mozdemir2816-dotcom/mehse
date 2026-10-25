<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsKazasiRaporu as IsKazasiRaporuModel;
use App\Models\RiskDegerlendirmesi;
use App\Filament\Support\ImzaSecenegi;
use App\Support\EkipmanKatalogu;
use App\Support\IsKazasiRaporuUretici;
use App\Support\RiskSkorlama;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * İş Kazası İnceleme ve Kök Neden Analiz Raporu — isgpratik 6 adımlı sihirbaz
 * mantığıyla: Genel Bilgiler → 5 Neden → Balık Kılçığı (6M) → DÖF → Foto & Notlar
 * → Önizleme. Tek sayfada bölümler hâlinde; "Taslak Kaydet" / "Raporu Tamamla".
 */
class IsKazasiRaporu extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    use WithFileUploads;

    protected string $view = 'filament.pages.is-kazasi-raporu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Sağlık & Olaylar';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 93;

    protected static ?string $slug = 'is-kazasi-raporu';

    protected static ?string $title = 'İş Kazası İnceleme Raporu';

    protected static ?string $navigationLabel = 'İş Kazası Raporu';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public ?int $duzenlenenId = null;

    // 1. Genel Bilgiler
    public ?int $kazazedeHizliSecId = null;

    public ?string $kazazedeAdSoyad = null;

    public ?string $kazazedeTc = null;

    public ?string $kazazedeGorev = null;

    public ?string $kazazedeKidem = null;

    public ?string $kazaTarihi = null;

    public ?string $kazaSaati = null;

    public ?string $kazaYeri = null;

    public ?string $kazaTuru = null;

    public ?string $agirlikDerecesi = null;

    public ?int $kayipGunSayisi = null;

    public ?string $kazaTanimi = null;

    // 1b. Yaralanma kaydı (24.10.2026) ve kazanın Fine-Kinney puanı
    /** @var array<int, array{bolge: ?string, tur: ?string, aciklama: ?string}> */
    public array $yaralanmalar = [];

    public ?float $kinneyOlasilik = null;

    public ?float $kinneyFrekans = null;

    public ?float $kinneySiddet = null;

    // 2. 5 Neden — sabit 5 soruya karşılık gelen yanıtlar
    /** @var array<int, string> */
    public array $besNeden = ['', '', '', '', ''];

    /** @var array<int, string> kök neden kategorileri (balık kılçığına otomatik dağıtım için) */
    public array $kokNedenKategorileri = [];

    // 3. Balık Kılçığı 6M
    /** @var array<string, array<int, string>> */
    public array $balikKilcigi = ['insan' => [], 'makine' => [], 'metot' => [], 'malzeme' => [], 'olcum' => [], 'cevre' => []];

    // 4. DÖF
    /** @var array<int, array{tip: string, sorumlu: ?string, aciklama: ?string, hedef_tarih: ?string, durum: string}> */
    public array $dofMaddeleri = [];

    // 5. Foto & Notlar
    public ?string $kritikNotlar = null;

    /** @var array<int, array{ad_soyad: string, gorev: ?string}> */
    public array $taniklar = [];

    public ?string $yeniTanikAd = null;

    public ?string $yeniTanikGorev = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $yeniFotograflar = [];

    // Bildirim / imza
    public bool $sgkBildirimiYapildi = false;

    public ?string $sgkBildirimTarihi = null;

    public ?string $raporHazirlayan = null;

    public bool $isyeriHekimiDahil = true;

    public ?string $isyeriHekimiAdi = null;

    public function mount(): void
    {
        $this->kazaTarihi = now()->toDateString();

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
    public function kazaTurleri(): array
    {
        return config('isg.is_kazasi.kaza_turleri');
    }

    #[Computed]
    public function agirlikDereceleri(): array
    {
        return config('isg.is_kazasi.agirlik_dereceleri');
    }

    #[Computed]
    public function kokNedenSecenekleri(): array
    {
        return config('isg.is_kazasi.kok_neden_kategorileri');
    }

    /** @return array<string, array<int, string>> grup başlığı => anahtar listesi */
    #[Computed]
    public function kokNedenGruplari(): array
    {
        return config('isg.is_kazasi.kok_neden_gruplari');
    }

    #[Computed]
    public function besNedenSorulari(): array
    {
        return config('isg.is_kazasi.bes_neden_sorulari');
    }

    #[Computed]
    public function balikKategorileri(): array
    {
        return config('isg.balik_kilcigi.kategoriler');
    }

    #[Computed]
    public function dofTipleri(): array
    {
        return config('isg.is_kazasi.dof_onlem_tipleri');
    }

    #[Computed]
    public function dofDurumlari(): array
    {
        return config('isg.is_kazasi.dof_durumlari');
    }

    #[Computed]
    public function vucutBolgeleri(): array
    {
        return config('isg.is_kazasi.vucut_bolgeleri');
    }

    #[Computed]
    public function yaralanmaTurleri(): array
    {
        return config('isg.is_kazasi.yaralanma_turleri');
    }

    #[Computed]
    public function kinneyOlcekleri(): array
    {
        return [
            'olasilik' => RiskSkorlama::olcek('fine_kinney', 'olasilik'),
            'frekans' => RiskSkorlama::olcek('fine_kinney', 'frekans'),
            'siddet' => RiskSkorlama::olcek('fine_kinney', 'siddet'),
        ];
    }

    /** @return array{puan:int|float, duzey:string, renk:string, eylem:string} */
    #[Computed]
    public function kinneySonuc(): array
    {
        return RiskSkorlama::hesapla('fine_kinney', $this->kinneyOlasilik, $this->kinneySiddet, $this->kinneyFrekans);
    }

    /** Firmanın en güncel risk değerlendirmesi — "Risk Analizine Ekle" aksiyonu için. */
    #[Computed]
    public function sonRiskDegerlendirmesi(): ?RiskDegerlendirmesi
    {
        return $this->firma ? EkipmanKatalogu::sonDegerlendirme($this->firma) : null;
    }

    /**
     * Kaza özeti, firmanın güncel risk değerlendirmesinde (metin benzerliğiyle) zaten
     * var mı? Kesin değil, yalnız "muhtemelen zaten var" uyarısı için — engelleyici değil.
     */
    #[Computed]
    public function kazaRiskteVarMi(): bool
    {
        $rd = $this->sonRiskDegerlendirmesi;
        if (! $rd || blank($this->kazaTanimi)) {
            return false;
        }

        $aranan = EkipmanKatalogu::norm($this->kazaTanimi);
        if ($aranan === '') {
            return false;
        }

        return $rd->maddeler->contains(function ($m) use ($aranan) {
            $mevcut = EkipmanKatalogu::norm($m->tehlike);

            return $mevcut !== '' && (str_contains($mevcut, $aranan) || str_contains($aranan, $mevcut));
        });
    }

    /** Önizleme özeti — anlık. */
    #[Computed]
    public function onizleme(): array
    {
        $dolu5N = collect($this->besNeden)->filter(fn ($n) => filled(trim((string) $n)))->count();
        $ishikawa = collect($this->balikKilcigi)->flatten()->filter(fn ($n) => filled(trim((string) $n)))->count();
        $dof = collect($this->dofMaddeleri)->filter(fn ($d) => filled(trim((string) ($d['aciklama'] ?? ''))))->count();

        $eksik = [];
        if (blank($this->kazaTarihi)) {
            $eksik[] = 'Kaza Tarihi';
        }
        if (blank($this->kazazedeAdSoyad)) {
            $eksik[] = 'Kazazede Adı';
        }
        if (blank($this->kazaYeri)) {
            $eksik[] = 'Kaza Yeri';
        }
        if (blank($this->kazaTanimi)) {
            $eksik[] = 'Kaza Özeti';
        }

        return ['bes_neden' => $dolu5N, 'ishikawa' => $ishikawa, 'dof' => $dof, 'eksik' => $eksik];
    }

    /** @return Collection<int, IsKazasiRaporuModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->isKazasiRaporlari()->latest('kaza_tarihi')->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->raporHazirlayan = $this->firma?->igu?->ad_soyad;
        $this->isyeriHekimiAdi = $this->firma?->isyeriHekimi?->ad_soyad;
    }

    public function updatedKazazedeHizliSecId(): void
    {
        $c = $this->kazazedeHizliSecId ? $this->calisanlar->firstWhere('id', $this->kazazedeHizliSecId) : null;

        $this->kazazedeAdSoyad = $c?->ad_soyad;
        $this->kazazedeTc = $c?->tc;
        $this->kazazedeGorev = $c?->gorev;
    }

    /*
    | Yaralanma kaydı
    */
    public function yaralanmaEkle(): void
    {
        $this->yaralanmalar[] = ['bolge' => null, 'tur' => null, 'aciklama' => null];
    }

    public function yaralanmaSil(int $index): void
    {
        unset($this->yaralanmalar[$index]);
        $this->yaralanmalar = array_values($this->yaralanmalar);
    }

    /*
    | Balık Kılçığı
    */
    public function balikNedenEkle(string $kategori, ?string $metin = null): void
    {
        if (! array_key_exists($kategori, $this->balikKilcigi)) {
            return;
        }

        $this->balikKilcigi[$kategori][] = $metin ?? '';
    }

    public function balikNedenSil(string $kategori, int $index): void
    {
        unset($this->balikKilcigi[$kategori][$index]);
        $this->balikKilcigi[$kategori] = array_values($this->balikKilcigi[$kategori]);
    }

    /** 5N son adımı + kök neden kategorilerini balık kılçığına dağıt. */
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

        $kok = trim((string) ($this->besNeden[4] ?? ''));

        if ($kok !== '' && ! in_array($kok, $this->balikKilcigi['metot'], true)) {
            $this->balikKilcigi['metot'][] = $kok;
        }

        Notification::make()->title('Balık kılçığı 5N ve kök nedenlerden dolduruldu — düzenleyin')->success()->send();
    }

    /*
    | DÖF
    */
    public function dofEkle(): void
    {
        $this->dofMaddeleri[] = ['tip' => 'teknik', 'sorumlu' => null, 'aciklama' => null, 'hedef_tarih' => null, 'durum' => 'acik'];
    }

    public function dofSil(int $index): void
    {
        unset($this->dofMaddeleri[$index]);
        $this->dofMaddeleri = array_values($this->dofMaddeleri);
    }

    /*
    | Tanık / Foto
    */
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
    | Kaydet & PDF
    */
    private function kaydet(string $durum): ?IsKazasiRaporuModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (blank($this->kazazedeAdSoyad) || blank($this->kazaTanimi)) {
            Notification::make()->title('Kazazede adı ve kaza özeti zorunlu')->danger()->send();

            return null;
        }

        $veri = [
            'firma_id' => $this->firma->id,
            'calisan_id' => $this->kazazedeHizliSecId,
            'durum' => $durum,
            'kazazede_ad_soyad' => $this->kazazedeAdSoyad,
            'kazazede_tc' => $this->kazazedeTc,
            'kazazede_gorev' => $this->kazazedeGorev,
            'kazazede_kidem' => $this->kazazedeKidem,
            'kaza_tarihi' => $this->kazaTarihi,
            'kaza_saati' => $this->kazaSaati,
            'kaza_yeri' => $this->kazaYeri,
            'kaza_turu' => $this->kazaTuru,
            'agirlik_derecesi' => $this->agirlikDerecesi,
            'kayip_gun_sayisi' => $this->kayipGunSayisi,
            'kaza_tanimi' => $this->kazaTanimi,
            'yaralanmalar' => collect($this->yaralanmalar)
                ->filter(fn (array $y) => filled($y['bolge'] ?? null) || filled($y['tur'] ?? null))
                ->values()->all(),
            'kinney_olasilik' => $this->kinneyOlasilik,
            'kinney_frekans' => $this->kinneyFrekans,
            'kinney_siddet' => $this->kinneySiddet,
            'kok_neden_kategorileri' => $this->kokNedenKategorileri,
            'bes_neden' => array_map('trim', $this->besNeden),
            'kaza_nedeni' => trim((string) ($this->besNeden[4] ?? '')) ?: null,
            'balik_kilcigi' => collect($this->balikKilcigi)
                ->map(fn (array $n) => array_values(array_filter(array_map('trim', $n), fn ($x) => $x !== '')))
                ->all(),
            'dof_maddeleri' => collect($this->dofMaddeleri)
                ->filter(fn (array $d) => filled(trim((string) ($d['aciklama'] ?? ''))))
                ->values()
                ->all(),
            'kritik_notlar' => $this->kritikNotlar,
            'taniklar' => $this->taniklar,
            'sgk_bildirimi_yapildi' => $this->sgkBildirimiYapildi,
            'sgk_bildirim_tarihi' => $this->sgkBildirimiYapildi ? $this->sgkBildirimTarihi : null,
            'rapor_hazirlayan' => $this->raporHazirlayan ?: $this->firma->igu?->ad_soyad,
            'rapor_hazirlayan_kase' => $this->firma->igu?->kase_gorseli,
            'isyeri_hekimi_dahil' => $this->isyeriHekimiDahil,
            'isyeri_hekimi_adi' => $this->isyeriHekimiDahil ? ($this->isyeriHekimiAdi ?: $this->firma->isyeriHekimi?->ad_soyad) : null,
            'isyeri_hekimi_kase' => $this->isyeriHekimiDahil ? $this->firma->isyeriHekimi?->kase_gorseli : null,
        ];

        $yeniFoto = collect($this->yeniFotograflar)->map(fn ($f) => $f->store('is-kazasi-foto', 'public'))->all();

        if ($this->duzenlenenId && $kayit = $this->firma->isKazasiRaporlari()->find($this->duzenlenenId)) {
            $veri['fotograflar'] = [...($kayit->fotograflar ?? []), ...$yeniFoto];
            $kayit->update($veri);
        } else {
            $veri['fotograflar'] = $yeniFoto;
            $kayit = IsKazasiRaporuModel::create($veri);
            $this->duzenlenenId = $kayit->id;
        }

        $this->yeniFotograflar = [];
        unset($this->gecmisKayitlar);

        return $kayit;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('taslakKaydet')
                ->label('Taslak Kaydet')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    if ($kayit = $this->kaydet('taslak')) {
                        Notification::make()->title('Taslak kaydedildi')->body($kayit->belge_no)->success()->send();
                    }
                }),

            Action::make('raporuTamamla')
                ->label('Raporu Tamamla ve İndir')
                ->icon('heroicon-o-document-check')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $kayit = $this->kaydet('tamamlandi');

                    if (! $kayit) {
                        return null;
                    }

                    Notification::make()->title('İş kazası raporu tamamlandı')->body($kayit->belge_no)->success()->send();

                    return IsKazasiRaporuUretici::pdf($kayit, ImzaSecenegi::secili($data));
                }),

            Action::make('riskAnalizineEkle')
                ->label(fn () => $this->sonRiskDegerlendirmesi ? 'Risk Analizine Ekle' : 'Önce Risk Değerlendirmesi Oluşturun')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->visible(fn () => $this->firma !== null && filled($this->kazaTanimi))
                ->requiresConfirmation(fn () => $this->kazaRiskteVarMi)
                ->modalDescription('Bu kazaya benzer bir madde firmanın güncel risk değerlendirmesinde zaten var görünüyor. Yine de yeni bir madde eklemek istiyor musunuz?')
                ->action(function () {
                    if (! $this->sonRiskDegerlendirmesi) {
                        $this->redirect(RiskSihirbazi::getUrl(['firma' => $this->firma->id]));

                        return;
                    }

                    $rd = $this->sonRiskDegerlendirmesi;
                    $sira = (int) $rd->maddeler()->max('sira');

                    $rd->maddeler()->create([
                        'sira' => $sira + 1,
                        'bolum' => $this->kazaYeri,
                        'faaliyet' => $this->kazazedeGorev,
                        'tehlike' => $this->kazaTanimi,
                        'risk' => $this->kazaTuru ? $this->kazaTurleri[$this->kazaTuru].' sonucu yaralanma' : 'Kazaya bağlı yaralanma',
                        'etkilenen_calisan' => true,
                        'etkilenen_diger' => false,
                        'olasilik' => $this->kinneyOlasilik,
                        'frekans' => RiskSkorlama::ucEksenliMi($rd->yontem) ? $this->kinneyFrekans : null,
                        'siddet' => $this->kinneySiddet,
                        'durum' => 'acik',
                    ]);

                    unset($this->sonRiskDegerlendirmesi, $this->kazaRiskteVarMi);

                    Notification::make()
                        ->title('Risk değerlendirmesine eklendi')
                        ->body('Puanları ve önlemi gözden geçirip revize edin.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function duzenle(int $id): void
    {
        $r = $this->firma?->isKazasiRaporlari()->find($id);

        if (! $r) {
            return;
        }

        $this->duzenlenenId = $r->id;
        $this->kazazedeAdSoyad = $r->kazazede_ad_soyad;
        $this->kazazedeTc = $r->kazazede_tc;
        $this->kazazedeGorev = $r->kazazede_gorev;
        $this->kazazedeKidem = $r->kazazede_kidem;
        $this->kazaTarihi = $r->kaza_tarihi?->toDateString();
        $this->kazaSaati = $r->kaza_saati;
        $this->kazaYeri = $r->kaza_yeri;
        $this->kazaTuru = $r->kaza_turu;
        $this->agirlikDerecesi = $r->agirlik_derecesi;
        $this->kayipGunSayisi = $r->kayip_gun_sayisi;
        $this->kazaTanimi = $r->kaza_tanimi;
        $this->yaralanmalar = $r->yaralanmalar ?? [];
        $this->kinneyOlasilik = $r->kinney_olasilik;
        $this->kinneyFrekans = $r->kinney_frekans;
        $this->kinneySiddet = $r->kinney_siddet;
        $this->kokNedenKategorileri = $r->kok_neden_kategorileri ?? [];
        $this->besNeden = array_pad($r->bes_neden ?? [], 5, '');
        $this->balikKilcigi = array_merge(
            ['insan' => [], 'makine' => [], 'metot' => [], 'malzeme' => [], 'olcum' => [], 'cevre' => []],
            $r->balik_kilcigi ?? [],
        );
        $this->dofMaddeleri = $r->dof_maddeleri ?? [];
        $this->kritikNotlar = $r->kritik_notlar;
        $this->taniklar = $r->taniklar ?? [];
        $this->sgkBildirimiYapildi = (bool) $r->sgk_bildirimi_yapildi;
        $this->sgkBildirimTarihi = $r->sgk_bildirim_tarihi?->toDateString();
        $this->raporHazirlayan = $r->rapor_hazirlayan;
        $this->isyeriHekimiDahil = (bool) $r->isyeri_hekimi_dahil;
        $this->isyeriHekimiAdi = $r->isyeri_hekimi_adi;

        Notification::make()->title($r->belge_no.' düzenlemeye alındı')->success()->send();
    }

    public function gecmisPdf(int $id)
    {
        $r = $this->firma?->isKazasiRaporlari()->find($id);

        return $r ? IsKazasiRaporuUretici::pdf($r) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->isKazasiRaporlari()->find($id)?->delete();

        if ($this->duzenlenenId === $id) {
            $this->duzenlenenId = null;
        }

        unset($this->gecmisKayitlar);
    }
}
