<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Resources\Firmas\Pages\CreateFirma;
use App\Models\AdayFirma;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Pazarlama — aday firma CRM'i. Profilim sekmesinden sol menüye taşındı (30.10.2026); veri ve iş mantığı aynı.
 */
class Pazarlama extends Page
{
    use \App\Filament\Concerns\AileAnaSayfasi;
    use SinirliErisim;

    protected string $view = 'filament.pages.pazarlama';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 19;

    /** Menüde "Pazarlama & Talepler" tek girişi; Teklif Talepleri ve OSGB Başvuruları sekme şeridinden açılır ('pazarlama' ailesi). */
    protected static ?string $navigationLabel = 'Pazarlama & Talepler';

    protected static ?string $slug = 'pazarlama';

    protected static ?string $title = 'Pazarlama';

    public string $pazarlamaArama = '';

    public string $pazarlamaAsamaFiltre = '';

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * aday firma listesi (isgpratik 143.jpg).
     *
     * @return Collection<int, AdayFirma>
     */
    #[Computed]
    public function adayFirmalar()
    {
        return AdayFirma::query()
            ->where('user_id', Filament::auth()->id())
            ->when($this->pazarlamaArama, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('unvan', 'like', "%{$this->pazarlamaArama}%")
                ->orWhere('yetkili_ad', 'like', "%{$this->pazarlamaArama}%")
                ->orWhere('telefon', 'like', "%{$this->pazarlamaArama}%")))
            ->when($this->pazarlamaAsamaFiltre, fn ($q) => $q->where('asama', $this->pazarlamaAsamaFiltre))
            ->orderByDesc('created_at')
            ->get();
    }

    /** @return array{toplam: int, teklif: int, kazanilan: int, acik_hatirlatma: int} */
    #[Computed]
    public function pazarlamaOzeti(): array
    {
        $adaylar = AdayFirma::query()->where('user_id', Filament::auth()->id())->get();

        return [
            'toplam' => $adaylar->count(),
            'teklif' => $adaylar->where('asama', 'teklif')->count(),
            'kazanilan' => $adaylar->where('asama', 'kazanildi')->count(),
            'acik_hatirlatma' => $adaylar->filter->acikHatirlatmasiMi()->count(),
        ];
    }

    public function adayFirmaKaydetAction(): Action
    {
        $userId = Filament::auth()->id();

        return Action::make('adayFirmaKaydet')
            ->label('Aday Firma Ekle')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading(fn (array $arguments) => filled($arguments['id'] ?? null) ? 'Aday Firma Düzenle' : 'Aday Firma Ekle')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments) use ($userId): array {
                if (blank($arguments['id'] ?? null)) {
                    return ['asama' => 'aday'];
                }

                return AdayFirma::where('user_id', $userId)->findOrFail($arguments['id'])
                    ->only(['unvan', 'yetkili_ad', 'telefon', 'sehir', 'asama', 'hatirlatma_tarihi', 'notlar']);
            })
            ->schema([
                TextInput::make('unvan')->label('Firma / Kişi Adı')->required(),
                TextInput::make('yetkili_ad')->label('Yetkili'),
                TextInput::make('telefon')->label('Telefon')->tel(),
                TextInput::make('sehir')->label('Şehir'),
                Select::make('asama')->label('Aşama')
                    ->options(config('isg.pazarlama.asamalar'))->native(false)->required(),
                DatePicker::make('hatirlatma_tarihi')->label('Hatırlatma Tarihi')->native(false)->displayFormat('d.m.Y'),
                Textarea::make('notlar')->label('Notlar')->rows(2)->columnSpanFull(),
            ])
            ->action(function (array $data, array $arguments) use ($userId): void {
                $id = $arguments['id'] ?? null;

                if ($id) {
                    AdayFirma::where('user_id', $userId)->findOrFail($id)->update($data);
                } else {
                    AdayFirma::create($data + ['user_id' => $userId]);
                }

                unset($this->adayFirmalar, $this->pazarlamaOzeti);
                Notification::make()->title('Aday firma kaydedildi')->success()->send();
            });
    }

    public function adayFirmaSil(int $id): void
    {
        AdayFirma::where('user_id', Filament::auth()->id())->where('id', $id)->delete();

        unset($this->adayFirmalar, $this->pazarlamaOzeti);
        Notification::make()->title('Aday firma silindi')->success()->send();
    }

    /** "Kazanıldı" aşamasındaki adayı Firma oluşturma formuna ad/telefon/il ile aktarır. */
    public function adayFirmaDonustur(int $id)
    {
        $aday = AdayFirma::where('user_id', Filament::auth()->id())->findOrFail($id);

        session(['aday_firma_donusum' => [
            'unvan' => $aday->unvan,
            'telefon' => $aday->telefon,
            'il' => $aday->sehir,
        ]]);

        return redirect(CreateFirma::getUrl());
    }
}
