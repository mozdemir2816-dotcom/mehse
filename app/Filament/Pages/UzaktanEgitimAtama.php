<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Support\UzaktanEgitimBelgesiUretici;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Uzaktan Eğitim Atama — İSG uzmanı bir eğitim paketini firmadaki çalışanlara atar;
 * portal giriş bilgisini (e-posta + geçici şifre) üretir. Atanan eğitimlerin
 * ilerlemesi ve tamamlananların katılım belgesi buradan izlenir.
 */
class UzaktanEgitimAtama extends Page
{
    protected string $view = 'filament.pages.uzaktan-egitim-atama';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 32;

    protected static ?string $slug = 'uzaktan-egitim-atama';

    protected static ?string $title = 'Uzaktan Eğitim Atama';

    protected static ?string $navigationLabel = 'Uzaktan Eğitim Atama';

    public ?int $firmaId = null;

    public ?int $paketId = null;

    public string $egitimTuru = 'yenileme';

    public ?string $sonTarih = null;

    /** @var array<int, int> */
    public array $secilenCalisanlar = [];

    /** @var array<int, array{ad_soyad: string, eposta: string, sifre: ?string}> */
    public array $sonUretilenGiris = [];

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null;
    }

    #[Computed]
    public function paketler(): array
    {
        return EgitimPaketi::query()->gorunur(Filament::auth()->id())->where('aktif', true)
            ->orderBy('ad')->get()->mapWithKeys(fn (EgitimPaketi $p) => [$p->id => $p->ad])->all();
    }

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->where('aktif', true)->orderBy('ad_soyad')->get() ?? collect();
    }

    /** @return Collection<int, EgitimAtamasi> */
    #[Computed]
    public function atamalar(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return EgitimAtamasi::query()
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firma->id))
            ->with(['calisan', 'paket'])
            ->latest()
            ->get();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->atamalar);
        $this->secilenCalisanlar = [];
        $this->sonUretilenGiris = [];
    }

    public function calisanToggle(int $id): void
    {
        $this->secilenCalisanlar = in_array($id, $this->secilenCalisanlar, true)
            ? array_values(array_diff($this->secilenCalisanlar, [$id]))
            : [...$this->secilenCalisanlar, $id];
    }

    public function tumunuSec(): void
    {
        $this->secilenCalisanlar = $this->calisanlar->pluck('id')->all();
    }

    public function ata(): void
    {
        $paket = EgitimPaketi::query()->gorunur(Filament::auth()->id())->find($this->paketId);

        if (! $paket || ! $this->firma || ! $this->secilenCalisanlar) {
            Notification::make()->title('Firma, paket ve en az bir çalışan seçin')->danger()->send();

            return;
        }

        if (! $paket->sinavaHazirMi()) {
            Notification::make()->title('Paket eksik')->body('Pakette en az bir ders ve 5 sınav sorusu olmalı.')->danger()->send();

            return;
        }

        $giris = [];
        $atananSayi = 0;
        $epostasiz = [];

        foreach ($this->calisanlar->whereIn('id', $this->secilenCalisanlar) as $calisan) {
            if (blank($calisan->eposta)) {
                $epostasiz[] = $calisan->ad_soyad;

                continue;
            }

            // Zaten aynı paket atanmışsa atlanır (unique kısıtı).
            $atama = EgitimAtamasi::firstOrNew([
                'egitim_paketi_id' => $paket->id,
                'calisan_id' => $calisan->id,
            ]);

            if (! $atama->exists) {
                $atama->fill([
                    'atayan_user_id' => Filament::auth()->id(),
                    'atandi_at' => now(),
                    'son_tarih' => $this->sonTarih ?: null,
                    'egitim_turu' => $this->egitimTuru,
                    'durum' => 'atandi',
                ])->save();
                $atananSayi++;
            }

            // Portal şifresi yoksa geçici bir tane üret.
            $gecici = null;

            if (blank($calisan->sifre)) {
                $gecici = Str::upper(Str::random(3)).random_int(100, 999);
                $calisan->forceFill(['sifre' => $gecici, 'sifre_belirlendi_at' => null])->save();
            }

            $giris[] = ['ad_soyad' => $calisan->ad_soyad, 'eposta' => $calisan->eposta, 'sifre' => $gecici];
        }

        $this->sonUretilenGiris = $giris;
        $this->secilenCalisanlar = [];
        unset($this->atamalar);

        $mesaj = $atananSayi.' çalışana eğitim atandı';
        if ($epostasiz) {
            $mesaj .= ' · e-postası olmayanlar atlandı: '.implode(', ', $epostasiz);
        }

        Notification::make()->title($mesaj)
            ->body('Portal adresi: '.url('/egitim').' — giriş bilgileri aşağıda listelendi.')
            ->success()->send();
    }

    public function sifreYenile(int $calisanId): void
    {
        $calisan = $this->firma?->calisanlar()->find($calisanId);

        if (! $calisan) {
            return;
        }

        $gecici = Str::upper(Str::random(3)).random_int(100, 999);
        $calisan->forceFill(['sifre' => $gecici, 'sifre_belirlendi_at' => null])->save();

        $this->sonUretilenGiris = [['ad_soyad' => $calisan->ad_soyad, 'eposta' => $calisan->eposta, 'sifre' => $gecici]];

        Notification::make()->title($calisan->ad_soyad.' için yeni geçici şifre üretildi')->success()->send();
    }

    public function atamaSil(int $id): void
    {
        EgitimAtamasi::query()
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firmaId))
            ->find($id)?->delete();

        unset($this->atamalar);
    }

    public function belgeIndir(int $atamaId)
    {
        $atama = EgitimAtamasi::query()
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firmaId))
            ->with(['calisan.firma', 'paket'])
            ->find($atamaId);

        if (! $atama || ! $atama->basariliMi()) {
            Notification::make()->title('Bu eğitim henüz başarıyla tamamlanmadı')->warning()->send();

            return null;
        }

        return UzaktanEgitimBelgesiUretici::pdf($atama);
    }
}
