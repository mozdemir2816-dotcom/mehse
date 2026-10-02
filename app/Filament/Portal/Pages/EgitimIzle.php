<?php

namespace App\Filament\Portal\Pages;

use App\Models\EgitimAtamasi;
use App\Models\EgitimDersIlerlemesi;
use App\Models\EgitimGirisi;
use App\Models\EgitimPaketiSorusu;
use App\Models\EgitimSinavSonucu;
use App\Support\UzaktanEgitimBelgesiUretici;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Çalışanın bir eğitimi izlediği / sınava girdiği ekran. Modlar:
 * on_test | izle | sinav | sonuc.
 *
 * Çalışanların İSG Eğitimleri Yönetmeliği (RG 02.04.2026) uyumu:
 * - Md.12/4 oturum giriş / son etkinlik (çıkış) ve fiilen izlenen süre
 *   `EgitimGirisi`ne yazılır.
 * - Md.12/5 ileri sarma engeli: ilerleme videonun konumundan değil, fiilen
 *   oynatılan saniyeden hesaplanır; sunucu, iki rapor arasında geçen gerçek
 *   süreden fazlasını kabul etmez. Sekme gizlenince video durur (tarayıcı).
 * - Md.12/5-c aktif katılım: belirli aralıkla açılır pencere + kısa soru.
 * - Md.16/1 ön test, Md.16/3 en az 60 puan ve üç sınav hakkı.
 */
class EgitimIzle extends Page
{
    protected string $view = 'filament.portal.pages.egitim-izle';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'izle/{atama}';

    protected static ?string $title = 'Eğitim';

    #[Url]
    public int $atama = 0;

    #[Url]
    public ?int $ders = null;

    public string $mod = 'izle';

    public ?int $aktifDersId = null;

    /** @var array<int, ?int> soru id => seçilen şık index */
    public array $cevaplar = [];

    /** @var array<int, array<string, mixed>> doğru cevap İÇERMEZ (istemciye gider) */
    public array $sinavSorulari = [];

    public ?EgitimSinavSonucu $sonSonuc = null;

    public function mount(): void
    {
        $a = $this->atamaKaydi();

        abort_unless($a !== null, 404);

        session()->put($this->oturumAnahtari('oturum'), EgitimGirisi::kaydet($a->calisan_id, $a->id)->id);

        $dersler = $a->paket->dersler;
        $this->aktifDersId = $dersler->firstWhere('id', $this->ders)?->id ?? $dersler->first()?->id;

        if ($a->basariliMi()) {
            $this->mod = 'sonuc';
            $this->sonSonuc = $a->sonSinav();
        } elseif ($a->onTestBekliyorMu()) {
            $this->soruHazirla($a);
            $this->mod = 'on_test';
        }
    }

    #[Computed]
    public function atamaKaydi(): ?EgitimAtamasi
    {
        return EgitimAtamasi::query()
            ->where('calisan_id', Filament::auth()->id())
            ->with(['paket.dersler', 'paket.sorular', 'ilerlemeler', 'calisan.firma'])
            ->find($this->atama);
    }

    #[Computed]
    public function izlenenDersIdler(): array
    {
        return $this->atamaKaydi()?->ilerlemeler->where('izlendi', true)->pluck('egitim_dersi_id')->all() ?? [];
    }

    /** Video arasında sorulacak kısa sorular (doğru cevap gönderilmez). */
    #[Computed]
    public function yoklamaSorulari(): array
    {
        return $this->atamaKaydi()?->paket->sorular->shuffle()->take(10)
            ->map(fn (EgitimPaketiSorusu $s) => ['id' => $s->id, 'soru' => $s->soru, 'secenekler' => $s->secenekler])
            ->values()->all() ?? [];
    }

    public function dersSec(int $dersId): void
    {
        // Tam sayfa geçiş: yeni dersin oynatıcı betiği temiz başlar.
        $this->redirect(static::getUrl(['atama' => $this->atama]).'?ders='.$dersId);
    }

