<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Models\KontrolKarti as KontrolKartiModel;
use App\Support\FotoYonu;
use App\Support\KontrolKartiUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Kontrol Kartı (14.11.2026) — bir alanın tek fotoğrafı üzerinde numaralı
 * işaretlerle kontrol listesi (ilk şablon: Basınçlı Tüp Depolama Alanı).
 * Her madde Uygun / Uygunsuz / Kapsam dışı; uygunsuzda Fine-Kinney önce/sonra,
 * öneri, sorumlu, hedef süre, yönetmelik + standart. Şablonlar config/kontrol_kartlari.php.
 */
class KontrolKarti extends Page
{
    use \App\Filament\Concerns\KaydetSecenegi;
    use \App\Filament\Concerns\SinirliErisim;
    use WithFileUploads;

    protected string $view = 'filament.pages.kontrol-karti';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — Saha sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 98;

    protected static ?string $slug = 'kontrol-karti';

    protected static ?string $title = 'Kontrol Kartı';

    protected static ?string $navigationLabel = 'Kontrol Kartı';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public string $sablon = 'basincli_tup';

    public ?string $alanAdi = null;

    public ?string $konum = null;

    public ?string $kontrolTarihi = null;

    public ?string $kontrolEden = null;

    /** Yeni yüklenen fotoğraf (Livewire geçici dosya). */
    public $foto = null;

    public ?string $fotoYolu = null;

    /** @var array<int, array<string, mixed>> */
    public array $maddeler = [];

    public function mount(): void
    {
        $this->kontrolTarihi = now()->toDateString();
        $this->kontrolEden = Filament::auth()->user()?->name;
        $this->sablonuYukle();

        // Saha sekmelerinin ortak firması (KomutaFirma) — her sekmede yeniden seçtirilmez.
        $this->firmaId = \App\Support\KomutaFirma::baslangic($this->firmalar) ?? $this->firmaId;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<int, KontrolKartiModel> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->kontrolKartlari()->latest()->get() ?? collect();
    }

