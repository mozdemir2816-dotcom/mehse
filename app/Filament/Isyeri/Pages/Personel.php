<?php

namespace App\Filament\Isyeri\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsyeriHesabi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

/**
 * İşyeri paneli — Personel (13.11.2026): işveren kendi firmasına çalışan ekler, bilgilerini
 * düzeltir, işten çıkışını işler. Kayıt silinmez (eğitim / muayene geçmişi yasal kayıttır);
 * çıkış yapılan çalışan pasife alınır, gerekirse geri alınır. Firma her zaman oturumdaki
 * hesaptan gelir — istemciden firma id'si alınmaz.
 */
class Personel extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.isyeri.pages.personel';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'Personel';

    protected static ?string $slug = 'personel';

    protected static ?int $navigationSort = 2;

    #[Computed]
    public function firma(): Firma
    {
        /** @var IsyeriHesabi $hesap */
        $hesap = Filament::auth()->user();

        return Firma::findOrFail($hesap->firma_id);
    }

    private function sorgu(): Builder
    {
        return Calisan::query()->where('firma_id', $this->firma->id);
    }

    /** @return array<int, \Filament\Schemas\Components\Component> */
    private function formAlanlari(?Calisan $kayit = null): array
    {
        $firmaId = $this->firma->id;

        return [
            Grid::make(2)->schema([
                TextInput::make('ad_soyad')->label('Ad soyad')->required()->maxLength(255),
                TextInput::make('tc')->label('T.C. kimlik no')->required()->rule('digits:11')
                    ->validationMessages(['digits' => 'T.C. kimlik no 11 haneli olmalı.'])
                    ->rule(fn () => function (string $alan, $deger, \Closure $hata) use ($firmaId, $kayit): void {
                        $var = Calisan::query()->where('firma_id', $firmaId)->where('tc', $deger)->where('aktif', true)
                            ->when($kayit, fn ($q) => $q->whereKeyNot($kayit->getKey()))->exists();
                        if ($var) {
                            $hata('Bu T.C. kimlik no ile kayıtlı bir çalışanınız zaten var.');
                        }
                    }),
                TextInput::make('gorev')->label('Görevi')->required()->maxLength(255),
                DatePicker::make('ise_giris')->label('İşe giriş tarihi')->required()->native(false)->displayFormat('d.m.Y')
                    ->default(now())->maxDate(now()->addMonth()),
                TextInput::make('telefon')->label('Cep telefonu')->tel()->maxLength(30)
                    ->helperText('Online eğitim giriş bilgisi WhatsApp ile bu numaraya gönderilebilir.'),
                TextInput::make('eposta')->label('E-posta')->email()->maxLength(255)
                    ->helperText('Boşsa eğitim portalı için kullanıcı kodu üretilir.'),
            ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->sorgu())
            ->defaultSort('ad_soyad')
            ->columns([
                TextColumn::make('ad_soyad')->label('Ad soyad')->searchable()->sortable()->weight('bold'),
                TextColumn::make('tc')->label('T.C. kimlik no')->searchable()->placeholder('—'),
                TextColumn::make('gorev')->label('Görevi')->searchable()->placeholder('—'),
                TextColumn::make('ise_giris')->label('İşe giriş')->date('d.m.Y')->sortable()->placeholder('—'),
                TextColumn::make('telefon')->label('Telefon')->placeholder('—'),
                TextColumn::make('durum')->label('Durum')->badge()
                    ->state(fn (Calisan $c) => $c->aktif ? 'Çalışıyor' : 'Ayrıldı'.($c->isten_cikis ? ' · '.$c->isten_cikis->format('d.m.Y') : ''))
                    ->color(fn (Calisan $c) => $c->aktif ? 'success' : 'gray'),
            ])
            ->filters([
                TernaryFilter::make('aktif')->label('Personel')
                    ->placeholder('Tümü')->trueLabel('Çalışanlar')->falseLabel('Ayrılanlar')
                    ->default(true),
            ])
            ->headerActions([
                Action::make('ekle')->label('Personel ekle')->icon('heroicon-o-user-plus')
                    ->modalHeading('Personel ekle')
                    ->modalSubmitActionLabel('Kaydet')
                    ->schema($this->formAlanlari())
                    ->action(function (array $data): void {
                        Calisan::create([
                            ...$data,
                            'firma_id' => $this->firma->id,
                            'aktif' => true,
                            'notlar' => 'İşveren panelinden eklendi ('.now()->format('d.m.Y').').',
                        ]);
                        Notification::make()->title($data['ad_soyad'].' eklendi')
                            ->body('İSG uzmanınız işe giriş eğitimi ve sağlık muayenesi takibini bu kayıtla yapar.')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('duzenle')->label('Düzenle')->icon('heroicon-o-pencil-square')->color('gray')
                    ->visible(fn (Calisan $c) => $c->aktif)
                    ->modalHeading(fn (Calisan $c) => $c->ad_soyad)
                    ->modalSubmitActionLabel('Kaydet')
                    ->fillForm(fn (Calisan $c) => $c->only(['ad_soyad', 'tc', 'gorev', 'ise_giris', 'telefon', 'eposta']))
                    ->schema(fn (Calisan $c) => $this->formAlanlari($c))
                    ->action(function (Calisan $c, array $data): void {
                        $this->sorgu()->findOrFail($c->id)->update($data);
                        Notification::make()->title('Kaydedildi')->success()->send();
                    }),
                Action::make('cikis')->label('İşten çıkış')->icon('heroicon-o-arrow-right-start-on-rectangle')->color('danger')
                    ->visible(fn (Calisan $c) => $c->aktif)
                    ->modalHeading(fn (Calisan $c) => $c->ad_soyad.' — işten çıkış')
                    ->modalDescription('Çalışan listeden çıkarılır ve atanmış online eğitimleri durur. Eğitim ve muayene geçmişi silinmez.')
                    ->modalSubmitActionLabel('Çıkışı kaydet')
                    ->schema([
                        DatePicker::make('isten_cikis')->label('İşten çıkış tarihi')->required()->native(false)->displayFormat('d.m.Y')
                            ->default(now())->maxDate(now()->addMonth()),
                    ])
                    ->action(function (Calisan $c, array $data): void {
                        $this->sorgu()->findOrFail($c->id)->update(['aktif' => false, 'isten_cikis' => $data['isten_cikis']]);
                        Notification::make()->title($c->ad_soyad.' için işten çıkış kaydedildi')->success()->send();
                    }),
                Action::make('geriAl')->label('Geri al')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                    ->visible(fn (Calisan $c) => ! $c->aktif)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Calisan $c) => $c->ad_soyad.' tekrar çalışıyor mu?')
                    ->modalDescription('İşten çıkış kaydı kaldırılır ve çalışan listeye geri döner.')
                    ->action(function (Calisan $c): void {
                        $this->sorgu()->findOrFail($c->id)->update(['aktif' => true, 'isten_cikis' => null]);
                        Notification::make()->title($c->ad_soyad.' listeye geri alındı')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Kayıtlı personel yok')
            ->emptyStateDescription('"Personel ekle" ile çalışanlarınızı ekleyin; eğitim ve sağlık takibi bu listeyle yapılır.');
    }
}
