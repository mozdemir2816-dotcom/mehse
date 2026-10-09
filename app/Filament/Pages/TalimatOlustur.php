<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Models\Talimat as TalimatModel;
use App\Models\TalimatSablonu;
use App\Support\GeminiTalimatUretici;
use App\Support\TalimatKutuphanesi;
use App\Support\TalimatListesiUretici;
use App\Support\TalimatSablonuExcelIceAktarici;
use App\Support\TalimatUretici;
use App\Support\TalimatWordUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Talimat Oluştur — isgpratik 82-83.jpg. Hazır şablon kütüphanesinden veya
 * uzmanın kendi arşivinden Excel ile yüklediği şablonlardan bir talimat
 * seçilir, madde metinleri AI ile üretilir veya elle yazılır.
 */
class TalimatOlustur extends Page
{
    use \App\Filament\Concerns\SinirliErisim;
    use \App\Filament\Concerns\KaydetSecenegi;

    protected string $view = 'filament.pages.talimat-olustur';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|UnitEnum|null $navigationGroup = 'Diğer Belge & Yazışma';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'talimat';

    protected static ?string $title = 'Talimat Oluştur';

    protected static ?string $navigationLabel = 'Talimat Oluştur';

    public ?int $firmaId = null;

    public string $kategoriFiltre = '';

    public string $arama = '';

    /** Seçili şablonun kaynağı ('hazir'|'ozel') + anahtarı (index veya id). */
    public ?string $secilenKaynak = null;

    public string|int|null $secilenAnahtar = null;

    public ?string $baslik = null;

    public ?string $kategori = null;

    public ?string $aciklama = null;

    /** @var array<int, string> */
    public array $kkdler = [];

    /** @var array<int, string> */
    public array $maddeler = [];

    public ?string $yeniMadde = null;

    /** Kullanıcının talimat künyesi (her sayfanın üst tablosu). */
    public ?string $dokumanNo = null;

    public ?string $yayinTarihi = null;

    public ?string $revizyonNo = null;

    public ?string $revizyonTarihi = null;

    /** Bölümlü yapı (Amaç, Kapsam, KKD… — Kalıp/Demir talimatları) mı, düz numaralı liste mi. */
    public bool $bolumlu = false;

    /**
     * Formdaki bölümler; maddeler textarea'da satır satır (kayıtta diziye çevrilir).
     *
     * @var array<int, array{baslik: string, aciklama: string, maddeler: string}>
     */
    public array $bolumler = [];

    public string $taahhut = '';

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
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
    public function kategoriler(): array
    {
        return config('isg.talimat.kategoriler');
    }

    /** Hazır (config) şablonlar + uzmanın kendi arşivinden yüklediği şablonlar — birleşik kütüphane. */
    #[Computed]
    public function sablonlar(): array
    {
        $hazir = collect(TalimatKutuphanesi::hazirSablonlar())
            ->map(fn ($s, $i) => [...$s, 'kaynak' => 'hazir', 'anahtar' => $i]);

        $ozel = TalimatSablonu::query()
            ->where('user_id', Filament::auth()->id())
            ->get()
            ->map(fn (TalimatSablonu $s) => [
                'baslik' => $s->baslik, 'kategori' => $s->kategori, 'aciklama' => $s->aciklama,
                'kkdler' => $s->kkdler ?? [], 'kaynak' => 'ozel', 'anahtar' => $s->id,
            ]);

        return $hazir->concat($ozel)
            ->filter(fn ($s) => ! $this->kategoriFiltre || $s['kategori'] === $this->kategoriFiltre)
            ->filter(fn ($s) => ! $this->arama || str_contains(mb_strtolower($s['baslik']), mb_strtolower($this->arama)))
            ->values()
            ->all();
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiTalimatUretici::aktifMi();
    }

    /** @return Collection<int, TalimatModel> */
    #[Computed]
    public function kayitliTalimatlar(): Collection
    {
        return $this->firma?->talimatlar()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        $this->kayitUnut();

        unset($this->firma, $this->kayitliTalimatlar);
    }

    /*
    |--------------------------------------------------------------------------
    | Şablon seçimi
    |--------------------------------------------------------------------------
    */

