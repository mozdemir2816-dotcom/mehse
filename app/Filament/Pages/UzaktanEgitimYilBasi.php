<?php

namespace App\Filament\Pages;

use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\UzaktanEgitimFirmaAyari;
use App\Support\YilBasiTopluEgitim;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

/**
 * Yıl Başı Toplu Eğitim (23.11.2026): tüm firmalarda temel İSG eğitimi yenilemesi o yıl dolanlara
 * 4 saatlik Yenileme paketi, hiç eğitim kaydı olmayanlara 8 saatlik Temel paket atanır. Önce
 * önizleme, sonra seçili firmalara tek düğmeyle gönderim: atama + giriş bilgisi + firma yetkilisine
 * e-posta (giriş kartları PDF ekli) + WhatsApp mesajı. Kural: App\Support\YilBasiTopluEgitim.
 */
class UzaktanEgitimYilBasi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.uzaktan-egitim-yil-basi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    /** Menüde gizli — Online Eğitim Yönet sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 93;

    protected static ?string $slug = 'uzaktan-egitim-yil-basi';

    protected static ?string $title = 'Yıl Başı Toplu Eğitim';

    protected static ?string $navigationLabel = 'Yıl Başı Toplu Eğitim';

    public int $yil;

    public ?int $temelPaketId = null;

    public ?int $yenilemePaketId = null;

    /** @var array<int, int> */
    public array $seciliFirmalar = [];

    public bool $epostaGonder = true;

    /** kaydi_olan | yenilemesi_dolan — bkz. YilBasiTopluEgitim::KAPSAMLAR */
    public string $kapsam = 'kaydi_olan';

    /** Son gönderimin sonuçları (firma başına). @var array<int, array<string, mixed>> */
    public array $sonuclar = [];

    public function mount(): void
    {
        // Kasım–Aralık'ta açılırsa gelecek yıl hazırlanır; Ocak–Ekim'de içinde bulunulan yıl.
        $this->yil = now()->month >= 11 ? now()->year + 1 : now()->year;
        $this->temelPaketId = $this->varsayilanPaket('ilk_defa');
        $this->yenilemePaketId = $this->varsayilanPaket('yenileme');
    }

    /** Varsayılan paket: adında "Temel" geçen, en çok atanmış olan; yoksa en çok atanmış. */
    private function varsayilanPaket(string $tur): ?int
    {
        $paketler = EgitimPaketi::query()->gorunur(Filament::auth()->id())->where('aktif', true)
            ->where('egitim_turu', $tur)->withCount('atamalar')->get()
            ->sortByDesc(fn (EgitimPaketi $p) => [str_contains(mb_strtolower($p->ad), 'temel') ? 1 : 0, $p->atamalar_count]);

        return $paketler->first()?->id;
    }

    /** @return array<int, string> */
    public function paketler(string $tur): array
    {
        return EgitimPaketi::query()->gorunur(Filament::auth()->id())->where('aktif', true)
            ->where('egitim_turu', $tur)->orderBy('ad')->pluck('ad', 'id')->all();
    }

    #[Computed]
    public function temelPaket(): ?EgitimPaketi
    {
        return $this->temelPaketId ? EgitimPaketi::query()->gorunur(Filament::auth()->id())->where('egitim_turu', 'ilk_defa')->find($this->temelPaketId) : null;
    }

    #[Computed]
    public function yenilemePaketi(): ?EgitimPaketi
    {
        return $this->yenilemePaketId ? EgitimPaketi::query()->gorunur(Filament::auth()->id())->where('egitim_turu', 'yenileme')->find($this->yenilemePaketId) : null;
    }

    /** @return array<int, array<string, mixed>> firma id → önizleme */
    #[Computed]
    public function onizleme(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->get()
            ->mapWithKeys(fn (Firma $f) => [$f->id => YilBasiTopluEgitim::firmaOnizleme($f, $this->yil, $this->temelPaket, $this->yenilemePaketi, $this->kapsamAnahtari())])
            ->filter(fn (array $o) => $o['yenileme']->isNotEmpty() || $o['ilk_defa']->isNotEmpty() || $o['bildirim'])
            ->all();
    }

    /** @return array{firma: int, yenileme: int, ilk_defa: int, hazir: int} */
    #[Computed]
    public function toplam(): array
    {
        $o = collect($this->onizleme);

        return [
            'firma' => $o->count(),
            'yenileme' => $o->sum(fn ($x) => $x['yenileme']->count()),
            'ilk_defa' => $o->sum(fn ($x) => $x['ilk_defa']->count()),
            'hazir' => $o->filter(fn ($x) => $this->gonderilebilirMi($x))->count(),
        ];
    }

    public function gonderilebilirMi(array $o): bool
    {
        return ! $o['eksik'] && ($o['yenileme']->isNotEmpty() || $o['ilk_defa']->isNotEmpty());
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['yil', 'temelPaketId', 'yenilemePaketId', 'kapsam'], true)) {
            $this->yil = max(2024, min(2100, (int) $this->yil));
            unset($this->temelPaket, $this->yenilemePaketi, $this->onizleme, $this->toplam);
            $this->seciliFirmalar = [];
            $this->sonuclar = [];
        }
    }

    public function kapsamAnahtari(): string
    {
        return array_key_exists($this->kapsam, YilBasiTopluEgitim::KAPSAMLAR) ? $this->kapsam : 'kaydi_olan';
    }

    /**
     * Seçili iki paketin içeriğini uzmanın TÜM firmaları için tek seferde onaylar (Firma Ayarları'ndaki
     * onayla aynı kayıt; eğitimi verenler boşsa belge firmanın İGU / hekim bilgisini kullanır).
     */
    public function topluOnayVer(): void
    {
        $paketler = array_filter([$this->temelPaket, $this->yenilemePaketi]);
        if (! $paketler) {
            Notification::make()->title('Önce paketleri seçin')->warning()->send();

            return;
        }

        $ad = Filament::auth()->user()?->name;
        $sayi = 0;
        foreach (Firma::query()->where('user_id', Filament::auth()->id())->get() as $firma) {
            $ayar = UzaktanEgitimFirmaAyari::firmaIcin($firma->id) ?? new UzaktanEgitimFirmaAyari(['firma_id' => $firma->id]);
            $degisti = false;
            foreach ($paketler as $p) {
                if (! $ayar->exists || ! $ayar->icerikOnayliMi($p)) {
                    $ayar->onayYaz($p, $ad);
                    $degisti = true;
                }
            }
            if ($degisti) {
                $ayar->save();
                $sayi++;
            }
        }

        unset($this->onizleme, $this->toplam);
        Notification::make()->title($sayi ? "{$sayi} firmada içerik onayı verildi" : 'Tüm firmalar zaten onaylı')
            ->body(collect($paketler)->map(fn (EgitimPaketi $p) => $p->ad)->implode(' · '))->success()->send();
    }

    public function hazirlariSec(): void
    {
        $this->seciliFirmalar = collect($this->onizleme)->filter(fn ($o) => $this->gonderilebilirMi($o))->keys()->map(fn ($k) => (int) $k)->values()->all();
    }

    public function secimiTemizle(): void
    {
        $this->seciliFirmalar = [];
    }

    public function gonder(): void
    {
        $firmalar = Firma::query()->where('user_id', Filament::auth()->id())->whereIn('id', $this->seciliFirmalar)->orderBy('unvan')->get();

        if ($firmalar->isEmpty()) {
            Notification::make()->title('Önce en az bir firma seçin')->warning()->send();

            return;
        }

        @set_time_limit(300);
        $this->sonuclar = [];
        $basarili = 0;

        foreach ($firmalar as $firma) {
            try {
                $s = YilBasiTopluEgitim::gonder($firma, $this->yil, $this->temelPaket, $this->yenilemePaketi, (int) Filament::auth()->id(), $this->epostaGonder, $this->kapsamAnahtari());
                $this->sonuclar[$firma->id] = [
                    'unvan' => $firma->secimEtiketi(),
                    'yenileme' => $s['yenileme'],
                    'ilk_defa' => $s['ilk_defa'],
                    'kisi' => count($s['giris']),
                    'eposta' => $s['eposta'],
                    'eposta_durum' => ! $this->epostaGonder ? 'gonderilmedi' : ($s['eposta_hatasi'] ? 'hata' : 'gitti'),
                    'eposta_hatasi' => $s['eposta_hatasi'],
                    'whatsapp' => filled($firma->telefon) ? YilBasiTopluEgitim::whatsappLinki($firma, $this->yil, $s['giris']) : null,
                    'hata' => null,
                ];
                $basarili++;
            } catch (Throwable $e) {
                $this->sonuclar[$firma->id] = ['unvan' => $firma->secimEtiketi(), 'hata' => $e->getMessage()];
            }
        }

        $this->seciliFirmalar = [];
        unset($this->onizleme, $this->toplam);

        Notification::make()
            ->title("{$basarili} firmaya {$this->yil} eğitimi gönderildi")
            ->body(count($this->sonuclar) > $basarili ? 'Bazı firmalarda gönderim yapılamadı; aşağıdaki sonuç listesine bakın.' : 'Sonuçlar aşağıda: e-posta durumu, WhatsApp mesajı ve giriş kartları.')
            ->{count($this->sonuclar) > $basarili ? 'warning' : 'success'}()
            ->send();
    }

    /** Giriş kartları PDF'i (gönderimden sonra ya da daha önce gönderilmiş firma için). Şifreler yenilenir. */
    public function kartlariIndir(int $firmaId)
    {
        $firma = Firma::query()->where('user_id', Filament::auth()->id())->find($firmaId);
        if (! $firma) {
            return null;
        }

        $giris = YilBasiTopluEgitim::girisBilgileri($firma, $this->yil, $this->temelPaket, $this->yenilemePaketi);
        if (! $giris) {
            Notification::make()->title('Bu dönem için bitmemiş atama yok')->warning()->send();

            return null;
        }

        $pdf = YilBasiTopluEgitim::girisKartlariPdf($firma, $giris);

        return response()->streamDownload(fn () => print ($pdf), 'giris-kartlari-'.$this->yil.'-'.Str::slug($firma->unvan).'.pdf');
    }
}
