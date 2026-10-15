<?php

namespace App\Filament\Isyeri\Pages;

use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\IsyeriHesabi;
use App\Models\OnBilgilendirme;
use App\Support\CalisanDosyasiUretici;
use App\Support\CalisanListesiUretici;
use App\Support\FirmaEvrakZipUretici;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

    /** @return array<int, array{anahtar: string, tip: string, tarih: CarbonInterface}> */
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

    /**
     * İGU'nun panele gönderdiği ön bilgilendirme yazıları (Ön Bilgilendirme / Tebliğ).
     *
     * @return Collection<int, OnBilgilendirme>
     */
    #[Computed]
    public function bilgilendirmeler(): Collection
    {
        return OnBilgilendirme::where('firma_id', $this->firma->id)
            ->whereNotNull('isyerine_gonderildi_at')
            ->latest('bildirim_tarihi')->latest('id')
            ->get();
    }

    private function bilgilendirme(int $id): ?OnBilgilendirme
    {
        return OnBilgilendirme::where('firma_id', $this->firma->id)->whereNotNull('isyerine_gonderildi_at')->find($id);
    }

    public function bilgilendirmeIndir(int $id)
    {
        $b = $this->bilgilendirme($id);

        if (! $b || ! Storage::disk('local')->exists($b->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('local')->download($b->dosya_yolu, $b->dosya_adi);
    }

    /** İşveren yazıyı okuduğunu onaylar — tebliğ kaydı (yalnız bir kez). */
    public function tebellugAction(): Action
    {
        return Action::make('tebellug')
            ->modalHeading('Okudum, tebellüğ ettim')
            ->modalDescription('Bu yazıyı okuduğunuzu ve içeriğinden haberdar olduğunuzu onaylarsınız; onay tarihi ve adınız İSG uzmanınızın kaydına işlenir.')
            ->modalSubmitActionLabel('Onaylıyorum')
            ->schema([
                TextInput::make('ad')->label('Adınız soyadınız / göreviniz')->required()->maxLength(150),
            ])
            ->action(function (array $data, array $arguments): void {
                $b = $this->bilgilendirme((int) ($arguments['id'] ?? 0));

                if ($b && ! $b->isyeri_okundu_at) {
                    $b->update(['isyeri_okundu_at' => now(), 'isyeri_onaylayan' => trim($data['ad'])]);
                }

                unset($this->bilgilendirmeler);
                Notification::make()->title('Onayınız kaydedildi')->success()->send();
            });
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
