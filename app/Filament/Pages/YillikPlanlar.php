<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Models\IseOzguEgitimKonusu;
use App\Models\YillikPlan as YillikPlanModel;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKonulari;
use App\Support\GeminiIseOzguEgitimUretici;
use App\Support\IseOzguEgitimKutuphanesi;
use App\Support\IseOzguSenkron;
use App\Support\IsKalemiKutuphanesi;
use App\Support\YillikDegerlendirmeExcelUretici;
use App\Support\YillikDegerlendirmeFormUretici;
use App\Support\YillikDegerlendirmeVerisi;
use App\Support\YillikEgitimPlanlayici;
use App\Support\YillikPlanExcelIceAktarici;
use App\Support\YillikPlanExcelUretici;
use App\Support\YillikPlanGerceklesme;
use App\Support\YillikPlanSablonu;
use App\Support\YillikPlanTopluIndirme;
use App\Support\YillikPlanUretici;
use App\Support\YillikPlanUyarlayici;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Locked;
use Throwable;
use UnitEnum;

/**
 * Yıllık Planlar ortak tabanı — isgpratik 86-90.jpg. Kullanıcı isteğiyle
 * (01.10.2026) üç AYRI menü sayfası: Pages/YillikPlan/YillikCalismaPlani,
 * YillikEgitimPlani, YillikDegerlendirmeRaporu. Üçü aynı YillikPlan kaydını
 * (firma + yıl) düzenler; her sayfa yalnız kendi bölümünü gösterir ($planTuru).
 * Bu sınıf abstract — Filament keşfi atlar, rota üretmez.
 */
abstract class YillikPlanlar extends Page
{
    protected string $view = 'filament.pages.yillik-planlar';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    /** 'calisma' | 'egitim' | 'degerlendirme' — alt sınıf belirler. */
    protected static string $planTuru = 'calisma';

    /**
     * Üç sayfa da eski tek sayfanın yetki anahtarını ('yillik-planlar') kullanır;
     * verilmiş yetkiler bölünmeden geçerli kalır.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->aktif && $user->sayfaErisimiVarMi('yillik-planlar');
    }

    public const AYLAR = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

    public const DURUM_SIRASI = ['bos', 'planlandi', 'tamamlandi'];

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public int $yil;

    #[Locked]
    #[Url(as: 'sekme')]
    public string $sekme = 'calisma';

    public ?string $yeniFaaliyet = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniAciklama = null;

    public ?string $yeniAnaKonu = null;

    public ?string $yeniPeriyot = null;

    public ?string $yeniEgitimKonu = null;

    public ?string $yeniEgitimSure = null;

    /** Eğitim planı bölümü (config isg.yillik_plan.egitim_kategorileri) — Excel çıktısında satırın gideceği bölüm. */
    public string $yeniEgitimKategori = 'ise_ozgu';

    public ?string $yeniEgitimEgitici = null;

    public ?string $yeniEgitimHedefKitle = null;

    public string $yeniEgitimYontem = 'yuz_yuze';

    /** Online / Yüz Yüze dağıtım modu (YillikEgitimPlanlayici::MODLAR). */
    public string $egitimPlanModu = YillikEgitimPlanlayici::VARSAYILAN_MOD;

    /** Firmanın elle seçilmiş "Gerçek İş Grubu" (NACE'si yapılan işle tutmayan firmalar için; null = NACE otomatik). */
    public ?string $firmaEgitimGrubu = null;

    /** Firmanın işyerinde yaptığı seçili iş kalemleri (`firmalar.is_kalemleri` anahtarları). */
    public array $seciliIsKalemleri = [];

    /** İş kalemi seçicide diğer sektör gruplarını da göster/gizle. */
    public bool $tumIsKalemleriniGoster = false;

    /** Şantiye firması ilk açıldığında 'insaat_genel_tehlikeler' otomatik ön-seçildiyse true. */
    public bool $santiyeGenelOtomatikSecildi = false;

    public ?string $yeniDegerlendirmeCalisma = null;

    public function mount(): void
    {
        $this->sekme = static::$planTuru;
        $this->yil = (int) now()->format('Y');

        if ($firmaId = request()->integer('firma') ?: \App\Support\KomutaFirma::baslangic($this->firmalar)) {
            $this->firmaId = $firmaId;
        }

        // Diğer yıllık plan sayfasından geçişte aynı yıl açılsın.
        if ($yil = request()->integer('yil')) {
            $this->yil = $yil;
        }

        $this->degerlendirmeyiOtomatikGuncelle();
        $this->gerceklesmeleriIsle();
        $this->egitimPlanModunuSenkronla();
    }

    /** Çalışma planında sistem kayıtları, eğitim planında katılım + LMS tamamlanmaları G olarak işlenir. */
    protected function gerceklesmeleriIsle(): void
    {
        if (! ($p = $this->plan())) {
            return;
        }

        if ($this->sekme === 'calisma') {
            YillikPlanGerceklesme::isle($p);
            unset($this->plan);
        } elseif ($this->sekme === 'egitim') {
            if (EgitimKonulari::kayitlariPlanaIsle($p) > 0) {
                unset($this->plan);
            }
        }
    }

    protected function egitimPlanModunuSenkronla(): void
    {
        if ($this->sekme === 'egitim' && ($p = $this->plan())) {
            $this->egitimPlanModu = YillikEgitimPlanlayici::moduTespitEt($p->egitimler ?? [], $this->firma);
            $this->firmaEgitimGrubu = IseOzguSenkron::firmaGrubuOku($this->firma) ?? '';
            $secili = array_values(array_filter((array) ($this->firma?->is_kalemleri ?? [])));
            $this->santiyeGenelOtomatikSecildi = false;
            if ($secili === [] && IsKalemiKutuphanesi::santiyeFirmasiMi($this->firma)) {
                $secili = [IsKalemiKutuphanesi::SANTIYE_GENEL_ANAHTAR];
                $this->santiyeGenelOtomatikSecildi = true;
            }
            $this->seciliIsKalemleri = $secili;
        }
    }

    #[Computed]
    public function anaIsKalemiGrubu(): string
    {
        return IsKalemiKutuphanesi::anaGrup(null, $this->firma);
    }

    #[Computed]
    public function isKalemiGruplari(): array
    {
        $gruplar = IsKalemiKutuphanesi::gruplar();
        $ana = $this->anaIsKalemiGrubu;

        if (! isset($gruplar[$ana])) {
            return $gruplar;
        }

        $sirali = [$ana => $gruplar[$ana]];
        foreach ($gruplar as $ad => $kalemler) {
            if ($ad !== $ana) {
                $sirali[$ad] = $kalemler;
            }
        }

        return $sirali;
    }

    /** İşyerinde yapılan bir iş kalemini seç / kaldır. */
    public function isKalemiToggle(string $anahtar): void
    {
        $tumu = IsKalemiKutuphanesi::tumu();
        if (! $tumu->has($anahtar)) {
            return;
        }

        if ($anahtar === IsKalemiKutuphanesi::SANTIYE_GENEL_ANAHTAR) {
            $this->santiyeGenelOtomatikSecildi = false;
        } elseif ($this->santiyeGenelOtomatikSecildi && ($tumu->get($anahtar)['grup'] ?? null) !== 'İnşaat / Şantiye') {
            // NACE'si inşaat ama gerçek işi fabrika/hizmet olan firmada başka sektör kalemi seçilince
            // otomatik ön-seçili gelen şantiye genel başlığını temizle.
            $this->seciliIsKalemleri = array_values(array_diff($this->seciliIsKalemleri, [IsKalemiKutuphanesi::SANTIYE_GENEL_ANAHTAR]));
            $this->santiyeGenelOtomatikSecildi = false;
        }

        $this->seciliIsKalemleri = in_array($anahtar, $this->seciliIsKalemleri, true)
            ? array_values(array_diff($this->seciliIsKalemleri, [$anahtar]))
            : [...$this->seciliIsKalemleri, $anahtar];
    }

    /**
     * Seçili iş kalemlerini firmanın kaydına (`firmalar.is_kalemleri`) kaydeder ve
     * Yıllık Eğitim Planı 4. Bölümünü yalnız bu iş kalemlerinin makine/ekipman konularıyla kurar.
     * Mevcut tamamlanmış (G) aylar korunur; 4. bölüm saatleri yasal saate göre otomatik dağıtılır.
     */
    public function isKalemleriniPlanaUygula(): void
    {
        $p = $this->plan();
        if (! $p || ! $this->firma) {
            return;
        }

        $tumu = IsKalemiKutuphanesi::tumu();
        $gecerli = array_values(array_unique(array_filter(
            $this->seciliIsKalemleri,
            fn ($a) => is_string($a) && $tumu->has($a),
        )));
        $gecerli = IsKalemiKutuphanesi::santiyeBasliginiEkle($gecerli);
        $this->seciliIsKalemleri = $gecerli;

        $this->firma->update(['is_kalemleri' => $gecerli ?: null]);
        unset($this->firma);

        if ($gecerli === []) {
            Notification::make()
                ->title('Firmanın iş kalemleri temizlendi')
                ->body('Mevcut 4. bölüm satırları korundu; yeni iş kalemi seçip tekrar uygulayabilirsiniz.')
                ->info()
                ->send();

            return;
        }

        // Seçilen iş kalemlerinden biri firmanın NACE ana grubundan farklıysa firmayı otomatik yalıt
        // (diğer aynı NACE'li firmaların şablonlarını etkilemez).
        $naceSektor = EgitimIcerikOlusturucu::naceSektoru($this->firma?->nace_kodu);
        $farkliGrupVar = false;
        if (filled($naceSektor)) {
            $naceAnaGrup = IsKalemiKutuphanesi::anaGrup($naceSektor);
            $farkliGrupVar = collect($gecerli)->contains(fn (string $a) => ($tumu[$a]['grup'] ?? null) !== $naceAnaGrup);
            if ($farkliGrupVar && $this->firma) {
                IseOzguSenkron::firmaOzelKaydet($this->firma, true);
                unset($this->firmaEgitimOzelMi, $this->ayniIsGrubuFirmaSayisi);
            }
        }

        $satirlar = IsKalemiKutuphanesi::planSatirlari($gecerli, $this->kilitAyIndeksi(), $p->egitimler ?? []);
        $yeniPlan = YillikPlanSablonu::iseOzguYerlestir($p->egitimler ?? [], $satirlar);
        $yeniPlan = YillikEgitimPlanlayici::satirlaraUygula($yeniPlan, $this->firma, $this->egitimPlanModu);

        $p->update(['egitimler' => $yeniPlan]);
        unset($this->plan);

        Notification::make()
            ->title(count($satirlar).' iş kalemi 4. bölüme uygulandı')
            ->body('Seçili iş kalemleri firmaya kaydedildi; Eğitim Katılım Formu ekip butonları da bu kalemlerden beslenir.'
                .($farkliGrupVar ? ' (NACE dışı iş kalemi seçildiği için firma yalnız kendine özel olarak yalıtıldı.)' : ''))
            ->success()
            ->send();
    }

