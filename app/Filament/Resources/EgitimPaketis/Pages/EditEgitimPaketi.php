<?php

namespace App\Filament\Resources\EgitimPaketis\Pages;

use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use App\Models\EgitimPaketi;
use App\Models\SoruBankasiSorusu;
use App\Support\GeminiSoruUretici;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EditEgitimPaketi extends EditRecord
{
    protected static string $resource = EgitimPaketiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bankadanEkle')
                ->label('Soru Bankasından Ekle')
                ->icon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->modalDescription(function () {
                    /** @var EgitimPaketi $paket */
                    $paket = $this->getRecord();

                    return 'Onaylı Soru Bankası\'ndan rastgele çekilir. Havuzda uygun soru: '.static::bankaSayisi($paket);
                })
                ->schema([
                    TextInput::make('adet')
                        ->label('Kaç soru eklensin')
                        ->numeric()->default(10)->minValue(1)->maxValue(50),
                ])
                ->action(function (array $data): void {
                    /** @var EgitimPaketi $paket */
                    $paket = $this->getRecord();

                    $sorular = static::bankaSorgusu($paket)
                        ->inRandomOrder()
                        ->limit(max(1, min(50, (int) $data['adet'])))
                        ->get();

                    if ($sorular->isEmpty()) {
                        Notification::make()
                            ->title('Bankada uygun soru bulunamadı')
                            ->body('Soru Bankası sayfasından bu sektöre onaylı soru ekleyin, ya da "AI ile Soru Üret" kullanın.')
                            ->warning()->send();

                        return;
                    }

                    foreach ($sorular as $s) {
                        $paket->sorular()->create([
                            'soru' => $s->soru,
                            'secenekler' => $s->secenekler,
                            'dogru_index' => $s->dogru_index,
                            'aciklama' => collect([$s->aciklama, $s->kaynak])->filter()->implode(' — ') ?: null,
                        ]);
                    }

                    Notification::make()->title($sorular->count().' soru bankadan eklendi')->success()->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $paket]));
                }),

            Action::make('aiSoruUret')
                ->label('AI ile Soru Üret')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => GeminiSoruUretici::aktifMi())
                ->modalDescription('Paketin sektörüne ve ders başlıklarına göre çoktan seçmeli sınav soruları üretilip doğrudan bu pakete eklenir.')
                ->schema([
                    TextInput::make('adet')
                        ->label('Kaç soru üretilsin')
                        ->numeric()->default(10)->minValue(1)->maxValue(50),
                    Select::make('zorluk')
                        ->label('Zorluk')
                        ->options(config('isg.egitim_sorulari.zorluklar'))
                        ->default('karisik')->native(false),
                ])
                ->action(function (array $data): void {
                    /** @var EgitimPaketi $paket */
                    $paket = $this->getRecord();

                    $sektorAdi = $paket->sektorEtiketi() ?: 'Genel İSG';
                    $zorlukEtiketi = config('isg.egitim_sorulari.zorluklar')[$data['zorluk']] ?? 'Karışık';
                    $konuAdi = static::dersKonuOzeti($paket) ?: $paket->ad;

                    $sorular = GeminiSoruUretici::uret($sektorAdi, $zorlukEtiketi, (int) $data['adet'], $konuAdi);

                    if (! $sorular) {
                        Notification::make()
                            ->title('Soru üretilemedi')
                            ->body('AI servisi yanıt vermedi; "Final Sınav Soruları" bölümünden elle ekleyebilirsiniz.')
                            ->warning()->send();

                        return;
                    }

                    foreach ($sorular as $s) {
                        $paket->sorular()->create([
                            'soru' => $s['soru'],
                            'secenekler' => $s['secenekler'],
                            'dogru_index' => $s['dogru_index'],
                            'aciklama' => collect([$s['aciklama'] ?? null, $s['kaynak'] ?? null])->filter()->implode(' — ') ?: null,
                        ]);
                    }

                    Notification::make()->title(count($sorular).' soru eklendi')->success()->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $paket]));
                }),
            DeleteAction::make(),
        ];
    }

    /** @return Builder<SoruBankasiSorusu> */
    private static function bankaSorgusu(EgitimPaketi $paket): Builder
    {
        return SoruBankasiSorusu::query()
            ->erisilebilir(Filament::auth()->id())
            ->onayli()
            ->where(fn ($q) => $q->whereNull('sektor_anahtari')->when($paket->sektor, fn ($w) => $w->orWhere('sektor_anahtari', $paket->sektor)));
    }

    private static function bankaSayisi(EgitimPaketi $paket): int
    {
        return static::bankaSorgusu($paket)->count();
    }

    /**
     * Ders başlıklarından AI istemi için kısa bir konu özeti — YouTube video
     * arşivi gibi çok sayıda (100+) dersi olan paketlerde ham başlık listesi
     * hem hashtag gürültüsü taşır hem de istemi gereksiz şişirir; en fazla
     * 20 başlık, hashtag'lerden arındırılmış ve toplam ~500 karakterle sınırlı.
     */
    private static function dersKonuOzeti(EgitimPaketi $paket): string
    {
        $basliklar = $paket->dersler()
            ->limit(20)
            ->pluck('baslik')
            ->map(fn (string $b) => trim(preg_replace('/#\S+/u', '', $b)))
            ->filter()
            ->unique()
            ->implode(', ');

        return Str::limit($basliklar, 500);
    }
}
