<?php

namespace App\Filament\Pages;

use App\Models\Basvuru;
use App\Models\User;
use App\Support\BasvuruIslemleri;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Kayıt / başvuru kuyruğu — yalnız sahip hesap. OSGB başvurularını onaylar
 * (hesap + deneme açılır, geçici şifre bir kez gösterilir) ya da reddeder;
 * bireysel İGU kayıtları bilgi amaçlı (hesap zaten açık, yasal onay izi).
 * SinirliErisim KASITLI kullanılmıyor: KullaniciYonetimi gibi sahipMi().
 */
class Basvurular extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.basvurular';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'basvurular';

    protected static ?string $title = 'Başvurular';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->sahipMi();
    }

    public static function getNavigationBadge(): ?string
    {
        $sayi = Basvuru::query()->where('durum', 'beklemede')->count();

        return $sayi ? (string) $sayi : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Basvuru::query()->latest())
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('tip')->label('Tür')->badge()
                    ->formatStateUsing(fn (string $state): string => Basvuru::TIPLER[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'osgb' ? 'info' : 'gray'),
                TextColumn::make('baslik')->label('OSGB / kişi')
                    ->state(fn (Basvuru $b): string => $b->baslik())
                    ->description(fn (Basvuru $b): ?string => $b->tip === 'osgb' ? $b->ad_soyad : null)
                    ->searchable(['osgb_adi', 'ad_soyad']),
                TextColumn::make('eposta')->label('E-posta')->searchable()->copyable(),
                TextColumn::make('telefon')->label('Telefon')->placeholder('—'),
                TextColumn::make('durum')->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state): string => Basvuru::DURUMLAR[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'beklemede' => 'warning',
                        'onaylandi' => 'success',
                        default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('tip')->label('Tür')->options(Basvuru::TIPLER),
                SelectFilter::make('durum')->label('Durum')->options(Basvuru::DURUMLAR),
            ])
            ->recordActions([
                Action::make('detay')
                    ->label('Detay')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Basvuru $b): string => $b->tipEtiketi().' — '.$b->baslik())
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat')
                    ->schema(fn (Basvuru $b): array => [
                        Grid::make(2)->schema(array_values(array_filter([
                            $b->tip === 'osgb' ? TextEntry::make('osgb_adi')->label('OSGB adı') : null,
                            $b->tip === 'osgb' ? TextEntry::make('yetki_no')->label('Yetki no') : null,
                            $b->tip === 'osgb' ? TextEntry::make('vergi_no')->label('Vergi no') : null,
                            $b->tip === 'osgb' ? TextEntry::make('sorumlu_mudur')->label('Sorumlu müdür')->placeholder('—') : null,
                            $b->tip === 'osgb' ? TextEntry::make('iletisim_eposta')->label('İletişim e-posta') : null,
                            TextEntry::make('telefon')->label('Telefon')->placeholder('—'),
                            TextEntry::make('ad_soyad')->label($b->tip === 'osgb' ? 'Başvuran' : 'Ad soyad'),
                            TextEntry::make('eposta')->label('E-posta'),
                            $b->tip === 'osgb' ? TextEntry::make('adres')->label('Adres')->placeholder('—')->columnSpanFull() : null,
                            $b->tip === 'osgb' ? TextEntry::make('not')->label('Not')->placeholder('—')->columnSpanFull() : null,
                            TextEntry::make('onay_izi')->label('Yasal onaylar')->columnSpanFull()
                                ->state(fn (): string => collect($b->onaylar ?? [])
                                    ->map(fn (array $o): string => $o['baslik'].' (rev. '.($o['revizyon'] ?? '—').') — '
                                        .\Illuminate\Support\Carbon::parse($o['onay_at'])->format('d.m.Y H:i'))
                                    ->implode(' · ') ?: 'Kayıt sırasında etkin yasal metin yoktu.'),
                            TextEntry::make('ip')->label('IP')->placeholder('—'),
                            TextEntry::make('red_nedeni')->label('Red nedeni')->visible($b->durum === 'reddedildi')->columnSpanFull(),
                        ]))),
                    ]),

                Action::make('onayla')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Basvuru $b): bool => $b->tip === 'osgb' && $b->durum === 'beklemede')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Basvuru $b): string => $b->baslik().' onaylansın mı?')
                    ->modalDescription(fn (Basvuru $b): string => "{$b->eposta} adına hesap açılacak ve "
                        .config('isg.kayit.osgb_deneme_gun', 90).' günlük deneme başlayacak. Geçici şifre bir sonraki ekranda bir kez gösterilir.')
                    ->action(function (Basvuru $b): void {
                        /** @var User $sahip */
                        $sahip = Filament::auth()->user();

                        try {
                            ['user' => $user, 'sifre' => $sifre] = BasvuruIslemleri::onayla($b, $sahip);
                        } catch (ValidationException $e) {
                            Notification::make()->title('Onaylanamadı')->body(collect($e->errors())->flatten()->first())->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title('Hesap açıldı — '.$user->name)
                            ->body("E-posta: {$user->email}\nGeçici şifre: {$sifre}\n\nBu şifre yalnız şimdi gösteriliyor; kişiye iletin, ilk girişte değiştirmesini isteyin. Sayfa yetkilerini Kullanıcı Yönetimi'nden tanımlayın.")
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                Action::make('reddet')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Basvuru $b): bool => $b->tip === 'osgb' && $b->durum === 'beklemede')
                    ->modalHeading(fn (Basvuru $b): string => $b->baslik().' reddedilsin mi?')
                    ->schema([
                        Textarea::make('neden')->label('Red nedeni (kayıtta saklanır)')->rows(3)->maxLength(1000),
                    ])
                    ->action(function (Basvuru $b, array $data): void {
                        /** @var User $sahip */
                        $sahip = Filament::auth()->user();
                        BasvuruIslemleri::reddet($b, $sahip, $data['neden'] ?? null);

                        Notification::make()->title('Başvuru reddedildi')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Başvuru yok')
            ->emptyStateDescription('Giriş ekranındaki "OSGB Başvurusu" ve bireysel kayıtlar burada listelenir.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