    public function sablonSec(string $kaynak, string|int $anahtar): void
    {
        $sablon = collect($this->sablonlar())->first(fn ($s) => $s['kaynak'] === $kaynak && (string) $s['anahtar'] === (string) $anahtar);

        if (! $sablon) {
            return;
        }

        $this->secilenKaynak = $kaynak;
        $this->secilenAnahtar = $anahtar;
        $this->baslik = $sablon['baslik'];
        $this->kategori = $sablon['kategori'];
        $this->aciklama = $sablon['aciklama'];
        $this->kkdler = $sablon['kkdler'];
        // Hazır şablonların çoğu artık statik (elle yazılmış) madde içeriğiyle
        // gelir — AI (Gemini) API anahtarı olmayan kullanıcı da doğrudan
        // kullanabilsin diye. Madde yoksa (ör. kullanıcının kendi eski
        // şablonu) boş kalır, "AI ile Üret" veya elle ekleme devreye girer.
        $this->maddeler = $sablon['maddeler'] ?? [];

        // Kullanıcının inşaat talimatlarından gelenlerde künye/bölüm/taahhüt de var.
        $this->kunyeSifirla();
        $this->dokumanNo = $sablon['dokuman_no'] ?? null;
        $this->bolumleriYukle($sablon['bolumler'] ?? null, $sablon['taahhut'] ?? null);
    }

    public function yeniTalimat(): void
    {
        $this->secilenKaynak = null;
        $this->secilenAnahtar = null;
        $this->baslik = null;
        $this->kategori = null;
        $this->aciklama = null;
        $this->kkdler = [];
        $this->maddeler = [];
        $this->kunyeSifirla();
        $this->bolumleriYukle(null, null);
    }

    private function kunyeSifirla(): void
    {
        $this->dokumanNo = null;
        $this->yayinTarihi = null;
        $this->revizyonNo = null;
        $this->revizyonTarihi = null;
    }

    /** @param  array<int, array<string, mixed>>|null  $bolumler */
    private function bolumleriYukle(?array $bolumler, ?string $taahhut): void
    {
        $this->bolumlu = ! empty($bolumler);
        $this->bolumler = collect($bolumler ?? [])->map(fn ($b) => [
            'baslik' => (string) ($b['baslik'] ?? ''),
            'aciklama' => (string) ($b['aciklama'] ?? ''),
            'maddeler' => implode("\n", $b['maddeler'] ?? []),
        ])->all();
        $this->taahhut = filled($taahhut) ? $taahhut : TalimatModel::varsayilanTaahhut($this->bolumlu);
    }