    /**
     * Tarayıcıdan ~15 sn'de bir: dersin oynatıcıdaki konumu, son rapordan beri
     * fiilen oynatılan saniye ve video süresi. Oynatma süresi sunucuda, son
     * rapordan beri geçen gerçek süreyle sınırlanır (ileri sarma / sahte artış
     * sayılmaz); konum yalnız oynatılan kadar ilerleyebilir.
     */
    public function dersIlerleme(int $dersId, int $konumSn, int $artisSn, int $sureSn = 0): void
    {
        $a = $this->atamaKaydi();
        $ders = $a?->paket->dersler->firstWhere('id', $dersId);

        if (! $a || ! $ders) {
            return;
        }

        $simdi = now()->getTimestamp();
        $anahtar = $this->oturumAnahtari('son').'_'.$dersId;
        $son = session()->get($anahtar);
        $izinli = $son === null ? 20 : $simdi - $son + 3;
        session()->put($anahtar, $simdi);

        $artis = max(0, min($artisSn, $izinli, 120));

        if (! $ders->sure_sn && $sureSn > 0) {
            $ders->forceFill(['sure_sn' => $sureSn])->save();
        }

        $ilerleme = EgitimDersIlerlemesi::firstOrNew(['egitim_atamasi_id' => $a->id, 'egitim_dersi_id' => $dersId]);
        $ilerleme->izlenen_sn = (int) $ilerleme->izlenen_sn + $artis;
        // Videoda en fazla fiilen oynatılan toplam süre kadar ileri gidilebilir.
        $ilerleme->son_konum_sn = max((int) $ilerleme->son_konum_sn, min(max(0, $konumSn), $ilerleme->izlenen_sn + 10));

        $sure = $ders->sure_sn ?: $sureSn;
        $yuzde = $sure > 0 ? min(100, (int) floor($ilerleme->izlenen_sn / $sure * 100)) : 0;
        $ilerleme->izleme_yuzdesi = max((int) $ilerleme->izleme_yuzdesi, $yuzde);

        $this->isaretle($ilerleme, $a);
        $this->oturum()?->nabiz($artis);
    }

    /** "İzledim" — YouTube dışı videolar (izleme süresi ölçülemez) için elle onay. */
    public function dersTamamla(int $dersId): void
    {
        $a = $this->atamaKaydi();
        $ders = $a?->paket->dersler->firstWhere('id', $dersId);

        if (! $a || ! $ders || $ders->saglayici === 'youtube') {
            return;
        }

        $ilerleme = EgitimDersIlerlemesi::firstOrNew(['egitim_atamasi_id' => $a->id, 'egitim_dersi_id' => $dersId]);
        $ilerleme->izleme_yuzdesi = 100;
        $this->isaretle($ilerleme, $a);
        $this->oturum()?->nabiz();

        Notification::make()->title('Ders izlendi olarak işaretlendi')->success()->send();
    }

    private function isaretle(EgitimDersIlerlemesi $ilerleme, EgitimAtamasi $a): void
    {
        if (! $ilerleme->izlendi && $ilerleme->izleme_yuzdesi >= $a->paket->video_zorunlu_yuzde) {
            $ilerleme->izlendi = true;
            $ilerleme->izlendi_at = now();
        }

        $ilerleme->save();

        unset($this->atamaKaydi, $this->izlenenDersIdler);
        $a->durumuTazele();
    }

    /** Sayfa açıkken dakikada bir — oturumun son etkinliği (çıkış saati). */
    public function nabiz(): void
    {
        $this->oturum()?->nabiz();
    }

    /** Aktif katılım penceresine verilen cevap (Md.12/5-c). */
    public function yoklamaCevap(int $soruId, int $secenek): void
    {
        $this->oturum()?->nabiz(0, true);
    }

    public function onTestiGonder(): void
    {
        $a = $this->atamaKaydi();

        if (! $a || $this->mod !== 'on_test' || ! $this->sinavSorulari) {
            return;
        }

        $a->forceFill(['on_test_puani' => $this->puanla()['puan'], 'on_test_at' => now()])->save();

        $this->sinavSorulari = [];
        $this->cevaplar = [];
        $this->mod = 'izle';
        unset($this->atamaKaydi);

        Notification::make()->title('Seviye tespit testi kaydedildi')->body('Şimdi derslere başlayabilirsiniz.')->success()->send();
    }

