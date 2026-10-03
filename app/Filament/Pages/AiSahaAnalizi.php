<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\ImzaSecenegi;
use App\Models\DofRaporu;
use App\Models\Firma;
use App\Models\SahaAnalizi;
use App\Support\GeminiSahaAnalizi;
use App\Support\SahaAnaliziUretici;
use App\Support\TurkceMetin;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Saha Gözlem Raporu — önce isgpratik AI SAHA ANALİZİ/1-6.jpg, 03.10.2026'dan
 * itibaren isgsuite "Saha Gözlem Raporu" akışı (Downloads/saha gözlem raporu):
 * taslak kayıt → uygunsuzluk ekle (Fotoğraftan AI / Açıklamadan AI / Manuel)
 * → madde başına Fine-Kinney + Onayla / İyileştir / Sil + DÖF bayrağı →
 * mevzuat referansları (QR) → Raporu Tamamla (kilitlenir, işaretli maddeler
 * için sorumlu/termin/hedef artık skorla DÖF açılır).
 */
class AiSahaAnalizi extends Page
{
    use SinirliErisim;
    use WithFileUploads;

    protected string $view = 'filament.pages.ai-saha-analizi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Kontrolleri';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'ai-saha-analizi';

    protected static ?string $title = 'Saha Gözlem Raporu';

    protected static ?string $navigationLabel = 'Saha Gözlem Raporu (AI)';

    /** Tek seferde analiz edilebilecek fotoğraf sayısı (isgsuite: "en fazla 10"). */
    public const MAX_FOTOGRAF = 10;

    /** Rapor fotoğraf havuzu üst sınırı. */
    public const MAX_HAVUZ = 40;

    public ?int $kayitId = null;

    public string $durum = SahaAnalizi::TASLAK;

    public ?int $firmaId = null;

    public ?string $alanBolge = null;

    public ?string $gozetimTarihAraligi = null;

    public ?string $raporTarihi = null;

    public ?string $gozetimYapan = null;

    public ?string $gozetimYapanSertifikaNo = null;

    public ?string $sorumluKisi = 'İşveren / İşveren Vekili';

    public ?string $isverenVekiliAdi = null;

    public ?string $baglamNotu = null;

    /** @var array<int, string> İncelenecek tehlikeler (config isg.saha_tehlike_kategorileri) */
    public array $odakKategoriler = [];

    /** Açık: seçilen kategoriler öncelikli ama diğer görünen tehlikeler de raporlanır. */
    public bool $tumunuTara = true;

    /** foto | aciklama | manuel */
    public string $eklemeYontemi = 'foto';

    public ?string $aciklamaMetni = null;

    /** @var array<int, UploadedFile> */
    public array $yeniFotograflar = [];

    /** @var array<int, array{yol: string, analiz_edildi: bool}> rapor fotoğraf havuzu */
    public array $fotograflar = [];

    /** @var array<int, string> analiz için seçilen havuz yolları */
    public array $seciliFotograflar = [];

    /** @var array<int, array<string, mixed>> */
    public array $bulgular = [];

    public ?int $iyilestirIndex = null;

    public ?string $iyilestirNotu = null;

    /** @var array<int, array{ad: string, url: ?string}> */
    public array $mevzuatReferanslari = [];

    public ?string $yeniRefAd = null;

    public ?string $yeniRefUrl = null;

    public bool $tamamlaPaneli = false;

    public string $topluSorumluTipi = 'kendim';

    public ?string $topluSorumlu = null;

