<?php

namespace App\Filament\Pages;

use App\Models\DofRaporu;
use App\Models\OlayKaydi;
use App\Models\Firma;
use App\Filament\Support\ImzaSecenegi;
use App\Support\DofRaporuUretici;
use App\Support\DofTabloOkuyucu;
use App\Support\GeminiOneriDanismani;
use App\Support\RiskSkorlama;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * DÖF Oluştur (Çoklu DÖF) — isgpratik 158.jpg. Saha gözetimi sonucu birden
 * çok Düzeltici Önleyici Faaliyet maddesini tek raporda toplar; "AI ile Öneri
 * Al" GeminiOneriDanismani'yı (Tespit Öneri Defteri ile aynı) kullanır.
 */
class DofOlustur extends Page
{
    use \App\Filament\Concerns\SinirliErisim;
    use \App\Filament\Concerns\KaydetSecenegi;

    use \App\Filament\Concerns\HazirRaporYukleme;
    use WithFileUploads;
    use \App\Filament\Concerns\WhatsAppGonderir;

    protected string $view = 'filament.pages.dof-olustur';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 98;

    protected static ?string $slug = 'dof';

    protected static ?string $title = 'DÖF Oluştur';

    protected static ?string $navigationLabel = 'DÖF Oluştur';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public ?string $alanBolge = null;

    public ?string $gozetimTarihAraligi = null;

    public ?string $raporTarihi = null;

    public ?string $gozetimYapan = null;

    public ?string $gozetimYapanSertifikaNo = null;

    public ?string $sorumluKisi = 'İşveren / İşveren Vekili';

    public ?string $isverenVekiliAdi = null;

    /** @var array<int, array{tespit: string, oncelik: string, oneri: ?string, sorumlu: ?string, termin: ?string, durum: string}> */
    public array $maddeler = [];

    /** DÖF bir Olay Kaydı'ndan aktarıldıysa — kaydedilince olaya bağlanır. */
    public ?int $kaynakOlayKaydiId = null;

    public ?string $yeniTespit = null;

    public string $yeniOncelik = 'orta';

    public ?string $yeniOneri = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniTermin = null;

    /** DÖF maddesi risk değerlendirme metodu: 'standart', 'matris_5x5' veya 'fine_kinney'. */
    public string $yeniRiskMetodu = 'standart';

    public ?float $yeniOlasilik = null;

    public ?float $yeniFrekans = null;

    public ?float $yeniSiddet = null;

    /** Sahada tespit edilen uygunsuzluğun fotoğraf kanıtı (yeni madde eklerken). */
    public $yeniFoto = null;

    /** Listedeki maddeye sonradan fotoğraf eklemek için (madde indeksi => dosya). */
    public array $maddeFotolari = [];

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();

        if ($aktarim = session()->pull('dof_aktarim')) {
            $this->firmaId = $aktarim['firma_id'];
            $this->updatedFirmaId();

            // Başka sayfanın "Hazır Rapor Yükle"sine verilen DÖF tablosu (Word/Excel)
            if (isset($aktarim['tablo'])) {
                $this->aktarimUygula($aktarim['tablo']);

                return;
            }

            $this->maddeler = [...$this->maddeler, ...$aktarim['maddeler']];
            $this->kaynakOlayKaydiId = $aktarim['olay_kaydi_id'] ?? null;

            Notification::make()
                ->title(count($aktarim['maddeler']).' bulgu '.($aktarim['kaynak'] ?? 'AI Saha Analizi').'\'nden aktarıldı')
                ->success()
                ->send();

            return;
        }

