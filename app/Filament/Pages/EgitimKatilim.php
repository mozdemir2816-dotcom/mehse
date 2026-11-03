<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AileAnaSayfasi;
use App\Filament\Concerns\KaydetSecenegi;
use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\CiktiBicimi;
use App\Filament\Support\ImzaSecenegi;
use App\Models\Calisan;
use App\Models\EgitimKatilim as EgitimKatilimModel;
use App\Models\Firma;
use App\Models\Sertifika;
use App\Models\YillikPlan;
use App\Support\EgitimFirmaIcerigi;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKatilimUretici;
use App\Support\EgitimKonulari;
use App\Support\IseOzguEgitimKutuphanesi;
use App\Support\IseOzguSenkron;
use App\Support\KatilimciExcelOkuyucu;
use App\Support\SertifikaUretici;
use App\Support\SertifikaYildizGrupUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Eğitim Katılım Formu — isgpratik EĞİTİM ekranları. "İş Sağlığı ve Güvenliği"
 * seçilince Genel/Sağlık/Teknik/İşyerine Özgü Riskler 4 bloğu birden gösterilir;
 * diğer başlıklar tek bloklu sabit içerikle gelir. Her "Form PDF" tıklaması
 * yeni bir kayıt (belge) oluşturur.
 */
class EgitimKatilim extends Page
{
    use AileAnaSayfasi;
    use KaydetSecenegi;
    use SinirliErisim;
    use WithFileUploads;

    protected string $view = 'filament.pages.egitim-katilim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'egitim-katilim';

    protected static ?string $title = 'Eğitim Katılım Formu';

    protected static ?string $navigationLabel = 'Eğitim Yönetim Merkezi';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public string $baslikAnahtari = 'genel';

    public string $egitimTuru = 'ilk';

    /** Çalışan grubu / iş kolu adı (ör. "Kazı Ekibi"): aynı firmada farklı gruplara ayrı form + ayrı konu şablonu. */
    public string $isKolu = '';

    /** @var array<int, string> seçili iş kalemleri (config isg.kkd_matris.is_kalemleri anahtarları) */
    public array $isKoluKalemleri = [];

    /** Katılımcı süzgeci: şube ya da departman adı (boş = tümü). */
    public string $calisanFiltre = '';

    /** Sektör → iş kalemi grubu (config isg.kkd_matris.is_kalemleri anahtarı). Yeni sektör eklenince buraya yazılır. */
    private const SEKTOR_IS_KALEMLERI = ['insaat' => 'İnşaat / Şantiye'];

    /** yuz_yuze | uzaktan | karma — formda işaretlenebilir kutu olarak da gösterilir. */
    public string $egitimSekli = 'yuz_yuze';

    public ?string $sektorAnahtari = null;

    public ?string $egitimYeri = null;

    public ?string $belgeTarihi = null;

    public int $sureGun = 1;

    /**
     * 2+ güne planlanan eğitimde her günün ayrı eğitim tarihi (gün sırasıyla).
     * Tek günlükte boş kalır; künyede/PDF'de "1. Gün / 2. Gün" olarak yazar.
     *
     * @var array<int, string>
     */
    public array $gunTarihleri = [];

    /** Belgede görünen "X Ders Saati" — tehlike sınıfına göre 8/12/16, gerekirse elle değiştirilir. */
    public ?int $dersSaati = null;

    public bool $isgUzmaniVar = true;

    public bool $isyeriHekimiVar = false;

    public ?string $isyeriHekimiAdi = null;

    /** @var array<int, int> katılımcı olarak dahil edilen firma çalışanı id'leri */
    public array $secilenCalisanIdler = [];

    /** @var array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    public array $manuelKatilimcilar = [];

    /** Katılımcılar ekip / kurul / temsilci üyelerinden otomatik geldi (başlık grup eğitimiyse). */
    public bool $uyelerOtomatik = false;

    /** @var array<int, string> Grup eğitiminde çalışan id => gruptaki rolü (katılımcı görevi olarak yazılır; çalışanın normal işi değil) */
    public array $grupGorevleri = [];

    public ?string $yeniAdSoyad = null;

    public ?string $yeniTc = null;

    public ?string $yeniGorev = null;

    public ?UploadedFile $excelDosya = null;

    /** @var array<int, string> */
    public array $excelHatalar = [];

    /** @var array<string, mixed> EgitimIcerikOlusturucu çıktısı — kullanıcı her maddeyi dahil/hariç bırakıp dakikasını değiştirebilir. */
    public array $icerik = [];

