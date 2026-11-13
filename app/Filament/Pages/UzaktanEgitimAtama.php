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
use Livewire\Attributes\Url;
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

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'uzaktan-egitim-atama';

    protected static ?string $title = 'Uzaktan Eğitim Atama';

    protected static ?string $navigationLabel = 'Uzaktan Eğitim Atama';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public ?int $paketId = null;

    /** Takip detayı açık olan atama (Yönetmelik Md.12/4 kayıtları). */
    public ?int $detayId = null;

    public string $egitimTuru = 'yenileme';

    public ?string $sonTarih = null;

    /** @var array<int, int> */
    public array $secilenCalisanlar = [];

    /** @var array<int, array{ad_soyad: string, eposta: string, sifre: ?string}> */
    public array $sonUretilenGiris = [];

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->secenekler()->all();
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
     * @return array{gereken_dk: int, paket_dk: int, suresiz_ders: int, yeterli: bool, dorduncu_yuz_yuze: bool, yuz_yuze_saat: int, saat: int}|null
     */
    #[Computed]
    public function sureKontrolu(): ?array
    {
        $paket = $this->paketId ? EgitimPaketi::query()->gorunur(Filament::auth()->id())->with('dersler')->find($this->paketId) : null;
        $sinif = $this->firma?->tehlike_sinifi;

        if (! $paket || ! $sinif) {
            return null;
        }

        ['saat' => $saat, 'gereken_dk' => $gereken, 'dorduncu_yuz_yuze' => $dorduncuYuzYuze, 'yuz_yuze_saat' => $yuzYuzeSaat]
            = EgitimPaketi::gerekenUzaktanSure($sinif, $this->egitimTuru);
        $paketDk = $paket->toplamSureDk();

        return [
            'gereken_dk' => $gereken,
            'paket_dk' => $paketDk,
            'suresiz_ders' => $paket->dersler->whereNull('sure_sn')->count(),
            'yeterli' => $paketDk >= $gereken,
            'dorduncu_yuz_yuze' => $dorduncuYuzYuze,
            'yuz_yuze_saat' => $yuzYuzeSaat,
            'saat' => $saat,
        ];
    }

    /** Tüm firmalar özeti: durum süzgeci (hepsi | devam | basarisiz | onay_bekliyor | tamamlandi | gecikti). */
    public string $genelFiltre = 'hepsi';

    /**
     * Firma seçilmeden önce: uzmanın tüm firmalarındaki atamalar tek listede.
     *
     * @return Collection<int, EgitimAtamasi>
     */
    #[Computed]
    public function tumAtamalar(): Collection
    {
        return EgitimAtamasi::query()
            ->whereHas('calisan.firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->with(['calisan.firma', 'paket.dersler', 'ilerlemeler', 'sinavSonuclari', 'girisler'])
            ->get()
            ->sortBy([fn ($a, $b) => strcmp((string) $a->calisan->firma->unvan, (string) $b->calisan->firma->unvan), fn ($a, $b) => strcmp((string) $a->calisan->ad_soyad, (string) $b->calisan->ad_soyad)])
            ->values();
    }

    /** Süzgece uyuyor mu (genel liste + sayaçlar). */
    public static function genelFiltreUyar(EgitimAtamasi $a, string $filtre): bool
    {
        return match ($filtre) {
            'hepsi' => true,
            'devam' => in_array($a->durum, ['atandi', 'devam'], true),
            'sertifika' => $a->belgeAcikMi(),
            'gecikti' => $a->son_tarih && $a->son_tarih->isPast() && ! in_array($a->durum, ['tamamlandi', 'onay_bekliyor', 'ih_onay_bekliyor'], true),
            default => $a->durum === $filtre,
        };
    }

    /** @return Collection<int, EgitimAtamasi> */
    #[Computed]
    public function genelListe(): Collection
    {
        return $this->tumAtamalar->filter(fn (EgitimAtamasi $a) => static::genelFiltreUyar($a, $this->genelFiltre))->values();
    }

    /** Genel listeden: firmayı seçip o çalışanın takip detayını açar. */
    public function detayaGit(int $atamaId): void
    {
        $a = $this->tumAtamalar->firstWhere('id', $atamaId);

        if (! $a) {
            return;
        }

        $this->firmaId = $a->calisan->firma_id;
        $this->updatedFirmaId();
        $this->detayId = $a->id;
    }

    /** Açık takip detayı: çalışan, oturumlar, ders ilerlemeleri, sınav denemeleri. */
    #[Computed]
    public function detay(): ?EgitimAtamasi
    {
        if (! $this->detayId || ! $this->firma) {
            return null;
        }

        return EgitimAtamasi::query()
            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firma->id))
            ->with(['calisan.firma', 'paket', 'girisler', 'ilerlemeler', 'sinavSonuclari'])
            ->find($this->detayId);
    }

    public function detayAc(int $id): void
    {
        $this->detayId = $this->detayId === $id ? null : $id;
        unset($this->detay);
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

        $basliklar = ['Ad Soyad', 'T.C. Kimlik No', 'Görev', 'Eğitim', 'Eğitim Türü', 'Atanma', 'Oturumlar (giriş – çıkış)', 'IP / Cihaz', 'KVKK-Taahhüt Onayı', 'Oturum Sayısı',
            'Fiili İzleme (dk)', 'Aktif Katılım Cevabı', 'İzlenen Ders', 'Konu Başlığı İlerlemesi', 'Ön Test', 'Sınav Puanı', 'Sınav Denemesi', 'Yeniden Başlatma',
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
                // Denetim kanıtı dosyası: TC tam yazılır (listelerdeki maske yalnız ekran içindir).
                $a->calisan->tc,
                $a->calisan->gorev,
                $a->paket->ad,
                $a->turEtiketi(),
                $a->atandi_at?->format('d.m.Y'),
                $a->girisler->map(fn (EgitimGirisi $g) => $g->giris_at->format('d.m.Y H:i').' – '.($g->cikis_at?->format('H:i') ?? '?'))->implode(', '),
                $a->girisler->map(fn (EgitimGirisi $g) => trim($g->ip.' '.$g->cihaz))->filter()->unique()->implode(' | '),
                $a->calisan->kvkk_onay_at?->format('d.m.Y H:i') ?? '—',
                $a->girisler->count(),
                (int) round($a->ilerlemeler->sum('izlenen_sn') / 60),
                $a->girisler->sum('yoklama_sayisi'),
                $a->izlenenDersSayisi().'/'.$a->toplamDersSayisi(),
                $a->modulOzeti(),
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

    /**
     * Toplu giriş bilgileri (08.11.2026): firmadaki tamamlanmamış atamaların çalışanları için
     * kullanıcı kodu + geçici şifre listesi (Excel ya da yazdırılabilir PDF kartlar). Şifreler
     * şifreli saklandığından, kendi şifresini belirlememiş çalışanlara YENİ geçici şifre üretilir;
     * kendi şifresini belirleyenlerin şifresine dokunulmaz.
     */
    public function girisBilgileriIndir(string $bicim = 'excel')
    {
        $satirlar = [];

        foreach ($this->atamalar->where('durum', '!=', 'tamamlandi')->pluck('calisan')->unique('id') as $c) {
            $sifre = null;

            if ($c->sifre_belirlendi_at === null) {
                $sifre = Str::upper(Str::random(3)).random_int(100, 999);
                $c->forceFill(['sifre' => $sifre])->save();
            }

            $satirlar[] = ['ad_soyad' => $c->ad_soyad, 'gorev' => $c->gorev, 'kod' => $c->eposta, 'sifre' => $sifre ?? '(kendi belirlediği şifre)', 'telefon' => $c->telefon];
        }

        if (! $satirlar) {
            Notification::make()->title('Giriş bilgisi verilecek çalışan yok')->body('Bu firmadaki tüm atamalar tamamlanmış.')->warning()->send();

            return null;
        }

        $ad = 'uzaktan-egitim-giris-bilgileri-'.Str::slug($this->firma->unvan);

        if ($bicim === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.uzaktan-egitim-giris-kartlari', [
                'firma' => $this->firma, 'satirlar' => $satirlar, 'portal' => url('/egitim'),
            ])->setPaper('a4');

            return response()->streamDownload(fn () => print ($pdf->output()), $ad.'.pdf');
        }

        ExcelBellek::artir();
        $kitap = new Spreadsheet;
        $sayfa = $kitap->getActiveSheet();
        $sayfa->setTitle('Giriş Bilgileri');
        $sayfa->fromArray(['Ad Soyad', 'Görev', 'Kullanıcı Kodu / E-posta', 'Geçici Şifre', 'Telefon', 'Portal'], null, 'A1');
        $sayfa->getStyle('A1:F1')->getFont()->setBold(true);
        foreach ($satirlar as $i => $r) {
            $sayfa->fromArray([$r['ad_soyad'], $r['gorev'], $r['kod'], $r['sifre'], $r['telefon'], url('/egitim')], null, 'A'.($i + 2));
        }
        foreach (range('A', 'F') as $k) {
            $sayfa->getColumnDimension($k)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ueg').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, $ad.'.xlsx');
    }

    /** Eğitimi henüz bitirmeyenler (WhatsApp hatırlatma listesi). */
    #[Computed]
    public function hatirlatilacaklar(): Collection
    {
        return $this->atamalar->filter(fn (EgitimAtamasi $a) => in_array($a->durum, ['atandi', 'devam', 'basarisiz'], true))->values();
    }

    /** Hatırlatma mesajı için wa.me bağlantısı (şifre içermez). */
    public function hatirlatmaLinki(EgitimAtamasi $a): string
    {
        $kalan = $a->toplamDersSayisi() - $a->izlenenDersSayisi();
        $metin = "Sayın {$a->calisan->ad_soyad}, {$this->firma?->unvan} bünyesindeki yasal İSG uzaktan eğitiminiz henüz tamamlanmadı"
            .($kalan > 0 ? " ({$kalan} ders kaldı)" : ' (final sınavı bekleniyor)').'.'
            .($a->son_tarih ? ' Son tarih: '.$a->son_tarih->format('d.m.Y').'.' : '')
            ."\nEğitim Portalı: ".url('/egitim')."\nKullanıcı Kodu: {$a->calisan->eposta}\nŞifrenizi unuttuysanız iş güvenliği uzmanınıza bildirin.";

        return 'https://wa.me/'.$this->telefonNo($a->calisan->telefon).'?text='.rawurlencode($metin);
    }

    private function telefonNo(?string $tel): string
    {
        $no = preg_replace('/\D/', '', (string) $tel);

        return match (true) {
            strlen($no) === 11 && str_starts_with($no, '0') => '9'.$no,
            strlen($no) === 10 => '90'.$no,
            default => $no,
        };
    }

    /**
     * Müfettiş denetimi için firma bazlı toplu İSG eğitim katılım ve kayıt (log) tutanağı (PDF):
     * her çalışanın oturumları, IP / cihaz, konu bazlı fiili izleme, ön test / final, onaylar.
     */
    public function denetimTutanagi()
    {
        if (! $this->firma || $this->atamalar->isEmpty()) {
            return null;
        }

        $atamalar = $this->atamalar->load(['ilerlemeler.ders', 'sinavSonuclari', 'girisler', 'calisan']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.uzaktan-egitim-denetim-tutanagi', [
            'firma' => $this->firma->loadMissing(['igu', 'isyeriHekimi']),
            'atamalar' => $atamalar,
            'uzman' => Filament::auth()->user(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(fn () => print ($pdf->output()), 'uzaktan-egitim-denetim-tutanagi-'.Str::slug($this->firma->unvan).'.pdf');
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['paketId', 'egitimTuru'], true)) {
            unset($this->sureKontrolu);
        }
    }

    /** Eğitim Takibi'nden toplu atama: firma ve çalışanlar hazır seçili gelir. */
    public function mount(): void
    {
        if ($aktarim = session()->pull('uzaktan_atama_aktarim')) {
            $this->firmaId = (int) $aktarim['firma_id'];
            $this->updatedFirmaId();
            $this->secilenCalisanlar = array_values(array_intersect($aktarim['calisan_idleri'], $this->calisanlar->pluck('id')->all()));
        }
    }

    public function updatedFirmaId(): void    {
        unset($this->firma, $this->calisanlar, $this->atamalar, $this->portalGirisleri, $this->sureKontrolu, $this->detay, $this->hatirlatilacaklar);
        $this->detayId = null;
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

        $sonuc = \App\Support\UzaktanEgitimAtayici::ata($paket, $this->calisanlar->whereIn('id', $this->secilenCalisanlar), $this->egitimTuru, $this->sonTarih, Filament::auth()->id());
        $giris = $sonuc['giris'];
        $atananSayi = $sonuc['atanan'];
        $silinenEski = $sonuc['silinen_eski'];
        $koduUretilenler = $sonuc['kod_uretilen'];

        $this->sonUretilenGiris = $giris;
        $this->secilenCalisanlar = [];
        unset($this->atamalar);

        $mesaj = $atananSayi.' çalışana eğitim atandı';
        if ($silinenEski) {
            $mesaj .= ' · önceki paketlerden '.$silinenEski.' atama silindi';
        }
        if ($koduUretilenler) {
            $mesaj .= ' · e-postası olmayanlara geçici kullanıcı kodu üretildi: '.implode(', ', $koduUretilenler);
        }

        $bekleyen = $paket->videoBekleyenSayisi();

        Notification::make()->title($mesaj)
            ->body('Portal adresi: '.url('/egitim').' — giriş bilgileri aşağıda listelendi.'
                .($bekleyen ? " Not: {$bekleyen} konu başlığı için henüz video bağlanmadı; çalışan şimdilik yalnızca videosu hazır konuları görür." : ''))
            ->success()->send();
    }

    /** Giriş bilgisi satırı için wa.me bağlantısı (telefon yoksa numarasız — kişi WhatsApp'ta seçilir). */
    public function whatsappLinki(array $g): string
    {
        $firma = $this->firma?->unvan ?? 'işyeriniz';
        $sifre = $g['sifre'] ?? '(mevcut şifreniz)';

        $metin = "Sayın {$g['ad_soyad']}, {$firma} bünyesindeki Yasal İş Sağlığı ve Güvenliği Uzaktan Eğitiminiz tanımlanmıştır.
"
            .'Eğitim Portalı: '.url('/egitim')."
"
            ."Kullanıcı Kodu: {$g['eposta']}
"
            ."Geçici Şifre: {$sifre}
"
            .'Eğitimi tamamlayıp sınavdan en az '.(int) config('isg.uzaktan_egitim.gecme_puani_alt_sinir', 60).' puan almanız gerekmektedir. İyi dersler dileriz.';

        $no = preg_replace('/\D/', '', (string) ($g['telefon'] ?? ''));
        $no = match (true) {
            strlen($no) === 11 && str_starts_with($no, '0') => '9'.$no,
            strlen($no) === 10 => '90'.$no,
            default => $no,
        };

        return 'https://wa.me/'.$no.'?text='.rawurlencode($metin);
    }

    /**
     * E-postası olmayan çalışan için, portala e-posta yerine girebileceği
     * benzersiz, okunabilir bir kullanıcı kodu üretir (ör. "ahmet.yilmaz482").
     * Aynı `eposta` sütununa yazılır — PortalLogin bu alanda artık e-posta
     * biçimi zorunlu tutmuyor.
     */
    private function geciciKullaniciKodu(Calisan $calisan): string
    {
        return \App\Support\UzaktanEgitimAtayici::kullaniciKodu($calisan);
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

    /** İGU: 4. modül yüz yüze tamamlandı — belgeyi aç. */
    public function iguOnayla(int $atamaId): void
    {
        $atama = $this->kendiAtamalarim()->find($atamaId);

        if (! $atama || ! $atama->iguOnayiBekliyorMu()) {
            return;
        }

        $atama->iguOnayla(Filament::auth()->id());
        unset($this->atamalar, $this->tumAtamalar, $this->genelListe, $this->detay);

        Notification::make()->title('İGU onayı verildi')
            ->body($atama->calisan?->ad_soyad.($atama->belgeAcikMi() ? ' — belge açıldı, eğitim kaydına işlendi.' : ' — şimdi işyeri hekimi onayı bekleniyor.'))
            ->success()->send();
    }

    /** İşyeri hekimi onayı (sağlık konuları) — İGU, firmanın kayıtlı hekimi adına işaretler. */
    public function ihOnayla(int $atamaId): void
    {
        $atama = $this->kendiAtamalarim()->with('calisan.firma.isyeriHekimi')->find($atamaId);

        if (! $atama || ! $atama->ihOnayiBekliyorMu()) {
            return;
        }

        if (! $atama->calisan?->firma?->isyeriHekimi) {
            Notification::make()->title('Firmada kayıtlı işyeri hekimi yok')
                ->body('Firma kartında işyeri hekimini seçin; hekimin adı ve diploma / sertifika no belgeye yazılır.')
                ->warning()->send();

            return;
        }

        $atama->ihOnayla(Filament::auth()->id());
        unset($this->atamalar, $this->tumAtamalar, $this->genelListe, $this->detay);

        Notification::make()->title('İşyeri hekimi onayı verildi')->body($atama->calisan?->ad_soyad.' — eğitim tamamlandı, sertifika açıldı.')->success()->send();
    }

    /** Uzmanın kendi firmalarındaki atamalar (Takip'te firma seçilmeden de onay / sertifika verilebilir). */
    protected function kendiAtamalarim()
    {
        return EgitimAtamasi::query()
            ->whereHas('calisan.firma', fn ($q) => $q->where('user_id', Filament::auth()->id())
                ->when($this->firma, fn ($f) => $f->whereKey($this->firma->id)));
    }

    public function belgeIndir(int $atamaId)
    {
        $atama = $this->kendiAtamalarim()->with(['calisan.firma', 'paket'])->find($atamaId);

        if (! $atama || ! $atama->belgeAcikMi()) {
            Notification::make()->title('Bu eğitim henüz tamamlanmadı ya da İGU onayı bekliyor')->warning()->send();

            return null;
        }

        return UzaktanEgitimBelgesiUretici::pdf($atama);
    }
}
