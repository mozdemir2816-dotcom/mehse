<?php

namespace App\Filament\Pages;

use App\Models\AcilEkip;
use App\Models\AcilEkipUyesi;
use App\Models\Calisan;
use App\Models\Firma;
use App\Support\AcilEkipDurumu;
use App\Support\AcilEkipUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Acil Durum Ekipleri / Destek Elemanları (isgsuite acil_ekipler):
 * söndürme, kurtarma, koruma, ilk yardım, tahliye ve haberleşme ekipleri;
 * asıl / yedek üye, lider, vardiya, eğitim belgesi ve geçerlilik takibi;
 * yasal asgari sayıya göre ekip yeterliliği, kontrol önerileri, üye
 * filtreleri, Excel / PDF ve silinenleri geri alma. Üyesi girilen ekip
 * Acil Durum Planı'na da yansır.
 */
class AcilDurumEkipleri extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.acil-durum-ekipleri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Acil Durum & Yangın';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'acil-durum-ekipleri';

    protected static ?string $title = 'Acil Durum Ekipleri / Destek Elemanları';

    protected static ?string $navigationLabel = 'Acil Durum Ekipleri';

    public ?int $firmaId = null;

    public string $arama = '';

    public string $ekipFiltre = '';

    public string $uyelikFiltre = '';

    public string $belgeFiltre = '';

    public string $vardiyaFiltre = '';

    public bool $silinenlerAcik = false;

    public function mount(): void
    {
        $id = request()->integer('firma');
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : array_key_first($this->firmalar);
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    /** @return Collection<int, AcilEkip> */
    #[Computed]
    public function ekipler(): Collection
    {
        return $this->firma ? AcilEkipDurumu::ekipler($this->firma) : collect();
    }

    #[Computed]
    public function ozet(): ?array
    {
        return $this->firma ? AcilEkipDurumu::ozet($this->firma, $this->ekipler) : null;
    }

    /** @return Collection<int, AcilEkipUyesi> */
    #[Computed]
    public function uyeler(): Collection
    {
        $q = mb_strtolower(trim($this->arama));

        return $this->ekipler
            ->flatMap(fn (AcilEkip $e) => $e->uyeler->each(fn ($u) => $u->setRelation('ekip', $e)))
            ->filter(fn (AcilEkipUyesi $u) => ($this->ekipFiltre === '' || (string) $u->acil_ekip_id === $this->ekipFiltre)
                && ($this->uyelikFiltre === '' || $u->uyelik === $this->uyelikFiltre)
                && ($this->belgeFiltre === '' || $u->belgeDurumu() === $this->belgeFiltre)
                && ($this->vardiyaFiltre === '' || $u->vardiya === $this->vardiyaFiltre)
                && ($q === '' || str_contains(mb_strtolower(implode(' ', [$u->ad_soyad, $u->gorev, $u->bolum, $u->sicil_no])), $q)))
            ->values();
    }

    #[Computed]
    public function silinenler(): array
    {
        if (! $this->firma) {
            return ['ekipler' => collect(), 'uyeler' => collect()];
        }

        $ekipIdleri = AcilEkip::withTrashed()->where('firma_id', $this->firma->id)->pluck('id');

        return [
            'ekipler' => AcilEkip::onlyTrashed()->where('firma_id', $this->firma->id)->latest('deleted_at')->get(),
            'uyeler' => AcilEkipUyesi::onlyTrashed()->whereIn('acil_ekip_id', $ekipIdleri)->with('ekip')->latest('deleted_at')->get(),
        ];
    }

    public function updated(string $alan): void
    {
        if ($alan === 'firmaId') {
            $this->ekipFiltre = '';
            unset($this->firma);
        }

        $this->yenile();
    }

    private function yenile(): void
    {
        unset($this->ekipler, $this->ozet, $this->uyeler, $this->silinenler);
    }

    private function ekip(int $id): AcilEkip
    {
        return AcilEkip::query()->where('firma_id', $this->firma?->id)->findOrFail($id);
    }

    private function uye(int $id): AcilEkipUyesi
    {
        return AcilEkipUyesi::query()->whereHas('ekip', fn ($q) => $q->where('firma_id', $this->firma?->id))->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Ekip
    |--------------------------------------------------------------------------
    */

    /** @return array<int, mixed> */
    private function ekipSemasi(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('tur')->label('Ekip türü')->required()->native(false)
                    ->options(collect(config('isg.acil_durum.ekip_turleri'))->map(fn ($t) => $t['ad'])->all())
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $state ? $set('ad', config('isg.acil_durum.ekip_turleri.'.$state.'.ad')) : null),
                TextInput::make('min_uye')->label('Minimum üye')->numeric()->integer()->minValue(1)->maxValue(500)
                    ->placeholder('Boş: yasal orandan')
                    ->helperText('Boş bırakılırsa tehlike sınıfı ve çalışan sayısına göre hesaplanır.'),
            ]),
            TextInput::make('ad')->label('Ekip adı')->required()->maxLength(255),
            Textarea::make('notlar')->label('Notlar')->rows(2),
        ];
    }

    public function yeniEkipAction(): Action
    {
        return Action::make('yeniEkip')
            ->label('Yeni Ekip')
            ->modalHeading('Yeni Acil Durum Ekibi')
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel('Kaydet')
            ->schema(fn () => $this->ekipSemasi())
            ->action(function (array $data): void {
                AcilEkip::create([...$data, 'firma_id' => $this->firma->id]);
                $this->yenile();
                Notification::make()->success()->title('Ekip eklendi')->send();
            });
    }

    public function ekipDuzenleAction(): Action
    {
        return Action::make('ekipDuzenle')
            ->modalHeading('Ekibi Düzenle')
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn (array $arguments) => $this->ekip($arguments['id'])->only(['tur', 'ad', 'min_uye', 'notlar']))
            ->schema(fn () => $this->ekipSemasi())
            ->action(function (array $data, array $arguments): void {
                $this->ekip($arguments['id'])->update($data);
                $this->yenile();
                Notification::make()->success()->title('Ekip güncellendi')->send();
            });
    }

    public function temelEkipler(): void
    {
        $n = $this->firma ? AcilEkipDurumu::temelEkipleriOlustur($this->firma) : 0;
        $this->yenile();

        Notification::make()->success()->title($n ? "{$n} temel ekip oluşturuldu" : 'Temel ekiplerin hepsi zaten var')->send();
    }

    public function ekipSil(int $id): void
    {
        $this->ekip($id)->delete();
        $this->yenile();

        Notification::make()->success()->title('Ekip silindi')->body('"Silinenleri Geri Al" ile geri alınabilir.')->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Üye (destek elemanı)
    |--------------------------------------------------------------------------
    */

    /** @return array<int, mixed> */
    private function uyeSemasi(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('acil_ekip_id')->label('Ekip')->required()->native(false)
                    ->options(fn () => $this->ekipler->pluck('ad', 'id')->all()),
                Select::make('calisan_id')->label('Personel (kayıtlı çalışan)')->searchable()
                    ->options(fn () => $this->firma?->calisanlar()->where('aktif', true)->orderBy('ad_soyad')->pluck('ad_soyad', 'id')->all() ?? [])
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($c = $state ? Calisan::find($state) : null) {
                            $set('ad_soyad', $c->ad_soyad);
                            $set('gorev', $c->gorev);
                            $set('bolum', $c->sube ?: $c->departman);
                            $set('telefon', $c->telefon);
                        }
                    }),
                TextInput::make('ad_soyad')->label('Ad Soyad')->required()->maxLength(255),
                TextInput::make('gorev')->label('Görev')->maxLength(255),
                TextInput::make('bolum')->label('Bölüm')->maxLength(255),
                TextInput::make('sicil_no')->label('Sicil no')->maxLength(255),
                TextInput::make('telefon')->label('Telefon')->tel()->maxLength(255),
                Select::make('vardiya')->label('Vardiya')->native(false)
                    ->options(array_combine(config('isg.acil_durum.vardiyalar'), config('isg.acil_durum.vardiyalar'))),
                Select::make('uyelik')->label('Üyelik')->required()->native(false)->default('asil')
                    ->options(['asil' => 'Asıl', 'yedek' => 'Yedek']),
                Toggle::make('lider')->label('Ekip lideri')->inline(false),
                TextInput::make('belge_no')->label('Eğitim belgesi / sertifika no')->maxLength(255),
                DatePicker::make('belge_tarihi')->label('Belge tarihi'),
                DatePicker::make('belge_bitis')->label('Belge geçerlilik sonu')
                    ->helperText('Boşsa belge tarihinden hesaplanır (destek elemanı 1 yıl, ilkyardımcı 3 yıl).'),
            ]),
        ];
    }

    private function uyeKaydet(array $data, ?AcilEkipUyesi $uye = null): void
    {
        $ekip = $this->ekip((int) $data['acil_ekip_id']);
        $data['calisan_id'] = $data['calisan_id'] ?? null;

        $uye ? $uye->update($data) : $uye = AcilEkipUyesi::create($data);

        // Ekipte tek lider
        if ($uye->lider) {
            $ekip->uyeler()->whereKeyNot($uye->id)->update(['lider' => false]);
        }

        $this->yenile();
    }

    public function uyeEkleAction(): Action
    {
        return Action::make('uyeEkle')
            ->label('Destek Elemanı Ekle')
            ->modalHeading('Destek Elemanı Ekle')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn (array $arguments) => ['acil_ekip_id' => $arguments['ekip'] ?? $this->ekipler->first()?->id, 'uyelik' => 'asil'])
            ->schema(fn () => $this->uyeSemasi())
            ->action(function (array $data): void {
                $this->uyeKaydet($data);
                Notification::make()->success()->title('Destek elemanı eklendi')->send();
            });
    }

    public function uyeDuzenleAction(): Action
    {
        return Action::make('uyeDuzenle')
            ->modalHeading('Destek Elemanını Düzenle')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments): array {
                $u = $this->uye($arguments['id']);

                return [...$u->only(['acil_ekip_id', 'calisan_id', 'ad_soyad', 'gorev', 'bolum', 'sicil_no', 'telefon', 'vardiya', 'uyelik', 'lider', 'belge_no']),
                    'belge_tarihi' => $u->belge_tarihi?->toDateString(), 'belge_bitis' => $u->belge_bitis?->toDateString()];
            })
            ->schema(fn () => $this->uyeSemasi())
            ->action(function (array $data, array $arguments): void {
                $this->uyeKaydet($data, $this->uye($arguments['id']));
                Notification::make()->success()->title('Destek elemanı güncellendi')->send();
            });
    }

    public function uyeSil(int $id): void
    {
        $this->uye($id)->delete();
        $this->yenile();
    }

    public function geriAl(string $tur, int $id): void
    {
        $sinif = $tur === 'ekip' ? AcilEkip::class : AcilEkipUyesi::class;
        $kayit = $sinif::onlyTrashed()->find($id);

        $sahip = $kayit instanceof AcilEkip ? $kayit->firma_id : AcilEkip::withTrashed()->find($kayit?->acil_ekip_id)?->firma_id;

        if (! $kayit || $sahip !== $this->firma?->id) {
            return;
        }

        $kayit->restore();
        $this->yenile();

        Notification::make()->success()->title('Geri alındı')->send();
    }

    public function filtreTemizle(): void
    {
        $this->reset('arama', 'ekipFiltre', 'uyelikFiltre', 'belgeFiltre', 'vardiyaFiltre');
        $this->yenile();
    }

    public function excel()
    {
        return $this->firma ? AcilEkipUretici::excel($this->firma, $this->ekipler) : null;
    }

    public function pdf()
    {
        return $this->firma ? AcilEkipUretici::pdf($this->firma, $this->ekipler) : null;
    }
}
