<?php

namespace App\Filament\Concerns;

use App\Models\ArsivDosya;
use App\Models\Firma;
use App\Support\ArsivKurali;
use App\Support\ArsivYukleyici;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Telefondan tek dokunuşla arşiv yükleme (Ziyaret Modu, Arşiv kontrol
 * listesi): 📷 → çek → sayfa tepside birikir → "Kaydet" ile kategori
 * varsayılanlarıyla arşive girer (çok sayfa tek PDF). Görünüm:
 * filament.components.hizli-arsiv (kategori başına).
 *
 * Kullanan sayfa hizliArsivFirmasi() ile hedef firmayı verir.
 */
trait HizliArsivYukleme
{
    use WithFileUploads;

    /** @var array<string, mixed> kategori → yeni seçilen dosya(lar) */
    public array $hizliYeni = [];

    /** @var array<string, array<int, array{yol: string, ad: string}>> kategori → tepsideki sayfalar */
    public array $hizliSayfalar = [];

    abstract protected function hizliArsivFirmasi(): ?Firma;

    /** Kayıt sonrası sayfanın kendi önbelleklerini yenilemesi için. */
    protected function hizliArsivKaydedildi(ArsivDosya $d): void {}

    public function updatedHizliYeni(mixed $deger, ?string $kategori = null): void
    {
        if (! $kategori || ! isset(config('arsiv.kategoriler')[$kategori])) {
            return;
        }

        $dosyalar = array_filter(is_array($deger) ? $deger : [$deger], fn ($d) => $d instanceof TemporaryUploadedFile);

        foreach ($dosyalar as $dosya) {
            $uzanti = strtolower($dosya->getClientOriginalExtension());

            if (! in_array($uzanti, [...ArsivYukleyici::GORSEL_UZANTILARI, 'pdf', 'heic'], true) || $dosya->getSize() > 20 * 1024 * 1024) {
                Notification::make()->title('Yalnız fotoğraf ya da PDF (en fazla 20 MB)')->danger()->send();

                continue;
            }

            $this->hizliSayfalar[$kategori][] = [
                'yol' => $dosya->store('arsiv/gecici', 'public'),
                'ad' => $dosya->getClientOriginalName(),
            ];
        }

        unset($this->hizliYeni[$kategori]);
    }

    public function hizliSayfaSil(string $kategori, int $sira): void
    {
        $sayfa = $this->hizliSayfalar[$kategori][$sira] ?? null;

        if ($sayfa) {
            Storage::disk('public')->delete($sayfa['yol']);
            unset($this->hizliSayfalar[$kategori][$sira]);
            $this->hizliSayfalar[$kategori] = array_values($this->hizliSayfalar[$kategori]);
        }
    }

    public function hizliIptal(string $kategori): void
    {
        Storage::disk('public')->delete(array_column($this->hizliSayfalar[$kategori] ?? [], 'yol'));
        unset($this->hizliSayfalar[$kategori]);
    }

    public function hizliKaydet(string $kategori): void
    {
        $firma = $this->hizliArsivFirmasi();
        $sayfalar = $this->hizliSayfalar[$kategori] ?? [];

        if (! $firma || ! $sayfalar) {
            Notification::make()->title($firma ? 'Önce fotoğraf çekin' : 'Önce firma seçin')->danger()->send();

            return;
        }

        $d = ArsivYukleyici::hizliKaydet($firma, $kategori, array_column($sayfalar, 'yol'), array_column($sayfalar, 'ad', 'yol'));
        unset($this->hizliSayfalar[$kategori]);

        if (! $d) {
            Notification::make()->title('Belge kaydedilemedi')->danger()->send();

            return;
        }

        $this->hizliArsivKaydedildi($d);
        Notification::make()
            ->title(ArsivKurali::kategori($kategori)['ad'].' arşive eklendi')
            ->body($d->etiket().' · '.(count($sayfalar) > 1 ? count($sayfalar).' sayfa tek PDF' : $d->dosya_adi).'. Tarih / yıl farklıysa Arşiv\'den "Düzelt" ile değiştirebilirsiniz.')
            ->success()->send();
    }
}
