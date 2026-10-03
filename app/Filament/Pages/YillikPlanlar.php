<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\YillikPlan as YillikPlanModel;
use App\Support\YillikDegerlendirmeVerisi;
use App\Support\YillikPlanExcelIceAktarici;
use App\Filament\Support\ImzaSecenegi;
use App\Support\YillikPlanExcelUretici;
use App\Support\IseOzguEgitimKutuphanesi;
use App\Support\YillikPlanSablonu;
use App\Support\YillikPlanUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Throwable;
use UnitEnum;

/**
 * Yıllık Planlar ortak tabanı — isgpratik 86-90.jpg. Kullanıcı isteğiyle
 * (01.10.2026) üç AYRI menü sayfası: Pages/YillikPlan/YillikCalismaPlani,
 * YillikEgitimPlani, YillikDegerlendirmeRaporu. Üçü aynı YillikPlan kaydını
 * (firma + yıl) düzenler; her sayfa yalnız kendi bölümünü gösterir ($planTuru).
 * Bu sınıf abstract — Filament keşfi atlar, rota üretmez.
 */
abstract class YillikPlanlar extends Page
{
    protected string $view = 'filament.pages.yillik-planlar';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    /** 'calisma' | 'egitim' | 'degerlendirme' — alt sınıf belirler. */
    protected static string $planTuru = 'calisma';

    /**
     * Üç sayfa da eski tek sayfanın yetki anahtarını ('yillik-planlar') kullanır;
     * verilmiş yetkiler bölünmeden geçerli kalır.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->aktif && $user->sayfaErisimiVarMi('yillik-planlar');
    }

    public const AYLAR = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

    public const DURUM_SIRASI = ['bos', 'planlandi', 'tamamlandi'];

    public ?int $firmaId = null;

    public int $yil;

    #[Locked]
    public string $sekme = 'calisma';

    public ?string $yeniFaaliyet = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniAciklama = null;

    public ?string $yeniAnaKonu = null;

    public ?string $yeniPeriyot = null;

    public ?string $yeniEgitimKonu = null;

    public ?string $yeniEgitimSure = null;

    /** Eğitim planı bölümü (config isg.yillik_plan.egitim_kategorileri) — Excel çıktısında satırın gideceği bölüm. */
    public string $yeniEgitimKategori = 'ise_ozgu';

    public ?string $yeniEgitimEgitici = null;

    public ?string $yeniEgitimHedefKitle = null;

    public ?string $yeniDegerlendirmeCalisma = null;

    public function mount(): void
    {
        $this->sekme = static::$planTuru;
        $this->yil = (int) now()->format('Y');

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }

