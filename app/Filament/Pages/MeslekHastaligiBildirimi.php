<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\MeslekHastaligiBildirimi as MeslekHastaligiBildirimiModel;
use App\Support\MeslekHastaligiBildirimiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Meslek Hastalığı Bildirimi — 6331 s.K. m.14: hastalığın meslek hastalığı
 * olduğunun işyeri hekimi/sağlık kuruluşu/sigortalı tarafından öğrenildiği
 * tarihten itibaren işveren 3 iş günü içinde SGK'ya bildirmekle yükümlüdür.
 */
class MeslekHastaligiBildirimi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.meslek-hastaligi-bildirimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|UnitEnum|null $navigationGroup = 'Sağlık Gözetimi';

    protected static ?int $navigationSort = 27;

    protected static ?string $slug = 'meslek-hastaligi-bildirimi';

    protected static ?string $title = 'Meslek Hastalığı Bildirimi';

    protected static ?string $navigationLabel = 'Meslek Hastalığı Bildirimi';

    public ?int $firmaId = null;

    public ?int $calisanHizliSecId = null;

    public ?string $calisanAdSoyad = null;

    public ?string $calisanTc = null;

    public ?string $calisanGorevi = null;

    public string $ogrenmeKaynagi = 'isyeri_hekimi';

    public ?string $ogrenmeTarihi = null;

    public ?string $taniHastane = null;

    public ?string $taniTarihi = null;

    public ?string $saglikKuruluRaporNo = null;

    public ?string $meslekHastaligiTanisi = null;

    public bool $sgkBildirimiYapildi = false;

    public ?string $sgkBildirimTarihi = null;

    public ?string $sgkBildirimYontemi = null;

    public string $sgkKurulOnayDurumu = 'bekliyor';

    public ?string $meslekteKazanmaGucuKaybiYuzde = null;

    public ?string $notlar = null;

    public function mount(): void
    {
        $this->ogrenmeTarihi = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

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

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function ogrenmeKaynaklari(): array
    {
        return config('isg.meslek_hastaligi.ogrenme_kaynaklari');
    }

    #[Computed]
    public function bildirimYontemleri(): array
    {
        return config('isg.meslek_hastaligi.bildirim_yontemleri');
    }

    #[Computed]
    public function kurulOnayDurumlari(): array
    {
        return config('isg.meslek_hastaligi.kurul_onay_durumlari');
    }

    /** Öğrenme tarihinden itibaren 3 iş günü — canlı önizleme (kaydetmeden). */
    #[Computed]
    public function bildirimSonTarihiOnizleme(): ?string
    {
        return blank($this->ogrenmeTarihi)
            ? null
            : \Illuminate\Support\Carbon::parse($this->ogrenmeTarihi)->addWeekdays(3)->format('d.m.Y');
    }

    /** @return Collection<int, MeslekHastaligiBildirimiModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->meslekHastaligiBildirimleri()->latest('ogrenme_tarihi')->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisKayitlar);
    }

    public function updatedCalisanHizliSecId(): void
    {
        $c = $this->calisanHizliSecId ? $this->calisanlar->firstWhere('id', $this->calisanHizliSecId) : null;

        $this->calisanAdSoyad = $c?->ad_soyad;
        $this->calisanTc = $c?->tc;
        $this->calisanGorevi = $c?->gorev;
    }

    private function kaydet(): ?MeslekHastaligiBildirimiModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (blank($this->calisanAdSoyad) || blank($this->ogrenmeTarihi)) {
            Notification::make()->title('Çalışan adı ve öğrenme tarihi zorunlu')->danger()->send();

            return null;
        }

        $m = new MeslekHastaligiBildirimiModel([
            'firma_id' => $this->firma->id,
            'calisan_id' => $this->calisanHizliSecId,
            'calisan_ad_soyad' => $this->calisanAdSoyad,
            'calisan_tc' => $this->calisanTc,
            'calisan_gorevi' => $this->calisanGorevi,
            'ogrenme_kaynagi' => $this->ogrenmeKaynagi,
            'ogrenme_tarihi' => $this->ogrenmeTarihi,
            'tani_hastane' => $this->taniHastane,
            'tani_tarihi' => $this->taniTarihi,
            'saglik_kurulu_rapor_no' => $this->saglikKuruluRaporNo,
            'meslek_hastaligi_tanisi' => $this->meslekHastaligiTanisi,
            'sgk_bildirimi_yapildi' => $this->sgkBildirimiYapildi,
            'sgk_bildirim_tarihi' => $this->sgkBildirimiYapildi ? $this->sgkBildirimTarihi : null,
            'sgk_bildirim_yontemi' => $this->sgkBildirimiYapildi ? $this->sgkBildirimYontemi : null,
            'sgk_kurul_onay_durumu' => $this->sgkKurulOnayDurumu,
            'meslekte_kazanma_gucu_kaybi_yuzde' => $this->meslekteKazanmaGucuKaybiYuzde ?: null,
            'notlar' => $this->notlar,
        ]);
        $m->save();

        unset($this->gecmisKayitlar);

        return $m;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Bildirimi Kaydet ve PDF Oluştur')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $m = $this->kaydet();

                    if (! $m) {
                        return null;
                    }

                    Notification::make()->title('Meslek hastalığı bildirimi kaydedildi')->body($m->belge_no)->success()->send();

                    return MeslekHastaligiBildirimiUretici::pdf($m);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $m = $this->firma?->meslekHastaligiBildirimleri()->find($id);

        return $m ? MeslekHastaligiBildirimiUretici::pdf($m) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->meslekHastaligiBildirimleri()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
