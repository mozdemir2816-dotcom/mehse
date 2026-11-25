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
use App\Support\EgitimSinavKagidiUretici;
use App\Support\GeminiIseOzguEgitimUretici;
use App\Support\IseOzguEgitimKutuphanesi;
use App\Support\IseOzguSenkron;
use App\Support\KatilimciExcelOkuyucu;
use App\Support\SertifikaUretici;
use App\Support\SertifikaYildizGrupUretici;
use App\Support\SgkIseGirisOkuyucu;
use App\Support\YillikEgitimPlanlayici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

    protected static string|UnitEnum|null $navigationGroup = 'Merkezler';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'egitim-katilim';

    protected static ?string $title = 'Eğitim Katılım Formu';

    protected static ?string $navigationLabel = 'Eğitim & Sınav';

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

    /** Katılımcı görev süzgeci (ör. Kalıpçı, Sıvacı — boş = tümü). */
    public string $gorevFiltre = '';

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

        if ($firmaId = request()->integer('firma') ?: \App\Support\KomutaFirma::baslangic($this->firmalar)) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }

        // ?baslik=<anahtar>: ilgili eğitim başlığı hazır seçili açılır (Kurul / Acil Durum Ekipleri / Çalışan Temsilcisi sayfalarındaki
        // "Eğitim Katılım'da aç" bağlantıları) — yanlış başlık seçilip mükerrer / uyuşmayan evrak oluşmasın.
        $baslik = (string) request()->query('baslik');

        if ($baslik !== '' && array_key_exists($baslik, $this->basliklar)) {
            $this->baslikAnahtari = $baslik;
            $this->sektorAnahtari = $baslik === 'genel' ? $this->sektorAnahtari : null;
            $this->icerikYenile();
            $this->grupUyeleriniUygula();
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

    /** Katılımcı görev süzgeci seçenekleri (ör. Kalıpçı, Sıvacı, Demirci). @return array<int, string> */
    #[Computed]
    public function gorevFiltreSecenekleri(): array
    {
        return $this->calisanlar->pluck('gorev')
            ->map(fn ($g) => trim((string) $g))->filter()->unique()->sort()->values()->all();
    }

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function gorunenCalisanlar(): Collection
    {
        return $this->calisanlar
            ->when($this->calisanFiltre !== '', fn (Collection $col) => $col->filter(
                fn (Calisan $c) => trim((string) $c->sube) === $this->calisanFiltre || trim((string) $c->departman) === $this->calisanFiltre,
            ))
            ->when($this->gorevFiltre !== '', fn (Collection $col) => $col->filter(
                fn (Calisan $c) => trim((string) $c->gorev) === $this->gorevFiltre,
            ))
            ->values();
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
        if ($this->firma) {
            IseOzguSenkron::firmaGrubuKaydet($this->firma, null);
        }

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

    private function icerikYenile(bool $sekliOtomatikBelirle = true): void
    {
        // Sektör firmanın NACE kodundan gelir; kullanıcı bu firmada daha önce elle
        // farklı sektör/gerçek iş grubu seçtiyse (NACE ile yapılan iş uyuşmuyor) o seçim gelir —
        // kullanıcı kararı 25.10.2026 & 24.11.2026. Başka firmadaki seçim taşınmaz.
        if ($this->baslikAnahtari === 'genel' && blank($this->sektorAnahtari) && ! $this->oncekiIcerikYoksay) {
            $elle = IseOzguSenkron::firmaGrubuOku($this->firma) ?? $this->elleSecilenSektor();
            $this->sektorAnahtari = $elle ?? $this->naceSektoru;
            $this->sektorElle = $elle !== null && $elle !== $this->naceSektoru;
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
            // Firma geneli (ekip seçilmemiş) temel eğitimde, firmanın bu yılki Yıllık Eğitim Planı varsa
            // 4. bölüm Yıllık Eğitim Planı ile birebir senkron tutulur (plandan silinen konu geri gelmez).
            if ($this->baslikAnahtari === 'genel' && $this->isKolu === '' && $this->firma) {
                $this->planOzguBolumunuKur();
            }
        } else {
            $this->icerik = $taze;
            $this->oncekidenYuklendi = false;
            $this->isKalemiKonulariniOner();
            $this->isKoluKutuphanedenEkle();
        }

        // 08.11.2026: firma online eğitim kullanıyorsa (tehlikeli / çok tehlikeli) varsayılan açılışta
        // şekil "Karma" (online sonrası yüz yüze tamamlama) seçilir.
        $yuzYuze = $sekliOtomatikBelirle ? $this->onlineTamamlayiciSaat() : null;
        if ($yuzYuze !== null) {
            $this->egitimSekli = 'karma';
        }

        // 24.11.2026: 3 ayrı Eğitim Katılım Formu modu (Tamamı Yüz Yüze | Online/Uzaktan | Yüz Yüze Tamamlama Karma).
        // Kullanıcı şekli değiştirdiyse, ya da aktif şekil uzaktan/karma ise, ya da önceki kayıt farklı moddaysa konuları moda göre uygula.
        $oncekiFarkliMod = in_array($this->icerik['form_modu'] ?? null, ['uzaktan', 'karma'], true) && $this->egitimSekli === 'yuz_yuze';
        if ($this->baslikAnahtari === 'genel' && (! $sekliOtomatikBelirle || $this->egitimSekli !== 'yuz_yuze' || $oncekiFarkliMod)) {
            $this->icerik = YillikEgitimPlanlayici::katilimFormuModuUygula(
                $this->icerik,
                $this->firma,
                $this->egitimSekli,
                $this->egitimTuru,
            );
        }

        // Tehlike sınıfına ve seçili oturum moduna göre ders saati (8/12/16 tam; 8/4 online; 4/8 karma) — kullanıcı elle değiştirebilir.
        $this->dersSaati = $this->icerik['saat'] ?? null;
        if ($this->egitimSekli === 'karma' && $yuzYuze !== null) {
            $this->dersSaati = $yuzYuze;
        }

        // Yıllık Eğitim Planından gelen 4. bölüm varsa (Tehlikeli 4s = 180 dk), tüm 4 bloğun dakikalarını
        // toplam ders saatine tam eşitle ki eski/yeni dakika toplamları karışmasın.
        if ($this->baslikAnahtari === 'genel' && $this->egitimSekli === 'yuz_yuze' && str_contains((string) ($this->icerik['isyerine_ozgu']['sektor'] ?? ''), 'Yıllık Eğitim Planı')) {
            $this->tumBlokSureleriniDagit();
        }

        $this->sureGunYenile();
    }

    /**
     * Genel temel eğitimde, firma tehlikeli / çok tehlikeli ve çalışanlarına online
     * temel eğitim atanmışsa yüz yüze verilecek ders saati; değilse null.
     */
    private function onlineTamamlayiciSaat(): ?int
    {
        if ($this->baslikAnahtari !== 'genel' || ! $this->firma) {
            return null;
        }

        $tur = $this->egitimTuru === 'tekrar' ? 'yenileme' : 'ilk_defa';
        $sonAtama = $this->sonOnlineAtama($tur);

        if (! $sonAtama) {
            return null;
        }

        $saat = $this->yuzYuzeSaati($tur, $sonAtama->paket);

        return $saat ?: null;
    }

    /** Firmadaki son online temel eğitim ataması — önce aynı türden, yoksa herhangi biri. */
    private function sonOnlineAtama(string $tur): ?\App\Models\EgitimAtamasi
    {
        $sorgu = fn () => \App\Models\EgitimAtamasi::query()
            ->whereIn('egitim_turu', ['ilk_defa', 'yenileme'])
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firma->id))
            ->with('paket')->latest('atandi_at')->latest('id');

        return $sorgu()->where('egitim_turu', $tur)->first() ?? $sorgu()->first();
    }

    /** Online paketin türüne göre (8 saat ilk / 4 saat yenileme) yüz yüze tamamlanacak ders saati. */
    private function yuzYuzeSaati(string $tur, ?\App\Models\EgitimPaketi $paket): int
    {
        $sinif = (string) $this->firma->tehlike_sinifi;

        return (int) ($paket
            ? $paket->gerekenSure($sinif, $tur)['yuz_yuze_saat']
            : \App\Models\EgitimPaketi::gerekenUzaktanSure($sinif, $tur)['yuz_yuze_saat']);
    }

    /** Şekil elle değişince konu blokları ve ders saati seçili forma göre kurulur (Tamamı Yüz Yüze | Uzaktan | Karma). */
    public function updatedEgitimSekli(): void
    {
        unset($this->sureYasalUyarisi);
        $this->icerikYenile(false);
    }

    /**
     * Taze (önceki kayıttan gelmeyen) içerikte, firmanın seçili iş kalemlerine
     * (bkz. Firma::isKalemiEgitimKonulari) göre önerilen konuları "işyerine
     * özgü" madde listesine ekler — kullanıcı sonradan çıkarabilir/düzenler.
     */
    private function isKalemiKonulariniOner(): void
    {
        // Grup (iş kolu) formu (ör. Kalıp Ekibi vs Sıva Ekibi): 4. bölüm YALNIZ seçilen iş kalemlerinin
        // konularıyla kurulur — ilgisiz kazı/iş makinesi/genel şablon konuları ekibe karışmaz.
        if ($this->isKoluKalemleri) {
            $konular = collect(config('isg.kkd_matris.is_kalemleri', []))
                ->flatten(1)
                ->whereIn('anahtar', $this->isKoluKalemleri)
                ->pluck('egitim_konusu')
                ->filter()
                ->unique()
                ->values();

            if ($konular->isNotEmpty()) {
                $sinif = $this->firma?->tehlike_sinifi ?: 'az_tehlikeli';
                $hedefDk = in_array($sinif, ['tehlikeli', 'cok_tehlikeli'], true) ? 180 : 90;
                $dk = max(1, (int) round($hedefDk / $konular->count()));

                $this->icerik['isyerine_ozgu'] = [
                    'sektor' => $this->isKolu !== '' ? $this->isKolu : ($this->icerik['isyerine_ozgu']['sektor'] ?? 'İş Kolu / Ekip'),
                    'maddeler' => $konular->map(fn (string $konu) => ['madde' => $konu, 'dakika' => $dk, 'dahil' => true])->all(),
                ];
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

        // Kullanıcının AI/elle kütüphaneye bu firmanın NACE'si / iş kalemi için kaydettiği kendi konuları da otomatik gelir.
        if ($this->firma) {
            $kutuphaneKendi = IseOzguEgitimKutuphanesi::firmaIcin($this->firma)
                ->filter(fn (array $k) => ! ($k['sistem'] ?? true))
                ->pluck('ad')
                ->all();
            $konular = array_values(array_unique([...$konular, ...$kutuphaneKendi]));
        }

        if (blank($konular)) {
            return;
        }

        $mevcut = collect($this->icerik['isyerine_ozgu']['maddeler'])->pluck('madde')->map(fn ($m) => mb_strtolower(trim((string) $m)))->all();

        foreach ($konular as $konu) {
            if (in_array(mb_strtolower(trim((string) $konu)), $mevcut, true)) {
                continue;
            }

            $this->icerik['isyerine_ozgu']['maddeler'][] = ['madde' => $konu, 'dakika' => 10, 'dahil' => true];
            $mevcut[] = mb_strtolower(trim((string) $konu));
        }

        $this->blokSuresiniDagit('isyerine_ozgu');
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
        if ($this->dersSaati && $this->dersSaati > 0) {
            $this->tumBlokSureleriniDagit();
        }
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

    /** Konu dakikası / dahil durumu her değiştiğinde süreleri ve gün sayısını yeniden hesapla. */
    public function updatedIcerik(mixed $value = null, ?string $key = null): void
    {
        // Kullanıcı bir konunun "dahil" kutusunu işaretleyip/kaldırdığında o bloğun toplam süresi
        // kalan dahil konulara otomatik dağıtılır; elle dakika yazılırsa manuel değer korunur.
        if (is_string($key) && str_ends_with($key, '.dahil')) {
            if (str_starts_with($key, 'isyerine_ozgu.')) {
                $this->blokSuresiniDagit('isyerine_ozgu');
            } elseif (str_starts_with($key, 'genel_konular.')) {
                $this->blokSuresiniDagit('genel_konular');
            } elseif (str_starts_with($key, 'saglik_konulari.')) {
                $this->blokSuresiniDagit('saglik_konulari');
            } elseif (str_starts_with($key, 'teknik_konular.')) {
                $this->blokSuresiniDagit('teknik_konular');
            } elseif (str_starts_with($key, 'maddeler.')) {
                $this->ozelSureyiDagit();
            }
        }

        $this->sureGunYenile();
    }

    /**
     * Seçili eğitim şekli, tehlike sınıfı ve Ders Saati'ne göre ilgili konu bloğunun
     * hedef fiili ders dakikasını (1 ders saati = 45 dk fiili) hesaplar.
     */
    private function blokHedefFiiliDk(string $blokAnahtari): int
    {
        $katEslesme = [
            'genel_konular' => 'genel',
            'saglik_konulari' => 'saglik',
            'teknik_konular' => 'teknik',
            'isyerine_ozgu' => 'ise_ozgu',
        ];
        $kat = $katEslesme[$blokAnahtari] ?? null;

        if (! $kat || ($this->icerik['tip'] ?? null) !== 'genel') {
            return 0;
        }

        $k = $this->egitimTuru === 'tekrar' ? 'tekrar' : 'ilk';
        $dagilim = YillikEgitimPlanlayici::bolumDagilimi($this->firma, 'karma_lms');

        if ($this->egitimSekli === 'uzaktan' || $this->egitimSekli === 'karma') {
            $alan = $this->egitimSekli === 'uzaktan' ? "{$k}_online" : "{$k}_yuz_yuze";
            $bazToplam = (float) $dagilim[$alan];
            $bazBlok = (float) ($dagilim['bolumler'][$kat][$alan] ?? 0);

            if ($bazToplam <= 0 || $bazBlok <= 0) {
                return 0;
            }

            $etkinToplam = ($this->dersSaati && $this->dersSaati > 0) ? (float) $this->dersSaati : $bazToplam;

            return (int) round(($bazBlok / $bazToplam) * $etkinToplam * 45);
        }

        // Tamamı Yüz Yüze: Yıllık Eğitim Planı bölüm dağılımı oranlarıyla (Tehlikeli: 2+2+4+4=12s; Tekrar: 1+1+2+4=8s;
        // Çok Tehlikeli: 2+3+7+4=16s; Az Tehlikeli: 2+2+2+2=8s) toplam ders saatine tam dağıtılır.
        $bazToplam = (float) $dagilim["{$k}_toplam"];
        $bazBlok = (float) ($dagilim['bolumler'][$kat]["{$k}_toplam"] ?? 0);
        $etkinToplam = ($this->dersSaati && $this->dersSaati > 0)
            ? (float) $this->dersSaati
            : (float) ($this->icerik['saat'] ?? $bazToplam);

        if ($bazToplam <= 0 || $bazBlok <= 0) {
            return 0;
        }

        return (int) round(($bazBlok / $bazToplam) * $etkinToplam * 45);
    }

    /** Tek bir bloğun dahil konularına hedef fiili dakikayı kayıpsız dağıtır. */
    private function blokSuresiniDagit(string $blokAnahtari): void
    {
        if (($this->icerik['tip'] ?? null) !== 'genel') {
            return;
        }

        $hedefDk = $this->blokHedefFiiliDk($blokAnahtari);
        if ($hedefDk <= 0) {
            return;
        }

        if ($blokAnahtari === 'isyerine_ozgu') {
            if (! isset($this->icerik['isyerine_ozgu']['maddeler'])) {
                return;
            }
            $this->icerik['isyerine_ozgu']['maddeler'] = EgitimIcerikOlusturucu::bolumDakikalariniDagit(
                $this->icerik['isyerine_ozgu']['maddeler'],
                $hedefDk,
            );

            return;
        }

        if (isset($this->icerik[$blokAnahtari]) && is_array($this->icerik[$blokAnahtari])) {
            $this->icerik[$blokAnahtari] = EgitimIcerikOlusturucu::bolumDakikalariniDagit(
                $this->icerik[$blokAnahtari],
                $hedefDk,
            );
        }
    }

    /** Ders Saati değişince tüm aktif blokların konu dakikalarını yeni toplam süreye göre günceller. */
    private function tumBlokSureleriniDagit(): void
    {
        if (($this->icerik['tip'] ?? null) === 'ozel') {
            $this->ozelSureyiDagit();

            return;
        }

        foreach (['genel_konular', 'saglik_konulari', 'teknik_konular', 'isyerine_ozgu'] as $blok) {
            $this->blokSuresiniDagit($blok);
        }
    }

    /** Özel (tek bloklu) eğitimde ders saati giriliyse toplam fiili dakikayı aktif konulara dağıtır. */
    private function ozelSureyiDagit(): void
    {
        if (($this->icerik['tip'] ?? null) !== 'ozel' || ! isset($this->icerik['maddeler']) || ! $this->dersSaati || $this->dersSaati <= 0) {
            return;
        }

        $this->icerik['maddeler'] = EgitimIcerikOlusturucu::bolumDakikalariniDagit(
            $this->icerik['maddeler'],
            (int) round($this->dersSaati * 45),
        );
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
        $this->blokSuresiniDagit('isyerine_ozgu');
        $this->sureGunYenile();
    }

    public function isyerineOzguMaddeCikar(int $index): void
    {
        if (! isset($this->icerik['isyerine_ozgu']['maddeler'][$index])) {
            return;
        }

        unset($this->icerik['isyerine_ozgu']['maddeler'][$index]);
        $this->icerik['isyerine_ozgu']['maddeler'] = array_values($this->icerik['isyerine_ozgu']['maddeler']);
        $this->blokSuresiniDagit('isyerine_ozgu');
        $this->sureGunYenile();
    }

    /**
     * Gemini API ile seçilen sektör / ekip / saha bağlamına özel 4. bölüm konularını üretir,
     * forma ekler ve toplam eğitim süresine göre dakikaları otomatik dağıtır.
     */
    public function aiIseOzguKonuUretAction(): Action
    {
        return Action::make('aiIseOzguKonuUret')
            ->modalHeading('✨ Yapay Zekâ (API) ile İşe Özgü Eğitim Konuları Üret')
            ->modalDescription('Seçili sektör ve ekip/iş kolu için 4. Bölüm ("İşe ve İşyerine Özgü Riskler") konularını üretir; eklenen konular toplam eğitim süresine göre otomatik dakikalandırılır.')
            ->modalSubmitActionLabel('Konuları Üret ve Uygula')
            ->fillForm(fn () => [
                'sektor' => ($this->sektorAnahtari && $this->sektorAnahtari !== 'ozel') ? $this->sektorAnahtari : ($this->naceSektoru ?: 'insaat'),
                'is_kolu' => $this->isKolu ?: null,
                'adet' => 5,
                'ekleme_sekli' => 'degistir',
                'kutuphaneye_kaydet' => false,
            ])
            ->schema([
                Select::make('sektor')
                    ->label('Sektör / İş Grubu')
                    ->options(GeminiIseOzguEgitimUretici::sektorSecenekleri())
                    ->searchable()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('is_kolu')
                    ->label('Çalışan Grubu / Ekip (Opsiyonel)')
                    ->placeholder('Örn: Kalıp Ekibi, Sıva Ekibi, CNC Operatörleri, Depo'),
                \Filament\Forms\Components\Textarea::make('ek_baglam')
                    ->label('Ek Saha / Ekipman Bilgisi (Opsiyonel)')
                    ->rows(2)
                    ->placeholder('Örn: Kule vinç, dış cephe iskelesi, epoksi boya, forklift'),
                \Filament\Schemas\Components\Grid::make(2)->schema([
                    Select::make('adet')
                        ->label('Üretilecek Konu Sayısı')
                        ->options([3 => '3 konu', 4 => '4 konu', 5 => '5 konu', 6 => '6 konu', 8 => '8 konu'])
                        ->default(5)
                        ->required(),
                    \Filament\Forms\Components\Radio::make('ekleme_sekli')
                        ->label('Uygulama Şekli')
                        ->options([
                            'ekle' => 'Mevcut konuların üzerine ekle',
                            'degistir' => '4. bölümü yenileriyle değiştir',
                        ])
                        ->default('ekle')
                        ->required(),
                ]),
                \Filament\Forms\Components\Toggle::make('kutuphaneye_kaydet')
                    ->label('Üretilen konuları İşe Özgü Eğitim Kütüphanesi\'ne de kaydet (aynı sektördeki diğer firmalar için)')
                    ->default(true),
            ])
            ->action(function (array $data): void {
                $sektorAnahtar = (string) ($data['sektor'] ?? '');
                $sektorler = GeminiIseOzguEgitimUretici::sektorSecenekleri();
                $sektorAd = $sektorler[$sektorAnahtar] ?? $sektorAnahtar;
                $isKolu = filled($data['is_kolu'] ?? null) ? trim((string) $data['is_kolu']) : null;
                $ekBaglam = filled($data['ek_baglam'] ?? null) ? trim((string) $data['ek_baglam']) : null;
                $adet = (int) ($data['adet'] ?? 5);

                $sonuc = GeminiIseOzguEgitimUretici::uret(
                    $sektorAd,
                    $this->firma?->nace_kodu ?: GeminiIseOzguEgitimUretici::sektorNaceOnekleri($sektorAnahtar),
                    $this->firma?->tehlike_sinifi,
                    $isKolu,
                    $ekBaglam,
                    $adet,
                );

                if ($sonuc['hata'] !== null) {
                    Notification::make()->title('Konu üretilemedi')->body($sonuc['hata'])->danger()->send();

                    return;
                }

                if (($this->icerik['tip'] ?? null) === 'genel') {
                    if (! isset($this->icerik['isyerine_ozgu']['maddeler'])) {
                        $this->icerik['isyerine_ozgu'] = [
                            'sektor' => $isKolu ?: $sektorAd,
                            'maddeler' => [],
                        ];
                    }

                    $mevcutlar = ($data['ekleme_sekli'] ?? 'ekle') === 'degistir'
                        ? []
                        : array_values(array_filter(
                            $this->icerik['isyerine_ozgu']['maddeler'],
                            fn ($m) => filled(trim((string) ($m['madde'] ?? ''))),
                        ));

                    $mevcutAdlar = collect($mevcutlar)->pluck('madde')->map(fn ($m) => mb_strtolower(trim((string) $m)))->flip();

                    foreach ($sonuc['konular'] as $k) {
                        $anahtar = mb_strtolower($k['ad']);
                        if (! $mevcutAdlar->has($anahtar)) {
                            $mevcutlar[] = ['madde' => $k['ad'], 'dakika' => 15, 'dahil' => true];
                            $mevcutAdlar->put($anahtar, true);
                        }
                    }

                    $this->icerik['isyerine_ozgu']['maddeler'] = $mevcutlar;
                    $this->blokSuresiniDagit('isyerine_ozgu');
                    $this->sureGunYenile();
                }

                $kaydedilen = 0;
                if (! empty($data['kutuphaneye_kaydet']) && ($userId = Filament::auth()->id())) {
                    $nace = GeminiIseOzguEgitimUretici::sektorNaceOnekleri($sektorAnahtar, $this->firma?->nace_kodu);
                    $kaydedilen = GeminiIseOzguEgitimUretici::kutuphaneyeKaydet($userId, $sonuc['konular'], $nace, $isKolu, $this->isKoluKalemleri);
                }

                Notification::make()
                    ->title(count($sonuc['konular']).' işe özgü konu eklendi ve süreler güncellendi')
                    ->body($kaydedilen > 0 ? "{$kaydedilen} yeni konu kütüphaneye de kaydedildi." : 'Dakikalar toplam eğitim süresine göre otomatik dağıtıldı.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Özel (tek bloklu) başlıklarda kullanıcının eklediği konular (`ek` işaretli)
     * — ör. Fiziksel Risk Etmenleri'ne birden fazla yeni konu.
     */
    public function ozelMaddeEkle(): void
    {
        if (($this->icerik['tip'] ?? null) !== 'ozel') {
            return;
        }

        $this->icerik['maddeler'][] = ['madde' => '', 'dakika' => 15, 'dahil' => true, 'ek' => true];
        $this->ozelSureyiDagit();
        $this->sureGunYenile();
    }

    public function ozelMaddeCikar(int $index): void
    {
        if (empty($this->icerik['maddeler'][$index]['ek'])) {
            return;
        }

        unset($this->icerik['maddeler'][$index]);
        $this->icerik['maddeler'] = array_values($this->icerik['maddeler']);
        $this->ozelSureyiDagit();
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
        \App\Support\KomutaFirma::sec($this->firmaId);
        $this->kayitUnut();

        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar, $this->naceSektoru, $this->isKalemiSecenekleri, $this->calisanFiltreSecenekleri, $this->gorevFiltreSecenekleri, $this->gorunenCalisanlar, $this->asgariDersSaati, $this->sureYasalUyarisi);
        // Önceki firmanın sektörü yeni firmaya taşınmasın — icerikYenile NACE'den/elle seçimden kurar.
        $this->sektorAnahtari = null;
        $this->sektorElle = false;
        $this->isKolu = '';
        $this->isKoluKalemleri = [];
        $this->calisanFiltre = '';
        $this->gorevFiltre = '';
        $this->secilenCalisanIdler = $this->calisanlar->pluck('id')->all();
        $this->egitmenBilgileriYenile();
        $this->egitimSekli = 'yuz_yuze';
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
        $icerik = EgitimIcerikOlusturucu::bosKonulariAyikla($this->icerik);
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

        // Kullanıcı NACE dışı bir "Gerçek İş Grubu" seçtiyse Yıllık Eğitim Planı ile ortak kullanılması için firmada hatırla.
        if ($this->baslikAnahtari === 'genel' && $this->sektorElle && filled($this->sektorAnahtari)) {
            IseOzguSenkron::firmaGrubuKaydet($this->firma, $this->sektorAnahtari);
        }

        // Formdaki işe özgü konular yıllık eğitim planının 4. bölümüne yazılır (önce), sonra işlenenler "tamamlandı" işaretlenir.
        IseOzguSenkron::formdanPlana($kayit);

        // Aynı başlıkta otomatik açılmış PLANLI kayıt varsa, bu kayıtla eğitimi alınan kişiler oradan çıkarılır (mükerrer kalmasın).
        \App\Support\EgitimPlanlayici::uzlastir($this->firma, $this->baslikAnahtari);

        // İş koluna göre kayıt: işe özgü konular kütüphaneye o iş koluyla yazılır (sonraki formlara / diğer firmalara gelir).
        if (($eklenen = IseOzguEgitimKutuphanesi::isKolundanKaydet($kayit)) > 0) {
            Notification::make()->title($eklenen.' konu "'.$kayit->is_kolu.'" iş koluyla kütüphaneye kaydedildi')->success()->send();
        }

        // İşlenen temel/işe özgü konular yıllık eğitim planında o aya "tamamlandı" işlenir.
        EgitimKonulari::planiIsle($kayit);

        if ($uyari = $this->sureYasalUyarisi) {
            Notification::make()->title('Yasal asgari süre uyarısı (kayıt oluşturuldu)')->body($uyari)->warning()->send();
        }

        unset($this->gecmisKayitlar);

        return $kayit;
    }

    /** Çalışanların İSG Eğitimleri Yön. m.11: tehlike sınıfına göre temel eğitim asgari ders saati. */
    #[Computed]
    public function asgariDersSaati(): ?int
    {
        if ($this->baslikAnahtari !== 'genel' || ! $this->firma) {
            return null;
        }

        return match ($this->firma->tehlike_sinifi) {
            'cok_tehlikeli' => 16,
            'tehlikeli' => 12,
            default => 8,
        };
    }

    /** Temel eğitimde girilen saat yasal asgari sürenin altındaysa uyarı metni (kısmi oturum kaydı yine serbest). */
    #[Computed]
    public function sureYasalUyarisi(): ?string
    {
        $asgari = $this->asgariDersSaati;

        if (! $asgari || $this->dersSaati === null) {
            return null;
        }

        if ($this->egitimSekli === 'uzaktan' || $this->egitimSekli === 'karma') {
            $dagilim = YillikEgitimPlanlayici::bolumDagilimi($this->firma, 'karma_lms');
            $k = $this->egitimTuru === 'tekrar' ? 'tekrar' : 'ilk';
            $oturumAsgari = (int) round($this->egitimSekli === 'uzaktan' ? $dagilim["{$k}_online"] : $dagilim["{$k}_yuz_yuze"]);

            if ($oturumAsgari <= 0 || $this->dersSaati >= $oturumAsgari) {
                return null;
            }

            $oturumAd = $this->egitimSekli === 'uzaktan' ? 'uzaktan (online)' : 'yüz yüze tamamlama (karma)';

            return 'Çalışanların İSG Eğitimleri Yön. m.11 / RG 02.04.2026 uyarısı: '
                .$this->firma->tehlikeSinifiEtiketi()
                ." işyerinde {$oturumAd} oturumu en az {$oturumAsgari} saat olmalıdır (girilen: {$this->dersSaati} saat).";
        }

        if ($this->dersSaati >= $asgari) {
            return null;
        }

        return 'Çalışanların İSG Eğitimleri Yön. m.11 uyarısı: '
            .$this->firma->tehlikeSinifiEtiketi()
            ." işyerinde temel eğitim toplam en az {$asgari} saat olmalıdır (girilen: {$this->dersSaati} saat). "
            .'Kısmi / oturum bazlı eğitim tutanağı olarak kaydedebilirsiniz.';
    }

    #[Computed]
    public function iseBaslamaEksik()
    {
        return $this->firma ? \App\Support\CanliEgitimYonetici::iseBaslamaEksikKuyrugu($this->firma) : collect();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->kaydetAction(),
            $this->yeniKayitAction(),
            \App\Filament\Support\CanliImzaAksiyonu::make('egitim_katilim', fn () => $this->firma),

            Action::make('canliEgitimOturumu')
                ->label('🎥 Canlı Eğitim & Özel Eğitim (QR Sertifika)')
                ->icon('heroicon-o-video-camera')
                ->color('primary')
                ->outlined()
                ->visible(fn () => $this->firma !== null)
                ->modalHeading('Canlı Eğitim / Özel Eğitim Oturumu (≥70 Puan QR Sertifika)')
                ->modalDescription('Temel İSG, Yüksekte Çalışma (8 sa), İşyeri Hijyeni (4 sa) veya Gıda/Su Hijyeni (4 sa) oturumu açın. Katılımcılar sınav linkini tamamlayıp ≥70 puan aldığında QR doğrulamalı sertifikasını anında indirir.')
                ->modalSubmitActionLabel('Canlı Eğitim & Sınav Linki Oluştur')
                ->schema([
                    \Filament\Forms\Components\Select::make('kategori')
                        ->label('Eğitim Programı')
                        ->options(collect(\App\Models\CanliEgitimOturumu::KATEGORILER)->map(fn ($k) => $k['ad'])->all())
                        ->default('temel_isg')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('baslik')
                        ->label('Oturum Başlığı')
                        ->default('Canlı İSG Eğitim Oturumu — '.now()->format('d.m.Y'))
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('toplanti_url')
                        ->label('Zoom / Meet / Teams Linki (Opsiyonel)')
                        ->url()
                        ->placeholder('https://meet.google.com/...'),
                ])
                ->action(function (array $data): void {
                    $oturum = \App\Support\CanliEgitimYonetici::oturumOlustur(
                        Filament::auth()->user(),
                        $this->firma,
                        (string) $data['kategori'],
                        (string) $data['baslik'],
                        now()->toDateTimeString(),
                        $data['toplanti_url'] ?? null,
                    );
                    $url = route('canli-egitim.form', ['token' => $oturum->katilim_token]);

                    Notification::make()
                        ->title('Canlı Eğitim & Sınav Oturumu Açıldı')
                        ->body('Katılımcı sınav & QR sertifika bağlantısı: '.$url)
                        ->actions([
                            Action::make('oturumAc')->label('Sınav Ekranını Aç')->url($url, shouldOpenInNewTab: true)->button(),
                        ])
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Action::make('sgkPdfYukle')
                ->label('SGK İşe Giriş PDF\'inden Katılımcı Ekle')
                ->icon('heroicon-o-identification')
                ->color('success')
                ->visible(fn () => $this->firma !== null)
                ->modalHeading('SGK İşe Giriş Bildirgesi PDF\'inden Katılımcı Ekle')
                ->modalDescription('e-SGK Sigortalı İşe Giriş Bildirgesi PDF dosyasını (tekli veya çoklu) yükleyin; çalışan(lar) otomatik olarak firmaya kaydedilir ve bu eğitim katılım formunda seçili hale gelir.')
                ->modalSubmitActionLabel('PDF\'ten Aktar')
                ->schema([
                    FileUpload::make('dosyalar')
                        ->label('SGK İşe Giriş Bildirgesi PDF Dosyaları')
                        ->disk('local')
                        ->directory('sgk-ice-aktarim')
                        ->acceptedFileTypes(['application/pdf'])
                        ->multiple()
                        ->maxFiles(30)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $dosyalar = is_array($data['dosyalar'] ?? null) ? $data['dosyalar'] : array_filter([$data['dosyalar'] ?? null]);
                    $yollar = array_map(fn (string $d) => Storage::disk('local')->path($d), $dosyalar);

                    try {
                        $kayitlar = SgkIseGirisOkuyucu::dosyalardanCikar($yollar);
                    } finally {
                        foreach ($dosyalar as $d) {
                            Storage::disk('local')->delete($d);
                        }
                    }

                    if (! $kayitlar) {
                        Notification::make()->title('PDF dosyasından çalışan bilgisi okunamadı')->danger()->send();

                        return;
                    }

                    $sonuc = SgkIseGirisOkuyucu::firmayaKaydet($this->firma, $kayitlar);
                    unset($this->calisanlar, $this->gorunenCalisanlar);

                    $yeniIdler = $sonuc['calisanlar']->pluck('id')->all();
                    $this->secilenCalisanIdler = array_values(array_unique([...$this->secilenCalisanIdler, ...$yeniIdler]));

                    Notification::make()
                        ->title($sonuc['calisanlar']->count().' çalışan SGK PDF\'inden eklendi ve katılımcı olarak seçildi')
                        ->body($sonuc['calisanlar']->pluck('ad_soyad')->implode(', '))
                        ->success()->send();
                }),

            Action::make('sinavKagidi')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Eğitim Sınav Kâğıdı & Cevap Anahtarı — PDF')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalHeading('📝 İSG Eğitimi Değerlendirme Sınav Kâğıdı (10 Soru + Cevap Anahtarı)')
                ->modalDescription('Seçili katılımcıların her biri için ad/T.C. basılı 10 soruluk sınav kâğıdı (katılımcı seçilmemişse boş sınav kâğıdı) ve en sonda eğitici için Cevap Anahtarı sayfası üretilir.')
                ->modalSubmitActionLabel('Sınav PDF İndir')
                ->schema([
                    Select::make('sinav_turu')
                        ->label('Sınav Türü')
                        ->options([
                            'Son Test (Değerlendirme Sınavı)' => 'Son Test (Eğitim Sonu Değerlendirme Sınavı)',
                            'Ön Test (Seviye Tespit Sınavı)' => 'Ön Test (Eğitim Öncesi Seviye Tespit Sınavı)',
                        ])
                        ->default('Son Test (Değerlendirme Sınavı)')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $baslik = $this->basliklar[$this->baslikAnahtari] ?? 'İş Sağlığı ve Güvenliği Eğitimi';
                    $tarih = $this->belgeTarihi ? Carbon::parse($this->belgeTarihi)->format('d.m.Y') : now()->format('d.m.Y');

                    return EgitimSinavKagidiUretici::pdf(
                        $this->firma,
                        $baslik,
                        $tarih,
                        $this->katilimcilarTopla(),
                        (string) ($data['sinav_turu'] ?? 'Son Test (Değerlendirme Sınavı)'),
                    );
                }),

            // Seçili firma adlı BOŞ form (katılımcısız, elle doldurulacak): kayıt oluşturmaz, yıllık planı etkilemez.
            // Ekrandaki güncel konular ve güncel dakikalar BİREBİR basılır — silinen konular geri eklenmez.
            Action::make('bosFormFirma')
                ->label('Boş Form (PDF)')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->tooltip('Ekrandaki güncel konular ve dakikalarla boş, katılımcısız imza formunu indirir (silinen konular geri gelmez). Kayıt oluşturmaz.')
                ->action(function () {
                    $icerik = EgitimIcerikOlusturucu::bosKonulariAyikla($this->icerik);
                    $icerik['saat'] = $this->dersSaati ?: ($icerik['saat'] ?? null);

                    return EgitimKatilimUretici::bosFormPdf(
                        $this->baslikAnahtari,
                        $this->baslikAnahtari === 'genel' ? $this->sektorAnahtari : null,
                        $this->firma->tehlike_sinifi ?: 'az_tehlikeli',
                        $this->egitimTuru,
                        $this->firma,
                        $icerik,
                    );
                }),
            // Çıktı eylemleri başlıkta GİZLİ kalır (display:none); sayfadaki "Çıktı Al" menüsü (egitim-katilim.blade.php) bunları
            // mountAction ile açar. Filament'in açılır menüsü bazı ekranlarda sol üst köşede açıldığı için menü sayfaya sabitlendi.
            Action::make('pdf')
                ->extraAttributes(['style' => 'display:none'])
                ->label('Eğitim Katılım Formu — PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([
                    ImzaSecenegi::alan(),
                    \Filament\Forms\Components\Radio::make('dil')
                        ->label('Belge Dili')
                        ->options(['tr' => 'Türkçe (TR)', 'tr_en' => 'Çift Dilli (TR / EN)'])
                        ->default('tr')
                        ->inline(),
                ])
                ->action(function (array $data) {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no)->success()->send();

                    return EgitimKatilimUretici::pdf($kayit, ImzaSecenegi::secili($data), (string) ($data['dil'] ?? 'tr'));
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
