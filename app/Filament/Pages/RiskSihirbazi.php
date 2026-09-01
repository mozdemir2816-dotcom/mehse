<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskSablonu;
use App\Models\Tehlike;
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

    /** @return Collection<int, \App\Models\TehlikeKategorisi> */
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

    /** @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection> sektör etiketi => şablonlar */
    public function sablonlar()
    {
        return RiskSablonu::query()
            ->gorunur(Filament::auth()->id())
            ->orderByDesc('kullanim_sayisi')
            ->latest()
            ->get()
            ->groupBy(fn (RiskSablonu $s) => $s->sektorEtiketi());
    }

    public function sablonUygula(int $id): void
    {
        $sablon = RiskSablonu::gorunur(Filament::auth()->id())->find($id);

        if (! $sablon) {
            return;
        }

        foreach ($sablon->maddeleriKopyala() as $madde) {
            $zaten = collect($this->secilenler)->contains(
                fn ($m) => Str::lower(trim($m['tehlike'] ?? '')) === Str::lower(trim($madde['tehlike'] ?? '')),
            );

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

    public function excelSecilenleriEkle(): void
    {
        $eklenen = 0;

        foreach ($this->excelAdaylar as $aday) {
            if (! in_array($aday['anahtar'], $this->excelSecilenAdaylar, true)) {
                continue;
            }

            $zaten = collect($this->secilenler)->contains(
                fn ($m) => Str::lower(trim($m['tehlike'] ?? '')) === Str::lower(trim($aday['tehlike'] ?? '')),
            );

            if (! $zaten) {
                $this->secilenler[] = $aday;
                $eklenen++;
            }
        }

        $this->excelAdaylar = [];
        $this->excelSecilenAdaylar = [];

        Notification::make()->title($eklenen.' risk maddesi eklendi')->success()->send();

        if (count($this->secilenler) > 0) {
            $this->adim = 4;
        }
    }

    /** AI akışında "bu sektörün şablonunu direkt kullan". */
    public function aiSablonKullan(int $id): void
    {
        $this->sablonUygula($id);
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
