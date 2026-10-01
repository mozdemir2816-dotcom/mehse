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
        unset($this->ayinZiyaretleri, $this->ziyaretOzeti);
    }

    public function gunSec(string $tarih): void
    {
        $this->seciliTarih = $this->seciliTarih === $tarih ? null : $tarih;
        unset($this->ayinZiyaretleri);
    }

    public function durumDegistir(int $programId, int $ayIndex, int $satirIndex): void
    {
        ZiyaretProgrami::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($programId)
            ?->durumIlerlet($ayIndex, $satirIndex);

        unset($this->gunlukGruplar, $this->ayinZiyaretleri, $this->ziyaretOzeti);
    }
}