    public function sinavaBasla(): void
    {
        $a = $this->atamaKaydi();

        if (! $a || ! $a->tumDerslerIzlendiMi()) {
            Notification::make()->title('Önce tüm dersleri izlemelisiniz')->warning()->send();

            return;
        }

        if ($a->kalanSinavHakki() < 1) {
            return;
        }

        $this->soruHazirla($a);
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

        ['puan' => $puan, 'detay' => $cevapDetay] = $this->puanla();
        $gecti = $puan >= $a->gecmePuani();

        $sonuc = $a->sinavSonuclari()->create([
            'deneme_no' => $a->sonrakiDeneme(),
            'puan' => $puan,
            'gecti' => $gecti,
            'cevaplar' => $cevapDetay,
            'tamamlandi_at' => now(),
        ]);

        $a->durumuTazele();
        $this->oturum()?->nabiz();

        $this->sonSonuc = $sonuc;
        $this->mod = 'sonuc';
        $this->sinavSorulari = [];
        unset($this->atamaKaydi);

        if (! $gecti && $a->kalanSinavHakki() < 1) {
            // Md.16/3: üç sınavda da başarısız → temel eğitime yeniden katılır.
            $a->yenidenBaslat();
            unset($this->atamaKaydi, $this->izlenenDersIdler);

            Notification::make()
                ->title("Sınav puanınız: %{$puan}. Sınav hakkınız doldu.")
                ->body('Yönetmelik gereği eğitime baştan başlamanız gerekiyor; dersler sıfırlandı.')
                ->danger()->persistent()->send();

            return;
        }

        Notification::make()
            ->title($gecti ? "Tebrikler! Sınavı %{$puan} ile geçtiniz." : "Sınav puanınız: %{$puan} (geçme: %{$a->gecmePuani()})")
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

    /** Soruları hazırlar; doğru cevaplar yalnız sunucu oturumunda tutulur. */
    private function soruHazirla(EgitimAtamasi $a): void
    {
        $sorular = $a->paket->sinavSorulari();

        session()->put($this->oturumAnahtari('cevap'), collect($sorular)->pluck('dogru_index', 'id')->all());

        $this->sinavSorulari = collect($sorular)->map(fn (array $s) => collect($s)->except('dogru_index')->all())->all();
        $this->cevaplar = [];
    }

    /** @return array{puan: int, detay: array<int, array<string, mixed>>} */
    private function puanla(): array
    {
        $dogrular = session()->get($this->oturumAnahtari('cevap'), []);
        $dogru = 0;
        $detay = [];

        foreach ($this->sinavSorulari as $s) {
            $verilen = (int) ($this->cevaplar[$s['id']] ?? -1);
            $dogruIndex = (int) ($dogrular[$s['id']] ?? -2);
            $dogru += $verilen === $dogruIndex ? 1 : 0;

            $detay[] = [
                'soru' => $s['soru'],
                'secenekler' => $s['secenekler'],
                'dogru_index' => $dogruIndex,
                'verilen_index' => $verilen,
            ];
        }

        return ['puan' => (int) round($dogru / max(1, count($this->sinavSorulari)) * 100), 'detay' => $detay];
    }

    private function oturumAnahtari(string $tur): string
    {
        return 'uzaktan_egitim_'.$tur.'_'.$this->atama;
    }

    private function oturum(): ?EgitimGirisi
    {
        $a = $this->atamaKaydi();
        $id = session()->get($this->oturumAnahtari('oturum'));

        $giris = $id ? EgitimGirisi::query()->where('egitim_atamasi_id', $a?->id)->find($id) : null;

        // Uzun aradan (sayfa açık bırakılıp) dönülmüşse yeni oturum açılır.
        if ($giris && ($giris->cikis_at ?? $giris->giris_at)->lt(now()->subMinutes(EgitimGirisi::TEKRAR_DK))) {
            $giris = null;
        }

        if (! $giris && $a) {
            $giris = EgitimGirisi::kaydet($a->calisan_id, $a->id);
            session()->put($this->oturumAnahtari('oturum'), $giris->id);
        }

        return $giris;
    }
}
