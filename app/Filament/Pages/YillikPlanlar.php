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
 * Yıllık Planlar — isgpratik 86-90.jpg. 3 sekme: Çalışma Planı / Eğitim
 * Planı (ikisi de ay durum matrisli — Boş→Planlandı→Tamamlandı) /
 * Değerlendirme Raporu (satır bazlı serbest metin, ay matrisi yok).
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

    public string $sekme = 'calisma';

    public ?string $yeniFaaliyet = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniAciklama = null;

    public ?string $yeniEgitimKonu = null;

    public ?string $yeniEgitimSure = null;

    public ?string $yeniEgitimEgitici = null;

    public ?string $yeniEgitimHedefKitle = null;

    public ?string $yeniDegerlendirmeCalisma = null;

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
    | Ay durum matrisi (Çalışma Planı + Eğitim Planı ortak) — $alan: 'faaliyetler'|'egitimler'
    |--------------------------------------------------------------------------
    */

    public function ayDurumDegistir(string $alan, int $index, int $ayIndex): void
    {
        $p = $this->plan();
        $satirlar = $p?->{$alan} ?? [];

        if (! $p || ! isset($satirlar[$index])) {
            return;
        }

        $mevcut = $satirlar[$index]['aylar'][$ayIndex] ?? 'bos';
        $siraIndex = array_search($mevcut, self::DURUM_SIRASI, true);
        $yeni = self::DURUM_SIRASI[($siraIndex + 1) % count(self::DURUM_SIRASI)];

        $satirlar[$index]['aylar'][$ayIndex] = $yeni;
        $p->update([$alan => $satirlar]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Çalışma Planı
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Yıllık Eğitim Planı
    |--------------------------------------------------------------------------
    */

    public function egitimEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniEgitimKonu)) {
            return;
        }

        $egitimler = $p->egitimler ?? [];
        $egitimler[] = [
            'konu' => $this->yeniEgitimKonu,
            'sure_saat' => $this->yeniEgitimSure ?: null,
            'egitici' => $this->yeniEgitimEgitici,
            'hedef' => null,
            'hedef_kitle' => $this->yeniEgitimHedefKitle,
            'aylar' => array_fill(0, 12, 'bos'),
        ];
        $p->update(['egitimler' => $egitimler]);

        $this->reset('yeniEgitimKonu', 'yeniEgitimSure', 'yeniEgitimEgitici', 'yeniEgitimHedefKitle');
    }

    public function egitimSil(int $index): void
    {
        $p = $this->plan();
        $egitimler = $p?->egitimler ?? [];

        if (! $p || ! isset($egitimler[$index])) {
            return;
        }

        unset($egitimler[$index]);
        $p->update(['egitimler' => array_values($egitimler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Değerlendirme Raporu (ay matrisi yok, satır bazlı serbest metin)
    |--------------------------------------------------------------------------
    */

    public function degerlendirmeGuncelle(int $index, string $alan, string $deger): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index]) || ! in_array($alan, ['tarih', 'yapan_kisi', 'tekrar_sayisi', 'yontem', 'sonuc'], true)) {
            return;
        }

        $degerlendirmeler[$index][$alan] = $deger;
        $p->update(['degerlendirmeler' => $degerlendirmeler]);
    }

    public function degerlendirmeEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniDegerlendirmeCalisma)) {
            return;
        }

        $degerlendirmeler = $p->degerlendirmeler ?? [];
        $degerlendirmeler[] = [
            'calisma' => $this->yeniDegerlendirmeCalisma,
            'yapan_kisi' => null, 'yontem' => null, 'sonuc' => null,
            'tarih' => null, 'tekrar_sayisi' => null,
        ];
        $p->update(['degerlendirmeler' => $degerlendirmeler]);

        $this->reset('yeniDegerlendirmeCalisma');
    }

    public function degerlendirmeSil(int $index): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index])) {
            return;
        }

        unset($degerlendirmeler[$index]);
        $p->update(['degerlendirmeler' => array_values($degerlendirmeler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Ortak
    |--------------------------------------------------------------------------
    */

    public function varsayilanaSifirla(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        match ($this->sekme) {
            'egitim' => $p->update([
                'egitimler' => collect(config('isg.yillik_plan.varsayilan_egitimler'))
                    ->map(fn ($e) => [...$e, 'aylar' => array_fill(0, 12, 'bos')])
                    ->all(),
            ]),
            'degerlendirme' => $p->update([
                'degerlendirmeler' => collect(config('isg.yillik_plan.varsayilan_degerlendirmeler'))
                    ->map(fn ($d) => [...$d, 'tarih' => null, 'tekrar_sayisi' => null])
                    ->all(),
            ]),
            default => $p->update([
                'faaliyetler' => collect(config('isg.yillik_plan.varsayilan_faaliyetler'))
                    ->map(fn ($f) => [...$f, 'aylar' => array_fill(0, 12, 'bos')])
                    ->all(),
            ]),
        };

        Notification::make()->title('Bu sekme varsayılan içeriğe sıfırlandı')->success()->send();
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