    #[Computed]
    public function egitimSektorSecenekleri(): array
    {
        return EgitimIcerikOlusturucu::sektorler();
    }

    #[Computed]
    public function ayniIsGrubuFirmaSayisi(): int
    {
        return $this->firma ? IseOzguSenkron::ayniGrubunFirmalari($this->firma)->count() : 0;
    }

    #[Computed]
    public function firmaEgitimOzelMi(): bool
    {
        return IseOzguSenkron::firmaOzelMi($this->firma);
    }

    /** Firmayı "yalnız bu firmaya özel" yalıtımına alır veya tekrar sektör grubuna dahil eder. */
    public function firmaOzelDurumuDegistir(bool $ozel): void
    {
        if (! $this->firma) {
            return;
        }

        IseOzguSenkron::firmaOzelKaydet($this->firma, $ozel);
        unset($this->firmaEgitimOzelMi, $this->ayniIsGrubuFirmaSayisi);

        Notification::make()
            ->title($ozel ? 'Firma yalnız kendine özel olarak yalıtıldı' : 'Firma tekrar iş grubu eşitlemesine açıldı')
            ->body($ozel
                ? 'Bu firmaya eklenen NACE dışı konular yalnız bu firmada kalır; diğer inşaat/NACE şablonlarını bozmaz ve grup eşitlemede ezilmez.'
                : 'Artık bu firma etkin iş grubundaki firmalarla eşitlenebilir.')
            ->success()
            ->send();
    }

    /**
     * Değerlendirme sayfası açılınca / firma-yıl değişince satırlar sistem
     * kayıtlarından yenilenir (elle düzenlenen satırlar korunur).
     */
    protected function degerlendirmeyiOtomatikGuncelle(): void
    {
        if ($this->sekme !== 'degerlendirme' || ! ($p = $this->plan())) {
            return;
        }

        $yeni = YillikDegerlendirmeVerisi::planiDoldur($this->firma, $this->yil, $p->degerlendirmeler ?? [])['satirlar'];

        if ($yeni !== $p->degerlendirmeler) {
            $p->update(['degerlendirmeler' => $yeni]);
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

    #[Computed]
    public function plan(): ?YillikPlanModel
    {
        return $this->firma ? YillikPlanModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
        $this->tumIsKalemleriniGoster = false;
        unset($this->firma, $this->plan, $this->firmaEgitimOzelMi, $this->ayniIsGrubuFirmaSayisi, $this->anaIsKalemiGrubu, $this->isKalemiGruplari);
        $this->degerlendirmeyiOtomatikGuncelle();
        $this->gerceklesmeleriIsle();
        $this->egitimPlanModunuSenkronla();
    }

    public function updatedYil(): void
    {
        unset($this->plan);
        $this->degerlendirmeyiOtomatikGuncelle();
        $this->gerceklesmeleriIsle();
        $this->egitimPlanModunuSenkronla();
    }

    /** Atanmış uzman (sözleşme başlangıcı) öncesi ay indeksi — bu aylar kilitli. */
    #[Computed]
    public function kilitAyIndeksi(): int
    {
        return $this->firma?->planKilitAyIndeksi($this->yil) ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Ay durum matrisi (Çalışma Planı + Eğitim Planı ortak) — $alan: 'faaliyetler'|'egitimler'
    |--------------------------------------------------------------------------
    */

    public function ayDurumDegistir(string $alan, int $index, int $ayIndex): void
    {
        $p = $this->plan();
        $satirlar = $p?->{$alan} ?? [];

        if (! $p || ! isset($satirlar[$index])) {
            return;
        }

        // Atanmış uzman öncesindeki aylar seçilemez.
        if ($ayIndex < $this->kilitAyIndeksi()) {
            Notification::make()
                ->title('Bu ay seçilemez')
                ->body('Firma sözleşme başlangıcından (atanmış uzman tarihi) önceki aylar için plan işaretlenemez.')
                ->warning()
                ->send();

            return;
        }

        $mevcut = $satirlar[$index]['aylar'][$ayIndex] ?? 'bos';
        $siraIndex = array_search($mevcut, self::DURUM_SIRASI, true);
        $yeni = self::DURUM_SIRASI[($siraIndex + 1) % count(self::DURUM_SIRASI)];

        $satirlar[$index]['aylar'][$ayIndex] = $yeni;
        $p->update([$alan => $satirlar]);
    }

    /** Çalışma planı tablosundaki P (Planlandı) / G (Gerçekleşti) hücresine tıklama. */
    public function ayHucresiDegistir(string $alan, int $index, int $ayIndex, string $hucre): void
    {
        $p = $this->plan();
        $satirlar = $p?->{$alan} ?? [];

        if (! $p || ! isset($satirlar[$index]) || ! in_array($hucre, ['P', 'G'], true)) {
            return;
        }

        if ($ayIndex < $this->kilitAyIndeksi()) {
            Notification::make()
                ->title('Bu ay seçilemez')
                ->body('Firma sözleşme başlangıcından (atanmış uzman tarihi) önceki aylar için plan işaretlenemez.')
                ->warning()
                ->send();

            return;
        }

        $satirlar[$index]['aylar'][$ayIndex] = YillikPlanModel::hucreDurumu($satirlar[$index]['aylar'][$ayIndex] ?? 'bos', $hucre);
        $p->update([$alan => $satirlar]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Çalışma Planı
    |--------------------------------------------------------------------------
    */

    public function faaliyetEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniFaaliyet)) {
            return;
        }

        $faaliyetler = $p->faaliyetler ?? [];
        $faaliyetler[] = [
            'ana_konu' => filled($this->yeniAnaKonu) ? $this->yeniAnaKonu : 'DİĞER',
            'faaliyet' => $this->yeniFaaliyet,
            'frekans' => $this->yeniPeriyot,
            'sorumlu' => $this->yeniSorumlu,
            'yasal_gereklilik' => $this->yeniAciklama,
            'aylar' => array_fill(0, 12, 'bos'),
        ];
        $p->update(['faaliyetler' => $faaliyetler]);

        $this->reset('yeniFaaliyet', 'yeniSorumlu', 'yeniAciklama', 'yeniAnaKonu', 'yeniPeriyot');
    }

    public function faaliyetSil(int $index): void
    {
        $p = $this->plan();
        $faaliyetler = $p?->faaliyetler ?? [];

        if (! $p || ! isset($faaliyetler[$index])) {
            return;
        }

        unset($faaliyetler[$index]);
        $p->update(['faaliyetler' => array_values($faaliyetler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Eğitim Planı
    |--------------------------------------------------------------------------
    */

    public function egitimEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniEgitimKonu)) {
            return;
        }

        $kategori = array_key_exists($this->yeniEgitimKategori, config('isg.yillik_plan.egitim_kategorileri'))
            ? $this->yeniEgitimKategori : 'genel';
        $yontem = array_key_exists($this->yeniEgitimYontem, YillikEgitimPlanlayici::YONTEMLER)
            ? $this->yeniEgitimYontem : 'yuz_yuze';

        if (YillikEgitimPlanlayici::zorunluYuzYuzeMi(['kategori' => $kategori], $this->firma)) {
            $yontem = 'yuz_yuze';
        }

        $sure = is_numeric($this->yeniEgitimSure) && (float) $this->yeniEgitimSure > 0
            ? round((float) $this->yeniEgitimSure, 2)
            : null;
        $onSaat = $sure !== null ? ($yontem === 'uzaktan' ? $sure : ($yontem === 'karma' ? round($sure / 2, 2) : 0.0)) : 0.0;
        $yySaat = $sure !== null ? round($sure - $onSaat, 2) : 0.0;

        $egitimler = $p->egitimler ?? [];
        $egitimler[] = [
            'konu' => $this->yeniEgitimKonu,
            'kategori' => $kategori,
            'yontem' => $yontem,
            'plan_modu' => $this->egitimPlanModu,
            'hafta' => 2,
            'sure_saat' => $sure,
            'online_saat' => $onSaat,
            'yuz_yuze_saat' => $yySaat,
            'egitici' => $this->yeniEgitimEgitici,
            'hedef' => null,
            'hedef_kitle' => $this->yeniEgitimHedefKitle,
            'firma_ozel' => $kategori === 'ise_ozgu',
            'aylar' => array_fill(0, 12, 'bos'),
        ];

        // 4 temel bölümden birine konu eklendiğinde tüm bölüm saatleri toplam eğitim süresine göre otomatik güncellenir.
        if ($kategori !== 'diger') {
            $egitimler = YillikEgitimPlanlayici::satirlaraUygula($egitimler, $this->firma, $this->egitimPlanModu);
        }

        $p->update(['egitimler' => $egitimler]);
        unset($this->plan);

        $this->reset('yeniEgitimKonu', 'yeniEgitimSure', 'yeniEgitimEgitici', 'yeniEgitimHedefKitle');
    }

    /** Seçili moda göre tüm satırların Online / Yüz Yüze yöntemini ve yasal saatlerini otomatik dağıtır. */
    public function egitimModuUygula(?string $mod = null): void
    {
        $p = $this->plan();
        if (! $p) {
            return;
        }

        $hedefMod = $mod && array_key_exists($mod, YillikEgitimPlanlayici::MODLAR)
            ? $mod
            : $this->egitimPlanModu;
        $this->egitimPlanModu = $hedefMod;

        $yeni = YillikEgitimPlanlayici::satirlaraUygula($p->egitimler ?? [], $this->firma, $hedefMod);
        $p->update(['egitimler' => $yeni]);
        unset($this->plan);

        $ozet = YillikEgitimPlanlayici::ozet($yeni, $this->firma);

        Notification::make()
            ->title('Yıllık eğitim planı güncellendi')
            ->body(sprintf(
                '%s (%s): %s Online + %s Yüz Yüze = %s',
                $ozet['tehlike_etiketi'],
                YillikEgitimPlanlayici::MODLAR[$hedefMod],
                YillikEgitimPlanlayici::saatMetni($ozet['ilk_online']),
                YillikEgitimPlanlayici::saatMetni($ozet['ilk_yuz_yuze']),
                YillikEgitimPlanlayici::saatMetni($ozet['ilk_toplam']),
            ))
            ->success()
            ->send();
    }

    /** Tek bir eğitim satırının yöntemini Online ↔ Yüz Yüze ↔ Karma olarak değiştirir (mevzuat kilidiyle). */
    public function egitimYontemDegistir(int $index): void
    {
        $p = $this->plan();
        $egitimler = $p?->egitimler ?? [];

        if (! $p || ! isset($egitimler[$index])) {
            return;
        }

        $satir = $egitimler[$index];
        if (YillikEgitimPlanlayici::zorunluYuzYuzeMi($satir, $this->firma)) {
            Notification::make()
                ->title('Bu konu yüz yüze zorunludur')
                ->body('RG 02.04.2026 (33212) Md.12/3 ve Md.7 gereği bu bölümdeki eğitimler online (uzaktan) verilemez.')
                ->warning()
                ->send();

            return;
        }

        $sira = ['uzaktan', 'yuz_yuze', 'karma'];
        $mevcut = $satir['yontem'] ?? 'yuz_yuze';
        $yeniYontem = $sira[((int) array_search($mevcut, $sira, true) + 1) % count($sira)];

        $toplam = (float) ($satir['sure_saat'] ?? ((float) ($satir['online_saat'] ?? 0) + (float) ($satir['yuz_yuze_saat'] ?? 0)));
        if ($toplam <= 0) {
            $toplam = 0.5;
        }

        $egitimler[$index]['yontem'] = $yontem = $yeniYontem;
        $egitimler[$index]['sure_saat'] = round($toplam, 2);
        if ($yontem === 'uzaktan') {
            $egitimler[$index]['online_saat'] = round($toplam, 2);
            $egitimler[$index]['yuz_yuze_saat'] = 0.0;
        } elseif ($yontem === 'yuz_yuze') {
            $egitimler[$index]['online_saat'] = 0.0;
            $egitimler[$index]['yuz_yuze_saat'] = round($toplam, 2);
        } else {
            $on = round($toplam / 2, 2);
            $egitimler[$index]['online_saat'] = $on;
            $egitimler[$index]['yuz_yuze_saat'] = round($toplam - $on, 2);
        }

        $p->update(['egitimler' => $egitimler]);
        unset($this->plan);
    }

    public function egitimSil(int $index): void
    {
        $p = $this->plan();
        $egitimler = $p?->egitimler ?? [];

        if (! $p || ! isset($egitimler[$index])) {
            return;
        }

        $silinenKat = $egitimler[$index]['kategori'] ?? 'diger';
        unset($egitimler[$index]);
        $egitimler = array_values($egitimler);

        if ($silinenKat !== 'diger') {
            $egitimler = YillikEgitimPlanlayici::satirlaraUygula($egitimler, $this->firma, $this->egitimPlanModu);
        }

        $p->update(['egitimler' => $egitimler]);
        unset($this->plan);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Değerlendirme Raporu (ay matrisi yok, satır bazlı serbest metin)
    |--------------------------------------------------------------------------
    */

    public function degerlendirmeGuncelle(int $index, string $alan, string $deger): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index]) || ! in_array($alan, ['tarih', 'yapan_kisi', 'tekrar_sayisi', 'yontem', 'sonuc'], true)) {
            return;
        }

        $degerlendirmeler[$index][$alan] = $deger;
        // Elle değiştirilen satır artık otomatik yenilemede ezilmez.
        $degerlendirmeler[$index]['elle'] = true;
        $p->update(['degerlendirmeler' => $degerlendirmeler]);
    }