    /** @return array<int, array{baslik: string, aciklama: string, maddeler: array<int, string>}> */
    private function bolumleriDiziyeCevir(): array
    {
        return collect($this->bolumler)->map(fn ($b) => [
            'baslik' => trim((string) ($b['baslik'] ?? '')),
            'aciklama' => trim((string) ($b['aciklama'] ?? '')),
            'maddeler' => collect(preg_split('/\R/u', (string) ($b['maddeler'] ?? '')))
                ->map(fn ($m) => trim(preg_replace('/^\s*([•\-–*]|\d+[.)])\s*/u', '', $m)))
                ->filter()->values()->all(),
        ])->filter(fn ($b) => $b['baslik'] !== '' || $b['aciklama'] !== '' || $b['maddeler'])->values()->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Bölümlü yapı (Amaç, Kapsam, KKD, … Yasaklar, Acil Durum)
    |--------------------------------------------------------------------------
    */

    /** Düz listeyi kullanıcının bölümlü düzenine çevirir; maddeler "Genel Kurallar"a gider. */
    public function bolumluYap(): void
    {
        $ozelTaahhut = $this->taahhut !== TalimatModel::TAAHHUT_DUZ && filled($this->taahhut) ? $this->taahhut : null;
        $this->bolumleriYukle(TalimatKutuphanesi::bolumIskeleti((string) $this->baslik, $this->maddeler, $this->kkdler), $ozelTaahhut);
    }

    /** Bölümlerdeki tüm maddeleri tek numaralı listede toplar (açıklamalar düşer). */
    public function duzYap(): void
    {
        $this->maddeler = collect($this->bolumleriDiziyeCevir())->pluck('maddeler')->flatten()->values()->all();

        if ($this->taahhut === TalimatModel::TAAHHUT_BOLUMLU) {
            $this->taahhut = TalimatModel::TAAHHUT_DUZ;
        }

        $this->bolumlu = false;
        $this->bolumler = [];
    }

    public function bolumEkle(): void
    {
        // Yeni bölüm, sondaki Yasaklar/Acil Durum/Yükümlülükler bloğunun önüne girer.
        $sabitSon = collect($this->bolumler)->search(fn ($b) => in_array(mb_strtoupper(trim($b['baslik'])), ['YASAKLAR', 'ACİL DURUM VE BİLDİRİM', 'ÇALIŞAN YÜKÜMLÜLÜKLERİ'], true));
        $yeni = ['baslik' => '', 'aciklama' => '', 'maddeler' => ''];

        if ($sabitSon === false) {
            $this->bolumler[] = $yeni;
        } else {
            array_splice($this->bolumler, $sabitSon, 0, [$yeni]);
        }
    }

    public function bolumSil(int $index): void
    {
        unset($this->bolumler[$index]);
        $this->bolumler = array_values($this->bolumler);
    }

    public function bolumTasi(int $index, int $yon): void
    {
        $hedef = $index + $yon;

        if (! isset($this->bolumler[$index], $this->bolumler[$hedef])) {
            return;
        }

        [$this->bolumler[$index], $this->bolumler[$hedef]] = [$this->bolumler[$hedef], $this->bolumler[$index]];
    }

    public function aiIleUret(): void
    {
        if (blank($this->baslik)) {
            Notification::make()->title('Önce başlık girin veya bir şablon seçin')->danger()->send();

            return;
        }

        $kategoriAdi = $this->kategori ? ($this->kategoriler()[$this->kategori] ?? $this->kategori) : 'Genel';

        if ($this->bolumlu) {
            $bolumler = GeminiTalimatUretici::uretBolumlu($this->baslik, $kategoriAdi, $this->kkdler);

            if (! $bolumler) {
                Notification::make()->title('Talimat üretilemedi')->body('AI servisi yanıt vermedi veya devre dışı; bölümleri elle doldurabilirsiniz.')->warning()->send();

                return;
            }

            $this->bolumleriYukle($bolumler, $this->taahhut);
            Notification::make()->title(count($bolumler).' bölüm üretildi')->success()->send();

            return;
        }

        $maddeler = GeminiTalimatUretici::uret($this->baslik, $kategoriAdi, $this->kkdler);

        if (! $maddeler) {
            Notification::make()->title('Talimat üretilemedi')->body('AI servisi yanıt vermedi veya devre dışı; elle madde ekleyebilirsiniz.')->warning()->send();

            return;
        }

        $this->maddeler = $maddeler;
        Notification::make()->title(count($maddeler).' madde üretildi')->success()->send();
    }

    public function maddeEkle(): void
    {
        if (blank($this->yeniMadde)) {
            return;
        }

        $this->maddeler[] = $this->yeniMadde;
        $this->reset('yeniMadde');
    }

    public function maddeSil(int $index): void
    {
        unset($this->maddeler[$index]);
        $this->maddeler = array_values($this->maddeler);
    }

    /*
    |--------------------------------------------------------------------------
    | Kendi arşivim (Excel ile toplu yükleme)
    |--------------------------------------------------------------------------
    */

    public function kendiSablonSil(int $id): void
    {
        TalimatSablonu::where('user_id', Filament::auth()->id())->where('id', $id)->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?TalimatModel
    {
        if (! $this->firma || blank($this->baslik)) {
            Notification::make()->title('Firma ve talimat başlığı zorunlu')->danger()->send();

            return null;
        }

        $talimat = $this->kayitIcin(TalimatModel::class, [
            'firma_id' => $this->firma->id,
            'baslik' => $this->baslik,
            'kategori' => $this->kategori,
            'aciklama' => $this->aciklama,
            'kkdler' => $this->kkdler,
            'maddeler' => $this->bolumlu ? [] : $this->maddeler,
            'bolumler' => $this->bolumlu ? $this->bolumleriDiziyeCevir() : null,
            // Varsayılan metin saklanmaz — ileride varsayılan güncellenirse eski kayıtlara da yansır.
            'taahhut' => trim($this->taahhut) === '' || trim($this->taahhut) === TalimatModel::varsayilanTaahhut($this->bolumlu) ? null : trim($this->taahhut),
            'dokuman_no' => filled($this->dokumanNo) ? trim($this->dokumanNo) : null,
            'yayin_tarihi' => $this->yayinTarihi ?: null,
            'revizyon_no' => filled($this->revizyonNo) ? trim($this->revizyonNo) : null,
            'revizyon_tarihi' => $this->revizyonTarihi ?: null,
        ]);
        $talimat->save();

        unset($this->kayitliTalimatlar);

        return $talimat;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->kaydetAction(),
            $this->yeniKayitAction(),

            // Firmaya verilen talimatların listesi = kullanıcının "Talimat Teslim Tutanağı" (Talimat İçerikleri.xlsx).
            Action::make('talimatListesi')
                ->label('Talimat Listesi / Teslim Tutanağı')
                ->icon('heroicon-o-list-bullet')
                ->color('success')
                ->visible(fn () => $this->firma !== null)
                ->disabled(fn () => $this->kayitliTalimatlar->isEmpty())
                ->modalHeading(fn () => 'Talimat Teslim Tutanağı — '.$this->firma?->unvan)
                ->modalDescription(fn () => $this->kayitliTalimatlar->count().' kayıtlı talimat listelenir; işveren yükümlülükleri ve Teslim Eden / Teslim Alan imzalarıyla.')
                ->modalSubmitActionLabel('İndir')
                ->fillForm(['bicim' => 'pdf', 'tarih' => now()->toDateString()])
                ->schema([
                    \Filament\Forms\Components\Radio::make('bicim')->label('Biçim')
                        ->options(['pdf' => 'PDF', 'excel' => 'Excel'])->inline()->required(),
                    \Filament\Forms\Components\DatePicker::make('tarih')->label('Teslim tarihi')->native(false)->displayFormat('d.m.Y')->required(),
                ])
                ->action(fn (array $data) => $data['bicim'] === 'excel'
                    ? TalimatListesiUretici::excel($this->firma, $data['tarih'] ?? null)
                    : TalimatListesiUretici::pdf($this->firma, $data['tarih'] ?? null)),

            Action::make('sablonIndir')
                ->label('Şablon İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => TalimatSablonuExcelIceAktarici::sablonIndir()),

            Action::make('excelYukle')
                ->label('Kendi Arşivimden Excel Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalDescription('İlk satır başlık kabul edilir. "Başlık" zorunlu; "Maddeler" sütununda her satıra (hücre içinde Alt+Enter ile) bir madde yazın.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    FileUpload::make('dosya')
                        ->label('Excel / CSV dosyası')
                        ->disk('local')
                        ->directory('excel-ice-aktarim')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->helperText('Beklenen sütunlar (şablonu indirin): '.implode(', ', TalimatSablonuExcelIceAktarici::SABLON_BASLIKLARI)),
                ])
                ->action(function (array $data): void {
                    $yol = Storage::disk('local')->path($data['dosya']);

                    try {
                        $sonuc = TalimatSablonuExcelIceAktarici::iceAktar($yol, (int) Filament::auth()->id());
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($data['dosya']);
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Storage::disk('local')->delete($data['dosya']);

                    $bildirim = Notification::make()->title($sonuc['basarili'].' talimat şablonu eklendi');

                    if ($sonuc['hatalar']) {
                        $bildirim->body(implode("\n", array_slice($sonuc['hatalar'], 0, 10)));
                    }

                    $sonuc['basarili'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                }),

            Action::make('dosyaYukle')
                ->label('Kendi Dosyanı Yükle (Word/PDF)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalHeading('Hazır Talimat Dosyası Yükle')
                ->modalDescription('Kendi hazırladığınız Word veya PDF dosyasını doğrudan bu firmaya talimat olarak kaydedin — madde/AI akışına gerek kalmadan indirilebilir olarak saklanır.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('baslik')->label('Başlık')->required(),
                    \Filament\Forms\Components\Select::make('kategori')->label('Kategori')
                        ->options(fn () => $this->kategoriler())->native(false),
                    FileUpload::make('dosya')->label('Dosya (Word/PDF)')
                        ->disk('public')
                        ->directory(fn () => 'talimatlar/'.$this->firmaId)
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $talimat = new TalimatModel([
                        'firma_id' => $this->firma->id,
                        'baslik' => $data['baslik'],
                        'kategori' => $data['kategori'] ?? null,
                        'dosya_adi' => basename($data['dosya']),
                        'dosya_yolu' => $data['dosya'],
                        'boyut' => Storage::disk('public')->exists($data['dosya']) ? Storage::disk('public')->size($data['dosya']) : 0,
                    ]);
                    $talimat->save();

                    unset($this->kayitliTalimatlar);
                    Notification::make()->title('Talimat dosyası yüklendi')->success()->send();
                }),

            Action::make('pdf')
                ->label('PDF İndir (Kaydet)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $talimat = $this->kaydet();
                    if ($talimat) {
                        $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır
                    }

                    return $talimat ? TalimatUretici::pdf($talimat, ImzaSecenegi::secili($data)) : null;
                }),

            Action::make('word')
                ->label('Word İndir (Kaydet)')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $talimat = $this->kaydet();
                    if ($talimat) {
                        $this->ciktiAlindi();   // aynı formun diğer çıktıları aynı belgeyi kullanır
                    }

                    return $talimat ? TalimatWordUretici::word($talimat, ImzaSecenegi::secili($data)) : null;
                }),
        ];
    }

