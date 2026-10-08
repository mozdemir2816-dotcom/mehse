<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HizliArsivYukleme;
use App\Filament\Concerns\SahaHizliIslemleri;
use App\Filament\Concerns\SinirliErisim;
use App\Models\ArsivDosya;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\SahaAnalizi;
use App\Models\SahaBulgusu;
use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami as ZiyaretProgramiModel;
use App\Support\ArsivKurali;
use App\Support\GorevDurumu;
use App\Support\KullaniciAyarlari;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

/**
 * Ziyaret Modu (kullanıcı isteği 04.10.2026: "sahadaki eksikleri görerek o
 * firmanın ziyaretinde tamamlamak") — telefonda firmaya girince tek ekran:
 * bu ayın yıllık plan maddeleri (gerçekleşti işareti), OSGB arşivindeki eksik
 * evraklar (📷 tek dokunuşla yükleme), açık saha bulguları ve DÖF maddeleri
 * (fotoğrafla "giderildi"), süresi geçen eğitim / sağlık / kontrol işleri,
 * hızlı aksiyonlar ve "Ziyareti Bitir" (ziyaret programına işler, özet paylaşılır).
 * Bugün ziyaret programında planlı tek firma varsa kendiliğinden açılır.
 */
class ZiyaretModu extends Page
{
    use HizliArsivYukleme;
    use SahaHizliIslemleri;
    use SinirliErisim;

    protected string $view = 'filament.pages.ziyaret-modu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Kontrolleri';

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'ziyaret';

    protected static ?string $title = 'Ziyaret Modu';

    protected static ?string $navigationLabel = 'Ziyaret Modu (Bugün)';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** @var array<string, mixed> "b12" / "d5_2" → kapanış fotoğrafı */
    public array $kapanisFoto = [];

    /** @var array<string, string> "b12" / "d5_2" → kapanış notu */
    public array $kapanisNot = [];

    public ?string $acikKapanis = null;

    public bool $ozetAcik = false;

    public function mount(): void
    {
        if ($this->firmaId && ! array_key_exists($this->firmaId, $this->firmalar)) {
            $this->firmaId = null;
        }

        $this->firmaId ??= session('ziyaret_firma') && array_key_exists((int) session('ziyaret_firma'), $this->firmalar) && $this->bugunPlanli->isEmpty()
            ? (int) session('ziyaret_firma')
            : ($this->bugunPlanli->count() === 1 ? $this->bugunPlanli->first()['firma_id'] : null);

        $this->firmaSecildi();
    }

    public function updatedFirmaId(): void
    {
        $this->firmaSecildi();
        $this->yenile();
    }

    private function firmaSecildi(): void
    {
        if ($this->firmaId) {
            // Alt menüdeki Saha Gözlem / Arşiv kısayolları bu firmayla açılır.
            session(['ziyaret_firma' => $this->firmaId]);
        }
    }

    private function yenile(): void
    {
        unset($this->firma, $this->planMaddeleri, $this->arsivDurumu, $this->acikBulgular, $this->acikDofler, $this->gorevler, $this->bugunYapilanlar, $this->bugunkuZiyaret, $this->aktifIzinler, $this->sonRamakKalalar);
    }

    protected function hizliArsivFirmasi(): ?Firma
    {
        return $this->firma;
    }

    protected function hizliArsivKaydedildi(ArsivDosya $d): void
    {
        $this->yenile();
    }