    public function mount(): void
    {
        $this->belgeTarihi = now()->toDateString();
        $this->icerikYenile();

        if ($aktarim = session()->pull('egitim_katilim_aktarim')) {
            $this->firmaId = $aktarim['firma_id'];
            $this->updatedFirmaId();
            // Aktarılan kişiler dışında firma çalışanları seçili gelmesin (updatedFirmaId hepsini seçer).
            $this->secilenCalisanIdler = [];
            $this->baslikAnahtari = $aktarim['baslik_anahtari'];
            $this->icerikYenile();
            $this->manuelKatilimcilar = [...$this->manuelKatilimcilar, ...$aktarim['katilimcilar']];

            Notification::make()
                ->title(count($aktarim['katilimcilar']).' katılımcı Atama Yazıları\'ndan aktarıldı')
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

    /** Seçili sektörün iş kalemleri (anahtar => ad); sektörün iş kalemi tanımı yoksa boş (yalnız serbest grup adı). */
    #[Computed]
    public function isKalemiSecenekleri(): array
    {
        $grup = self::SEKTOR_IS_KALEMLERI[$this->sektorAnahtari ?: (string) $this->naceSektoru] ?? null;

        return $grup ? collect(config('isg.kkd_matris.is_kalemleri.'.$grup, []))->pluck('ad', 'anahtar')->all() : [];
    }

    /** Katılımcı süzgeci seçenekleri: çalışanların şube ve departman adları. @return array<int, string> */
    #[Computed]
    public function calisanFiltreSecenekleri(): array
    {
        return $this->calisanlar->flatMap(fn (Calisan $c) => [$c->sube, $c->departman])
            ->map(fn ($d) => trim((string) $d))->filter()->unique()->sort()->values()->all();
    }

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function gorunenCalisanlar(): Collection
    {
        return $this->calisanFiltre === ''
            ? $this->calisanlar
            : $this->calisanlar->filter(fn (Calisan $c) => trim((string) $c->sube) === $this->calisanFiltre || trim((string) $c->departman) === $this->calisanFiltre)->values();
    }

    /** İş kalemi seç/kaldır: grup adı boşsa (ya da önceki otomatik addaysa) seçime göre yazılır; konu şablonu yenilenir. */
    public function isKoluToggle(string $anahtar): void
    {
        if (! array_key_exists($anahtar, $this->isKalemiSecenekleri)) {
            return;
        }

        $eskiOtomatik = $this->isKoluOtomatikAd();
        $this->isKoluKalemleri = in_array($anahtar, $this->isKoluKalemleri, true)
            ? array_values(array_diff($this->isKoluKalemleri, [$anahtar]))
            : [...$this->isKoluKalemleri, $anahtar];

        if ($this->isKolu === '' || $this->isKolu === $eskiOtomatik) {
            $this->isKolu = $this->isKoluOtomatikAd();
        }

        $this->oncekiIcerikYoksay = false;
        $this->icerikYenile();
    }

    /** Grup (iş kolu) formu: kütüphanede bu iş koluna kayıtlı konular (başka firmalardan da) işe özgü bölüme eklenir. */
    private function isKoluKutuphanedenEkle(): void
    {
        if ($this->isKolu === '' || ! isset($this->icerik['isyerine_ozgu']['maddeler'])) {
            return;
        }

        $konular = IseOzguEgitimKutuphanesi::isKoluKonulari($this->firma?->user_id, $this->isKolu);

        if ($konular->isEmpty()) {
            return;
        }

        $mevcut = collect($this->icerik['isyerine_ozgu']['maddeler'])->pluck('madde')->map(fn ($m) => mb_strtolower(trim((string) $m)))->all();

        foreach ($konular as $k) {
            if (! in_array(mb_strtolower(trim($k['ad'])), $mevcut, true)) {
                $this->icerik['isyerine_ozgu']['maddeler'][] = ['madde' => $k['ad'], 'dakika' => 10, 'dahil' => true];
            }
        }

        $this->oncekiKaynak = '"'.$this->isKolu.'" iş kolu için kütüphaneye kaydedilmiş konulardan';
    }

    private function isKoluOtomatikAd(): string
    {
        return collect($this->isKoluKalemleri)->map(fn ($a) => $this->isKalemiSecenekleri[$a] ?? null)->filter()->implode(' + ');
    }

    /** Grup adı elle değişince (kayıtlı o gruba ait önceki konu şablonu varsa) onu yükler. */
    public function updatedIsKolu(): void
    {
        $this->isKolu = trim($this->isKolu);
        $this->oncekiIcerikYoksay = false;
        $this->icerikYenile();
    }

    /** Süzgeçteki (görünen) çalışanları seçer; öncekilerin yerine geçer. */
    public function filtredekileriSec(): void
    {
        $this->secilenCalisanIdler = $this->gorunenCalisanlar->pluck('id')->all();
    }

    #[Computed]
    public function basliklar(): array
    {
        return EgitimIcerikOlusturucu::basliklar();
    }

    #[Computed]
    public function sektorler(): array
    {
        return EgitimIcerikOlusturucu::sektorler();
    }

    /** Sektör kullanıcı tarafından NACE önerisinden farklı seçildi (kayıtta `konu_secimleri.sektor_elle`). */
    public bool $sektorElle = false;

    /** Firmanın NACE kodundan türetilen işyerine özgü risk sektörü (yoksa null). */
    #[Computed]
    public function naceSektoru(): ?string
    {
        return EgitimIcerikOlusturucu::naceSektoru($this->firma?->nace_kodu);
    }

    /** Bu firmanın son temel eğitiminde kullanıcının ELLE seçtiği sektör (yoksa null). */
    private function elleSecilenSektor(): ?string
    {
        $kayit = $this->firma?->egitimKatilimlari()->where('baslik_anahtari', 'genel')
            ->whereNotNull('sektor_anahtari')->latest('belge_tarihi')->latest('id')->first();

        return ($kayit?->konu_secimleri['sektor_elle'] ?? false) ? $kayit->sektor_anahtari : null;
    }

    /** "NACE'ye göre seç" — elle seçimi bırakıp firmanın NACE sektörüne döner. */
    public function naceSektorunuKullan(): void
    {
        $this->sektorAnahtari = $this->naceSektoru;
        $this->sektorElle = false;
        $this->oncekiIcerikYoksay = false;
        $this->icerikYenile();
    }

    /** "Sıfırdan başlat" dendiyse önceki kayıttan konu yükleme atlanır (sektör/başlık değişince sıfırlanır). */
    public bool $oncekiIcerikYoksay = false;

    /** Bu turdaki içerik önceki bir eğitim katılım kaydından mı geldi? (arayüzde not göstermek için) */
    public bool $oncekidenYuklendi = false;

    /** Önceki içerik nereden geldi (bu firma / NACE'si aynı başka firma) — arayüz notu. */
    public ?string $oncekiKaynak = null;

    private function icerikYenile(): void
    {
        // Sektör firmanın NACE kodundan gelir; kullanıcı bu firmada daha önce elle
        // farklı sektör seçtiyse (NACE ile yapılan iş uyuşmuyor) o seçim gelir —
        // kullanıcı kararı 25.10.2026. Başka firmadaki seçim artık taşınmaz.
        if ($this->baslikAnahtari === 'genel' && blank($this->sektorAnahtari) && ! $this->oncekiIcerikYoksay) {
            $elle = $this->elleSecilenSektor();
            $this->sektorAnahtari = $elle ?? $this->naceSektoru;
            $this->sektorElle = $elle !== null;
        }

        $taze = EgitimIcerikOlusturucu::olustur(
            $this->baslikAnahtari,
            $this->sektorAnahtari,
            $this->firma?->tehlike_sinifi ?? 'az_tehlikeli',
            $this->egitimTuru,
        );

        // Bu firma için aynı başlık/sektör/türde daha önce düzenlenmiş içerik varsa
        // onu baz al — kullanıcı işe özgü konuları her seferinde yeniden eklemesin.
        $onceki = $this->oncekiIcerikYoksay ? null : $this->oncekiKonuIcerigi();

        if ($onceki && ($onceki['tip'] ?? null) === ($taze['tip'] ?? null)) {
            $this->icerik = $this->konuIcerigiBirlestir($taze, $onceki);
            $this->oncekidenYuklendi = true;
        } else {
            $this->icerik = $taze;
            $this->oncekidenYuklendi = false;
            $this->isKalemiKonulariniOner();
            $this->isKoluKutuphanedenEkle();
        }

        // Tehlike sınıfına göre nominal ders saati (8/12/16) — kullanıcı elle değiştirebilir.
        $this->dersSaati = $this->icerik['saat'] ?? null;

        $this->sureGunYenile();
    }

    /**
     * Taze (önceki kayıttan gelmeyen) içerikte, firmanın seçili iş kalemlerine
     * (bkz. Firma::isKalemiEgitimKonulari) göre önerilen konuları "işyerine
     * özgü" madde listesine ekler — kullanıcı sonradan çıkarabilir/düzenler.
     */
    private function isKalemiKonulariniOner(): void
    {
        // Grup (iş kolu) formu: işe özgü konular YALNIZ seçilen iş kalemlerinden gelir (firma geneli yıllık plan /
        // tüm iş kalemleri bu gruba karışmaz).
        if ($this->isKoluKalemleri) {
            if (isset($this->icerik['isyerine_ozgu']['maddeler'])) {
                $mevcut = collect($this->icerik['isyerine_ozgu']['maddeler'])->pluck('madde')->all();

                foreach (collect(config('isg.kkd_matris.is_kalemleri', []))->flatten(1)->whereIn('anahtar', $this->isKoluKalemleri)->pluck('egitim_konusu')->filter() as $konu) {
                    if (! in_array($konu, $mevcut, true)) {
                        $this->icerik['isyerine_ozgu']['maddeler'][] = ['madde' => $konu, 'dakika' => 10, 'dahil' => true];
                    }
                }
            }

            return;
        }

        // Firmaya özgü içerik önceliklidir: yıllık eğitim planının 4. bölümü
        // ("İşe ve İşyerine Özgü Riskler") doluysa formun işyerine özgü bölümü
        // doğrudan o konularla kurulur (sektör seçilmemiş olsa bile) — Yıldız Grup
        // sertifikası da 4. kategoriyi buradan alır.
        if ($this->planOzguBolumunuKur()) {
            return;
        }

        if (! isset($this->icerik['isyerine_ozgu']['maddeler'])) {
            return;
        }

        $konular = $this->firma?->isKalemiEgitimKonulari() ?? [];

        if (blank($konular)) {
            return;
        }

        $mevcut = collect($this->icerik['isyerine_ozgu']['maddeler'])->pluck('madde')->all();

        foreach ($konular as $konu) {
            if (in_array($konu, $mevcut, true)) {
                continue;
            }

            $this->icerik['isyerine_ozgu']['maddeler'][] = ['madde' => $konu, 'dakika' => 10, 'dahil' => true];
        }
    }

    /**
     * Genel (temel İSG) eğitimde, firmanın belge yılına ait yıllık eğitim
     * planındaki "işe özgü" konularla işyerine özgü bölümü kurar. Blok süresi
     * (tehlike sınıfı / eğitim türü) konulara eşit dağıtılır. Kurulduysa true.
     */
    private function planOzguBolumunuKur(): bool
    {
        if (($this->icerik['tip'] ?? null) !== 'genel' || ! $this->firma) {
            return false;
        }

        $yil = (int) Carbon::parse($this->belgeTarihi ?: now())->year;
        $ozgu = EgitimFirmaIcerigi::planOzguBolumu($this->firma, $this->egitimTuru, $yil);

        if (! $ozgu) {
            return false;
        }

        $this->icerik['isyerine_ozgu'] = $ozgu;

        return true;
    }

    /**
     * Aynı firma + başlık (+ genel'de sektör) + tür için en son eğitim katılım
     * kaydının konu içeriği; bu firmada yoksa NACE kodu aynı (önce tam kod, sonra
     * sınıf xx.yy, sonra bölüm xx) başka bir firmanızdaki son kayıttan — işe özgü
     * konular her yeni firmada yeniden yazılmasın, evraklar tutarlı kalsın.
     */
    private function oncekiKonuIcerigi(): ?array
    {
        $this->oncekiKaynak = null;

        $kayit = $this->firma?->egitimKatilimlari()
            ->where('baslik_anahtari', $this->baslikAnahtari)
            ->when($this->baslikAnahtari === 'genel', fn ($q) => $q->where('sektor_anahtari', $this->sektorAnahtari))
            ->where('egitim_turu', $this->egitimTuru)
            ->when($this->isKolu !== '', fn ($q) => $q->where('is_kolu', $this->isKolu), fn ($q) => $q->where(fn ($w) => $w->whereNull('is_kolu')->orWhere('is_kolu', '')))
            ->latest('belge_tarihi')
            ->latest()
            ->first();

        if ($kayit) {
            $this->oncekiKaynak = 'Bu firmanın önceki eğitim katılım kaydından';

            return $kayit->konu_secimleri;
        }

        // Grup (iş kolu) formunda başka firmanın genel şablonu taşınmaz — konular seçilen iş kalemlerinden gelir.
        $benzer = $this->isKolu !== '' ? null : $this->ayniNaceKaydi();

        if ($benzer) {
            $this->oncekiKaynak = 'NACE kodu aynı olan '.$benzer->firma?->unvan.' firmasındaki son eğitimden ('.$benzer->belge_tarihi?->format('d.m.Y').')';
        }

        return $benzer?->konu_secimleri;
    }

    /**
     * Kullanıcının NACE kodu bu firmayla aynı olan diğer firmalarındaki en son
     * eğitim katılım kaydı (tam kod → xx.yy → xx), aynı sektörde.
     */
    private function ayniNaceKaydi(): ?EgitimKatilimModel
    {
        $nace = (string) $this->firma?->nace_kodu;
        $rakam = preg_replace('/\D/', '', $nace);

        if (strlen($rakam) < 2) {
            return null;
        }

        $onekler = array_unique(array_filter([
            strlen($rakam) >= 6 ? substr($rakam, 0, 2).'.'.substr($rakam, 2, 2).'.'.substr($rakam, 4, 2) : null,
            strlen($rakam) >= 4 ? substr($rakam, 0, 2).'.'.substr($rakam, 2, 2) : null,
            substr($rakam, 0, 2),
        ]));

        foreach ($onekler as $onek) {
            $kayit = EgitimKatilimModel::query()
                ->with('firma:id,unvan')
                ->where('firma_id', '!=', $this->firma->id)
                ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id())->where('nace_kodu', 'like', $onek.'%'))
                ->where('baslik_anahtari', $this->baslikAnahtari)
                ->when($this->baslikAnahtari === 'genel', fn ($q) => $q->where('sektor_anahtari', $this->sektorAnahtari))
                ->orderByRaw('egitim_turu = ? desc', [$this->egitimTuru])
                ->latest('belge_tarihi')
                ->latest('id')
                ->first();

            if ($kayit) {
                return $kayit;
            }
        }

        return null;
    }

