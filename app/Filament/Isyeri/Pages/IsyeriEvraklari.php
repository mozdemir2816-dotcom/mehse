<?php

namespace App\Filament\Isyeri\Pages;

use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\IsyeriHesabi;
use App\Support\CalisanDosyasiUretici;
use App\Support\CalisanListesiUretici;
use App\Support\FirmaEvrakZipUretici;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İşyeri paneli ana sayfası — giriş yapan işyeri hesabının firmasına ait
 * künye, İSG profesyonelleri, özet ve evrak listesi. Salt-okunur: yalnız
 * görüntüleme ve indirme; hiçbir kayıt değiştirilemez. Firma her zaman
 * oturumdaki hesaptan gelir — istemciden firma id'si alınmaz.
 */
class IsyeriEvraklari extends Page
{
    protected string $view = 'filament.isyeri.pages.isyeri-evraklari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $title = 'Firma Evraklarım';

    protected static ?string $slug = '/';

    public static function getNavigationLabel(): string
    {
        return 'Evraklarım';
    }

    #[Computed]
    public function firma(): Firma
    {
        /** @var IsyeriHesabi $hesap */
        $hesap = Filament::auth()->user();

        return Firma::with(['igu', 'isyeriHekimi'])->findOrFail($hesap->firma_id);
    }

    /** @return array<int, array{anahtar: string, tip: string, tarih: \Carbon\CarbonInterface}> */
    #[Computed]
    public function evraklar(): array
    {
        return FirmaEvrakZipUretici::liste($this->firma);
    }

    /** @return array<string, int> */
    #[Computed]
    public function ozet(): array
    {
        $izinler = IsIzinFormu::where('firma_id', $this->firma->id)->get(['id', 'durum', 'baslangic', 'bitis']);

        return [
            'calisan' => $this->firma->calisanlar()->where('aktif', true)->count(),
            'evrak' => count($this->evraklar),
            'aktif_izin' => $izinler->filter(fn (IsIzinFormu $f) => $f->aktifMi())->count(),
        ];
    }

    public function evrakIndir(string $anahtar): ?StreamedResponse
    {
        $yanit = FirmaEvrakZipUretici::pdf($this->firma, $anahtar);

        if (! $yanit) {
            Notification::make()->title('Evrak bulunamadı')->danger()->send();
        }

        return $yanit;
    }

    public function tumunuIndir(): ?StreamedResponse
    {
        if (! $this->evraklar) {
            return null;
        }

        return FirmaEvrakZipUretici::zip($this->firma, array_column($this->evraklar, 'anahtar'));
    }

    public function personelListesi(): StreamedResponse
    {
        return CalisanListesiUretici::pdf($this->firma, 'aktif');
    }

    public function personelDosyasi(): StreamedResponse
    {
        return CalisanDosyasiUretici::excel($this->firma, 'aktif');
    }
}
