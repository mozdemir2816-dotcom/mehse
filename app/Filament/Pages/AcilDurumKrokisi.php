<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImzaSecenegi;
use App\Models\AcilDurumKrokisi as KrokiModel;
use App\Models\Firma;
use App\Support\AcilDurumKrokisiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Acil Durum / Tahliye Krokisi Editörü (ISGCEO kroki editörü + isgsuite
 * referansı). Çizim tamamen tarayıcıda (Alpine + SVG): seç / taşı,
 * duvar (90° kenetli, zincirleme), oda / bölüm, kaçış yolu (oklu), metin,
 * ISO 7010 renk-biçim kurallarına uygun 57 işaret, döndür / boyut / çoğalt /
 * öne-arkaya, geri al / ileri al, yakınlaştırma, ızgara, altlık görseli,
 * otomatik lejant + resmî antet; PNG / proje (JSON) indir-yükle, yazdır.
 * Sunucu yalnız `kaydet()` ile temizlenmiş veriyi ve PNG görüntüsünü saklar;
 * PDF bu PNG'yi basar.
 */
class AcilDurumKrokisi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.acil-durum-krokisi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Acil Durum & Yangın';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'acil-durum-krokisi';

    protected static ?string $title = 'Acil Durum Krokisi';

    protected static ?string $navigationLabel = 'Acil Durum Krokisi';

    public ?int $firmaId = null;

    public function mount(): void
    {
        $id = request()->integer('firma');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : null;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    public function kroki(): ?KrokiModel
    {
        return $this->firma ? KrokiModel::firmaIcin($this->firma) : null;
    }

    /** Editör tarayıcıda yaşadığı için firma değişince sayfa yeniden yüklenir. */
    public function updatedFirmaId(): void
    {
        $this->redirect(static::getUrl($this->firmaId ? ['firma' => $this->firmaId] : []));
    }

    /** Editörün ilk durumu (eski 1000x700 krokiler ölçeklenir). */
    public function editorVerisi(): array
    {
        $kroki = $this->kroki();
        $veri = $kroki?->editorVerisi() ?? ['duvarlar' => [], 'semboller' => [], 'ogeler' => ['odalar' => [], 'yollar' => [], 'metinler' => []], 'antet' => []];
        $firma = $this->firma;

        $veri['antet'] += [
            'baslik' => 'ACİL DURUM TAHLİYE KROKİSİ',
            'hazirlayan' => Filament::auth()->user()?->name,
        ];
        $veri['bilgi'] = [
            'firma' => $firma?->unvan,
            'adres' => trim(($firma?->adres ?? '').' '.($firma?->ilce ?? '').' '.($firma?->il ?? '')),
            'tarih' => ($kroki?->hazirlanma_tarihi ?? now())->format('d.m.Y'),
            'altlik' => $this->arkaPlanUrl(),
        ];

        return $veri;
    }

    /**
     * Tarayıcıdan: kroki verisi + o anki görünümün PNG'si (data URL).
     * PNG en fazla ~8 MB kabul edilir.
     */
    public function kaydet(array $veri, ?string $png = null): void
    {
        $kroki = $this->kroki();

        if (! $kroki) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $temiz = KrokiModel::temizle($veri);
        $kroki->forceFill([...$temiz, 'hazirlanma_tarihi' => now()]);

        if ($png && str_starts_with($png, 'data:image/png;base64,') && strlen($png) < 11_000_000) {
            $ikili = base64_decode(substr($png, 22), true);

            if ($ikili !== false && str_starts_with($ikili, "\x89PNG")) {
                $eski = $kroki->gorsel_yolu;
                $yol = 'acil-durum-kroki/'.$kroki->firma_id.'-'.now()->format('YmdHis').'.png';
                Storage::disk('public')->put($yol, $ikili);
                $kroki->gorsel_yolu = $yol;

                if ($eski && $eski !== $yol) {
                    Storage::disk('public')->delete($eski);
                }
            }
        }

        $kroki->save();

        Notification::make()->title('Kroki kaydedildi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('arkaPlan')
                ->label('Plan Altlığı')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('Mimari plan / kat planı görselini krokinin altına yerleştirin; çizimi üzerine yapın. Kaydettikten sonra sayfa yenilenir — önce krokiyi kaydedin.')
                ->fillForm(fn (): array => ['arka_plan_gorseli' => $this->kroki()?->arka_plan_gorseli])
                ->schema([
                    FileUpload::make('arka_plan_gorseli')
                        ->label('Mimari plan / kat planı görseli (boş bırakırsanız kaldırılır)')
                        ->image()
                        ->disk('public')->directory('acil-durum-kroki-arkaplan')->maxSize(4096),
                ])
                ->action(function (array $data): void {
                    $this->kroki()?->forceFill(['arka_plan_gorseli' => $data['arka_plan_gorseli'] ?? null])->save();
                    Notification::make()->title('Plan altlığı kaydedildi')->success()->send();
                    $this->redirect(static::getUrl(['firma' => $this->firmaId]));
                }),

            Action::make('pdf')
                ->label('Kroki PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('PDF, en son "Kaydet" ile kaydedilen kroki görüntüsünden üretilir.')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => AcilDurumKrokisiUretici::pdf($this->kroki())),
        ];
    }

    public function arkaPlanUrl(): ?string
    {
        $yol = $this->kroki()?->arka_plan_gorseli;

        return $yol ? Storage::disk('public')->url($yol) : null;
    }
}
