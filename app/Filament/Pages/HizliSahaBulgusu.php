<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Support\GeminiSahaAnalizi;
use App\Support\SahaBulgusuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Hızlı Saha Bulgusu (isgsuite Saha Denetimi "Hızlı bulgu") — sahada tek
 * uygunsuzluğu hızla kaydetme: bölüm, gözlem konumu + GPS, 75 tehlike
 * kategorisinden seçim, uygunsuzluk, mevcut önlemler, 5×5 risk, aksiyon /
 * sorumlu / termin ve en fazla 5 fotoğraf. "AI ile doldur" fotoğraftan
 * taslak üretir (uzman düzenler). Bulgular kapatılabilir, DÖF'e aktarılabilir.
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

    protected static ?string $title = 'Hızlı Saha Bulgusu';

    protected static ?string $navigationLabel = 'Hızlı Saha Bulgusu';

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

    public int $olasilik = 3;

    public int $siddet = 3;

    public ?string $aksiyon = null;

    public ?string $sorumlu = null;

    public ?string $termin = null;

    /** @var array<int, mixed> */
    public array $yeniFotograflar = [];

    /** "AI ile doldur" sırasında diske yazılmış fotoğraflar (kayıtta yeniden yüklenmez). */
    public array $kaydedilenFotolar = [];

    public string $kaynak = 'manuel';

    // --- Liste ---
    public ?string $listeDurum = 'acik';

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
            ->when($this->listeDurum, fn ($q) => $q->where('durum', $this->listeDurum))
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
        $acik = $hepsi->where('durum', 'acik');

        return [
            'acik' => $acik->count(),
            'kritik' => $acik->filter(fn (SahaBulgusu $b) => $b->skor() >= 15)->count(),
            'gecikmis' => $acik->filter->terminGectiMi()->count(),
            'kapandi' => $hepsi->where('durum', 'kapandi')->count(),
        ];
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'listeDurum'], true)) {
            unset($this->bulgular, $this->ozet, $this->bolumOnerileri);
        }
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

    /** Fotoğraftan AI taslağı: ilk tespit forma doldurulur (uzman düzenler). */
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

        $baglam = collect([$this->bolum ? 'Bölüm: '.$this->bolum : null, $this->kategori ? 'Odak tehlike: '.$this->kategori : null])->filter()->implode('. ');
        $sonuc = GeminiSahaAnalizi::analizEt($this->kaydedilenFotolar, $baglam ?: null);

        if (! $sonuc) {
            Notification::make()->title('AI tespit üretemedi')->body('Alanları elle doldurun.')->warning()->send();

            return;
        }

        $ilk = $sonuc[0];
        $this->uygunsuzluk = $ilk['tespit'];
        $this->aksiyon = implode("\n", $ilk['oneriler'] ?? []);
        $this->siddet = max(1, min(5, 6 - (int) ($ilk['risk_derecesi'] ?? 3)));
        $this->tehlike ??= filled($ilk['kategori'] ?? null) ? $ilk['kategori'] : null;
        $this->kategori ??= static::kategoriEslestir($ilk['kategori'] ?? '');
        $this->gozlemKonumu ??= $ilk['bina_bolge'] ?? null;
        $this->kaynak = 'ai';

        Notification::make()
            ->title('AI taslağı dolduruldu')
            ->body(count($sonuc) > 1 ? count($sonuc).' tespit bulundu; ilki forma yazıldı. Kontrol edip kaydedin.' : 'Kontrol edip kaydedin.')
            ->success()
            ->send();
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
            'olasilik' => $this->olasilik,
            'siddet' => $this->siddet,
            'aksiyon' => $this->aksiyon,
            'sorumlu' => $this->sorumlu,
            'termin' => $this->termin ?: null,
            'fotograflar' => $this->kaydedilenFotolar,
            'kaydeden' => Filament::auth()->user()?->name,
        ]);

        // Firma ve bölüm sonraki bulgu için kalsın (sahada aynı alanda devam).
        $this->reset('gozlemKonumu', 'enlem', 'boylam', 'kategori', 'tehlike', 'uygunsuzluk', 'mevcutOnlemler', 'aksiyon', 'sorumlu', 'termin', 'yeniFotograflar', 'kaydedilenFotolar', 'kaynak', 'olasilik', 'siddet');
        unset($this->bulgular, $this->ozet, $this->bolumOnerileri);

        Notification::make()->title('Bulgu kaydedildi')->body($b->bulgu_no.' — risk '.$b->skor().' ('.$b->seviyeEtiketi().')')->success()->send();
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
                unset($this->bulgular, $this->ozet);

                Notification::make()->title('Bulgu kapatıldı')->success()->send();
            });
    }

    public function yenidenAc(int $id): void
    {
        $this->bulgu($id)->update(['durum' => 'acik', 'kapanis_tarihi' => null]);
        unset($this->bulgular, $this->ozet);
    }

    /** Seçili bulguyu fotoğrafıyla DÖF Oluştur ekranına taşır. */
    public function dofeAktar(int $id)
    {
        $b = $this->bulgu($id);

        session(['dof_aktarim' => [
            'firma_id' => $b->firma_id,
            'kaynak' => 'Saha Bulgusu '.$b->bulgu_no,
            'maddeler' => [[
                'tespit' => trim(($b->bolum ? "[{$b->bolum}] " : '').$b->uygunsuzluk),
                'oncelik' => match (true) {
                    $b->skor() >= 16 => 'kritik',
                    $b->skor() >= 10 => 'yuksek',
                    $b->skor() >= 5 => 'orta',
                    default => 'dusuk',
                },
                'oneri' => $b->aksiyon,
                'sorumlu' => $b->sorumlu,
                'termin' => $b->termin?->toDateString(),
                'durum' => 'acik',
                'foto_yolu' => $b->fotograflar[0] ?? null,
            ]],
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
        unset($this->bulgular, $this->ozet);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->bulgular->isNotEmpty())
                ->action(fn () => SahaBulgusuUretici::excel($this->bulgular)),
        ];
    }
}
