<?php

namespace App\Filament\Pages;

use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\KazaIstatistigi;
use App\Models\OlayKaydi;
use App\Support\OlayKomuta;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

/**
 * Olay Komuta 360° — yönetici gözüyle olay panosu. Olay Kayıtları, DÖF ve Kaza
 * İstatistikleri verisinden: pano, analitik, vücut haritası, kök neden, DÖF
 * takibi ve aylık/yıllık performans raporu. Veri girişi Olay Kayıtları'ndadır.
 */
class OlayKomuta360 extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.olay-komuta-360';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Sağlık & Olaylar';

    /** Menüde gizli — Sağlık & Olay merkezinin sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 95;

    protected static ?string $slug = 'olay-komuta-360';

    protected static ?string $title = 'Olay Komuta 360°';

    protected static ?string $navigationLabel = 'Olay Komuta 360°';

    public const SEKMELER = [
        'pano' => 'Yönetici Panosu',
        'analitik' => 'Olay Analitiği',
        'vucut' => 'Vücut Haritası',
        'kok_neden' => 'Kök Neden',
        'dof' => 'DÖF Takibi',
        'rapor' => 'Aylık & Yıllık Rapor',
    ];

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    #[Url(as: 'yil')]
    public int $yil = 0;

    #[Url(as: 'ay')]
    public int $ay = 0;

    #[Url(as: 'bolum')]
    public string $bolum = '';

    #[Url(as: 'sekme')]
    public string $sekme = 'pano';

    #[Url(as: 'bolge')]
    public string $bolge = '';

    #[Url(as: 'olay')]
    public ?int $incelenenOlayId = null;

    public string $arama = '';

    public string $raporModu = 'aylik';

    public function mount(): void
    {
        if (! array_key_exists($this->sekme, self::SEKMELER)) {
            $this->sekme = 'pano';
        }
        // Varsayılan: içinde bulunulan yıl (veri yoksa tüm yıllar).
        if (! request()->has('yil')) {
            $buYil = (int) now()->year;
            $this->yil = $this->tumOlaylar()->contains(fn ($o) => $o->olay_tarihi?->year === $buYil) ? $buYil : 0;
        }
    }

    public function sekmeAc(string $sekme): void
    {
        if (array_key_exists($sekme, self::SEKMELER)) {
            $this->sekme = $sekme;
        }
    }

    public function bolgeSec(string $bolge): void
    {
        $this->bolge = $this->bolge === $bolge ? '' : $bolge;
    }

    public function olayInceleyin(int $id): void
    {
        $this->incelenenOlayId = $id;
        $this->sekme = 'kok_neden';
    }

    public function filtreleriTemizle(): void
    {
        $this->firmaId = null;
        $this->yil = 0;
        $this->ay = 0;
        $this->bolum = '';
        $this->bolge = '';
        $this->arama = '';
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

    /** Kullanıcının firmalarındaki tüm olaylar (zaman/bölüm filtresi öncesi). */
    private function tumOlaylar(): Collection
    {
        return OlayKaydi::query()
            ->whereIn('firma_id', Firma::query()->where('user_id', Filament::auth()->id())->select('id'))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->with('firma')
            ->orderByDesc('olay_tarihi')
            ->get();
    }

    #[Computed]
    public function firmaOlaylari(): Collection
    {
        return $this->tumOlaylar();
    }

    /** Yıl / ay / bölüm filtreli olaylar. */
    #[Computed]
    public function olaylar(): Collection
    {
        return $this->firmaOlaylari->filter(function (OlayKaydi $o) {
            if ($this->yil && $o->olay_tarihi?->year !== $this->yil) {
                return false;
            }
            if ($this->ay && $o->olay_tarihi?->month !== $this->ay) {
                return false;
            }
            if ($this->bolum !== '' && trim((string) $o->bolum) !== $this->bolum) {
                return false;
            }

            return true;
        })->values();
    }

    /** Tablo için: vücut bölgesi + arama uygulanmış liste. */
    #[Computed]
    public function listelenenOlaylar(): Collection
    {
        $ara = mb_strtolower(trim($this->arama));

        return $this->olaylar
            ->when($this->bolge !== '', fn ($c) => $c->where('yaralanan_bolge', $this->bolge))
            ->when($ara !== '', fn ($c) => $c->filter(fn (OlayKaydi $o) => str_contains(
                mb_strtolower($o->belge_no.' '.$o->olay_ozeti.' '.$o->olay_yeri.' '.$o->bolum.' '.$o->etkilenen_ad_soyad),
                $ara,
            )))
            ->values();
    }

    #[Computed]
    public function bolumler(): array
    {
        return $this->firmaOlaylari->pluck('bolum')->map(fn ($b) => trim((string) $b))->filter()->unique()->sort()->values()->all();
    }

    #[Computed]
    public function yillar(): array
    {
        return $this->firmaOlaylari->map(fn ($o) => $o->olay_tarihi?->year)->filter()->unique()->sortDesc()->values()->all();
    }

    #[Computed]
    public function ozet(): array
    {
        return OlayKomuta::ozet($this->olaylar);
    }

    #[Computed]
    public function aylik(): array
    {
        return OlayKomuta::aylik($this->olaylar);
    }

    #[Computed]
    public function siniflandirma(): array
    {
        return OlayKomuta::siniflandirma($this->olaylar);
    }

    #[Computed]
    public function turler(): array
    {
        return OlayKomuta::turDagilimi($this->olaylar);
    }

    #[Computed]
    public function siddetler(): array
    {
        return OlayKomuta::siddetDagilimi($this->olaylar);
    }

    #[Computed]
    public function bolgeVerisi(): array
    {
        return OlayKomuta::bolgeler($this->olaylar);
    }

    #[Computed]
    public function kokNedenler(): array
    {
        return OlayKomuta::kokNedenler($this->olaylar);
    }

    #[Computed]
    public function karne(): array
    {
        return OlayKomuta::bolumKarnesi($this->olaylar);
    }

    #[Computed]
    public function yillikTablo(): array
    {
        return OlayKomuta::yillik($this->firmaOlaylari);
    }

    #[Computed]
    public function incelenenOlay(): ?OlayKaydi
    {
        return $this->incelenenOlayId
            ? $this->olaylar->firstWhere('id', $this->incelenenOlayId)
            : null;
    }

    /** Seçili yıl (yoksa tüm yıllar) için girilmiş toplam çalışma saati + çalışan sayısı. */
    #[Computed]
    public function calisma(): array
    {
        $firmaIdleri = $this->firmaId ? [$this->firmaId] : array_keys($this->firmalar);
        $kayitlar = KazaIstatistigi::query()
            ->whereIn('firma_id', $firmaIdleri)
            ->when($this->yil, fn ($q) => $q->where('yil', $this->yil))
            ->get();

        return [
            'saat' => (int) $kayitlar->sum(fn (KazaIstatistigi $k) => $k->toplamCalismaSaati()),
            'calisan' => (int) $kayitlar->sum(fn (KazaIstatistigi $k) => $k->toplamOrtalamaCalisan()),
        ];
    }

    #[Computed]
    public function oranlar(): array
    {
        return OlayKomuta::oranlar($this->ozet, $this->calisma['saat']);
    }

    /** DÖF maddeleri (firma filtresine uygun) + kapanış / gecikme özeti. */
    #[Computed]
    public function dofMaddeleri(): Collection
    {
        $bugun = now()->startOfDay();

        return DofRaporu::query()
            ->whereIn('firma_id', Firma::query()->where('user_id', Filament::auth()->id())->select('id'))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->with('firma')
            ->get()
            ->flatMap(fn (DofRaporu $r) => collect($r->maddeler ?? [])->map(function (array $m) use ($r, $bugun) {
                $termin = filled($m['termin'] ?? null) ? \Illuminate\Support\Carbon::parse($m['termin'])->startOfDay() : null;
                $acik = ($m['durum'] ?? null) !== 'tamamlandi';

                return [
                    'firma' => $r->firma?->unvan ?? '—',
                    'belge_no' => $r->belge_no,
                    'baslik' => (string) ($m['tespit'] ?? $m['baslik'] ?? 'DÖF maddesi'),
                    'oncelik' => (string) ($m['oncelik'] ?? 'orta'),
                    'sorumlu' => $r->maddeSorumlusu($m),
                    'termin' => $termin,
                    'acik' => $acik,
                    'geciken' => $acik && $termin && $termin->lt($bugun),
                ];
            }))
            ->values();
    }

    #[Computed]
    public function dofOzeti(): array
    {
        $m = $this->dofMaddeleri;
        $toplam = $m->count();
        $kapanan = $m->where('acik', false)->count();

        return [
            'toplam' => $toplam,
            'acik' => $m->where('acik', true)->count(),
            'geciken' => $m->where('geciken', true)->count(),
            'kapanan' => $kapanan,
            'oran' => $toplam ? (int) round($kapanan / $toplam * 100) : 0,
            'oncelik' => $m->where('acik', true)->countBy('oncelik')->all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('yazdir')
                ->label('Yazdır')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Action::make('excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->action(fn () => $this->excelIndir()),
        ];
    }

    public function excelIndir()
    {
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Olaylar');
        $s->fromArray(['Belge No', 'Firma', 'Tarih', 'Bölüm', 'Tür', 'Sonuç', 'Yaralanan Bölge', 'Kayıp Gün', 'Durum', 'Özet'], null, 'A1');
        $s->getStyle('A1:J1')->getFont()->setBold(true);

        $r = 2;
        foreach ($this->listelenenOlaylar as $o) {
            $s->fromArray([
                $o->belge_no,
                $o->firma?->unvan,
                $o->olay_tarihi?->format('d.m.Y'),
                $o->bolum,
                config('isg.olay.tipler.'.$o->olay_tipi, $o->olay_tipi),
                config('isg.olay.sonuc_turleri.'.$o->sonuc_turu, $o->sonuc_turu),
                config('isg.olay.vucut_bolgeleri.'.$o->yaralanan_bolge.'.ad', $o->yaralanan_bolge),
                (int) $o->kayip_gun_sayisi,
                config('isg.olay.durumlar.'.$o->durum, $o->durum),
                $o->olay_ozeti,
            ], null, 'A'.$r++);
        }
        foreach (range('A', 'I') as $kol) {
            $s->getColumnDimension($kol)->setAutoSize(true);
        }
        $s->getColumnDimension('J')->setWidth(60);

        $tmp = tempnam(sys_get_temp_dir(), 'okm').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'olay-komuta-360-'.now()->format('Ymd').'.xlsx');
    }
}
