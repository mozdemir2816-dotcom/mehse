<?php

namespace App\Filament\Pages;

use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\UzaktanEgitimFirmaAyari;
use App\Support\FirmaImza;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Online Eğitim Yönet > Firma Ayarları: eğitimi verenler, sertifika kaşesi / logosu, çalışan imza alanı
 * ve içerik onayı. İçerik onayı verilmeden o firmaya uzaktan eğitim gönderilmez (Eğitim Yönetmeliği
 * md.10, 17/5: içerik eğitimi verenlerce incelenip onaylanır; içerik değişirse onay yenilenir).
 */
class UzaktanEgitimFirmaAyarlari extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.uzaktan-egitim-firma-ayarlari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    /** Menüde gizli — Online Eğitim Yönet sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 92;

    protected static ?string $slug = 'uzaktan-egitim-firma-ayarlari';

    protected static ?string $title = 'Uzaktan Eğitim Firma Ayarları';

    protected static ?string $navigationLabel = 'Firma Ayarları';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public ?int $paketId = null;

    public ?string $iguAd = null;

    public ?string $iguUnvan = null;

    public ?string $hekimAd = null;

    public ?string $hekimUnvan = null;

    public bool $kaseBas = true;

    public string $logoKonumu = 'yok';

    public bool $calisanImzaAlani = false;

    public bool $icerikOnayi = false;

    public function mount(): void
    {
        $this->paketId = array_key_first($this->paketler) ?: null;
        $this->yukle();
    }

    public function updatedFirmaId(): void
    {
        $this->yukle();
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->secenekler()->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::query()->where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    #[Computed]
    public function paketler(): array
    {
        return EgitimPaketi::query()->gorunur(Filament::auth()->id())->where('aktif', true)
            ->orderBy('ad')->get()->mapWithKeys(fn (EgitimPaketi $p) => [$p->id => $p->ad])->all();
    }

    #[Computed]
    public function paket(): ?EgitimPaketi
    {
        return $this->paketId ? EgitimPaketi::query()->gorunur(Filament::auth()->id())->find($this->paketId) : null;
    }

    #[Computed]
    public function ayar(): ?UzaktanEgitimFirmaAyari
    {
        return $this->firma ? UzaktanEgitimFirmaAyari::firmaIcin($this->firma->id) : null;
    }

    /** Seçili paket için içerik onayı geçerli mi. */
    #[Computed]
    public function onayli(): bool
    {
        return $this->ayar && $this->paket ? $this->ayar->icerikOnayliMi($this->paket) : false;
    }

    /** Eğitim içeriği: konu başlığı bazında oturum listesi (modül → videolar). */
    #[Computed]
    public function icerik(): array
    {
        return $this->paket?->dersler()->orderBy('sira')->get()
            ->groupBy(fn ($d) => $d->modul ?: 0)
            ->map(fn ($g, $modul) => [
                'konu' => $modul ? (\App\Models\EgitimDersi::MODULLER[$modul] ?? 'Diğer') : 'Diğer dersler',
                'videolar' => $g->map(fn ($d) => $d->baslik)->all(),
                'dk' => (int) round($g->sum('sure_sn') / 60),
            ])->values()->all() ?? [];
    }

    /** Alanları kayıttan (yoksa firmanın İGU / işyeri hekimi bilgisinden) doldurur. */
    private function yukle(): void
    {
        unset($this->firma, $this->ayar, $this->onayli);
        $this->icerikOnayi = false;
        $firma = $this->firma;
        if (! $firma) {
            return;
        }

        $ayar = $this->ayar;
        $uzman = FirmaImza::uzman($firma);
        $hekim = $firma->isyeriHekimi;

        $this->iguAd = $ayar?->egitici_igu_ad ?: $uzman['ad'];
        $this->iguUnvan = $ayar?->egitici_igu_unvan ?: $uzman['unvan'];
        $this->hekimAd = $ayar?->egitici_hekim_ad ?: $hekim?->ad_soyad;
        $this->hekimUnvan = $ayar?->egitici_hekim_unvan ?: ($hekim ? 'İşyeri Hekimi' : null);
        $this->kaseBas = $ayar?->kase_bas ?? true;
        $this->logoKonumu = $ayar?->logo_konumu ?? 'yok';
        $this->calisanImzaAlani = (bool) $ayar?->calisan_imza_alani;
        $this->icerikOnayi = $this->onayli;
    }

    public function kaydet(): void
    {
        $firma = $this->firma;
        $paket = $this->paket;

        if (! $firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        if (! array_key_exists($this->logoKonumu, UzaktanEgitimFirmaAyari::LOGO_KONUMLARI)) {
            $this->logoKonumu = 'yok';
        }

        $ayar = UzaktanEgitimFirmaAyari::firmaIcin($firma->id) ?? new UzaktanEgitimFirmaAyari(['firma_id' => $firma->id]);
        $ayar->fill([
            'egitici_igu_ad' => trim((string) $this->iguAd) ?: null,
            'egitici_igu_unvan' => trim((string) $this->iguUnvan) ?: null,
            'egitici_hekim_ad' => trim((string) $this->hekimAd) ?: null,
            'egitici_hekim_unvan' => trim((string) $this->hekimUnvan) ?: null,
            'kase_bas' => $this->kaseBas,
            'logo_konumu' => $this->logoKonumu,
            'calisan_imza_alani' => $this->calisanImzaAlani,
        ]);

        // İçerik onayı: işaretliyse mevcut paket + içerik sürümü onaylanır; kaldırılırsa geri alınır.
        if ($this->icerikOnayi && $paket) {
            $zatenOnayli = $ayar->exists && $ayar->icerikOnayliMi($paket);
            if (! $zatenOnayli) {
                $ayar->icerik_paket_id = $paket->id;
                $ayar->icerik_surumu = UzaktanEgitimFirmaAyari::icerikSurumu($paket);
                $ayar->icerik_onay_at = now();
                $ayar->icerik_onaylayan = Filament::auth()->user()?->name;
            }
        } else {
            $ayar->icerik_onay_at = null;
            $ayar->icerik_surumu = null;
            $ayar->icerik_onaylayan = null;
        }

        $ayar->save();
        unset($this->ayar, $this->onayli);

        Notification::make()
            ->title($this->onayli ? 'Firma ayarı kaydedildi. Bu firmaya gönderim yapabilirsiniz.' : 'Firma ayarı kaydedildi')
            ->body($this->onayli ? null : 'Gönderim için eğitim içeriğini incelediğinizi onaylayın.')
            ->success()->send();
    }
}
