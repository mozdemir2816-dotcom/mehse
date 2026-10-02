<?php

namespace App\Filament\Pages;

use App\Models\Bildirim;
use App\Models\Firma;
use App\Models\User;
use App\Support\BildirimTarayici;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Bildirim Merkezi (isgsuite "Bildirimler"): otomatik süre uyarıları —
 * görevlendirme / sözleşme bitişi, İSG-KATİP no eksikliği, atanmamış
 * profesyonel, doküman geçerliliği, sağlık muayenesi, geciken yıllık plan,
 * SDS / PKD gözden geçirme ve tarihli takipler. Sayfa açılınca (son taramadan
 * 1 saat geçtiyse) kendiliğinden tarar; "Süreleri Kontrol Et" elle tarar.
 */
class BildirimMerkezi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.bildirim-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'bildirimler';

    protected static ?string $title = 'Bildirim Merkezi';

    protected static ?string $navigationLabel = 'Bildirimler';

    public ?int $firmaId = null;

    public ?string $seviye = null;

    public bool $okunanlariGoster = true;

    public static function getNavigationBadge(): ?string
    {
        $sayi = Filament::auth()->id() ? Bildirim::okunmamisSayisi(Filament::auth()->id()) : 0;

        return $sayi > 0 ? (string) $sayi : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }

        BildirimTarayici::gerekirseTara($this->kullanici());
    }

    private function kullanici(): User
    {
        /** @var User */
        return Filament::auth()->user();
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', $this->kullanici()->id)->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** @return Collection<int, Bildirim> */
    #[Computed]
    public function bildirimler(): Collection
    {
        $sira = ['kritik' => 0, 'uyari' => 1, 'bilgi' => 2];

        return Bildirim::query()
            ->where('user_id', $this->kullanici()->id)
            ->acik()
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->when($this->seviye, fn ($q) => $q->where('seviye', $this->seviye))
            ->when(! $this->okunanlariGoster, fn ($q) => $q->whereNull('okundu_at'))
            ->with('firma:id,unvan')
            ->get()
            ->sortBy(fn (Bildirim $b) => [$b->okundu_at ? 1 : 0, $sira[$b->seviye] ?? 3, $b->tarih?->timestamp ?? PHP_INT_MAX])
            ->values();
    }

    #[Computed]
    public function sayilar(): array
    {
        $tumu = Bildirim::query()->where('user_id', $this->kullanici()->id)->acik()
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->get(['seviye', 'okundu_at']);

        return [
            'kritik' => $tumu->where('seviye', 'kritik')->count(),
            'uyari' => $tumu->where('seviye', 'uyari')->count(),
            'bilgi' => $tumu->where('seviye', 'bilgi')->count(),
            'okunmamis' => $tumu->whereNull('okundu_at')->count(),
        ];
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'seviye', 'okunanlariGoster'], true)) {
            $this->tazele();
        }
    }

    public function sureleriKontrolEt(): void
    {
        $sonuc = BildirimTarayici::tara($this->kullanici(), $this->firmaId ?: null);
        $this->tazele();

        Notification::make()
            ->title('Süreler kontrol edildi')
            ->body("{$sonuc['acik']} açık bildirim · {$sonuc['yeni']} yeni · {$sonuc['cozulen']} çözüldü")
            ->success()->send();
    }

    public function okunduIsaretle(int $id): void
    {
        $b = Bildirim::query()->where('user_id', $this->kullanici()->id)->find($id);
        $b?->update(['okundu_at' => $b->okundu_at ? null : now()]);
        $this->tazele();
    }

    public function tumunuOkundu(): void
    {
        Bildirim::query()->where('user_id', $this->kullanici()->id)->acik()->whereNull('okundu_at')
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->update(['okundu_at' => now()]);
        $this->tazele();
    }

    /** "Git": okundu işaretleyip ilgili modüle yönlendirir. */
    public function git(int $id): void
    {
        $b = Bildirim::query()->where('user_id', $this->kullanici()->id)->find($id);

        if (! $b) {
            return;
        }

        $b->update(['okundu_at' => $b->okundu_at ?? now()]);

        if ($b->url) {
            $this->redirect($b->url);
        }
    }

    private function tazele(): void
    {
        unset($this->bildirimler, $this->sayilar);
    }
}
