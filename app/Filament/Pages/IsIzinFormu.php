<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsIzinFormu as IsIzinFormuModel;
use App\Support\IsIzinFormuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * İş İzin Formu (Permit to Work) — isgpratik 76-78.jpg. İzin türüne göre
 * ilgili güvenlik önlemi maddeleri dinamik gösterilir.
 */
class IsIzinFormu extends Page
{
    protected string $view = 'filament.pages.is-izin-formu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 22;

    protected static ?string $slug = 'is-izin-formu';

    protected static ?string $title = 'İş İzin Formu (PTW)';

    protected static ?string $navigationLabel = 'İş İzin Formu';

    public ?int $firmaId = null;

    public ?string $calismaAlani = null;

    public ?string $isDetayi = null;

    public ?string $baslangic = null;

    public ?string $bitis = null;

    /** @var array<int, string> */
    public array $izinTurleri = [];

    /** @var array<int, string> */
    public array $secilenOnlemler = [];

    /** @var array<int, string> */
    public array $secilenKkdler = [];

    public ?string $onay1Baslik = null;

    public ?string $onay1Ad = null;

    public ?string $onay2Baslik = null;

    public ?string $onay2Ad = null;

    public function mount(): void
    {
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
    public function turler(): array
    {
        return config('isg.is_izin.turler');
    }

    #[Computed]
    public function kkdSecenekleri(): array
    {
        return config('isg.is_izin.kkd_secenekleri');
    }

    /** İzin türüne göre gösterilecek önlem maddeleri (genel + seçili türler). */
    #[Computed]
    public function onlemler(): array
    {
        return collect(config('isg.is_izin.guvenlik_onlemleri'))
            ->filter(fn ($o) => $o['tur'] === null || in_array($o['tur'], $this->izinTurleri, true))
            ->pluck('madde')
            ->all();
    }

    /** @return Collection<int, IsIzinFormuModel> */
    #[Computed]
    public function gecmisFormlar(): Collection
    {
        return $this->firma?->isIzinFormlari()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->gecmisFormlar);
    }

    public function updatedIzinTurleri(): void
    {
        unset($this->onlemler);
        $gecerliMaddeler = $this->onlemler();
        $this->secilenOnlemler = array_values(array_intersect($this->secilenOnlemler, $gecerliMaddeler));
    }

    /*
    |--------------------------------------------------------------------------
    | Seçimler
    |--------------------------------------------------------------------------
    */

    public function izinTuruToggle(string $anahtar): void
    {
        $this->izinTurleri = in_array($anahtar, $this->izinTurleri, true)
            ? array_values(array_diff($this->izinTurleri, [$anahtar]))
            : [...$this->izinTurleri, $anahtar];

        $this->updatedIzinTurleri();
    }

    public function onlemToggle(string $madde): void
    {
        $this->secilenOnlemler = in_array($madde, $this->secilenOnlemler, true)
            ? array_values(array_diff($this->secilenOnlemler, [$madde]))
            : [...$this->secilenOnlemler, $madde];
    }

    public function kkdToggle(string $kkd): void
    {
        $this->secilenKkdler = in_array($kkd, $this->secilenKkdler, true)
            ? array_values(array_diff($this->secilenKkdler, [$kkd]))
            : [...$this->secilenKkdler, $kkd];
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?IsIzinFormuModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        $form = new IsIzinFormuModel([
            'firma_id' => $this->firma->id,
            'calisma_alani' => $this->calismaAlani,
            'is_detayi' => $this->isDetayi,
            'baslangic' => $this->baslangic,
            'bitis' => $this->bitis,
            'izin_turleri' => $this->izinTurleri,
            'guvenlik_onlemleri' => $this->secilenOnlemler,
            'gerekli_kkdler' => $this->secilenKkdler,
            'onay1_baslik' => $this->onay1Baslik,
            'onay1_ad' => $this->onay1Ad,
            'onay2_baslik' => $this->onay2Baslik,
            'onay2_ad' => $this->onay2Ad,
        ]);
        $form->save();

        unset($this->gecmisFormlar);

        return $form;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Formu Onayla ve PDF Oluştur')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $form = $this->kaydet();

                    return $form ? IsIzinFormuUretici::pdf($form) : null;
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $form = $this->firma?->isIzinFormlari()->find($id);

        return $form ? IsIzinFormuUretici::pdf($form) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->isIzinFormlari()->find($id)?->delete();
        unset($this->gecmisFormlar);
    }
}