    /** Elle düzenlenen satırı tekrar sistem kayıtlarından doldurur. */
    public function degerlendirmeOtomatigeDondur(int $index): void
    {
        $p = $this->plan();
        $satirlar = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($satirlar[$index])) {
            return;
        }

        $anahtar = $satirlar[$index]['anahtar'] ?? null;
        $sablon = collect(YillikDegerlendirmeVerisi::sablonSatirlari())->firstWhere('anahtar', $anahtar);
        $satirlar[$index] = $sablon ?? [...$satirlar[$index], 'elle' => false];
        $p->update(['degerlendirmeler' => YillikDegerlendirmeVerisi::planiDoldur($this->firma, $this->yil, $satirlar)['satirlar']]);
    }

    public function degerlendirmeEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniDegerlendirmeCalisma)) {
            return;
        }

        $degerlendirmeler = $p->degerlendirmeler ?? [];
        // Ek çalışma satırı "Genel sonuç" bölümünden önce girer.
        $konum = collect($degerlendirmeler)->search(fn ($d) => ($d['tur'] ?? 'calisma') === 'genel');
        array_splice($degerlendirmeler, $konum === false ? count($degerlendirmeler) : $konum, 0, [[
            'tur' => 'calisma', 'anahtar' => 'ek_'.uniqid(), 'calisma' => $this->yeniDegerlendirmeCalisma,
            'yapan_kisi' => null, 'yontem' => null, 'sonuc' => null, 'tarih' => null, 'otomatik' => false, 'elle' => true,
        ]]);
        $p->update(['degerlendirmeler' => $degerlendirmeler]);

        $this->reset('yeniDegerlendirmeCalisma');
    }

    public function degerlendirmeSil(int $index): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index])) {
            return;
        }

        unset($degerlendirmeler[$index]);
        $p->update(['degerlendirmeler' => array_values($degerlendirmeler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Ortak
    |--------------------------------------------------------------------------
    */

    public function varsayilanaSifirla(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        // Standart şablon; 4. eğitim bölümü yalnız inşaat firmalarında.
        $icerik = YillikPlanSablonu::icerik($this->firma, $this->yil);

        match ($this->sekme) {
            'egitim' => $p->update(['egitimler' => $icerik['egitimler']]),
            'degerlendirme' => $p->update([
                'degerlendirmeler' => YillikDegerlendirmeVerisi::planiDoldur($this->firma, $this->yil, [])['satirlar'],
            ]),
            default => $p->update(['faaliyetler' => $icerik['faaliyetler']]),
        };

        Notification::make()->title('Plan varsayılan içeriğe sıfırlandı (otomatik dolduruldu)')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | İşe özgü konular (4. bölüm) — kütüphaneden seçim / kütüphaneye kayıt
    |--------------------------------------------------------------------------
    */

    /** Plandaki 4. bölüm satırlarının kütüphane anahtarları (eski satırlar ada göre eşlenir). */
    private function plandakiIseOzguAnahtarlari(): array
    {
        $tumu = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id());
        $adaGore = $tumu->mapWithKeys(fn ($k, $a) => [mb_strtolower(trim($k['ad'])) => $a]);

        return collect($this->plan()?->egitimler ?? [])->where('kategori', 'ise_ozgu')
            ->map(fn ($e) => ($e['kutuphane_anahtari'] ?? null) && $tumu->has($e['kutuphane_anahtari'])
                ? $e['kutuphane_anahtari']
                : ($adaGore[mb_strtolower(trim((string) ($e['konu'] ?? '')))] ?? null))
            ->filter()->unique()->values()->all();
    }

    public function iseOzguSecAction(): Action
    {
        return Action::make('iseOzguSec')
            ->label('İşe Özgü Konuları Seç')
            ->icon('heroicon-o-queue-list')
            ->modalHeading('İşe ve İşyerine Özgü Riskler — konu seçimi')
            ->modalDescription(fn () => $this->firma
                ? 'Firmanın '.(filled($this->firma->is_kalemleri) ? 'iş kalemlerine' : 'NACE koduna ('.($this->firma->nace_kodu ?: 'girilmemiş').')').' göre önerilenler ★ ile işaretli. Seçilen konular planın 4. bölümüne ve oradan Eğitim Katılım formunun işyerine özgü bölümüne girer.'
                : null)
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitActionLabel('Plana uygula')
            ->fillForm(function (): array {
                $mevcut = $this->plandakiIseOzguAnahtarlari();

                return ['secilenler' => $mevcut ?: IseOzguEgitimKutuphanesi::firmaIcin($this->firma)->keys()->all()];
            })
            ->schema(function (): array {
                $onerilen = $this->firma ? IseOzguEgitimKutuphanesi::firmaIcin($this->firma)->keys()->all() : [];
                $tumu = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id())
                    ->sortBy(fn ($k, $a) => [in_array($a, $onerilen, true) ? 0 : 1, $k['kaynak'], $k['ad']]);

                return [
                    CheckboxList::make('secilenler')
                        ->label('Konular')
                        ->options($tumu->map(fn ($k, $a) => (in_array($a, $onerilen, true) ? '★ ' : '').$k['ad'])->all())
                        ->descriptions($tumu->map(fn ($k) => $k['kaynak']
                            .($k['nace'] ? ' · NACE '.IseOzguEgitimKutuphanesi::naceGoster($k['nace']) : '')
                            .' — '.Str::limit((string) $k['hedef'], 90))->all())
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(2),
                ];
            })
            ->action(function (array $data): void {
                $this->iseOzguUygula($data['secilenler'] ?? []);
            });
    }

    /**
     * 4. bölümü seçilen kütüphane konularıyla günceller: seçili kalanların
     * işaretli ayları korunur, seçimden çıkarılan kütüphane konuları silinir,
     * kütüphanede olmayan (elle yazılmış) satırlar olduğu gibi kalır.
     */
    public function iseOzguUygula(array $secilenler): void
    {
        $p = $this->plan();

        if (! $p || ! $this->firma) {
            return;
        }

        $tumu = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id());
        $adaGore = $tumu->mapWithKeys(fn ($k, $a) => [mb_strtolower(trim($k['ad'])) => $a]);
        $kilit = $this->kilitAyIndeksi();

        $mevcutlar = collect($p->egitimler ?? [])->where('kategori', 'ise_ozgu')->values()->map(function ($e) use ($tumu, $adaGore) {
            $a = $e['kutuphane_anahtari'] ?? null;
            $e['_anahtar'] = $a && $tumu->has($a) ? $a : ($adaGore[mb_strtolower(trim((string) ($e['konu'] ?? '')))] ?? null);

            return $e;
        });

        $yeni = $mevcutlar->filter(fn ($e) => $e['_anahtar'] === null || in_array($e['_anahtar'], $secilenler, true));
        $varOlan = $yeni->pluck('_anahtar')->filter()->all();

        foreach ($secilenler as $a) {
            if ($tumu->has($a) && ! in_array($a, $varOlan, true)) {
                $yeni->push(IseOzguEgitimKutuphanesi::planSatiri($tumu[$a], $kilit));
            }
        }

        $satirlar = $yeni->map(function ($e) {
            if (array_key_exists('_anahtar', $e)) {
                $e['kutuphane_anahtari'] = $e['_anahtar'] ?? ($e['kutuphane_anahtari'] ?? null);
                unset($e['_anahtar']);
            }

            return $e;
        })->values()->all();

        $yeniPlan = YillikPlanSablonu::iseOzguYerlestir($p->egitimler ?? [], $satirlar);
        $yeniPlan = YillikEgitimPlanlayici::satirlaraUygula($yeniPlan, $this->firma, $this->egitimPlanModu);
        $p->update(['egitimler' => $yeniPlan]);
        unset($this->plan);

        Notification::make()->title('İşe özgü konular güncellendi')->body(count($satirlar).' konu planın 4. bölümünde.')->success()->send();
    }

    /**
     * NACE'si gerçek işiyle tutmayan firma için elle "Gerçek İş Grubu" kaydeder;
     * $konulariYukle = true ise o grubun standart 4. bölüm konularını bu firmanın planına uygular (tamamlanan aylar korunur).
     */
    public function firmaEgitimGrubunuUygula(bool $konulariYukle = false): void
    {
        $p = $this->plan();
        if (! $p || ! $this->firma) {
            return;
        }

        $secili = filled($this->firmaEgitimGrubu) ? (string) $this->firmaEgitimGrubu : null;
        IseOzguSenkron::firmaGrubuKaydet($this->firma, $secili);
        unset($this->ayniIsGrubuFirmaSayisi);

        $etkin = IseOzguSenkron::etkinGrup($this->firma);

        if ($konulariYukle && $etkin) {
            $sablon = EgitimIcerikOlusturucu::olustur('genel', $etkin, $this->firma->tehlike_sinifi ?: 'tehlikeli', 'ilk');
            $maddeler = collect($sablon['isyerine_ozgu']['maddeler'] ?? [])
                ->pluck('madde')->map(fn ($m) => trim((string) $m))->filter()->unique()->values();

            if ($maddeler->isNotEmpty()) {
                $kilit = $this->kilitAyIndeksi();
                $mevcutlar = collect($p->egitimler ?? [])->where('kategori', 'ise_ozgu')
                    ->keyBy(fn ($e) => EgitimKonulari::normalize((string) ($e['konu'] ?? '')));

                $satirlar = $maddeler->map(function (string $konu) use ($mevcutlar, $kilit): array {
                    $norm = EgitimKonulari::normalize($konu);
                    if ($mevcutlar->has($norm)) {
                        return $mevcutlar[$norm];
                    }

                    return YillikPlanModel::maddeAylarIle([[
                        'konu' => $konu,
                        'kategori' => 'ise_ozgu',
                        'hedef' => '',
                        'egitici' => IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                        'hafta' => 2,
                        'varsayilan_aylar' => range(0, 11),
                    ]], $kilit)[0];
                })->all();

                $yeniPlan = YillikPlanSablonu::iseOzguYerlestir($p->egitimler ?? [], $satirlar);
                $yeniPlan = YillikEgitimPlanlayici::satirlaraUygula($yeniPlan, $this->firma, $this->egitimPlanModu);
                $p->update(['egitimler' => $yeniPlan]);
                unset($this->plan);
            }
        }

        $sektorAd = $etkin ? (EgitimIcerikOlusturucu::sektorler()[$etkin] ?? $etkin) : 'NACE Otomatik';
        Notification::make()
            ->title($konulariYukle ? '4. bölüm gerçek iş grubuna göre güncellendi' : 'Gerçek iş grubu kaydedildi')
            ->body('Etkin grup: '.$sektorAd.'. Eğitim Katılım Formu da aynı grubu kullanır.')
            ->success()
            ->send();
    }

    /** Bu firmanın 4. bölüm ("İşe ve İşyerine Özgü Riskler") konularını aynı iş grubundaki tüm firmalara tek tıkla eşitler. */
    public function dorduncuBolumuGrubaEsitle(): void
    {
        if (! $this->firma) {
            return;
        }

        $adet = IseOzguSenkron::dorduncuBolumuGrubaEsitle($this->firma, $this->yil);

        if ($adet === 0) {
            Notification::make()
                ->title('Eşitlenecek başka firma bulunamadı')
                ->body('Aynı NACE veya aynı "Gerçek İş Grubu"na sahip başka aktif firmanız yok.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($adet.' firmanın 4. bölümü eşitlendi')
            ->body('Aynı iş grubundaki firmaların '.$this->yil.' Yıllık Eğitim Planı 4. bölümleri güncellendi (tamamlanmış G ayları ve tehlike sınıfı saatleri korundu).')
            ->success()
            ->send();
    }

    /** Gemini API ile firmanın sektörü/NACE/ekibine uygun 4. bölüm konularını üretip plana ekler ve saatleri günceller. */
    public function aiIseOzguUretAction(): Action
    {
        return Action::make('aiIseOzguUret')
            ->label('✨ AI ile 4. Bölüm Üret')
            ->modalHeading('✨ Yapay Zekâ (API) ile Bu Firmanın 4. Bölümünü Planla')
            ->modalDescription('Firmanın sektörüne/NACE koduna ve ekibine özel 4. Bölüm konularını üretir; toplam eğitim süresine göre satır saatlerini sıfırdan yeniden dağıtır. Eğitim Katılım, Boş Form ve Sertifikalar doğrudan bu plandan beslenir.')
            ->modalSubmitActionLabel('Üret ve Plana Uygula')
            ->fillForm(fn () => [
                'sektor' => ($this->firma ? IseOzguSenkron::etkinGrup($this->firma) : null) ?: 'insaat',
                'adet' => 5,
                'ekleme_sekli' => 'degistir',
                'kutuphaneye_kaydet' => false,
            ])
            ->schema([
                \Filament\Forms\Components\Select::make('sektor')
                    ->label('Sektör / İş Grubu')
                    ->options(GeminiIseOzguEgitimUretici::sektorSecenekleri())
                    ->searchable()
                    ->required(),
                \Filament\Forms\Components\TextInput::make('hedef_kitle')
                    ->label('Ekip / Çalışan Grubu Etiketi (Opsiyonel)')
                    ->placeholder('Örn: Kalıp Ekibi, Sıva Ekibi, Kaynak Atölyesi'),
                \Filament\Forms\Components\Textarea::make('ek_baglam')
                    ->label('Ek Saha / Ekipman Bilgisi (Opsiyonel)')
                    ->rows(2)
                    ->placeholder('Örn: CNC tezgâhları, tavan vinci, solvent bazlı boya'),
                Grid::make(2)->schema([
                    \Filament\Forms\Components\Select::make('adet')
                        ->label('Konu Sayısı')
                        ->options([3 => '3 konu', 4 => '4 konu', 5 => '5 konu', 6 => '6 konu', 8 => '8 konu'])
                        ->default(5)
                        ->required(),
                    Radio::make('ekleme_sekli')
                        ->label('Uygulama Şekli')
                        ->options([
                            'degistir' => '4. bölümü yenileriyle değiştir (önerilen — eski/yeni karışmaz)',
                            'ekle' => 'Mevcut 4. bölümün üzerine ekle',
                        ])
                        ->default('degistir')
                        ->required(),
                ]),
                Toggle::make('kutuphaneye_kaydet')
                    ->label('İşe Özgü Eğitim Kütüphanesi\'ne de kaydet (kapalıysa yalnız bu firmanın planında kalır)')
                    ->default(false),
            ])
            ->action(function (array $data): void {
                $p = $this->plan();
                if (! $p || ! $this->firma) {
                    return;
                }

                $sektorAnahtar = (string) ($data['sektor'] ?? '');
                $sektorler = GeminiIseOzguEgitimUretici::sektorSecenekleri();
                $sektorAd = $sektorler[$sektorAnahtar] ?? $sektorAnahtar;
                $hedefKitle = filled($data['hedef_kitle'] ?? null) ? trim((string) $data['hedef_kitle']) : null;
                $ekBaglam = filled($data['ek_baglam'] ?? null) ? trim((string) $data['ek_baglam']) : null;
                $adet = (int) ($data['adet'] ?? 5);

                $sonuc = GeminiIseOzguEgitimUretici::uret(
                    $sektorAd,
                    $this->firma->nace_kodu ?: GeminiIseOzguEgitimUretici::sektorNaceOnekleri($sektorAnahtar),
                    $this->firma->tehlike_sinifi,
                    $hedefKitle,
                    $ekBaglam,
                    $adet,
                );

                if ($sonuc['hata'] !== null) {
                    Notification::make()->title('Konu üretilemedi')->body($sonuc['hata'])->danger()->send();

                    return;
                }

                $kilit = $this->kilitAyIndeksi();
                // Mevcut G (tamamlandi) aylarını konu adına göre koru
                $mevcutG = [];
                foreach (collect($p->egitimler ?? [])->where('kategori', 'ise_ozgu') as $m) {
                    $norm = EgitimKonulari::normalize((string) ($m['konu'] ?? ''));
                    foreach (($m['aylar'] ?? []) as $ay => $durum) {
                        if ($durum === 'tamamlandi') {
                            $mevcutG[$norm][$ay] = true;
                        }
                    }
                }

                $mevcutlar = ($data['ekleme_sekli'] ?? 'degistir') === 'degistir'
                    ? collect()
                    : collect($p->egitimler ?? [])->where('kategori', 'ise_ozgu')->values();

                $mevcutAdlar = $mevcutlar->map(fn ($e) => EgitimKonulari::normalize((string) ($e['konu'] ?? '')))->flip();

                $naceSektor = EgitimIcerikOlusturucu::naceSektoru($this->firma->nace_kodu);
                $firmaOzelMi = ($naceSektor !== null && $sektorAnahtar !== $naceSektor) || filled($ekBaglam);

                foreach ($sonuc['konular'] as $k) {
                    $norm = EgitimKonulari::normalize($k['ad']);
                    if ($mevcutAdlar->has($norm)) {
                        continue;
                    }

                    $satir = YillikPlanModel::maddeAylarIle([[
                        'konu' => $k['ad'],
                        'kategori' => 'ise_ozgu',
                        'hedef' => $k['hedef'],
                        'hedef_kitle' => $hedefKitle,
                        'egitici' => $k['egitici'],
                        'firma_ozel' => $firmaOzelMi,
                        'hafta' => 2,
                        'varsayilan_aylar' => range(0, 11),
                    ]], $kilit)[0];

                    foreach (($mevcutG[$norm] ?? []) as $ay => $_) {
                        $satir['aylar'][$ay] = 'tamamlandi';
                    }

                    $mevcutlar->push($satir);
                    $mevcutAdlar->put($norm, true);
                }

                $yeniPlan = YillikPlanSablonu::iseOzguYerlestir($p->egitimler ?? [], $mevcutlar->all());
                $yeniPlan = YillikEgitimPlanlayici::satirlaraUygula($yeniPlan, $this->firma, $this->egitimPlanModu);
                $p->update(['egitimler' => $yeniPlan]);

                // Firmanın NACE'si (ör. İnşaat) olmasına rağmen farklı sektör / özel bağlam üretildiyse
                // yalnız bu firmada kayıtlı kalsın; diğer inşaat firmalarının şablonunu bozmasın!
                if ($firmaOzelMi) {
                    IseOzguSenkron::firmaOzelKaydet($this->firma, true);
                    unset($this->firmaEgitimOzelMi, $this->ayniIsGrubuFirmaSayisi);
                }

                unset($this->plan);

                $kaydedilen = 0;
                if (! empty($data['kutuphaneye_kaydet']) && ($userId = Filament::auth()->id())) {
                    // Kütüphaneye kaydederken seçilen sektörün kendi NACE'sini kullan (firmanın inşaat NACE'sine yazma!)
                    $nace = GeminiIseOzguEgitimUretici::sektorNaceOnekleri($sektorAnahtar);
                    $kaydedilen = GeminiIseOzguEgitimUretici::kutuphaneyeKaydet($userId, $sonuc['konular'], $nace, $hedefKitle);
                }

                Notification::make()
                    ->title(count($sonuc['konular']).' konu 4. bölüme uygulandı ve süreler yeniden dağıtıldı')
                    ->body($kaydedilen > 0 ? "{$kaydedilen} konu kütüphaneye de kaydedildi." : 'Eğitim Katılım Formu ve Boş Form artık doğrudan bu planı kullanır.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Portföydeki tüm (veya seçili) firmaların yıllık eğitim planlarını sektör/Gerçek İş Grubuna göre
     * AI ile tek seferde planlar; aynı iş grubundaki firmalar aynı 4. bölüm konularını paylaşır, her firmanın
     * tehlike sınıfı saatleri ve tamamlanmış G ayları korunur.
     */
    public function aiTopluFirmaPlanlaAction(): Action
    {
        return Action::make('aiTopluFirmaPlanla')
            ->label('✨ AI ile Tüm Firmaları Planla')
            ->modalHeading('✨ AI ile Firmaların Yıllık Eğitim Planlarını Toplu Planla')
            ->modalDescription('Her firmanın NACE / Gerçek İş Grubuna göre 4. Bölüm konularını AI ile üretir, Karma (Online + Yüz Yüze) saatlerini dağıtır. Aynı iş grubundaki firmalar tek API çağrısıyla birebir aynı 4. bölümü alır; Eğitim Katılım, Boş Form ve Sertifikalar doğrudan bu planlardan beslenir.')
            ->modalSubmitActionLabel('Toplu Planlamayı Başlat')
            ->schema([
                Radio::make('kapsam')
                    ->label('Hangi Firmalar Planlansın?')
                    ->options([
                        'tumu' => 'Tüm aktif firmalarım',
                        'secili' => 'Seçeceğim firmalar',
                    ])
                    ->default('tumu')
                    ->live()
                    ->required(),
                \Filament\Forms\Components\Select::make('firma_idler')
                    ->label('Firmalar')
                    ->multiple()
                    ->searchable()
                    ->options(fn () => $this->firmalar)
                    ->visible(fn ($get) => ($get('kapsam') ?? 'tumu') === 'secili')
                    ->required(fn ($get) => ($get('kapsam') ?? 'tumu') === 'secili'),
                Grid::make(2)->schema([
                    \Filament\Forms\Components\Select::make('adet')
                        ->label('Firma / Grup Başına 4. Bölüm Konu Sayısı')
                        ->options([4 => '4 konu', 5 => '5 konu', 6 => '6 konu'])
                        ->default(5)
                        ->required(),
                    \Filament\Forms\Components\Select::make('mod')
                        ->label('Online / Yüz Yüze Planlama Modu')
                        ->options(YillikEgitimPlanlayici::MODLAR)
                        ->default(YillikEgitimPlanlayici::VARSAYILAN_MOD)
                        ->required(),
                ]),
            ])
            ->action(function (array $data): void {
                $userId = (int) Filament::auth()->id();
                $sorgu = Firma::query()->where('user_id', $userId)->where('aktif', true)->orderBy('unvan');
                if (($data['kapsam'] ?? 'tumu') === 'secili') {
                    $sorgu->whereIn('id', array_map('intval', (array) ($data['firma_idler'] ?? [])));
                }
                $firmalar = $sorgu->get();

                if ($firmalar->isEmpty()) {
                    Notification::make()->title('Planlanacak firma bulunamadı')->warning()->send();

                    return;
                }

                $adet = (int) ($data['adet'] ?? 5);
                $mod = (string) ($data['mod'] ?? YillikEgitimPlanlayici::VARSAYILAN_MOD);
                $sektorler = GeminiIseOzguEgitimUretici::sektorSecenekleri();
                $grupOnbellek = [];
                $planlanan = 0;
                $hatalar = [];

                foreach ($firmalar as $firma) {
                    // Firmaya özel NACE dışı konu tanımlanmış firmalar toplu eşitlemede atlanır (özel içeriği bozulmaz).
                    if (IseOzguSenkron::firmaOzelMi($firma)) {
                        continue;
                    }

                    $etkinGrup = IseOzguSenkron::etkinGrup($firma);
                    $cacheAnahtar = $etkinGrup ?: ('nace_'.substr(preg_replace('/\D/', '', (string) $firma->nace_kodu), 0, 4) ?: 'genel_'.$firma->id);

                    if (! array_key_exists($cacheAnahtar, $grupOnbellek)) {
                        $sektorAd = $etkinGrup
                            ? ($sektorler[$etkinGrup] ?? $etkinGrup)
                            : ($firma->faaliyet_alani ?: 'Genel İşyeri (NACE '.($firma->nace_kodu ?: '—').')');
                        $nace = $etkinGrup
                            ? GeminiIseOzguEgitimUretici::sektorNaceOnekleri($etkinGrup, $firma->nace_kodu)
                            : $firma->nace_kodu;

                        $sonuc = GeminiIseOzguEgitimUretici::uret($sektorAd, $nace, $firma->tehlike_sinifi, null, null, $adet);
                        $grupOnbellek[$cacheAnahtar] = $sonuc;
                    }

                    $sonuc = $grupOnbellek[$cacheAnahtar];
                    if ($sonuc['hata'] !== null || $sonuc['konular'] === []) {
                        $hatalar[] = $firma->unvan.': '.($sonuc['hata'] ?? 'konu üretilemedi');

                        continue;
                    }

                    $plan = YillikPlanModel::firmaYilIcin($firma, $this->yil);
                    $kilit = $firma->planKilitAyIndeksi($this->yil);

                    $mevcutG = [];
                    foreach (collect($plan->egitimler ?? [])->where('kategori', 'ise_ozgu') as $m) {
                        $norm = EgitimKonulari::normalize((string) ($m['konu'] ?? ''));
                        foreach (($m['aylar'] ?? []) as $ay => $durum) {
                            if ($durum === 'tamamlandi') {
                                $mevcutG[$norm][$ay] = true;
                            }
                        }
                    }

                    $yeniOzgu = [];
                    foreach ($sonuc['konular'] as $k) {
                        $norm = EgitimKonulari::normalize($k['ad']);
                        $satir = YillikPlanModel::maddeAylarIle([[
                            'konu' => $k['ad'],
                            'kategori' => 'ise_ozgu',
                            'hedef' => $k['hedef'],
                            'egitici' => $k['egitici'],
                            'hafta' => 2,
                            'varsayilan_aylar' => range(0, 11),
                        ]], $kilit)[0];

                        foreach (($mevcutG[$norm] ?? []) as $ay => $_) {
                            $satir['aylar'][$ay] = 'tamamlandi';
                        }
                        $yeniOzgu[] = $satir;
                    }

                    $tum = YillikPlanSablonu::iseOzguYerlestir($plan->egitimler ?? [], $yeniOzgu);
                    $tum = YillikEgitimPlanlayici::satirlaraUygula($tum, $firma, $mod);
                    $plan->update(['egitimler' => $tum]);
                    $planlanan++;
                }

                unset($this->plan);
                $this->egitimPlanModunuSenkronla();

                if ($planlanan === 0) {
                    Notification::make()
                        ->title('Toplu planlama yapılamadı')
                        ->body(implode(' | ', array_slice($hatalar, 0, 3)))
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title("{$planlanan} firmanın {$this->yil} Yıllık Eğitim Planı AI ile hazırlandı")
                    ->body('Aynı iş grubundaki firmalar aynı 4. bölüm konularını aldı; Eğitim Katılım, Boş Form ve Sertifikalar doğrudan bu planlardan beslenir.')
                    ->success()
                    ->send();
            });
    }

    /** Plandaki bir 4. bölüm satırını kendi kütüphaneme kaydeder (başka firmalarda da seçilebilsin). */
    public function kutuphaneyeKaydetAction(): Action
    {
        return Action::make('kutuphaneyeKaydet')
            ->modalHeading('Konuyu kütüphaneye kaydet')
            ->modalDescription('Kaydedilen konu, NACE kodu veya iş kalemi eşleşen diğer firmaların planına otomatik önerilir.')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments): array {
                $e = ($this->plan()?->egitimler ?? [])[$arguments['index'] ?? -1] ?? [];
                $nace = preg_replace('/\D/', '', (string) $this->firma?->nace_kodu);

                return [
                    'ad' => $e['konu'] ?? null,
                    'hedef' => $e['hedef'] ?? null,
                    'egitici' => $e['egitici'] ?? IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                    'nace' => strlen($nace) >= 4 ? substr($nace, 0, 2).'.'.substr($nace, 2, 2) : null,
                    'is_kalemleri' => array_values($this->firma?->is_kalemleri ?? []),
                ];
            })
            ->schema(fn () => IseOzguEgitimKonulari::konuSemasi())
            ->action(function (array $data, array $arguments): void {
                $k = IseOzguEgitimKonusu::create([
                    'user_id' => Filament::auth()->id(),
                    'ad' => $data['ad'], 'hedef' => $data['hedef'] ?? null, 'egitici' => $data['egitici'] ?? null,
                    'nace_onekleri' => IseOzguEgitimKutuphanesi::naceHazirla($data['nace'] ?? null),
                    'is_kalemleri' => array_values($data['is_kalemleri'] ?? []) ?: null,
                ]);

                $p = $this->plan();
                $egitimler = $p?->egitimler ?? [];
                if (isset($egitimler[$arguments['index'] ?? -1])) {
                    $egitimler[$arguments['index']]['kutuphane_anahtari'] = 'ozel_'.$k->id;
                    $p->update(['egitimler' => $egitimler]);
                }

                Notification::make()->title('Konu kütüphaneye kaydedildi')->success()->send();
            });
    }

    /**
     * Planı Kaydet — çalışma/eğitim ay hücreleri ve satır ekleme/silme zaten anlık
     * kaydediliyor; bu buton değerlendirme sekmesindeki serbest metin alanlarının
     * (henüz odaktan çıkmamış olsa bile) sunucuya yazılmasını garantiler ve
     * kullanıcıya "kaydedildi" geri bildirimi verir.
     */
    public function planiKaydet(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $p->update([
            'faaliyetler' => $p->faaliyetler ?? [],
            'egitimler' => $p->egitimler ?? [],
            'degerlendirmeler' => $p->degerlendirmeler ?? [],
        ]);

        unset($this->plan);

        Notification::make()
            ->title('Yıllık plan kaydedildi')
            ->body('Son kayıt: '.now()->format('d.m.Y H:i'))
            ->success()
            ->send();
    }

    /**
     * Çıktıdaki "Hazırlanma Tarihi" — varsayılanı planın yılına göre gelir
     * (varsayilanHazirlanmaTarihi), kullanıcı her zaman değiştirebilir.
     */
    protected static function hazirlanmaTarihiAlani(string $ad = 'hazirlanma_tarihi'): DatePicker
    {
        return DatePicker::make($ad)
            ->label('Hazırlanma Tarihi')
            ->default(fn ($livewire) => static::varsayilanHazirlanmaTarihi((int) ($livewire->yil ?? now()->year)))
            ->native(false)
            ->displayFormat('d.m.Y')
            ->required()
            ->helperText('Tarih otomatik gelir; farklı bir tarih istiyorsanız değiştirin.');
    }

    /**
     * Yıl için ayarlanmış tarih (config isg.yillik_plan.hazirlanma_tarihleri) henüz gelmediyse o
     * tarih, geldiyse ya da ayar yoksa bugün. Ör. 2027 planları 04.01.2027'ye kadar o tarihle çıkar.
     */
    public static function varsayilanHazirlanmaTarihi(int $yil): string
    {
        $ayarli = config('isg.yillik_plan.hazirlanma_tarihleri.'.$yil);
        $bugun = now()->toDateString();

        return filled($ayarli) && $bugun < Carbon::parse($ayarli)->toDateString()
            ? Carbon::parse($ayarli)->toDateString()
            : $bugun;
    }

    protected static function secilenTarih(array $data, string $ad = 'hazirlanma_tarihi'): ?Carbon
    {
        return filled($data[$ad] ?? null) ? Carbon::parse($data[$ad]) : null;
    }

    /**
     * Planın uyarlanabileceği firmalar (kendi firmalarınız, açık olan hariç). Etikette inşaat
     * firması ve o yıl için zaten plan olup olmadığı yazar.
     *
     * @return array<int, string>
     */
    protected function uyarlanabilirFirmalar(): array
    {
        $firmalar = Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->whereKeyNot($this->firmaId)
            ->orderBy('unvan')
            ->get();

        $planliIdler = YillikPlanModel::where('yil', $this->yil)
            ->whereIn('firma_id', $firmalar->pluck('id'))
            ->pluck('firma_id')
            ->all();

        return $firmalar
            ->mapWithKeys(fn (Firma $f) => [$f->id => $f->secimEtiketi()
                .(YillikPlanSablonu::santiyeMi($f) ? ' · inşaat' : '')
                .(in_array($f->id, $planliIdler, true) ? ' · '.$this->yil.' planı var, seçilen bölümler değişir' : '')])
            ->all();
    }

    /** @return array<int, int> */
    protected function insaatFirmaIdleri(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->whereKeyNot($this->firmaId)
            ->get()
            ->filter(fn (Firma $f) => YillikPlanSablonu::santiyeMi($f))
            ->modelKeys();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('otomatikPlanUret')
                ->label('⚡ Otomatik Plan Üret')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->outlined()
                ->visible(fn () => $this->plan() !== null && in_array($this->sekme, ['calisma', 'egitim'], true))
                ->requiresConfirmation()
                ->modalHeading('Tehlike Sınıfına Göre 12 Aylık Plan Otomatik Doldurulsun mu?')
                ->modalDescription('Boş kalan plan satırları işyerinin tehlike sınıfına (Çok Tehlikeli / Tehlikeli / Az Tehlikeli) göre 12 aya dengeli şekilde Planlandı (P) olarak dağıtılır.')
                ->action(function (): void {
                    $plan = $this->plan();
                    if (! $plan) {
                        return;
                    }
                    $alan = $this->sekme === 'egitim' ? 'egitimler' : 'faaliyetler';
                    $sinif = $this->firma?->tehlike_sinifi ?? 'tehlikeli';
                    $adim = match ($sinif) {
                        'cok_tehlikeli' => 1,
                        'tehlikeli' => 2,
                        default => 3,
                    };

                    $satirlar = (array) ($plan->{$alan} ?? []);
                    $guncellenen = 0;
                    foreach ($satirlar as $idx => $s) {
                        $aylar = array_pad((array) ($s['aylar'] ?? []), 12, 'bos');
                        if (! collect($aylar)->contains(fn ($d) => $d !== 'bos')) {
                            $baslangic = $idx % max(1, $adim);
                            for ($ay = $baslangic; $ay < 12; $ay += max(3, $adim * 2)) {
                                $aylar[$ay] = 'planlandi';
                            }
                            $satirlar[$idx]['aylar'] = $aylar;
                            $guncellenen++;
                        }
                    }

                    $plan->forceFill([$alan => $satirlar])->save();
                    unset($this->plan);
                    Notification::make()->title("{$guncellenen} boş satır için 12 aylık otomatik plan üretildi")->success()->send();
                }),

            Action::make('plandanDegerlendirmeSenkron')
                ->label('🔄 Plandan Değerlendirmeyi Senkronize Et')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->outlined()
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'degerlendirme')
                ->action(function (): void {
                    $plan = $this->plan();
                    if (! $plan) {
                        return;
                    }
                    $planli = 0;
                    $gerceklesen = 0;
                    foreach (['faaliyetler', 'egitimler'] as $alan) {
                        foreach ((array) ($plan->{$alan} ?? []) as $s) {
                            foreach ((array) ($s['aylar'] ?? []) as $d) {
                                if (in_array($d, ['planlandi', 'tamamlandi', 'gerceklesti'], true)) {
                                    $planli++;
                                }
                                if (in_array($d, ['tamamlandi', 'gerceklesti'], true)) {
                                    $gerceklesen++;
                                }
                            }
                        }
                    }
                    $oran = $planli > 0 ? (int) round(($gerceklesen / $planli) * 100) : 0;
                    $yeniSatir = [
                        'tarih' => now()->format('d.m.Y'),
                        'calisma' => $this->yil.' Yılı Plan Gerçekleşme Senkronizasyonu',
                        'yapan_kisi' => Filament::auth()->user()?->name ?: 'İSG Uzmanı',
                        'sonuc' => "Planlanan: {$planli} · Gerçekleşen: {$gerceklesen} · Gerçekleşme Oranı: %{$oran}",
                        'otomatik' => true,
                    ];
                    // Aynı yıl için önceki otomatik senkron satırını güncelle — her tıkta çöp satır birikmesin.
                    $deg = collect((array) ($plan->degerlendirmeler ?? []))
                        ->reject(fn ($satir) => ! empty($satir['otomatik']) && ($satir['calisma'] ?? '') === $yeniSatir['calisma'])
                        ->values()
                        ->all();
                    $deg[] = $yeniSatir;
                    $plan->forceFill(['degerlendirmeler' => $deg])->save();
                    unset($this->plan);

                    Notification::make()
                        ->title("Yıllık Plan → Değerlendirme Senkronize Edildi (%{$oran})")
                        ->body("Planlanan: {$planli} · Gerçekleşen: {$gerceklesen}")
                        ->success()
                        ->send();
                }),

            Action::make('planiKaydet')
                ->label('Planı Kaydet')
                ->icon('heroicon-o-check')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null)
                ->action(fn () => $this->planiKaydet()),

            Action::make('gerceklesmeleriIsle')
                ->label('Sistem Kayıtlarından G İşle')
                ->icon('heroicon-o-check-badge')
                ->color('gray')
                ->tooltip('Tatbikat, saha denetimi, DÖF, kurul toplantısı, olay, KKD zimmeti ve temel eğitim kayıtlarının aylarını plan satırlarında Gerçekleşti yapar')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'calisma')
                ->action(function () {
                    $sayi = YillikPlanGerceklesme::isle($this->plan());
                    unset($this->plan);

                    Notification::make()
                        ->title($sayi ? $sayi.' plan hücresi "Gerçekleşti" işaretlendi' : 'İşlenecek yeni kayıt bulunamadı')
                        ->success()->send();
                }),

            Action::make('egitimKayitlariniIsle')
                ->label('Verilen Eğitimleri İşle')
                ->icon('heroicon-o-academic-cap')
                ->color('gray')
                ->tooltip('Bu yıl Eğitim Katılım ile kaydedilen temel İSG eğitimlerinin konularını, eğitim ayına "Gerçekleşti" olarak işler')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'egitim')
                ->requiresConfirmation()
                ->modalHeading('Verilen eğitimler plana işlensin mi?')
                ->modalDescription('Bu yıl kaydedilmiş temel İSG eğitimlerinde işlenen konular, eğitimin yapıldığı aya "Gerçekleşti" olarak işaretlenir. Diğer hücrelere dokunulmaz.')
                ->modalSubmitActionLabel('İşle')
                ->action(function () {
                    $sayi = EgitimKonulari::kayitlariPlanaIsle($this->plan());
                    unset($this->plan);

                    Notification::make()
                        ->title($sayi ? $sayi.' plan hücresi "Gerçekleşti" işaretlendi' : 'İşlenecek yeni eğitim bulunamadı')
                        ->body($sayi ? null : 'Bu yılın eğitim kayıtları planda zaten işli.')
                        ->success()->send();
                }),

            // Çalışma/eğitim planı çıktısı Excel şablonlarıyla (aşağıda); PDF yalnız
            // Değerlendirme Raporu için kaldı.
            Action::make('pdf')
                ->label('Çıktı Al (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('Yıllık Değerlendirme Raporu PDF olarak iner')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'degerlendirme')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn (array $data) => YillikPlanUretici::pdf($this->plan(), ImzaSecenegi::secili($data))),

            // Kullanıcının gerçek Excel şablonlarıyla birebir çıktı (YillikPlanExcelUretici).
            Action::make('calismaExcel')
                ->label('Çıktı Al (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('A4 yatay, yazdırmaya hazır Excel dosyası iner')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'calisma')
                ->modalHeading('Yıllık Çalışma Planı — Çıktı Al')
                ->modalSubmitActionLabel('İndir')
                ->schema([static::hazirlanmaTarihiAlani()])
                ->action(fn (array $data) => YillikPlanExcelUretici::calisma($this->plan(), static::secilenTarih($data))),

            Action::make('egitimExcel')
                ->label('Çıktı Al (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('Yazdırmaya hazır Excel dosyası iner')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'egitim')
                ->modalHeading('Yıllık Eğitim Planı — Çıktı Al')
                ->modalSubmitActionLabel('İndir')
                ->schema([static::hazirlanmaTarihiAlani(), ImzaSecenegi::alan()])
                ->action(fn (array $data) => YillikPlanExcelUretici::egitim($this->plan(), ImzaSecenegi::secili($data), static::secilenTarih($data))),

            Action::make('tumFirmalarEgitimZip')
                ->label('Tüm Firmalar (Klasörlü ZIP)')
                ->icon('heroicon-o-folder-arrow-down')
                ->color('info')
                ->tooltip('Tüm aktif firmalarınızın seçilen yıl Yıllık Eğitim Planlarını her firma için ayrı klasörde ZIP olarak indirir')
                ->visible(fn () => $this->sekme === 'egitim')
                ->modalHeading('Tüm Firmalar — Klasörlü Yıllık Eğitim Planı Paketi (ZIP)')
                ->modalDescription('Her aktif firmanız için ayrı klasör açılır ve o yılın Yıllık Eğitim Planı Excel dosyası içine konur. Yeni yıl planı yoksa bir önceki yılın (ör. 2026) 4. bölümü ve Online/Yüz Yüze dağılımı otomatik aktarılır.')
                ->modalSubmitActionLabel('Klasörlü ZIP İndir')
                ->schema([
                    \Filament\Forms\Components\Select::make('hedef_yil')
                        ->label('Plan Yılı')
                        ->options(collect(range(now()->year, now()->year + 2))->mapWithKeys(fn (int $y) => [$y => (string) $y])->all())
                        ->default(fn () => max($this->yil, now()->year + 1))
                        ->required(),
                    DatePicker::make('hazirlanma_tarihi')
                        ->label('Hazırlanma Tarihi')
                        ->default(fn () => static::varsayilanHazirlanmaTarihi(max($this->yil, now()->year + 1)))
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->required(),
                    Toggle::make('onceki_yildan_aktar')
                        ->label('Yeni yıl planı boşsa önceki yıldaki (ör. 2026) 4. bölümü ve karma modu aktar')
                        ->default(true),
                    ImzaSecenegi::alan()->inline(),
                ])
                ->action(function (array $data) {
                    $firmalar = Firma::query()
                        ->where('user_id', Filament::auth()->id())
                        ->where('aktif', true)
                        ->orderBy('unvan')
                        ->get();

                    if ($firmalar->isEmpty()) {
                        Notification::make()->title('Aktif firma bulunamadı')->warning()->send();

                        return null;
                    }

                    $hedefYil = (int) ($data['hedef_yil'] ?? $this->yil);
                    $sonuc = YillikPlanTopluIndirme::tumFirmalarEgitimZip(
                        $firmalar,
                        $hedefYil,
                        static::secilenTarih($data),
                        ImzaSecenegi::secili($data),
                        (bool) ($data['onceki_yildan_aktar'] ?? true),
                    );
                    $zip = $sonuc['zip_yolu'];

                    return response()->streamDownload(function () use ($zip) {
                        echo file_get_contents($zip);
                        @unlink($zip);
                    }, "tum-firmalar-yillik-egitim-planlari-{$hedefYil}.zip", ['Content-Type' => 'application/zip']);
                }),

            Action::make('topluIndir')
                ->label('3 Planı Birlikte İndir')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('success')
                ->tooltip('Yıllık Çalışma Planı, Yıllık Eğitim Planı ve Yıllık Değerlendirme Raporu tek ZIP dosyasında iner')
                ->visible(fn () => $this->plan() !== null)
                ->modalHeading(fn () => $this->yil.' yıllık planlarını birlikte indir')
                ->modalDescription('Üç belge tek ZIP dosyasında iner. Her belgenin hazırlanma tarihini ayrı girebilirsiniz.')
                ->modalSubmitActionLabel('İndir')
                ->modalWidth(Width::ThreeExtraLarge)
                ->schema([
                    // Tarihler yan yana: pencere kısa kalır, dizüstü ekranda kaydırma gerekmez.
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        static::hazirlanmaTarihiAlani('calisma_tarihi')->label('Çalışma Planı tarihi')->helperText(null),
                        static::hazirlanmaTarihiAlani('egitim_tarihi')->label('Eğitim Planı tarihi')->helperText(null),
                        static::hazirlanmaTarihiAlani('degerlendirme_tarihi')->label('Değerlendirme Raporu tarihi')->helperText(null),
                    ]),
                    Radio::make('bicim')
                        ->label('Değerlendirme raporu biçimi')
                        ->options([
                            'form' => 'İSG Plan ve Değerlendirme Formu (tek sayfa)',
                            'ydr' => 'İSG-YDR-01 (4 sayfa, yatay)',
                        ])
                        ->default('form')
                        ->inline()
                        ->required(),
                    ImzaSecenegi::alan()->inline(),
                ])
                ->action(fn (array $data) => YillikPlanTopluIndirme::indir(
                    $this->plan(),
                    static::secilenTarih($data, 'calisma_tarihi'),
                    static::secilenTarih($data, 'egitim_tarihi'),
                    static::secilenTarih($data, 'degerlendirme_tarihi'),
                    ($data['bicim'] ?? 'form') === 'ydr' ? 'ydr' : 'form',
                    ImzaSecenegi::secili($data),
                )),

            Action::make('digerFirmalaraUyarla')
                ->label('Diğer Firmalara Uyarla')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->tooltip('Bu firmanın yıllık planlarını seçtiğiniz firmalara, her firmanın bilgilerine göre uyarlayarak kopyalar')
                ->visible(fn () => $this->plan() !== null)
                ->modalHeading(fn () => ($this->firma?->secimEtiketi() ?? '').' — '.$this->yil.' planlarını diğer firmalara uyarla')
                ->modalDescription('Seçtiğiniz firmaların bu yıla ait seçili bölümleri, bu firmadaki planla değiştirilir. '
                    .'Her firmada: sözleşme başlangıcından önceki aylar boş kalır, İSG Kurulu ayları çalışan sayısı ve tehlike sınıfına göre yerleşir, '
                    .'gerçekleşenler o firmanın kendi kayıtlarından işlenir, değerlendirmede elle yazdığınız satırlar firma adı değiştirilerek kopyalanır. '
                    .'İnşaat firmaları önceden seçili gelir.')
                ->modalWidth(Width::TwoExtraLarge)
                ->modalSubmitActionLabel('Uyarla')
                ->schema([
                    CheckboxList::make('bolumler')
                        ->label('Kopyalanacak bölümler')
                        ->options(YillikPlanUyarlayici::BOLUMLER)
                        ->default(array_keys(YillikPlanUyarlayici::BOLUMLER))
                        ->required(),
                    CheckboxList::make('firmalar')
                        ->label('Hedef firmalar')
                        ->options(fn () => $this->uyarlanabilirFirmalar())
                        ->default(fn () => $this->insaatFirmaIdleri())
                        ->searchable()
                        ->bulkToggleable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $kaynak = $this->plan();
                    if (! $kaynak) {
                        return;
                    }

                    // Yalnız kullanıcının kendi firmaları (formdan gelen kimlikler yeniden süzülür).
                    $hedefler = Firma::query()
                        ->where('user_id', Filament::auth()->id())
                        ->whereKeyNot($this->firmaId)
                        ->whereKey(array_map('intval', (array) ($data['firmalar'] ?? [])))
                        ->get();
                    $bolumler = array_values(array_intersect((array) ($data['bolumler'] ?? []), array_keys(YillikPlanUyarlayici::BOLUMLER)));

                    foreach ($hedefler as $hedef) {
                        YillikPlanUyarlayici::uyarla($kaynak, $hedef, $bolumler);
                    }

                    Notification::make()
                        ->title($hedefler->count().' firmaya uyarlandı')
                        ->body($hedefler->map(fn (Firma $f) => $f->secimEtiketi())->implode(', '))
                        ->success()
                        ->send();
                }),

            Action::make('oncekiYildanKopyala')
                ->label('Önceki Yıldan Kopyala')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->tooltip(fn () => ($this->yil - 1).' yılındaki özel satırları ve planlanan ayları '.$this->yil.' yılına aktarır')
                ->visible(fn () => $this->plan() !== null && in_array($this->sekme, ['calisma', 'egitim'], true))
                ->requiresConfirmation()
                ->modalHeading(fn () => ($this->yil - 1).' Yılı Planından '.$this->yil.' Yılına Kopyala')
                ->modalDescription(fn () => 'Bu firmanın '.($this->yil - 1).' yılına ait '.($this->sekme === 'egitim' ? 'Yıllık Eğitim Planı' : 'Yıllık Çalışma Planı')
                    .' satırları ve planlanan ayları '.$this->yil.' yılına kopyalanır (geçen yılın "Gerçekleşti" işaretleri yeni yılda "Planlandı" olarak aktarılır).')
                ->modalSubmitActionLabel('Kopyala')
                ->action(function (): void {
                    $hedef = $this->plan();
                    if (! $hedef || ! $this->firma) {
                        return;
                    }

                    $onceki = YillikPlanModel::where('firma_id', $this->firma->id)
                        ->where('yil', $this->yil - 1)
                        ->first();

                    if (! $onceki) {
                        Notification::make()
                            ->title(($this->yil - 1).' yılına ait kayıtlı bir plan bulunamadı')
                            ->warning()->send();

                        return;
                    }

                    $alan = $this->sekme === 'egitim' ? 'egitimler' : 'faaliyetler';
                    $satirlar = collect($onceki->{$alan} ?? [])->map(function (array $satir) {
                        if (isset($satir['aylar']) && is_array($satir['aylar'])) {
                            $satir['aylar'] = array_map(
                                fn ($durum) => in_array($durum, ['planlandi', 'tamamlandi', 'planlandi_gerceklesti', 'P', 'G', 'PG'], true) ? 'planlandi' : 'bos',
                                $satir['aylar']
                            );
                        }

                        return $satir;
                    })->values()->all();

                    if (! $satirlar) {
                        Notification::make()->title('Önceki yıl planında kopyalanacak satır yok')->warning()->send();

                        return;
                    }

                    $hedef->update([$alan => $satirlar]);
                    unset($this->plan);
                    $this->gerceklesmeleriIsle();

                    Notification::make()
                        ->title(($this->yil - 1).' yılı planı '.$this->yil.' yılına kopyalandı')
                        ->body(count($satirlar).' satır aktarıldı.')
                        ->success()->send();
                }),

            Action::make('sablonuUygula')
                ->label('Standart Şablonu Uygula')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && in_array($this->sekme, ['calisma', 'egitim'], true))
                ->requiresConfirmation()
                ->modalHeading('Standart şablon uygulansın mı?')
                ->modalDescription(fn () => 'Bu plandaki ('.($this->sekme === 'egitim' ? 'eğitim planı' : 'çalışma planı').') tüm satırlar standart şablonla değiştirilecek: '
                    .($this->sekme === 'egitim'
                        ? '5 bölümlü eğitim planı (4. bölüm "İşe ve İşyerine Özgü Riskler" yalnız inşaat firmalarında dolu gelir).'
                        : '36 faaliyet; ana konu, periyot, sorumlu ve mevzuat/kayıt notlarıyla.')
                    .' Sözleşme başlangıcından önceki aylar boş bırakılır.')
                ->modalSubmitActionLabel('Uygula')
                ->action(function (): void {
                    $p = $this->plan();
                    if (! $p || ! $this->firma) {
                        return;
                    }
                    $icerik = YillikPlanSablonu::icerik($this->firma, $this->yil);
                    $alan = $this->sekme === 'egitim' ? 'egitimler' : 'faaliyetler';
                    $p->update([$alan => $icerik[$alan]]);
                    unset($this->plan);

                    Notification::make()->title('Standart şablon uygulandı')->success()->send();
                }),

            Action::make('excelYukleCalisma')
                ->label('Excel’den Yükle')->tooltip('Çalışma Planı Excel’den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'calisma')
                ->modalDescription('Kendi Yıllık Çalışma Planı Excel’inizi yükleyin. Satırlar isimle eşleştirilir: mevcut satır varsa ayları güncellenir, yoksa eklenir. Atanmış uzman öncesindeki aylar işaretlenmez.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([static::dosyaAlani()])
                ->action(fn (array $data) => $this->exceliIsle($data['dosya'], 'faaliyetler')),

            Action::make('excelYukleEgitim')
                ->label('Eğitim Planı Excel’den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'egitim')
                ->modalDescription('Kendi Yıllık Eğitim Planı Excel’inizi yükleyin (12 ay × 4 hafta düzeni desteklenir, ay bazına indirilir). Satırlar isimle eşleştirilir.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([static::dosyaAlani()])
                ->action(fn (array $data) => $this->exceliIsle($data['dosya'], 'egitimler')),

            Action::make('degerlendirmeExcel')
                ->label('Çıktı Al (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('İSG Plan ve Değerlendirme Formu (tek sayfa) ya da İSG-YDR-01 (4 sayfa, yatay) Excel olarak iner')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'degerlendirme')
                ->modalHeading('Yıllık Değerlendirme Raporu — Çıktı Al')
                ->modalSubmitActionLabel('İndir')
                ->schema([
                    Radio::make('bicim')
                        ->label('Biçim')
                        ->options([
                            'form' => 'İSG Plan ve Değerlendirme Formu (tek sayfa, 19 çalışma satırı)',
                            'ydr' => 'İSG-YDR-01 (4 sayfa yatay, 36 satır — bu sayfadaki satırlarla)',
                        ])
                        ->default('form')
                        ->required(),
                    static::hazirlanmaTarihiAlani()->label('Rapor Tarihi'),
                    ImzaSecenegi::alan(),
                ])
                ->action(fn (array $data) => ($data['bicim'] ?? 'form') === 'ydr'
                    ? YillikDegerlendirmeExcelUretici::excel($this->plan(), static::secilenTarih($data))
                    : YillikDegerlendirmeFormUretici::excel($this->firma, $this->yil, static::secilenTarih($data), ImzaSecenegi::secili($data))),

            Action::make('degerlendirmeSistemdenDoldur')
                ->label('Sistemden Yenile')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'degerlendirme')
                ->modalHeading('Değerlendirmeyi sistem kayıtlarından yenile')
                ->modalDescription(fn () => 'Dönem: '.($this->firma ? YillikDegerlendirmeVerisi::donemMetni($this->firma, $this->yil) : '—')
                    .' (atandığınız tarih → yıl sonu). Risk değerlendirmesi, muayeneler, sağlık gözetimi, eğitimler, acil durum, tatbikat, ekipman kontrolleri, saha denetimi, DÖF, olaylar, KKD, kimyasallar, kurul ve görevlendirme kayıtlarından Tarih / Dönem ve Sonuç alanları yazılır. Kaydı olmayan satırlar şablon metniyle kalır.')
                ->schema([
                    Toggle::make('ustune_yaz')->label('Elle düzenlediğim satırların da üzerine yaz')->default(false),
                ])
                ->modalSubmitActionLabel('Yenile')
                ->action(function (array $data): void {
                    $p = $this->plan();

                    if (! $p) {
                        return;
                    }

                    $sonuc = YillikDegerlendirmeVerisi::planiDoldur($this->firma, $this->yil, $p->degerlendirmeler ?? [], (bool) ($data['ustune_yaz'] ?? false));
                    $p->update(['degerlendirmeler' => $sonuc['satirlar']]);

                    $bildirim = Notification::make()->title($sonuc['doldurulan'] > 0
                        ? $sonuc['doldurulan'].' satır sistem kayıtlarından dolduruldu'
                        : 'Bu dönem için eşleşen sistem kaydı bulunamadı');
                    ($sonuc['doldurulan'] > 0 ? $bildirim->success() : $bildirim->warning())->send();
                }),
        ];
    }

    private static function dosyaAlani(): FileUpload
    {
        return FileUpload::make('dosya')
            ->label('Excel dosyası (.xlsx / .xls)')
            ->disk('local')
            ->directory('excel-ice-aktarim')
            ->acceptedFileTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
            ])
            ->required();
    }

    /** @param  'faaliyetler'|'egitimler'  $tip */
    private function exceliIsle(string $dosya, string $tip): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $yol = Storage::disk('local')->path($dosya);

        try {
            $sonuc = YillikPlanExcelIceAktarici::iceAktar($yol, $p, $tip);
        } catch (Throwable $e) {
            Notification::make()->title('Dosya işlenemedi')->body($e->getMessage())->danger()->send();

            return;
        } finally {
            Storage::disk('local')->delete($dosya);
        }

        unset($this->plan);

        if ($sonuc['hatalar']) {
            Notification::make()->title('İçe aktarma tamamlanamadı')->body(implode(' ', $sonuc['hatalar']))->danger()->send();

            return;
        }

        Notification::make()
            ->title('Excel içe aktarıldı')
            ->body("{$sonuc['eklenen']} satır eklendi, {$sonuc['guncellenen']} satır güncellendi.")
            ->success()
            ->send();
    }
}