    /**
     * Önceki kaydın konu seçimlerini (dahil/dakika + işe özgü ekler) taze yapının
     * üzerine yazar; `saat` ve `egitim_turu` her zaman güncel bağlamdan gelir.
     */
    private function konuIcerigiBirlestir(array $taze, array $onceki): array
    {
        if (($taze['tip'] ?? null) !== 'genel') {
            return array_merge($taze, ['maddeler' => $onceki['maddeler'] ?? $taze['maddeler']]);
        }

        $ozgu = $taze['isyerine_ozgu'];

        if ($ozgu && ! empty($onceki['isyerine_ozgu']['maddeler'])) {
            $ozgu = array_merge($ozgu, ['maddeler' => $onceki['isyerine_ozgu']['maddeler']]);
        }

        return array_merge($taze, [
            'genel_konular' => $onceki['genel_konular'] ?? $taze['genel_konular'],
            'saglik_konulari' => $onceki['saglik_konulari'] ?? $taze['saglik_konulari'],
            'teknik_konular' => $onceki['teknik_konular'] ?? $taze['teknik_konular'],
            'isyerine_ozgu' => $ozgu,
        ]);
    }

    /** "Sıfırdan başlat" — önceki kayıttan yüklemeyi atla, standart içeriğe dön. */
    public function icerigiSifirla(): void
    {
        $this->oncekiIcerikYoksay = true;
        $this->icerikYenile();

        Notification::make()->title('Konu içeriği sıfırlandı')->success()->send();
    }

