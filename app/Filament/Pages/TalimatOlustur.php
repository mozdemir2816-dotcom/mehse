<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\Talimat as TalimatModel;
use App\Models\TalimatSablonu;
use App\Support\GeminiTalimatUretici;
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

/**
 * Talimat Oluştur — isgpratik 82-83.jpg. Hazır şablon kütüphanesinden veya
 * uzmanın kendi arşivinden Excel ile yüklediği şablonlardan bir talimat
 * seçilir, madde metinleri AI ile üretilir veya elle yazılır.
 */
class TalimatOlustur extends Page
{
    protected string $view = 'filament.pages.talimat-olustur';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

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
        $hazir = collect(config('isg.talimat.sablonlar'))
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
        $this->maddeler = [];
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
    }

    public function aiIleUret(): void
    {
        if (blank($this->baslik)) {
            Notification::make()->title('Önce başlık girin veya bir şablon seçin')->danger()->send();

            return;
        }

        $kategoriAdi = $this->kategori ? ($this->kategoriler()[$this->kategori] ?? $this->kategori) : 'Genel';
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

        $talimat = new TalimatModel([
            'firma_id' => $this->firma->id,
            'baslik' => $this->baslik,
            'kategori' => $this->kategori,
            'aciklama' => $this->aciklama,
            'kkdler' => $this->kkdler,
            'maddeler' => $this->maddeler,
        ]);
        $talimat->save();

        unset($this->kayitliTalimatlar);

        return $talimat;
    }

    protected function getHeaderActions(): array
    {
        return [
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

            Action::make('pdf')
                ->label('PDF İndir (Kaydet)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $talimat = $this->kaydet();

                    return $talimat ? TalimatUretici::pdf($talimat) : null;
                }),

            Action::make('word')
                ->label('Word İndir (Kaydet)')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $talimat = $this->kaydet();

                    return $talimat ? TalimatWordUretici::word($talimat) : null;
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

    public function kayitliSil(int $id): void
    {
        $this->firma?->talimatlar()->find($id)?->delete();
        unset($this->kayitliTalimatlar);
    }
}
