<?php

namespace App\Filament\Widgets;

use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami;
use App\Support\ZiyaretTakvimi;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

/**
 * Panel ana sayfası — bu ayın ziyaret takvimi + o ay gidilmesi planlanan
 * firmaların toplu listesi. Kaynak ZiyaretTakvimi::gunlukGruplar() ile
 * Profilim > Firma Ziyaretleri ve Ziyaret Programı sayfalarıyla AYNIdır,
 * ayrı bir sorgu/gruplama tekrarlanmaz.
 */
class BuAyZiyaretlerWidget extends Widget
{
    protected string $view = 'filament.widgets.bu-ay-ziyaretler-widget';

    protected int|string|array $columnSpan = 'full';

    public string $gosterilenAy = '';

    public ?string $seciliTarih = null;

    public function mount(): void
    {
        $this->gosterilenAy = now()->format('Y-m');
    }

    #[Computed]
    public function gunlukGruplar(): array
    {
        return ZiyaretTakvimi::gunlukGruplar(Filament::auth()->id());
    }

    /**
     * Gösterilen aya ait ziyaretler, tarihe göre sıralı — bir gün seçiliyse
     * sadece o güne süzülür, aksi halde ayın tamamı listelenir.
     *
     * @return array<int, array{firma: mixed, amac: ?string, sure_saat: mixed, durum: string, program_id: int, ay_index: int, satir_index: int, tarih: string}>
     */
    #[Computed]
    public function ayinZiyaretleri(): array
    {
        $sonuc = [];

        foreach ($this->gunlukGruplar as $tarih => $oGunkuZiyaretler) {
            if (! str_starts_with($tarih, $this->gosterilenAy)) {
                continue;
            }

            if ($this->seciliTarih && $tarih !== $this->seciliTarih) {
                continue;
            }

            foreach ($oGunkuZiyaretler as $z) {
                $sonuc[] = $z + ['tarih' => $tarih];
            }
        }

        usort($sonuc, fn (array $a, array $b) => $a['tarih'] <=> $b['tarih']);

        return $sonuc;
    }

    /**
     * Listelenen her ziyaret için yıllık plandan o ayın yapılacak sayısı —
     * yalnız mevcut planlar okunur (widget plan oluşturmaz).
     *
     * @return array<string, array{toplam: int, yapilan: int}> "firmaId-yil-ay" => sayılar
     */
    #[Computed]
    public function yapilacakSayilari(): array
    {
        $anahtarlar = collect($this->ayinZiyaretleri)
            ->filter(fn (array $z) => $z['firma'])
            ->map(fn (array $z) => [$z['firma']->id, (int) substr($z['tarih'], 0, 4), $z['ay_index']])
            ->unique(fn (array $k) => implode('-', $k));

        if ($anahtarlar->isEmpty()) {
            return [];
        }

        $planlar = YillikPlan::query()
            ->whereIn('firma_id', $anahtarlar->pluck(0)->unique())
            ->whereIn('yil', $anahtarlar->pluck(1)->unique())
            ->get()
            ->keyBy(fn (YillikPlan $p) => $p->firma_id.'-'.$p->yil);

        return $anahtarlar
            ->mapWithKeys(function (array $k) use ($planlar): array {
                $liste = collect($planlar->get($k[0].'-'.$k[1])?->ayinYapilacaklari($k[2]) ?? []);

                return [implode('-', $k) => ['toplam' => $liste->count(), 'yapilan' => $liste->where('gerceklesti', true)->count()]];
            })
            ->all();
    }

    /**
     * Gösterilen ayda gidilmesi gereken firmaların yüzde kaçına gidildi —
     * Ziyaret Programı'nda o ay tarihi girilmiş firmalar "gidilecek", en az bir
     * ziyareti "Tamamlandı" olanlar "gidildi" sayılır (firma başına bir kez).
     * Takvim yüzdesi: ayın geçen kısmı (geçmiş ay 100, gelecek ay 0) — geride mi
     * ileride mi kıyası için.
     *
     * @return array{toplam: int, gidilen: int, yuzde: ?int, takvim_yuzde: int, firmalar: array<int, array{firma: string, firma_id: int, gidildi: bool}>}
     */
    #[Computed]
    public function ziyaretOzeti(): array
    {
        $ayBasi = Carbon::parse($this->gosterilenAy.'-01');

        $firmalar = collect($this->gunlukGruplar)
            ->filter(fn ($_, string $tarih) => str_starts_with($tarih, $this->gosterilenAy))
            ->flatten(1)
            ->filter(fn (array $z) => $z['firma'])
            ->groupBy(fn (array $z) => $z['firma']->id)
            ->map(fn ($ziyaretler) => [
                'firma' => (string) $ziyaretler->first()['firma']->unvan,
                'firma_id' => $ziyaretler->first()['firma']->id,
                'gidildi' => $ziyaretler->contains('durum', 'tamamlandi'),
            ])
            ->sortBy([['gidildi', 'asc'], ['firma', 'asc']])
            ->values();

        $toplam = $firmalar->count();
        $gidilen = $firmalar->where('gidildi', true)->count();

        $bugun = now();
        $takvim = match (true) {
            $ayBasi->copy()->endOfMonth()->lt($bugun) => 100,
            $ayBasi->gt($bugun) => 0,
            default => (int) round($bugun->day * 100 / $ayBasi->daysInMonth),
        };

        return [
            'toplam' => $toplam,
            'gidilen' => $gidilen,
            'yuzde' => $toplam ? (int) round($gidilen * 100 / $toplam) : null,
            'takvim_yuzde' => $takvim,
            'firmalar' => $firmalar->all(),
        ];
    }