    public function updatedDersSaati(): void
    {
        $this->sureGunYenile();
    }

    /**
     * Toplam süre 11 saati aşıyorsa eğitim 2 güne planlanır (kullanıcı sonra elle
     * değiştirebilir). "Ders Saati" elle 12+ yapıldıysa (ör. az tehlikeli işyeri
     * için 16) o da 2 güne çeker — 1 ders saati ≈ 60 dk duvar saati.
     */
    private function sureGunYenile(): void
    {
        $konuGun = EgitimIcerikOlusturucu::planlananGun($this->icerik);
        $dersGun = ($this->dersSaati && $this->dersSaati * 60 > EgitimIcerikOlusturucu::IKI_GUN_ESIGI_DK) ? 2 : 1;

        $this->sureGun = max($konuGun, $dersGun);

        $this->gunTarihleriYenile();
    }

    /** "Eğitim Süresi (Gün)" elle değiştirilince gün tarihi alanlarını uyarla. */
    public function updatedSureGun(): void
    {
        $this->gunTarihleriYenile();
    }

    /** Belge tarihi değişince, henüz doldurulmamış gün tarihlerini yeniden türet. */
    public function updatedBelgeTarihi(): void
    {
        $this->gunTarihleriYenile();
    }

    /**
     * 2+ günlük eğitimde her gün için bir tarih alanı bulundur; kullanıcının
     * girdiği değerleri koru, boş kalanları belge tarihinden gün gün türet.
     * Tek günlükte alanları temizle.
     */
    private function gunTarihleriYenile(): void
    {
        if ($this->sureGun < 2) {
            $this->gunTarihleri = [];

            return;
        }

        $mevcut = array_values($this->gunTarihleri);
        $baz = $this->belgeTarihi ?: now()->toDateString();

        $this->gunTarihleri = collect(range(0, $this->sureGun - 1))
            ->map(fn (int $i) => $mevcut[$i] ?? Carbon::parse($baz)->addDays($i)->toDateString())
            ->all();
    }

    /** Konu dakikası / dahil durumu her değiştiğinde gün sayısını yeniden hesapla. */
    public function updatedIcerik(): void
    {
        $this->sureGunYenile();
    }

    public function updatedEgitimTuru(): void
    {
        $this->oncekiIcerikYoksay = false;
        $this->icerikYenile();
    }

    /*
    |--------------------------------------------------------------------------
    | İşyerine özgü konular — kullanıcı ekler / çıkarır / metnini düzenler
    |--------------------------------------------------------------------------
    */

    public function isyerineOzguMaddeEkle(): void
    {
        if (! isset($this->icerik['isyerine_ozgu']['maddeler'])) {
            Notification::make()->title('Önce bir işyerine özgü risk sektörü seçin')->warning()->send();

            return;
        }

        $this->icerik['isyerine_ozgu']['maddeler'][] = ['madde' => '', 'dakika' => 10, 'dahil' => true];
    }

    public function isyerineOzguMaddeCikar(int $index): void
    {
        if (! isset($this->icerik['isyerine_ozgu']['maddeler'][$index])) {
            return;
        }

        unset($this->icerik['isyerine_ozgu']['maddeler'][$index]);
        $this->icerik['isyerine_ozgu']['maddeler'] = array_values($this->icerik['isyerine_ozgu']['maddeler']);
        $this->sureGunYenile();
    }

    /** @return Collection<int, EgitimKatilimModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->egitimKatilimlari()->latest('belge_tarihi')->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        $this->kayitUnut();

        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar, $this->naceSektoru, $this->isKalemiSecenekleri, $this->calisanFiltreSecenekleri, $this->gorunenCalisanlar);
        // Önceki firmanın sektörü yeni firmaya taşınmasın — icerikYenile NACE'den/elle seçimden kurar.
        $this->sektorAnahtari = null;
        $this->sektorElle = false;
        $this->isKolu = '';
        $this->isKoluKalemleri = [];
        $this->calisanFiltre = '';
        $this->secilenCalisanIdler = $this->calisanlar->pluck('id')->all();
        $this->egitmenBilgileriYenile();
        $this->oncekiIcerikYoksay = false;
        $this->icerikYenile();
        $this->uyelerOtomatik = false;
        $this->manuelKatilimcilar = [];
        $this->grupGorevleri = [];
        $this->grupUyeleriniUygula();
    }

    /**
     * Firma seçilince eğitmen bilgilerini firmaya atanmış İSG Profesyoneli'nden
     * (Firma.igu / isyeriHekimi) çek — kaşesi belge oluşturulunca kopyalanır.
     */
    private function egitmenBilgileriYenile(): void
    {
        $hekim = $this->firma?->isyeriHekimi;

        $this->isyeriHekimiVar = $hekim !== null;
        $this->isyeriHekimiAdi = $hekim?->ad_soyad;
    }

