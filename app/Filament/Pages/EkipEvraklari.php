<?php

namespace App\Filament\Pages;

use App\Models\AcilDurumPlani;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\RiskSablonu;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Sahip hesabın, yetki verdiği kişilerin (kendi hesaplarında oluşturdukları
 * firmalardaki) Risk Değerlendirmesi ve Acil Durum Planı kayıtlarını görebildiği
 * ekran. Risk Değerlendirmesi'nden seçilen bir kayıt, tek tıkla paylaşılan Risk
 * Şablonu kütüphanesine (havuz) eklenebilir — bkz. RiskSablonu::olustur().
 *
 * KullaniciYonetimi ile aynı gerekçeyle: SinirliErisim trait'i KASITLI olarak
 * kullanılmıyor, canAccess() doğrudan sahipMi() kontrol eder.
 */
class EkipEvraklari extends Page
{
    protected string $view = 'filament.pages.ekip-evraklari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ekip-evraklari';

    protected static ?string $title = 'Ekip Evrakları';

    protected static ?string $navigationLabel = 'Ekip Evrakları';

    public ?int $kullaniciId = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->sahipMi();
    }

    #[Computed]
    public function kullanicilar(): Collection
    {
        return User::where('rol', '!=', 'sahip')->orderBy('name')->get();
    }

    /** @return Collection<int, RiskDegerlendirmesi> */
    #[Computed]
    public function riskDegerlendirmeleri(): Collection
    {
        $benimId = Filament::auth()->id();

        return RiskDegerlendirmesi::query()
            ->withCount('maddeler')
            ->whereHas('firma', function ($q) use ($benimId) {
                $q->withoutGlobalScopes()->where('user_id', '!=', $benimId);

                if ($this->kullaniciId) {
                    $q->where('user_id', $this->kullaniciId);
                }
            })
            ->with(['firma' => fn ($q) => $q->withoutGlobalScopes()->with('user')])
            ->latest('rapor_tarihi')
            ->get();
    }

    /** @return Collection<int, AcilDurumPlani> */
    #[Computed]
    public function acilDurumPlanlari(): Collection
    {
        $benimId = Filament::auth()->id();

        return AcilDurumPlani::query()
            ->whereNotNull('konular')
            ->whereHas('firma', function ($q) use ($benimId) {
                $q->withoutGlobalScopes()->where('user_id', '!=', $benimId);

                if ($this->kullaniciId) {
                    $q->where('user_id', $this->kullaniciId);
                }
            })
            ->with(['firma' => fn ($q) => $q->withoutGlobalScopes()->with('user')])
            ->latest('updated_at')
            ->get()
            ->filter(fn (AcilDurumPlani $p) => filled($p->konular));
    }

    public function updatedKullaniciId(): void
    {
        unset($this->riskDegerlendirmeleri, $this->acilDurumPlanlari);
    }

    /**
     * Seçili risk değerlendirmesinin maddelerini paylaşılan Risk Şablonu
     * kütüphanesine ekler (`paylasildi = true`) — herkes Risk Sihirbazı'nda
     * "Şablonlar" adımından kullanabilir. Değerlendirmenin kendisine
     * dokunmaz, salt bir kopya oluşturur.
     */
    public function havuzaEkle(int $id): void
    {
        if (! Filament::auth()->user()?->sahipMi()) {
            return;
        }

        $rd = RiskDegerlendirmesi::with(['maddeler', 'firma' => fn ($q) => $q->withoutGlobalScopes()])->find($id);

        if (! $rd || $rd->maddeler->isEmpty()) {
            Notification::make()->title('Bu değerlendirmede madde bulunamadı')->danger()->send();

            return;
        }

        $maddeler = $rd->maddeler->map(fn (RiskMaddesi $m) => [
            'anahtar' => 'ekip-'.$m->id.'-'.substr(md5(uniqid('', true)), 0, 6),
            'kaynak' => 'ekip',
            'bolum' => $m->bolum,
            'faaliyet' => $m->faaliyet,
            'tehlike' => $m->tehlike,
            'risk' => $m->risk,
            'mevcut_onlem' => $m->mevcut_onlem,
            'oneri' => $m->oneri,
            'sorumlu' => null,
            'termin' => null,
            'olasilik' => $m->olasilik,
            'siddet' => $m->siddet,
            'frekans' => $m->frekans,
        ])->all();

        $sablon = RiskSablonu::olustur(
            Filament::auth()->user(),
            ($rd->firma_unvan ?: $rd->firma?->unvan ?: 'Ekip').' — '.now()->format('d.m.Y'),
            null,
            $rd->firma?->tehlikeSinifiEtiketi(),
            $rd->yontem,
            $maddeler,
        );
        $sablon->update(['paylasildi' => true]);

        Notification::make()
            ->title('Risk Şablonları havuzuna eklendi')
            ->body('Adını/sektörünü "Risk Şablonları" sayfasından düzenleyebilirsiniz.')
            ->success()->send();
    }
}
