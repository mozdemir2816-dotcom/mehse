<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\CalisanTemsilcisiSecimi as SecimModel;
use App\Models\Firma;
use App\Support\AtamaYazisiUretici;
use App\Support\CalisanTemsilcisiSecimiUretici;
use App\Support\EkipAtamaEgitim;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Support\EgitimTakibi;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Çalışan Temsilcisi Seçim süreci — isgpratik ATAMA YAZISI/ÇALIŞAN TEMSİLCİSİ
 * referansı. Atama Yazıları'ndaki direkt "atama" akışının aksine, burada
 * gerçek bir SEÇİM süreci yürütülür: duyuru → aday başvuruları → kesin aday
 * listesi → oy pusulası → seçim sonucu atama tutanağı.
 */
class CalisanTemsilcisiSecimi extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.calisan-temsilcisi-secimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Belgeler & Yazışmalar';

    /** Menüde gizli — Resmi Belge & Kurul Yönetim Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'calisan-temsilcisi-secimi';

    protected static ?string $title = 'Çalışan Temsilcisi Seçimi';

    protected static ?string $navigationLabel = 'Çalışan Temsilcisi Seçimi';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public ?string $adayBasvuruSonTarihi = null;

    public ?string $secimTarihi = null;

    public ?string $secimSaati = null;

    public ?string $secimYeri = null;

    public ?int $isyeriCalisanSayisi = null;

    public ?int $zorunluTemsilciSayisi = null;

    public ?string $gorevlendirmeTarihi = null;

    /** @var array<int, array{ad_soyad: string, unvan: ?string}> */
    public array $adaylar = [];

    public ?int $secilenAdayIndex = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
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

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    public function secim(): ?SecimModel
    {
        return $this->firma ? SecimModel::firmaIcin($this->firma) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma);

        $secim = $this->secim();

        if (! $secim) {
            return;
        }

        $this->adayBasvuruSonTarihi = $secim->aday_basvuru_son_tarihi?->toDateString();
        $this->secimTarihi = $secim->secim_tarihi?->toDateString();
        $this->secimSaati = $secim->secim_saati;
        $this->secimYeri = $secim->secim_yeri;
        $this->isyeriCalisanSayisi = $secim->isyeri_calisan_sayisi;
        $this->zorunluTemsilciSayisi = $secim->zorunlu_temsilci_sayisi;
        $this->gorevlendirmeTarihi = $secim->gorevlendirme_tarihi?->toDateString();
        $this->adaylar = $secim->adaylarListesi();
        $this->secilenAdayIndex = $secim->secilen_aday_index;
    }

    public function adayEkle(): void
    {
        $this->adaylar[] = ['ad_soyad' => '', 'unvan' => ''];
    }

    public function adaySil(int $index): void
    {
        unset($this->adaylar[$index]);
        $this->adaylar = array_values($this->adaylar);

        if ($this->secilenAdayIndex === $index) {
            $this->secilenAdayIndex = null;
        }
    }

    public function kazananSec(int $index): void
    {
        $this->secilenAdayIndex = $this->secilenAdayIndex === $index ? null : $index;
    }

    private function kaydet(): ?SecimModel
    {
        $secim = $this->secim();

        if (! $secim) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        $temizAdaylar = collect($this->adaylar)
            ->map(fn ($a) => ['ad_soyad' => trim($a['ad_soyad'] ?? ''), 'unvan' => trim($a['unvan'] ?? '') ?: null])
            ->filter(fn ($a) => filled($a['ad_soyad']))
            ->values()
            ->all();

        $secim->forceFill([
            'aday_basvuru_son_tarihi' => $this->adayBasvuruSonTarihi,
            'secim_tarihi' => $this->secimTarihi,
            'secim_saati' => $this->secimSaati,
            'secim_yeri' => $this->secimYeri,
            'isyeri_calisan_sayisi' => $this->isyeriCalisanSayisi,
            'zorunlu_temsilci_sayisi' => $this->zorunluTemsilciSayisi,
            'gorevlendirme_tarihi' => $this->gorevlendirmeTarihi,
            'adaylar' => $temizAdaylar,
            'secilen_aday_index' => $this->secilenAdayIndex !== null && isset($temizAdaylar[$this->secilenAdayIndex])
                ? $this->secilenAdayIndex
                : null,
        ])->save();

        $this->adaylar = $temizAdaylar;

        $this->temsilciAtamasiniHazirla();

        return $secim;
    }

    /** Kazanan seçildiyse atama yazısı otomatik hazırlanır (aynı kişinin son 2 yıldaki yazısı varsa yenisi açılmaz); eğitim hatırlatılır. */
    private function temsilciAtamasiniHazirla(): void
    {
        unset($this->atamaEgitim);

        if (! $this->firma) {
            return;
        }

        $yazi = EkipAtamaEgitim::temsilciAtamaHazirla($this->firma);
        $planli = EkipAtamaEgitim::temsilciEgitimPlanla($this->firma);   // eğitimi olmayan temsilciler Eğitim Katılım'da planlanır
        \App\Support\GorevDurumu::onbellegiTemizle(Filament::auth()->user());
        unset($this->atamaEgitim);

        if ($yazi) {
            $bekleyen = EkipAtamaEgitim::temsilciEgitimDurumu($this->firma)['eksik']->count();

            Notification::make()->success()
                ->title('Çalışan temsilcisi atama yazısı hazırlandı')
                ->body(($yazi->uyeler[0]['ad_soyad'] ?? '').' için hazırlandı (Atama Yazıları > Çalışan Temsilcisi).'.($bekleyen ? " {$bekleyen} temsilcinin eğitimi Eğitim Katılım'da planlandı".($planli ? ' ('.$planli->belge_no.')' : '').'.' : ''))
                ->send();
        }
    }

    /** Temsilcilerin atama yazısı ve eğitim durumu (panel). @return array<int, array<string, mixed>> */
    #[Computed]
    public function atamaEgitim(): array
    {
        if (! $this->firma) {
            return [];
        }

        $egitim = EkipAtamaEgitim::temsilciEgitimDurumu($this->firma);
        $eksikAnahtar = $egitim['eksik']->pluck('ad_soyad')->map(fn ($a) => \App\Support\EgitimTakibi::adAnahtari($a))->flip();

        return EkipAtamaEgitim::temsilciler($this->firma)->map(fn (array $t) => [
            ...$t,
            'atama' => EkipAtamaEgitim::temsilciAtamaVar($this->firma, $t['ad_soyad']),
            'egitim_yok' => $eksikAnahtar->has(\App\Support\EgitimTakibi::adAnahtari($t['ad_soyad'])),
        ])->all();
    }

    /** Seçilen kazanan için atama yazısını (yoksa hazırlayıp) indirir. */
    public function temsilciAtamaPdf()
    {
        if ($this->secim()?->secilenAday() === null) {
            Notification::make()->title('Önce aday listesinden kazananı işaretleyip kaydedin')->warning()->send();

            return null;
        }

        $this->kaydet();
        $ad = EgitimTakibi::adAnahtari($this->secim()->secilenAday()['ad_soyad']);
        $yazi = $this->firma->atamaYazilari()->where('rol_anahtari', EkipAtamaEgitim::TEMSILCI_ROL)->latest('id')->get()
            ->first(fn ($y) => collect((array) $y->uyeler)->contains(fn ($u) => EgitimTakibi::adAnahtari($u['ad_soyad'] ?? null) === $ad));

        return $yazi ? AtamaYazisiUretici::pdf($yazi) : null;
    }

    /** Eğitimi olmayan çalışan temsilcilerini eğitim katılım formuna hazır aktarır. */
    public function temsilciEgitimFormunaGit()
    {
        $aktarim = $this->firma ? EkipAtamaEgitim::temsilciEgitimAktarimi($this->firma) : null;

        if (! $aktarim) {
            Notification::make()->title('Eğitim bekleyen çalışan temsilcisi yok')->info()->send();

            return null;
        }

        session(['egitim_katilim_aktarim' => $aktarim]);

        return $this->redirect(\App\Filament\Pages\EgitimKatilim::getUrl(['firma' => $this->firma->id]));
    }

    public function kaydetVeBildir(): void
    {
        if ($this->kaydet()) {
            Notification::make()->title('Çalışan temsilcisi seçim bilgileri kaydedildi')->success()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bosSablon')
                ->label('Boş Şablon Seti (ZIP)')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->tooltip('Firma adı ve aday listesi boş — elle doldurmak için 5 belgenin tamamı')
                ->action(fn () => CalisanTemsilcisiSecimiUretici::bosSablonZip($this->firma)),

            Action::make('duyuru')
                ->label('Seçim Duyuru İlanı')
                ->icon('heroicon-o-megaphone')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => ($kayit = $this->kaydet()) ? CalisanTemsilcisiSecimiUretici::duyuruPdf($kayit) : null),

            Action::make('basvuru')
                ->label('Aday Başvuru Dilekçesi (Boş)')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => ($kayit = $this->kaydet()) ? CalisanTemsilcisiSecimiUretici::basvuruDilekcesiPdf($kayit) : null),

            Action::make('adayListesi')
                ->label('Kesin Aday Listesi')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => ($kayit = $this->kaydet()) ? CalisanTemsilcisiSecimiUretici::adayListesiPdf($kayit) : null),

            Action::make('oyPusulasi')
                ->label('Oy Pusulası')
                ->icon('heroicon-o-check-badge')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(fn () => ($kayit = $this->kaydet()) ? CalisanTemsilcisiSecimiUretici::oyPusulasiPdf($kayit) : null),

            Action::make('tutanak')
                ->label('Atama Tutanağı')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success')
                ->visible(fn () => $this->firma !== null)
                ->disabled(fn () => $this->secilenAdayIndex === null)
                ->tooltip(fn () => $this->secilenAdayIndex === null ? 'Önce aday listesinden kazananı işaretleyin' : null)
                ->action(fn () => ($kayit = $this->kaydet()) ? CalisanTemsilcisiSecimiUretici::tutanakPdf($kayit) : null),
        ];
    }
}