    public function updatedBaslikAnahtari(): void
    {
        if ($this->baslikAnahtari !== 'genel') {
            $this->sektorAnahtari = null;
        }

        $this->oncekiIcerikYoksay = false;
        $this->icerikYenile();
        $this->grupUyeleriniUygula();
    }

    /**
     * Grup eğitimi (İSG Kurulu, çalışan temsilcisi, acil durum ekipleri, destek elemanları): katılımcılar o grubun güncel
     * üyelerinden OTOMATİK gelir (firma çalışanı olanlar işaretli, diğerleri ek katılımcı olarak). Fazlası aşağıdan seçilerek
     * ya da "Ek Katılımcı Ekle" ile eklenebilir. Grup eğitimi olmayan başlığa dönülünce tüm çalışanlar yeniden işaretlenir.
     */
    public function grupUyeleriniUygula(): void
    {
        if (! $this->firma) {
            return;
        }

        $uyeler = \App\Support\EkipAtamaEgitim::egitimGrubuUyeleri($this->firma, $this->baslikAnahtari);

        if ($uyeler === null) {
            if ($this->uyelerOtomatik) {
                $this->secilenCalisanIdler = $this->calisanlar->pluck('id')->all();
                $this->manuelKatilimcilar = [];
                $this->grupGorevleri = [];
                $this->uyelerOtomatik = false;
            }

            return;
        }

        if ($uyeler === []) {
            $this->uyelerOtomatik = false;
            Notification::make()->warning()->title('Bu grupta kayıtlı üye yok')->body('Katılımcıları aşağıdan seçin ya da önce Kurul / Acil Durum Ekipleri / Çalışan Temsilcisi sayfasında üyeleri atayın.')->send();

            return;
        }

        $ids = [];
        $manuel = [];
        $gorevler = [];

        foreach ($uyeler as $u) {
            $anahtar = \App\Support\EgitimTakibi::adAnahtari($u['ad_soyad']);
            $c = ($u['calisan_id'] ? $this->calisanlar->firstWhere('id', $u['calisan_id']) : null)
                ?? $this->calisanlar->first(fn (Calisan $x) => \App\Support\EgitimTakibi::adAnahtari($x->ad_soyad) === $anahtar);

            if ($c) {
                $ids[] = $c->id;
                $gorevler[$c->id] = $u['gorev'];
            } else {
                // Firma çalışanı olmayan üye (ör. dışarıdan İGU / hekim): ek katılımcı olarak gelir, çalışan listesine EKLENMEZ.
                $manuel[] = ['ad_soyad' => $u['ad_soyad'], 'tc' => $u['tc'], 'gorev' => $u['gorev'], 'grup_uyesi' => true];
            }
        }

        $this->secilenCalisanIdler = array_values(array_unique($ids));
        $this->manuelKatilimcilar = $manuel;
        $this->grupGorevleri = array_filter($gorevler);
        $this->uyelerOtomatik = true;

        Notification::make()->success()->title(count($uyeler).' üye katılımcı olarak otomatik geldi')->body('Fazlasını aşağıdan seçebilir ya da "Ek Katılımcı Ekle" ile ekleyebilirsiniz.')->send();
    }

    public function updatedSektorAnahtari(): void
    {
        // Kullanıcı açılır listeden seçti: NACE önerisinden farklıysa "elle" sayılır ve bu firmada hatırlanır.
        $this->sektorElle = filled($this->sektorAnahtari) && $this->sektorAnahtari !== $this->naceSektoru;
        unset($this->isKalemiSecenekleri);
        $this->isKoluKalemleri = array_values(array_intersect($this->isKoluKalemleri, array_keys($this->isKalemiSecenekleri)));
        $this->oncekiIcerikYoksay = false;
        $this->icerikYenile();
    }

    public function calisanToggle(int $id): void
    {
        $this->secilenCalisanIdler = in_array($id, $this->secilenCalisanIdler, true)
            ? array_values(array_diff($this->secilenCalisanIdler, [$id]))
            : [...$this->secilenCalisanIdler, $id];
    }

    public function tumCalisanlar(bool $sec): void
    {
        $this->secilenCalisanIdler = $sec ? $this->calisanlar->pluck('id')->all() : [];
    }

    /*
    |--------------------------------------------------------------------------
    | Katılımcılar — manuel ekleme & Excel toplu yükleme
    |--------------------------------------------------------------------------
    */

    public function manuelEkle(): void
    {
        $this->yeniTc = preg_replace('/\s+/', '', (string) $this->yeniTc) ?: null;

        $this->validate(
            ['yeniAdSoyad' => 'required|string|max:190', 'yeniTc' => 'nullable|digits:11'],
            ['yeniTc.digits' => 'T.C. Kimlik No 11 haneli olmalı (yazılan: :input).'],
        );

        $this->manuelKatilimcilar[] = [
            'ad_soyad' => $this->yeniAdSoyad,
            'tc' => $this->yeniTc,
            'gorev' => $this->yeniGorev ?: null,
        ];

        $this->reset('yeniAdSoyad', 'yeniTc', 'yeniGorev');
    }

    public function manuelCikar(int $index): void
    {
        unset($this->manuelKatilimcilar[$index]);
        $this->manuelKatilimcilar = array_values($this->manuelKatilimcilar);
    }

