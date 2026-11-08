<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Pages\Concerns\PortfoyAjandasi;
use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami as ZiyaretProgramiModel;
use App\Support\GeminiZiyaretDanismani;
use App\Support\KomutaFirma;
use App\Support\ZiyaretOtomatikPlanlayici;
use App\Support\ZiyaretProgramiUretici;
use App\Support\YasalZiyaretSuresi;
use App\Support\ZiyaretTakvimi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Saha & Ziyaret Planlama Merkezi (29.10.2026): eski "Ziyaret Programı" (firma bazlı 12 aylık plan) ile eski "Saha Takvimi"
 * (portföy ajandası) tek ekranda, üstte görünüm seçici. Yasal asgari süre firma seçilince otomatik hesaplanır.
 *
 * Ziyaret Programı — isgpratik'te ekran görüntüsü yok; kullanıcı onayıyla
 * BASİTLEŞTİRİLMİŞ liste (firma+yıl başına 12 aylık satır) olarak kuruldu.
 * isgpratik'teki tam takvim/sürükle-bırak/çoklu-firma-OSGB arayüzü kapsam
 * dışı bırakıldı.
 */
class ZiyaretProgrami extends Page
{
    use PortfoyAjandasi;
    use SinirliErisim;

    protected string $view = 'filament.pages.ziyaret-programi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Günlük Saha İşi';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'ziyaret-programi';

    protected static ?string $title = 'Saha & Ziyaret Planlama Merkezi';

    protected static ?string $navigationLabel = 'Ziyaret Planlama';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** Görünüm seçici: 'ajanda' = Genel Ajanda (portföy takvimi), 'plan' = Firma Bazlı Yıllık Plan (12 ay). */
    #[Url(as: 'gorunum')]
    public string $gorunum = 'plan';

    public int $yil;

    public ?string $takvimAy = null;

    public ?string $takvimSeciliTarih = null;

    /** "Ziyarette Yapılacaklar" listesinin gösterdiği ay (0–11). */
    public int $yapilacakAy = 0;

    /** Yapılacaklar penceresi (modal) açık mı. */
    public bool $yapilacakModalAcik = false;

    /** Bir ay için öne çıkarılabilecek kritik görev sayısı. */
    public const KRITIK_GOREV_SINIRI = 2;

