<?php

namespace App\Filament\Pages;

use App\Filament\Support\DosyaKabul;
use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Support\BulguDonusturucu;
use App\Support\DofTabloOkuyucu;
use App\Support\GeminiSahaAnalizi;
use App\Support\SahaBulgusuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Saha Bulguları — saha kontrollerinin TEK GİRİŞ NOKTASI ("tek bulgu, çok
 * çıktı", 08.10.2026; önceki adı Hızlı Saha Bulgusu, isgsuite "Hızlı bulgu").
 * Bulgu girişi: elle, fotoğraftan AI, açıklamadan AI, Word/Excel'den aktar
 * (DÖF tablosu formatı — başka yapay zekaya hazırlatılan rapor). Kayıt: yer +
 * GPS, 75 tehlike kategorisi, uygunsuzluk, mevcut önlem, yasal gerekçe, 5×5
 * risk (öncelik skordan ya da elle), aksiyon / sorumlu / termin, 5 fotoğraf.
 * Durumlar açık / devam ediyor / ertelendi / kapandı; seçilen bulgular DÖF'e
 * aktarılır. Adres (slug) eski bağlantılar bozulmasın diye aynı kaldı.
 */
class HizliSahaBulgusu extends Page
{
    use \App\Filament\Concerns\SinirliErisim;
    use WithFileUploads;

    protected string $view = 'filament.pages.hizli-saha-bulgusu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Kontrolleri';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'hizli-saha-bulgusu';

    protected static ?string $title = 'Saha Bulguları';

    protected static ?string $navigationLabel = 'Saha Bulguları';

    // --- Form ---
    public ?int $firmaId = null;

    public ?string $bolum = null;

    public ?string $gozlemKonumu = null;

    public ?float $enlem = null;

    public ?float $boylam = null;

    public ?string $kategori = null;

    public ?string $tehlike = null;

    public ?string $uygunsuzluk = null;

    public ?string $mevcutOnlemler = null;

    public ?string $yasalGerekce = null;

    public int $olasilik = 3;

    public int $siddet = 3;

    /** Boş = 5×5 skordan otomatik. */
    public ?string $oncelik = null;

    public ?string $aksiyon = null;

    public ?string $sorumlu = null;

    public ?string $termin = null;

    /** @var array<int, mixed> */
    public array $yeniFotograflar = [];

    /** "AI ile doldur" sırasında diske yazılmış fotoğraflar (kayıtta yeniden yüklenmez). */
    public array $kaydedilenFotolar = [];

    public string $kaynak = 'manuel';

    /** Açıklamadan AI için serbest metin. */
    public ?string $aciklamaMetni = null;

    /** AI'ın forma yazılan ilk tespit dışında bulduğu tespitler (toplu eklenebilir). */
    public array $aiEkTespitler = [];

    // --- Liste ---
    public ?string $listeDurum = 'acik';

    /** @var array<int, int|string> DÖF'e toplu aktarım için seçili bulgu id'leri */
    public array $secili = [];

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** Seçili firmadaki önceki bulguların bölümleri — hızlı seçim önerisi. @return array<int, string> */
    #[Computed]
    public function bolumOnerileri(): array
    {
        return $this->firmaId
            ? SahaBulgusu::query()->where('firma_id', $this->firmaId)->whereNotNull('bolum')->distinct()->orderBy('bolum')->pluck('bolum')->all()
            : [];
    }

    /** @return Collection<int, SahaBulgusu> */
    #[Computed]
    public function bulgular(): Collection
    {
        return SahaBulgusu::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->when($this->listeDurum === 'acik', fn ($q) => $q->whereIn('durum', ['acik', 'devam_ediyor']))
            ->when($this->listeDurum && $this->listeDurum !== 'acik', fn ($q) => $q->where('durum', $this->listeDurum))
            ->with('firma')
            ->latest('id')
            ->get();
    }

    /** @return array{acik: int, kritik: int, gecikmis: int, kapandi: int} */
    #[Computed]
    public function ozet(): array
    {
        $hepsi = SahaBulgusu::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->get();
        $acik = $hepsi->filter->acikMi();

        return [
            'acik' => $acik->count(),
            'kritik' => $acik->filter(fn (SahaBulgusu $b) => $b->oncelikAnahtari() === 'kritik')->count(),
            'gecikmis' => $acik->filter->terminGectiMi()->count(),
            'kapandi' => $hepsi->where('durum', 'kapandi')->count(),
        ];
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'listeDurum'], true)) {
            unset($this->bulgular, $this->ozet, $this->bolumOnerileri);
            $this->secili = [];
        }
    }

    private function yenile(): void
    {
        unset($this->bulgular, $this->ozet, $this->bolumOnerileri);
    }

    /** Tarayıcıdan gelen GPS koordinatı (Konum al düğmesi). */
    public function konumAyarla(float $enlem, float $boylam): void
    {
        $this->enlem = round($enlem, 7);
        $this->boylam = round($boylam, 7);
    }

    public function fotoSil(int $index): void
    {
        unset($this->yeniFotograflar[$index]);
        $this->yeniFotograflar = array_values($this->yeniFotograflar);
    }

    private function fotoSayisi(): int
    {
        return count($this->kaydedilenFotolar) + count($this->yeniFotograflar);
    }

    /** Yeni seçilen fotoğrafları diske yazar (bir kez). */
    private function fotolariYaz(): void
    {
        foreach ($this->yeniFotograflar as $f) {
            $this->kaydedilenFotolar[] = $f->store('saha-bulgu-foto', 'public');
        }
        $this->yeniFotograflar = [];
    }

    private function aiBaglami(): ?string
    {
        return collect([$this->bolum ? 'Bölüm: '.$this->bolum : null, $this->kategori ? 'Odak tehlike: '.$this->kategori : null])->filter()->implode('. ') ?: null;
    }

    /** AI tespitini (GeminiSahaAnalizi) forma yazar. */
    private function aiTespitiniFormaYaz(array $t): void
    {
        $this->uygunsuzluk = $t['tespit'];
        $this->aksiyon = implode("\n", $t['oneriler'] ?? []);
        $this->yasalGerekce = $t['yasal_gerekce'] ?? $this->yasalGerekce;
        $this->siddet = max(1, min(5, 6 - (int) ($t['risk_derecesi'] ?? 3)));
        $this->oncelik = null;
        $this->tehlike ??= filled($t['kategori'] ?? null) ? $t['kategori'] : null;
        $this->kategori ??= static::kategoriEslestir($t['kategori'] ?? '');
        $this->gozlemKonumu ??= $t['bina_bolge'] ?? null;
        $this->kaynak = 'ai';
    }

    /** Fotoğraftan AI taslağı: ilk tespit forma, diğerleri toplu eklemeye hazır. */
    public function aiIleDoldur(): void
    {
        if ($this->fotoSayisi() === 0) {
            Notification::make()->title('Önce fotoğraf ekleyin')->danger()->send();

            return;
        }

        if (! GeminiSahaAnalizi::aktifMi()) {
            Notification::make()->title('Yapay zeka kullanılamıyor')->body('Gemini API anahtarı tanımlı değil — alanları elle doldurun.')->warning()->send();

            return;
        }

        $this->fotolariYaz();
        $sonuc = GeminiSahaAnalizi::analizEt($this->kaydedilenFotolar, $this->aiBaglami());

        if (! $sonuc) {
            Notification::make()->title('AI tespit üretemedi')->body('Alanları elle doldurun.')->warning()->send();

            return;
        }

        $this->aiTespitiniFormaYaz($sonuc[0]);
        $this->aiEkTespitler = array_values(array_slice($sonuc, 1));

        Notification::make()
            ->title('AI taslağı dolduruldu')
            ->body($this->aiEkTespitler ? count($sonuc).' tespit bulundu; ilki forma yazıldı. Diğerlerini "Kalan tespitleri ekle" ile ekleyebilirsiniz.' : 'Kontrol edip kaydedin.')
            ->success()
            ->send();
    }

    /** Açıklamadan AI taslağı (fotoğraf olmadan, sahada yazılan kısa nottan). */
    public function aciklamadanDoldur(): void
    {
        if (mb_strlen(trim((string) $this->aciklamaMetni)) < 5) {
            Notification::make()->title('Gördüğünüzü birkaç kelimeyle yazın')->danger()->send();

            return;
        }

        if (! GeminiSahaAnalizi::aktifMi()) {
            Notification::make()->title('Yapay zeka kullanılamıyor')->body('Gemini API anahtarı tanımlı değil — alanları elle doldurun.')->warning()->send();

            return;
        }

        $t = GeminiSahaAnalizi::aciklamadanUret($this->aciklamaMetni, $this->aiBaglami());

        if (! $t) {
            Notification::make()->title('AI tespit üretemedi')->body('Alanları elle doldurun.')->warning()->send();

            return;
        }

        $this->aiTespitiniFormaYaz($t);
        $this->aciklamaMetni = null;

        Notification::make()->title('AI taslağı dolduruldu')->body('Kontrol edip kaydedin.')->success()->send();
    }

    /** AI'ın bulduğu diğer tespitleri, aynı yer ve fotoğraflarla ayrı bulgular olarak kaydeder. */
    public function kalanTespitleriEkle(): void
    {
        if (! $this->firmaId || ! array_key_exists($this->firmaId, $this->firmalar) || ! $this->aiEkTespitler) {
            return;
        }

        foreach ($this->aiEkTespitler as $t) {
            $alanlar = BulguDonusturucu::gozlemMaddesinden([
                'tespit' => $t['tespit'],
                'oneriler_metni' => implode("\n", $t['oneriler'] ?? []),
                'yasal_gerekce' => $t['yasal_gerekce'] ?? null,
                'bina_bolge' => $t['bina_bolge'] ?? null,
                'kategori' => static::kategoriEslestir($t['kategori'] ?? ''),
                'risk_derecesi' => $t['risk_derecesi'] ?? 3,
                'kaynak' => 'foto',
            ]);

            SahaBulgusu::create([
                ...$alanlar,
                'firma_id' => $this->firmaId,
                'bolum' => $this->bolum,
                'gozlem_konumu' => $alanlar['bolum'] ?? $this->gozlemKonumu,
                'tehlike' => $t['kategori'] ?? null,
                'fotograflar' => array_values(array_filter([$t['foto_yolu'] ?? null])) ?: $this->kaydedilenFotolar,
                'kaydeden' => Filament::auth()->user()?->name,
            ]);
        }

        Notification::make()->title(count($this->aiEkTespitler).' bulgu daha eklendi')->success()->send();
        $this->aiEkTespitler = [];
        $this->yenile();
    }

    /** AI kategori metnini 75'lik listeden en yakın başlığa eşler (içerme). */
    public static function kategoriEslestir(string $metin): ?string
    {
        $m = mb_strtolower(trim($metin));

        if ($m === '') {
            return null;
        }

        return collect(config('isg.saha_tehlike_kategorileri'))
            ->first(fn (string $k) => str_contains($m, mb_strtolower($k)) || str_contains(mb_strtolower($k), $m));
    }

    public function kaydet(): void
    {
        $this->validate([
            'firmaId' => ['required', 'integer'],
            'uygunsuzluk' => ['required', 'string', 'min:5'],
            'olasilik' => ['required', 'integer', 'between:1,5'],
            'siddet' => ['required', 'integer', 'between:1,5'],
            'oncelik' => ['nullable', 'in:'.implode(',', array_keys(SahaBulgusu::ONCELIKLER))],
            'termin' => ['nullable', 'date'],
            'yeniFotograflar.*' => ['image', 'max:15360'],
        ], [], ['firmaId' => 'işyeri', 'uygunsuzluk' => 'uygunsuzluk tanımı']);

        abort_unless(array_key_exists($this->firmaId, $this->firmalar), 403);

        if ($this->fotoSayisi() > (int) config('isg.saha_bulgu.max_foto', 5)) {
            Notification::make()->title('En fazla '.config('isg.saha_bulgu.max_foto', 5).' fotoğraf')->danger()->send();

            return;
        }

        $this->fotolariYaz();

        $b = SahaBulgusu::create([
            'firma_id' => $this->firmaId,
            'kaynak' => $this->kaynak,
            'bolum' => $this->bolum,
            'gozlem_konumu' => $this->gozlemKonumu,
            'enlem' => $this->enlem,
            'boylam' => $this->boylam,
            'kategori' => $this->kategori,
            'tehlike' => $this->tehlike,
            'uygunsuzluk' => trim($this->uygunsuzluk),
            'mevcut_onlemler' => $this->mevcutOnlemler,
            'yasal_gerekce' => $this->yasalGerekce,
            'olasilik' => $this->olasilik,
            'siddet' => $this->siddet,
            'oncelik' => $this->oncelik ?: null,
            'aksiyon' => $this->aksiyon,
            'sorumlu' => $this->sorumlu,
            'termin' => $this->termin ?: null,
            'fotograflar' => $this->kaydedilenFotolar,
            'kaydeden' => Filament::auth()->user()?->name,
        ]);

        // Firma ve bölüm sonraki bulgu için kalsın (sahada aynı alanda devam).
        $this->reset('gozlemKonumu', 'enlem', 'boylam', 'kategori', 'tehlike', 'uygunsuzluk', 'mevcutOnlemler', 'yasalGerekce', 'aksiyon', 'sorumlu', 'termin', 'yeniFotograflar', 'kaydedilenFotolar', 'kaynak', 'olasilik', 'siddet', 'oncelik');
        $this->yenile();

        Notification::make()->title('Bulgu kaydedildi')->body($b->bulgu_no.' — risk '.$b->skor().' ('.$b->oncelikEtiketi().')')->success()->send();
    }

    private function bulgu(int $id): SahaBulgusu
    {
        return SahaBulgusu::query()->whereIn('firma_id', array_keys($this->firmalar))->findOrFail($id);
    }

    public function kapatAction(): Action
    {
        return Action::make('kapat')
            ->label('Kapat')
            ->modalHeading(fn (array $arguments) => 'Bulguyu kapat — '.$this->bulgu($arguments['id'])->bulgu_no)
            ->fillForm(fn (): array => ['kapanis_tarihi' => now()->toDateString()])
            ->schema([
                DatePicker::make('kapanis_tarihi')->label('Kapanış tarihi')->required(),
                Textarea::make('kapanis_notu')->label('Yapılan düzeltme')->rows(3)->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->bulgu($arguments['id'])->update([...$data, 'durum' => 'kapandi']);
                $this->yenile();

                Notification::make()->title('Bulgu kapatıldı')->success()->send();
            });
    }

    /** Açık / devam ediyor / ertelendi arası geçiş (kapatma ayrı aksiyonla, not ister). */
    public function durumDegistir(int $id, string $durum): void
    {
        abort_unless(in_array($durum, ['acik', 'devam_ediyor', 'ertelendi'], true), 422);

        $this->bulgu($id)->update(['durum' => $durum, 'kapanis_tarihi' => null]);
        $this->yenile();
    }

    public function yenidenAc(int $id): void
    {
        $this->durumDegistir($id, 'acik');
    }

    /** Seçili bulguları (ya da tek bulguyu) fotoğraflarıyla DÖF Oluştur ekranına taşır. */
    public function dofeAktar(?int $id = null)
    {
        $idler = $id ? [$id] : array_map('intval', $this->secili);
        $bulgular = SahaBulgusu::query()->whereIn('firma_id', array_keys($this->firmalar))->whereIn('id', $idler)->orderBy('id')->get();

        if ($bulgular->isEmpty()) {
            Notification::make()->title('Önce bulgu seçin')->warning()->send();

            return null;
        }

        if ($bulgular->pluck('firma_id')->unique()->count() > 1) {
            Notification::make()->title('Tek DÖF raporunda tek işyeri olur')->body('Seçimi tek işyerinin bulgularıyla sınırlayın.')->warning()->send();

            return null;
        }

        session(['dof_aktarim' => [
            'firma_id' => $bulgular->first()->firma_id,
            'kaynak' => $bulgular->count() === 1 ? 'Saha Bulgusu '.$bulgular->first()->bulgu_no : 'Saha Bulguları',
            'maddeler' => $bulgular->map(function (SahaBulgusu $b): array {
                $m = BulguDonusturucu::dofMaddesi($b);
                $m['tespit'] = trim(($b->bolum ? "[{$b->bolum}] " : '').$b->uygunsuzluk);

                return $m;
            })->all(),
        ]]);

        return $this->redirect(DofOlustur::getUrl());
    }

    public function pdf(int $id)
    {
        return SahaBulgusuUretici::pdf($this->bulgu($id));
    }

    public function sil(int $id): void
    {
        $this->bulgu($id)->delete();
        $this->secili = array_values(array_diff($this->secili, [$id, (string) $id]));
        $this->yenile();
    }

    /**
     * Word/Excel'den aktar — DÖF tablosu formatı (Tespit / Öneri sütunları;
     * başka yapay zekaya hazırlatılan rapor). Her satır bir bulgu olur,
     * künyedeki Alan / Bölge bulgunun bölümüne yazılır.
     *
     * @return int eklenen bulgu sayısı
     */
    public function dosyadanAktar(string $yol, string $adi): int
    {
        abort_unless($this->firmaId && array_key_exists($this->firmaId, $this->firmalar), 403);

        try {
            $sonuc = DofTabloOkuyucu::oku(Storage::disk('local')->path($yol), pathinfo($adi, PATHINFO_EXTENSION));
        } catch (\Throwable $e) {
            report($e);
            $sonuc = ['bilgi' => [], 'maddeler' => []];
        } finally {
            Storage::disk('local')->delete($yol);
        }

        foreach ($sonuc['maddeler'] as $m) {
            SahaBulgusu::create([
                ...BulguDonusturucu::dofMaddesinden($m),
                'firma_id' => $this->firmaId,
                'kaynak' => 'dosya',
                'bolum' => $sonuc['bilgi']['alanBolge'] ?? $this->bolum,
                'kaydeden' => Filament::auth()->user()?->name,
            ]);
        }

        $this->yenile();

        return count($sonuc['maddeler']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dosyadanAktar')
                ->label("Word/Excel'den Aktar")
                ->icon('heroicon-o-document-arrow-up')
                ->color('gray')
                ->visible(fn () => $this->firmaId !== null)
                ->modalHeading("Word/Excel'den bulgu aktar")
                ->modalDescription('DÖF tablosu formatındaki dosya (Tespit / Öncelik / Öneri / Sorumlu / Termin / Durum / Foto sütunları) — her satır seçili işyerine ayrı bir bulgu olarak eklenir, fotoğraflar dahil.')
                ->schema([
                    DosyaKabul::uygula(FileUpload::make('dosya')->storeFileNamesIn('dosya_adi')
                        ->label('Word (.docx) veya Excel (.xlsx/.xls) dosyası')
                        ->disk('local')->directory('bulgu-aktarim'), ['docx', 'xlsx', 'xls'])
                        ->maxSize(20480)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $sayi = $this->dosyadanAktar((string) $data['dosya'], (string) ($data['dosya_adi'] ?? $data['dosya']));

                    $sayi > 0
                        ? Notification::make()->title($sayi.' bulgu eklendi')->body('DÖF raporu için listeden seçip "Seçilenleri DÖF\'e Aktar"ı kullanın.')->success()->send()
                        : Notification::make()->title('Dosyada bulgu tablosu bulunamadı')->body('Tabloda "Tespit" ve "Öneri / Düzeltici Faaliyet" başlıklı sütunlar olmalı.')->danger()->send();
                }),

            Action::make('excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->bulgular->isNotEmpty())
                ->action(fn () => SahaBulgusuUretici::excel($this->bulgular)),
        ];
    }
}
