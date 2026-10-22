<?php

namespace App\Filament\Pages;

use App\Models\IsgKatipSozlesmesi;
use App\Support\IsgKatipSozlesmeIceAktarici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * İSG-KATİP Sözleşmeleri — "Toplu Atama Sözleşmesi İndir" botunun gerçek
 * hâli (bkz. IsgKatipRobot). Devlet portalında doğrudan otomasyon yok;
 * kullanıcı İSG-KATİP > Süreç Yönetimi > İSG Hizmet Sözleşmeleri ekranındaki
 * native "Tümünü Dışa aktar" Excel'ini kendi oturumunda indirip burada
 * yükler (bkz. IsgKatipSozlesmeIceAktarici). Ana görünüm KİŞİ bazlıdır:
 * birden fazla uzman/hekim çalıştıran kullanıcı her görevlendirilen kişinin
 * aktif atama sayısını, çalıştığı firmaları ve bitiş/onay durumunu tek
 * yerden görüp planlama yapar; bir kişi seçilince altta sözleşmeleri açılır.
 */
class IsgKatipSozlesmeleri extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.isg-katip-sozlesmeleri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar ve Kütüphaneler';

    /** Menüde gizli — İSG-KATİP Robot sayfasındaki bot kartından açılır. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'isg-katip-sozlesmeleri';

    protected static ?string $title = 'İSG-KATİP Sözleşmeleri';

    protected static ?string $navigationLabel = 'İSG-KATİP Sözleşmeleri';

    #[Url(as: 'kisi')]
    public ?string $seciliKisi = null;

    public int $yaklasanGun = 30;

    /** @return Collection<int, IsgKatipSozlesmesi> */
    #[Computed]
    public function tumu(): Collection
    {
        return IsgKatipSozlesmesi::query()
            ->where('user_id', Filament::auth()->id())
            ->with('firma:id,unvan')
            ->orderByDesc('baslangic_tarihi')
            ->get();
    }

    #[Computed]
    public function sonIceAktarim(): ?string
    {
        return $this->tumu->max('son_iceaktarim_at')?->diffForHumans();
    }

    /** @return Collection<string, array<string, mixed>> kisi anahtarı (tc veya ad) => özet */
    #[Computed]
    public function kisiler(): Collection
    {
        return $this->tumu
            ->filter(fn (IsgKatipSozlesmesi $s) => filled($s->kisi_ad))
            ->groupBy(fn (IsgKatipSozlesmesi $s) => $s->kisi_tc ?: $s->kisi_ad)
            ->map(function (Collection $grup, string $anahtar) {
                $aktifler = $grup->filter->aktifMi();

                return [
                    'anahtar' => $anahtar,
                    'ad' => $grup->first()->kisi_ad,
                    'sertifika' => $grup->first()->sertifika_tipi,
                    'toplam' => $grup->count(),
                    'aktif' => $aktifler->count(),
                    'firmalar' => $aktifler->pluck('hizmet_alan_unvan')->filter()->unique()->values(),
                    'sureler' => $aktifler->countBy('calisma_suresi')->filter(fn ($v, $k) => filled($k)),
                    'onay_bekleyen' => $grup->filter->onayBekliyorMu()->count(),
                    'suresi_gecmis' => $aktifler->filter->suresiGecmisMi()->count(),
                    'suresi_yaklasan' => $aktifler->filter(fn ($s) => $s->suresiYaklasiyorMu($this->yaklasanGun) && ! $s->suresiGecmisMi())->count(),
                    'en_yakin_bitis' => $aktifler->pluck('bitis_tarihi')->filter()->sort()->first(),
                ];
            })
            ->sortByDesc(fn ($k) => $k['suresi_gecmis'] * 1000 + $k['suresi_yaklasan'] * 100 + $k['onay_bekleyen'])
            ->values()
            ->keyBy('anahtar');
    }

    #[Computed]
    public function secilenKisiOzet(): ?array
    {
        return $this->seciliKisi ? ($this->kisiler[$this->seciliKisi] ?? null) : null;
    }

    /** @return Collection<int, IsgKatipSozlesmesi> seçili kişinin sözleşmeleri (aktifler önce, bitişe göre) */
    #[Computed]
    public function seciliKisiSozlesmeleri(): Collection
    {
        if (! $this->seciliKisi) {
            return collect();
        }

        return $this->tumu
            ->filter(fn (IsgKatipSozlesmesi $s) => ($s->kisi_tc ?: $s->kisi_ad) === $this->seciliKisi)
            ->sortBy([
                fn ($a, $b) => (int) $b->aktifMi() <=> (int) $a->aktifMi(),
                fn ($a, $b) => ($a->bitis_tarihi?->timestamp ?? PHP_INT_MAX) <=> ($b->bitis_tarihi?->timestamp ?? PHP_INT_MAX),
            ])
            ->values();
    }

    public function kisiSec(?string $anahtar): void
    {
        $this->seciliKisi = $anahtar && $this->kisiler->has($anahtar) ? $anahtar : null;
        unset($this->secilenKisiOzet, $this->seciliKisiSozlesmeleri);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('yukle')
                ->label('Excel Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('İSG-KATİP Sözleşmelerini Yükle')
                ->modalDescription('İSG-KATİP > Süreç Yönetimi > İSG Hizmet Sözleşmeleri ekranında "Tümünü Dışa aktar" ile indirdiğiniz Excel dosyasını buraya yükleyin. Aynı sözleşmeler tekrar yüklenirse güncellenir, çoğalmaz.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    FileUpload::make('dosya')
                        ->label('Excel dosyası (.xlsx)')
                        ->required()
                        ->disk('public')->directory('isg-katip/gecici')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->maxSize(20480),
                ])
                ->action(function (array $data): void {
                    $disk = Storage::disk('public');
                    $sonuc = IsgKatipSozlesmeIceAktarici::iceAktar($disk->path($data['dosya']), Filament::auth()->id());
                    $disk->delete($data['dosya']);

                    unset($this->tumu, $this->kisiler, $this->sonIceAktarim, $this->secilenKisiOzet, $this->seciliKisiSozlesmeleri);

                    if ($sonuc['basarili'] === 0 && $sonuc['hatalar']) {
                        Notification::make()->title('Hiçbir satır içe aktarılamadı')->body(implode("\n", array_slice($sonuc['hatalar'], 0, 5)))->danger()->persistent()->send();

                        return;
                    }

                    Notification::make()
                        ->title($sonuc['basarili'].' sözleşme içe aktarıldı')
                        ->body($sonuc['hatalar'] ? (count($sonuc['hatalar']).' satırda sorun oluştu: '.implode(' ', array_slice($sonuc['hatalar'], 0, 3))) : null)
                        ->success()
                        ->send();
                }),
        ];
    }
}