    public function excelIceAktar(): void
    {
        $this->validate(['excelDosya' => 'required|file|mimes:xlsx,xls,csv']);

        try {
            $sonuc = KatilimciExcelOkuyucu::oku($this->excelDosya->getRealPath());
        } catch (\Throwable $e) {
            Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

            return;
        }

        $eklenen = 0;

        foreach ($sonuc['katilimcilar'] as $k) {
            $zaten = collect($this->manuelKatilimcilar)->contains(
                fn ($m) => mb_strtolower(trim($m['ad_soyad'])) === mb_strtolower(trim($k['ad_soyad'])),
            );

            if (! $zaten) {
                $this->manuelKatilimcilar[] = $k;
                $eklenen++;
            }
        }

        $this->excelHatalar = $sonuc['hatalar'];
        $this->excelDosya = null;

        Notification::make()->title($eklenen.' katılımcı eklendi')->success()->send();
    }

    public function excelSablonIndir()
    {
        return KatilimciExcelOkuyucu::sablonIndir();
    }

    /** @return array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    private function katilimcilarTopla(): array
    {
        $firmaCalisanlari = $this->calisanlar
            ->whereIn('id', $this->secilenCalisanIdler)
            ->map(fn (Calisan $c) => [
                'ad_soyad' => $c->ad_soyad,
                'tc' => $c->tc,
                // Grup eğitiminde görev = gruptaki rolü (örn. İnsan Kaynakları Sorumlusu), çalışanın normal işi değil.
                'gorev' => $this->grupGorevleri[$c->id] ?? $c->gorev,
            ])
            ->values()
            ->all();

        return [...$firmaCalisanlari, ...$this->manuelKatilimcilar];
    }

    /**
     * Elle / Excel ile eklenen katılımcılardan firmanın çalışan listesinde
     * OLMAYANLARı (TC varsa TC'ye, yoksa ad-soyada göre) firmaya ekler.
     */
    /** eksikCalisanlariEkle() sırasında 11 hane olmadığı için çalışan kaydına yazılmayan T.C.'ler (yalnız bu istek). */
    private array $gecersizTcler = [];

    private function eksikCalisanlariEkle(): int
    {
        if (! $this->firma) {
            return 0;
        }

        $mevcut = $this->firma->calisanlar()->get(['tc', 'ad_soyad']);
        $eklenen = 0;

        foreach ($this->manuelKatilimcilar as $k) {
            $ad = trim((string) ($k['ad_soyad'] ?? ''));
            $tc = filled($k['tc'] ?? null) ? trim((string) $k['tc']) : null;

            // Çalışan kaydı 11 haneli T.C. kabul eder; hatalı T.C. indirmeyi düşürmesin (canlı 04.10.2026).
            if ($tc !== null && ! preg_match('/^\d{11}$/', $tc)) {
                $this->gecersizTcler[] = "{$ad}: {$tc}";
                $tc = null;
            }

            if ($ad === '' || ! empty($k['grup_uyesi'])) {
                continue;   // grup üyesi (kurul / ekip / temsilci) çalışan listesine eklenmez
            }

            $var = $mevcut->contains(function (Calisan $c) use ($ad, $tc) {
                if ($tc !== null && (string) $c->tc === $tc) {
                    return true;
                }

                return mb_strtolower((string) $c->ad_soyad) === mb_strtolower($ad);
            });

            if ($var) {
                continue;
            }

            $this->firma->calisanlar()->create([
                'ad_soyad' => $ad,
                'tc' => $tc,
                'gorev' => $k['gorev'] ?? null,
            ]);

            $mevcut->push(new Calisan(['ad_soyad' => $ad, 'tc' => $tc]));
            $eklenen++;
        }

        if ($eklenen > 0) {
            unset($this->calisanlar);
        }

        return $eklenen;
    }

