<?php

namespace App\Filament\Portal\Pages;

use App\Models\EgitimAtamasi;
use App\Models\EgitimDersIlerlemesi;
use App\Models\EgitimSinavSonucu;
use App\Support\UzaktanEgitimBelgesiUretici;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Çalışanın bir eğitimi izlediği / sınava girdiği ekran. Modlar: izle | sinav | sonuc.
 * Video ilerlemesi tarayıcıdan (YouTube IFrame API) `dersIlerleme()` ile gelir.
 */
class EgitimIzle extends Page
{
    protected string $view = 'filament.portal.pages.egitim-izle';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'izle/{atama}';

    protected static ?string $title = 'Eğitim';

    #[Url]
    public int $atama = 0;

    public string $mod = 'izle';

    public ?int $aktifDersId = null;

    /** @var array<int, ?int> soru id => seçilen şık index */
    public array $cevaplar = [];

    /** @var array<int, array<string, mixed>> */
    public array $sinavSorulari = [];

    public ?EgitimSinavSonucu $sonSonuc = null;

    public function mount(): void
    {
        $a = $this->atamaKaydi();

        abort_unless($a !== null, 404);

        $this->aktifDersId = $a->paket->dersler->first()?->id;

        if ($a->basariliMi()) {
            $this->mod = 'sonuc';
            $this->sonSonuc = $a->sonSinav();
        }
    }

    #[Computed]
    public function atamaKaydi(): ?EgitimAtamasi
    {
        return EgitimAtamasi::query()
            ->where('calisan_id', Filament::auth()->id())
            ->with(['paket.dersler', 'paket.sorular', 'ilerlemeler'])
            ->find($this->atama);
    }

    #[Computed]
    public function izlenenDersIdler(): array
    {
        return $this->atamaKaydi()?->ilerlemeler->where('izlendi', true)->pluck('egitim_dersi_id')->all() ?? [];
    }

    public function dersSec(int $dersId): void
    {
        $this->aktifDersId = $dersId;
    }

    /** Tarayıcı JS'inden periyodik olarak çağrılır: video izleme yüzdesi. */
    public function dersIlerleme(int $dersId, int $yuzde): void
    {
        $a = $this->atamaKaydi();
        $ders = $a?->paket->dersler->firstWhere('id', $dersId);

        if (! $a || ! $ders) {
            return;
        }

        $yuzde = max(0, min(100, $yuzde));
        $zorunlu = $a->paket->video_zorunlu_yuzde;

        $ilerleme = EgitimDersIlerlemesi::firstOrNew([
            'egitim_atamasi_id' => $a->id,
            'egitim_dersi_id' => $dersId,
        ]);

        if ($yuzde <= $ilerleme->izleme_yuzdesi && $ilerleme->izlendi) {
            return;
        }

        $ilerleme->izleme_yuzdesi = max($ilerleme->izleme_yuzdesi, $yuzde);

        if (! $ilerleme->izlendi && $ilerleme->izleme_yuzdesi >= $zorunlu) {
            $ilerleme->izlendi = true;
            $ilerleme->izlendi_at = now();
        }

        $ilerleme->save();

        unset($this->atamaKaydi, $this->izlenenDersIdler);
        $a->durumuTazele();
    }

    /** "İzledim" — YouTube dışı videolar için elle onay. */
    public function dersTamamla(int $dersId): void
    {
        $this->dersIlerleme($dersId, 100);
        Notification::make()->title('Ders izlendi olarak işaretlendi')->success()->send();
    }

    public function sinavaBasla(): void
    {
        $a = $this->atamaKaydi();

        if (! $a || ! $a->tumDerslerIzlendiMi()) {
            Notification::make()->title('Önce tüm dersleri izlemelisiniz')->warning()->send();

            return;
        }

        $this->sinavSorulari = $a->paket->sinavSorulari();
        $this->cevaplar = [];
        $this->mod = 'sinav';
    }

    public function sinaviGonder(): void
    {
        $a = $this->atamaKaydi();

        if (! $a || $this->mod !== 'sinav' || ! $this->sinavSorulari) {
            return;
        }

        if (count(array_filter($this->cevaplar, fn ($c) => $c !== null && $c !== '')) < count($this->sinavSorulari)) {
            Notification::make()->title('Tüm soruları cevaplayın')->warning()->send();

            return;
        }

        $dogru = 0;
        $cevapDetay = [];

        foreach ($this->sinavSorulari as $s) {
            $verilen = (int) ($this->cevaplar[$s['id']] ?? -1);
            $isDogru = $verilen === (int) $s['dogru_index'];
            $dogru += $isDogru ? 1 : 0;

            $cevapDetay[] = [
                'soru' => $s['soru'],
                'secenekler' => $s['secenekler'],
                'dogru_index' => $s['dogru_index'],
                'verilen_index' => $verilen,
            ];
        }

        $puan = (int) round($dogru / count($this->sinavSorulari) * 100);
        $gecti = $puan >= $a->paket->gecme_puani;

        $sonuc = $a->sinavSonuclari()->create([
            'deneme_no' => $a->sonrakiDeneme(),
            'puan' => $puan,
            'gecti' => $gecti,
            'cevaplar' => $cevapDetay,
            'tamamlandi_at' => now(),
        ]);

        $a->durumuTazele();

        $this->sonSonuc = $sonuc;
        $this->mod = 'sonuc';
        $this->sinavSorulari = [];
        unset($this->atamaKaydi);

        Notification::make()
            ->title($gecti ? "Tebrikler! Sınavı %{$puan} ile geçtiniz." : "Sınav puanınız: %{$puan} (geçme: %{$a->paket->gecme_puani})")
            ->{$gecti ? 'success' : 'danger'}()
            ->send();
    }

    public function tekrarDene(): void
    {
        $this->mod = 'izle';
        $this->sonSonuc = null;
    }

    public function belgeIndir()
    {
        $a = $this->atamaKaydi();

        if (! $a || ! $a->basariliMi()) {
            return null;
        }

        return UzaktanEgitimBelgesiUretici::pdf($a);
    }
}
