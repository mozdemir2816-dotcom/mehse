<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\YillikPlan as YillikPlanModel;
use App\Support\YillikPlanUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Yıllık Planlar — isgpratik 86-87.jpg. Firma + yıl seçilince 14 varsayılan
 * faaliyet otomatik yüklenir; her ay hücresi tıklanarak boş → planlandı →
 * tamamlandı arasında döner.
 */
class YillikPlanlar extends Page
{
    protected string $view = 'filament.pages.yillik-planlar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'yillik-planlar';

    protected static ?string $title = 'Yıllık Planlar';

    protected static ?string $navigationLabel = 'Yıllık Planlar';

    public const AYLAR = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

    public const DURUM_SIRASI = ['bos', 'planlandi', 'tamamlandi'];

    public ?int $firmaId = null;

    public int $yil;

    public ?string $yeniFaaliyet = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniAciklama = null;

    public function mount(): void
    {
        $this->yil = (int) now()->format('Y');

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

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

    #[Computed]
    public function plan(): ?YillikPlanModel
    {
        return $this->firma ? YillikPlanModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->plan);
    }

    public function updatedYil(): void
    {
        unset($this->plan);
    }

    /*
    |--------------------------------------------------------------------------
    | Faaliyet & ay durumu
    |--------------------------------------------------------------------------
    */

    public function ayDurumDegistir(int $faaliyetIndex, int $ayIndex): void
    {
        $p = $this->plan();
        $faaliyetler = $p?->faaliyetler ?? [];

        if (! $p || ! isset($faaliyetler[$faaliyetIndex])) {
            return;
        }

        $mevcut = $faaliyetler[$faaliyetIndex]['aylar'][$ayIndex] ?? 'bos';
        $siraIndex = array_search($mevcut, self::DURUM_SIRASI, true);
        $yeni = self::DURUM_SIRASI[($siraIndex + 1) % count(self::DURUM_SIRASI)];

        $faaliyetler[$faaliyetIndex]['aylar'][$ayIndex] = $yeni;
        $p->update(['faaliyetler' => $faaliyetler]);
    }

    public function faaliyetEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniFaaliyet)) {
            return;
        }

        $faaliyetler = $p->faaliyetler ?? [];
        $faaliyetler[] = [
            'faaliyet' => $this->yeniFaaliyet,
            'sorumlu' => $this->yeniSorumlu,
            'aciklama' => $this->yeniAciklama,
            'aylar' => array_fill(0, 12, 'bos'),
        ];
        $p->update(['faaliyetler' => $faaliyetler]);

        $this->reset('yeniFaaliyet', 'yeniSorumlu', 'yeniAciklama');
    }

    public function faaliyetSil(int $index): void
    {
        $p = $this->plan();
        $faaliyetler = $p?->faaliyetler ?? [];

        if (! $p || ! isset($faaliyetler[$index])) {
            return;
        }

        unset($faaliyetler[$index]);
        $p->update(['faaliyetler' => array_values($faaliyetler)]);
    }

    public function varsayilanaSifirla(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $p->update([
            'faaliyetler' => collect(config('isg.yillik_plan.varsayilan_faaliyetler'))
                ->map(fn ($f) => [...$f, 'aylar' => array_fill(0, 12, 'bos')])
                ->all(),
        ]);

        Notification::make()->title('Varsayılan faaliyetlere sıfırlandı')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Çıktı İndir (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->plan() !== null)
                ->action(fn () => YillikPlanUretici::pdf($this->plan())),
        ];
    }
}
