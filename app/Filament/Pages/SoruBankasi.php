<?php

namespace App\Filament\Pages;

use App\Models\SoruBankasiSorusu;
use App\Support\GeminiSoruUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Soru Bankası — kalıcı, kaynaklı ve onaylı sektörel İSG sınav sorusu havuzu.
 * AI ile üretilen veya elle eklenen sorular "taslak" gelir; incelenip
 * onaylandıktan sonra Eğitim Soruları ve Uzaktan Eğitim sınavları bu havuzdan
 * (yalnız onaylı sorular) beslenir.
 */
class SoruBankasi extends Page
{
    protected string $view = 'filament.pages.soru-bankasi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'soru-bankasi';

    protected static ?string $title = 'Soru Bankası';

    protected static ?string $navigationLabel = 'Soru Bankası';

    // Filtreler
    public ?string $sektorFiltre = null;

    public ?string $konuFiltre = null;

    public string $durumFiltre = 'onaylandi';

    public ?string $zorlukFiltre = null;

    public ?string $arama = null;

    // Yeni soru (manuel)
    public ?string $yeniSoru = null;

    /** @var array<int, string> */
    public array $yeniSecenekler = ['', '', '', ''];

    public int $yeniDogruIndex = 0;

    public ?string $yeniAciklama = null;

    public ?string $yeniKaynak = null;

    public ?string $yeniSektor = null;

    public ?string $yeniKonu = 'genel_isg';

    public string $yeniZorluk = 'orta';

    // AI üretim
    public ?string $aiSektor = null;

    public ?string $aiKonu = 'genel_isg';

    public string $aiZorluk = 'orta';

    public int $aiAdet = 10;

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function sektorler(): array
    {
        return ['' => 'Genel'] + collect(config('isg.risk_ai.sektorler'))->map(fn ($s) => $s['ad'])->all();
    }

    #[Computed]
    public function konular(): array
    {
        return config('isg.soru_bankasi.konular');
    }

    #[Computed]
    public function zorluklar(): array
    {
        return config('isg.soru_bankasi.zorluklar');
    }

    #[Computed]
    public function durumlar(): array
    {
        return config('isg.soru_bankasi.durumlar');
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiSoruUretici::aktifMi();
    }

    /** @return Collection<int, SoruBankasiSorusu> */
    #[Computed]
    public function sorular(): Collection
    {
        return SoruBankasiSorusu::query()
            ->erisilebilir(Filament::auth()->id())
            ->when($this->sektorFiltre !== null && $this->sektorFiltre !== '', fn ($q) => $q->where('sektor_anahtari', $this->sektorFiltre))
            ->when($this->sektorFiltre === '', fn ($q) => $q->whereNull('sektor_anahtari'))
            ->when($this->konuFiltre, fn ($q) => $q->where('konu', $this->konuFiltre))
            ->when($this->durumFiltre, fn ($q) => $q->where('durum', $this->durumFiltre))
            ->when($this->zorlukFiltre, fn ($q) => $q->where('zorluk', $this->zorlukFiltre))
            ->when($this->arama, fn ($q) => $q->where('soru', 'like', '%'.$this->arama.'%'))
            ->latest()
            ->limit(300)
            ->get();
    }

