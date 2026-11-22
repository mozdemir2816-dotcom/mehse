<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\Firma;
use App\Models\KimyasalUrun;
use App\Support\KimyasalOzetleri;
use App\Support\MatbuEvrakPaketi as Paket;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Matbu Evrak Paketi (22.11.2026) — firmanın ekipman ve kimyasallarına ait takip edilmesi
 * gereken tüm evrak (talimat, günlük kontrol çizelgesi, ekipman afişi, kimyasal afişi, MSDS
 * özeti) tek birleşik PDF'te; yeni eklenen ve henüz gönderilmemiş kayıtlar ayrıca basılabilir.
 * Eksik MSDS özetleri Gemini sohbetinde (API kotası harcanmadan) üretilip yapıştırılır.
 */
class MatbuEvrakPaketi extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.matbu-evrak-paketi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string|UnitEnum|null $navigationGroup = 'Günlük Saha İşi';

    /** Menüde gizli — Ekipman & Ölçüm sekme şeridinden, Periyodik Kontrol ve Kimyasal Sicili'nden açılır. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'matbu-evrak-paketi';

    protected static ?string $title = 'Matbu Evrak Paketi';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** @var array<int, string> */
    public array $seciliEkipman = [];

    /** @var array<int, string> */
    public array $seciliKimyasal = [];

    public string $cevap = '';

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->secenekler()->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function ekipmanlar(): array
    {
        return $this->firma ? Paket::ekipmanDurumlari($this->firma) : [];
    }

    /** @return Collection<int, KimyasalUrun> */
    #[Computed]
    public function kimyasallar(): Collection
    {
        return $this->firma ? Paket::kimyasallar($this->firma) : collect();
    }

    /** @return Collection<int, KimyasalUrun> */
    #[Computed]
    public function ozetsizler(): Collection
    {
        return $this->kimyasallar->reject->ozetVarMi()->values();
    }

    /** @return array<int, string> */
    #[Computed]
    public function istemler(): array
    {
        return KimyasalOzetleri::istemler($this->ozetsizler);
    }

    /** @return array{ekipman: array<int, int>, kimyasal: array<int, int>} gönderilmemiş (yeni eklenen) kayıtlar */
    #[Computed]
    public function yeniler(): array
    {
        return [
            'ekipman' => collect($this->ekipmanlar)->filter(fn ($d) => ! $d['gonderildi'])->map(fn ($d) => $d['e']->id)->values()->all(),
            'kimyasal' => $this->kimyasallar->filter(fn (KimyasalUrun $k) => ! $k->evrak_cikti_at && $k->ozetVarMi())->pluck('id')->all(),
        ];
    }

    public function updatedFirmaId(): void
    {
        $this->seciliEkipman = [];
        $this->seciliKimyasal = [];
        $this->cevap = '';
        $this->yenile();
    }

    private function yenile(): void
    {
        unset($this->firma, $this->ekipmanlar, $this->kimyasallar, $this->ozetsizler, $this->istemler, $this->yeniler);
    }

    public function yenileriIndir()
    {
        $y = $this->yeniler;

        return $this->indir($y['ekipman'], $y['kimyasal'], 'Gönderilmemiş evrak yok', 'Tüm ekipman ve kimyasalların evrakı daha önce basılmış. Tekrar basmak için "Tümünü indir"i kullanın.');
    }

    public function secilenleriIndir()
    {
        return $this->indir(array_map('intval', $this->seciliEkipman), array_map('intval', $this->seciliKimyasal), 'Seçim yok', 'Listeden en az bir ekipman ya da kimyasal işaretleyin.');
    }

    public function tumunuIndir()
    {
        return $this->indir(null, null, 'Basılacak evrak yok', 'Bu firmada aktif ekipman ya da MSDS özeti olan kimyasal yok.');
    }

    /** @param  array<int, int>|null  $ekipman  @param  array<int, int>|null  $kimyasal */
    private function indir(?array $ekipman, ?array $kimyasal, string $bosBaslik, string $bosMetin)
    {
        if (! $this->firma || ($ekipman === [] && $kimyasal === [])) {
            Notification::make()->title($bosBaslik)->body($bosMetin)->warning()->send();

            return null;
        }

        $yanit = Paket::indir($this->firma, $ekipman, $kimyasal);
        if (! $yanit) {
            Notification::make()->title($bosBaslik)->body($bosMetin)->warning()->send();

            return null;
        }

        $this->seciliEkipman = [];
        $this->seciliKimyasal = [];
        $this->yenile();

        return $yanit;
    }

    public function hazirOzetleriKullan(): void
    {
        if (! $this->firma) {
            return;
        }
        $kopyalanan = KimyasalOzetleri::hazirOzetleriUygula($this->firma);
        $this->yenile();

        Notification::make()
            ->title($kopyalanan ? count($kopyalanan).' kimyasalın özeti başka firmalarınızdan alındı' : 'Eşleşen özet bulunamadı')
            ->body($kopyalanan ? implode(', ', $kopyalanan) : 'Başka firmalarınızda aynı ad ya da CAS numarasıyla özeti olan kimyasal yok. Gemini istemini kullanın.')
            ->{$kopyalanan ? 'success' : 'warning'}()
            ->send();
    }

    public function ozetleriKaydet(): void
    {
        if (! $this->firma) {
            return;
        }
        $sonuc = KimyasalOzetleri::iceAktar($this->firma, $this->cevap);
        if ($sonuc['kaydedilen']) {
            $this->cevap = '';
        }
        $this->yenile();

        Notification::make()
            ->title(count($sonuc['kaydedilen']).' kimyasalın MSDS özeti kaydedildi')
            ->body($sonuc['hatalar'] ? implode("\n", $sonuc['hatalar']) : 'Özetler "uzman onayı bekliyor" etiketiyle hazır. Kontrol edip onaylayın.')
            ->{$sonuc['hatalar'] ? 'warning' : 'success'}()
            ->persistent((bool) $sonuc['hatalar'])
            ->send();
    }

    public function onayla(int $id): void
    {
        KimyasalOzetleri::onayla($this->kimyasal($id));
        $this->yenile();
        Notification::make()->title('MSDS özeti onaylandı')->body('Afiş ve özetteki taslak etiketi kalktı.')->success()->send();
    }

    public function kimyasalAfisi(int $id)
    {
        $k = $this->kimyasal($id);

        return response()->streamDownload(fn () => print (KimyasalOzetleri::afisPdf($k)), 'kimyasal-afisi-'.Str::slug($k->urun_adi).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function msdsOzeti(int $id)
    {
        $k = $this->kimyasal($id);

        return response()->streamDownload(fn () => print (KimyasalOzetleri::ozetPdf($k)), 'msds-ozeti-'.Str::slug($k->urun_adi).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function kimyasal(int $id): KimyasalUrun
    {
        abort_unless($this->firma, 404);

        return $this->firma->kimyasalUrunler()->findOrFail($id);
    }
}