    public function ayDegistir(int $fark): void
    {
        $this->gosterilenAy = Carbon::parse($this->gosterilenAy.'-01')->addMonths($fark)->format('Y-m');
        $this->seciliTarih = null;
        $this->yenile();
    }

    public function gunSec(string $tarih): void
    {
        $this->seciliTarih = $this->seciliTarih === $tarih ? null : $tarih;
        unset($this->ayinZiyaretleri);
    }

    /** Takvimi bu aya alır ve bugünü seçer. */
    public function bugun(): void
    {
        $this->gosterilenAy = now()->format('Y-m');
        $this->seciliTarih = now()->toDateString();
        $this->yenile();
    }

    public function tumunuGoster(): void
    {
        $this->seciliTarih = null;
        unset($this->ayinZiyaretleri);
    }

    public function durumDegistir(int $programId, int $ayIndex, int $satirIndex): void
    {
        ZiyaretProgrami::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($programId)
            ?->durumIlerlet($ayIndex, $satirIndex);

        unset($this->gunlukGruplar);
        $this->yenile();
    }

    private function yenile(): void
    {
        unset($this->ayinZiyaretleri, $this->ziyaretOzeti, $this->ayOzeti, $this->sureEksikleri);
    }

    /** Planlanmış ama tarihi geçmiş ve tamamlanmamış ziyaret. */
    public static function gecikmisMi(array $z): bool
    {
        return ($z['durum'] ?? 'bos') !== 'tamamlandi' && ($z['tarih'] ?? '9999') < now()->toDateString();
    }

    /**
     * Gösterilen ayın ziyaret sayaçları (isgsuite "Saha Takvimi"): tarihli tüm
     * ziyaretler; tamamlanan; tarihi gelmemiş planlı; tarihi geçip
     * tamamlanmamış (gecikmiş).
     *
     * @return array{toplam: int, planli: int, tamamlanan: int, gecikmis: int}
     */
    #[Computed]
    public function ayOzeti(): array
    {
        $liste = collect($this->gunlukGruplar)
            ->filter(fn ($_, string $tarih) => str_starts_with($tarih, $this->gosterilenAy))
            ->flatMap(fn (array $gun, string $tarih) => collect($gun)->map(fn (array $z) => $z + ['tarih' => $tarih]));

        $tamamlanan = $liste->where('durum', 'tamamlandi')->count();
        $gecikmis = $liste->filter(fn (array $z) => static::gecikmisMi($z))->count();

        return [
            'toplam' => $liste->count(),
            'planli' => $liste->count() - $tamamlanan - $gecikmis,
            'tamamlanan' => $tamamlanan,
            'gecikmis' => $gecikmis,
        ];
    }

    /**
     * Eksik saha süresi — aktif firmalarda gereken aylık İGU süresi (çalışan ×
     * tehlike sınıfı dakikası) ile o ay tamamlanan + planlanan ziyaret
     * sürelerinin farkı. Yalnız eksiği olan firmalar döner; "plan_var" = ayın
     * kalan günlerinde tamamlanmamış ziyaret var mı.
     *
     * @return array<int, array{firma_id: int, firma: string, calisan: int, gerekli: int, yapilan: int, planli: int, eksik: int, plan_var: bool}>
     */
    #[Computed]
    public function sureEksikleri(): array
    {
        $bugun = now()->toDateString();
        $ziyaretler = collect($this->gunlukGruplar)
            ->filter(fn ($_, string $tarih) => str_starts_with($tarih, $this->gosterilenAy))
            ->flatMap(fn (array $gun, string $tarih) => collect($gun)->map(fn (array $z) => $z + ['tarih' => $tarih]))
            ->filter(fn (array $z) => $z['firma'])
            ->groupBy(fn (array $z) => $z['firma']->id);

        $dk = fn ($liste) => (int) round(collect($liste)->sum(fn (array $z) => (float) str_replace(',', '.', (string) ($z['sure_saat'] ?? 0))) * 60);

        return \App\Models\Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->where('aktif', true)
            ->where(fn ($q) => $q->where('calisan_sayisi', '>', 0)->orWhere('katip_aylik_dk', '>', 0))
            ->orderBy('unvan')
            ->get(['id', 'unvan', 'calisan_sayisi', 'tehlike_sinifi', 'katip_aylik_dk'])
            ->map(function (\App\Models\Firma $f) use ($ziyaretler, $dk, $bugun): array {
                $z = $ziyaretler->get($f->id, collect());
                $gerekli = $f->iguAylikDk();
                $yapilan = $dk($z->where('durum', 'tamamlandi'));
                $planli = $dk($z->where('durum', '!=', 'tamamlandi')->filter(fn (array $x) => $x['tarih'] >= $bugun));

                return [
                    'firma_id' => $f->id,
                    'firma' => $f->unvan,
                    'calisan' => (int) $f->calisan_sayisi,
                    'gerekli' => $gerekli,
                    'yapilan' => $yapilan,
                    'planli' => $planli,
                    'eksik' => max(0, $gerekli - $yapilan - $planli),
                    'plan_var' => $z->contains(fn (array $x) => $x['durum'] !== 'tamamlandi' && $x['tarih'] >= $bugun),
                ];
            })
            ->filter(fn (array $s) => $s['eksik'] > 0)
            ->sortByDesc('eksik')
            ->values()
            ->all();
    }
}