    public function kayitliPdf(int $id)
    {
        $talimat = $this->firma?->talimatlar()->find($id);

        return $talimat ? TalimatUretici::pdf($talimat) : null;
    }

    public function kayitliWord(int $id)
    {
        $talimat = $this->firma?->talimatlar()->find($id);

        return $talimat ? TalimatWordUretici::word($talimat) : null;
    }

    /** Kayıtlı talimatı forma yükler; "Kaydet" aynı kaydı günceller (künye/revizyon düzeltmek için). */
    public function kayitliDuzenle(int $id): void
    {
        $talimat = $this->firma?->talimatlar()->find($id);

        if (! $talimat || $talimat->dosyaVarMi()) {
            return;
        }

        $this->secilenKaynak = null;
        $this->secilenAnahtar = null;
        $this->baslik = $talimat->baslik;
        $this->kategori = $talimat->kategori;
        $this->aciklama = $talimat->aciklama;
        $this->kkdler = $talimat->kkdler ?? [];
        $this->maddeler = $talimat->maddeler ?? [];
        $this->dokumanNo = $talimat->dokuman_no;
        $this->yayinTarihi = $talimat->yayin_tarihi?->format('Y-m-d');
        $this->revizyonNo = $talimat->revizyon_no;
        $this->revizyonTarihi = $talimat->revizyon_tarihi?->format('Y-m-d');
        $this->bolumleriYukle($talimat->bolumler, $talimat->taahhut);

        $this->kayitHatirla($talimat);
        $this->ciktiImzasi = null;

        Notification::make()->title('Talimat düzenlemeye açıldı')->body('Değişiklikten sonra "Kaydet" aynı kaydı günceller.')->send();
    }

    public function kayitliDosyaIndir(int $id)
    {
        $talimat = $this->firma?->talimatlar()->find($id);

        if (! $talimat || ! $talimat->dosya_yolu || ! Storage::disk('public')->exists($talimat->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($talimat->dosya_yolu, $talimat->dosya_adi);
    }

    public function kayitliSil(int $id): void
    {
        $talimat = $this->firma?->talimatlar()->find($id);

        if ($talimat?->dosya_yolu && Storage::disk('public')->exists($talimat->dosya_yolu)) {
            Storage::disk('public')->delete($talimat->dosya_yolu);
        }

        $talimat?->delete();
        unset($this->kayitliTalimatlar);
    }
}
