<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimGirisi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Support\ExcelBellek;
use App\Support\UzaktanEgitimBelgesiUretici;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Uzaktan Eğitim Atama — İSG uzmanı bir eğitim paketini firmadaki çalışanlara atar;
 * portal giriş bilgisini (e-posta + geçici şifre) üretir. Atanan eğitimlerin
 * ilerlemesi ve tamamlananların katılım belgesi buradan izlenir.
 */
class UzaktanEgitimAtama extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.uzaktan-egitim-atama';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

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
            ->with(['calisan.firma', 'paket', 'girisler', 'ilerlemeler.ders'])
            ->latest()
            ->get();
    }

    /**
     * Md.13 süre kontrolü: seçili paketin video süresi, firmanın tehlike
     * sınıfı + eğitim türüne göre uzaktan verilmesi gereken asgari süreyi
     * karşılıyor mu. Tehlikeli / çok tehlikeli işyerinde 4. konu yüz yüze
     * verildiğinden onun süresi düşülür.
     *
     * @return array{gereken_dk: int, paket_dk: int, suresiz_ders: int, yeterli: bool, dorduncu_yuz_yuze: bool, saat: int}|null
     */
    #[Computed]
    public function sureKontrolu(): ?array
    {
        $paket = $this->paketId ? EgitimPaketi::query()->gorunur(Filament::auth()->id())->with('dersler')->find($this->paketId) : null;
        $sinif = $this->firma?->tehlike_sinifi;

        if (! $paket || ! $sinif) {
            return null;
        }

        $saat = (int) ($this->egitimTuru === 'ilk_defa'
            ? config("isg.egitim.sureler.ilk.{$sinif}.saat", 8)
            : config('isg.egitim.sureler.tekrar.saat', 8));
        $dorduncuYuzYuze = in_array($sinif, config('isg.uzaktan_egitim.yuz_yuze_dorduncu_konu', []), true);
        $dorduncu = $this->egitimTuru === 'ilk_defa' ? (int) config("isg.uzaktan_egitim.dorduncu_konu_saat.{$sinif}", 0) : intdiv($saat, 4);
        $gereken = ($saat - ($dorduncuYuzYuze ? $dorduncu : 0)) * (int) config('isg.uzaktan_egitim.ders_saati_dk', 45);
        $paketDk = $paket->toplamSureDk();

        return [
            'gereken_dk' => $gereken,
            'paket_dk' => $paketDk,
            'suresiz_ders' => $paket->dersler->whereNull('sure_sn')->count(),
            'yeterli' => $paketDk >= $gereken,
            'dorduncu_yuz_yuze' => $dorduncuYuzYuze,
            'saat' => $saat,
        ];
    }

    /** Firmadaki eğitimli çalışanların yalnız portala girdiği (eğitim açmadığı) tarihler. */
    #[Computed]
    public function portalGirisleri(): Collection
    {
        return EgitimGirisi::query()
            ->whereIn('calisan_id', $this->atamalar->pluck('calisan_id')->unique())
            ->whereNull('egitim_atamasi_id')
            ->orderBy('giris_at')
            ->get()
            ->groupBy('calisan_id');
    }

    /** Uzaktan eğitim takip listesi: kim, hangi tarihlerde girdi, bitirdi mi. */
    public function takipExcel()
    {
        if ($this->atamalar->isEmpty()) {
            return null;
        }

        ExcelBellek::artir();

        $basliklar = ['Ad Soyad', 'Görev', 'Eğitim', 'Eğitim Türü', 'Atanma', 'Oturumlar (giriş – çıkış)', 'Oturum Sayısı',
            'Fiili İzleme (dk)', 'Aktif Katılım Cevabı', 'İzlenen Ders', 'Ön Test', 'Sınav Puanı', 'Sınav Denemesi', 'Yeniden Başlatma',
            'Tamamlandı', 'Tamamlanma Tarihi', 'Eğitim Kaydı'];
        $son = Coordinate::stringFromColumnIndex(count($basliklar));

        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();
        $sayfa->setTitle('Uzaktan Eğitim Takibi');
        $sayfa->fromArray($basliklar, null, 'A1');
        $sayfa->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $sayfa->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        foreach ($this->atamalar->values() as $i => $a) {
            $tamam = $a->durum === 'tamamlandi';
            $sayfa->fromArray([
                $a->calisan->ad_soyad,
                $a->calisan->gorev,
                $a->paket->ad,
                $a->turEtiketi(),
                $a->atandi_at?->format('d.m.Y'),
                $a->girisler->map(fn (EgitimGirisi $g) => $g->giris_at->format('d.m.Y H:i').' – '.($g->cikis_at?->format('H:i') ?? '?'))->implode(', '),
                $a->girisler->count(),
                (int) round($a->ilerlemeler->sum('izlenen_sn') / 60),
                $a->girisler->sum('yoklama_sayisi'),
                $a->izlenenDersSayisi().'/'.$a->toplamDersSayisi(),
                $a->on_test_puani,
                $a->sonSinav()?->puan,
                $a->sinavSonuclari()->count(),
                $a->yeniden_baslatma ?: null,
                $tamam ? '✓' : '—',
                $a->tamamlandi_at?->format('d.m.Y'),
                ! $tamam ? '—' : ($a->egitimKaydinaIslenirMi() ? 'İşlendi' : ($a->dorduncuKonuYuzYuzeMi() ? '4. konu yüz yüze bekleniyor' : '—')),
            ], null, 'A'.($i + 2));
        }

        foreach (range(1, count($basliklar)) as $i) {
            $sayfa->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sayfa->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'uze').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'uzaktan-egitim-takibi-'.Str::slug($this->firma->unvan).'.xlsx');
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['paketId', 'egitimTuru'], true)) {
            unset($this->sureKontrolu);
        }
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->atamalar, $this->portalGirisleri, $this->sureKontrolu);
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

        if (! array_key_exists($this->egitimTuru, config('isg.uzaktan_egitim.egitim_turleri'))) {
            Notification::make()->title('Geçersiz eğitim türü')->body('İşe başlama eğitimi uygulamalı ve yüz yüze verilir (Yönetmelik Md.7).')->danger()->send();

            return;
        }

        if (! $paket->sinavaHazirMi()) {
            Notification::make()->title('Paket eksik')->body('Pakette en az bir ders ve 5 sınav sorusu olmalı.')->danger()->send();

            return;
        }

        $giris = [];
        $atananSayi = 0;
        $koduUretilenler = [];

        foreach ($this->calisanlar->whereIn('id', $this->secilenCalisanlar) as $calisan) {
            // E-postası olmayan çalışana portala girebilmesi için geçici bir kullanıcı kodu ata.
            if (blank($calisan->eposta)) {
                $calisan->forceFill(['eposta' => $this->geciciKullaniciKodu($calisan)])->save();
                $koduUretilenler[] = $calisan->ad_soyad;
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
        if ($koduUretilenler) {
            $mesaj .= ' · e-postası olmayanlara geçici kullanıcı kodu üretildi: '.implode(', ', $koduUretilenler);
        }

        Notification::make()->title($mesaj)
            ->body('Portal adresi: '.url('/egitim').' — giriş bilgileri aşağıda listelendi.')
            ->success()->send();
    }

    /**
     * E-postası olmayan çalışan için, portala e-posta yerine girebileceği
     * benzersiz, okunabilir bir kullanıcı kodu üretir (ör. "ahmet.yilmaz482").
     * Aynı `eposta` sütununa yazılır — PortalLogin bu alanda artık e-posta
     * biçimi zorunlu tutmuyor.
     */
    private function geciciKullaniciKodu(Calisan $calisan): string
    {
        $taban = (string) Str::of($calisan->ad_soyad)
            ->ascii()
            ->lower()
            ->replace(' ', '.')
            ->replaceMatches('/[^a-z0-9.]/', '');

        do {
            $kod = $taban.random_int(100, 999);
        } while (Calisan::where('eposta', $kod)->exists());

        return $kod;
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
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firma?->id))
            ->find($id)?->delete();

        unset($this->atamalar);
    }

    public function belgeIndir(int $atamaId)
    {
        $atama = EgitimAtamasi::query()
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firma?->id))
            ->with(['calisan.firma', 'paket'])
            ->find($atamaId);

        if (! $atama || ! $atama->basariliMi()) {
            Notification::make()->title('Bu eğitim henüz başarıyla tamamlanmadı')->warning()->send();

            return null;
        }

        return UzaktanEgitimBelgesiUretici::pdf($atama);
    }
}
