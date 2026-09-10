<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\RiskSablonu;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Support\GeminiRiskPuanTamamlayici;
use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use App\Support\RiskKutuphanesi;
use App\Support\RiskSkorlama;
use App\Support\RiskUretici;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Risk Değerlendirme Sihirbazı — isgpratik 10-18.jpg.
 * 6 adım: Firma Bilgileri → Ekleme Yöntemi → Risk Ekleme → Tercihler →
 *         Risklerim → Önizleme & Kaydet.
 *
 * Faz 3a: "Manuel Seçim" yöntemi tam; AI / Şablon / Kayıtlı / Excel yöntemleri
 * ile PDF çıktısı sonraki fazda (ekran görüntüleri geldikçe).
 */
class RiskSihirbazi extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.risk-sihirbazi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Risk Yönetimi';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'risk-degerlendirme';

    protected static ?string $title = 'Risk Değerlendirme';

    protected static ?string $navigationLabel = 'Risk Değerlendirme';

    public const ADIMLAR = [
        1 => 'Firma Bilgileri',
        2 => 'Ekleme Yöntemi',
        3 => 'Risk Ekleme',
        4 => 'Tercihler',
        5 => 'Risklerim',
        6 => 'Önizleme & PDF',
    ];

    public const YONTEMLER = [
        'ai' => ['ad' => 'Yapay Zeka Sohbeti ile Risk Üret', 'onerilen' => true, 'hazir' => true,
            'aciklama' => 'Önce işyeriniz hakkında sektöre özel sorular sorulur, sonra kütüphane + duruma özel öneriler üretilir.',
            'maddeler' => ['Sektör-spesifik soru akışı', 'Cevaplardaki eksiklik → mevzuat riski', 'Mevzuat referanslı öneriler']],
        'manuel' => ['ad' => 'Manuel Seçim', 'onerilen' => false, 'hazir' => true,
            'aciklama' => 'Risk kütüphanesinden kendiniz seçin.',
            'maddeler' => ['Kategori bazlı filtreleme', 'Detaylı risk listesi', 'Tam kontrol']],
        'sablon' => ['ad' => 'Şablonlar & Paylaşılanlar', 'onerilen' => false, 'hazir' => true,
            'aciklama' => 'Sektöre göre kaydettiğiniz risk setleri; aynı sektörden yeni firmada tek tıkla uygulanır.',
            'maddeler' => ['Sektör bazlı gruplama', 'Kendi hazır risk setleriniz', 'Tek tıkla toplu ekleme']],
        'kayitli' => ['ad' => 'Kayıtlı Risklerim', 'onerilen' => false, 'hazir' => false,
            'aciklama' => 'Daha önce eklediğiniz risk maddelerinizi klasörlenmiş olarak seçin.',
            'maddeler' => ['Klasör bazlı görüntüleme', 'Arama ve filtreleme', 'Toplu veya tekli ekleme']],
        'excel' => ['ad' => 'Risk Değerlendirmenizden Yükleyin', 'onerilen' => false, 'hazir' => true,
            'aciklama' => 'Kendi Excel risk değerlendirmenizi yükleyin; sistem risk maddelerini otomatik ekler.',
            'maddeler' => ['Her formatı akıllı algılama', 'Eksik puan/önlem tamamlama', 'Tüm maddeler otomatik eklenir']],
    ];

    /** Excel içe aktarımda "eksik puan/önlemi AI ile tamamla" tek seferde en fazla bu kadar madde işler. */
    private const AI_TOPLU_LIMIT = 40;

    /**
     * Bu sayıdan çok maddeli Excel dosyaları sihirbaz adımına yüklenmez —
     * binlerce Livewire input tarayıcıyı kilitler, payload sınırını aşar.
     * Doğrudan bir Risk Değerlendirmesi oluşturulup düzenleme tablosuna yönlendirilir.
     */
    private const EXCEL_DOGRUDAN_ESIGI = 400;

    public int $adim = 1;

    public ?int $firmaId = null;

    public ?string $raporTarihi = null;

    public ?string $gecerlilikTarihi = null;

    public string $yontem = 'matris_5x5';

    public ?string $yontemSecim = null;

    /** @var array<int, int> açık accordion kategori id'leri */
    public array $acikKategoriler = [];

    /** @var array<int, array<string, mixed>> seçilen risk maddeleri */
    public array $secilenler = [];

    // Adım 4 — Tercihler
    public bool $etkilenenDiger = false;

    public ?string $varsayilanTermin = null;

    /*
    | Adım 3 — "Yapay Zeka" (kural tabanlı) alt akışı (isgpratik 103-115)
    | aiAsama: baslangic | sektor | altkategori | sohbet | sonuc
    */
    public string $aiAsama = 'baslangic';

    public ?string $aiSektor = null;

    /** @var array<int, string> seçilen alt kategori etiketleri */
    public array $aiAltKategoriler = [];

    /** @var array<string, string|array<int,string>> soru anahtarı => cevap */
    public array $aiCevaplar = [];

    /** @var array<int, string> "Atla" denen soru anahtarları */
    public array $aiAtlananlar = [];

    /** Çoklu soruda "Cevabı Gönder" öncesi geçici seçim. */
    public array $aiGecici = [];

    /** @var array<int, array<string, mixed>> üretilen aday riskler */
    public array $aiAdaylar = [];

    /** @var array<int, string> seçilen aday anahtarları */
    public array $aiSecilenAdaylar = [];

    /*
    | Adım 3 — "Risk Değerlendirmenizden Yükleyin" alt akışı: kullanıcının
    | kendi Excel dosyası, esnek başlık algılamayla (RiskDegerlendirmesiExcelOkuyucu).
    */
    public ?UploadedFile $excelDosya = null;

    /** @var array<int, array<string, mixed>> dosyadan okunan aday riskler */
    public array $excelAdaylar = [];

    /** @var array<int, string> seçilen aday anahtarları */
    public array $excelSecilenAdaylar = [];

    /** @var array<int, string> */
    public array $excelHatalar = [];

    /** Dosyadaki (bölüm+faaliyet+tehlike+risk aynı) tekrar eden satır sayısı. */
    public int $excelTekrarSayisi = 0;

    /** İşaretliyse tekrar eden satırlar tek maddeye indirilir; varsayılan: hepsi eklenir. */
    public bool $excelTekrarBirlestir = false;

    // Adım 6 — sektör şablonu olarak kaydetme
    public ?string $sablonAd = null;

    public ?string $sablonSektor = null;

    /** Adıma göre birincil buton etiketi (isgpratik: "Yöntem Seç", "Risk Ekle" …). */
    public const ILERI_ETIKET = [
        1 => 'Yöntem Seç',
        2 => 'Risk Ekle',
        3 => 'Devam Et',
        4 => 'Devam Et',
        5 => 'Önizlemeye Geç',
    ];

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();
        $this->gecerlilikTarihiHesapla();
    }

    public function sifirla(): void
    {
        $this->reset([
            'adim', 'firmaId', 'gecerlilikTarihi', 'yontem', 'yontemSecim',
            'acikKategoriler', 'secilenler', 'etkilenenDiger', 'varsayilanTermin',
            'aiAsama', 'aiSektor', 'aiAltKategoriler', 'aiCevaplar', 'aiAtlananlar',
            'aiGecici', 'aiAdaylar', 'aiSecilenAdaylar', 'sablonAd', 'sablonSektor',
            'excelDosya', 'excelAdaylar', 'excelSecilenAdaylar', 'excelHatalar',
            'excelTekrarSayisi', 'excelTekrarBirlestir',
        ]);
        $this->raporTarihi = now()->toDateString();
        $this->gecerlilikTarihiHesapla();
        unset($this->firma, $this->fineKinney);
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan (computed) veriler
    |--------------------------------------------------------------------------
    */

    /** @return array<int, string> kullanıcının aktif firmaları */
    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->get(['id', 'unvan', 'tehlike_sinifi'])
            ->mapWithKeys(fn (Firma $f) => [
                $f->id => $f->unvan.' ('.$f->tehlikeSinifiEtiketi().')',
            ])
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<int, TehlikeKategorisi> */
    #[Computed]
    public function kategoriler(): Collection
    {
        return RiskKutuphanesi::kategoriler();
    }

    #[Computed]
    public function fineKinney(): bool
    {
        return $this->yontem === 'fine_kinney';
    }

    /** @return array<string, int> düzey → madde sayısı */
    #[Computed]
    public function duzeyDagilimi(): array
    {
        $dagilim = [];

        foreach ($this->secilenler as $m) {
            $sonuc = $this->maddePuani($m);
            $duzey = $sonuc['puan'] ? $sonuc['duzey'] : 'Puansız';
            $dagilim[$duzey] = ($dagilim[$duzey] ?? 0) + 1;
        }

        return $dagilim;
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 1 — Firma & tarih
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma);
        $this->gecerlilikTarihiHesapla();
    }

    public function updatedRaporTarihi(): void
    {
        $this->gecerlilikTarihiHesapla();
    }

    public function updatedYontem(): void
    {
        unset($this->fineKinney);
    }

    private function gecerlilikTarihiHesapla(): void
    {
        if (! $this->raporTarihi) {
            return;
        }

        $yil = $this->firma?->riskGecerlilikYili() ?? 4;
        $this->gecerlilikTarihi = Carbon::parse($this->raporTarihi)->addYears($yil)->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 2 — Yöntem
    |--------------------------------------------------------------------------
    */

    public function yontemSec(string $anahtar): void
    {
        if (! (self::YONTEMLER[$anahtar]['hazir'] ?? false)) {
            Notification::make()
                ->title('Bu yöntem yakında')
                ->body('Şu an yalnız "Manuel Seçim" kullanılabilir.')
                ->warning()->send();

            return;
        }

        $this->yontemSecim = $anahtar;
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 3 — Risk ekleme (manuel / kütüphane)
    |--------------------------------------------------------------------------
    */

    public function kategoriToggle(int $id): void
    {
        $this->acikKategoriler = in_array($id, $this->acikKategoriler, true)
            ? array_values(array_diff($this->acikKategoriler, [$id]))
            : [...$this->acikKategoriler, $id];
    }

    public function tehlikeEkle(int $tehlikeId): void
    {
        if ($this->tehlikeSecili($tehlikeId)) {
            return;
        }

        $t = Tehlike::find($tehlikeId);

        if ($t) {
            $this->secilenler[] = RiskKutuphanesi::maddeyeCevir($t);
        }
    }

    public function kategoriTumunuEkle(int $kategoriId): void
    {
        foreach (Tehlike::where('tehlike_kategorisi_id', $kategoriId)->get() as $t) {
            if (! $this->tehlikeSecili($t->id)) {
                $this->secilenler[] = RiskKutuphanesi::maddeyeCevir($t);
            }
        }
    }

    public function tehlikeSecili(int $tehlikeId): bool
    {
        return collect($this->secilenler)->contains('tehlike_id', $tehlikeId);
    }

    public function maddeCikar(string $anahtar): void
    {
        $this->secilenler = array_values(array_filter(
            $this->secilenler,
            fn ($m) => $m['anahtar'] !== $anahtar,
        ));
    }

    public function bosMaddeEkle(): void
    {
        $this->secilenler[] = RiskKutuphanesi::bosMadde();

        if ($this->adim < 5) {
            $this->adim = 5;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 3 — "Yapay Zeka" (kural tabanlı) alt akışı
    |--------------------------------------------------------------------------
    */

    public function aiBaslat(): void
    {
        $this->aiAsama = 'sektor';
    }

    public function aiSektorSec(string $anahtar): void
    {
        $this->aiSektor = $anahtar;
    }

    public function aiSektorOnayla(): void
    {
        if ($this->aiSektor) {
            $this->aiAsama = 'altkategori';
        }
    }

    public function aiAltKategoriToggle(int $index): void
    {
        $etiket = config('isg.risk_ai.sektorler.'.$this->aiSektor.'.alt_kategoriler.'.$index);

        if ($etiket === null) {
            return;
        }

        $this->aiAltKategoriler = in_array($etiket, $this->aiAltKategoriler, true)
            ? array_values(array_diff($this->aiAltKategoriler, [$etiket]))
            : [...$this->aiAltKategoriler, $etiket];
    }

    public function aiAltKategoriOnayla(): void
    {
        $this->aiAsama = 'sohbet';
        $this->aiGecici = [];
    }

    /** Blade için: sırada sorulacak soru (yoksa null → aday üretimine geçilir). */
    public function aiSiradakiSoru(): ?array
    {
        return RiskUretici::siradakiSoru(
            (string) $this->aiSektor,
            array_keys($this->aiCevaplar),
            $this->aiAtlananlar,
        );
    }

    public function aiCevapla(string $anahtar, string $deger, bool $coklu): void
    {
        if (! $coklu) {
            $this->aiCevaplar[$anahtar] = $deger;
            $this->aiGecici = [];
            $this->aiSohbetIlerlet();

            return;
        }

        $this->aiGecici = in_array($deger, $this->aiGecici, true)
            ? array_values(array_diff($this->aiGecici, [$deger]))
            : [...$this->aiGecici, $deger];
    }

    public function aiCokluGonder(string $anahtar): void
    {
        $this->aiCevaplar[$anahtar] = $this->aiGecici;
        $this->aiGecici = [];
        $this->aiSohbetIlerlet();
    }

    public function aiSoruAtla(string $anahtar): void
    {
        $this->aiAtlananlar[] = $anahtar;
        $this->aiGecici = [];
        $this->aiSohbetIlerlet();
    }

    public function aiOncekiSoru(): void
    {
        // Son cevaplanan / atlanan soruyu geri al
        if (! empty($this->aiCevaplar)) {
            array_pop($this->aiCevaplar);
        } elseif (! empty($this->aiAtlananlar)) {
            array_pop($this->aiAtlananlar);
        }

        $this->aiGecici = [];

        if ($this->aiAsama === 'sonuc') {
            $this->aiAsama = 'sohbet';
        }
    }

    private function aiSohbetIlerlet(): void
    {
        if ($this->aiSiradakiSoru() === null) {
            $this->aiAdaylariUret();
        }
    }

    public function aiAdaylariUret(): void
    {
        $this->aiAdaylar = RiskUretici::uret(
            (string) $this->aiSektor,
            $this->aiAltKategoriler,
            $this->aiCevaplar,
        );

        // Varsayılan: puanı önerilmiş (cevap kaynaklı) adaylar seçili gelir
        $this->aiSecilenAdaylar = collect($this->aiAdaylar)
            ->filter(fn ($a) => $a['kaynak'] === 'ai')
            ->pluck('anahtar')
            ->all();

        $this->aiAsama = 'sonuc';
    }

    public function aiAdayToggle(string $anahtar): void
    {
        $this->aiSecilenAdaylar = in_array($anahtar, $this->aiSecilenAdaylar, true)
            ? array_values(array_diff($this->aiSecilenAdaylar, [$anahtar]))
            : [...$this->aiSecilenAdaylar, $anahtar];
    }

    public function aiTumAdaylar(bool $sec): void
    {
        $this->aiSecilenAdaylar = $sec
            ? collect($this->aiAdaylar)->pluck('anahtar')->all()
            : [];
    }

    public function aiAdaylariEkle(): void
    {
        $eklenecek = collect($this->aiAdaylar)
            ->whereIn('anahtar', $this->aiSecilenAdaylar);

        foreach ($eklenecek as $aday) {
            $zaten = collect($this->secilenler)->contains(
                fn ($m) => Str::lower(trim($m['tehlike'] ?? '')) === Str::lower(trim($aday['tehlike'] ?? '')),
            );

            if (! $zaten) {
                $this->secilenler[] = $aday;
            }
        }

        if (count($this->secilenler) > 0) {
            $this->adim = 4;
        }
    }

    public function aiSektorEtiketi(): ?string
    {
        return $this->aiSektor
            ? config('isg.risk_ai.sektorler.'.$this->aiSektor.'.ad')
            : null;
    }

    /** Sohbet başlığı için: cevaplardan tetiklenen aday risk sayısı (kaba). */
    public function aiTetiklenenSayisi(): int
    {
        $n = 0;

        foreach (config('isg.risk_ai.sorular', []) as $soru) {
            $secim = (array) ($this->aiCevaplar[$soru['anahtar']] ?? []);

            foreach ($soru['secenekler'] as $s) {
                if (in_array($s['deger'], $secim, true)) {
                    $n += count($s['riskler'] ?? []);
                }
            }
        }

        return $n;
    }

    /** Cevaplanan soru sayısı / sektöre uygun toplam. */
    public function aiIlerleme(): string
    {
        $cevaplanan = count($this->aiCevaplar) + count($this->aiAtlananlar);

        return $cevaplanan.' / '.RiskUretici::soruSayisi((string) $this->aiSektor);
    }

    /** AI'da seçilen sektör için kayıtlı şablonlar (kısayol). */
    public function aiSektorSablonlari()
    {
        if (! $this->aiSektor) {
            return collect();
        }

        return RiskSablonu::query()
            ->gorunur(Filament::auth()->id())
            ->where('sektor', $this->aiSektor)
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 3 — "Şablonlar & Paylaşılanlar" (sektör bazlı)
    |--------------------------------------------------------------------------
    */

    /** @return Collection<string, Collection> sektör etiketi => şablonlar */
    public function sablonlar()
    {
        return RiskSablonu::query()
            ->gorunur(Filament::auth()->id())
            ->orderByDesc('kullanim_sayisi')
            ->latest()
            ->get()
            ->groupBy(fn (RiskSablonu $s) => $s->sektorEtiketi());
    }

    /**
     * Bu sayıdan çok maddeli şablonlar (ör. 1.671 maddelik sektörel "master" analiz)
     * etkileşimli sihirbaz adımına yüklenmez — binlerce Livewire form input'u tarayıcıyı
     * kilitler, her istek payload sınırını aşar. Onun yerine doğrudan Risk Değerlendirmesi
     * oluşturulup sayfalı düzenleme tablosuna yönlendirilir.
     */
    private const SABLON_DOGRUDAN_ESIGI = 250;

    public function sablonUygula(int $id)
    {
        $sablon = RiskSablonu::gorunur(Filament::auth()->id())->find($id);

        if (! $sablon) {
            return null;
        }

        $maddeler = $sablon->maddeleriKopyala();

        if (count($maddeler) > self::SABLON_DOGRUDAN_ESIGI) {
            return $this->buyukSablonuDogrudanUygula($sablon, $maddeler);
        }

        foreach ($maddeler as $madde) {
            $kimlik = static::maddeKimligi($madde);
            $zaten = collect($this->secilenler)->contains(fn ($m) => static::maddeKimligi($m) === $kimlik);

            if (! $zaten) {
                $this->secilenler[] = $madde;
            }
        }

        $this->yontem = $sablon->yontem;
        $sablon->kullanildi();

        Notification::make()
            ->title($sablon->ad.' uygulandı')
            ->body(count($this->secilenler).' madde')
            ->success()->send();

        if (count($this->secilenler) > 0) {
            $this->adim = 4;
        }

        return null;
    }

    /**
     * Çok maddeli şablonu doğrudan yeni bir Risk Değerlendirmesine yazar (sihirbaza
     * yüklemeden) ve düzenleme sayfasına yönlendirir. Puan/düzey satır bazında
     * `RiskSkorlama` ile hesaplanıp toplu insert edilir (model `saving` hook'u
     * tek tek çalışmasın diye).
     */
    private function buyukSablonuDogrudanUygula(RiskSablonu $sablon, array $maddeler)
    {
        if (! $this->firmaId) {
            Notification::make()
                ->title('Önce 1. adımdan bir firma seçin')
                ->body(count($maddeler).' maddelik şablon, seçilen firmaya doğrudan bir risk değerlendirmesi olarak uygulanacak.')
                ->warning()->send();
            $this->adim = 1;

            return null;
        }

        $firma = Firma::where('user_id', Filament::auth()->id())->find($this->firmaId);

        if (! $firma) {
            return null;
        }

        @set_time_limit(300);

        $rd = new RiskDegerlendirmesi([
            'firma_id' => $firma->id,
            'yontem' => $sablon->yontem,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => $this->gecerlilikTarihi,
            'durum' => 'taslak',
        ]);
        $rd->save();

        $fk = $sablon->yontem === 'fine_kinney';
        $sira = 0;

        foreach (array_chunk($maddeler, 250) as $parca) {
            $satirlar = [];

            foreach ($parca as $m) {
                $sira++;
                $o = $this->sayiVeyaNull($m['olasilik'] ?? null);
                $s = $this->sayiVeyaNull($m['siddet'] ?? null);
                $f = $fk ? $this->sayiVeyaNull($m['frekans'] ?? null) : null;
                $mevcut = RiskSkorlama::hesapla($sablon->yontem, $o, $s, $f);

                $so = $this->sayiVeyaNull($m['son_olasilik'] ?? null) ?? ($o !== null ? 1.0 : null);
                $ss = $this->sayiVeyaNull($m['son_siddet'] ?? null) ?? $s;
                $sf = $fk ? ($this->sayiVeyaNull($m['son_frekans'] ?? null) ?? $f) : null;
                $son = RiskSkorlama::hesapla($sablon->yontem, $so, $ss, $sf);

                $satirlar[] = [
                    'risk_degerlendirmesi_id' => $rd->id,
                    'sira' => $sira,
                    'bolum' => $m['bolum'] ?? null,
                    'faaliyet' => $m['faaliyet'] ?? null,
                    'tehlike' => ($m['tehlike'] ?? '') ?: '(tanımsız)',
                    'risk' => $m['risk'] ?? null,
                    'mevcut_onlem' => $m['mevcut_onlem'] ?? null,
                    'etkilenen_calisan' => true,
                    'etkilenen_diger' => $this->etkilenenDiger,
                    'olasilik' => $o,
                    'frekans' => $f,
                    'siddet' => $s,
                    'puan' => $mevcut['puan'] ?: null,
                    'duzey' => $mevcut['puan'] ? $mevcut['duzey'] : null,
                    'oneri' => $m['oneri'] ?? null,
                    'sorumlu' => $m['sorumlu'] ?? null,
                    'termin' => ($m['termin'] ?? '') ?: $this->varsayilanTermin,
                    'son_olasilik' => $so,
                    'son_frekans' => $sf,
                    'son_siddet' => $ss,
                    'son_puan' => $son['puan'] ?: null,
                    'son_duzey' => $son['puan'] ? $son['duzey'] : null,
                    'durum' => 'acik',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            RiskMaddesi::insert($satirlar);
        }

        $sablon->kullanildi();

        Notification::make()
            ->title($sablon->ad.' uygulandı')
            ->body($sira.' madde ile yeni risk değerlendirmesi oluşturuldu.')
            ->success()->send();

        return $this->redirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd]));
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 3 — "Risk Değerlendirmenizden Yükleyin" alt akışı
    |--------------------------------------------------------------------------
    */

    public function excelIceAktar(): void
    {
        $this->validate(['excelDosya' => 'required|file|mimes:xlsx,xls,csv']);

        try {
            $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($this->excelDosya->getRealPath());
        } catch (\Throwable $e) {
            Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->excelAdaylar = $sonuc['adaylar'];
        $this->excelHatalar = $sonuc['hatalar'];
        // Kullanıcının kendi belgesindeki maddeler zaten kendi onayından geçmiş
        // kabul edilir (kütüphane/AI önerilerinin aksine) — hepsi seçili gelir.
        $this->excelSecilenAdaylar = collect($this->excelAdaylar)->pluck('anahtar')->all();
        $this->excelTekrarBirlestir = false;
        $this->excelTekrarSayisi = count($this->excelAdaylar) - collect($this->excelAdaylar)
            ->map(fn ($a) => static::maddeKimligi($a))
            ->unique()
            ->count();
        $this->excelDosya = null;

        if (! $this->excelAdaylar) {
            Notification::make()->title('Madde bulunamadı')
                ->body($this->excelHatalar[0] ?? 'Dosyada tanınabilir bir risk tablosu bulunamadı.')
                ->danger()->send();
        }
    }

    public function excelAdayToggle(string $anahtar): void
    {
        $this->excelSecilenAdaylar = in_array($anahtar, $this->excelSecilenAdaylar, true)
            ? array_values(array_diff($this->excelSecilenAdaylar, [$anahtar]))
            : [...$this->excelSecilenAdaylar, $anahtar];
    }

    public function excelTumAdaylar(bool $sec): void
    {
        $this->excelSecilenAdaylar = $sec ? collect($this->excelAdaylar)->pluck('anahtar')->all() : [];
    }

    public function excelSecilenleriEkle()
    {
        // Büyük dosya + AI tamamlama toplamı 120 sn'yi aşabilir; sayfa çökmesin.
        @set_time_limit(300);

        $secilenAdaylar = collect($this->excelAdaylar)
            ->filter(fn ($a) => in_array($a['anahtar'], $this->excelSecilenAdaylar, true));

        // Kullanıcı "tekrarları birleştir" dediyse aynı bölüm+faaliyet+tehlike+risk
        // olan satırları tek maddeye indir. Varsayılan: hepsini ekle.
        if ($this->excelTekrarBirlestir) {
            $secilenAdaylar = $secilenAdaylar->unique(fn ($a) => static::maddeKimligi($a));
        }

        $secilenAdaylar = $secilenAdaylar->values()->all();

        // 400+ madde → sihirbaza yükleme, doğrudan risk değerlendirmesi oluştur.
        if (count($secilenAdaylar) > self::EXCEL_DOGRUDAN_ESIGI) {
            return $this->excelDosyayiDogrudanUygula($secilenAdaylar);
        }

        // Kullanıcının kendi belgesindeki her satır eklenir — "aynı tehlike"
        // farklı bölüm/faaliyet/önlemle tekrar edebilir; birebir tekrarları da
        // kullanıcı bilinçli tutmuş olabilir (isterse "birleştir" ile indirir).
        $eklenen = 0;
        $eklenenler = [];
        $eklenenIndeksler = [];

        foreach ($secilenAdaylar as $aday) {
            $this->secilenler[] = $aday;
            $eklenenIndeksler[] = array_key_last($this->secilenler);
            $eklenenler[] = $aday;
            $eklenen++;
        }

        $this->excelAdaylar = [];
        $this->excelSecilenAdaylar = [];
        $this->excelTekrarSayisi = 0;
        $this->excelTekrarBirlestir = false;

        // Excel'deki O/Ş(/F) puanları, ekrandaki açılır listede yalnızca SEÇİLİ
        // puanlama yönteminin ölçek noktalarıyla (örn. 5x5 için 1-5) eşleşirse
        // görünür. Kullanıcının dosyası Fine-Kinney ölçeğinde (0.2/0.5/…/40 gibi
        // veya Frekans sütunu dolu) geldiyse yöntemi otomatik ona çevirmezsek
        // puanlar "kayboldu" gibi görünür — kullanıcı elle girmek zorunda kalır.
        // NOT: bu kontrol AI tamamlamadan ÖNCE yapılır — aksi halde AI, henüz
        // eski (yanlış) yönteme göre puan üretip ölçek dışı kalabilir.
        if ($eklenenler && $this->yontem !== 'fine_kinney' && RiskSkorlama::fineKinneyeUyuyorMu($eklenenler)) {
            $this->yontem = 'fine_kinney';
            Notification::make()
                ->title('Puanlama yöntemi Fine-Kinney\'e çevrildi')
                ->body('Excel dosyanızdaki olasılık/şiddet değerleri Fine-Kinney ölçeğine uyuyor, 5x5 Matris\'te görünmüyorlardı.')
                ->warning()->send();
        }

        [$aiPuan, $aiOnlem] = $this->excelEksikleriAiIleTamamla($eklenenIndeksler);

        $mesaj = $eklenen.' risk maddesi eklendi';

        if ($aiPuan || $aiOnlem) {
            $ekler = collect([
                $aiPuan ? "{$aiPuan} puan" : null,
                $aiOnlem ? "{$aiOnlem} önlem" : null,
            ])->filter()->implode(' + ');
            $mesaj .= " ({$ekler} AI ile tamamlandı)";
        }

        Notification::make()->title($mesaj)->success()->send();

        if (count($this->secilenler) > 0) {
            $this->adim = 4;
        }

        return null;
    }

    /** Bir maddenin kimliği — tekrar tespitinde kullanılır (bölüm+faaliyet+tehlike+risk). */
    private static function maddeKimligi(array $m): string
    {
        return Str::lower(trim(
            ($m['bolum'] ?? '').'|'.($m['faaliyet'] ?? '').'|'.($m['tehlike'] ?? '').'|'.($m['risk'] ?? '')
        ));
    }

    /**
     * 400+ maddeli Excel dosyasını doğrudan yeni bir Risk Değerlendirmesine yazar
     * (sihirbaza yüklemeden) ve düzenleme sayfasına yönlendirir. İyileştirme
     * sonrası puanlar Excel'de doluysa alınır, boşsa model varsayımı (son_olasilik=1)
     * devreye girer.
     *
     * @param  array<int, array<string, mixed>>  $maddeler
     */
    private function excelDosyayiDogrudanUygula(array $maddeler)
    {
        if (! $this->firmaId) {
            Notification::make()
                ->title('Önce 1. adımdan bir firma seçin')
                ->body(count($maddeler).' maddelik dosya, seçilen firmaya doğrudan bir risk değerlendirmesi olarak uygulanacak.')
                ->warning()->send();
            $this->adim = 1;

            return null;
        }

        $firma = Firma::where('user_id', Filament::auth()->id())->find($this->firmaId);

        if (! $firma) {
            return null;
        }

        @set_time_limit(300);

        // Excel puanları Fine-Kinney ölçeğindeyse yöntemi ona çevir (5x5'te "kayıp" görünmesin).
        $yontem = ($this->yontem !== 'fine_kinney' && RiskSkorlama::fineKinneyeUyuyorMu($maddeler))
            ? 'fine_kinney'
            : $this->yontem;

        $rd = new RiskDegerlendirmesi([
            'firma_id' => $firma->id,
            'yontem' => $yontem,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => $this->gecerlilikTarihi,
            'durum' => 'taslak',
        ]);
        $rd->save();

        $fk = $yontem === 'fine_kinney';
        $sira = 0;

        foreach (array_chunk($maddeler, 250) as $parca) {
            $satirlar = [];

            foreach ($parca as $m) {
                $sira++;
                $o = $this->sayiVeyaNull($m['olasilik'] ?? null);
                $s = $this->sayiVeyaNull($m['siddet'] ?? null);
                $f = $fk ? $this->sayiVeyaNull($m['frekans'] ?? null) : null;
                $mevcut = RiskSkorlama::hesapla($yontem, $o, $s, $f);

                $so = $this->sayiVeyaNull($m['son_olasilik'] ?? null) ?? ($o !== null ? 1.0 : null);
                $ss = $this->sayiVeyaNull($m['son_siddet'] ?? null) ?? $s;
                $sf = $fk ? ($this->sayiVeyaNull($m['son_frekans'] ?? null) ?? $f) : null;
                $son = RiskSkorlama::hesapla($yontem, $so, $ss, $sf);

                $satirlar[] = [
                    'risk_degerlendirmesi_id' => $rd->id,
                    'sira' => $sira,
                    'bolum' => $m['bolum'] ?? null,
                    'faaliyet' => $m['faaliyet'] ?? null,
                    'tehlike' => ($m['tehlike'] ?? '') ?: '(tanımsız)',
                    'risk' => $m['risk'] ?? null,
                    'mevcut_onlem' => $m['mevcut_onlem'] ?? null,
                    'etkilenen_calisan' => true,
                    'etkilenen_diger' => $this->etkilenenDiger,
                    'olasilik' => $o,
                    'frekans' => $f,
                    'siddet' => $s,
                    'puan' => $mevcut['puan'] ?: null,
                    'duzey' => $mevcut['puan'] ? $mevcut['duzey'] : null,
                    'oneri' => $m['oneri'] ?? null,
                    'sorumlu' => $m['sorumlu'] ?? null,
                    'termin' => ($m['termin'] ?? '') ?: $this->varsayilanTermin,
                    'aciklama' => $m['aciklama'] ?? null,
                    'son_olasilik' => $so,
                    'son_frekans' => $sf,
                    'son_siddet' => $ss,
                    'son_puan' => $son['puan'] ?: null,
                    'son_duzey' => $son['puan'] ? $son['duzey'] : null,
                    'durum' => 'acik',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            RiskMaddesi::insert($satirlar);
        }

        Notification::make()
            ->title($sira.' madde ile risk değerlendirmesi oluşturuldu')
            ->body('Dosya '.self::EXCEL_DOGRUDAN_ESIGI.' maddeyi aştığı için sihirbaza yüklenmeden doğrudan uygulandı; düzenleme sayfasına yönlendiriliyorsunuz.')
            ->success()->send();

        return $this->redirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd]));
    }

    /**
     * Excel'den eklenen maddelerden Olasılık/Şiddet(/Frekans) veya Mevcut
     * Önlem metni boş kalanları AI ile tamamlar — kullanıcı isteği: "risk
     * değerlendirmesine yükleyeceğim tablolarda puanlama ve önlemler bölümü
     * boşsa yapay zeka doldursun". AI kapalıysa (API anahtarı yok) dokunmadan
     * bırakır, eski davranış (elle giriş) aynen sürer.
     *
     * @param  array<int, int>  $indeksler  $this->secilenler içindeki ilgili öğelerin indeksleri
     * @return array{0: int, 1: int} [AI ile puanlanan sayısı, AI ile önlem verilen sayısı]
     */
    private function excelEksikleriAiIleTamamla(array $indeksler): array
    {
        if (! GeminiRiskPuanTamamlayici::aktifMi()) {
            return [0, 0];
        }

        GeminiRiskPuanTamamlayici::devreyiSifirla();

        $puanlanan = 0;
        $onlemli = 0;
        $fk = $this->fineKinney();
        $islenen = 0;

        foreach ($indeksler as $i) {
            // Gemini kota/hız sınırına takıldıysa ya da tek seferde çok fazla madde
            // varsa döngüyü kes — 120 sn'yi aşıp sayfa çökmemeli.
            if ($islenen >= self::AI_TOPLU_LIMIT || GeminiRiskPuanTamamlayici::devreKesikMi()) {
                break;
            }

            $m = $this->secilenler[$i];

            $puanEksik = blank($m['olasilik'] ?? null) || blank($m['siddet'] ?? null) || ($fk && blank($m['frekans'] ?? null));

            // "Mevcut önlem" AI'ya YALNIZCA hem mevcut önlem hem de öneri/alınacak
            // tedbir sütunu boşsa sorulur. Kullanıcının kendi tablosunda genelde
            // "Alınması Gereken Önlem" (oneri) dolu, "Mevcut Önlem" sütunu yoktur —
            // bu satırlar zaten kontrollü sayılır, AI ile 40 istek atıp sayfayı
            // kilitlemenin (ve kullanıcının belgesini "düzenlemenin") anlamı yok.
            $onlemEksik = blank($this->secilenler[$i]['mevcut_onlem'] ?? null) && blank($m['oneri'] ?? null);

            if ($puanEksik || $onlemEksik) {
                $islenen++;
            }

            if ($puanEksik) {
                $oneri = GeminiRiskPuanTamamlayici::oner(
                    $m['tehlike'] ?? '', $m['risk'] ?? null, $m['bolum'] ?? null, $m['faaliyet'] ?? null, $this->yontem,
                );

                if ($oneri) {
                    $this->secilenler[$i]['olasilik'] = $oneri['olasilik'];
                    $this->secilenler[$i]['siddet'] = $oneri['siddet'];

                    if ($fk) {
                        $this->secilenler[$i]['frekans'] = $oneri['frekans'];
                    }

                    $puanlanan++;
                }
            }

            if ($onlemEksik) {
                $onlem = GeminiRiskPuanTamamlayici::onlemOner(
                    $m['tehlike'] ?? '', $m['risk'] ?? null, $m['bolum'] ?? null, $m['faaliyet'] ?? null,
                );

                if ($onlem) {
                    $this->secilenler[$i]['mevcut_onlem'] = $onlem;
                    $onlemli++;
                }
            }
        }

        return [$puanlanan, $onlemli];
    }

    /** AI akışında "bu sektörün şablonunu direkt kullan". */
    public function aiSablonKullan(int $id)
    {
        return $this->sablonUygula($id);
    }

    public function sablonlaKaydet(): void
    {
        if (count($this->secilenler) === 0) {
            Notification::make()->title('Kaydedilecek madde yok')->danger()->send();

            return;
        }

        $sektor = $this->sablonSektor ?: $this->aiSektor;
        $sektorGecerli = $sektor && array_key_exists($sektor, config('isg.risk_ai.sektorler', []));

        RiskSablonu::olustur(
            Filament::auth()->user(),
            $this->sablonAd ?: (($this->firma?->unvan ?? 'Şablon').' — '.now()->format('d.m.Y')),
            $sektorGecerli ? $sektor : null,
            $sektorGecerli ? null : $sektor,
            $this->yontem,
            $this->secilenler,
        );

        $this->reset('sablonAd', 'sablonSektor');

        Notification::make()
            ->title('Sektör şablonu kaydedildi')
            ->body('Aynı sektörden yeni firmada "Şablonlar" yönteminden uygulayabilirsiniz.')
            ->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Adım navigasyonu
    |--------------------------------------------------------------------------
    */

    public function ileri(): void
    {
        if (! $this->adimGecerli($this->adim)) {
            return;
        }

        $this->adim = min(6, $this->adim + 1);
    }

    public function geri(): void
    {
        $this->adim = max(1, $this->adim - 1);
    }

    public function adimaGit(int $hedef): void
    {
        if ($hedef <= $this->adim) {
            $this->adim = max(1, $hedef);

            return;
        }

        for ($a = $this->adim; $a < $hedef; $a++) {
            if (! $this->adimGecerli($a)) {
                return;
            }
        }

        $this->adim = $hedef;
    }

    public function adimGecerli(int $adim): bool
    {
        return match ($adim) {
            1 => filled($this->firmaId) && filled($this->raporTarihi),
            2 => filled($this->yontemSecim),
            3 => count($this->secilenler) > 0,
            4 => true,
            5 => $this->tumMaddelerPuanli(),
            default => true,
        };
    }

    public function tumMaddelerPuanli(): bool
    {
        if (count($this->secilenler) === 0) {
            return false;
        }

        foreach ($this->secilenler as $m) {
            if (blank($m['olasilik'] ?? null) || blank($m['siddet'] ?? null)) {
                return false;
            }

            if ($this->fineKinney && blank($m['frekans'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /** Blade için: bir maddenin canlı puan/düzeyi. */
    public function maddePuani(array $madde): array
    {
        return RiskSkorlama::hesapla(
            $this->yontem,
            $this->sayiVeyaNull($madde['olasilik'] ?? null),
            $this->sayiVeyaNull($madde['siddet'] ?? null),
            $this->sayiVeyaNull($madde['frekans'] ?? null),
        );
    }

    private function sayiVeyaNull($deger): ?float
    {
        return ($deger === null || $deger === '') ? null : (float) $deger;
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 6 — Kaydet
    |--------------------------------------------------------------------------
    */

    public function kaydet()
    {
        if (! $this->adimGecerli(1) || ! $this->tumMaddelerPuanli()) {
            Notification::make()->title('Eksik bilgi var')->danger()->send();

            return null;
        }

        $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($this->firmaId);

        $rd = new RiskDegerlendirmesi([
            'firma_id' => $firma->id,
            'yontem' => $this->yontem,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => $this->gecerlilikTarihi,
            'durum' => 'taslak',
        ]);
        $rd->save();

        foreach (array_values($this->secilenler) as $i => $m) {
            $rd->maddeler()->create([
                'sira' => $i + 1,
                'bolum' => $m['bolum'] ?? null,
                'faaliyet' => $m['faaliyet'] ?? null,
                'tehlike' => ($m['tehlike'] ?? '') ?: '(tanımsız)',
                'risk' => $m['risk'] ?? null,
                'mevcut_onlem' => $m['mevcut_onlem'] ?? null,
                'etkilenen_calisan' => true,
                'etkilenen_diger' => $this->etkilenenDiger,
                'olasilik' => $this->sayiVeyaNull($m['olasilik'] ?? null),
                'frekans' => $this->fineKinney ? $this->sayiVeyaNull($m['frekans'] ?? null) : null,
                'siddet' => $this->sayiVeyaNull($m['siddet'] ?? null),
                'oneri' => $m['oneri'] ?? null,
                'sorumlu' => $m['sorumlu'] ?? null,
                'termin' => ($m['termin'] ?? '') ?: $this->varsayilanTermin,
                'aciklama' => $m['aciklama'] ?? null,
                // İyileştirme sonrası: Excel'de doluysa al, boşsa model varsayımı (1 / şiddet) devreye girer.
                'son_olasilik' => $this->sayiVeyaNull($m['son_olasilik'] ?? null),
                'son_frekans' => $this->fineKinney ? $this->sayiVeyaNull($m['son_frekans'] ?? null) : null,
                'son_siddet' => $this->sayiVeyaNull($m['son_siddet'] ?? null),
                'durum' => 'acik',
            ]);
        }

        Notification::make()
            ->title('Risk değerlendirmesi kaydedildi')
            ->body($rd->belge_no.' · '.count($this->secilenler).' madde')
            ->success()->send();

        return redirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd]));
    }
}
