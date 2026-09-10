<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\EgitimKatilim as EgitimKatilimModel;
use App\Models\Firma;
use App\Models\Sertifika;
use App\Support\EgitimIcerikOlusturucu;
use App\Support\EgitimKatilimUretici;
use App\Support\KatilimciExcelOkuyucu;
use App\Support\SertifikaUretici;
use BackedEnum;
use Illuminate\Support\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
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
    use WithFileUploads;

    protected string $view = 'filament.pages.egitim-katilim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'egitim-katilim';

    protected static ?string $title = 'Eğitim Katılım Formu';

    protected static ?string $navigationLabel = 'Eğitim Katılım';

    public ?int $firmaId = null;

    public string $baslikAnahtari = 'genel';

    public string $egitimTuru = 'ilk';

    /** yuz_yuze | uzaktan | karma — formda işaretlenebilir kutu olarak da gösterilir. */
    public string $egitimSekli = 'yuz_yuze';

    public ?string $sektorAnahtari = null;

    public ?string $egitimYeri = null;

    public ?string $belgeTarihi = null;

    public int $sureGun = 1;

    /** Belgede görünen "X Ders Saati" — tehlike sınıfına göre 8/12/16, gerekirse elle değiştirilir. */
    public ?int $dersSaati = null;

    public bool $isgUzmaniVar = true;

    public bool $isyeriHekimiVar = false;

    public ?string $isyeriHekimiAdi = null;

    /** @var array<int, int> katılımcı olarak dahil edilen firma çalışanı id'leri */
    public array $secilenCalisanIdler = [];

    /** @var array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    public array $manuelKatilimcilar = [];

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
    public function basliklar(): array
    {
        return EgitimIcerikOlusturucu::basliklar();
    }

    #[Computed]
    public function sektorler(): array
    {
        return EgitimIcerikOlusturucu::sektorler();
    }

    private function icerikYenile(): void
    {
        $this->icerik = EgitimIcerikOlusturucu::olustur(
            $this->baslikAnahtari,
            $this->sektorAnahtari,
            $this->firma?->tehlike_sinifi ?? 'az_tehlikeli',
            $this->egitimTuru,
        );

        // Tehlike sınıfına göre nominal ders saati (8/12/16) — kullanıcı elle değiştirebilir.
        $this->dersSaati = $this->icerik['saat'] ?? null;

        $this->sureGunYenile();
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
    }

    /** Konu dakikası / dahil durumu her değiştiğinde gün sayısını yeniden hesapla. */
    public function updatedIcerik(): void
    {
        $this->sureGunYenile();
    }

    public function updatedEgitimTuru(): void
    {
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
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
        $this->secilenCalisanIdler = $this->calisanlar->pluck('id')->all();
        $this->egitmenBilgileriYenile();
        $this->icerikYenile();
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

        $this->icerikYenile();
    }

    public function updatedSektorAnahtari(): void
    {
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
        $this->validate(['yeniAdSoyad' => 'required|string|max:190']);

        $this->manuelKatilimcilar[] = [
            'ad_soyad' => $this->yeniAdSoyad,
            'tc' => $this->yeniTc ?: null,
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
                'gorev' => $c->gorev,
            ])
            ->values()
            ->all();

        return [...$firmaCalisanlari, ...$this->manuelKatilimcilar];
    }

    /**
     * Elle / Excel ile eklenen katılımcılardan firmanın çalışan listesinde
     * OLMAYANLARı (TC varsa TC'ye, yoksa ad-soyada göre) firmaya ekler.
     */
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

            if ($ad === '') {
                continue;
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
    private function sertifikaKur(EgitimKatilimModel $kayit): Sertifika
    {
        $tip = 'isg';

        foreach (config('isg.sertifika.tipler', []) as $anahtar => $tanim) {
            if (($tanim['icerik_anahtari'] ?? null) === $kayit->baslik_anahtari && $kayit->baslik_anahtari) {
                $tip = $anahtar;

                break;
            }
        }

        $gun = max(1, (int) ($kayit->sure_gun ?? 1));
        $tarih = $kayit->belge_tarihi?->toDateString() ?? now()->toDateString();
        $tehlike = $kayit->firma?->tehlike_sinifi ?? 'az_tehlikeli';
        $dersSaati = $kayit->konu_secimleri['saat'] ?? null;

        $s = new Sertifika([
            'firma_id' => $kayit->firma_id,
            'tip' => $tip,
            'tur' => ($kayit->egitim_turu ?? 'ilk') === 'tekrar' ? 'tekrar' : 'ilk_defa',
            'sekil' => $kayit->egitim_sekli ?? 'yuz_yuze',
            'sektor_anahtari' => $tip === 'isg' ? $kayit->sektor_anahtari : null,
            'gun_sayisi' => $gun,
            'egitim_tarihleri' => array_fill(0, $gun, $tarih),
            'gecerlilik_tarihi' => Carbon::parse($tarih)
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
            'katilimcilar' => $kayit->katilimcilar,
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
        if (! $this->firma || ! $this->belgeTarihi) {
            Notification::make()->title('Firma ve tarih zorunlu')->danger()->send();

            return null;
        }

        // Firmada kayıtlı OLMAYAN katılımcılar firma çalışan listesine otomatik eklenir.
        $yeniCalisan = $this->eksikCalisanlariEkle();

        if ($yeniCalisan > 0) {
            Notification::make()->title($yeniCalisan.' katılımcı firma çalışan listesine eklendi')->success()->send();
        }

        // Belgede görünen "Ders Saati" — kullanıcı elle değiştirdiyse onu kaydet.
        $icerik = $this->icerik;

        if (($icerik['tip'] ?? null) === 'genel' && $this->dersSaati) {
            $icerik['saat'] = $this->dersSaati;
        }

        $kayit = new EgitimKatilimModel([
            'firma_id' => $this->firma->id,
            'baslik_anahtari' => $this->baslikAnahtari,
            'egitim_turu' => $this->egitimTuru,
            'egitim_sekli' => $this->egitimSekli,
            'sektor_anahtari' => $this->baslikAnahtari === 'genel' ? $this->sektorAnahtari : null,
            'egitim_yeri' => $this->egitimYeri,
            'belge_tarihi' => $this->belgeTarihi,
            'sure_gun' => $this->sureGun,
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

        unset($this->gecmisKayitlar);

        return $kayit;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Form PDF (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no)->success()->send();

                    return EgitimKatilimUretici::pdf($kayit);
                }),

            Action::make('excel')
                ->label('Excel (Kaydet ve İndir)')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no)->success()->send();

                    return EgitimKatilimUretici::excel($kayit);
                }),

            Action::make('sertifika')
                ->label('Katılımcı Sertifikaları (Kaydet ve İndir)')
                ->icon('heroicon-o-check-badge')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $kayit = $this->kaydet();

                    if (! $kayit) {
                        return null;
                    }

                    Notification::make()->title('Eğitim katılım formu kaydedildi')->body($kayit->belge_no.' — her katılımcı için ayrı sertifika sayfası')->success()->send();

                    return SertifikaUretici::pdf($this->sertifikaKur($kayit));
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

    public function gecmisSertifika(int $id)
    {
        $kayit = $this->firma?->egitimKatilimlari()->find($id);

        if (! $kayit) {
            return null;
        }

        $kayit->setRelation('firma', $this->firma);

        return SertifikaUretici::pdf($this->sertifikaKur($kayit));
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
