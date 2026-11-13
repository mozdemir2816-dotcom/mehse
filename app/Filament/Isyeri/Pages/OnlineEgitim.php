<?php

namespace App\Filament\Isyeri\Pages;

use App\Models\Calisan;
use App\Models\EgitimAtamasi;
use App\Models\EgitimPaketi;
use App\Models\Firma;
use App\Models\IsyeriHesabi;
use App\Support\UzaktanEgitimAtayici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * İşyeri paneli — Online Eğitim (13.11.2026): işveren kendi çalışanlarını İSG uzmanının hazırladığı
 * uzaktan eğitim paketine ekler ya da henüz başlamamış eğitimden çıkarır; çalışanın portal giriş
 * bilgisini görür. Başlamış eğitim (ders izlenmiş / sınav girilmiş) kaldırılamaz — yasal kayıttır.
 * Atama kuralı uzman paneliyle aynıdır (App\Support\UzaktanEgitimAtayici).
 */
class OnlineEgitim extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.isyeri.pages.online-egitim';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $title = 'Online Eğitim';

    protected static ?string $slug = 'online-egitim';

    protected static ?int $navigationSort = 3;

    /** Son eklemede üretilen giriş bilgileri (ekranda gösterilir, kaydedilmez). */
    public array $girisBilgileri = [];

    #[Computed]
    public function firma(): Firma
    {
        /** @var IsyeriHesabi $hesap */
        $hesap = Filament::auth()->user();

        return Firma::findOrFail($hesap->firma_id);
    }

    /** İSG uzmanının bu firmaya atanabilir paketleri: aktif, sınava hazır, süresi tehlike sınıfına yeterli. */
    #[Computed]
    public function paketler(): Collection
    {
        return EgitimPaketi::query()->gorunur((int) $this->firma->user_id)->where('aktif', true)->with('dersler')->orderBy('ad')->get()
            ->filter(fn (EgitimPaketi $p) => $p->sinavaHazirMi());
    }

    private function calisanSorgu(): Builder
    {
        return Calisan::query()->where('firma_id', $this->firma->id)->where('aktif', true);
    }

    private function sonAtama(Calisan $c): ?EgitimAtamasi
    {
        return $c->egitimAtamalari->sortByDesc('atandi_at')->first();
    }

    /** Paket süresi firmanın tehlike sınıfı + eğitim türü için yeterli mi (Uzaktan eğitim md.13). */
    private function sureYeterliMi(EgitimPaketi $paket, string $tur): bool
    {
        $sinif = $this->firma->tehlike_sinifi;
        if (! $sinif) {
            return true;
        }

        return $paket->toplamSureDk() >= EgitimPaketi::gerekenUzaktanSure($sinif, $tur)['gereken_dk'];
    }

    /** @return array<int, \Filament\Schemas\Components\Component> */
    private function ekleFormu(): array
    {
        return [
            Select::make('paket_id')->label('Eğitim')->required()->native(false)
                ->options(fn () => $this->paketler->mapWithKeys(fn (EgitimPaketi $p) => [$p->id => $p->ad])->all())
                ->default(fn () => $this->paketler->count() === 1 ? $this->paketler->first()->id : null)
                ->helperText('İSG uzmanınızın hazırladığı eğitimler listelenir.'),
            Select::make('egitim_turu')->label('Eğitim türü')->required()->native(false)
                ->options(config('isg.uzaktan_egitim.egitim_turleri'))->default('ilk_defa')
                ->helperText('Yeni başlayan çalışan: İlk defa. Süresi dolan eğitimi tekrarlayan: Yenileme.'),
        ];
    }

    /** @param  Collection<int, Calisan>  $calisanlar */
    private function egitimeEkle(Collection $calisanlar, array $data): void
    {
        $paket = $this->paketler->firstWhere('id', (int) $data['paket_id']);
        $tur = (string) $data['egitim_turu'];

        if (! $paket || ! array_key_exists($tur, config('isg.uzaktan_egitim.egitim_turleri'))) {
            Notification::make()->title('Eğitim bulunamadı')->danger()->send();

            return;
        }

        if (! $this->sureYeterliMi($paket, $tur)) {
            Notification::make()->title('Bu eğitimin süresi yetersiz')
                ->body('Seçilen eğitim, işyerinizin tehlike sınıfı için gereken süreyi karşılamıyor. Lütfen İSG uzmanınıza danışın.')
                ->danger()->persistent()->send();

            return;
        }

        // Yalnız bu firmanın aktif çalışanları (istemciden gelen kayıtlar tekrar süzülür).
        $calisanlar = $this->calisanSorgu()->whereKey($calisanlar->modelKeys())->get();
        $sonuc = UzaktanEgitimAtayici::ata($paket, $calisanlar, $tur, null, (int) $this->firma->user_id);
        $this->girisBilgileri = $sonuc['giris'];

        Notification::make()->title($sonuc['atanan'].' çalışan eğitime eklendi')
            ->body('Giriş bilgileri tablonun altında listelendi; çalışanlarınıza iletin.')->success()->send();
    }

    public function whatsappLinki(array $g): string
    {
        $metin = "Sayın {$g['ad_soyad']}, {$this->firma->unvan} bünyesindeki İş Sağlığı ve Güvenliği uzaktan eğitiminiz tanımlanmıştır.\n"
            .'Eğitim Portalı: '.url('/egitim')."\n"
            ."Kullanıcı Kodu: {$g['eposta']}\n"
            .'Geçici Şifre: '.($g['sifre'] ?? '(mevcut şifreniz)');

        $no = preg_replace('/\D/', '', (string) ($g['telefon'] ?? ''));
        $no = match (true) {
            strlen($no) === 11 && str_starts_with($no, '0') => '9'.$no,
            strlen($no) === 10 => '90'.$no,
            default => $no,
        };

        return 'https://wa.me/'.$no.'?text='.rawurlencode($metin);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->calisanSorgu()->with(['egitimAtamalari.paket.dersler', 'egitimAtamalari.ilerlemeler']))
            ->defaultSort('ad_soyad')
            ->columns([
                TextColumn::make('ad_soyad')->label('Ad soyad')->searchable()->sortable()->weight('bold'),
                TextColumn::make('gorev')->label('Görevi')->placeholder('—'),
                TextColumn::make('egitim')->label('Eğitim')->placeholder('Eklenmedi')
                    ->state(fn (Calisan $c) => $this->sonAtama($c)?->paket?->ad),
                TextColumn::make('egitim_durum')->label('Durum')->badge()->placeholder('—')
                    ->state(fn (Calisan $c) => ($a = $this->sonAtama($c)) ? $a->durumEtiketi().' · %'.$a->ilerlemeYuzdesi() : null)
                    ->color(fn (Calisan $c) => match ($this->sonAtama($c)?->durum) {
                        'tamamlandi' => 'success',
                        'basarisiz' => 'danger',
                        null => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('eposta')->label('Kullanıcı kodu')->placeholder('—')->copyable(),
            ])
            ->headerActions([
                Action::make('portal')->label('Eğitim portalı')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(url('/egitim'), shouldOpenInNewTab: true),
            ])
            ->recordActions([
                Action::make('ekle')->label('Eğitime ekle')->icon('heroicon-o-plus-circle')
                    ->visible(fn (Calisan $c) => $this->sonAtama($c) === null)
                    ->modalHeading(fn (Calisan $c) => $c->ad_soyad.' — eğitime ekle')
                    ->modalSubmitActionLabel('Ekle')
                    ->schema($this->ekleFormu())
                    ->action(fn (Calisan $c, array $data) => $this->egitimeEkle(collect([$c]), $data)),
                Action::make('cikar')->label('Eğitimden çıkar')->icon('heroicon-o-minus-circle')->color('danger')
                    ->visible(fn (Calisan $c) => ($a = $this->sonAtama($c)) && UzaktanEgitimAtayici::baslamadiMi($a))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Calisan $c) => $c->ad_soyad.' eğitimden çıkarılsın mı?')
                    ->modalDescription('Çalışan henüz eğitime başlamadı; atama kaldırılır.')
                    ->action(function (Calisan $c): void {
                        $a = EgitimAtamasi::query()->whereKey($this->sonAtama($c)?->id)
                            ->whereHas('calisan', fn ($q) => $q->where('firma_id', $this->firma->id))->first();
                        if ($a && UzaktanEgitimAtayici::baslamadiMi($a)) {
                            $a->delete();
                            Notification::make()->title($c->ad_soyad.' eğitimden çıkarıldı')->success()->send();
                        }
                    }),
                Action::make('sifre')->label('Yeni şifre')->icon('heroicon-o-key')->color('gray')
                    ->visible(fn (Calisan $c) => $this->sonAtama($c) !== null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Calisan $c) => $c->ad_soyad.' için yeni geçici şifre')
                    ->modalDescription('Çalışanın eski şifresi geçersiz olur.')
                    ->action(function (Calisan $c): void {
                        $c = $this->calisanSorgu()->findOrFail($c->id);
                        $sifre = UzaktanEgitimAtayici::geciciSifre();
                        $c->forceFill(['sifre' => $sifre, 'sifre_belirlendi_at' => null])->save();
                        $this->girisBilgileri = [['ad_soyad' => $c->ad_soyad, 'eposta' => $c->eposta, 'sifre' => $sifre, 'telefon' => $c->telefon]];
                        Notification::make()->title('Yeni şifre üretildi')->body('Giriş bilgisi tablonun altında.')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('topluEkle')->label('Seçilenleri eğitime ekle')->icon('heroicon-o-plus-circle')
                    ->modalHeading('Seçilen çalışanları eğitime ekle')
                    ->modalSubmitActionLabel('Ekle')
                    ->schema($this->ekleFormu())
                    ->action(fn (Collection $kayitlar, array $data) => $this->egitimeEkle($kayitlar, $data))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Kayıtlı çalışan yok')
            ->emptyStateDescription('Önce "Personel" sayfasından çalışanlarınızı ekleyin.');
    }
}