        // Diğer yıllık plan sayfasından geçişte aynı yıl açılsın.
        if ($yil = request()->integer('yil')) {
            $this->yil = $yil;
        }
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
    public function plan(): ?YillikPlanModel
    {
        return $this->firma ? YillikPlanModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->plan);
    }

    public function updatedYil(): void
    {
        unset($this->plan);
    }

    /** Atanmış uzman (sözleşme başlangıcı) öncesi ay indeksi — bu aylar kilitli. */
    #[Computed]
    public function kilitAyIndeksi(): int
    {
        return $this->firma?->planKilitAyIndeksi($this->yil) ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Ay durum matrisi (Çalışma Planı + Eğitim Planı ortak) — $alan: 'faaliyetler'|'egitimler'
    |--------------------------------------------------------------------------
    */

    public function ayDurumDegistir(string $alan, int $index, int $ayIndex): void
    {
        $p = $this->plan();
        $satirlar = $p?->{$alan} ?? [];

        if (! $p || ! isset($satirlar[$index])) {
            return;
        }

        // Atanmış uzman öncesindeki aylar seçilemez.
        if ($ayIndex < $this->kilitAyIndeksi()) {
            Notification::make()
                ->title('Bu ay seçilemez')
                ->body('Firma sözleşme başlangıcından (atanmış uzman tarihi) önceki aylar için plan işaretlenemez.')
                ->warning()
                ->send();

            return;
        }

        $mevcut = $satirlar[$index]['aylar'][$ayIndex] ?? 'bos';
        $siraIndex = array_search($mevcut, self::DURUM_SIRASI, true);
        $yeni = self::DURUM_SIRASI[($siraIndex + 1) % count(self::DURUM_SIRASI)];

        $satirlar[$index]['aylar'][$ayIndex] = $yeni;
        $p->update([$alan => $satirlar]);
    }

    /** Çalışma planı tablosundaki P (Planlandı) / G (Gerçekleşti) hücresine tıklama. */
    public function ayHucresiDegistir(string $alan, int $index, int $ayIndex, string $hucre): void
    {
        $p = $this->plan();
        $satirlar = $p?->{$alan} ?? [];

        if (! $p || ! isset($satirlar[$index]) || ! in_array($hucre, ['P', 'G'], true)) {
            return;
        }

        if ($ayIndex < $this->kilitAyIndeksi()) {
            Notification::make()
                ->title('Bu ay seçilemez')
                ->body('Firma sözleşme başlangıcından (atanmış uzman tarihi) önceki aylar için plan işaretlenemez.')
                ->warning()
                ->send();

            return;
        }

        $satirlar[$index]['aylar'][$ayIndex] = YillikPlanModel::hucreDurumu($satirlar[$index]['aylar'][$ayIndex] ?? 'bos', $hucre);
        $p->update([$alan => $satirlar]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Çalışma Planı
    |--------------------------------------------------------------------------
    */

    public function faaliyetEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniFaaliyet)) {
            return;
        }

        $faaliyetler = $p->faaliyetler ?? [];
        $faaliyetler[] = [
            'ana_konu' => filled($this->yeniAnaKonu) ? $this->yeniAnaKonu : 'DİĞER',
            'faaliyet' => $this->yeniFaaliyet,
            'frekans' => $this->yeniPeriyot,
            'sorumlu' => $this->yeniSorumlu,
            'yasal_gereklilik' => $this->yeniAciklama,
            'aylar' => array_fill(0, 12, 'bos'),
        ];
        $p->update(['faaliyetler' => $faaliyetler]);

        $this->reset('yeniFaaliyet', 'yeniSorumlu', 'yeniAciklama', 'yeniAnaKonu', 'yeniPeriyot');
    }

    public function faaliyetSil(int $index): void
    {
        $p = $this->plan();
        $faaliyetler = $p?->faaliyetler ?? [];

        if (! $p || ! isset($faaliyetler[$index])) {
            return;
        }

        unset($faaliyetler[$index]);
        $p->update(['faaliyetler' => array_values($faaliyetler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Eğitim Planı
    |--------------------------------------------------------------------------
    */

    public function egitimEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniEgitimKonu)) {
            return;
        }

        $egitimler = $p->egitimler ?? [];
        $egitimler[] = [
            'konu' => $this->yeniEgitimKonu,
            'kategori' => array_key_exists($this->yeniEgitimKategori, config('isg.yillik_plan.egitim_kategorileri'))
                ? $this->yeniEgitimKategori : 'genel',
            'hafta' => 2,
            'sure_saat' => $this->yeniEgitimSure ?: null,
            'egitici' => $this->yeniEgitimEgitici,
            'hedef' => null,
            'hedef_kitle' => $this->yeniEgitimHedefKitle,
            'aylar' => array_fill(0, 12, 'bos'),
        ];
        $p->update(['egitimler' => $egitimler]);

        $this->reset('yeniEgitimKonu', 'yeniEgitimSure', 'yeniEgitimEgitici', 'yeniEgitimHedefKitle');
    }

    public function egitimSil(int $index): void
    {
        $p = $this->plan();
        $egitimler = $p?->egitimler ?? [];

        if (! $p || ! isset($egitimler[$index])) {
            return;
        }

        unset($egitimler[$index]);
        $p->update(['egitimler' => array_values($egitimler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Değerlendirme Raporu (ay matrisi yok, satır bazlı serbest metin)
    |--------------------------------------------------------------------------
    */

    public function degerlendirmeGuncelle(int $index, string $alan, string $deger): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index]) || ! in_array($alan, ['tarih', 'yapan_kisi', 'tekrar_sayisi', 'yontem', 'sonuc'], true)) {
            return;
        }

        $degerlendirmeler[$index][$alan] = $deger;
        $p->update(['degerlendirmeler' => $degerlendirmeler]);
    }

    public function degerlendirmeEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniDegerlendirmeCalisma)) {
            return;
        }

        $degerlendirmeler = $p->degerlendirmeler ?? [];
        $degerlendirmeler[] = [
            'calisma' => $this->yeniDegerlendirmeCalisma,
            'yapan_kisi' => null, 'yontem' => null, 'sonuc' => null,
            'tarih' => null, 'tekrar_sayisi' => null,
        ];
        $p->update(['degerlendirmeler' => $degerlendirmeler]);

        $this->reset('yeniDegerlendirmeCalisma');
    }

    public function degerlendirmeSil(int $index): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index])) {
            return;
        }

        unset($degerlendirmeler[$index]);
        $p->update(['degerlendirmeler' => array_values($degerlendirmeler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Ortak
    |--------------------------------------------------------------------------
    */

    public function varsayilanaSifirla(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        // Standart şablon; 4. eğitim bölümü yalnız inşaat firmalarında.
        $icerik = YillikPlanSablonu::icerik($this->firma, $this->yil);

        match ($this->sekme) {
            'egitim' => $p->update(['egitimler' => $icerik['egitimler']]),
            'degerlendirme' => $p->update([
                'degerlendirmeler' => collect(config('isg.yillik_plan.varsayilan_degerlendirmeler'))
                    ->map(fn ($d) => [...$d, 'tarih' => null, 'tekrar_sayisi' => null])
                    ->all(),
            ]),
            default => $p->update(['faaliyetler' => $icerik['faaliyetler']]),
        };

        Notification::make()->title('Plan varsayılan içeriğe sıfırlandı (otomatik dolduruldu)')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | İşe özgü konular (4. bölüm) — kütüphaneden seçim / kütüphaneye kayıt
    |--------------------------------------------------------------------------
    */

    /** Plandaki 4. bölüm satırlarının kütüphane anahtarları (eski satırlar ada göre eşlenir). */
    private function plandakiIseOzguAnahtarlari(): array
    {
        $tumu = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id());
        $adaGore = $tumu->mapWithKeys(fn ($k, $a) => [mb_strtolower(trim($k['ad'])) => $a]);

        return collect($this->plan()?->egitimler ?? [])->where('kategori', 'ise_ozgu')
            ->map(fn ($e) => ($e['kutuphane_anahtari'] ?? null) && $tumu->has($e['kutuphane_anahtari'])
                ? $e['kutuphane_anahtari']
                : ($adaGore[mb_strtolower(trim((string) ($e['konu'] ?? '')))] ?? null))
            ->filter()->unique()->values()->all();
    }

    public function iseOzguSecAction(): Action
    {
        return Action::make('iseOzguSec')
            ->label('İşe Özgü Konuları Seç')
            ->icon('heroicon-o-queue-list')
            ->modalHeading('İşe ve İşyerine Özgü Riskler — konu seçimi')
            ->modalDescription(fn () => $this->firma
                ? 'Firmanın '.(filled($this->firma->is_kalemleri) ? 'iş kalemlerine' : 'NACE koduna ('.($this->firma->nace_kodu ?: 'girilmemiş').')').' göre önerilenler ★ ile işaretli. Seçilen konular planın 4. bölümüne ve oradan Eğitim Katılım formunun işyerine özgü bölümüne girer.'
                : null)
            ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge)
            ->modalSubmitActionLabel('Plana uygula')
            ->fillForm(function (): array {
                $mevcut = $this->plandakiIseOzguAnahtarlari();

                return ['secilenler' => $mevcut ?: IseOzguEgitimKutuphanesi::firmaIcin($this->firma)->keys()->all()];
            })
            ->schema(function (): array {
                $onerilen = $this->firma ? IseOzguEgitimKutuphanesi::firmaIcin($this->firma)->keys()->all() : [];
                $tumu = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id())
                    ->sortBy(fn ($k, $a) => [in_array($a, $onerilen, true) ? 0 : 1, $k['kaynak'], $k['ad']]);

                return [
                    \Filament\Forms\Components\CheckboxList::make('secilenler')
                        ->label('Konular')
                        ->options($tumu->map(fn ($k, $a) => (in_array($a, $onerilen, true) ? '★ ' : '').$k['ad'])->all())
                        ->descriptions($tumu->map(fn ($k) => $k['kaynak']
                            .($k['nace'] ? ' · NACE '.IseOzguEgitimKutuphanesi::naceGoster($k['nace']) : '')
                            .' — '.\Illuminate\Support\Str::limit((string) $k['hedef'], 90))->all())
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(2),
                ];
            })
            ->action(function (array $data): void {
                $this->iseOzguUygula($data['secilenler'] ?? []);
            });
    }

    /**
     * 4. bölümü seçilen kütüphane konularıyla günceller: seçili kalanların
     * işaretli ayları korunur, seçimden çıkarılan kütüphane konuları silinir,
     * kütüphanede olmayan (elle yazılmış) satırlar olduğu gibi kalır.
     */
    public function iseOzguUygula(array $secilenler): void
    {
        $p = $this->plan();

        if (! $p || ! $this->firma) {
            return;
        }

        $tumu = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id());
        $adaGore = $tumu->mapWithKeys(fn ($k, $a) => [mb_strtolower(trim($k['ad'])) => $a]);
        $kilit = $this->kilitAyIndeksi();

        $mevcutlar = collect($p->egitimler ?? [])->where('kategori', 'ise_ozgu')->values()->map(function ($e) use ($tumu, $adaGore) {
            $a = $e['kutuphane_anahtari'] ?? null;
            $e['_anahtar'] = $a && $tumu->has($a) ? $a : ($adaGore[mb_strtolower(trim((string) ($e['konu'] ?? '')))] ?? null);

            return $e;
        });

        $yeni = $mevcutlar->filter(fn ($e) => $e['_anahtar'] === null || in_array($e['_anahtar'], $secilenler, true));
        $varOlan = $yeni->pluck('_anahtar')->filter()->all();

        foreach ($secilenler as $a) {
            if ($tumu->has($a) && ! in_array($a, $varOlan, true)) {
                $yeni->push(IseOzguEgitimKutuphanesi::planSatiri($tumu[$a], $kilit));
            }
        }

        $satirlar = $yeni->map(function ($e) {
            if (array_key_exists('_anahtar', $e)) {
                $e['kutuphane_anahtari'] = $e['_anahtar'] ?? ($e['kutuphane_anahtari'] ?? null);
                unset($e['_anahtar']);
            }

            return $e;
        })->values()->all();

        $p->update(['egitimler' => YillikPlanSablonu::iseOzguYerlestir($p->egitimler ?? [], $satirlar)]);

        Notification::make()->title('İşe özgü konular güncellendi')->body(count($satirlar).' konu planın 4. bölümünde.')->success()->send();
    }

    /** Plandaki bir 4. bölüm satırını kendi kütüphaneme kaydeder (başka firmalarda da seçilebilsin). */
    public function kutuphaneyeKaydetAction(): Action
    {
        return Action::make('kutuphaneyeKaydet')
            ->modalHeading('Konuyu kütüphaneye kaydet')
            ->modalDescription('Kaydedilen konu, NACE kodu veya iş kalemi eşleşen diğer firmaların planına otomatik önerilir.')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments): array {
                $e = ($this->plan()?->egitimler ?? [])[$arguments['index'] ?? -1] ?? [];
                $nace = preg_replace('/\D/', '', (string) $this->firma?->nace_kodu);

                return [
                    'ad' => $e['konu'] ?? null,
                    'hedef' => $e['hedef'] ?? null,
                    'egitici' => $e['egitici'] ?? IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                    'nace' => strlen($nace) >= 4 ? substr($nace, 0, 2).'.'.substr($nace, 2, 2) : null,
                    'is_kalemleri' => array_values($this->firma?->is_kalemleri ?? []),
                ];
            })
            ->schema(fn () => \App\Filament\Pages\IseOzguEgitimKonulari::konuSemasi())
            ->action(function (array $data, array $arguments): void {
                $k = \App\Models\IseOzguEgitimKonusu::create([
                    'user_id' => Filament::auth()->id(),
                    'ad' => $data['ad'], 'hedef' => $data['hedef'] ?? null, 'egitici' => $data['egitici'] ?? null,
                    'nace_onekleri' => IseOzguEgitimKutuphanesi::naceHazirla($data['nace'] ?? null),
                    'is_kalemleri' => array_values($data['is_kalemleri'] ?? []) ?: null,
                ]);

                $p = $this->plan();
                $egitimler = $p?->egitimler ?? [];
                if (isset($egitimler[$arguments['index'] ?? -1])) {
                    $egitimler[$arguments['index']]['kutuphane_anahtari'] = 'ozel_'.$k->id;
                    $p->update(['egitimler' => $egitimler]);
                }

                Notification::make()->title('Konu kütüphaneye kaydedildi')->success()->send();
            });
    }

    /**
     * Planı Kaydet — çalışma/eğitim ay hücreleri ve satır ekleme/silme zaten anlık
     * kaydediliyor; bu buton değerlendirme sekmesindeki serbest metin alanlarının
     * (henüz odaktan çıkmamış olsa bile) sunucuya yazılmasını garantiler ve
     * kullanıcıya "kaydedildi" geri bildirimi verir.
     */
    public function planiKaydet(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $p->update([
            'faaliyetler' => $p->faaliyetler ?? [],
            'egitimler' => $p->egitimler ?? [],
            'degerlendirmeler' => $p->degerlendirmeler ?? [],
        ]);

        unset($this->plan);

        Notification::make()
            ->title('Yıllık plan kaydedildi')
            ->body('Son kayıt: '.now()->format('d.m.Y H:i'))
            ->success()
            ->send();
    }

    /** Çıktıdaki "Hazırlanma Tarihi" — bugün dolu gelir, istenirse değiştirilir. */
    protected static function hazirlanmaTarihiAlani(): DatePicker
    {
        return DatePicker::make('hazirlanma_tarihi')
            ->label('Hazırlanma Tarihi')
            ->default(now()->toDateString())
            ->native(false)
            ->displayFormat('d.m.Y')
            ->required()
            ->helperText('Bugünün tarihi otomatik gelir; farklı bir tarih istiyorsanız değiştirin.');
    }

    protected static function secilenTarih(array $data): ?Carbon
    {
        return filled($data['hazirlanma_tarihi'] ?? null) ? Carbon::parse($data['hazirlanma_tarihi']) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('planiKaydet')
                ->label('Planı Kaydet')
                ->icon('heroicon-o-check')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null)
                ->action(fn () => $this->planiKaydet()),

            // Çalışma/eğitim planı çıktısı Excel şablonlarıyla (aşağıda); PDF yalnız
            // Değerlendirme Raporu için kaldı.
            Action::make('pdf')
                ->label('Çıktı Al (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('Yıllık Değerlendirme Raporu PDF olarak iner')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'degerlendirme')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn (array $data) => YillikPlanUretici::pdf($this->plan(), ImzaSecenegi::secili($data))),

            // Kullanıcının gerçek Excel şablonlarıyla birebir çıktı (YillikPlanExcelUretici).
            Action::make('calismaExcel')
                ->label('Çıktı Al (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('A4 yatay, yazdırmaya hazır Excel dosyası iner')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'calisma')
                ->modalHeading('Yıllık Çalışma Planı — Çıktı Al')
                ->modalSubmitActionLabel('İndir')
                ->schema([static::hazirlanmaTarihiAlani()])
                ->action(fn (array $data) => YillikPlanExcelUretici::calisma($this->plan(), static::secilenTarih($data))),

            Action::make('egitimExcel')
                ->label('Çıktı Al (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('Yazdırmaya hazır Excel dosyası iner')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'egitim')
                ->modalHeading('Yıllık Eğitim Planı — Çıktı Al')
                ->modalSubmitActionLabel('İndir')
                ->schema([static::hazirlanmaTarihiAlani(), ImzaSecenegi::alan()])
                ->action(fn (array $data) => YillikPlanExcelUretici::egitim($this->plan(), ImzaSecenegi::secili($data), static::secilenTarih($data))),

            Action::make('sablonuUygula')
                ->label('Standart Şablonu Uygula')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && in_array($this->sekme, ['calisma', 'egitim'], true))
                ->requiresConfirmation()
                ->modalHeading('Standart şablon uygulansın mı?')
                ->modalDescription(fn () => 'Bu plandaki ('.($this->sekme === 'egitim' ? 'eğitim planı' : 'çalışma planı').') tüm satırlar standart şablonla değiştirilecek: '
                    .($this->sekme === 'egitim'
                        ? '5 bölümlü eğitim planı (4. bölüm "İşe ve İşyerine Özgü Riskler" yalnız inşaat firmalarında dolu gelir).'
                        : '36 faaliyet; ana konu, periyot, sorumlu ve mevzuat/kayıt notlarıyla.')
                    .' Sözleşme başlangıcından önceki aylar boş bırakılır.')
                ->modalSubmitActionLabel('Uygula')
                ->action(function (): void {
                    $p = $this->plan();
                    if (! $p || ! $this->firma) {
                        return;
                    }
                    $icerik = YillikPlanSablonu::icerik($this->firma, $this->yil);
                    $alan = $this->sekme === 'egitim' ? 'egitimler' : 'faaliyetler';
                    $p->update([$alan => $icerik[$alan]]);
                    unset($this->plan);

                    Notification::make()->title('Standart şablon uygulandı')->success()->send();
                }),

            Action::make('excelYukleCalisma')
                ->label('Çalışma Planı Excel’den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'calisma')
                ->modalDescription('Kendi Yıllık Çalışma Planı Excel’inizi yükleyin. Satırlar isimle eşleştirilir: mevcut satır varsa ayları güncellenir, yoksa eklenir. Atanmış uzman öncesindeki aylar işaretlenmez.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([static::dosyaAlani()])
                ->action(fn (array $data) => $this->exceliIsle($data['dosya'], 'faaliyetler')),

            Action::make('excelYukleEgitim')
                ->label('Eğitim Planı Excel’den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'egitim')
                ->modalDescription('Kendi Yıllık Eğitim Planı Excel’inizi yükleyin (12 ay × 4 hafta düzeni desteklenir, ay bazına indirilir). Satırlar isimle eşleştirilir.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([static::dosyaAlani()])
                ->action(fn (array $data) => $this->exceliIsle($data['dosya'], 'egitimler')),

            Action::make('degerlendirmeSistemdenDoldur')
                ->label('Sistemden Doldur')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'degerlendirme')
                ->requiresConfirmation()
                ->modalHeading('Değerlendirmeyi sistem verisinden doldur')
                ->modalDescription('Risk değerlendirmesi, muayeneler, eğitimler, tatbikat, saha denetimi, kurul ve iş kazası satırlarının tarih + tekrar sayısı, '.$this->yil.' yılı için sisteme girilmiş kayıtlardan yazılır. Elle girdiğiniz bu iki alan üzerine yazılır; diğer alanlara dokunulmaz.')
                ->modalSubmitActionLabel('Doldur')
                ->action(function (): void {
                    $p = $this->plan();

                    if (! $p) {
                        return;
                    }

                    $sonuc = YillikDegerlendirmeVerisi::planiDoldur($this->firma, $this->yil, $p->degerlendirmeler ?? []);
                    $p->update(['degerlendirmeler' => $sonuc['satirlar']]);

                    Notification::make()
                        ->title($sonuc['doldurulan'] > 0
                            ? $sonuc['doldurulan'].' satır sistem verisinden dolduruldu'
                            : 'Bu yıl için eşleşen sistem kaydı bulunamadı')
                        ->{$sonuc['doldurulan'] > 0 ? 'success' : 'warning'}()
                        ->send();
                }),
        ];
    }

    private static function dosyaAlani(): FileUpload
    {
        return FileUpload::make('dosya')
            ->label('Excel dosyası (.xlsx / .xls)')
            ->disk('local')
            ->directory('excel-ice-aktarim')
            ->acceptedFileTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
            ])
            ->required();
    }

    /** @param  'faaliyetler'|'egitimler'  $tip */
    private function exceliIsle(string $dosya, string $tip): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $yol = Storage::disk('local')->path($dosya);

        try {
            $sonuc = YillikPlanExcelIceAktarici::iceAktar($yol, $p, $tip);
        } catch (Throwable $e) {
            Notification::make()->title('Dosya işlenemedi')->body($e->getMessage())->danger()->send();

            return;
        } finally {
            Storage::disk('local')->delete($dosya);
        }

        unset($this->plan);

        if ($sonuc['hatalar']) {
            Notification::make()->title('İçe aktarma tamamlanamadı')->body(implode(' ', $sonuc['hatalar']))->danger()->send();

            return;
        }

        Notification::make()
            ->title('Excel içe aktarıldı')
            ->body("{$sonuc['eklenen']} satır eklendi, {$sonuc['guncellenen']} satır güncellendi.")
            ->success()
            ->send();
    }
}