    public function mount(): void
    {
        $this->yil = (int) now()->format('Y');
        $this->takvimSeciliTarih = now()->toDateString();
        $this->yapilacakAy = now()->month - 1;

        $this->ajandaBaslat();

        if (! in_array($this->gorunum, ['ajanda', 'plan'], true)) {
            $this->gorunum = 'plan';
        }

        // ?firma= yoksa ortak firma bağlamı (App\Support\KomutaFirma) açılır.
        if ($firmaId = request()->integer('firma') ?: KomutaFirma::baslangic($this->firmalar)) {
            $this->firmaId = $firmaId;
            KomutaFirma::sec($firmaId);
        }

        // Ana sayfa widget'ından gelen bağlantı: ?yil=2026&ay=10 (1–12).
        if ($yil = request()->integer('yil')) {
            $this->yil = $yil;
        }
        if ($ay = request()->integer('ay')) {
            $this->yapilacakAy = max(0, min(11, $ay - 1));
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
    public function program(): ?ZiyaretProgramiModel
    {
        return $this->firma ? ZiyaretProgramiModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    #[Computed]
    public function amacKategorileri(): array
    {
        return config('isg.ziyaret_programi.amac_kategorileri');
    }

    /**
     * Seçili firmanın tarihli ziyaret günleri — Saha Takvimi sayfasıyla AYNI
     * kaynaktan (ZiyaretTakvimi) türetilir, ayrı bir
     * gruplama mantığı tekrarlanmaz; sadece bu firmaya süzülür.
     *
     * @return array<string, array<int, array{firma: Firma, amac: ?string, sure_saat: mixed, durum: string}>>
     */
    #[Computed]
    public function takvimGunler(): array
    {
        if (! $this->firmaId) {
            return [];
        }

        $gunler = [];

        foreach (ZiyaretTakvimi::gunlukGruplar(Filament::auth()->id()) as $tarih => $oGunkuZiyaretler) {
            $buFirmaninkiler = array_values(array_filter(
                $oGunkuZiyaretler,
                fn (array $z) => $z['firma']?->id === $this->firmaId
            ));

            if ($buFirmaninkiler !== []) {
                $gunler[$tarih] = $buFirmaninkiler;
            }
        }

        return $gunler;
    }

    /**
     * Firmanın aynı yıla ait Yıllık Çalışma/Eğitim Planı — yoksa standart
     * şablonla açılır, böylece ziyaret yapılacaklar listesi hep dolu gelir.
     */
    #[Computed]
    public function yillikPlan(): ?YillikPlan
    {
        return $this->firma ? YillikPlan::firmaYilIcin($this->firma, $this->yil) : null;
    }

    /** Seçili ayın ziyaret yapılacakları (yıllık planda o ay P/G olan maddeler). */
    #[Computed]
    public function yapilacaklar(): array
    {
        return $this->yillikPlan?->ayinYapilacaklari($this->yapilacakAy) ?? [];
    }

    /** @return array<int, array{toplam: int, yapilan: int}> ay indeksi => sayılar */
    #[Computed]
    public function aylikYapilacakSayilari(): array
    {
        return collect(range(0, 11))
            ->mapWithKeys(function (int $ay): array {
                $liste = collect($this->yillikPlan?->ayinYapilacaklari($ay) ?? []);

                return [$ay => ['toplam' => $liste->count(), 'yapilan' => $liste->where('gerceklesti', true)->count()]];
            })
            ->all();
    }

    /** Ayı seçer ve "Yapılacaklar" penceresini (modal) açar — uzun liste artık sayfada değil, pencerede. */
    public function yapilacakAySec(int $ay): void
    {
        $this->yapilacakAy = max(0, min(11, $ay));
        $this->yapilacakModalAcik = true;
        unset($this->yapilacaklar);
    }

    public function yapilacakModalKapat(): void
    {
        $this->yapilacakModalAcik = false;
    }

    /** Ajanda gün kartındaki "Yıllık planı aç": firma seçilip 12 aylık plan görünümüne geçilir. */
    public function planAc(int $firmaId): void
    {
        if (! array_key_exists($firmaId, $this->firmalar)) {
            return;
        }

        $this->firmaId = $firmaId;
        $this->gorunum = 'plan';
        $this->updatedFirmaId();
    }

    /**
     * Ayın öne çıkan (kritik) görevleri: o ayın ilk ziyaret satırında "alan:index" olarak en fazla 2 tane saklanır.
     * Satırda kompakt etiket olarak görünür; tüm liste pencerede.
     */
    public function kritikGorevToggle(string $alan, int $index): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! in_array($alan, ['faaliyetler', 'egitimler'], true) || ! isset($aylar[$this->yapilacakAy])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$this->yapilacakAy]);
        $anahtar = $alan.':'.$index;
        $kritik = array_values(array_filter((array) ($girdiler[0]['kritik'] ?? [])));

        if (in_array($anahtar, $kritik, true)) {
            $kritik = array_values(array_diff($kritik, [$anahtar]));
        } elseif (count($kritik) >= self::KRITIK_GOREV_SINIRI) {
            Notification::make()->title('En fazla '.self::KRITIK_GOREV_SINIRI.' öne çıkan görev')->body('Önce birini kaldırın.')->warning()->send();

            return;
        } else {
            $kritik[] = $anahtar;
        }

        $girdiler[0]['kritik'] = $kritik;
        $aylar[$this->yapilacakAy] = $girdiler;
        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->ayKritikGorevleri);
    }

    /**
     * Her ayın seçili öne çıkan görevleri: ay indeksi => [{alan, index, baslik, gerceklesti}].
     *
     * @return array<int, array<int, array{alan: string, index: int, baslik: string, gerceklesti: bool}>>
     */
    #[Computed]
    public function ayKritikGorevleri(): array
    {
        $sonuc = [];

        foreach ($this->program?->ziyaretler ?? [] as $ayIndex => $ay) {
            $kritik = (array) (ZiyaretProgramiModel::ayGirdileri($ay)[0]['kritik'] ?? []);

            if (! $kritik) {
                continue;
            }

            $liste = collect($this->yillikPlan?->ayinYapilacaklari((int) $ayIndex) ?? []);

            foreach ($kritik as $anahtar) {
                [$alan, $index] = array_pad(explode(':', (string) $anahtar, 2), 2, null);
                $madde = $liste->first(fn (array $m) => $m['alan'] === $alan && (string) $m['index'] === (string) $index);

                if ($madde) {
                    $sonuc[(int) $ayIndex][] = ['alan' => $alan, 'index' => (int) $index, 'baslik' => $madde['baslik'], 'gerceklesti' => (bool) $madde['gerceklesti']];
                }
            }
        }

        return $sonuc;
    }

    /** Listeden "Gerçekleşti" işareti — Yıllık Plan'daki ilgili ayın G hücresine yazılır. */
    public function yapilacakGerceklesti(string $alan, int $index): void
    {
        $this->yillikPlan?->gerceklestiDegistir($alan, $index, $this->yapilacakAy);
        unset($this->yillikPlan, $this->yapilacaklar, $this->aylikYapilacakSayilari, $this->ayKritikGorevleri);
    }

    #[Computed]
    public function takvimGosterilenAy(): string
    {
        return $this->takvimAy ?: $this->yil.'-01';
    }

    public function updatedFirmaId(): void
    {
        KomutaFirma::sec($this->firmaId);
        unset($this->firma, $this->yasalDakika, $this->ayKritikGorevleri, $this->program, $this->takvimGunler, $this->yillikPlan, $this->yapilacaklar, $this->aylikYapilacakSayilari);
        $this->takvimAy = null;
    }

    public function updatedYil(): void
    {
        unset($this->ayKritikGorevleri, $this->program, $this->takvimGunler, $this->yillikPlan, $this->yapilacaklar, $this->aylikYapilacakSayilari);
        $this->takvimAy = null;
    }

    public function takvimAyDegistir(int $fark): void
    {
        $this->takvimAy = Carbon::parse($this->takvimGosterilenAy().'-01')->addMonths($fark)->format('Y-m');
    }

    public function takvimGunSec(string $tarih): void
    {
        $this->takvimSeciliTarih = $tarih;
    }

    /*
    |--------------------------------------------------------------------------
    | Ay satırı düzenleme
    |--------------------------------------------------------------------------
    */

    public function ayGuncelle(int $ayIndex, int $satirIndex, string $alan, mixed $deger): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);

        if (! isset($girdiler[$satirIndex])) {
            return;
        }

        $girdiler[$satirIndex][$alan] = $deger;

        // Tarih girilince süre boşsa yasal asgari süre varsayılan olarak yazılır (elle değiştirilebilir).
        if ($alan === 'tarih' && filled($deger) && blank($girdiler[$satirIndex]['sure_saat'] ?? null) && $this->yasalDakika > 0) {
            $girdiler[$satirIndex]['sure_saat'] = YasalZiyaretSuresi::saat($this->yasalDakika);
        }

        $aylar[$ayIndex] = $girdiler;
        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);

        if ($alan === 'tarih' && filled($deger)) {
            $this->takvimAy = Carbon::parse($deger)->format('Y-m');
            $this->takvimSeciliTarih = $deger;
        }
    }

    public function gorunumSec(string $gorunum): void
    {
        if (in_array($gorunum, ['ajanda', 'plan'], true)) {
            $this->gorunum = $gorunum;
        }
    }

    /** Seçili firmanın aylık yasal asgari süresi (dakika): KATİP süresi varsa o, yoksa çalışan × tehlike sınıfı dakikası. */
    #[Computed]
    public function yasalDakika(): int
    {
        return $this->firma ? YasalZiyaretSuresi::dakika($this->firma) : 0;
    }

    /**
     * Yasal süreyi planın tüm aylarının İLK ziyaret satırına yazar — yalnız süresi boş olanlara; elle girilmiş
     * süreye dokunmaz. Süre saat cinsinden saklanır (200 dk → 3.33).
     */
    public function yasalSureyiYaz(): void
    {
        $p = $this->program();

        if (! $p || $this->yasalDakika <= 0) {
            Notification::make()->title('Önce çalışan sayısı olan bir firma seçin')->warning()->send();

            return;
        }

        $aylar = $p->ziyaretler ?? [];
        $yazilan = 0;

        foreach ($aylar as $ayIndex => $ay) {
            $girdiler = ZiyaretProgramiModel::ayGirdileri($ay);

            if (isset($girdiler[0]) && blank($girdiler[0]['sure_saat'] ?? null)) {
                $girdiler[0]['sure_saat'] = YasalZiyaretSuresi::saat($this->yasalDakika);
                $aylar[$ayIndex] = $girdiler;
                $yazilan++;
            }
        }

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);

        Notification::make()->title($yazilan.' ayın süresine '.YasalZiyaretSuresi::etiket($this->yasalDakika).' yazıldı')
            ->body('Elle girilmiş sürelere dokunulmadı.')->success()->send();
    }

    public function durumDegistir(int $ayIndex, int $satirIndex): void
    {
        $this->program()?->durumIlerlet($ayIndex, $satirIndex);
        unset($this->program, $this->takvimGunler);
    }

    public function ziyaretEkle(int $ayIndex): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);
        $girdiler[] = ZiyaretProgramiModel::bosGirdi();
        $aylar[$ayIndex] = $girdiler;

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    public function ziyaretSil(int $ayIndex, int $satirIndex): void
    {
        $p = $this->program();
        $aylar = $p?->ziyaretler ?? [];

        if (! $p || ! isset($aylar[$ayIndex])) {
            return;
        }

        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex]);

        if (count($girdiler) <= 1 || ! isset($girdiler[$satirIndex])) {
            return;
        }

        unset($girdiler[$satirIndex]);
        $aylar[$ayIndex] = array_values($girdiler);

        $p->update(['ziyaretler' => $aylar]);
        unset($this->program, $this->takvimGunler);
    }

    /**
     * Kutular değişince zaten tek tek kaydedilir (ayGuncelle); buton, son
     * düzenlenen kutunun değişikliği işlendikten sonra kaydı teyit eder.
     */
    public function kaydet(): void
    {
        if (! $this->program()) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        unset($this->program, $this->takvimGunler);

        Notification::make()->title('Ziyaret programı kaydedildi')->success()->send();
    }

    public function aiAmacOner(int $ayIndex, int $satirIndex): void
    {
        $p = $this->program();

        if (! $p || ! $this->firma) {
            return;
        }

        $ayAdi = ZiyaretProgramiModel::AYLAR[$ayIndex] ?? null;

        if (! $ayAdi) {
            return;
        }

        $oneri = GeminiZiyaretDanismani::oner($ayAdi, $this->firma->nace_aciklama, $this->firma->tehlikeSinifiEtiketi());

        if ($oneri) {
            $this->ayGuncelle($ayIndex, $satirIndex, 'amac', $oneri);
        } else {
            Notification::make()->title('AI önerisi alınamadı')->warning()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('otomatikDoldur')
                ->label('Boş Ayları Otomatik Doldur')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->visible(fn () => $this->gorunum === 'plan')
                ->requiresConfirmation()
                ->modalHeading('Boş ayları otomatik doldur')
                ->modalDescription('Seçtiğiniz yıl için: elle veya daha önce doldurulmuş aylara DOKUNULMAZ. Hâlâ boş olan aylar; FOM MAKİNA / Orman Müdürlükleri gibi sabit kurallı firmalarda o kurala göre, diğer tüm aktif firmalarda ise İSG-KATİP süresine göre (günde en fazla 10 saat) otomatik doldurulur. Bugünden önceki günlere ziyaret konmaz.')
                ->modalSubmitActionLabel('Doldur')
                ->action(function (): void {
                    $sonuc = ZiyaretOtomatikPlanlayici::calistir($this->yil);

                    unset($this->program);

                    Notification::make()
                        ->title($sonuc['firma'].' firmada '.$sonuc['doldurulan_ay'].' ay dolduruldu')
                        ->body($sonuc['atlanan_ay'].' ay zaten planlıydı, dokunulmadı.'.($sonuc['detay'] ? ' Doldurulan: '.implode(', ', array_slice($sonuc['detay'], 0, 6)).(count($sonuc['detay']) > 6 ? '…' : '.') : ''))
                        ->success()
                        ->send();
                }),
            Action::make('kaydet')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->gorunum === 'plan' && $this->program() !== null)
                ->action(fn () => $this->kaydet()),
            Action::make('pdf')
                ->label('Çıktı İndir (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->gorunum === 'plan' && $this->program() !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => ZiyaretProgramiUretici::pdf($this->program())),
        ];
    }
}