    #[Computed]
    public function ozet(): array
    {
        $temel = SoruBankasiSorusu::query()->erisilebilir(Filament::auth()->id());

        return [
            'toplam' => (clone $temel)->count(),
            'onayli' => (clone $temel)->where('durum', 'onaylandi')->count(),
            'taslak' => (clone $temel)->where('durum', 'taslak')->count(),
            'arsiv' => (clone $temel)->where('durum', 'arsiv')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Manuel ekleme
    |--------------------------------------------------------------------------
    */

    public function soruEkle(): void
    {
        $secenekler = array_map('trim', $this->yeniSecenekler);

        if (blank($this->yeniSoru) || count(array_filter($secenekler, 'filled')) !== 4) {
            Notification::make()->title('Soru metni ve 4 şık zorunlu')->danger()->send();

            return;
        }

        SoruBankasiSorusu::create([
            'user_id' => Filament::auth()->id(),
            'sektor_anahtari' => $this->yeniSektor ?: null,
            'konu' => $this->yeniKonu,
            'zorluk' => $this->yeniZorluk,
            'soru' => trim($this->yeniSoru),
            'secenekler' => array_values($secenekler),
            'dogru_index' => max(0, min(3, $this->yeniDogruIndex)),
            'aciklama' => $this->yeniAciklama ?: null,
            'kaynak' => $this->yeniKaynak ?: null,
            'durum' => 'taslak',
            'uretim_kaynagi' => 'manuel',
        ]);

        $this->reset('yeniSoru', 'yeniAciklama', 'yeniKaynak', 'yeniDogruIndex');
        $this->yeniSecenekler = ['', '', '', ''];
        unset($this->sorular, $this->ozet);

        Notification::make()->title('Soru bankaya taslak olarak eklendi')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Durum yönetimi
    |--------------------------------------------------------------------------
    */

    private function sahipMi(SoruBankasiSorusu $s): bool
    {
        return $s->user_id === null || $s->user_id === Filament::auth()->id();
    }

    public function onayla(int $id): void
    {
        $s = SoruBankasiSorusu::find($id);

        if (! $s || ! $this->sahipMi($s)) {
            return;
        }

        $s->update([
            'durum' => 'onaylandi',
            'onaylayan' => Filament::auth()->user()?->name,
            'onay_tarihi' => now(),
        ]);
        unset($this->sorular, $this->ozet);
    }

    public function arsivle(int $id): void
    {
        $s = SoruBankasiSorusu::find($id);

        if ($s && $this->sahipMi($s)) {
            $s->update(['durum' => 'arsiv']);
            unset($this->sorular, $this->ozet);
        }
    }

    public function taslagaAl(int $id): void
    {
        $s = SoruBankasiSorusu::find($id);

        if ($s && $this->sahipMi($s)) {
            $s->update(['durum' => 'taslak', 'onaylayan' => null, 'onay_tarihi' => null]);
            unset($this->sorular, $this->ozet);
        }
    }

    public function sil(int $id): void
    {
        $s = SoruBankasiSorusu::find($id);

        // Sistem havuzu soruları (user_id NULL) silinmez, yalnız arşivlenir.
        if ($s && $s->user_id === Filament::auth()->id()) {
            $s->delete();
            unset($this->sorular, $this->ozet);
        }
    }

    public function tumTaslaklariOnayla(): void
    {
        $sayi = SoruBankasiSorusu::query()
            ->erisilebilir(Filament::auth()->id())
            ->where('durum', 'taslak')
            ->when($this->sektorFiltre !== null && $this->sektorFiltre !== '', fn ($q) => $q->where('sektor_anahtari', $this->sektorFiltre))
            ->when($this->konuFiltre, fn ($q) => $q->where('konu', $this->konuFiltre))
            ->update([
                'durum' => 'onaylandi',
                'onaylayan' => Filament::auth()->user()?->name,
                'onay_tarihi' => now(),
            ]);

        unset($this->sorular, $this->ozet);
        Notification::make()->title($sayi.' taslak soru onaylandı')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | AI üretimi (havuza taslak yazar)
    |--------------------------------------------------------------------------
    */

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiUret')
                ->label('AI ile Soru Üret')
                ->icon('heroicon-o-sparkles')
                ->visible(fn () => $this->aiAktif())
                ->schema([
                    Select::make('sektor')
                        ->label('Sektör')
                        ->options($this->sektorler())
                        ->default(fn () => $this->sektorFiltre),
                    Select::make('konu')
                        ->label('Konu')
                        ->options($this->konular())
                        ->default(fn () => $this->konuFiltre ?? 'genel_isg')
                        ->required(),
                    Select::make('zorluk')
                        ->label('Zorluk')
                        ->options($this->zorluklar())
                        ->default('orta'),
                    TextInput::make('adet')
                        ->label('Soru sayısı')
                        ->numeric()->default(10)->minValue(3)->maxValue(20),
                ])
                ->action(function (array $data): void {
                    $this->aiUret($data['sektor'] ?? null, $data['konu'], $data['zorluk'] ?? 'orta', (int) ($data['adet'] ?? 10));
                }),
        ];
    }

    public function aiUret(?string $sektor, string $konu, string $zorluk, int $adet): void
    {
        $sektorAdi = $sektor ? (config('isg.risk_ai.sektorler.'.$sektor.'.ad') ?? $sektor) : 'Genel';
        $konuAdi = config('isg.soru_bankasi.konular.'.$konu, $konu);
        $zorlukEtiketi = config('isg.soru_bankasi.zorluklar.'.$zorluk, 'Orta');

        $uretilen = GeminiSoruUretici::uret($sektorAdi, $zorlukEtiketi, $adet, $konuAdi);

        if (! $uretilen) {
            Notification::make()->title('Soru üretilemedi')->body('AI servisi yanıt vermedi veya devre dışı.')->warning()->send();

            return;
        }

        foreach ($uretilen as $s) {
            SoruBankasiSorusu::create([
                'user_id' => Filament::auth()->id(),
                'sektor_anahtari' => $sektor ?: null,
                'konu' => $konu,
                'zorluk' => $zorluk,
                'soru' => $s['soru'],
                'secenekler' => $s['secenekler'],
                'dogru_index' => $s['dogru_index'],
                'aciklama' => $s['aciklama'] ?? null,
                'kaynak' => $s['kaynak'] ?? null,
                'durum' => 'taslak',
                'uretim_kaynagi' => 'ai',
            ]);
        }

        $this->sektorFiltre = $sektor ?: '';
        $this->konuFiltre = $konu;
        $this->durumFiltre = 'taslak';
        unset($this->sorular, $this->ozet);

        Notification::make()->title(count($uretilen).' soru üretildi — inceleyip onaylayın')->success()->send();
    }
}
