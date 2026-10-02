<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\Ziyaretci;
use App\Support\ZiyaretciUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Ziyaretçi Yönetimi (isgsuite "Ziyaretçi Yönetimi") — işyerine gelen dış
 * kişilere süreli, QR'lı geçiş kartı; giriş / çıkış kaydı, İSG bilgilendirmesi
 * ve acil durum sayımı için "İçerideki Ziyaretçiler" listesi.
 */
class ZiyaretciYonetimi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.ziyaretci-yonetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Diğer Belge & Yazışma';

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'ziyaretci-yonetimi';

    protected static ?string $title = 'Ziyaretçi Yönetimi';

    protected static ?string $navigationLabel = 'Ziyaretçiler';

    // --- Liste filtreleri ---
    public ?int $firmaId = null;

    public string $donem = 'bugun';

    public ?string $durumFiltre = null;

    public ?string $arama = null;

    // --- Geçiş formu ---
    public ?int $formFirmaId = null;

    public ?string $adSoyad = null;

    public ?string $kurum = null;

    public ?string $telefon = null;

    public ?string $ziyaretAmaci = null;

    public ?string $ziyaretEdilen = null;

    public ?string $baslangic = null;

    public ?string $bitis = null;

    public bool $isgBilgilendirme = false;

    public ?string $verilenKkd = null;

    public ?string $notlar = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->formFirmaId = $firmaId;
        }

        $this->formuSifirla();
    }

    private function formuSifirla(): void
    {
        $this->reset('adSoyad', 'kurum', 'telefon', 'ziyaretAmaci', 'ziyaretEdilen', 'isgBilgilendirme', 'verilenKkd', 'notlar');
        $this->baslangic = now()->format('Y-m-d\TH:i');
        $this->bitis = now()->addHours((int) config('isg.ziyaretci.varsayilan_sure_saat', 8))->format('Y-m-d\TH:i');
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** @return Collection<int, Ziyaretci> filtrelenmiş liste */
    #[Computed]
    public function ziyaretciler(): Collection
    {
        $aranan = mb_strtolower(trim((string) $this->arama));

        return Ziyaretci::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->when($this->donem === 'bugun', fn ($q) => $q->where('gecerlilik_baslangic', '<=', now()->endOfDay())->where('gecerlilik_bitis', '>=', now()->startOfDay()))
            ->when($this->donem === 'hafta', fn ($q) => $q->where('gecerlilik_baslangic', '>=', now()->subDays(7)->startOfDay()))
            ->when($this->donem === 'ay', fn ($q) => $q->where('gecerlilik_baslangic', '>=', now()->subDays(30)->startOfDay()))
            ->with('firma')
            ->latest('gecerlilik_baslangic')
            ->latest('id')
            ->get()
            // İçeride olan, dönem dışı kalsa bile listede görünsün.
            ->when($this->donem === 'bugun', fn (Collection $c) => $c->merge(
                Ziyaretci::query()->whereIn('firma_id', array_keys($this->firmalar))
                    ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
                    ->whereNotNull('giris_zamani')->whereNull('cikis_zamani')->where('iptal', false)
                    ->with('firma')->get(),
            )->unique('id'))
            ->filter(fn (Ziyaretci $z) => ! $this->durumFiltre || $z->durum() === $this->durumFiltre)
            ->filter(fn (Ziyaretci $z) => $aranan === '' || str_contains(
                mb_strtolower(implode(' ', [$z->kart_no, $z->ad_soyad, $z->kurum, $z->ziyaret_edilen, $z->ziyaret_amaci])),
                $aranan,
            ))
            ->values();
    }

    /** @return Collection<int, Ziyaretci> şu an içeride (giriş yapmış, çıkışı yok) */
    #[Computed]
    public function iceridekiler(): Collection
    {
        return Ziyaretci::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->whereNotNull('giris_zamani')->whereNull('cikis_zamani')->where('iptal', false)
            ->with('firma')
            ->orderBy('giris_zamani')
            ->get();
    }

    /** @return array{bugun: int, iceride: int, gecerli: int} */
    #[Computed]
    public function ozet(): array
    {
        $bugunku = Ziyaretci::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->where('gecerlilik_baslangic', '<=', now()->endOfDay())
            ->where('gecerlilik_bitis', '>=', now()->startOfDay())
            ->where('iptal', false)
            ->get();

        return [
            'bugun' => $bugunku->count(),
            'iceride' => $this->iceridekiler->count(),
            'gecerli' => $bugunku->filter->gecerliMi()->count(),
        ];
    }

    private function yenile(): void
    {
        unset($this->ziyaretciler, $this->iceridekiler, $this->ozet);
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'donem', 'durumFiltre', 'arama'], true)) {
            $this->yenile();
        }

        if ($alan === 'firmaId' && $this->firmaId) {
            $this->formFirmaId = $this->firmaId;
        }
    }

    private function ziyaretci(int $id): Ziyaretci
    {
        return Ziyaretci::query()->whereIn('firma_id', array_keys($this->firmalar))->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Geçiş oluşturma ve giriş / çıkış
    |--------------------------------------------------------------------------
    */

    /** Kartı oluşturur ve PDF'ini indirir. */
    public function gecisOlustur()
    {
        $this->validate([
            'formFirmaId' => ['required', 'integer'],
            'adSoyad' => ['required', 'string', 'max:255'],
            'baslangic' => ['required', 'date'],
            'bitis' => ['required', 'date', 'after:baslangic'],
        ], [], [
            'formFirmaId' => 'işyeri',
            'adSoyad' => 'ad soyad',
            'baslangic' => 'geçerlilik başlangıcı',
            'bitis' => 'geçerlilik bitişi',
        ]);

        abort_unless(array_key_exists($this->formFirmaId, $this->firmalar), 403);

        $z = Ziyaretci::create([
            'firma_id' => $this->formFirmaId,
            'ad_soyad' => trim($this->adSoyad),
            'kurum' => $this->kurum,
            'telefon' => $this->telefon,
            'ziyaret_amaci' => $this->ziyaretAmaci,
            'ziyaret_edilen' => $this->ziyaretEdilen,
            'gecerlilik_baslangic' => Carbon::parse($this->baslangic),
            'gecerlilik_bitis' => Carbon::parse($this->bitis),
            'isg_bilgilendirme' => $this->isgBilgilendirme,
            'verilen_kkd' => $this->verilenKkd,
            'notlar' => $this->notlar,
        ]);

        $this->formuSifirla();
        $this->yenile();

        Notification::make()->title('Geçiş kartı oluşturuldu')->body($z->kart_no.' — '.$z->ad_soyad)->success()->send();

        return ZiyaretciUretici::kartPdf($z);
    }

    public function kart(int $id)
    {
        return ZiyaretciUretici::kartPdf($this->ziyaretci($id));
    }

    public function giris(int $id): void
    {
        $z = $this->ziyaretci($id);

        if (! in_array($z->durum(), ['gecerli', 'planli'], true)) {
            Notification::make()->title('Bu kartla giriş yapılamaz')->body($z->durumEtiketi())->danger()->send();

            return;
        }

        $z->update([
            'giris_zamani' => now(),
            // Planlanan saatten önce gelindiyse kart o andan geçerli olsun.
            'gecerlilik_baslangic' => $z->gecerlilik_baslangic->isFuture() ? now() : $z->gecerlilik_baslangic,
        ]);
        $this->yenile();
    }

    public function cikis(int $id): void
    {
        $z = $this->ziyaretci($id);

        if ($z->giris_zamani && ! $z->cikis_zamani) {
            $z->update(['cikis_zamani' => now()]);
        }

        $this->yenile();
    }

    public function iptalEt(int $id): void
    {
        $this->ziyaretci($id)->update(['iptal' => true]);
        $this->yenile();
    }

    public function sil(int $id): void
    {
        $this->ziyaretci($id)->delete();
        $this->yenile();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('iceridekiler_pdf')
                ->label('İçerideki Ziyaretçiler (Acil Durum)')
                ->icon('heroicon-o-users')
                ->color('danger')
                ->visible(fn () => $this->firmaId !== null)
                ->action(fn () => ZiyaretciUretici::iceridekilerPdf(Firma::findOrFail($this->firmaId), $this->iceridekiler)),

            Action::make('defter_excel')
                ->label('Ziyaretçi Defteri (Excel)')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->ziyaretciler->isNotEmpty())
                ->action(fn () => ZiyaretciUretici::defterExcel($this->ziyaretciler)),
        ];
    }
}