    public ?string $topluTermin = null;

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }

        if ($raporId = request()->integer('rapor')) {
            $this->raporAc($raporId);
        }
    }

    /** Kategori seçimini AI bağlam notuna çevirir (Gemini istemine eklenir). */
    public function aiBaglami(): ?string
    {
        $odak = collect($this->odakKategoriler)
            ->intersect(config('isg.saha_tehlike_kategorileri'))
            ->values();

        if ($odak->isEmpty()) {
            return $this->baglamNotu;
        }

        $talimat = 'İncelenecek tehlike kategorileri: '.$odak->implode(', ').'. '
            .($this->tumunuTara
                ? 'Bu kategorilere öncelik ver; fotoğrafta açıkça görülen diğer tehlikeleri de raporla.'
                : 'Yalnız bu kategorilerdeki tehlikeleri raporla.');

        return trim(($this->baglamNotu ? $this->baglamNotu.'. ' : '').$talimat);
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
    public function kayit(): ?SahaAnalizi
    {
        return $this->kayitId ? $this->firma?->sahaAnalizleri()->find($this->kayitId) : null;
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiSahaAnalizi::aktifMi();
    }

    /** @return Collection<int, SahaAnalizi> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->sahaAnalizleri()->latest()->get() ?? collect();
    }

    /** @return array<string, array{ad: string, url: ?string}> hızlı ekleme — doğrulanmış resmî linkler */
    #[Computed]
    public function hazirReferanslar(): array
    {
        return collect(config('isg.uzman_raporu.dayanaklar', []))
            ->unique('ad')
            ->all();
    }

    public function kilitliMi(): bool
    {
        return $this->durum === SahaAnalizi::TAMAMLANDI;
    }

    public function kullanilanFotoSayisi(string $yol): int
    {
        return collect($this->bulgular)->where('foto_yolu', $yol)->count();
    }

    /** @return array<int, int> DÖF açılacak işaretli bulgu index'leri */
    public function dofIndexleri(): array
    {
        return collect($this->bulgular)->filter(fn ($b) => $b['dof_acilacak'] ?? false)->keys()->all();
    }

    public function dofHazirMi(int $i): bool
    {
        $b = $this->bulgular[$i] ?? [];

        return filled($b['dof_termin'] ?? null)
            && (($b['dof_sorumlu_tipi'] ?? 'kendim') === 'kendim' || filled($b['dof_sorumlu'] ?? null));
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->gecmisKayitlar, $this->kayit);

        // Başka firmaya geçince açık taslakla bağ kopar (yeni rapor olarak kaydedilir).
        $this->kayitId = null;
        $this->durum = SahaAnalizi::TASLAK;

        $this->gozetimYapan = $this->firma?->igu?->ad_soyad;
        $this->gozetimYapanSertifikaNo = $this->firma?->igu?->sertifika_no;
        $this->isverenVekiliAdi = $this->firma?->isveren_vekili ?: $this->firma?->isveren_ad;
    }

    /** Yüklenen fotoğraflar hemen havuza alınır ve analiz için seçilir. */
    public function updatedYeniFotograflar(): void
    {
        if ($this->kilitliMi() || ! $this->yeniFotograflar) {
            return;
        }

        $bos = static::MAX_HAVUZ - count($this->fotograflar);
        $dosyalar = array_slice($this->yeniFotograflar, 0, max(0, $bos));

        foreach ($dosyalar as $dosya) {
            $yol = $dosya->store('saha-analiz-foto', 'public');
            $this->fotograflar[] = ['yol' => $yol, 'analiz_edildi' => false];

            if (count($this->seciliFotograflar) < static::MAX_FOTOGRAF) {
                $this->seciliFotograflar[] = $yol;
            }
        }

        if (count($this->yeniFotograflar) > count($dosyalar)) {
            Notification::make()->title('Fotoğraf havuzu dolu')->body('Bir raporda en fazla '.static::MAX_HAVUZ.' fotoğraf olabilir.')->warning()->send();
        }

        $this->yeniFotograflar = [];
    }

    public function fotoSeciminiDegistir(string $yol): void
    {
        if (in_array($yol, $this->seciliFotograflar, true)) {
            $this->seciliFotograflar = array_values(array_diff($this->seciliFotograflar, [$yol]));

            return;
        }

        if (count($this->seciliFotograflar) >= static::MAX_FOTOGRAF) {
            Notification::make()->title('En fazla '.static::MAX_FOTOGRAF.' fotoğraf seçebilirsiniz')->warning()->send();

            return;
        }

        $this->seciliFotograflar[] = $yol;
    }

    public function seciminiBirak(): void
    {
        $this->seciliFotograflar = [];
    }

    public function fotografSil(string $yol): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        if ($this->kullanilanFotoSayisi($yol) > 0) {
            Notification::make()->title('Fotoğraf bir uygunsuzlukta kullanılıyor')->body('Önce o maddeyi silin.')->warning()->send();

            return;
        }

        $this->fotograflar = array_values(array_filter($this->fotograflar, fn ($f) => $f['yol'] !== $yol));
        $this->seciliFotograflar = array_values(array_diff($this->seciliFotograflar, [$yol]));
    }

    /*
    |--------------------------------------------------------------------------
    | Uygunsuzluk ekleme
    |--------------------------------------------------------------------------
    */

    /** Boş bulgu şablonu — tüm ekleme yollarının ortak alan seti. */
    private static function bosBulgu(array $deger = []): array
    {
        return array_merge([
            'foto_yolu' => null,
            'bina_bolge' => null,
            'kategori' => null,
            'tespit' => '',
            'oneriler_metni' => '',
            'yasal_gerekce' => null,
            'olasilik' => null,
            'frekans' => null,
            'siddet' => null,
            'risk_derecesi' => 3,
            'durum' => 'bekliyor',
            'kaynak' => 'manuel',
            'dof_acilacak' => false,
            'dof_sorumlu_tipi' => 'kendim',
            'dof_sorumlu' => null,
            'dof_termin' => null,
            'dof_hedef_skor' => null,
        ], $deger);
    }

    /** AI çıktısını (GeminiSahaAnalizi) bulgu alanlarına çevirir. */
    private static function aiBulgusu(array $b, string $kaynak, ?string $fotoYolu = null): array
    {
        return static::bosBulgu([
            'foto_yolu' => $fotoYolu ?? ($b['foto_yolu'] ?? null),
            'bina_bolge' => $b['bina_bolge'] ?? null,
            'kategori' => $b['kategori'] ?? null,
            'tespit' => $b['tespit'],
            'oneriler_metni' => implode("\n", $b['oneriler'] ?? []),
            'yasal_gerekce' => $b['yasal_gerekce'] ?? null,
            'olasilik' => $b['olasilik'] ?? null,
            'frekans' => $b['frekans'] ?? null,
            'siddet' => $b['siddet'] ?? null,
            'risk_derecesi' => (int) ($b['risk_derecesi'] ?? 3),
            'kaynak' => $kaynak,
        ]);
    }

    public function fotograflariAnalizEt(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        if ($this->yeniFotograflar) {
            $this->updatedYeniFotograflar();
        }

        // Seçim yoksa henüz analiz edilmemiş fotoğraflar alınır.
        $yollar = $this->seciliFotograflar ?: collect($this->fotograflar)->where('analiz_edildi', false)->pluck('yol')->all();

        if (! $yollar) {
            Notification::make()->title('Önce en az bir fotoğraf ekleyin ya da seçin')->danger()->send();

            return;
        }

        if (count($yollar) > static::MAX_FOTOGRAF) {
            Notification::make()->title('En fazla '.static::MAX_FOTOGRAF.' fotoğraf analiz edilebilir')->danger()->send();

            return;
        }

        if (! GeminiSahaAnalizi::aktifMi()) {
            Notification::make()->title('Yapay zeka kullanılamıyor')->body('Gemini API anahtarı tanımlı değil — "Manuel" ile ekleyebilirsiniz.')->warning()->send();

            return;
        }

        $bulunanlar = GeminiSahaAnalizi::analizEt($yollar, $this->aiBaglami());

        foreach ($bulunanlar as $b) {
            $this->bulgular[] = static::aiBulgusu($b, 'foto');
        }

        $this->fotograflar = array_map(
            fn ($f) => in_array($f['yol'], $yollar, true) ? ['yol' => $f['yol'], 'analiz_edildi' => true] : $f,
            $this->fotograflar,
        );
        $this->seciliFotograflar = [];

        if (! $bulunanlar) {
            Notification::make()->title('Uygunsuzluk tespit edilmedi')->body('Seçilen fotoğraflarda belirgin bir tehlike bulunamadı.')->info()->send();
        } else {
            $this->taslagiKaydet(sessiz: true);
            Notification::make()->title(count($bulunanlar).' uygunsuzluk tespit edildi')->body('Her maddeyi kontrol edip onaylayın.')->success()->send();
        }
    }

    public function aciklamadanEkle(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        if (mb_strlen(trim((string) $this->aciklamaMetni)) < 5) {
            Notification::make()->title('Kısa bir açıklama yazın')->danger()->send();

            return;
        }

        if (! GeminiSahaAnalizi::aktifMi()) {
            Notification::make()->title('Yapay zeka kullanılamıyor')->body('Gemini API anahtarı tanımlı değil — "Manuel" ile ekleyebilirsiniz.')->warning()->send();

            return;
        }

        $b = GeminiSahaAnalizi::aciklamadanUret($this->aciklamaMetni, $this->aiBaglami());

        if (! $b) {
            Notification::make()->title('Madde oluşturulamadı')->body('Yapay zekâ yanıt vermedi; tekrar deneyin ya da elle ekleyin.')->danger()->send();

            return;
        }

        $this->bulgular[] = static::aiBulgusu($b, 'aciklama', $this->seciliFotograflar[0] ?? null);
        $this->aciklamaMetni = null;
        $this->taslagiKaydet(sessiz: true);

        Notification::make()->title('Uygunsuzluk eklendi')->success()->send();
    }

    public function manuelEkle(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        $this->bulgular[] = static::bosBulgu(['foto_yolu' => $this->seciliFotograflar[0] ?? null]);
    }

    /*
    |--------------------------------------------------------------------------
    | Madde işlemleri
    |--------------------------------------------------------------------------
    */

    public function bulguOnayla(int $index): void
    {
        if ($this->kilitliMi() || ! isset($this->bulgular[$index])) {
            return;
        }

        if (mb_strlen(trim((string) ($this->bulgular[$index]['tespit'] ?? ''))) < 5) {
            Notification::make()->title('Önce uygunsuzluk açıklamasını yazın')->danger()->send();

            return;
        }

        $this->bulgular[$index]['durum'] = 'onaylandi';
    }

    public function bulguOnayiniKaldir(int $index): void
    {
        if (! $this->kilitliMi() && isset($this->bulgular[$index])) {
            $this->bulgular[$index]['durum'] = 'bekliyor';
        }
    }

    public function tumunuOnayla(): void
    {
        foreach (array_keys($this->bulgular) as $i) {
            if (mb_strlen(trim((string) ($this->bulgular[$i]['tespit'] ?? ''))) >= 5) {
                $this->bulguOnayla($i);
            }
        }
    }

    public function iyilestirAc(int $index): void
    {
        $this->iyilestirIndex = $index;
        $this->iyilestirNotu = null;
    }

    public function bulguIyilestir(): void
    {
        $i = $this->iyilestirIndex;

        if ($this->kilitliMi() || $i === null || ! isset($this->bulgular[$i])) {
            return;
        }

        if (! GeminiSahaAnalizi::aktifMi()) {
            Notification::make()->title('Yapay zeka kullanılamıyor')->warning()->send();

            return;
        }

        $yeni = GeminiSahaAnalizi::iyilestir($this->bulgular[$i], $this->iyilestirNotu);

        if (! $yeni) {
            Notification::make()->title('İyileştirilemedi')->body('Yapay zekâ yanıt vermedi; tekrar deneyin.')->danger()->send();

            return;
        }

        $eski = $this->bulgular[$i];
        $this->bulgular[$i] = array_merge(static::aiBulgusu($yeni, $eski['kaynak'] ?? 'manuel', $eski['foto_yolu'] ?? null), [
            'bina_bolge' => $eski['bina_bolge'] ?: ($yeni['bina_bolge'] ?? null),
            'dof_acilacak' => $eski['dof_acilacak'] ?? false,
        ]);
        $this->iyilestirIndex = null;
        $this->iyilestirNotu = null;

        Notification::make()->title('Madde yeniden yazıldı')->body('Kontrol edip onaylayın.')->success()->send();
    }

    public function bulguSil(int $index): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        unset($this->bulgular[$index]);
        $this->bulgular = array_values($this->bulgular);
        $this->iyilestirIndex = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Mevzuat referansları
    |--------------------------------------------------------------------------
    */

    public function referansEkle(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        $ad = trim((string) $this->yeniRefAd);
        $url = trim((string) $this->yeniRefUrl) ?: null;

        if ($ad === '') {
            Notification::make()->title('Mevzuat adını yazın')->danger()->send();

            return;
        }

        if ($url && ! filter_var($url, FILTER_VALIDATE_URL)) {
            Notification::make()->title('Bağlantı geçerli bir adres değil')->body('https:// ile başlayan tam adres girin.')->danger()->send();

            return;
        }

        $this->mevzuatReferanslari[] = ['ad' => $ad, 'url' => $url];
        $this->yeniRefAd = $this->yeniRefUrl = null;
    }

    public function hazirReferansEkle(string $anahtar): void
    {
        $r = $this->hazirReferanslar[$anahtar] ?? null;

        if ($this->kilitliMi() || ! $r || collect($this->mevzuatReferanslari)->contains('ad', $r['ad'])) {
            return;
        }

        $this->mevzuatReferanslari[] = ['ad' => $r['ad'], 'url' => $r['url'] ?? null];
    }

    /** Maddelerin "mevzuat referansı" alanlarını (bağlantısız) listeye toplar. */
    public function bulgulardanReferansTopla(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        $mevcut = collect($this->mevzuatReferanslari)->pluck('ad')->map(fn ($a) => TurkceMetin::buyuk($a));
        $eklenen = 0;

        foreach (collect($this->bulgular)->pluck('yasal_gerekce')->filter()->map(fn ($a) => trim($a))->unique() as $ad) {
            if (! $mevcut->contains(TurkceMetin::buyuk($ad))) {
                $this->mevzuatReferanslari[] = ['ad' => $ad, 'url' => null];
                $mevcut->push(TurkceMetin::buyuk($ad));
                $eklenen++;
            }
        }

        Notification::make()->title($eklenen ? "{$eklenen} referans eklendi" : 'Eklenecek yeni referans yok')->success()->send();
    }

    public function referansSil(int $index): void
    {
        if (! $this->kilitliMi()) {
            unset($this->mevzuatReferanslari[$index]);
            $this->mevzuatReferanslari = array_values($this->mevzuatReferanslari);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kayıt yaşam döngüsü
    |--------------------------------------------------------------------------
    */

    /** @return array<int, array<string, mixed>> PDF/DB'ye yazılan bulgu biçimi */
    private function kayitBulgulari(): array
    {
        return collect($this->bulgular)
            ->map(function ($b) {
                $skor = SahaAnalizi::fineKinneySkoru($b);

                return [
                    'foto_yolu' => $b['foto_yolu'] ?? null,
                    'bina_bolge' => $b['bina_bolge'] ?? null,
                    'kategori' => $b['kategori'] ?? null,
                    'tespit' => trim((string) ($b['tespit'] ?? '')),
                    'oneriler' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($b['oneriler_metni'] ?? ''))))),
                    'yasal_gerekce' => $b['yasal_gerekce'] ?? null,
                    'olasilik' => $b['olasilik'] ?? null,
                    'frekans' => $b['frekans'] ?? null,
                    'siddet' => $b['siddet'] ?? null,
                    'skor' => $skor,
                    'risk_derecesi' => SahaAnalizi::skordanDerece($skor, (int) ($b['risk_derecesi'] ?? 3)),
                    'durum' => $b['durum'] ?? 'bekliyor',
                    'kaynak' => $b['kaynak'] ?? 'manuel',
                    'dof_acilacak' => (bool) ($b['dof_acilacak'] ?? false),
                    'dof_sorumlu_tipi' => $b['dof_sorumlu_tipi'] ?? 'kendim',
                    'dof_sorumlu' => $b['dof_sorumlu'] ?? null,
                    'dof_termin' => $b['dof_termin'] ?? null,
                    'dof_hedef_skor' => filled($b['dof_hedef_skor'] ?? null) ? (float) $b['dof_hedef_skor'] : null,
                ];
            })
            ->values()
            ->all();
    }

    public function taslagiKaydet(bool $sessiz = false): ?SahaAnalizi
    {
        if ($this->kilitliMi()) {
            return $this->kayit;
        }

        if (! $this->firma) {
            if (! $sessiz) {
                Notification::make()->title('Önce firma seçin')->danger()->send();
            }

            return null;
        }

        $s = $this->kayit ?? new SahaAnalizi(['firma_id' => $this->firma->id, 'durum' => SahaAnalizi::TASLAK]);
        $s->fill([
            'alan_bolge' => $this->alanBolge,
            'gozetim_tarih_araligi' => $this->gozetimTarihAraligi,
            'rapor_tarihi' => $this->raporTarihi,
            'gozetim_yapan' => $this->gozetimYapan,
            'gozetim_yapan_sertifika_no' => $this->gozetimYapanSertifikaNo,
            'gozetim_yapan_kase' => $this->firma->igu?->kase_gorseli,
            'sorumlu_kisi' => $this->sorumluKisi,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'baglam_notu' => $this->baglamNotu,
            'bulgular' => $this->kayitBulgulari(),
            'fotograflar' => array_values($this->fotograflar),
            'mevzuat_referanslari' => array_values($this->mevzuatReferanslari),
        ]);
        $s->save();

        $this->kayitId = $s->id;
        unset($this->kayit, $this->gecmisKayitlar);

        if (! $sessiz) {
            Notification::make()->title('Taslak kaydedildi')->body($s->belge_no)->success()->send();
        }

        return $s;
    }

    public function raporAc(int $id): void
    {
        $s = SahaAnalizi::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($id);

        if (! $s) {
            return;
        }

        $this->firmaId = $s->firma_id;
        unset($this->firma, $this->gecmisKayitlar, $this->kayit);

        $this->kayitId = $s->id;
        $this->durum = $s->durum ?: SahaAnalizi::TASLAK;
        $this->alanBolge = $s->alan_bolge;
        $this->gozetimTarihAraligi = $s->gozetim_tarih_araligi;
        $this->raporTarihi = $s->rapor_tarihi?->toDateString();
        $this->gozetimYapan = $s->gozetim_yapan;
        $this->gozetimYapanSertifikaNo = $s->gozetim_yapan_sertifika_no;
        $this->sorumluKisi = $s->sorumlu_kisi;
        $this->isverenVekiliAdi = $s->isveren_vekili_adi;
        $this->baglamNotu = $s->baglam_notu;
        $this->mevzuatReferanslari = $s->mevzuat_referanslari ?? [];
        $this->seciliFotograflar = [];
        $this->iyilestirIndex = null;
        $this->tamamlaPaneli = false;

        // Eski kayıtlarda durum yoktu; "Rapor Oluştur" ile kesinleşmiş sayılır.
        $this->bulgular = collect($s->bulgular ?? [])->map(fn ($b) => static::bosBulgu(array_merge(
            array_intersect_key($b, static::bosBulgu()),
            ['oneriler_metni' => implode("\n", $b['oneriler'] ?? []), 'durum' => $b['durum'] ?? 'onaylandi'],
        )))->all();

        // Eski kayıtlarda havuz yok — bulgu fotoğraflarından kurulur.
        $this->fotograflar = $s->fotograflar ?: collect($s->bulgular ?? [])->pluck('foto_yolu')->filter()->unique()
            ->map(fn ($y) => ['yol' => $y, 'analiz_edildi' => true])->values()->all();
    }

    public function yeniRapor(): void
    {
        $firmaId = $this->firmaId;
        $this->reset('kayitId', 'durum', 'alanBolge', 'gozetimTarihAraligi', 'baglamNotu', 'bulgular', 'fotograflar',
            'seciliFotograflar', 'mevzuatReferanslari', 'iyilestirIndex', 'tamamlaPaneli', 'aciklamaMetni');
        $this->raporTarihi = now()->toDateString();
        $this->firmaId = $firmaId;
        $this->updatedFirmaId();
    }

    public function taslagiIptal(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        $this->kayit?->delete();
        $this->yeniRapor();

        Notification::make()->title('Taslak iptal edildi')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Raporu tamamla → DÖF
    |--------------------------------------------------------------------------
    */

    public function tamamlaPaneliAc(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        if (! $this->bulgular) {
            Notification::make()->title('Raporda en az bir uygunsuzluk olmalı')->danger()->send();

            return;
        }

        $bekleyen = collect($this->bulgular)->where('durum', '!=', 'onaylandi')->count();

        if ($bekleyen) {
            Notification::make()->title("{$bekleyen} madde onay bekliyor")->body('Tamamlamadan önce tüm maddeleri kontrol edip onaylayın.')->danger()->send();

            return;
        }

        $this->tamamlaPaneli = true;
    }

    public function hepsineUygula(): void
    {
        foreach ($this->dofIndexleri() as $i) {
            $this->bulgular[$i]['dof_sorumlu_tipi'] = $this->topluSorumluTipi;
            $this->bulgular[$i]['dof_sorumlu'] = $this->topluSorumluTipi === 'dis' ? $this->topluSorumlu : null;

            if (filled($this->topluTermin)) {
                $this->bulgular[$i]['dof_termin'] = $this->topluTermin;
            }
        }
    }

    public function dofIsaretiniKaldir(int $index): void
    {
        if (! $this->kilitliMi() && isset($this->bulgular[$index])) {
            $this->bulgular[$index]['dof_acilacak'] = false;
        }
    }

    public function raporuTamamla(): void
    {
        if ($this->kilitliMi()) {
            return;
        }

        $bekleyen = collect($this->bulgular)->where('durum', '!=', 'onaylandi')->count();

        if (! $this->firma || ! $this->bulgular || $bekleyen) {
            $this->tamamlaPaneliAc();

            return;
        }

        $eksik = collect($this->dofIndexleri())->reject(fn ($i) => $this->dofHazirMi($i))->count();

        if ($eksik) {
            Notification::make()->title("{$eksik} DÖF maddesinde bilgi eksik")->body('Termin ve (dış kişi ise) sorumlu adı girin ya da işareti kaldırın.')->danger()->send();

            return;
        }

        $s = $this->taslagiKaydet(sessiz: true);
        $dof = $this->dofAc($s);

        $s->update([
            'durum' => SahaAnalizi::TAMAMLANDI,
            'tamamlanma_tarihi' => now(),
            'dof_raporu_id' => $dof?->id,
        ]);

        $this->durum = SahaAnalizi::TAMAMLANDI;
        $this->tamamlaPaneli = false;
        unset($this->kayit, $this->gecmisKayitlar);

        Notification::make()
            ->title('Rapor tamamlandı')
            ->body($s->belge_no.($dof ? ' · '.count($dof->maddeler ?? []).' DÖF açıldı ('.$dof->belge_no.')' : ''))
            ->success()
            ->send();
    }

    private function dofAc(SahaAnalizi $s): ?DofRaporu
    {
        $kendim = Filament::auth()->user()?->name ?: $this->gozetimYapan;

        $maddeler = collect($s->bulgular)->filter(fn ($b) => $b['dof_acilacak'] ?? false)->map(fn ($b) => [
            'tespit' => "[{$s->belge_no}] ".(filled($b['bina_bolge']) ? "[{$b['bina_bolge']}] " : '').$b['tespit'],
            'oncelik' => match ((int) $b['risk_derecesi']) {
                1 => 'kritik',
                2 => 'yuksek',
                3 => 'orta',
                default => 'dusuk',
            },
            'oneri' => trim(implode("\n", $b['oneriler'] ?? []).(filled($b['yasal_gerekce']) ? "\n\nYasal dayanak: {$b['yasal_gerekce']}" : '')) ?: null,
            'sorumlu' => ($b['dof_sorumlu_tipi'] ?? 'kendim') === 'dis' ? $b['dof_sorumlu'] : $kendim,
            'termin' => $b['dof_termin'],
            'durum' => 'acik',
            'foto_yolu' => $b['foto_yolu'],
            'mevcut_skor' => $b['skor'],
            'hedef_skor' => $b['dof_hedef_skor'],
        ])->values()->all();

        if (! $maddeler) {
            return null;
        }

        return DofRaporu::create([
            'firma_id' => $s->firma_id,
            'alan_bolge' => $s->alan_bolge,
            'gozetim_tarih_araligi' => $s->gozetim_tarih_araligi,
            'rapor_tarihi' => now()->toDateString(),
            'gozetim_yapan' => $s->gozetim_yapan,
            'gozetim_yapan_sertifika_no' => $s->gozetim_yapan_sertifika_no,
            'gozetim_yapan_kase' => $s->gozetim_yapan_kase,
            'sorumlu_kisi' => $s->sorumlu_kisi,
            'isveren_vekili_adi' => $s->isveren_vekili_adi,
            'maddeler' => $maddeler,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label(fn () => $this->kilitliMi() ? 'PDF İndir' : 'Önizle / PDF (Taslak)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => $this->firma !== null && $this->bulgular)
                ->schema([
                    Radio::make('bicim')
                        ->label('Biçim')
                        ->options([
                            'gozlem' => 'Saha Gözlem Raporu (dikey, Fine-Kinney)',
                            'gozetim' => 'İSG Saha Gözetim Raporu (yatay, eski biçim)',
                        ])
                        ->default('gozlem')
                        ->required(),
                    ImzaSecenegi::alan(),
                ])
                ->action(function (array $data) {
                    $s = $this->taslagiKaydet(sessiz: true);

                    return $s ? SahaAnaliziUretici::pdf($s, $data['bicim'] ?? 'gozlem', ImzaSecenegi::secili($data)) : null;
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $s = $this->firma?->sahaAnalizleri()->find($id);

        return $s ? SahaAnaliziUretici::pdf($s) : null;
    }

    public function gecmisSil(int $id): void
    {
        $s = $this->firma?->sahaAnalizleri()->find($id);

        if (! $s) {
            return;
        }

        $s->delete();

        if ($this->kayitId === $id) {
            $this->yeniRapor();
        }

        unset($this->gecmisKayitlar);
    }
}
