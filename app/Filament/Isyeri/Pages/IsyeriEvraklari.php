<?php

namespace App\Filament\Isyeri\Pages;

use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\IsyeriHesabi;
use App\Models\OnBilgilendirme;
use App\Models\SahaBulgusu;
use App\Support\CalisanDosyasiUretici;
use App\Support\CalisanListesiUretici;
use App\Support\FirmaEvrakZipUretici;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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

    /** Firmanın açık saha bulguları / DÖF maddeleri — işveren kapanış kanıtı yükleyip İGU onayına sunar (F1). */
    #[Computed]
    public function acikBulgular(): Collection
    {
        return SahaBulgusu::query()
            ->where('firma_id', $this->firma->id)
            ->where('durum', '!=', 'kapandi')
            ->orderByRaw('termin IS NULL, termin ASC')
            ->latest('id')
            ->take(25)
            ->get();
    }

    /** İşveren DÖF kapanış kanıtı gönderir — doğrudan kapatmaz, İGU onayına düşer (F1). */
    public function dofKanitGonderAction(): Action
    {
        return Action::make('dofKanitGonder')
            ->modalHeading('DÖF Kapanış Kanıtı Gönder')
            ->modalDescription('Uygunsuzluğu giderdiğinize dair fotoğraf veya belge yükleyin. İSG uzmanınız doğrulayıp onayladıktan sonra DÖF kapatılacaktır.')
            ->modalSubmitActionLabel('Uzman Onayına Gönder')
            ->schema([
                TextInput::make('gonderen')->label('Adınız Soyadınız / Göreviniz')->required()->maxLength(150),
                Textarea::make('not')->label('Yapılan Düzeltici İşlem')->rows(3)->required(),
                FileUpload::make('dosya')
                    ->label('Kapanış Kanıtı (Fotoğraf / PDF)')
                    ->disk('public')
                    ->directory('dof-kanit')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->maxSize(10240),
            ])
            ->action(function (array $data, array $arguments): void {
                $bulgu = SahaBulgusu::query()
                    ->where('firma_id', $this->firma->id)
                    ->where('durum', '!=', 'kapandi')
                    ->find((int) ($arguments['id'] ?? 0));

                if (! $bulgu) {
                    Notification::make()->title('Açık bulgu bulunamadı')->danger()->send();

                    return;
                }

                $bulgu->update([
                    'isveren_kapanis_talebi_at' => now(),
                    'isveren_kapanis_gonderen' => trim($data['gonderen']),
                    'isveren_kapanis_notu' => trim($data['not']),
                    'isveren_kapanis_dosya' => $data['dosya'] ?? $bulgu->isveren_kapanis_dosya,
                    'durum' => $bulgu->durum === 'acik' ? 'devam_ediyor' : $bulgu->durum,
                ]);

                unset($this->acikBulgular);
                Notification::make()
                    ->title('Kanıt İSG uzmanınıza iletildi')
                    ->body($bulgu->bulgu_no.' için kapanış talebiniz uzman onayına alındı.')
                    ->success()
                    ->send();
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
