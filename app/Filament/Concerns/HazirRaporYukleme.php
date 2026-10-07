<?php

namespace App\Filament\Concerns;

use App\Models\ArsivDosya;
use App\Models\Firma;
use App\Support\ArsivKurali;
use App\Filament\Support\DosyaKabul;
use App\Support\ArsivYukleyici;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

/**
 * Dışarıda hazırlanmış (Excel / Word / PDF) raporu, sayfanın madde/AI
 * akışına girmeden olduğu gibi firmanın Arşiv'ine kaydeder — DÖF Oluştur ve
 * Saha Gözlem Raporu sayfaları. Kayıt Arşiv'de de görünür ve kategorinin
 * takip kuralına (ör. saha gözlem 3 ayda bir) sayılır.
 * Görünüm: filament.components.hazir-raporlar.
 *
 * Kullanan sayfa $firmaId ve firma() computed'ını sağlar.
 */
trait HazirRaporYukleme
{
    /** @return array<int, string> */
    protected static function hazirRaporUzantilari(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'];
    }

    /** Arşiv kategori anahtarı (config/arsiv.php). */
    abstract protected function hazirRaporKategorisi(): string;

    protected function hazirRaporYukleAction(): Action
    {
        $ad = ArsivKurali::kategori($this->hazirRaporKategorisi())['ad'];

        return Action::make('hazirRaporYukle')
            ->label('Hazır Rapor Yükle (Excel/Word)')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn () => $this->firma !== null)
            ->modalHeading('Hazır Rapor Yükle')
            ->modalDescription('Kendi hazırladığınız Excel, Word veya PDF raporunu olduğu gibi bu firmanın arşivine ("'.$ad.'") kaydedin.')
            ->modalSubmitActionLabel('Kaydet')
            ->stickyModalFooter()
            ->schema([
                // MIME yerine uzantıyla sınırlanır — gerekçe DosyaKabul'de.
                DosyaKabul::uygula(FileUpload::make('dosyalar')->storeFileNamesIn('dosya_adlari')
                    ->label('Dosya')
                    ->multiple()
                    ->disk('public')->directory('arsiv/gecici'), static::hazirRaporUzantilari())
                    ->maxSize(20480)
                    ->maxFiles(10)
                    ->helperText('Birden fazla dosya seçerseniz tek ZIP olarak (yalnız fotoğraflarsa tek PDF) saklanır.')
                    ->required(),
                DatePicker::make('tarih')->label('Rapor tarihi')->default(now())->required(),
                Textarea::make('aciklama')->label('Not (opsiyonel)')->rows(1),
            ])
            ->action(fn (array $data) => $this->hazirRaporKaydet($data));
    }

    public function hazirRaporKaydet(array $data): void
    {
        $firma = $this->firma;

        if (! $firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $kategori = ArsivKurali::kategori($this->hazirRaporKategorisi());
        $baslik = $kategori['ad'].' — '.Carbon::parse($data['tarih'])->format('d.m.Y');
        $dosya = ArsivYukleyici::birlestir((array) ($data['dosyalar'] ?? []), $firma, $baslik, (array) ($data['dosya_adlari'] ?? []));

        if (! $dosya) {
            Notification::make()->title('Dosya yüklenemedi')->danger()->send();

            return;
        }

        ArsivDosya::create($dosya + [
            'firma_id' => $firma->id,
            'kategori' => $kategori['anahtar'],
            'baslik' => $baslik,
            'aciklama' => $data['aciklama'] ?? null,
            'yil' => (int) Carbon::parse($data['tarih'])->format('Y'),
            'baslangic_tarihi' => $data['tarih'],
            'aktif' => true,
            'asama' => ArsivDosya::DOSYADA,
            'kaynak' => 'yukleme',
        ]);

        unset($this->hazirRaporlar);
        Notification::make()->title('Rapor arşive kaydedildi')->body($dosya['dosya_adi'])->success()->send();
    }

    /** Bu firmanın, sayfanın kategorisindeki arşiv dosyaları (yeniden eskiye). */
    #[Computed]
    public function hazirRaporlar(): Collection
    {
        return $this->firma
            ? ArsivDosya::query()
                ->where('firma_id', $this->firma->id)
                ->where('kategori', $this->hazirRaporKategorisi())
                ->whereNotNull('dosya_yolu')
                ->latest('baslangic_tarihi')->latest('id')
                ->get()
            : collect();
    }

    public function hazirRaporIndir(int $id)
    {
        $d = $this->hazirRaporlar->firstWhere('id', $id);

        if (! $d || ! Storage::disk('public')->exists($d->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($d->dosya_yolu, $d->dosya_adi);
    }

    public function hazirRaporSil(int $id): void
    {
        $d = $this->hazirRaporlar->firstWhere('id', $id);

        if (! $d) {
            return;
        }

        Storage::disk('public')->delete($d->dosya_yolu);
        $d->delete();
        unset($this->hazirRaporlar);
        Notification::make()->title('Rapor silindi')->success()->send();
    }
}
