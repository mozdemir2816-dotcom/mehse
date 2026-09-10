<?php

namespace App\Filament\Pages;

use App\Models\CalisanTemsilcisiSecimi as SecimModel;
use App\Models\Firma;
use App\Support\CalisanTemsilcisiSecimiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Çalışan Temsilcisi Seçim süreci — isgpratik ATAMA YAZISI/ÇALIŞAN TEMSİLCİSİ
 * referansı. Atama Yazıları'ndaki direkt "atama" akışının aksine, burada
 * gerçek bir SEÇİM süreci yürütülür: duyuru → aday başvuruları → kesin aday
 * listesi → oy pusulası → seçim sonucu atama tutanağı.
 */
class CalisanTemsilcisiSecimi extends Page
{
    protected string $view = 'filament.pages.calisan-temsilcisi-secimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Çalışan & Kurul';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'calisan-temsilcisi-secimi';

    protected static ?string $title = 'Çalışan Temsilcisi Seçimi';

    protected static ?string $navigationLabel = 'Çalışan Temsilcisi Seçimi';

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

        return $secim;
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
                ->action(fn () => CalisanTemsilcisiSecimiUretici::bosSablonZip()),

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
