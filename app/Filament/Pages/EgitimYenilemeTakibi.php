<?php

namespace App\Filament\Pages;

use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Support\EgitimTakibi;
use App\Support\ExcelBellek;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

/**
 * Eğitim Takibi (isgsuite Eğitimler → "Bugün ne yapmalıyım" + "Yenileme
 * Takibi" + "Yüz Yüze Kayıtlar"): çalışan bazında temel İSG eğitimi durumu
 * (kayıt yok / süresi dolmuş / yaklaşan / geçerli), işbaşı eğitimi eksikleri
 * ve tüm firmaların yüz yüze eğitim oturumları. Kayıt üretmez, yönlendirir.
 */
class EgitimYenilemeTakibi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.egitim-yenileme-takibi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    protected static ?int $navigationSort = 14;

    protected static ?string $slug = 'egitim-takibi';

    protected static ?string $title = 'Eğitim Takibi — Yenileme ve Kayıtlar';

    protected static ?string $navigationLabel = 'Eğitim Takibi (Yenileme)';

    public string $sekme = 'yenileme';

    public ?int $firmaId = null;

    public ?string $durumFiltre = null;

    public ?string $arama = null;

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

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    #[Computed]
    public function satirlar(): Collection
    {
        return $this->firma ? EgitimTakibi::satirlar($this->firma) : collect();
    }

    #[Computed]
    public function ozet(): array
    {
        return EgitimTakibi::ozet($this->satirlar);
    }

    #[Computed]
    public function gosterilenSatirlar(): Collection
    {
        $aranan = EgitimTakibi::adAnahtari($this->arama);

        return $this->satirlar
            ->when($this->durumFiltre === 'isbasi_eksik', fn ($c) => $c->where('isbasi', false))
            ->when($this->durumFiltre && $this->durumFiltre !== 'isbasi_eksik', fn ($c) => $c->where('durum', $this->durumFiltre))
            ->when($aranan !== '', fn ($c) => $c->filter(fn (array $s) => str_contains(
                EgitimTakibi::adAnahtari(implode(' ', [$s['calisan']->ad_soyad, $s['calisan']->gorev, $s['calisan']->departman])),
                $aranan,
            )))
            ->values();
    }

    /** @return Collection<int, EgitimKatilim> yüz yüze oturumlar (seçili firma ya da tümü) */
    #[Computed]
    public function oturumlar(): Collection
    {
        $aranan = EgitimTakibi::adAnahtari($this->arama);

        return EgitimKatilim::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->with('firma')
            ->latest('belge_tarihi')
            ->latest('id')
            ->get()
            ->when($aranan !== '', fn ($c) => $c->filter(fn (EgitimKatilim $k) => str_contains(
                EgitimTakibi::adAnahtari(implode(' ', [$k->basliklarEtiketi(), $k->firma?->unvan, $k->egitim_yeri, $k->belge_no])),
                $aranan,
            )))
            ->values();
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'durumFiltre', 'arama'], true)) {
            unset($this->firma, $this->satirlar, $this->ozet, $this->gosterilenSatirlar, $this->oturumlar);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('takip_excel')
                ->label('Takip Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->sekme === 'yenileme' && $this->satirlar->isNotEmpty())
                ->action(fn () => $this->excel(
                    'Yenileme Takibi',
                    ['Personel', 'Görev', 'Bölüm', 'Son Temel Eğitim', 'Kaynak', 'Yenileme Tarihi', 'Kalan Gün', 'Durum', 'İşbaşı Eğitimi'],
                    $this->gosterilenSatirlar->map(fn (array $s) => [
                        $s['calisan']->ad_soyad,
                        $s['calisan']->gorev,
                        $s['calisan']->departman,
                        $s['son_egitim']?->format('d.m.Y'),
                        $s['kaynak'],
                        $s['yenileme']?->format('d.m.Y'),
                        $s['kalan_gun'],
                        static::durumEtiketi($s['durum']),
                        $s['isbasi'] ? 'Var' : 'Eksik',
                    ])->all(),
                    'egitim-yenileme-'.Str::slug($this->firma?->unvan ?? 'firma'),
                )),

            Action::make('oturum_excel')
                ->label('Kayıtlar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->sekme === 'kayitlar' && $this->oturumlar->isNotEmpty())
                ->action(fn () => $this->excel(
                    'Yüz Yüze Kayıtlar',
                    ['Eğitim', 'Firma', 'Tarih', 'Tehlike Sınıfı', 'Gün', 'Katılımcı', 'Eğitim Yeri', 'Belge No'],
                    $this->oturumlar->map(fn (EgitimKatilim $k) => [
                        $k->basliklarEtiketi(),
                        $k->firma?->unvan,
                        EgitimTakibi::katilimTarihi($k)?->format('d.m.Y'),
                        $k->firma?->tehlike_sinifi ? $k->firma->tehlikeSinifiEtiketi() : null,
                        $k->sure_gun,
                        $k->katilimciSayisi(),
                        $k->egitim_yeri,
                        $k->belge_no,
                    ])->all(),
                    'yuz-yuze-egitim-kayitlari',
                )),
        ];
    }

    /**
     * Planla → Sonuçlandır: oturumdaki her katılımcı için "katıldı" ve
     * (varsa) sınav puanı. Katılmayan ya da geçme puanının altında kalan
     * sertifika almaz, yenileme takibinde eğitimli sayılmaz.
     */
    public function sonucGirAction(): Action
    {
        $oturum = fn (array $arguments): ?EgitimKatilim => EgitimKatilim::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->find($arguments['id'] ?? null);

        return Action::make('sonucGir')
            ->hidden(fn (array $arguments) => ! $oturum($arguments))
            ->modalHeading(fn (array $arguments) => 'Katılım / Sonuç — '.($oturum($arguments)?->basliklarEtiketi() ?? ''))
            ->modalDescription('Geçme puanı '.EgitimKatilim::gecmePuani().'. Puan boş bırakılırsa yalnız katılım esas alınır.')
            ->modalSubmitActionLabel('Sonuçlandır')
            ->fillForm(fn (array $arguments) => collect($oturum($arguments)?->katilimcilar ?? [])
                ->flatMap(fn (array $k, int $i) => ["katildi_{$i}" => $k['katildi'] ?? true, "puan_{$i}" => $k['puan'] ?? null])
                ->all())
            ->schema(fn (array $arguments) => collect($oturum($arguments)?->katilimcilar ?? [])
                ->map(fn (array $k, int $i) => Grid::make(3)->schema([
                    TextEntry::make("kisi_{$i}")->label("Katılımcı")->state(trim(($k['ad_soyad'] ?? '—').' '.(filled($k['gorev'] ?? null) ? '· '.$k['gorev'] : ''))),
                    Toggle::make("katildi_{$i}")->label('Katıldı')->inline(false),
                    TextInput::make("puan_{$i}")->label('Puan')->numeric()->minValue(0)->maxValue(100)->nullable(),
                ]))
                ->all())
            ->action(function (array $data, array $arguments) use ($oturum): void {
                $kayit = $oturum($arguments);
                if (! $kayit) {
                    return;
                }

                $kayit->katilimcilar = collect($kayit->katilimcilar ?? [])
                    ->map(fn (array $k, int $i) => [
                        ...$k,
                        'katildi' => (bool) ($data["katildi_{$i}"] ?? false),
                        'puan' => filled($data["puan_{$i}"] ?? null) ? (int) $data["puan_{$i}"] : null,
                    ])
                    ->all();
                $kayit->save();
                unset($this->oturumlar, $this->satirlar, $this->ozet, $this->gosterilenSatirlar);

                Notification::make()
                    ->title('Sonuçlandı: '.count($kayit->belgeAlacakKatilimcilar()).'/'.$kayit->katilimciSayisi().' başarılı')
                    ->body('Sertifika yalnız başarılı katılımcılar için üretilir.')
                    ->success()
                    ->send();
            });
    }

    public static function durumEtiketi(string $durum): string
    {
        return [
            'kayit_yok' => 'Eğitim kaydı yok',
            'dolmus' => 'Süresi dolmuş',
            'yaklasan' => 'Yenileme yaklaşıyor',
            'gecerli' => 'Geçerli',
        ][$durum] ?? $durum;
    }

    private function excel(string $sayfaAdi, array $basliklar, array $satirlar, string $dosya)
    {
        ExcelBellek::artir();

        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle($sayfaAdi);
        $son = Coordinate::stringFromColumnIndex(count($basliklar));
        $s->fromArray($basliklar, null, 'A1');
        $s->getStyle("A1:{$son}1")->getFont()->setBold(true);
        $s->getStyle("A1:{$son}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');
        foreach (array_values($satirlar) as $i => $satir) {
            $s->fromArray($satir, null, 'A'.($i + 2));
        }
        foreach (range(1, count($basliklar)) as $i) {
            $s->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'egt').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, $dosya.'.xlsx');
    }
}
