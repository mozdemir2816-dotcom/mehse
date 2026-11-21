<?php

namespace App\Filament\Pages;

use App\Models\EkipmanAfisi;
use App\Support\EkipmanAfisKutuphanesi as Kutuphane;
use App\Support\EkipmanKatalogu;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Ekipman Afişleri (21.11.2026) — makine tipi başına güvenlik afişi kütüphanesi.
 * Resimler ve YAPIN / YAPMAYIN / KKD yazıları Gemini sohbetinde üretilip toplu yüklenir
 * (App\Support\EkipmanAfisKutuphanesi); yükleme, düzenleme ve onay yalnız sahip hesapta.
 * Firmaya özel (logo + QR'lı) afiş Periyodik Kontrol'deki ekipman satırından basılır.
 */
class EkipmanAfisleri extends Page
{
    protected string $view = 'filament.pages.ekipman-afisleri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Günlük Saha İşi';

    /** Menüde gizli — Ekipman & Ölçüm sekme şeridinden açılır (App\Support\SayfaAileleri, 'ekipman'). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'ekipman-afisleri';

    protected static ?string $title = 'Ekipman Afişleri';

    public string $arama = '';

    public string $durum = '';

    public string $grup = '';

    public function mount(): void
    {
        Kutuphane::kayitlariHazirla();
    }

    public function yonetebilirMi(): bool
    {
        return (bool) auth()->user()?->sahipMi();
    }

    /** @return Collection<int, EkipmanAfisi> */
    public function afisler(): Collection
    {
        $sira = array_flip(array_keys(config('ekipman_afis.tipler', [])));
        $n = EkipmanKatalogu::norm($this->arama);

        return EkipmanAfisi::query()->get()
            ->sortBy(fn ($a) => $sira[$a->anahtar] ?? 9999)
            ->filter(fn ($a) => $n === '' || str_contains(EkipmanKatalogu::norm($a->ad), $n))
            ->filter(fn ($a) => $this->grup === '' || $a->grup === $this->grup)
            ->filter(fn ($a) => match ($this->durum) {
                'resim_yok' => ! $a->gorselVarMi(),
                'yazi_yok' => ! $a->yaziVarMi(),
                'onay' => $a->yaziVarMi() && $a->onayBekliyorMu(),
                'hazir' => $a->hazirMi() && ! $a->onayBekliyorMu(),
                default => true,
            })
            ->values();
    }

    /** @return array<string, int> */
    public function sayilar(): array
    {
        $hepsi = EkipmanAfisi::query()->get();

        return [
            'toplam' => $hepsi->count(),
            'resim' => $hepsi->filter->gorselVarMi()->count(),
            'yazi' => $hepsi->filter->yaziVarMi()->count(),
            'onay' => $hepsi->filter(fn ($a) => $a->yaziVarMi() && $a->onayBekliyorMu())->count(),
        ];
    }

    /** @return array<int, string> */
    public function gruplar(): array
    {
        return collect(config('ekipman_afis.tipler', []))->pluck('grup')->filter()->unique()->values()->all();
    }

    public function onizle(int $id): StreamedResponse
    {
        $afis = EkipmanAfisi::query()->findOrFail($id);
        $icerik = Kutuphane::pdfIcerik($afis);

        return response()->streamDownload(fn () => print ($icerik), Kutuphane::dosyaAdi($afis), ['Content-Type' => 'application/pdf']);
    }

    public function onayla(int $id): void
    {
        abort_unless($this->yonetebilirMi(), 403);
        EkipmanAfisi::query()->findOrFail($id)->forceFill(['onaylandi_at' => now(), 'onaylayan_id' => auth()->id()])->save();
        Notification::make()->title('Afiş onaylandı')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resimYukle')
                ->label('Resimleri yükle')
                ->icon('heroicon-o-photo')
                ->visible(fn () => $this->yonetebilirMi())
                ->modalDescription('Gemini\'den indirdiğiniz resimleri ya da hepsini içeren ZIP\'i seçin. Dosya adı makineyi belirler (ör. forklift.png); aynı makinenin eski resmi değişir.')
                ->schema([
                    FileUpload::make('dosyalar')
                        ->label('Resimler ya da ZIP')
                        ->multiple()
                        ->maxFiles(120)
                        ->disk('local')
                        ->directory('afis-yukleme')
                        ->preserveFilenames()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'application/zip', 'application/x-zip-compressed'])
                        ->maxSize(51200)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $yollar = array_map(fn ($y) => Storage::disk('local')->path($y), (array) $data['dosyalar']);
                    $sonuc = Kutuphane::gorselleriYukle($yollar);
                    Storage::disk('local')->delete((array) $data['dosyalar']);
                    $this->sonucBildir(count($sonuc['yuklenen']).' resim yüklendi', $sonuc['eslesmeyen'], 'Eşleşmeyen dosyalar');
                }),

            Action::make('yaziYukle')
                ->label('Yazıları yükle')
                ->icon('heroicon-o-document-text')
                ->visible(fn () => $this->yonetebilirMi())
                ->modalDescription('Gemini\'nin verdiği tabloyu (Dosya adı | Makine | YAPIN | YAPMAYIN | KKD) Excel olarak yükleyin ya da doğrudan aşağıya yapıştırın. Yazılar "uzman onayı bekliyor" etiketiyle gelir.')
                ->schema([
                    FileUpload::make('dosya')
                        ->label('Excel dosyası')
                        ->disk('local')
                        ->directory('afis-yukleme')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']),
                    Textarea::make('metin')
                        ->label('ya da Gemini tablosunu yapıştırın')
                        ->rows(8),
                ])
                ->action(function (array $data): void {
                    if (filled($data['dosya'] ?? null)) {
                        $sonuc = Kutuphane::yazilariIceAktar(Storage::disk('local')->path($data['dosya']));
                        Storage::disk('local')->delete($data['dosya']);
                    } elseif (filled($data['metin'] ?? null)) {
                        $sonuc = Kutuphane::metniIceAktar($data['metin']);
                    } else {
                        Notification::make()->title('Excel seçin ya da tabloyu yapıştırın')->warning()->send();

                        return;
                    }
                    $this->sonucBildir(count($sonuc['guncellenen']).' makinenin yazısı yüklendi', $sonuc['eslesmeyen'], 'Eşleşmeyen satırlar', $sonuc['atlanan_kkd']);
                }),
        ];
    }

    public function duzenleAction(): Action
    {
        return Action::make('duzenle')
            ->label('Düzenle')
            ->visible(fn () => $this->yonetebilirMi())
            ->modalHeading(fn (array $arguments) => EkipmanAfisi::query()->find($arguments['id'] ?? 0)?->ad)
            ->fillForm(function (array $arguments): array {
                $a = EkipmanAfisi::query()->findOrFail($arguments['id']);

                return ['yapin' => implode("\n", $a->yapin ?? []), 'yapmayin' => implode("\n", $a->yapmayin ?? []), 'kkd' => $a->kkd ?? []];
            })
            ->schema([
                Textarea::make('yapin')->label('Yapın (her satır bir madde)')->rows(6)->helperText('En fazla 8 madde; kısa tutun (60 karakter).'),
                Textarea::make('yapmayin')->label('Yapmayın (her satır bir madde)')->rows(5)->helperText('En fazla 6 madde.'),
                CheckboxList::make('kkd')->label('Zorunlu koruyucu donanım')->options(array_combine(array_keys(config('ekipman_afis.kkd')), array_keys(config('ekipman_afis.kkd'))))->columns(2),
                FileUpload::make('resim')->label('Resmi değiştir (isteğe bağlı)')->disk('local')->directory('afis-yukleme')->image()->maxSize(20480),
            ])
            ->action(function (array $data, array $arguments): void {
                $a = EkipmanAfisi::query()->findOrFail($arguments['id']);
                $satirlar = fn (?string $m, int $max) => array_slice(array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $m)))), 0, $max);
                $a->forceFill([
                    'yapin' => $satirlar($data['yapin'] ?? '', 8),
                    'yapmayin' => $satirlar($data['yapmayin'] ?? '', 6),
                    'kkd' => array_values($data['kkd'] ?? []),
                ])->save();

                if (filled($data['resim'] ?? null)) {
                    $yol = Storage::disk('local')->path($data['resim']);
                    $hedef = dirname($yol).'/'.$a->anahtar.'.'.strtolower(pathinfo($yol, PATHINFO_EXTENSION));
                    rename($yol, $hedef);
                    Kutuphane::gorselleriYukle([$hedef]);
                    @unlink($hedef);
                }
                Notification::make()->title('Afiş kaydedildi')->success()->send();
            });
    }

    /** @param  array<int, string>  $eslesmeyen */
    private function sonucBildir(string $baslik, array $eslesmeyen, string $eslesmeyenBaslik, array $atlananKkd = []): void
    {
        $govde = collect([
            $eslesmeyen ? $eslesmeyenBaslik.': '.implode(', ', array_slice($eslesmeyen, 0, 15)).(count($eslesmeyen) > 15 ? ' …' : '') : null,
            $atlananKkd ? 'Listede olmayan KKD (atlandı): '.implode(', ', $atlananKkd) : null,
        ])->filter()->implode("\n");

        Notification::make()->title($baslik)->body($govde ?: null)
            ->{$eslesmeyen || $atlananKkd ? 'warning' : 'success'}()
            ->persistent(filled($govde))
            ->send();
    }
}