    /**
     * Kayıtlı eğitim katılım formundan bir Sertifika modeli kurar (kaydedilmez;
     * yalnız PDF üretimi için). Eğitim başlığına göre sertifika tipi seçilir
     * (genel → isg, yüksekte_çalışma → yükseklik, kapalı_alan → kapali_alan).
     */
    /**
     * @param  array<int, string>|null  $egitimTarihleri  gün gün eğitim tarihleri (kullanıcı sorulunca);
     *                                                    boş ise belge tarihi gün sayısı kadar tekrarlanır
     */
    private function sertifikaKur(EgitimKatilimModel $kayit, ?array $egitimTarihleri = null): Sertifika
    {
        $tip = 'isg';

        foreach (config('isg.sertifika.tipler', []) as $anahtar => $tanim) {
            if (($tanim['icerik_anahtari'] ?? null) === $kayit->baslik_anahtari && $kayit->baslik_anahtari) {
                $tip = $anahtar;

                break;
            }
        }

        $gun = max(1, (int) ($kayit->sure_gun ?? 1));
        $belgeTarih = $kayit->belge_tarihi?->toDateString() ?? now()->toDateString();
        $tehlike = $kayit->firma?->tehlike_sinifi ?? 'az_tehlikeli';
        $dersSaati = $kayit->konu_secimleri['saat'] ?? null;

        $tarihler = collect($egitimTarihleri ?? [])
            ->filter()
            ->map(fn ($t) => Carbon::parse($t)->toDateString())
            ->values();

        // Kullanıcı sertifika tarihini vermediyse formda girilen gün tarihlerine düş.
        if ($tarihler->isEmpty()) {
            $tarihler = collect($kayit->gun_tarihleri ?: [])
                ->filter()
                ->map(fn ($t) => Carbon::parse($t)->toDateString())
                ->values();
        }

        if ($tarihler->isEmpty()) {
            $tarihler = collect(array_fill(0, $gun, $belgeTarih));
        }

        $sonTarih = $tarihler->last();

        $s = new Sertifika([
            'firma_id' => $kayit->firma_id,
            'tip' => $tip,
            'tur' => ($kayit->egitim_turu ?? 'ilk') === 'tekrar' ? 'tekrar' : 'ilk_defa',
            'sekil' => $kayit->egitim_sekli ?? 'yuz_yuze',
            'sektor_anahtari' => $tip === 'isg' ? $kayit->sektor_anahtari : null,
            'gun_sayisi' => max($gun, $tarihler->count()),
            'egitim_tarihleri' => $tarihler->all(),
            'gecerlilik_tarihi' => Carbon::parse($sonTarih)
                ->addYears((int) config('isg.sertifika.gecerlilik_yili.'.$tehlike, 1))
                ->toDateString(),
            'sure_metni' => $dersSaati ? $dersSaati.' Ders Saati' : null,
            'egitici_igu_dahil' => (bool) $kayit->isg_uzmani_var,
            'egitici_igu_adi' => $kayit->isg_uzmani_adi,
            'egitici_igu_kase' => $kayit->isg_uzmani_kase,
            'egitici_hekim_dahil' => (bool) $kayit->isyeri_hekimi_var,
            'egitici_hekim_adi' => $kayit->isyeri_hekimi_adi,
            'egitici_hekim_kase' => $kayit->isyeri_hekimi_kase,
            'logo_konumu' => 'sol',
            'cerceve' => 'sade',
            'konu_icerigi' => $kayit->konu_secimleri,
            'katilimcilar' => $kayit->belgeAlacakKatilimcilar(),
        ]);
        $s->belge_no = 'SRT · '.$kayit->belge_no;
        $s->setRelation('firma', $kayit->firma);

        return $s;
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?EgitimKatilimModel
    {
        // Tarih ve ders saati boş bırakılabilir — belgede elle doldurulacak boşluk çıkar (kullanıcı 15.10.2026).
        if (! $this->firma) {
            Notification::make()->title('Firma seçin')->danger()->send();

            return null;
        }

        // Firmada kayıtlı OLMAYAN katılımcılar firma çalışan listesine otomatik eklenir.
        $yeniCalisan = $this->eksikCalisanlariEkle();

        if ($yeniCalisan > 0) {
            Notification::make()->title($yeniCalisan.' katılımcı firma çalışan listesine eklendi')->success()->send();
        }

        if ($this->gecersizTcler) {
            Notification::make()->title('T.C. 11 haneli olmadığı için çalışan kaydına T.C.\'siz eklendi')
                ->body(implode('
', $this->gecersizTcler).'
Formda yazdığınız gibi görünür; Çalışanlar sayfasından düzeltebilirsiniz.')
                ->warning()->persistent()->send();
        }

        // Belgede görünen "Ders Saati" — kullanıcı elle değiştirdiyse ya da sildiyse (elle yazılacak) onu kaydet.
        $icerik = $this->icerik;
        $icerik['saat'] = $this->dersSaati ?: null;
        $icerik['sektor_elle'] = $this->baslikAnahtari === 'genel' && $this->sektorElle;

        $kayit = $this->kayitIcin(EgitimKatilimModel::class, [
            'firma_id' => $this->firma->id,
            'baslik_anahtari' => $this->baslikAnahtari,
            'egitim_turu' => $this->egitimTuru,
            'egitim_sekli' => $this->egitimSekli,
            'sektor_anahtari' => $this->baslikAnahtari === 'genel' ? $this->sektorAnahtari : null,
            'egitim_yeri' => $this->egitimYeri,
            'is_kolu' => $this->isKolu !== '' ? $this->isKolu : null,
            'is_kolu_kalemleri' => $this->isKoluKalemleri ?: null,
            'belge_tarihi' => $this->belgeTarihi,
            'sure_gun' => $this->sureGun,
            'gun_tarihleri' => $this->sureGun >= 2
                ? (array_values(array_filter($this->gunTarihleri)) ?: null)
                : null,
            'isg_uzmani_var' => $this->isgUzmaniVar,
            'isg_uzmani_adi' => $this->isgUzmaniVar ? $this->firma->igu?->ad_soyad : null,
            'isg_uzmani_kase' => $this->isgUzmaniVar ? $this->firma->igu?->kase_gorseli : null,
            'isyeri_hekimi_var' => $this->isyeriHekimiVar,
            'isyeri_hekimi_adi' => $this->isyeriHekimiVar
                ? ($this->isyeriHekimiAdi ?: $this->firma->isyeriHekimi?->ad_soyad)
                : null,
            'isyeri_hekimi_kase' => $this->isyeriHekimiVar ? $this->firma->isyeriHekimi?->kase_gorseli : null,
            'konu_secimleri' => $icerik,
            'katilimcilar' => $this->katilimcilarTopla(),
        ]);
        $kayit->save();

        // Formdaki işe özgü konular yıllık eğitim planının 4. bölümüne yazılır (önce), sonra işlenenler "tamamlandı" işaretlenir.
        IseOzguSenkron::formdanPlana($kayit);

        // İş koluna göre kayıt: işe özgü konular kütüphaneye o iş koluyla yazılır (sonraki formlara / diğer firmalara gelir).
        if (($eklenen = IseOzguEgitimKutuphanesi::isKolundanKaydet($kayit)) > 0) {
            Notification::make()->title($eklenen.' konu "'.$kayit->is_kolu.'" iş koluyla kütüphaneye kaydedildi')->success()->send();
        }

        // İşlenen temel/işe özgü konular yıllık eğitim planında o aya "tamamlandı" işlenir.
        EgitimKonulari::planiIsle($kayit);

        unset($this->gecmisKayitlar);

        return $kayit;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->kaydetAction(),
            $this->yeniKayitAction(),
            // Seçili firma adlı BOŞ form (katılımcısız, elle doldurulacak): kayıt oluşturmaz, yıllık planı etkilemez.
            Action::make('bosFormFirma')
                ->label('Boş Form (PDF)')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->tooltip('Ekrandaki konularla (firmanın güncel 4. bölüm konuları dahil) boş, katılımcısız formu indirir; ad/T.C./imza elle doldurulur. Kayıt oluşturmaz.')
                ->action(fn () => EgitimKatilimUretici::bosFormPdf(
                    $this->baslikAnahtari,
                    $this->baslikAnahtari === 'genel' ? $this->sektorAnahtari : null,
                    $this->firma->tehlike_sinifi ?: 'az_tehlikeli',
                    $this->egitimTuru,
                    $this->firma,
                    // Ekrandaki (düzenlenmiş) konu içeriği + firmanın konuları eklenir; iş kolu (grup) formunda yalnız ekrandakiler.
                    $this->isKolu !== ''
                        ? $this->icerik
                        : EgitimFirmaIcerigi::firmaKonulariniEkle($this->icerik, $this->firma, $this->baslikAnahtari, $this->baslikAnahtari === 'genel' ? $this->sektorAnahtari : null, $this->egitimTuru),
                )),
            // Çıktı eylemleri başlıkta GİZLİ kalır (display:none); sayfadaki "Çıktı Al" menüsü (egitim-katilim.blade.php) bunları
            // mountAction ile açar. Filament'in açılır menüsü bazı ekranlarda sol üst köşede açıldığı için menü sayfaya sabitlendi.
            Action::make('pdf')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Eğitim Katılım Formu — PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no)->success()->send();

                    return EgitimKatilimUretici::pdf($kayit, ImzaSecenegi::secili($data));
                }),

            Action::make('excel')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Eğitim Katılım Formu — Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function () {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no)->success()->send();

                    return EgitimKatilimUretici::excel($kayit);
                }),

            Action::make('word')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Eğitim Katılım Formu — Word')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no)->success()->send();

                    return EgitimKatilimUretici::word($kayit, ImzaSecenegi::secili($data));
                }),

            Action::make('sertifika')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Katılımcı Sertifikaları — PDF')
                ->icon('heroicon-o-check-badge')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalHeading('Katılımcı Sertifikaları')
                ->modalDescription('Sertifikaya basılacak eğitim tarih(ler)ini girin. Her katılımcı için ayrı sertifika sayfası oluşturulur.')
                ->schema(fn () => [
                    ...array_map(
                        fn (int $g) => DatePicker::make("egitim_gun_{$g}")
                            ->label((int) $this->sureGun > 1 ? "{$g}. Gün Eğitim Tarihi" : 'Eğitim Tarihi')
                            ->default($this->gunTarihleri[$g - 1] ?? $this->belgeTarihi)
                            ->required(),
                        range(1, max(1, (int) $this->sureGun)),
                    ),
                    ImzaSecenegi::alan(),
                ])
                ->action(function (array $data) {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    $tarihler = array_values(array_filter(collect($data)->except('imzali')->all()));

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no.' — her katılımcı için ayrı sertifika sayfası')->success()->send();

                    return SertifikaUretici::pdf($this->sertifikaKur($kayit, $tarihler), ImzaSecenegi::secili($data));
                }),

            Action::make('yildizGrupSertifika')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Yıldız Grup Eğitim Sertifikası — Excel / PDF')
                ->icon('heroicon-o-check-badge')
                ->color('gray')
                // Şablon 4 sabit kategoriye (Genel/Sağlık/Teknik/İşe Özgü) dayanır → yalnız "genel" başlık.
                ->visible(fn () => $this->firma !== null && $this->baslikAnahtari === 'genel')
                ->modalHeading('Yıldız Grup Eğitim Sertifikası')
                ->modalDescription('Eğitim konuları ve süreleri bu katılım formundan alınır. Sertifikaya basılacak eğitim tarih(ler)ini girin. Excel: her katılımcı için ayrı sayfa/dosya; PDF: tek dosyada her katılımcı için belge + eğitim konuları sayfası.')
                ->schema(fn () => [CiktiBicimi::alan(), ImzaSecenegi::alan(), ...array_map(
                    fn (int $g) => DatePicker::make("egitim_gun_{$g}")
                        ->label((int) $this->sureGun > 1 ? "{$g}. Gün Eğitim Tarihi" : 'Eğitim Tarihi')
                        ->default($this->gunTarihleri[$g - 1] ?? $this->belgeTarihi)
                        ->required(),
                    range(1, max(1, (int) $this->sureGun)),
                )])
                ->action(function (array $data) {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    $tarihler = array_values(array_filter($data, fn ($v, $k) => str_starts_with((string) $k, 'egitim_gun_') && filled($v), ARRAY_FILTER_USE_BOTH));
                    $indirme = SertifikaYildizGrupUretici::cikti($this->sertifikaKur($kayit, $tarihler), ImzaSecenegi::secili($data), CiktiBicimi::secili($data));

                    if (! $indirme) {
                        Notification::make()->title('Yıldız Grup şablonu bu eğitim için uygun değil')->warning()->send();

                        return null;
                    }

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no.' — Yıldız Grup sertifikası ('.(CiktiBicimi::secili($data) === 'pdf' ? 'PDF' : 'Excel').')')->success()->send();

                    return $indirme;
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $kayit = $this->firma?->egitimKatilimlari()->find($id);

        return $kayit ? EgitimKatilimUretici::pdf($kayit) : null;
    }

    public function gecmisExcel(int $id)
    {
        $kayit = $this->firma?->egitimKatilimlari()->find($id);

        return $kayit ? EgitimKatilimUretici::excel($kayit) : null;
    }

    public function gecmisWord(int $id)
    {
        $kayit = $this->firma?->egitimKatilimlari()->find($id);

        return $kayit ? EgitimKatilimUretici::word($kayit) : null;
    }

    public function gecmisSertifika(int $id)
    {
        $kayit = $this->firma?->egitimKatilimlari()->find($id);

        if (! $kayit) {
            return null;
        }

        $kayit->setRelation('firma', $this->firma);

        return SertifikaUretici::pdf($this->sertifikaKur($kayit));
    }

    public function gecmisYildizGrup(int $id, bool $imzali = true, string $bicim = 'xlsx')
    {
        $kayit = $this->firma?->egitimKatilimlari()->find($id);

        if (! $kayit) {
            return null;
        }

        $kayit->setRelation('firma', $this->firma);

        return SertifikaYildizGrupUretici::cikti($this->sertifikaKur($kayit), $imzali, $bicim);
    }

    /** Geçmiş kayıttan Yıldız Grup sertifikası — imzalı / imzasız sorulur. */
    public function gecmisYildizGrupAction(): Action
    {
        return Action::make('gecmisYildizGrup')
            ->modalHeading('Yıldız Grup Eğitim Sertifikası')
            ->modalSubmitActionLabel('İndir')
            ->schema([CiktiBicimi::alan(), ImzaSecenegi::alan()])
            ->action(fn (array $data, array $arguments) => $this->gecmisYildizGrup((int) $arguments['id'], ImzaSecenegi::secili($data), CiktiBicimi::secili($data)));
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->egitimKatilimlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }

    /*
    |--------------------------------------------------------------------------
    | Boş İmza Formu — firma/katılımcı seçmeden, her konu için tek tıkla indir
    |--------------------------------------------------------------------------
    */

    public string $bosFormTehlikeSinifi = 'az_tehlikeli';

    public string $bosFormTuru = 'ilk';

    public ?string $bosFormSektor = null;

    public function bosFormIndir(string $baslikAnahtari)
    {
        return EgitimKatilimUretici::bosFormPdf(
            $baslikAnahtari,
            $baslikAnahtari === 'genel' ? $this->bosFormSektor : null,
            $this->bosFormTehlikeSinifi,
            $this->bosFormTuru,
        );
    }
}