    #[Computed]
    public function sablonlar(): array
    {
        return KontrolKartiModel::sablonSecenekleri();
    }

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
        $this->kayitUnut();
        unset($this->firma, $this->gecmisKayitlar);
    }

    public function updatedSablon(): void
    {
        $this->sablonuYukle();
    }

    /** Şablona göre boş kart: tüm maddeler "bakılmadı", işaret yok. */
    public function sablonuYukle(): void
    {
        if (! KontrolKartiModel::sablon($this->sablon)) {
            $this->sablon = array_key_first(KontrolKartiModel::sablonSecenekleri()) ?? '';
        }

        $this->maddeler = KontrolKartiModel::bosMaddeler($this->sablon);
        $this->alanAdi = KontrolKartiModel::sablon($this->sablon)['ad'] ?? null;
    }

    public function updatedFoto(): void
    {
        $this->validate(['foto' => 'image|max:15360'], [], ['foto' => 'fotoğraf']);

        $yol = $this->foto->store('kontrol-karti-foto', 'public');
        FotoYonu::duzelt($yol);
        $this->fotoYolu = $yol;
        $this->foto = null;
    }

    public function fotoKaldir(): void
    {
        $this->fotoYolu = null;

        foreach ($this->maddeler as $i => $m) {
            $this->maddeler[$i]['isaret'] = null;
        }
    }

    public function durumSec(int $i, string $durum): void
    {
        if (! isset($this->maddeler[$i]) || ! array_key_exists($durum, KontrolKartiModel::DURUMLAR)) {
            return;
        }

        // Aynı düğmeye tekrar basmak seçimi kaldırır.
        $this->maddeler[$i]['durum'] = $this->maddeler[$i]['durum'] === $durum ? null : $durum;
    }

    /** Bakılmamış maddelerin hepsini "Uygun" yapar (uygunsuzlar işaretlendikten sonra). */
    public function kalanlariUygunYap(): void
    {
        foreach ($this->maddeler as $i => $m) {
            if (blank($m['durum'])) {
                $this->maddeler[$i]['durum'] = KontrolKartiModel::UYGUN;
            }
        }
    }

    /** Fotoğraf üzerindeki işaret konumu (yüzde, 0–100). */
    public function isaretKoy(int $i, float $x, float $y): void
    {
        if (! isset($this->maddeler[$i]) || ! $this->fotoYolu) {
            return;
        }

        $this->maddeler[$i]['isaret'] = [
            'x' => round(max(0, min(100, $x)), 2),
            'y' => round(max(0, min(100, $y)), 2),
        ];
    }

    public function isaretKaldir(int $i): void
    {
        if (isset($this->maddeler[$i])) {
            $this->maddeler[$i]['isaret'] = null;
        }
    }

    private function kaydet(): ?KontrolKartiModel
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firmayı seçin')->danger()->send();

            return null;
        }

        $sayim = KontrolKartiModel::sayim($this->maddeler);

        if ($sayim['bos'] === $sayim['toplam']) {
            Notification::make()->title('Hiçbir madde işaretlenmedi')->body('Her madde için Uygun, Uygunsuz ya da Kapsam dışı seçin.')->warning()->send();

            return null;
        }

        $k = $this->kayitIcin(KontrolKartiModel::class, [
            'firma_id' => $this->firma->id,
            'sablon' => $this->sablon,
            'alan_adi' => $this->alanAdi,
            'konum' => $this->konum,
            'kontrol_tarihi' => $this->kontrolTarihi ?: null,
            'kontrol_eden' => $this->kontrolEden,
            'foto_yolu' => $this->fotoYolu,
            'maddeler' => array_values($this->maddeler),
        ]);
        $k->save();

        unset($this->gecmisKayitlar);

        return $k;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Hazır İstemler → "Kontrol kartı" istemiyle Gemini'de hazırlanan cevap (14.11.2026).
            Action::make('geminidenYukle')
                ->label('Gemini\'den Yükle')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalHeading('Gemini cevabından kontrol kartı')
                ->modalDescription('Hazır İstemler sayfasındaki "Kontrol kartı" istemiyle Gemini\'nin verdiği cevabı yapıştırın, Gemini\'ye gönderdiğiniz fotoğrafı da seçin. Alan sistemde yoksa kontrol listesi yeni şablon olarak kaydedilir.')
                ->modalSubmitActionLabel('Kartı doldur')
                ->schema([
                    \Filament\Forms\Components\Textarea::make('cevap')
                        ->label('Gemini\'nin cevabı (JSON)')
                        ->placeholder('{"alan_adi": "...", "maddeler": [ ... ]}')
                        ->rows(8)
                        ->required(),
                    \Filament\Forms\Components\FileUpload::make('foto')
                        ->label('Fotoğraf (Gemini\'ye gönderdiğiniz)')
                        ->helperText('İşaretler bu fotoğrafın üzerine yerleşir.')
                        ->image()
                        ->maxSize(15360)
                        ->disk('public')->directory('kontrol-karti-foto'),
                ])
                ->action(function (array $data): void {
                    try {
                        $sonuc = \App\Support\KontrolKartiIceAktarici::aktar((string) $data['cevap'], Filament::auth()->id());
                    } catch (\Throwable $e) {
                        if (filled($data['foto'] ?? null)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($data['foto']);
                        }
                        Notification::make()->title('Cevap okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    if (filled($data['foto'] ?? null)) {
                        FotoYonu::duzelt($data['foto']);
                        $this->fotoYolu = $data['foto'];
                    }

                    $this->sablon = $sonuc['sablon'];
                    $this->alanAdi = $sonuc['alan_adi'];
                    $this->maddeler = $sonuc['maddeler'];
                    unset($this->sablonlar);

                    if (! $this->fotoYolu) {
                        foreach ($this->maddeler as $i => $m) {
                            $this->maddeler[$i]['isaret'] = null;
                        }
                    }

                    $sayim = KontrolKartiModel::sayim($this->maddeler);
                    Notification::make()
                        ->title(count($this->maddeler).' madde dolduruldu')
                        ->body(($sonuc['yeni_sablon'] ? '"'.$sonuc['alan_adi'].'" sistemde yoktu; kontrol listesi yeni şablon olarak kaydedildi. ' : '')
                            .$sayim['uygunsuz'].' uygunsuz, '.$sayim['uygun'].' uygun'.($sayim['bos'] ? ', '.$sayim['bos'].' bakılmadı' : '').'. Kontrol edip "Kaydet" ile kaydedin.')
                        ->success()->send();
                }),
            $this->kaydetAction(),
            $this->yeniKayitAction(),
            Action::make('pdf')
                ->label('Kartı Oluştur (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $k = $this->kaydet();

                    if (! $k) {
                        return null;
                    }

                    $this->ciktiAlindi();

                    Notification::make()->title('Kontrol kartı kaydedildi')->body($k->belge_no)->success()->send();

                    return KontrolKartiUretici::pdf($k, ImzaSecenegi::secili($data));
                }),
        ];
    }

    /** Kayıtlı kartı forma açar; kaydetmek aynı kartı günceller. */
    public function gecmisAc(int $id): void
    {
        $k = $this->firma?->kontrolKartlari()->find($id);

        if (! $k) {
            return;
        }

        $this->sablon = $k->sablon;
        $this->alanAdi = $k->alan_adi;
        $this->konum = $k->konum;
        $this->kontrolTarihi = $k->kontrol_tarihi?->toDateString();
        $this->kontrolEden = $k->kontrol_eden;
        $this->fotoYolu = $k->foto_yolu;
        $this->maddeler = $k->maddeler ?? [];
        $this->kayitUnut();
        $this->kayitHatirla($k);

        Notification::make()->title($k->belge_no.' açıldı')->body('Değişiklikleri "Kaydet" ile aynı karta yazabilirsiniz.')->send();
    }

    public function gecmisPdf(int $id)
    {
        $k = $this->firma?->kontrolKartlari()->find($id);

        return $k ? KontrolKartiUretici::pdf($k) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->kontrolKartlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
