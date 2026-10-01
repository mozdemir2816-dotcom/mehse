<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsIzinFormu as IsIzinFormuModel;
use App\Models\IsIzinSablonu;
use App\Models\SahaDenetimi;
use App\Filament\Support\ImzaSecenegi;
use App\Support\IsIzinFormuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * İş İzin Formu (Permit to Work) — isgpratik 76-78.jpg. İzin türüne göre ilgili
 * güvenlik önlemi maddeleri dinamik gösterilir. Ayrıca: izin kütüphanesinden
 * (hazır katalog + uzmanın kendi şablonları) ön doldurma ve onay/kapanış
 * yaşam döngüsü (taslak -> onay bekliyor -> onaylandı -> iş tamamlandı -> kapatıldı).
 */
class IsIzinFormu extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.is-izin-formu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Diğer Belge & Yazışma';

    protected static ?int $navigationSort = 22;

    protected static ?string $slug = 'is-izin-formu';

    protected static ?string $title = 'İş İzin Formu (PTW)';

    protected static ?string $navigationLabel = 'İş İzin Formu';

    public ?int $firmaId = null;

    public ?string $calismaAlani = null;

    public ?string $isDetayi = null;

    public ?string $baslangic = null;

    public ?string $bitis = null;

    public ?int $gecerlilikSaat = null;

    public ?string $ozelKosullar = null;

    /** @var array<int, string> */
    public array $izinTurleri = [];

    /** @var array<int, string> */
    public array $secilenOnlemler = [];

    /** @var array<int, string> */
    public array $secilenKkdler = [];

    /** @var array<int, string> kütüphaneden gelen serbest uyarı metinleri */
    public array $uyarilar = [];

    public ?string $sablonKaynak = null;

    public ?string $onay1Baslik = null;

    public ?string $onay1Ad = null;

    public ?string $onay2Baslik = null;

    public ?string $onay2Ad = null;

    /** @var array<int, int> izinde çalışacak personel (firma çalışanı id'leri) */
    public array $secilenCalisanlar = [];

    public ?string $taseron = null;

    public ?int $sahaDenetimiId = null;

    /** @var array<string, string> PTW operasyon kontrolleri: anahtar => durum */
    public array $kontroller = [];

    /** @var array<string, string> geçmiş kayıtta düzenlenen kontroller */
    public array $islemKontroller = [];

    // Kütüphane seçimi
    public string $sablonSecim = '';

    // Geçmiş kayıt üzerinde inline işlem (reddet / kapat)
    public ?int $islemId = null;

    public ?string $islemTuru = null;

    public ?string $islemNotu = null;

    public bool $islemSahaTeslim = false;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }

        $this->kontroller = IsIzinFormuModel::varsayilanKontroller([]);
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

    /**
     * İzin kütüphanesi — hazır katalog + uzmanın kendi şablonları.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function sablonlar(): array
    {
        $hazir = collect(config('isg.is_izin.kutuphane', []))
            ->map(fn ($s, $i) => [
                'kaynak' => 'hazir', 'anahtar' => (string) $i,
                'ad' => $s['ad'], 'aciklama' => $s['aciklama'] ?? null,
                'turler' => $s['turler'] ?? [], 'ek_onlemler' => $s['ek_onlemler'] ?? [],
                'kkdler' => $s['kkdler'] ?? [], 'gecerlilik_saat' => $s['gecerlilik_saat'] ?? null,
                'uyarilar' => $s['uyarilar'] ?? [], 'ozel_kosullar' => null,
                'onay1_baslik' => $s['onay1_baslik'] ?? null, 'onay2_baslik' => $s['onay2_baslik'] ?? null,
            ]);

        $ozel = IsIzinSablonu::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('ad')
            ->get()
            ->map(fn (IsIzinSablonu $s) => [
                'kaynak' => 'ozel', 'anahtar' => (string) $s->id,
                'ad' => $s->ad, 'aciklama' => $s->aciklama,
                'turler' => $s->turler ?? [], 'ek_onlemler' => $s->ek_onlemler ?? [],
                'kkdler' => $s->kkdler ?? [], 'gecerlilik_saat' => $s->gecerlilik_saat,
                'uyarilar' => $s->uyarilar ?? [], 'ozel_kosullar' => $s->ozel_kosullar,
                'onay1_baslik' => $s->onay1_baslik, 'onay2_baslik' => $s->onay2_baslik,
            ]);

        return $hazir->concat($ozel)->values()->all();
    }

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function firmaCalisanlari(): Collection
    {
        return $this->firma?->calisanlar()->where('aktif', true)->orderBy('ad_soyad')->get(['id', 'ad_soyad', 'gorev']) ?? collect();
    }

    /** @return array<int, string> id => "dd.mm.yyyy — şantiye / iş tanımı" */
    #[Computed]
    public function sahaDenetimleri(): array
    {
        if (! $this->firma) {
            return [];
        }

        return SahaDenetimi::where('firma_id', $this->firma->id)
            ->latest('denetim_tarihi')->limit(50)->get()
            ->mapWithKeys(fn (SahaDenetimi $d) => [
                $d->id => trim(($d->denetim_tarihi?->format('d.m.Y') ?? '—').' — '.($d->santiye_adi ?: $d->is_tanimi ?: 'Saha denetimi'), ' —'),
            ])->all();
    }

    /** Özet kartları — seçili firma, yoksa tüm firmalar. @return array<string, int> */
    #[Computed]
    public function ozet(): array
    {
        $izinler = IsIzinFormuModel::query()
            ->whereIn('firma_id', $this->firma ? [$this->firma->id] : array_keys($this->firmalar()))
            ->get(['id', 'durum', 'baslangic', 'bitis']);

        return [
            'toplam' => $izinler->count(),
            'aktif' => $izinler->filter(fn ($f) => $f->aktifMi())->count(),
            'onay_bekleyen' => $izinler->where('durum', 'onay_bekliyor')->count(),
            'suresi_gecen' => $izinler->filter(fn ($f) => $f->suresiGectiMi())->count(),
        ];
    }

    /** @return Collection<int, IsIzinFormuModel> */
    #[Computed]
    public function gecmisFormlar(): Collection
    {
        return $this->firma?->isIzinFormlari()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->gecmisFormlar, $this->firmaCalisanlari, $this->sahaDenetimleri, $this->ozet);
        $this->secilenCalisanlar = [];
        $this->sahaDenetimiId = null;
    }

    public function updatedIzinTurleri(): void
    {
        unset($this->onlemler);
        $gecerliMaddeler = $this->onlemler();
        $this->secilenOnlemler = array_values(array_intersect($this->secilenOnlemler, $gecerliMaddeler));
        $this->kontrolVarsayilanlariniUygula();
    }

    /** Tür değişince kontrol varsayılanlarını yenile; elle "uygun / uygun değil" yapılanlar korunur. */
    private function kontrolVarsayilanlariniUygula(): void
    {
        $yeni = IsIzinFormuModel::varsayilanKontroller($this->izinTurleri);

        foreach ($this->kontroller as $anahtar => $durum) {
            if (in_array($durum, ['uygun', 'uygun_degil'], true) && isset($yeni[$anahtar])) {
                $yeni[$anahtar] = $durum;
            }
        }

        $this->kontroller = $yeni;
    }

    public function calisanToggle(int $id): void
    {
        $this->secilenCalisanlar = in_array($id, $this->secilenCalisanlar, true)
            ? array_values(array_diff($this->secilenCalisanlar, [$id]))
            : [...$this->secilenCalisanlar, $id];
    }

    /*
    |--------------------------------------------------------------------------
    | Kütüphane
    |--------------------------------------------------------------------------
    */

    public function updatedSablonSecim(string $deger): void
    {
        if ($deger === '') {
            return;
        }

        [$kaynak, $anahtar] = array_pad(explode(':', $deger, 2), 2, null);

        $sablon = collect($this->sablonlar())
            ->first(fn ($s) => $s['kaynak'] === $kaynak && $s['anahtar'] === $anahtar);

        if (! $sablon) {
            return;
        }

        $this->izinTurleri = $sablon['turler'];
        unset($this->onlemler);
        $this->kontrolVarsayilanlariniUygula();

        // Önlem seti = türlerin filtreli maddeleri + şablonun ek maddeleri (hepsi işaretli).
        $this->secilenOnlemler = array_values(array_unique([
            ...$this->onlemler(),
            ...$sablon['ek_onlemler'],
        ]));
        $this->secilenKkdler = $sablon['kkdler'];
        $this->uyarilar = $sablon['uyarilar'];
        $this->gecerlilikSaat = $sablon['gecerlilik_saat'];
        $this->ozelKosullar = $sablon['ozel_kosullar'] ?: $this->ozelKosullar;
        $this->onay1Baslik = $sablon['onay1_baslik'] ?: $this->onay1Baslik;
        $this->onay2Baslik = $sablon['onay2_baslik'] ?: $this->onay2Baslik;
        $this->sablonKaynak = $sablon['ad'];

        Notification::make()->title('"'.$sablon['ad'].'" uygulandı')->success()->send();
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

    public function uyariSil(int $index): void
    {
        unset($this->uyarilar[$index]);
        $this->uyarilar = array_values($this->uyarilar);
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
            'sablon_kaynak' => $this->sablonKaynak,
            'calisma_alani' => $this->calismaAlani,
            'is_detayi' => $this->isDetayi,
            'baslangic' => $this->baslangic ?: null,
            'bitis' => $this->bitis ?: null,
            'gecerlilik_saat' => $this->gecerlilikSaat,
            'izin_turleri' => $this->izinTurleri,
            'guvenlik_onlemleri' => $this->secilenOnlemler,
            'gerekli_kkdler' => $this->secilenKkdler,
            'uyarilar' => array_values(array_filter($this->uyarilar, 'filled')),
            'ozel_kosullar' => $this->ozelKosullar,
            'onay1_baslik' => $this->onay1Baslik,
            'onay1_ad' => $this->onay1Ad,
            'onay2_baslik' => $this->onay2Baslik,
            'onay2_ad' => $this->onay2Ad,
            'calisanlar' => $this->firmaCalisanlari()
                ->whereIn('id', $this->secilenCalisanlar)
                ->map(fn (Calisan $c) => ['id' => $c->id, 'ad_soyad' => $c->ad_soyad, 'gorev' => $c->gorev])
                ->values()->all(),
            'taseron' => $this->taseron,
            'saha_denetimi_id' => array_key_exists((int) $this->sahaDenetimiId, $this->sahaDenetimleri()) ? $this->sahaDenetimiId : null,
            'kontroller' => $this->gecerliKontroller($this->kontroller),
        ]);
        $form->save();

        unset($this->gecmisFormlar, $this->ozet);

        return $form;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kutuphayeEkle')
                ->label('Formu Kütüphaneye Şablon Olarak Ekle')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->visible(fn () => $this->izinTurleri !== [] || $this->secilenOnlemler !== [])
                ->schema([
                    TextInput::make('ad')
                        ->label('Şablon adı')
                        ->required()
                        ->default(fn () => $this->sablonKaynak),
                    Textarea::make('aciklama')
                        ->label('Açıklama')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    IsIzinSablonu::create([
                        'user_id' => Filament::auth()->id(),
                        'ad' => $data['ad'],
                        'aciklama' => $data['aciklama'] ?? null,
                        'turler' => $this->izinTurleri,
                        'ek_onlemler' => array_values(array_diff($this->secilenOnlemler, $this->onlemler())),
                        'kkdler' => $this->secilenKkdler,
                        'gecerlilik_saat' => $this->gecerlilikSaat,
                        'uyarilar' => array_values(array_filter($this->uyarilar, 'filled')),
                        'ozel_kosullar' => $this->ozelKosullar,
                        'onay1_baslik' => $this->onay1Baslik,
                        'onay2_baslik' => $this->onay2Baslik,
                    ]);

                    unset($this->sablonlar);
                    Notification::make()->title('Şablon kütüphaneye eklendi')->success()->send();
                }),

            Action::make('pdf')
                ->label('Formu Kaydet ve PDF Oluştur')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function () {
                    $form = $this->kaydet();

                    if ($form) {
                        Notification::make()->title('İş izni kaydedildi')->body($form->izin_no.' — durum: Taslak')->success()->send();
                    }

                    return $form ? IsIzinFormuUretici::pdf($form) : null;
                }),
        ];
    }

    public function sablonSil(int $id): void
    {
        IsIzinSablonu::where('user_id', Filament::auth()->id())->where('id', $id)->delete();
        unset($this->sablonlar);
    }

    /*
    |--------------------------------------------------------------------------
    | Onay / kapanış yaşam döngüsü (geçmiş kayıt üzerinde)
    |--------------------------------------------------------------------------
    */

    private function kayit(int $id): ?IsIzinFormuModel
    {
        return $this->firma?->isIzinFormlari()->find($id);
    }

    public function onayaGonder(int $id): void
    {
        $f = $this->kayit($id);

        if (! $f || $f->durum !== 'taslak') {
            return;
        }

        if ($uygunDegil = $f->kontrolAdlari('uygun_degil')) {
            Notification::make()->title('Onaya gönderilemez')
                ->body('Uygun olmayan kontrol var: '.implode(', ', $uygunDegil).'. Önce düzeltin.')
                ->danger()->send();

            return;
        }

        $f->update([
            'durum' => 'onay_bekliyor',
            'onay1_durum' => 'bekliyor',
            'onay2_durum' => 'bekliyor',
        ]);
        unset($this->gecmisFormlar);
        Notification::make()->title($f->izin_no.' onaya gönderildi')->success()->send();
    }

    public function onayla(int $id, int $hangi): void
    {
        $f = $this->kayit($id);

        if (! $f || $f->durum !== 'onay_bekliyor') {
            return;
        }

        $alan = $hangi === 1 ? 'onay1' : 'onay2';
        $digerOnayli = ($hangi === 1 ? $f->onay2_durum : $f->onay1_durum) === 'onayladi';

        // Son onay = çalışma yetkisi; bekleyen / uygun olmayan operasyon kontrolü varken verilmez.
        if ($digerOnayli && ($eksik = $f->kontrolAdlari('bekliyor', 'uygun_degil'))) {
            Notification::make()->title('Tam onay verilemez')
                ->body('Tamamlanmamış operasyon kontrolleri: '.implode(', ', $eksik).'. "Kontroller" butonundan güncelleyin.')
                ->danger()->send();

            return;
        }
        $f->{$alan.'_durum'} = 'onayladi';
        $f->{$alan.'_tarih'} = now();

        if ($f->onay1_durum === 'onayladi' && $f->onay2_durum === 'onayladi') {
            $f->durum = 'onaylandi';
        }

        $f->save();
        unset($this->gecmisFormlar);

        Notification::make()
            ->title($f->durum === 'onaylandi' ? $f->izin_no.' tam onaylandı — çalışma yetkisi verildi' : 'Onay kaydedildi')
            ->success()->send();
    }

    public function isiTamamla(int $id): void
    {
        $f = $this->kayit($id);

        if (! $f || $f->durum !== 'onaylandi') {
            return;
        }

        $f->update(['durum' => 'is_tamamlandi', 'is_bitis_tarihi' => now()]);
        unset($this->gecmisFormlar);
        Notification::make()->title($f->izin_no.' iş tamamlandı olarak işaretlendi')->success()->send();
    }

    public function iptalEt(int $id): void
    {
        $f = $this->kayit($id);

        if (! $f || in_array($f->durum, ['kapatildi', 'iptal'], true)) {
            return;
        }

        $f->update(['durum' => 'iptal']);
        unset($this->gecmisFormlar);
        Notification::make()->title($f->izin_no.' iptal edildi')->warning()->send();
    }

    /** Inline işlem başlat (reddet / kapat). */
    public function islemBaslat(int $id, string $turu): void
    {
        $this->islemId = $id;
        $this->islemTuru = $turu;
        $this->islemNotu = null;
        $this->islemSahaTeslim = false;

        if ($turu === 'kontroller' && ($f = $this->kayit($id))) {
            $this->islemKontroller = $f->kontroller ?? IsIzinFormuModel::varsayilanKontroller($f->izin_turleri ?? []);
        }
    }

    public function islemIptal(): void
    {
        $this->reset('islemId', 'islemTuru', 'islemNotu', 'islemSahaTeslim', 'islemKontroller');
    }

    /** @return array<string, string> yalnız tanımlı anahtar + geçerli durum */
    private function gecerliKontroller(array $kontroller): array
    {
        $durumlar = config('isg.is_izin.kontrol_durumlari');

        return collect(config('isg.is_izin.operasyon_kontrolleri'))
            ->mapWithKeys(fn ($k, $anahtar) => [$anahtar => isset($durumlar[$kontroller[$anahtar] ?? '']) ? $kontroller[$anahtar] : 'bekliyor'])
            ->all();
    }

    public function islemiUygula(): void
    {
        $f = $this->islemId ? $this->kayit($this->islemId) : null;

        if (! $f) {
            $this->islemIptal();

            return;
        }

        if ($this->islemTuru === 'reddet') {
            if (blank($this->islemNotu)) {
                Notification::make()->title('Ret gerekçesi zorunlu')->danger()->send();

                return;
            }

            $f->update([
                'durum' => 'reddedildi',
                'red_gerekcesi' => $this->islemNotu,
                'onay1_durum' => $f->onay1_durum === 'onayladi' ? 'onayladi' : 'reddetti',
                'onay2_durum' => $f->onay2_durum === 'onayladi' ? 'onayladi' : 'reddetti',
            ]);
            Notification::make()->title($f->izin_no.' reddedildi')->danger()->send();
        }

        if ($this->islemTuru === 'kontroller') {
            if (in_array($f->durum, ['kapatildi', 'iptal'], true)) {
                $this->islemIptal();

                return;
            }

            $f->update(['kontroller' => $this->gecerliKontroller($this->islemKontroller)]);
            Notification::make()->title($f->izin_no.' kontrolleri kaydedildi')->success()->send();
        }

        if ($this->islemTuru === 'kapat') {
            $f->update([
                'durum' => 'kapatildi',
                'is_bitis_tarihi' => $f->is_bitis_tarihi ?? now(),
                'saha_teslim_alindi' => $this->islemSahaTeslim,
                'kapanis_notu' => $this->islemNotu,
                'kapatan' => $this->firma?->igu?->ad_soyad ?? Filament::auth()->user()?->name,
            ]);
            Notification::make()->title($f->izin_no.' kapatıldı')->success()->send();
        }

        $this->islemIptal();
        unset($this->gecmisFormlar, $this->ozet);
    }

    public function gecmisPdf(int $id)
    {
        $form = $this->kayit($id);

        return $form ? IsIzinFormuUretici::pdf($form) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->kayit($id)?->delete();
        unset($this->gecmisFormlar);
    }
}