        // ?firma= yoksa Saha modülü ortak firma bağlamı (App\Support\KomutaFirma) açılır.
        if ($firmaId = request()->integer('firma') ?: \App\Support\KomutaFirma::baslangic($this->firmalar)) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    /**
     * Firma modelinin kendi görünürlük global scope'u (bkz. Firma::booted) zaten
     * sahip olunan + paylaşılan firmaları doğru kapsıyor — burada AYRICA
     * `user_id` ile filtrelemek (eski, tek kullanıcılı dönemden kalma) paylaşılan
     * firmaları dışlayıp uzmanın DÖF'ünü görememesine/indirememesine yol açıyordu.
     */
    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::find($this->firmaId)
            : null;
    }

    #[Computed]
    public function oncelikler(): array
    {
        return config('isg.dof.oncelikler');
    }

    #[Computed]
    public function durumlar(): array
    {
        return config('isg.dof.durumlar');
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiOneriDanismani::aktifMi();
    }

    /** @return Collection<int, DofRaporu> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->dofRaporlari()->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Portföy Geneli Takip — isgpratik DÖF ana ekranı (24.jpg)
    |--------------------------------------------------------------------------
    */

    public string $takipArama = '';

    public string $takipOncelikFiltre = '';

    /** @var array<string, string> "raporId-maddeIndex" => kapatma notu taslağı */
    public array $kapatmaNotlari = [];

    /** Kullanıcının tüm firmalarındaki tüm DÖF maddeleri, firma bilgisiyle birlikte düz liste. */
    #[Computed]
    public function tumMaddeler(): Collection
    {
        return DofRaporu::query()
            ->whereHas('firma')
            ->with('firma')
            ->get()
            ->flatMap(fn (DofRaporu $rapor) => collect($rapor->maddeler ?? [])->map(fn (array $m, int $i) => [
                ...$m,
                'anahtar' => $rapor->id.'-'.$i,
                'rapor_id' => $rapor->id,
                'madde_index' => $i,
                'firma' => $rapor->firma?->unvan ?? '—',
                'belge_no' => $rapor->belge_no,
            ]));
    }

    #[Computed]
    public function takipOzeti(): array
    {
        $tumu = $this->tumMaddeler;
        $acik = $tumu->where('durum', '!=', 'tamamlandi');
        $kapanmis = $tumu->where('durum', 'tamamlandi');

        return [
            'toplam_dof' => DofRaporu::query()->whereHas('firma')->count(),
            'acik_madde' => $acik->count(),
            'kapanmis_madde' => $kapanmis->count(),
            'kapatma_orani' => $tumu->isEmpty() ? 0 : round(($kapanmis->count() / $tumu->count()) * 100),
            'oncelik_dagilimi' => $acik->countBy('oncelik'),
        ];
    }

    /** @return Collection<int, array> arama/filtre uygulanmış açık maddeler */
    #[Computed]
    public function acikMaddelerFiltreli(): Collection
    {
        $terim = mb_strtolower(trim($this->takipArama));

        return $this->tumMaddeler
            ->where('durum', '!=', 'tamamlandi')
            ->when($this->takipOncelikFiltre !== '', fn ($q) => $q->where('oncelik', $this->takipOncelikFiltre))
            ->when($terim !== '', fn ($q) => $q->filter(
                fn (array $m) => str_contains(mb_strtolower($m['tespit'] ?? ''), $terim)
                    || str_contains(mb_strtolower($m['firma'] ?? ''), $terim)
                    || str_contains(mb_strtolower($m['belge_no'] ?? ''), $terim),
            ))
            ->sortByDesc(fn (array $m) => array_search($m['oncelik'] ?? 'dusuk', ['dusuk', 'orta', 'yuksek', 'kritik']))
            ->values();
    }

    /** Portföydeki (kaydedilmiş) bir DÖF maddesini "Tamamlandı" yapıp kapatma notu ekler. */
    public function maddeKapat(int $raporId, int $maddeIndex): void
    {
        $rapor = DofRaporu::query()
            ->whereHas('firma')
            ->find($raporId);

        if (! $rapor || ! isset($rapor->maddeler[$maddeIndex])) {
            return;
        }

        $maddeler = $rapor->maddeler;
        $maddeler[$maddeIndex]['durum'] = 'tamamlandi';
        $maddeler[$maddeIndex]['kapatma_notu'] = $this->kapatmaNotlari[$raporId.'-'.$maddeIndex] ?? null;
        $maddeler[$maddeIndex]['kapatma_tarihi'] = now()->toDateString();
        $rapor->update(['maddeler' => $maddeler]);

        unset($this->kapatmaNotlari[$raporId.'-'.$maddeIndex], $this->tumMaddeler, $this->takipOzeti, $this->acikMaddelerFiltreli);
        unset($this->gecmisKayitlar);

        Notification::make()->title('Madde kapatıldı')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
        $this->kayitUnut();

        unset($this->firma, $this->gecmisKayitlar);

        foreach ($this->firmaKunyesi() as $alan => $deger) {
            $this->{$alan} = $deger;
        }
    }

    /**
     * Künyenin kişi bilgileri firma kaydından: gözetim yapan = firmanın İGU'su,
     * İGU atanmamışsa hesap sahibi (uzmanın kendisi); işveren vekili firmadan.
     * Word/Excel'den aktarımda bu alanlar dosyadakiyle değiştirilmez (kullanıcı
     * isteği 08.10.2026 — dışarıda hazırlanan raporlar aynı standartta çıksın).
     *
     * @return array<string, ?string>
     */
    private function firmaKunyesi(): array
    {
        $firma = $this->firma;

        if (! $firma) {
            return [];
        }

        $igu = $firma->igu;
        $sahip = $firma->user;

        return [
            'gozetimYapan' => $igu?->ad_soyad ?: $sahip?->name,
            'gozetimYapanSertifikaNo' => $igu ? $igu->sertifika_no : $sahip?->sertifika_no,
            'isverenVekiliAdi' => $firma->isveren_vekili ?: $firma->isveren_ad,
        ];
    }

    public function maddeEkle(): void
    {
        if (blank($this->yeniTespit)) {
            return;
        }

        $riskSonuc = $this->yeniRiskSonuc();

        $this->maddeler[] = [
            'tespit' => $this->yeniTespit,
            'oncelik' => $this->yeniOncelik,
            'oneri' => $this->yeniOneri,
            'sorumlu' => $this->yeniSorumlu,
            'termin' => $this->yeniTermin,
            'durum' => 'acik',
            'foto_yolu' => $this->yeniFoto?->store('dof-foto', 'public'),
            ...(($riskSonuc && ($riskSonuc['puan'] ?? 0) > 0) ? [
                'risk_metodu' => $this->yeniRiskMetodu,
                'risk_puani' => $riskSonuc['puan'],
                'risk_duzeyi' => $riskSonuc['duzey'],
            ] : []),
        ];

        $this->reset('yeniTespit', 'yeniOneri', 'yeniSorumlu', 'yeniTermin', 'yeniFoto', 'yeniOlasilik', 'yeniFrekans', 'yeniSiddet');
        $this->yeniOncelik = 'orta';
    }

    /** Hazır termin kısayolu (1 Hafta, 2 Hafta, 1 Ay, 2 Ay, 3 Ay, 6 Ay). */
    public function terminKisayolu(int $gun): void
    {
        $this->yeniTermin = now()->addDays($gun)->toDateString();
    }

    /** @return array{puan:int|float, duzey:string, renk:string, eylem:string}|null */
    #[Computed]
    public function yeniRiskSonuc(): ?array
    {
        if ($this->yeniRiskMetodu === 'standart') {
            return null;
        }

        return RiskSkorlama::hesapla(
            $this->yeniRiskMetodu,
            $this->yeniOlasilik,
            $this->yeniSiddet,
            $this->yeniRiskMetodu === 'fine_kinney' ? $this->yeniFrekans : null,
        );
    }

    public function updatedYeniOlasilik(): void
    {
        $this->riskOnceliginiSenkronla();
    }

    public function updatedYeniFrekans(): void
    {
        $this->riskOnceliginiSenkronla();
    }

    public function updatedYeniSiddet(): void
    {
        $this->riskOnceliginiSenkronla();
    }

    private function riskOnceliginiSenkronla(): void
    {
        $sonuc = $this->yeniRiskSonuc();
        if (! $sonuc || ! ($sonuc['puan'] ?? 0)) {
            return;
        }

        $puan = (float) $sonuc['puan'];
        if ($this->yeniRiskMetodu === 'fine_kinney') {
            $this->yeniOncelik = match (true) {
                $puan >= 400 => 'kritik',
                $puan >= 200 => 'yuksek',
                $puan >= 70 => 'orta',
                default => 'dusuk',
            };
        } else {
            $this->yeniOncelik = match (true) {
                $puan >= 20 => 'kritik',
                $puan >= 12 => 'yuksek',
                $puan >= 6 => 'orta',
                default => 'dusuk',
            };
        }
    }

    /** Ham saha notundan Tespit + Düzeltici Faaliyet (Öneri) + Öncelik oto-doldurma. */
    public function aiOtoDoldur(): void
    {
        $not = trim((string) $this->yeniTespit);
        if ($not === '') {
            Notification::make()->title('Önce kısa bir tespit / uygunsuzluk notu yazın')->warning()->send();

            return;
        }

        if ($this->aiAktif && ($oneri = GeminiOneriDanismani::oner($not))) {
            $this->yeniOneri = $oneri;
            Notification::make()->title('Düzeltici faaliyet yapay zekâ ile dolduruldu')->success()->send();

            return;
        }

        $kucuk = mb_strtolower($not, 'UTF-8');
        $this->yeniOneri = match (true) {
            str_contains($kucuk, 'elektrik') || str_contains($kucuk, 'pano') || str_contains($kucuk, 'kablo')
                => 'Elektrik panosu ve açık kablolar yetkili elektrikçi tarafından izole edilmeli, kaçak akım rölesi (30 mA) kontrol edilmeli, pano önü yalıtkan paspas tamamlanmalı ve yetkisiz erişime karşı kilitlenmelidir.',
            str_contains($kucuk, 'yangın') || str_contains($kucuk, 'tüp') || str_contains($kucuk, 'acil çıkış')
                => 'Yangın söndürme tüplerinin basınç/dolum kontrolleri yapılmalı, yerden 90 cm yüksekliğe asılmalı, önleri ve acil çıkış güzergâhları malzeme istifinden arındırılarak sürekli açık tutulmalıdır.',
            str_contains($kucuk, 'iskele') || str_contains($kucuk, 'yüksek') || str_contains($kucuk, 'korkuluk') || str_contains($kucuk, 'boşluk')
                => 'Düşme tehlikesi bulunan kat/şaft boşluklarına standartlara uygun (110 cm üst, 55 cm ara kayıt ve 15 cm topuk levhalı) korkuluk yapılmalı, emniyet kemeri yaşam hattı kurulmalıdır.',
            str_contains($kucuk, 'kkd') || str_contains($kucuk, 'baret') || str_contains($kucuk, 'gözlük') || str_contains($kucuk, 'ayakkabı')
                => 'Çalışanlara yapılan işe uygun EN standartlı Kişisel Koruyucu Donanım (KKD) zimmet tutanağı ile teslim edilmeli, sahada kullanımı günlük olarak denetlenmelidir.',
            str_contains($kucuk, 'makine') || str_contains($kucuk, 'koruyucu') || str_contains($kucuk, 'pres') || str_contains($kucuk, 'acil durdurma')
                => 'Makinenin döner/hareketli aksam koruyucu muhafazaları ve acil durdurma (E-Stop) butonu aktif hale getirilmeli, güvenlik donanımı devre dışı bırakılarak kesinlikle çalıştırılmamalıdır.',
            str_contains($kucuk, 'kimyasal') || str_contains($kucuk, 'msds') || str_contains($kucuk, 'depolama')
                => 'Kimyasal maddelerin Türkçe Güvenlik Bilgi Formları (GBF/MSDS) çalışma alanına asılmalı, taşma havuzlu ve havalandırmalı alanda etiketli şekilde depolanmalıdır.',
            default
                => 'Tespit edilen uygunsuzluk 6331 sayılı İSG Kanunu m.4 kapsamında teknik ve idari tedbirlerle giderilmeli, ilgili çalışanlara bilgilendirme yapılarak tekrarını önleyici saha kontrolü sağlanmalıdır.',
        };

        Notification::make()->title('Düzeltici faaliyet önerisi otomatik dolduruldu')->success()->send();
    }

    public function tablodanAktar(array $data): void
    {
        $yol = (string) ($data['dosya'] ?? '');
        $adi = (string) ($data['dosya_adi'] ?? $yol);

        try {
            $sonuc = DofTabloOkuyucu::oku(Storage::disk('local')->path($yol), pathinfo($adi, PATHINFO_EXTENSION));
        } catch (\Throwable $e) {
            report($e);
            $sonuc = ['bilgi' => [], 'maddeler' => []];
        } finally {
            Storage::disk('local')->delete($yol);
        }

        $fotolar = array_values(array_filter((array) ($data['fotograflar'] ?? [])));
        $esles = \App\Support\SahaBulguExcelOkuyucu::fotoEsle($fotolar, (array) ($data['fotograf_adlari'] ?? []));

        if (! $sonuc['maddeler']) {
            Storage::disk('public')->delete($fotolar);
            Notification::make()
                ->title('Dosyada DÖF tablosu bulunamadı')
                ->body('Tabloda "Tespit / Uygunsuzluk" ve "Öneri / Düzeltici Faaliyet" başlıklı sütunlar olmalı.')
                ->danger()->send();

            return;
        }

        // 14.11.2026: Fotoğraf No'ya göre — dosya adındaki numara (1.jpg) ya da yükleme sırası.
        // Excel/Word satırına yapıştırılmış fotoğraf varsa o korunur.
        $sira = count($this->maddeler);
        $fotosuz = 0;
        foreach ($sonuc['maddeler'] as $i => $m) {
            $foto = ($m['foto_no'] ?? null) ? ($esles[$m['foto_no']] ?? null) : null;

            if ($foto && empty($m['foto_yolu'])) {
                $sonuc['maddeler'][$i]['foto_yolu'] = $foto;
            }

            if (($m['foto_no'] ?? null) && empty($sonuc['maddeler'][$i]['foto_yolu'])) {
                $fotosuz++;
            }

            // Gemini işaret verdiyse uygunsuzluğun yeri fotoğrafın kopyasında madde
            // numarasıyla çemberlenir (14.11.2026) — işverenin dikkatini çeker.
            $yol = $sonuc['maddeler'][$i]['foto_yolu'] ?? null;
            if ($yol && ! empty($m['isaret'])) {
                $isaretli = \App\Support\FotoIsaretleyici::cember($yol, $m['isaret']['x'], $m['isaret']['y'], $m['isaret']['cap'], 'public', $sira + $i + 1);
                if ($isaretli) {
                    $sonuc['maddeler'][$i]['foto_yolu'] = $isaretli;
                    $sonuc['isaretlenen'] = ($sonuc['isaretlenen'] ?? 0) + 1;
                }
            }
        }
        // Raporda kullanılmayan yüklemeler silinir (işaretli kopyası alınmış orijinal dahil).
        Storage::disk('public')->delete(array_diff($fotolar, array_map(
            fn (array $m) => $m['foto_yolu'] ?? null, $sonuc['maddeler'],
        )));

        $this->aktarimUygula($sonuc);

        if ($uyari = \App\Support\SahaBulguExcelOkuyucu::fotoUyarisi($fotosuz, count($fotolar))) {
            Notification::make()->title('Bazı maddeler fotoğrafsız eklendi')->body($uyari)->warning()->persistent()->send();
        }
    }

    /** "Hazır Rapor Yükle"ye DÖF tablosu verildiyse bu sayfada yönlendirmeden aktarılır. */
    protected function dofTablosunuAktar(array $tablo)
    {
        $this->aktarimUygula($tablo);

        return null;
    }

    /**
     * DofTabloOkuyucu sonucunu sayfaya işler — "Word/Excel'den Aktar", "Hazır
     * Rapor Yükle"ye verilen DÖF tablosu ve başka sayfadan yönlendirme ortak.
     *
     * @param  array{bilgi: array<string, string>, maddeler: array<int, array<string, mixed>>}  $sonuc
     */
    public function aktarimUygula(array $sonuc): void
    {
        // Dosyadan yalnız alan/bölge ve tarihler alınır; kişi bilgileri (gözetim
        // yapan, sertifika, işveren vekili, sorumlu kişi) firma kaydından gelir —
        // dosyadaki yalnız firmada karşılığı boşsa kullanılır.
        $firmadan = array_filter($this->firmaKunyesi());

        foreach ($sonuc['bilgi'] ?? [] as $alan => $deger) {
            if (isset($firmadan[$alan]) || $alan === 'sorumluKisi') {
                continue;
            }

            if (blank($this->{$alan}) || $alan === 'raporTarihi') {
                $this->{$alan} = $deger;
            }
        }

        foreach ($firmadan as $alan => $deger) {
            $this->{$alan} = $deger;
        }

        $maddeler = array_map(function (array $m) {
            unset($m['foto_no'], $m['isaret']);

            return $m;
        }, $sonuc['maddeler']);
        $this->maddeler = [...$this->maddeler, ...$maddeler];

        $fotolu = count(array_filter($maddeler, fn (array $m) => filled($m['foto_yolu'] ?? null)));
        $fotoNotu = ($fotolu ? $fotolu.' maddeye fotoğraf bağlandı. ' : '')
            .(! empty($sonuc['isaretlenen']) ? $sonuc['isaretlenen'].' maddede uygunsuzluğun yeri fotoğrafta numaralı çemberle işaretlendi. ' : '');

        Notification::make()
            ->title(count($sonuc['maddeler']).' madde aktarıldı')
            ->body($fotoNotu.($this->firma ? 'Eksik fotoğrafları ekleyip "DÖF Raporu (Kaydet ve İndir)" ile çıktı alın.' : 'Kaydetmek için firmayı seçin.'))
            ->success()->send();
    }

    public function updatedMaddeFotolari($dosya, $index): void
    {
        $index = (int) $index;

        if (! isset($this->maddeler[$index]) || ! $dosya) {
            return;
        }

        $this->validate(['maddeFotolari.'.$index => 'image|max:10240']);

        $this->maddeler[$index]['foto_yolu'] = $dosya->store('dof-foto', 'public');
        unset($this->maddeFotolari[$index]);
    }

    public function maddeFotoKaldir(int $index): void
    {
        if (isset($this->maddeler[$index])) {
            $this->maddeler[$index]['foto_yolu'] = null;
        }
    }

    public function maddeSil(int $index): void
    {
        unset($this->maddeler[$index]);
        $this->maddeler = array_values($this->maddeler);
        $this->maddeFotolari = [];
    }

    public function durumGuncelle(int $index, string $durum): void
    {
        if (isset($this->maddeler[$index])) {
            $this->maddeler[$index]['durum'] = $durum;
        }
    }

    public function aiOnerisiAl(): void
    {
        $oneri = GeminiOneriDanismani::oner((string) $this->yeniTespit);

        if ($oneri) {
            $this->yeniOneri = $oneri;
        } else {
            Notification::make()->title('Öneri alınamadı')->body('Yapay zeka şu an kullanılamıyor.')->warning()->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?DofRaporu
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (! $this->maddeler) {
            Notification::make()->title('En az bir DÖF maddesi ekleyin')->danger()->send();

            return null;
        }

        $d = $this->kayitIcin(DofRaporu::class, [
            'firma_id' => $this->firma->id,
            'alan_bolge' => $this->alanBolge,
            'gozetim_tarih_araligi' => $this->gozetimTarihAraligi,
            'rapor_tarihi' => $this->raporTarihi,
            'gozetim_yapan' => $this->gozetimYapan,
            'gozetim_yapan_sertifika_no' => $this->gozetimYapanSertifikaNo,
            'gozetim_yapan_kase' => $this->firma->igu?->kase_gorseli,
            'sorumlu_kisi' => $this->sorumluKisi,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'maddeler' => $this->maddeler,
        ]);
        $d->save();

        // Kayıt maddeleri ortak bulgulara bağladı (bulgu_id); sayfa da bunu taşısın,
        // tekrar kaydedilirse aynı bulgular kullanılsın.
        $this->maddeler = $d->fresh()->maddeler ?? $this->maddeler;

        // Olay Kaydı'ndan gelen DÖF → olayın "DÖF" sütununa bağla (ilk DÖF korunur).
        if ($this->kaynakOlayKaydiId) {
            OlayKaydi::where('firma_id', $this->firma->id)
                ->whereKey($this->kaynakOlayKaydiId)
                ->whereNull('dof_raporu_id')
                ->update(['dof_raporu_id' => $d->id]);
        }

        unset($this->gecmisKayitlar);

        return $d;
    }

    protected function hazirRaporKategorisi(): string
    {
        return 'dof';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->kaydetAction(),
            $this->yeniKayitAction(),
            Action::make('bulgulardanEkle')
                ->label('Saha Bulgularından Ekle')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('Saha Bulguları\'nda kayıtlı açık bulgular DÖF maddesi olarak eklenir; madde tamamlanınca bulgu da kapanır.')
                ->schema([
                    \Filament\Forms\Components\CheckboxList::make('idler')
                        ->label('Açık bulgular')
                        ->options(fn () => \App\Support\BulguHavuzu::secenekler($this->firma->id, $this->maddeler))
                        ->required()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data): void {
                    $eklenen = \App\Models\SahaBulgusu::query()->where('firma_id', $this->firma->id)->whereIn('id', $data['idler'])->orderBy('id')->get();
                    $this->maddeler = [...$this->maddeler, ...$eklenen->map(fn (\App\Models\SahaBulgusu $b) => \App\Support\BulguDonusturucu::dofMaddesi($b, bolumOnEki: true))->all()];
                    Notification::make()->title($eklenen->count().' bulgu eklendi')->success()->send();
                }),

            Action::make('tablodanAktar')
                ->label("Word/Excel'den Aktar")
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->modalHeading("Word/Excel'deki DÖF tablosunu aktar")
                ->modalDescription('Bilgisayarda hazırladığınız DÖF tablosu (Tespit, Öncelik, Öneri, Sorumlu, Termin, Durum sütunları) madde madde listeye eklenir; üst bilgiler (Alan/Bölge, Gözetim Yapan...) doldurulur. Ardından fotoğrafları ekleyip sistemin DÖF raporu olarak çıktı alırsınız.')
                ->modalSubmitActionLabel('Aktar')
                ->stickyModalFooter()
                ->schema([
                    // MIME yerine uzantı — gerekçe DosyaKabul'de.
                    \App\Filament\Support\DosyaKabul::uygula(FileUpload::make('dosya')->storeFileNamesIn('dosya_adi')
                        ->label('Word (.docx) veya Excel (.xlsx/.xls) dosyası')
                        ->disk('local')->directory('dof-aktarim'), ['docx', 'xlsx', 'xls'])
                        ->maxSize(20480)
                        ->required(),
                    FileUpload::make('fotograflar')
                        ->label('Fotoğraflar (isteğe bağlı)')
                        ->helperText('Tablodaki "Fotoğraf No"ya göre maddelere yerleşir. Dosya adları numaralıysa (1.jpg, 2.jpg…) numaraya, değilse sıraya bakılır (sürükleyerek düzeltebilirsiniz). Fotoğrafı Excel/Word\'de maddenin satırına yapıştırdıysanız burayı boş bırakın.')
                        ->storeFileNamesIn('fotograf_adlari')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->maxFiles(20)
                        ->maxSize(10240)
                        ->disk('public')->directory('dof-foto'),
                ])
                ->action(fn (array $data) => $this->tablodanAktar($data)),
            $this->hazirRaporYukleAction(),
            Action::make('pdf')
                ->label('DÖF Raporu (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $d = $this->kaydet();

                    if (! $d) {
                        return null;
                    }

                    $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır

                    Notification::make()->title('DÖF raporu kaydedildi')->body($d->belge_no)->success()->send();

                    return DofRaporuUretici::pdf($d, ImzaSecenegi::secili($data));
                }),

            Action::make('whatsapp')
                ->label('WhatsApp ile İşverene Gönder')
                ->color('success')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn () => $this->firma !== null)
                ->action(function (): void {
                    if ($d = $this->kaydet()) {
                        $this->whatsappAc($this->getWhatsappShareUrl($d->id), 'DÖF raporu');
                    }
                }),
        ];
    }

    /**
     * DÖF raporunu işverene gönderen WhatsApp bağlantısı (imzalı 30 günlük PDF bağlantısıyla).
     * İşverenin cep telefonu yoksa null.
     */
    public function getWhatsappShareUrl(int $dofReportId): ?string
    {
        $d = $this->firma?->dofRaporlari()->find($dofReportId);

        return $d ? \App\Support\IsverenWhatsApp::url($d->firma, \App\Support\IsverenWhatsApp::dofMesaji($d)) : null;
    }

    public function gecmisWhatsapp(int $id): void
    {
        $this->whatsappAc($this->getWhatsappShareUrl($id), 'DÖF raporu');
    }

    public function gecmisPdf(int $id)
    {
        $d = $this->firma?->dofRaporlari()->find($id);

        return $d ? DofRaporuUretici::pdf($d) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->dofRaporlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
