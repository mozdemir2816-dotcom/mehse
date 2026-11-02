<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Firma;
use App\Models\FirmaJsa;
use App\Models\IsgProfesyoneli;
use App\Models\JsaSablonu;
use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Models\RiskSablonu;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use App\Support\GeminiRiskPdfOkuyucu;
use App\Support\GeminiRiskPuanTamamlayici;
use App\Support\EkipmanRiskKutuphanesi;
use App\Support\KutuphaneEksikleri;
use App\Support\NaceGrubu;
use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use App\Support\RiskDegerlendirmesiOlusturucu;
use App\Support\RiskKutuphanesi;
use App\Support\RiskMaddeMukerrer;
use App\Support\RiskSkorlama;
use App\Support\RiskUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
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
    use \App\Filament\Concerns\AileAnaSayfasi;

    use \App\Filament\Concerns\SinirliErisim;

    use WithFileUploads;

    protected string $view = 'filament.pages.risk-sihirbazi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'risk-degerlendirme';

    protected static ?string $title = 'Risk Değerlendirme';

    protected static ?string $navigationLabel = 'Risk Yönetim Merkezi';

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
        'hizli' => ['ad' => 'Hızlı Kurulum: Genel + Ekipman', 'onerilen' => false, 'hazir' => true,
            'aciklama' => 'Önce her işyerinde ortak genel riskleri tek tıkla ekleyin, sonra tesisteki ekipmanları işaretleyip risklerini toplu aktarın.',
            'maddeler' => ['Tek tıkla ortak genel riskler', 'Çoklu ekipman / proses seçimi', 'Yönteme (5x5 / Fine-Kinney) göre puanlanır']],
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
        'pdf' => ['ad' => 'PDF Yükleyin (AI ile Oku)', 'onerilen' => false, 'hazir' => true,
            'aciklama' => 'Taranmış/görsel PDF risk analizinizi yükleyin; Gemini tabloyu okuyup maddeleri çıkarır.',
            'maddeler' => ['Taranmış/görsel tabloları okur', 'Excel ile aynı inceleme ekranı', 'Gemini API anahtarı gerekir']],
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

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** Bu değerlendirmeyi hazırlayan İGU — boşsa firmanın atanmış İGU'su kullanılır. */
    public ?int $iguId = null;

    public ?string $raporTarihi = null;

    public ?string $gecerlilikTarihi = null;

    /** Analiz yöntemi (Fine-Kinney / 5x5 / HAZOP). Adım 1'de seçilir; kütüphane ve şablonlar buna göre süzülür. */
    public string $yontem = '';

    public ?string $yontemSecim = null;

    /** @var array<int, int> açık accordion kategori id'leri */
    public array $acikKategoriler = [];

    /** Hızlı Kurulum: işaretlenen ekipman anahtarları (config isg.risk_hizli_kurulum.ekipmanlar). */
    public array $hizliEkipmanSecim = [];

    /** "Kütüphanede Olmayan Ekipman (Yapay Zekâ)" penceresi — App\Support\GeminiEkipmanRiskUretici. */
    public string $aiEkipmanAdi = '';

    /** @var array<int, array<string, mixed>> üretilen, henüz eklenmemiş maddeler */
    public array $aiEkipmanAdaylar = [];

    /** @var array<int, int> seçili aday indeksleri */
    public array $aiEkipmanSecim = [];

    public bool $aiKutuphaneyeKaydet = true;

    public ?string $aiEkipmanHata = null;

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

    /**
     * "PDF Yükleyin" — Gemini ile okunur, sonucu excelAdaylar/excelSecilenAdaylar'a
     * yazılır (bkz. pdfIceAktar). Ayrı bir inceleme ekranı yok, Excel'inkini paylaşır.
     */
    public ?UploadedFile $pdfDosya = null;

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
            'aiAsama', 'aiSektor', 'aiSektorOtomatik', 'aiOrtakEklenen', 'aiOzelSonuc', 'hizliEkipmanSecim', 'aiEkipmanAdi', 'aiAltKategoriler', 'aiCevaplar', 'aiAtlananlar',
            'aiGecici', 'aiAdaylar', 'aiSecilenAdaylar', 'sablonAd', 'sablonSektor',
            'excelDosya', 'excelAdaylar', 'excelSecilenAdaylar', 'excelHatalar',
            'excelTekrarSayisi', 'excelTekrarBirlestir', 'pdfDosya', 'jsaOnerildiKategoriler',
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
    /**
     * Seçili firmanın NACE kodu / ekipmanları için kütüphanede olmayan kayıtlar (28.10.2026) —
     * sessizce atlanmasın, İSG uzmanı kütüphaneye eklesin.
     *
     * @return list<array{tur: string, ad: string, mesaj: string}>
     */
    #[Computed]
    public function kutuphaneEksikleri(): array
    {
        return $this->firma ? KutuphaneEksikleri::firma($this->firma, $this->yontem) : [];
    }

    #[Computed]
    public function kategoriler(): Collection
    {
        return RiskKutuphanesi::kategoriler($this->yontem, NaceGrubu::bul($this->firma?->nace_kodu));
    }

    /**
     * Yöntem değişince önceki yöntemin maddeleri listede kalmasın (karışıklığı önler).
     */
    public function updatedYontem(): void
    {
        unset($this->fineKinney);

        if ($this->secilenler !== []) {
            $this->secilenler = [];

            Notification::make()->title('Yöntem değişti')
                ->body('Önceki yönteme ait seçilen maddeler temizlendi; yeni yönteme göre yeniden ekleyin.')
                ->warning()->send();
        }
    }

    #[Computed]
    public function fineKinney(): bool
    {
        return RiskSkorlama::ucEksenliMi($this->yontem);
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
        // Firma değişince, elle seçilmediyse firmanın atanmış İGU'su öneri olarak gelir.
        $this->iguId = $this->firma?->igu_id;
    }

    /** @return array<int, string> */
    #[Computed]
    public function iguSecenekleri(): array
    {
        return IsgProfesyoneli::query()
            ->where('user_id', Filament::auth()->id())->where('tip', 'igu')
            ->pluck('ad_soyad', 'id')->all();
    }

    public function updatedRaporTarihi(): void
    {
        $this->gecerlilikTarihiHesapla();
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
            $this->secilenler[] = RiskKutuphanesi::maddeyeCevir($t, $this->yontem);
            $this->jsaOneriKontrolEt($t->tehlike_kategorisi_id);
        }
    }

    public function kategoriTumunuEkle(int $kategoriId): void
    {
        foreach (RiskKutuphanesi::grupTehlikeleri($kategoriId, $this->yontem) as $t) {
            if (! $this->tehlikeSecili($t->id)) {
                $this->secilenler[] = RiskKutuphanesi::maddeyeCevir($t, $this->yontem);
            }
        }
        $this->jsaOneriKontrolEt($kategoriId);
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 3 — Hızlı Kurulum (genel riskler + ekipman ızgarası)
    |--------------------------------------------------------------------------
    | Kütüphane klasörleri config isg.risk_hizli_kurulum'daki anahtarlardan bulunur;
    | maddeler mevcut manuel akıştaki gibi RiskKutuphanesi::maddeyeCevir ile seçili
    | yönteme (5x5 / Fine-Kinney) çevrilerek eklenir, zaten ekli olan madde tekrar eklenmez.
    */

    /**
     * @return array{genel: array{kategoriler: array<int, int>, maddeler: Collection}, ekipmanlar: array<string, array{ad: string, ikon: string, kategoriler: array<int, int>, adet: int, eklenen: int}>}
     */
    #[Computed]
    public function hizliGruplar(): array
    {
        $ayar = config('isg.risk_hizli_kurulum', []);
        $tum = collect($ayar['genel'] ?? [])
            ->merge(collect($ayar['ekipmanlar'] ?? [])->pluck('kategoriler')->flatten())
            ->unique()->values();
        $idler = TehlikeKategorisi::whereIn('anahtar', $tum)->pluck('id', 'anahtar');
        $secili = array_flip(array_filter(array_column($this->secilenler, 'tehlike_id')));

        $maddeler = fn (array $anahtarlar): Collection => collect($anahtarlar)
            ->map(fn ($k) => $idler[$k] ?? null)->filter()
            ->flatMap(fn ($id) => RiskKutuphanesi::grupTehlikeleri((int) $id, $this->yontem));

        $genelKat = collect($ayar['genel'] ?? [])->map(fn ($k) => $idler[$k] ?? null)->filter()->values()->all();

        $ekipmanlar = [];
        foreach ($ayar['ekipmanlar'] ?? [] as $anahtar => $e) {
            $liste = $maddeler($e['kategoriler']);
            $ekipmanlar[$anahtar] = [
                'ad' => $e['ad'],
                'ikon' => $e['ikon'],
                'kategoriler' => collect($e['kategoriler'])->map(fn ($k) => $idler[$k] ?? null)->filter()->values()->all(),
                'adet' => $liste->count(),
                'eklenen' => $liste->filter(fn ($t) => isset($secili[$t->id]))->count(),
            ];
        }

        return [
            'genel' => ['kategoriler' => $genelKat, 'maddeler' => $maddeler($ayar['genel'] ?? [])],
            'ekipmanlar' => $ekipmanlar + $this->digerEkipmanKutulari($tum->all(), $secili),
        ];
    }

    /**
     * Manuel yöntemde listelenen diğer tüm ekipman klasörleri (ekipman_* / atölye makineleri) — config'teki
     * kutularda ve genel risklerde OLMAYANLAR, ayrı kutu olarak (kullanıcı 01.11.2026: aynı ekipman iki yerden
     * eklenip mükerrer olmasın). Manuel listeyle aynı süzgeç: yöntem (FK klasörleri 5x5'e çevrilir) + NACE grubu.
     * Anahtar "kat_{kategori anahtarı}", 'diger' => true (görünümde ayrı, aramalı bölüm).
     *
     * @param  array<int, string>  $haric  config'te kullanılan kategori anahtarları
     * @param  array<int, int>  $secili  tehlike_id => sıra (secilenler)
     * @return array<string, array<string, mixed>>
     */
    private function digerEkipmanKutulari(array $haric, array $secili): array
    {
        $nace = NaceGrubu::bul($this->firma?->nace_kodu);
        $ayar = config('isg.risk_hizli_kurulum.diger_ekipman', []);

        $kategoriler = TehlikeKategorisi::query()
            ->where(fn ($q) => collect($ayar['onekler'] ?? ['ekipman_'])->each(fn ($o) => $q->orWhere('anahtar', 'like', $o.'%')))
            ->whereNotIn('anahtar', [...$haric, ...($ayar['haric'] ?? [])])
            ->where(fn ($q) => $q->whereNull('yontem')->orWhere('yontem', $this->yontem)
                ->when($this->yontem === 'matris_5x5', fn ($w) => $w->orWhere('yontem', 'fine_kinney')))
            ->where(fn ($q) => $q->whereNull('nace_gruplari')->orWhereJsonContains('nace_gruplari', 'ortak')
                ->when($nace, fn ($w) => $w->orWhereJsonContains('nace_gruplari', $nace)))
            ->withCount('tehlikeler')
            ->get()
            ->where('tehlikeler_count', '>', 0);

        $ekliSayisi = $secili
            ? Tehlike::whereIn('id', array_keys($secili))->selectRaw('tehlike_kategorisi_id, count(*) n')->groupBy('tehlike_kategorisi_id')->pluck('n', 'tehlike_kategorisi_id')
            : collect();

        return $kategoriler
            ->mapWithKeys(fn (TehlikeKategorisi $k) => ['kat_'.$k->anahtar => [
                'ad' => Str::of($k->ad)->replaceMatches('/^(Ekipman|Atölye)\s*[–-]\s*/u', '')->ucfirst()->toString(),
                'ikon' => 'heroicon-o-wrench-screwdriver',
                'kategoriler' => [$k->id],
                'adet' => $k->tehlikeler_count,
                'eklenen' => (int) ($ekliSayisi[$k->id] ?? 0),
                'diger' => true,
            ]])
            ->sortBy('ad', SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
    }

    /** Adım 1 — ortak genel riskleri (genel işyeri, acil durum, eğitim, elektrik) tek tıkla ekler. */
    public function genelRiskleriAktar(): void
    {
        $once = count($this->secilenler);
        foreach ($this->hizliGruplar['genel']['kategoriler'] as $kategoriId) {
            $this->kategoriTumunuEkle($kategoriId);
        }
        unset($this->hizliGruplar);

        $eklenen = count($this->secilenler) - $once;
        Notification::make()
            ->title($eklenen ? $eklenen.' genel risk eklendi' : 'Genel riskler zaten ekli')
            ->body('Toplam '.count($this->secilenler).' madde. Şimdi tesisteki ekipmanları seçebilirsiniz.')
            ->success()->send();
    }

    public function hizliEkipmanToggle(string $anahtar): void
    {
        $this->hizliEkipmanSecim = in_array($anahtar, $this->hizliEkipmanSecim, true)
            ? array_values(array_diff($this->hizliEkipmanSecim, [$anahtar]))
            : [...$this->hizliEkipmanSecim, $anahtar];
    }

    /** Adım 2 — işaretlenen tüm ekipmanların kütüphane maddelerini toplu ekler. */
    public function ekipmanRiskleriAktar(): void
    {
        $gruplar = $this->hizliGruplar['ekipmanlar'];
        $secim = array_values(array_filter($this->hizliEkipmanSecim, fn ($k) => ($gruplar[$k]['adet'] ?? 0) > 0));

        if (! $secim) {
            Notification::make()->title('Önce en az bir ekipman işaretleyin')->warning()->send();

            return;
        }

        $once = count($this->secilenler);
        foreach ($secim as $anahtar) {
            foreach ($gruplar[$anahtar]['kategoriler'] as $kategoriId) {
                $this->kategoriTumunuEkle($kategoriId);
            }
        }
        unset($this->hizliGruplar);
        $this->hizliEkipmanSecim = [];

        $eklenen = count($this->secilenler) - $once;
        Notification::make()
            ->title($eklenen ? $eklenen.' ekipman riski eklendi' : 'Seçilen ekipmanların riskleri zaten ekli')
            ->body(collect($secim)->map(fn ($k) => $gruplar[$k]['ad'])->implode(', ').' · toplam '.count($this->secilenler).' madde')
            ->success()->send();
    }

    /*
    | Kütüphanede olmayan ekipman / proses — yapay zekâ ile madde üretimi
    */

    /** Genel kütüphane tüm kullanıcılarda ortak: yalnız sahip hesap yapay zekâ maddelerini oraya kaydedebilir. */
    public function aiKutuphaneyeKaydedebilir(): bool
    {
        return (bool) Filament::auth()->user()?->sahipMi();
    }

    public function aiEkipmanUret(): void
    {
        $this->validate(['aiEkipmanAdi' => 'required|string|min:3|max:120'], [
            'aiEkipmanAdi.required' => 'Ekipman veya proses adını yazın.',
            'aiEkipmanAdi.min' => 'Ekipman adını biraz daha açık yazın.',
        ]);

        $sonuc = \App\Support\GeminiEkipmanRiskUretici::uret(trim($this->aiEkipmanAdi), (string) $this->yontem);
        $this->aiEkipmanAdaylar = $sonuc['maddeler'];
        $this->aiEkipmanSecim = array_keys($sonuc['maddeler']);
        $this->aiEkipmanHata = $sonuc['hata'];
    }

    public function aiEkipmanAdayToggle(int $i): void
    {
        $this->aiEkipmanSecim = in_array($i, $this->aiEkipmanSecim, true)
            ? array_values(array_diff($this->aiEkipmanSecim, [$i]))
            : [...$this->aiEkipmanSecim, $i];
    }

    /** Seçilen yapay zekâ maddelerini analize ekler; istenirse (yalnız sahip) genel kütüphaneye de kaydeder. */
    public function aiEkipmanEkle(): void
    {
        $secilen = collect($this->aiEkipmanSecim)->map(fn ($i) => $this->aiEkipmanAdaylar[$i] ?? null)->filter()->values();

        if ($secilen->isEmpty()) {
            Notification::make()->title('Eklenecek madde seçin')->warning()->send();

            return;
        }

        $kaydet = $this->aiKutuphaneyeKaydet && $this->aiKutuphaneyeKaydedebilir();
        $kategori = null;

        if ($kaydet) {
            $ad = Str::limit(trim($this->aiEkipmanAdi), 80, '');
            $kategori = TehlikeKategorisi::firstOrCreate(
                ['anahtar' => 'ekipman_ai_'.Str::slug($ad, '_')],
                ['ad' => 'Ekipman – '.$ad.' (Özel Ekipman)', 'yontem' => null, 'sira' => 600],
            );
        }

        $eklenen = 0;
        foreach ($secilen as $m) {
            $tehlike = null;
            if ($kategori) {
                $tehlike = Tehlike::firstOrCreate(
                    ['tehlike_kategorisi_id' => $kategori->id, 'tehlike' => $m['tehlike']],
                    [
                        'kod' => 'AI-'.strtoupper(Str::random(6)),
                        'bolum' => $m['bolum'], 'faaliyet' => $m['faaliyet'], 'risk' => $m['risk'],
                        'mevcut_onlem' => $m['mevcut_onlem'], 'oneri' => $m['oneri'], 'mevzuat' => $m['mevzuat'],
                        'olasilik' => $m['olasilik'], 'frekans' => $m['frekans'], 'siddet' => $m['siddet'],
                        // Önlem sonrası (rezidüel) hedef puanlar da kütüphaneye yazılır.
                        'son_olasilik' => $m['son_olasilik'], 'son_frekans' => $m['son_frekans'], 'son_siddet' => $m['son_siddet'],
                        'matris' => $m['matris'],
                    ],
                );
                if ($this->tehlikeSecili($tehlike->id)) {
                    continue;
                }
            }

            $madde = $tehlike
                ? RiskKutuphanesi::maddeyeCevir($tehlike, $this->yontem)
                : array_merge(RiskKutuphanesi::bosMadde(), \Illuminate\Support\Arr::except($m, ['matris']), [
                    'anahtar' => 'ai-'.substr(md5(uniqid('', true)), 0, 8),
                    'kaynak' => 'ai',
                ]);

            if (! $tehlike && $this->yontem === 'matris_5x5') {
                $madde['olasilik'] = $m['matris']['olasilik'];
                $madde['siddet'] = $m['matris']['siddet'];
                $madde['son_olasilik'] = $m['matris']['son_olasilik'];
                $madde['son_siddet'] = $m['matris']['son_siddet'];
                $madde['frekans'] = $madde['son_frekans'] = null;
            }

            $this->secilenler[] = $madde;
            $eklenen++;
        }

        unset($this->hizliGruplar);
        $this->aiEkipmanAdaylar = [];
        $this->aiEkipmanSecim = [];
        $this->aiEkipmanAdi = '';
        $this->dispatch('close-modal', id: 'ai-ekipman-risk');

        Notification::make()
            ->title($kaydet
                ? 'Maddeler risk tablosuna eklendi ve Genel Kütüphaneye kaydedildi'
                : 'Maddeler risk tablosuna eklendi')
            ->body($eklenen.' madde eklendi · toplam '.count($this->secilenler).' madde. Puanları Adım 5’te kontrol edin.')
            ->success()->send();
    }

    /**
     * Önizleme (Adım 6): risk değerlendirme ekibi — firmanın atanmış İSG profesyonelleri ve atama
     * yazılarından otomatik (App\Support\RiskEkibiOtomatik). Kayıttan sonra düzenleme sayfasında değiştirilebilir.
     *
     * @return array<string, array<int, array{ad_soyad: string, tc: ?string, gorev: ?string, bas_uye: bool, ekip_gorevi: string}>>
     */
    #[Computed]
    public function ekipOnizleme(): array
    {
        return $this->firma ? \App\Support\RiskEkibiOtomatik::uyeler($this->firma) : [];
    }

    /** Bir madde eklenen kategori için bu oturumda zaten önerilmiş olanlar (tekrar bildirim gelmesin). */
    public array $jsaOnerildiKategoriler = [];

    /**
     * Manuel seçimde eklenen maddenin kategorisi, kütüphanedeki bir JSA'nın kapsadığı iş ile
     * eşleşiyorsa (JsaSablonu::tehlike_kategorileri) ve bu JSA firmaya henüz atanmamışsa,
     * "işe özgü risk değerlendirmesi de hazırlanmalı" uyarısı verir — kullanıcı kararı 23.10.2026.
     */
    private function jsaOneriKontrolEt(?int $kategoriId): void
    {
        if (! $kategoriId || ! $this->firmaId) {
            return;
        }

        $kategori = TehlikeKategorisi::find($kategoriId);
        if (! $kategori || in_array($kategori->anahtar, $this->jsaOnerildiKategoriler, true)) {
            return;
        }

        $atanmisSablonIdler = FirmaJsa::where('firma_id', $this->firmaId)->pluck('jsa_sablonu_id');

        $sablon = JsaSablonu::query()
            ->sahip((int) Filament::auth()->id())
            ->whereNotNull('tehlike_kategorileri')
            ->whereNotIn('id', $atanmisSablonIdler)
            ->get()
            ->first(fn (JsaSablonu $s) => in_array($kategori->anahtar, $s->tehlike_kategorileri ?? [], true));

        $this->jsaOnerildiKategoriler[] = $kategori->anahtar;

        if (! $sablon) {
            return;
        }

        Notification::make()
            ->title('İşe özgü risk değerlendirmesi (JSA) mevcut')
            ->body('"'.$kategori->ad.'" için kütüphanede "'.$sablon->baslik.'" adlı JSA var. Firmaya ekleyip imzalatmak isterseniz JSA sayfasından "Firmalara Ekle" kullanın.')
            ->info()
            ->actions([
                Action::make('jsaGit')->label('JSA Kütüphanesine Git')->url(JsaDegerlendirmesi::getUrl(['firma' => $this->firmaId]))->button(),
            ])
            ->send();
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

    /**
     * NACE bölümü (ilk iki hane) → yapay zekâ akışının sektörü (config isg.risk_ai.sektorler).
     * Eşleşme yoksa null — kullanıcıya sektör sorulur.
     */
    public static function naceAiSektoru(?string $nace): ?string
    {
        if (! preg_match('/^\s*(\d{2})/', (string) $nace, $e)) {
            return null;
        }
        $b = (int) $e[1];

        return match (true) {
            $b >= 1 && $b <= 3 => 'tarim',
            $b >= 5 && $b <= 9 => 'maden',
            in_array($b, [10, 11, 56], true) => 'gida',
            $b >= 12 && $b <= 33 => 'fabrika',
            $b >= 35 && $b <= 39, $b === 45, $b === 81, $b === 95 => 'servis',
            $b >= 41 && $b <= 43 => 'insaat',
            in_array($b, [46, 52], true) => 'depo',
            in_array($b, [49, 50, 51, 53], true) => 'ulasim',
            in_array($b, [55, 90, 91, 92, 93], true) => 'eglence',
            $b === 85 => 'egitim',
            $b >= 86 && $b <= 88 => 'saglik',
            $b === 47, $b >= 58 && $b <= 84, $b === 94, $b === 96 => 'ofis',
            default => null,
        };
    }

    /** Firma NACE'sinden tespit edilen sektör (02.11.2026): varsa sektör / alt kategori sorulmadan ekipman adımına geçilir. */
    public bool $aiSektorOtomatik = false;

    /** Akış başında soru sorulmadan eklenen zorunlu ortak risk sayısı (bilgi şeridi). */
    public int $aiOrtakEklenen = 0;

    /** Ekipman adımındaki "Listede olmayan ekipman" sonucunun açıklaması (kütüphaneden mi, AI'dan mı). */
    public ?string $aiOzelSonuc = null;

    /**
     * Akış (02.11.2026): zorunlu ortak riskler soru sorulmadan eklenir → sektör (NACE'den otomatik ya da
     * seçim) → İşyerinde Kullanılan Ekipman ve Prosesler → yalnız sektöre / NACE'ye özgü sorular → adaylar.
     */
    public function aiBaslat(): void
    {
        $this->aiZorunluOrtakEkle();
        $sektor = static::naceAiSektoru($this->firma?->nace_kodu);

        if ($sektor && config('isg.risk_ai.sektorler.'.$sektor)) {
            $this->aiSektor = $sektor;
            $this->aiAltKategoriler = [];
            $this->aiSektorOtomatik = true;
            $this->aiAsama = 'ekipman';
            $this->aiGecici = [];

            return;
        }

        $this->aiAsama = 'sektor';
    }

    /** Yangın, acil durum, ilk yardım, genel bina, aydınlatma, elektrik… — her işyerinde ortak maddeler. */
    private function aiZorunluOrtakEkle(): void
    {
        $mevcut = collect($this->secilenler)->map(fn ($m) => Str::lower(trim($m['tehlike'] ?? '')))->flip();
        $eklenen = 0;

        foreach (RiskUretici::zorunluOrtakMaddeler($this->yontem) as $madde) {
            if (! isset($mevcut[Str::lower(trim($madde['tehlike']))])) {
                $this->secilenler[] = array_merge(RiskKutuphanesi::bosMadde(), $madde);
                $eklenen++;
            }
        }

        $this->aiOrtakEklenen += $eklenen;
    }

    /**
     * Soru filtresi için NACE grubu: yalnız sektör firmanın NACE'sinden belirlendiyse. Sektör elle
     * seçildiyse null → sorular sektöre göre süzülür.
     */
    public function aiSoruNaceGrubu(): ?string
    {
        return $this->aiSektorOtomatik ? NaceGrubu::bul($this->firma?->nace_kodu) : null;
    }

    /** Ekipman önerileri için NACE grubu: firmanın NACE'si, yoksa seçilen sektörün karşılığı. */
    private function aiEkipmanNaceGrubu(): ?string
    {
        return NaceGrubu::bul($this->firma?->nace_kodu)
            ?? config('isg.risk_ai.sektor_nace_grubu.'.$this->aiSektor);
    }

    /**
     * Ekipman adımı kutuları: NACE grubuna göre öne çıkanlar + kütüphanedeki diğer ekipman klasörleri.
     * Kutu yapısı Hızlı Kurulum'la aynı (partials.hizli-ekipman-kutu); seçim $hizliEkipmanSecim'de tutulur.
     *
     * @return array{one_cikan: array<string, array<string, mixed>>, diger: array<string, array<string, mixed>>}
     */
    #[Computed]
    public function aiEkipmanKutulari(): array
    {
        $grup = $this->aiEkipmanNaceGrubu();
        $ayar = config('isg.risk_ai.nace_ekipmanlari', []);
        $tanimlar = $ayar[$grup] ?? $ayar['varsayilan'] ?? [];
        $hizli = config('isg.risk_hizli_kurulum.ekipmanlar', []);

        $kutular = [];
        foreach ($tanimlar as $anahtar => $tanim) {
            if (is_int($anahtar)) {
                [$anahtar, $tanim] = [$tanim, $hizli[$tanim] ?? null];
            }
            if ($tanim) {
                $kutular['oz_'.$anahtar] = $tanim;
            }
        }

        $kategoriAnahtarlari = collect($kutular)->pluck('kategoriler')->flatten()->unique()->all();
        $idler = TehlikeKategorisi::whereIn('anahtar', $kategoriAnahtarlari)->pluck('id', 'anahtar');
        $secili = array_flip(array_filter(array_column($this->secilenler, 'tehlike_id')));

        $oneCikan = [];
        foreach ($kutular as $anahtar => $e) {
            $katIdler = collect($e['kategoriler'])->map(fn ($k) => $idler[$k] ?? null)->filter()->values()->all();
            $liste = collect($katIdler)->flatMap(fn ($id) => RiskKutuphanesi::grupTehlikeleri((int) $id, $this->yontem));

            if ($liste->isEmpty()) {
                continue; // kütüphanede maddesi olmayan öneri gösterilmez
            }

            $oneCikan[$anahtar] = [
                'ad' => $e['ad'], 'ikon' => $e['ikon'], 'kategoriler' => $katIdler,
                'adet' => $liste->count(),
                'eklenen' => $liste->filter(fn ($t) => isset($secili[$t->id]))->count(),
            ];
        }

        $onerilenIdler = collect($oneCikan)->pluck('kategoriler')->flatten()->all();
        $diger = collect($this->digerEkipmanKutulari([], $secili))
            ->reject(fn ($e) => array_intersect($e['kategoriler'], $onerilenIdler))
            ->all();

        return ['one_cikan' => $oneCikan, 'diger' => $diger];
    }

    /** Ekipman adımı → işaretlenen ekipmanların kütüphane risklerini ekler ve sektör sorularına geçer. */
    public function aiEkipmanAdiminiTamamla(): void
    {
        $kutular = $this->aiEkipmanKutulari['one_cikan'] + $this->aiEkipmanKutulari['diger'];
        $secim = array_values(array_filter($this->hizliEkipmanSecim, fn ($k) => isset($kutular[$k])));

        if ($secim) {
            $once = count($this->secilenler);
            foreach ($secim as $anahtar) {
                foreach ($kutular[$anahtar]['kategoriler'] as $kategoriId) {
                    $this->kategoriTumunuEkle($kategoriId);
                }
            }

            Notification::make()
                ->title((count($this->secilenler) - $once).' ekipman riski kütüphaneden eklendi')
                ->body(collect($secim)->map(fn ($k) => $kutular[$k]['ad'])->implode(', '))
                ->success()->send();
        }

        $this->hizliEkipmanSecim = [];
        unset($this->aiEkipmanKutulari, $this->hizliGruplar);
        $this->aiAsama = 'sohbet';
        $this->aiGecici = [];

        if ($this->aiSiradakiSoru() === null) {
            $this->aiAdaylariUret();
        }
    }

    /**
     * "Listede Olmayan Ekipman / Özel İş Ekle" — önce kütüphane, yoksa yapay zekâ (02.11.2026).
     * a) Kütüphanede adı eşleşen ekipman klasörü varsa maddeleri (puanlarıyla) anında eklenir, API çağrılmaz.
     * b) Yoksa Gemini; firmanın NACE'si ve tehlike sınıfıyla puanlı maddeler üretilir, hepsi analize eklenir
     *    ve "Özel Ekipman" olarak kütüphaneye kaydedilir — bir dahaki sefere (a) ile gelir.
     */
    public function aiOzelEkipmanEkle(): void
    {
        $this->validate(['aiEkipmanAdi' => 'required|string|min:3|max:120'], [
            'aiEkipmanAdi.required' => 'Ekipman veya iş adını yazın.',
            'aiEkipmanAdi.min' => 'Ekipman adını biraz daha açık yazın.',
        ]);

        $ad = trim($this->aiEkipmanAdi);
        $bulunan = RiskKutuphanesi::ekipmanAra($ad, $this->yontem);

        if ($bulunan->isNotEmpty()) {
            $once = count($this->secilenler);
            foreach ($bulunan as $kategori) {
                $this->kategoriTumunuEkle($kategori->id);
            }
            $eklenen = count($this->secilenler) - $once;
            $adlar = $bulunan->map(fn ($k) => RiskKutuphanesi::sadeAd($k->ad))->implode(', ');

            $this->aiOzelSonuc = '📚 Kütüphanede bulundu: '.$adlar.' — '.$eklenen.' madde eklendi (yapay zekâ kullanılmadı).';
            $this->aiEkipmanAdi = '';
            unset($this->aiEkipmanKutulari, $this->hizliGruplar);

            Notification::make()
                ->title($eklenen ? $eklenen.' madde kütüphaneden eklendi' : 'Bu ekipmanın maddeleri zaten ekli')
                ->body($adlar.' · yapay zekâ kotası harcanmadı')
                ->success()->send();

            return;
        }

        $sonuc = \App\Support\GeminiEkipmanRiskUretici::uret($ad, (string) $this->yontem, $this->aiFirmaBaglami());

        if (! $sonuc['maddeler']) {
            $this->aiEkipmanHata = $sonuc['hata'];
            $this->aiOzelSonuc = null;
            Notification::make()->title('Kütüphanede yok, yapay zekâ da madde üretemedi')->body($sonuc['hata'])->warning()->send();

            return;
        }

        $this->aiEkipmanAdaylar = $sonuc['maddeler'];
        $this->aiEkipmanSecim = array_keys($sonuc['maddeler']);
        $this->aiEkipmanHata = null;
        $this->aiKutuphaneyeKaydet = true;
        $sayi = count($sonuc['maddeler']);

        $this->aiEkipmanEkle();

        $this->aiOzelSonuc = '✨ Kütüphanede bulunamadı — yapay zekâ "'.$ad.'" için '.$sayi.' madde üretti ve analize ekledi'
            .($this->aiKutuphaneyeKaydedebilir() ? '; "Özel Ekipman" olarak kütüphaneye kaydedildi.' : '.');
        unset($this->aiEkipmanKutulari);
    }

    /** Yapay zekâya gönderilen işyeri bağlamı: ünvan yok — yalnız NACE, faaliyet ve tehlike sınıfı. */
    private function aiFirmaBaglami(): ?string
    {
        $f = $this->firma;

        if (! $f) {
            return null;
        }

        return collect([
            $f->nace_kodu ? 'NACE '.$f->nace_kodu.($f->nace_aciklama ? ' ('.$f->nace_aciklama.')' : '') : null,
            $f->tehlike_sinifi ? 'Tehlike sınıfı: '.$f->tehlikeSinifiEtiketi() : null,
        ])->filter()->implode(' · ') ?: null;
    }

    /** Otomatik tespit edilen sektörü değiştirmek isteyen kullanıcı sektör seçimine döner. */
    public function aiSektorDegistir(): void
    {
        $this->aiSektorOtomatik = false;
        $this->aiCevaplar = [];
        $this->aiAtlananlar = [];
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
        $this->aiAsama = 'ekipman';
        $this->aiGecici = [];
    }

    /** Blade için: sırada sorulacak soru (yoksa null → aday üretimine geçilir). */
    public function aiSiradakiSoru(): ?array
    {
        return RiskUretici::siradakiSoru(
            (string) $this->aiSektor,
            array_keys($this->aiCevaplar),
            $this->aiAtlananlar,
            $this->aiSoruNaceGrubu(),
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
        // Zaten analizde olan (zorunlu ortak, ekipman) maddeler aday olarak tekrar gösterilmez.
        $ekliIdler = array_flip(array_filter(array_column($this->secilenler, 'tehlike_id')));
        $ekliMetinler = collect($this->secilenler)->map(fn ($m) => Str::lower(trim($m['tehlike'] ?? '')))->flip();

        $this->aiAdaylar = collect(RiskUretici::uret(
            (string) $this->aiSektor,
            $this->aiAltKategoriler,
            $this->aiCevaplar,
            $this->yontem ?: null,
        ))->reject(fn ($a) => isset($ekliIdler[$a['tehlike_id'] ?? 0]) || isset($ekliMetinler[Str::lower(trim($a['tehlike'] ?? ''))]))
            ->values()->all();

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

        return $cevaplanan.' / '.RiskUretici::soruSayisi((string) $this->aiSektor, $this->aiSoruNaceGrubu());
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

    /** @return Collection<string, Collection> sektör etiketi => şablonlar (seçili puanlama yöntemine göre) */
    public function sablonlar()
    {
        return RiskSablonu::query()
            ->gorunur(Filament::auth()->id())
            ->where('yontem', $this->yontem)
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

        $rd = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur(
            $firma, $sablon->yontem, $maddeler,
            $this->raporTarihi, $this->gecerlilikTarihi,
            $this->etkilenenDiger, $this->varsayilanTermin,
            iguId: $this->iguId,
        );

        $sablon->kullanildi();

        Notification::make()
            ->title($sablon->ad.' uygulandı')
            ->body($rd->maddeler()->count().' madde ile yeni risk değerlendirmesi oluşturuldu.')
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

        $yol = $this->excelDosya->getRealPath();

        // Yöntem adım 1'de seçilir ve Excel yüklemesi onu değiştirmez; dosya seçilen
        // yöntemin şablonuna uymuyorsa içe aktarma yapılmaz.
        $hazopDosyasi = EkipmanRiskKutuphanesi::hazopMi($yol);

        if ($hazopDosyasi !== ($this->yontem === 'hazop')) {
            Notification::make()->title('Yöntem uyuşmuyor')
                ->body($hazopDosyasi
                    ? 'Bu dosya HAZOP şablonu; seçili yöntem "'.config('isg.risk_yontemleri.'.$this->yontem).'". Yöntemi HAZOP yapmak için adım 1\'e dönün.'
                    : 'Bu dosya HAZOP şablonu değil; seçili yöntem HAZOP. Yöntemi değiştirmek için adım 1\'e dönün.')
                ->danger()->send();

            return;
        }

        if ($hazopDosyasi) {
            try {
                $adaylar = EkipmanRiskKutuphanesi::hazopAdaylari($yol, $this->excelDosya->getClientOriginalName());
            } catch (\Throwable $e) {
                Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                return;
            }

            $this->excelAdaylar = $adaylar;
            $this->excelHatalar = [];

            Notification::make()->title('HAZOP dosyası okundu')
                ->body('Maddeler puansız eklenecek.')
                ->success()->send();
        } else {
            try {
                $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);
            } catch (\Throwable $e) {
                Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                return;
            }

            $this->excelAdaylar = $sonuc['adaylar'];
            $this->excelHatalar = $sonuc['hatalar'];
        }

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

    /**
     * "PDF Yükleyin" — Gemini ile okunan maddeler AYNI excelAdaylar/excelSecilenAdaylar
     * durumuna yazılır; inceleme, seçim ve toplu ekleme akışının tamamı Excel ile
     * paylaşılır (bkz. GeminiRiskPdfOkuyucu::oku, "aday" şekli birebir aynı).
     */
    public function pdfIceAktar(): void
    {
        $this->validate(['pdfDosya' => 'required|file|mimes:pdf|max:15360']);

        $sonuc = GeminiRiskPdfOkuyucu::oku($this->pdfDosya->getRealPath());

        $this->excelAdaylar = $sonuc['adaylar'];
        $this->excelHatalar = $sonuc['hatalar'];
        $this->excelSecilenAdaylar = collect($this->excelAdaylar)->pluck('anahtar')->all();
        $this->excelTekrarBirlestir = false;
        $this->excelTekrarSayisi = count($this->excelAdaylar) - collect($this->excelAdaylar)
            ->map(fn ($a) => static::maddeKimligi($a))
            ->unique()
            ->count();
        $this->pdfDosya = null;

        if (! $this->excelAdaylar) {
            Notification::make()->title('Madde bulunamadı')
                ->body($this->excelHatalar[0] ?? 'PDF içinde tanınabilir bir risk tablosu bulunamadı.')
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
        // Yöntem otomatik değişmez; puanlar seçilen yönteme uymuyorsa yalnız uyarılır.
        if ($eklenenler && $this->yontem === 'matris_5x5' && RiskSkorlama::fineKinneyeUyuyorMu($eklenenler)) {
            Notification::make()
                ->title('Puanlar Fine-Kinney ölçeğinde')
                ->body('Excel\'deki olasılık/şiddet değerleri 5x5 Matris ölçeğine uymuyor. Yöntem değişmedi; gerekirse adım 1\'den Fine-Kinney seçin.')
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

        $rd = RiskDegerlendirmesiOlusturucu::maddelerdenOlustur(
            $firma, $this->yontem, $maddeler,
            $this->raporTarihi, $this->gecerlilikTarihi,
            $this->etkilenenDiger, $this->varsayilanTermin,
            iguId: $this->iguId,
        );

        Notification::make()
            ->title($rd->maddeler()->count().' madde ile risk değerlendirmesi oluşturuldu')
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
        // HAZOP puansızdır; AI'nin puan üretmesi istenmez.
        if ($this->yontem === 'hazop') {
            return [0, 0];
        }

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
            1 => filled($this->firmaId) && filled($this->raporTarihi) && filled($this->yontem),
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

        // HAZOP puansızdır: her madde yalnız açıklama metniyle yeterli.
        if ($this->yontem === 'hazop') {
            return true;
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

    /** Seçili maddeler arasındaki mükerrer (bölüm + faaliyet + tehlike + risk aynı) madde sayısı. */
    public function mukerrerSayisi(): int
    {
        return count(RiskMaddeMukerrer::tekrarlar($this->secilenler));
    }

    public function mukerrerleriSil(): void
    {
        $once = count($this->secilenler);
        $this->secilenler = RiskMaddeMukerrer::temizle($this->secilenler);

        Notification::make()
            ->title(($once - count($this->secilenler)).' mükerrer madde silindi')
            ->body(count($this->secilenler).' madde kaldı.')
            ->success()->send();
    }

    public function kaydet()
    {
        if (! $this->adimGecerli(1) || ! $this->tumMaddelerPuanli()) {
            Notification::make()->title('Eksik bilgi var')->danger()->send();

            return null;
        }

        $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($this->firmaId);

        $rd = new RiskDegerlendirmesi([
            'firma_id' => $firma->id,
            'igu_id' => $this->iguId,
            'yontem' => $this->yontem,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => $this->gecerlilikTarihi,
            'durum' => 'taslak',
        ]);
        $rd->save();

        foreach (array_values($this->secilenler) as $i => $m) {
            $rd->maddeler()->create([
                'sira' => $i + 1,
                'tehlike_id' => is_numeric($m['tehlike_id'] ?? null) ? (int) $m['tehlike_id'] : null,
                'bolum' => $m['bolum'] ?? null,
                'faaliyet' => $m['faaliyet'] ?? null,
                'tehlike' => ($m['tehlike'] ?? '') ?: '(tanımsız)',
                'risk' => $m['risk'] ?? null,
                'mevcut_onlem' => $m['mevcut_onlem'] ?? null,
                'hazop' => $m['hazop'] ?? null,
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
