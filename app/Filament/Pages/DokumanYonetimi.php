<?php

namespace App\Filament\Pages;

use App\Models\ArsivDosya;
use App\Models\Firma;
use App\Support\ExcelBellek;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

/**
 * Doküman Yönetimi (isgsuite "Doküman Yönetimi"): işyeri dokümanlarının
 * kategori, başlık, dosya, açıklama, başlangıç / geçerlilik sonu, versiyon
 * ve aktif / pasif durumuyla kaydı. Profilim > Arşiv ile aynı tabloyu
 * (arsiv_dosyalari) kullanır; süresi dolan dokümanlar Bildirim Merkezi'ne
 * ve İşyeri Durum Merkezi'ne düşer.
 */
class DokumanYonetimi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.dokuman-yonetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 18;

    protected static ?string $slug = 'dokuman-yonetimi';

    protected static ?string $title = 'Doküman Yönetimi';

    protected static ?string $navigationLabel = 'Doküman Yönetimi';

    public ?int $firmaId = null;

    public string $kategori = '';

    public string $durum = '';

    public string $arama = '';

    public function mount(): void
    {
        $id = request()->integer('firma');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : null;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** @return Collection<int, ArsivDosya> */
    #[Computed]
    public function tumu(): Collection
    {
        return ArsivDosya::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->with('firma:id,unvan')
            ->latest('id')
            ->get();
    }

    /** @return Collection<int, ArsivDosya> */
    #[Computed]
    public function dokumanlar(): Collection
    {
        $aranan = mb_strtolower(trim($this->arama));

        return $this->tumu
            ->when($this->kategori !== '', fn ($c) => $c->filter(fn (ArsivDosya $d) => ($d->kategori ?: 'diger') === $this->kategori))
            ->when($this->durum === 'aktif', fn ($c) => $c->where('aktif', true))
            ->when($this->durum === 'pasif', fn ($c) => $c->where('aktif', false))
            ->when(in_array($this->durum, ['dolmus', 'yaklasan'], true), fn ($c) => $c->filter(fn (ArsivDosya $d) => $d->aktif && $d->gecerlilikDurumu() === $this->durum))
            ->when($aranan !== '', fn ($c) => $c->filter(fn (ArsivDosya $d) => str_contains(
                mb_strtolower(implode(' ', [$d->etiket(), $d->dosya_adi, $d->aciklama, $d->kategoriEtiketi(), $d->firma?->unvan, $d->versiyon])),
                $aranan,
            )))
            ->values();
    }

    #[Computed]
    public function ozet(): array
    {
        $aktif = $this->tumu->where('aktif', true);

        return [
            'aktif' => $aktif->count(),
            'dolmus' => $aktif->filter(fn (ArsivDosya $d) => $d->gecerlilikDurumu() === 'dolmus')->count(),
            'yaklasan' => $aktif->filter(fn (ArsivDosya $d) => $d->gecerlilikDurumu() === 'yaklasan')->count(),
            'pasif' => $this->tumu->where('aktif', false)->count(),
        ];
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'kategori', 'durum', 'arama'], true)) {
            unset($this->tumu, $this->dokumanlar, $this->ozet);
        }
    }

    private function bul(?int $id): ?ArsivDosya
    {
        return $id ? ArsivDosya::query()->whereIn('firma_id', array_keys($this->firmalar))->find($id) : null;
    }

    private function formSemasi(bool $yeni): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('firma_id')->label('Firma')->options($this->firmalar)->required()->searchable()->default($this->firmaId),
                Select::make('kategori')->label('Kategori')->options(config('isg.dokuman.kategoriler'))->required()->default('diger'),
                TextInput::make('baslik')->label('Doküman başlığı')->required()->maxLength(160),
                TextInput::make('versiyon')->label('Versiyon')->default('1.0')->maxLength(20),
                Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull(),
                DatePicker::make('baslangic_tarihi')->label('Başlangıç tarihi')->native(false)->displayFormat('d.m.Y'),
                DatePicker::make('gecerlilik_sonu')->label('Geçerlilik sonu')->native(false)->displayFormat('d.m.Y')->afterOrEqual('baslangic_tarihi'),
                FileUpload::make('dosya')
                    ->label($yeni ? 'Dosya' : 'Yeni dosya (yeni versiyon yüklemek için; boş bırakılırsa mevcut dosya korunur)')
                    ->disk('public')->directory('dokumanlar/'.now()->format('Y'))
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'image/jpeg', 'image/png', 'application/zip',
                    ])
                    ->maxSize(20480)
                    ->required($yeni)
                    ->columnSpanFull(),
            ]),
        ];
    }

    public function yeniAction(): Action
    {
        return Action::make('yeni')
            ->label('Yeni Doküman')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni doküman kaydı')
            ->modalSubmitActionLabel('Kaydet')
            ->schema(fn () => $this->formSemasi(true))
            ->action(function (array $data): void {
                if (! array_key_exists((int) $data['firma_id'], $this->firmalar)) {
                    return;
                }

                ArsivDosya::create([
                    'firma_id' => (int) $data['firma_id'],
                    'kategori' => $data['kategori'],
                    'baslik' => $data['baslik'],
                    'aciklama' => $data['aciklama'] ?? null,
                    'baslangic_tarihi' => $data['baslangic_tarihi'] ?? null,
                    'gecerlilik_sonu' => $data['gecerlilik_sonu'] ?? null,
                    'versiyon' => $data['versiyon'] ?? null,
                    'dosya_adi' => basename($data['dosya']),
                    'dosya_yolu' => $data['dosya'],
                    'boyut' => Storage::disk('public')->size($data['dosya']),
                    'aktif' => true,
                ]);

                unset($this->tumu, $this->dokumanlar, $this->ozet);
                Notification::make()->title('Doküman kaydedildi')->success()->send();
            });
    }

    public function duzenleAction(): Action
    {
        return Action::make('duzenle')
            ->modalHeading('Dokümanı düzenle')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments): array {
                $d = $this->bul($arguments['id'] ?? null);

                return $d ? [
                    'firma_id' => $d->firma_id, 'kategori' => $d->kategori ?: 'diger', 'baslik' => $d->etiket(),
                    'versiyon' => $d->versiyon, 'aciklama' => $d->aciklama,
                    'baslangic_tarihi' => $d->baslangic_tarihi?->toDateString(), 'gecerlilik_sonu' => $d->gecerlilik_sonu?->toDateString(),
                ] : [];
            })
            ->schema(fn () => $this->formSemasi(false))
            ->action(function (array $data, array $arguments): void {
                $d = $this->bul($arguments['id'] ?? null);

                if (! $d || ! array_key_exists((int) $data['firma_id'], $this->firmalar)) {
                    return;
                }

                $alanlar = collect($data)->only(['firma_id', 'kategori', 'baslik', 'aciklama', 'baslangic_tarihi', 'gecerlilik_sonu', 'versiyon'])->all();

                if (filled($data['dosya'] ?? null) && $data['dosya'] !== $d->dosya_yolu) {
                    Storage::disk('public')->delete($d->dosya_yolu);
                    $alanlar += [
                        'dosya_adi' => basename($data['dosya']),
                        'dosya_yolu' => $data['dosya'],
                        'boyut' => Storage::disk('public')->size($data['dosya']),
                    ];
                }

                $d->update($alanlar);
                unset($this->tumu, $this->dokumanlar, $this->ozet);
                Notification::make()->title('Doküman güncellendi')->success()->send();
            });
    }

    public function durumDegistir(int $id): void
    {
        $d = $this->bul($id);
        $d?->update(['aktif' => ! $d->aktif]);
        unset($this->tumu, $this->dokumanlar, $this->ozet);
    }

    public function sil(int $id): void
    {
        $d = $this->bul($id);

        if ($d) {
            Storage::disk('public')->delete($d->dosya_yolu);
            $d->delete();
        }

        unset($this->tumu, $this->dokumanlar, $this->ozet);
        Notification::make()->title('Doküman silindi')->success()->send();
    }

    public function indir(int $id)
    {
        $d = $this->bul($id);

        if (! $d || ! Storage::disk('public')->exists($d->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($d->dosya_yolu, $d->dosya_adi);
    }

    public function excelRapor()
    {
        ExcelBellek::artir();
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Dokümanlar');
        $s->fromArray(['Firma', 'Doküman', 'Kategori', 'Dosya adı', 'Versiyon', 'Başlangıç', 'Geçerlilik sonu', 'Durum', 'Açıklama', 'Boyut'], null, 'A1');
        $s->getStyle('A1:J1')->getFont()->setBold(true);
        $s->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E5F2');

        foreach ($this->dokumanlar->values() as $n => $d) {
            $s->fromArray([
                $d->firma?->unvan, $d->etiket(), $d->kategoriEtiketi(), $d->dosya_adi, $d->versiyon,
                $d->baslangic_tarihi?->format('d.m.Y'), $d->gecerlilik_sonu?->format('d.m.Y'), $d->durumEtiketi(), $d->aciklama, $d->boyutEtiketi(),
            ], null, 'A'.($n + 2));
        }
        foreach (range('A', 'J') as $c) {
            $s->getColumnDimension($c)->setAutoSize(true);
        }
        $s->freezePane('A2');

        $tmp = tempnam(sys_get_temp_dir(), 'dok').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'dokuman-raporu.xlsx');
    }
}
