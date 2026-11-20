<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\Firma;
use App\Models\User;
use App\Support\CariHesapYonetici;
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

    protected static string|UnitEnum|null $navigationGroup = 'Hesap & Arşiv';

    protected static ?int $navigationSort = 18;

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

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
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

        CariHesapYonetici::ucretKaydet($this->kullanici(), $this->secilenFirma->id, (float) $this->aylikUcret, (int) $this->kdvOrani);
        unset($this->portfoy, $this->firmaCari);

        Notification::make()->title('Aylık hizmet bedeli kaydedildi')->success()->send();
    }

    public function hareketEkle(): void
    {
        if (! $this->secilenFirma) {
            return;
        }

        if ($this->hareketTutar <= 0) {
            Notification::make()->title('Geçerli bir tutar girin')->warning()->send();

            return;
        }

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
            Action::make('topluTahakkuk')
                ->label('⚡ Bu Ay Tüm Firmalara Tahakkuk Oluştur')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Bu Ayın Aylık Hizmet Tahakkukları')
                ->modalDescription('Aylık hizmet bedeli tanımlanmış tüm aktif firmalara bu ayın KDV dahil hizmet tutarı (mükerrer olmadan) borç olarak işlenecektir.')
                ->action(fn () => $this->buAyTahakkukOlustur()),
        ];
    }
}
