<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\Firma;
use App\Models\User;
use App\Support\CariHesapYonetici;
use App\Support\KomutaFirma;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * OSGB & Uzman Cari Hesap, Aylık Hizmet Bedeli ve Tahsilat Takibi + PDF Hesap Ekstresi.
 */
class CariHesap extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.cari-hesap';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Hesap & Yönetim';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'cari-hesap';

    protected static ?string $title = 'OSGB Cari & Tahsilat';

    protected static ?string $navigationLabel = 'OSGB Cari & Tahsilat';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public float $aylikUcret = 0.0;

    public int $kdvOrani = 20;

    public string $hareketTur = 'tahsilat';

    public float $hareketTutar = 0.0;

    public string $hareketTarih = '';

    public string $hareketAciklama = '';

    public function mount(): void
    {
        $this->hareketTarih = now()->toDateString();

        $firmaSecenekleri = Firma::query()
            ->where('user_id', $this->kullanici()->id)
            ->where('aktif', true)
            ->orderBy('unvan')
            ->secenekler()
            ->all();

        $hedefId = request()->integer('firma') ?: KomutaFirma::baslangic($firmaSecenekleri);
        if ($hedefId) {
            $this->firmaId = $hedefId;
            KomutaFirma::sec($hedefId);
            $this->firmaYukle();
        }
    }

    private function kullanici(): User
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return $user;
    }

    #[Computed]
    public function portfoy(): array
    {
        return CariHesapYonetici::portfoyOzeti($this->kullanici());
    }

    #[Computed]
    public function secilenFirma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', $this->kullanici()->id)->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function firmaCari(): ?array
    {
        return $this->secilenFirma
            ? CariHesapYonetici::firmaOzeti($this->kullanici(), $this->secilenFirma)
            : null;
    }

    public function firmaSec(int $id): void
    {
        $this->firmaId = $id;
        KomutaFirma::sec($id);
        $this->firmaYukle();
    }

    private function firmaYukle(): void
    {
        unset($this->secilenFirma, $this->firmaCari);
        if ($c = $this->firmaCari) {
            $this->aylikUcret = (float) $c['aylik_ucret'];
            $this->kdvOrani = (int) $c['kdv'];
        }
    }

    public function ucretKaydet(): void
    {
        if (! $this->secilenFirma) {
            return;
        }

        $this->validate([
            'aylikUcret' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'kdvOrani' => ['required', 'integer', 'min:0', 'max:100'],
        ], [], ['aylikUcret' => 'aylık ücret', 'kdvOrani' => 'KDV oranı']);

        CariHesapYonetici::ucretKaydet($this->kullanici(), $this->secilenFirma->id, (float) $this->aylikUcret, (int) $this->kdvOrani);
        unset($this->portfoy, $this->firmaCari);

        Notification::make()->title('Aylık hizmet bedeli kaydedildi')->success()->send();
    }

    public function hareketEkle(): void
    {
        if (! $this->secilenFirma) {
            return;
        }

        $this->validate([
            'hareketTur' => ['required', 'in:borc,tahsilat'],
            'hareketTutar' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'hareketTarih' => ['required', 'date'],
            'hareketAciklama' => ['nullable', 'string', 'max:200'],
        ], [], [
            'hareketTur' => 'hareket türü',
            'hareketTutar' => 'tutar',
            'hareketTarih' => 'tarih',
            'hareketAciklama' => 'açıklama',
        ]);

        CariHesapYonetici::hareketEkle(
            $this->kullanici(),
            $this->secilenFirma->id,
            $this->hareketTur,
            (float) $this->hareketTutar,
            $this->hareketTarih ?: now()->toDateString(),
            $this->hareketAciklama,
        );

        $this->hareketTutar = 0.0;
        $this->hareketAciklama = '';
        unset($this->portfoy, $this->firmaCari);

        Notification::make()->title('Cari hareket işlendi')->success()->send();
    }

    public function hareketSil(string $hareketId): void
    {
        if (! $this->secilenFirma) {
            return;
        }

        CariHesapYonetici::hareketSil($this->kullanici(), $this->secilenFirma->id, $hareketId);
        \App\Support\DenetimKaydedici::kaydet('Cari Hesap', 'sildi', $this->secilenFirma->unvan.' — hareket #'.$hareketId);
        unset($this->portfoy, $this->firmaCari);

        Notification::make()->title('Hareket silindi')->success()->send();
    }

    public function buAyTahakkukOlustur(): void
    {
        $adet = CariHesapYonetici::buAyTahakkukOlustur($this->kullanici());
        unset($this->portfoy, $this->firmaCari);

        if ($adet === 0) {
            Notification::make()
                ->title('Yeni tahakkuk oluşturulmadı')
                ->body('Aylık ücreti tanımlı tüm firmaların bu ayki tahakkuku zaten işlenmiş (veya henüz aylık ücret girilmemiş).')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($adet.' firma için '.now()->translatedFormat('F Y').' tahakkuku oluşturuldu')
            ->success()
            ->send();
    }

    public function ekstrePdf(?int $firmaId = null)
    {
        $hedefId = $firmaId ?: $this->firmaId;
        $firma = $hedefId ? Firma::where('user_id', $this->kullanici()->id)->find($hedefId) : null;
        if (! $firma) {
            Notification::make()->title('Önce bir firma seçin')->warning()->send();

            return null;
        }

        return CariHesapYonetici::ekstrePdf($this->kullanici(), $firma);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('personelHakedis')
                ->label('💼 Personel Hakediş & Prim')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->outlined()
                ->modalHeading('OSGB Personel Aylık Hakediş & Prim Özeti')
                ->modalDescription('Portföyünüzdeki firmalara atanmış İGU / İşyeri Hekimi bazında aylık yasal dakika ve prim payı hesabı.')
                ->modalSubmitActionLabel('Hesapla')
                ->fillForm(['prim_yuzde' => 25, 'saatlik_birim' => 350])
                ->schema([
                    \Filament\Forms\Components\TextInput::make('prim_yuzde')
                        ->label('Net Cirodan (KDV Hariç) Prim Oranı (%)')
                        ->numeric()->minValue(0)->maxValue(100)->default(25)->required(),
                    \Filament\Forms\Components\TextInput::make('saatlik_birim')
                        ->label('Saatlik Uzman / Hekim Birim Ücreti (₺)')
                        ->numeric()->minValue(0)->default(350)->required(),
                ])
                ->action(function (array $data): void {
                    $p = $this->portfoy;
                    $primOran = (float) ($data['prim_yuzde'] ?? 25);
                    $birim = (float) ($data['saatlik_birim'] ?? 350);
                    $toplamDk = Firma::where('user_id', $this->kullanici()->id)->where('aktif', true)->sum('katip_aylik_dk');
                    $saat = round($toplamDk / 60, 1);
                    $netCiro = (float) ($p['aylik_ciro_net'] ?? 0);
                    $primTutar = round($netCiro * ($primOran / 100), 2);
                    $saatTutar = round($saat * $birim, 2);

                    Notification::make()
                        ->title('Aylık Hakediş Özeti')
                        ->body('Net Ciro (KDV Hariç): '.number_format($netCiro, 2, ',', '.').' ₺ (KDV Dahil: '.number_format($p['aylik_ciro'], 2, ',', '.')." ₺) · Toplam İSG-KATİP Süresi: {$saat} saat · Saatlik Hakediş: ".number_format($saatTutar, 2, ',', '.').' ₺ · Net Ciro Primi (%'.$primOran.'): '.number_format($primTutar, 2, ',', '.').' ₺')
                        ->success()
                        ->persistent()
                        ->send();
                }),

            Action::make('osgbSubeler')
                ->label('🏢 OSGB Şubeleri')
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->outlined()
                ->modalHeading('OSGB Çoklu Şube Profilleri')
                ->modalDescription('Birden fazla il veya şubede hizmet veren OSGB\'ler için şube tanımlayıp aktif şubeyi seçebilirsiniz.')
                ->modalSubmitActionLabel('Şube Ekle / Aktif Yap')
                ->fillForm(fn (): array => [
                    'aktif_sube' => (string) (($this->kullanici()->ayarlar ?? [])['aktif_sube'] ?? 'Merkez Şube'),
                ])
                ->schema([
                    \Filament\Forms\Components\TextInput::make('aktif_sube')
                        ->label('Aktif Şube Adı')
                        ->placeholder('Örn: Bursa Nilüfer Merkez / İstanbul Anadolu Şube')
                        ->required()
                        ->maxLength(120),
                    \Filament\Forms\Components\TextInput::make('yeni_sube_il')
                        ->label('Şube İli / Yetki Bölgesi (Opsiyonel)')
                        ->placeholder('Örn: Bursa / Kocaeli')
                        ->maxLength(80),
                ])
                ->action(function (array $data): void {
                    $user = $this->kullanici();
                    $ayar = (array) ($user->ayarlar ?? []);
                    $subeler = array_values((array) ($ayar['osgb_subeleri'] ?? []));
                    $ad = trim((string) $data['aktif_sube']);
                    if (! collect($subeler)->contains(fn ($s) => ($s['ad'] ?? null) === $ad)) {
                        $subeler[] = ['ad' => $ad, 'il' => trim((string) ($data['yeni_sube_il'] ?? ''))];
                    }
                    \App\Support\KullaniciAyarlari::kaydet($user, [
                        'aktif_sube' => $ad,
                        'osgb_subeleri' => $subeler,
                    ]);
                    Notification::make()->title("Aktif OSGB şubesi: {$ad} (Toplam ".count($subeler).' şube)')->success()->send();
                }),

            Action::make('topluTahakkuk')
                ->label('Bu Ay Tüm Firmalara Tahakkuk Oluştur')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Bu Ayın Aylık Hizmet Tahakkukları')
                ->modalDescription('Aylık hizmet bedeli tanımlanmış tüm aktif firmalara bu ayın KDV dahil hizmet tutarı (mükerrer olmadan) borç olarak işlenecektir.')
                ->action(fn () => $this->buAyTahakkukOlustur()),
        ];
    }
}
