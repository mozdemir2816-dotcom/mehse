<?php

namespace App\Filament\Pages;

use App\Models\EgitimPaketi;
use App\Models\EgitimPaketiSorusu;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Online Eğitim Yönet > Sınav Soruları (08.11.2026): paket seçilir, sınav soruları havuzu
 * tek ekranda yönetilir. Her soru "sınav öncesi (ön test)", "sınav sonrası (final)" ya da
 * ikisinde birden sorulur; çalışana her sınavda havuzdan rastgele 10 soru çıkar
 * (config isg.uzaktan_egitim.on_test_soru_sayisi / final_soru_sayisi).
 */
class UzaktanEgitimSorulari extends Page implements HasTable
{
    use \App\Filament\Concerns\SinirliErisim;
    use InteractsWithTable;

    protected string $view = 'filament.pages.uzaktan-egitim-sorulari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    /** Menüde gizli — Online Eğitim Yönet sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'uzaktan-egitim-sorulari';

    protected static ?string $title = 'Sınav Soruları';

    #[Url(as: 'paket')]
    public ?int $paketId = null;

    public function mount(): void
    {
        if (! $this->paketId || ! array_key_exists($this->paketId, $this->paketler)) {
            $this->paketId = array_key_first($this->paketler);
        }
    }

    #[Computed]
    public function paketler(): array
    {
        return EgitimPaketi::query()->gorunur(Filament::auth()->id())
            ->orderBy('ad')->pluck('ad', 'id')->all();
    }

    #[Computed]
    public function paket(): ?EgitimPaketi
    {
        return $this->paketId ? EgitimPaketi::query()->gorunur(Filament::auth()->id())->find($this->paketId) : null;
    }

    public function updatedPaketId(): void
    {
        unset($this->paket, $this->ozet);
        $this->resetTable();
    }

    /**
     * Havuz özeti: sınav öncesi / sonrası sınavlarda kaç soru arasından seçim yapılacağı.
     *
     * @return array{on_test: int, final: int, on_test_gereken: int, final_gereken: int, toplam: int}
     */
    #[Computed]
    public function ozet(): array
    {
        $sayilar = $this->paket?->sorular()->selectRaw('kullanim, count(*) as adet')->groupBy('kullanim')->pluck('adet', 'kullanim') ?? collect();

        return [
            'on_test' => (int) ($sayilar['ortak'] ?? 0) + (int) ($sayilar['on_test'] ?? 0),
            'final' => (int) ($sayilar['ortak'] ?? 0) + (int) ($sayilar['final'] ?? 0),
            'on_test_gereken' => (int) config('isg.uzaktan_egitim.on_test_soru_sayisi', 10),
            'final_gereken' => (int) config('isg.uzaktan_egitim.final_soru_sayisi', 10),
            'toplam' => (int) $sayilar->sum(),
            'konular' => $this->paket?->sorular()->whereIn('kullanim', ['ortak', 'final'])
                ->selectRaw('COALESCE(modul, 0) as k, count(*) as adet')->groupBy('k')->pluck('adet', 'k')->all() ?? [],
        ];
    }

    public function ozetiTazele(): void
    {
        unset($this->ozet);
    }

    private function soruFormu(): array
    {
        return [
            Select::make('kullanim')->label('Hangi sınavda sorulur')
                ->options(EgitimPaketiSorusu::KULLANIMLAR)->default('ortak')->required()->native(false),
            Select::make('modul')->label('Konu başlığı (Ek-1)')
                ->options(EgitimPaketiSorusu::KONULAR)->placeholder('Belirtilmedi')->native(false)
                ->helperText('Final sınavında sorular Genel / Sağlık / Teknik konulara dengeli dağıtılır.'),
            Select::make('zorluk')->label('Zorluk')
                ->options(EgitimPaketiSorusu::ZORLUKLAR)->placeholder('Belirtilmedi')->native(false),
            Textarea::make('soru')->label('Soru metni')->rows(2)->required()->columnSpanFull(),
            Repeater::make('secenekler')->label('Şıklar')
                ->simple(TextInput::make('secenek')->required())
                ->minItems(2)->maxItems(5)->defaultItems(4)->columnSpanFull(),
            Radio::make('dogru_index')->label('Doğru şık')
                ->options([0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E'])
                ->inline()->required()->default(0),
            Textarea::make('aciklama')->label('Doğru cevabın gerekçesi (opsiyonel)')->rows(2)->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => EgitimPaketiSorusu::query()
                ->where('egitim_paketi_id', $this->paket?->id ?? 0)
                ->orderBy('id'))
            ->heading(fn () => $this->paket ? 'Soru havuzu — '.$this->paket->ad : 'Önce bir paket seçin')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Bu pakette henüz soru yok')
            ->emptyStateDescription('"Soru Ekle" ile sınav öncesi ve sınav sonrası sorularını ekleyin.')
            ->columns([
                TextColumn::make('sira')->label('#')->rowIndex(),
                TextColumn::make('soru')->label('Soru')->wrap()->searchable()
                    ->description(fn (EgitimPaketiSorusu $s) => 'Doğru: '.chr(65 + (int) $s->dogru_index).') '.$s->dogruSecenek()),
                SelectColumn::make('kullanim')->label('Hangi sınavda')
                    ->options(EgitimPaketiSorusu::KULLANIMLAR)->selectablePlaceholder(false)
                    ->afterStateUpdated(fn () => $this->ozetiTazele()),
                SelectColumn::make('modul')->label('Konu')
                    ->options(EgitimPaketiSorusu::KONULAR)->placeholder('—')
                    ->afterStateUpdated(fn () => $this->ozetiTazele()),
                SelectColumn::make('zorluk')->label('Zorluk')
                    ->options(EgitimPaketiSorusu::ZORLUKLAR)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('kullanim')->label('Sınav')->options(EgitimPaketiSorusu::KULLANIMLAR),
                SelectFilter::make('modul')->label('Konu')->options(EgitimPaketiSorusu::KONULAR),
                SelectFilter::make('zorluk')->label('Zorluk')->options(EgitimPaketiSorusu::ZORLUKLAR),
            ])
            ->headerActions([
                CreateAction::make()->label('Soru Ekle')->icon('heroicon-o-plus')
                    ->visible(fn () => $this->paket !== null)
                    ->schema($this->soruFormu())
                    ->using(fn (array $data) => $this->paket->sorular()->create($data))
                    ->after(fn () => $this->ozetiTazele())
                    ->createAnother(true),
            ])
            ->recordActions([
                EditAction::make()->label('Düzenle')->schema($this->soruFormu()),
                DeleteAction::make()->label('Sil')->after(fn () => $this->ozetiTazele()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->after(fn () => $this->ozetiTazele()),
            ]);
    }
}