    /*
    |--------------------------------------------------------------------------
    | Veri
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** @return Collection<int, array{firma_id: int, unvan: string, amac: ?string, durum: string}> bugün ziyaret programında planlı firmalar */
    #[Computed]
    public function bugunPlanli(): Collection
    {
        $bugun = Carbon::today();

        return ZiyaretProgramiModel::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->where('yil', $bugun->year)
            ->get()
            ->flatMap(fn (ZiyaretProgramiModel $p) => collect(ZiyaretProgramiModel::ayGirdileri(($p->ziyaretler ?? [])[$bugun->month - 1] ?? null))
                ->filter(fn ($g) => filled($g['tarih'] ?? null) && Carbon::parse($g['tarih'])->isSameDay($bugun))
                ->map(fn ($g) => ['firma_id' => $p->firma_id, 'unvan' => $this->firmalar[$p->firma_id], 'amac' => $g['amac'] ?? null, 'durum' => $g['durum'] ?? 'planlandi']))
            ->unique('firma_id')
            ->values();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::query()->where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    /** @return array{ay: int, maddeler: array<int, array<string, mixed>>, plan_var: bool} bu ayın yıllık plan maddeleri */
    #[Computed]
    public function planMaddeleri(): array
    {
        $ay = (int) now()->month - 1;
        $plan = $this->firma ? YillikPlan::query()->where('firma_id', $this->firma->id)->where('yil', now()->year)->first() : null;

        return ['ay' => $ay, 'maddeler' => $plan?->ayinYapilacaklari($ay) ?? [], 'plan_var' => (bool) $plan];
    }

    /** @return array<string, array<string, mixed>> takip edilen OSGB arşiv evraklarının durumu */
    #[Computed]
    public function arsivDurumu(): array
    {
        if (! $this->firma) {
            return [];
        }

        $kayitlar = ArsivDosya::query()->where('firma_id', $this->firma->id)->get()
            ->groupBy(fn (ArsivDosya $d) => ArsivKurali::kategori($d->kategori)['anahtar']);
        $haric = KullaniciAyarlari::arsivHaric();

        return collect(config('arsiv.kategoriler'))
            ->filter(fn ($k, $a) => $k['grup'] === config('arsiv.takip_grubu') && ! in_array($a, $haric, true))
            ->map(fn ($k, $a) => ArsivKurali::durum($this->firma, $a, $kayitlar->get($a, collect()), null, $haric) + ['ad' => $k['ad'], 'ikon' => $k['ikon']])
            ->all();
    }

    /** @return Collection<int, SahaBulgusu> */
    #[Computed]
    public function acikBulgular(): Collection
    {
        return $this->firma
            ? SahaBulgusu::query()->where('firma_id', $this->firma->id)->whereIn('durum', ['acik', 'devam_ediyor'])->orderByRaw('termin is null')->orderBy('termin')->get()
            : collect();
    }

    /**
     * Saha bulgusuna bağlanmamış (eski) açık DÖF maddeleri — bağlı olanlar
     * bulgu olarak listelenir, çift görünmez (BulguHavuzu, 08.10.2026).
     *
     * @return Collection<int, array{rapor_id: int, index: int, belge_no: ?string, madde: array<string, mixed>}>
     */
    #[Computed]
    public function acikDofler(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return DofRaporu::query()->where('firma_id', $this->firma->id)->get()
            ->flatMap(fn (DofRaporu $r) => collect($r->maddeler ?? [])
                ->map(fn ($m, $i) => ['rapor_id' => $r->id, 'index' => $i, 'belge_no' => $r->belge_no, 'madde' => $m])
                ->filter(fn ($s) => ($s['madde']['durum'] ?? 'acik') !== 'tamamlandi' && empty($s['madde']['bulgu_id'])))
            ->sortBy(fn ($s) => $s['madde']['termin'] ?? '9999-12-31')
            ->values();
    }

    /** @return array<int, array<string, mixed>> eğitim / sağlık / periyodik kontrol / KKD / ölçüm (DÖF ayrı listede) */
    #[Computed]
    public function gorevler(): array
    {
        return $this->firma
            ? collect(GorevDurumu::tarihliGorevler($this->firma))->reject(fn ($g) => $g['baslik'] === 'DÖF')->values()->all()
            : [];
    }

    /** @return array<string, int|Collection> bugün bu firmada yapılanlar (özet için) */
    #[Computed]
    public function bugunYapilanlar(): array
    {
        if (! $this->firma) {
            return [];
        }

        $bugun = Carbon::today()->toDateString();

        return [
            'arsiv' => ArsivDosya::query()->where('firma_id', $this->firma->id)->whereDate('created_at', $bugun)->get(),
            'bulgu_kapanan' => SahaBulgusu::query()->where('firma_id', $this->firma->id)->whereDate('kapanis_tarihi', $bugun)->count(),
            'bulgu_yeni' => SahaBulgusu::query()->where('firma_id', $this->firma->id)->whereDate('created_at', $bugun)->count(),
            'dof_kapanan' => DofRaporu::query()->where('firma_id', $this->firma->id)->get()
                // Bulguya bağlı maddenin kapanışı bulgu_kapanan'da sayılır
                ->sum(fn (DofRaporu $r) => collect($r->maddeler ?? [])->where('kapatma_tarihi', $bugun)->filter(fn ($m) => empty($m['bulgu_id']))->count()),
            'gozlem' => SahaAnalizi::query()->where('firma_id', $this->firma->id)->whereDate('created_at', $bugun)->count(),
        ];
    }

    /** @return ?array{ay: int, sira: int, girdi: array<string, mixed>} ziyaret programında bugünün girdisi */
    #[Computed]
    public function bugunkuZiyaret(): ?array
    {
        if (! $this->firma) {
            return null;
        }

        $bugun = Carbon::today();
        $p = ZiyaretProgramiModel::query()->where('firma_id', $this->firma->id)->where('yil', $bugun->year)->first();

        foreach (ZiyaretProgramiModel::ayGirdileri(($p?->ziyaretler ?? [])[$bugun->month - 1] ?? null) as $sira => $g) {
            if (filled($g['tarih'] ?? null) && Carbon::parse($g['tarih'])->isSameDay($bugun)) {
                return ['ay' => $bugun->month - 1, 'sira' => $sira, 'girdi' => $g];
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | İşlemler
    |--------------------------------------------------------------------------
    */

    public function firmaSec(int $id): void
    {
        $this->firmaId = array_key_exists($id, $this->firmalar) ? $id : null;
        $this->updatedFirmaId();
    }

    public function planMaddesiIsaretle(string $alan, int $index): void
    {
        $plan = $this->firma ? YillikPlan::query()->where('firma_id', $this->firma->id)->where('yil', now()->year)->first() : null;
        $plan?->gerceklestiDegistir($alan, $index, (int) now()->month - 1);
        unset($this->planMaddeleri);
    }

    public function kapanisAc(?string $anahtar): void
    {
        $this->acikKapanis = $this->acikKapanis === $anahtar ? null : $anahtar;
    }

    private function kapanisFotografi(string $anahtar, string $klasor): ?string
    {
        $foto = $this->kapanisFoto[$anahtar] ?? null;

        return $foto instanceof TemporaryUploadedFile ? $foto->store($klasor, 'public') : null;
    }

    /** Saha bulgusunu sahada "giderildi" olarak kapatır (isteğe bağlı fotoğraf + not). */
    public function bulguKapat(int $id): void
    {
        $b = $this->firma ? SahaBulgusu::query()->where('firma_id', $this->firma->id)->find($id) : null;

        if (! $b) {
            return;
        }

        $anahtar = 'b'.$id;
        $foto = $this->kapanisFotografi($anahtar, 'saha-bulgu');

        $b->update([
            'durum' => 'kapandi',
            'kapanis_tarihi' => Carbon::today()->toDateString(),
            'kapanis_notu' => trim((string) ($this->kapanisNot[$anahtar] ?? '')) ?: 'Ziyarette yerinde kontrol edildi, giderildi.',
            'fotograflar' => $foto ? [...($b->fotograflar ?? []), $foto] : $b->fotograflar,
        ]);

        unset($this->kapanisFoto[$anahtar], $this->kapanisNot[$anahtar]);
        $this->acikKapanis = null;
        unset($this->acikBulgular, $this->bugunYapilanlar);
        Notification::make()->title('Bulgu kapatıldı')->success()->send();
    }

    /** DÖF maddesini sahada tamamlandı yapar (isteğe bağlı fotoğraf + not). */
    public function dofKapat(int $raporId, int $index): void
    {
        $r = $this->firma ? DofRaporu::query()->where('firma_id', $this->firma->id)->find($raporId) : null;

        if (! $r || ! isset($r->maddeler[$index])) {
            return;
        }

        $anahtar = 'd'.$raporId.'_'.$index;
        $maddeler = $r->maddeler;
        $maddeler[$index]['durum'] = 'tamamlandi';
        $maddeler[$index]['kapatma_tarihi'] = Carbon::today()->toDateString();
        $maddeler[$index]['kapatma_notu'] = trim((string) ($this->kapanisNot[$anahtar] ?? '')) ?: 'Ziyarette yerinde kontrol edildi, tamamlandı.';

        if ($foto = $this->kapanisFotografi($anahtar, 'dof-foto')) {
            $maddeler[$index]['kapatma_foto'] = $foto;
        }

        $r->update(['maddeler' => $maddeler]);

        unset($this->kapanisFoto[$anahtar], $this->kapanisNot[$anahtar]);
        $this->acikKapanis = null;
        unset($this->acikDofler, $this->bugunYapilanlar);
        Notification::make()->title('DÖF maddesi tamamlandı')->success()->send();
    }

    /** Ziyareti ziyaret programına "tamamlandı" işler ve özeti açar. */
    public function ziyaretiBitir(): void
    {
        if (! $this->firma) {
            return;
        }

        $bugun = Carbon::today();
        $p = ZiyaretProgramiModel::firmaYilIcin($this->firma, $bugun->year);
        $aylar = $p->ziyaretler ?? [];
        $ayIndex = $bugun->month - 1;
        $girdiler = ZiyaretProgramiModel::ayGirdileri($aylar[$ayIndex] ?? null);

        $sira = collect($girdiler)->search(fn ($g) => filled($g['tarih'] ?? null) && Carbon::parse($g['tarih'])->isSameDay($bugun));
        // Bugüne tarih verilmemişse, bu ayın tarihsiz planlı / boş ilk satırı kullanılır; yoksa yeni satır.
        $sira = $sira !== false ? $sira : collect($girdiler)->search(fn ($g) => blank($g['tarih'] ?? null));

        if ($sira === false) {
            $girdiler[] = ZiyaretProgramiModel::bosGirdi();
            $sira = array_key_last($girdiler);
        }

        $girdiler[$sira] = array_merge(ZiyaretProgramiModel::bosGirdi(), $girdiler[$sira], [
            'tarih' => $bugun->toDateString(),
            'durum' => 'tamamlandi',
            'amac' => $girdiler[$sira]['amac'] ?? null ?: 'Saha ziyareti',
        ]);
        $aylar[$ayIndex] = array_values($girdiler);
        $p->update(['ziyaretler' => $aylar]);

        unset($this->bugunkuZiyaret, $this->bugunPlanli);
        $this->ozetAcik = true;
        Notification::make()->title('Ziyaret tamamlandı olarak işlendi')->body('Ziyaret Programı\'na yazıldı.')->success()->send();
    }

    /** Ziyaret özeti (WhatsApp / paylaşım metni). Görevli adı yazılmaz. */
    public function ozetMetni(): string
    {
        if (! $this->firma) {
            return '';
        }

        $y = $this->bugunYapilanlar;
        $plan = collect($this->planMaddeleri['maddeler']);
        $satir = fn (string $s) => '• '.$s;

        $yapilanlar = array_filter([
            $plan->where('gerceklesti', true)->isNotEmpty() ? $satir('Yıllık plan: '.$plan->where('gerceklesti', true)->pluck('baslik')->implode(', ')) : null,
            $y['gozlem'] ? $satir('Saha gözlem raporu: '.$y['gozlem']) : null,
            $y['bulgu_yeni'] ? $satir('Yeni tespit: '.$y['bulgu_yeni']) : null,
            $y['bulgu_kapanan'] + $y['dof_kapanan'] ? $satir('Giderilen uygunsuzluk: '.($y['bulgu_kapanan'] + $y['dof_kapanan'])) : null,
            $y['arsiv']->isNotEmpty() ? $satir('Arşive eklenen: '.$y['arsiv']->map(fn ($d) => $d->kategoriEtiketi())->unique()->implode(', ')) : null,
        ]);

        $eksik = collect($this->arsivDurumu)->filter(fn ($d) => in_array($d['durum'], ['eksik', 'gecikmis'], true))->pluck('ad');
        $kalanlar = array_filter([
            $plan->where('gerceklesti', false)->isNotEmpty() ? $satir('Bu ayın plan maddeleri: '.$plan->where('gerceklesti', false)->pluck('baslik')->implode(', ')) : null,
            $this->acikBulgular->count() + $this->acikDofler->count() ? $satir('Açık uygunsuzluk: '.($this->acikBulgular->count() + $this->acikDofler->count())) : null,
            $eksik->isNotEmpty() ? $satir('Eksik evrak: '.$eksik->implode(', ')) : null,
            ...collect($this->gorevler)->where('durum', 'gecikmis')->map(fn ($g) => $satir($g['baslik'].': '.$g['aciklama']))->all(),
        ]);

        return trim(implode("\n", [
            '*'.$this->firma->unvan.'*',
            'İSG ziyaret özeti — '.Carbon::today()->format('d.m.Y'),
            '',
            '✅ Yapılanlar',
            $yapilanlar ? implode("\n", $yapilanlar) : '• —',
            '',
            '⚠️ Kalan eksikler',
            $kalanlar ? implode("\n", $kalanlar) : '• Eksik yok',
        ]));
    }
}
