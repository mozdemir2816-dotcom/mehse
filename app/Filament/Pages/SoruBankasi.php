<?php

namespace App\Filament\Pages;

use App\Models\NaceKodu;
use App\Models\SoruBankasiSorusu;
use App\Support\GeminiSoruUretici;
use App\Support\SoruBankasiIceAktarici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Soru Bankası — kalıcı, kaynaklı ve editoryal onaylı İSG sınav sorusu havuzu
 * (isgsuite "İSG soru bankası ve akıllı sınav motoru").
 *
 * Akış: taslak → incelemede → yayımlandı → kullanımdan kaldırıldı. Her soru
 * kod + sürüm taşır; "Yeni sürüm" yayımlanmış sorunun taslak kopyasını açar,
 * yeni sürüm yayımlanınca eskisi kaldırılır. Yayımlamak için doğru cevabın
 * gerekçesi ve en az bir doğrulanabilir kaynak zorunludur.
 *
 * Kapsam: ortak (tüm sektörler), sektör veya NACE ön eki ("43.21" →
 * 43.21 ile başlayan tüm kodlar). Eğitim Soruları / Uzaktan Eğitim sınavları
 * yalnız yayımlanmış ve kapsama uyan soruları çeker. NACE kapsama tablosu her
 * NACE için hazır soru sayısını hedefle (5 temel + 15 işe özgü) karşılaştırır.
 */
class SoruBankasi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.soru-bankasi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Eğitimler';

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'soru-bankasi';

    protected static ?string $title = 'Soru Bankası';

    protected static ?string $navigationLabel = 'Soru Bankası';

    // Filtreler
    public ?string $sektorFiltre = null;

    public ?string $konuFiltre = null;

    public string $durumFiltre = '';

    public ?string $zorlukFiltre = null;

    public ?string $arama = null;

    // Yeni soru / düzenleme
    public ?int $duzenlenenId = null;

    public ?string $yeniKod = null;

    public ?string $yeniSoru = null;

    /** @var array<int, string> */
    public array $yeniSecenekler = ['', '', '', ''];

    public int $yeniDogruIndex = 0;

    public ?string $yeniAciklama = null;

    /** Eski tek satırlık kaynak alanı (geri uyumluluk) — kaynak listesi boşsa ilk kaynak olur. */
    public ?string $yeniKaynak = null;

    /** @var array<int, array{ad: string, url: string, madde: string, tarih: string}> */
    public array $yeniKaynaklar = [['ad' => '', 'url' => '', 'madde' => '', 'tarih' => '']];

    public ?string $yeniSektor = null;

    public ?string $yeniNace = null;

    public ?string $yeniKonu = 'genel_isg';

    public string $yeniZorluk = 'orta';

    public ?string $yeniIncelemeNotu = null;

    // NACE kapsama tablosu
    public string $naceArama = '';

    public string $naceDurum = '';

    public int $naceSayfa = 1;

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function sektorler(): array
    {
        return ['' => 'Ortak (tüm sektörler)'] + collect(config('isg.risk_ai.sektorler'))->map(fn ($s) => $s['ad'])->all();
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
        $a = trim((string) $this->arama);

        return SoruBankasiSorusu::query()
            ->erisilebilir(Filament::auth()->id())
            ->when($this->sektorFiltre === '__ortak__', fn ($q) => $q->whereNull('sektor_anahtari')->whereNull('nace_onekleri'))
            ->when($this->sektorFiltre === '__nace__', fn ($q) => $q->whereNotNull('nace_onekleri'))
            ->when(filled($this->sektorFiltre) && ! str_starts_with($this->sektorFiltre, '__'), fn ($q) => $q->where('sektor_anahtari', $this->sektorFiltre))
            ->when($this->konuFiltre, fn ($q) => $q->where('konu', $this->konuFiltre))
            ->when($this->durumFiltre, fn ($q) => $q->where('durum', $this->durumFiltre))
            ->when($this->zorlukFiltre, fn ($q) => $q->where('zorluk', $this->zorlukFiltre))
            ->when($a !== '', fn ($q) => $q->where(fn ($w) => $w->where('soru', 'like', "%{$a}%")->orWhere('soru_kodu', 'like', "%{$a}%")))
            ->latest('updated_at')
            ->limit(300)
            ->get();
    }

    #[Computed]
    public function ozet(): array
    {
        $sayilar = SoruBankasiSorusu::query()->erisilebilir(Filament::auth()->id())
            ->selectRaw('durum, COUNT(*) n')->groupBy('durum')->pluck('n', 'durum');

        return [
            'toplam' => (int) $sayilar->sum(),
            'taslak' => (int) ($sayilar['taslak'] ?? 0),
            'incelemede' => (int) ($sayilar['incelemede'] ?? 0),
            'onayli' => (int) ($sayilar['onaylandi'] ?? 0),
            'arsiv' => (int) ($sayilar['arsiv'] ?? 0),
        ];
    }

    /**
     * NACE kapsama: her NACE için yayımlanmış ortak (temel) ve NACE kapsamlı
     * (işe özgü) soru sayısı, bekleyen (taslak / incelemede) NACE taslağı.
     */
    #[Computed]
    public function naceKapsama(): array
    {
        $hedef = config('isg.soru_bankasi.kapsama');
        $kullanici = Filament::auth()->id();

        $ortak = SoruBankasiSorusu::query()->erisilebilir($kullanici)->onayli()->whereNull('sektor_anahtari')->whereNull('nace_onekleri')->count();
        $naceli = SoruBankasiSorusu::query()->erisilebilir($kullanici)->whereNotNull('nace_onekleri')
            ->whereIn('durum', ['onaylandi', 'taslak', 'incelemede'])->get(['nace_onekleri', 'durum']);

        $satirlar = NaceKodu::query()->orderBy('kod')->get(['kod', 'tanim', 'tehlike_sinifi'])->map(function (NaceKodu $n) use ($naceli, $ortak, $hedef) {
            $onekler = SoruBankasiSorusu::naceOnEkleri($n->kod);
            $eslesen = $naceli->filter(fn ($s) => collect($onekler)->contains(fn ($p) => str_contains($s->nace_onekleri, ','.$p.',')));
            $yayinli = $eslesen->where('durum', 'onaylandi')->count();

            return [
                'kod' => $n->kod, 'tanim' => $n->tanim, 'tehlike' => $n->tehlike_sinifi,
                'ortak' => $ortak, 'nace' => $yayinli, 'bekleyen' => $eslesen->count() - $yayinli,
                'durum' => $ortak >= $hedef['temel'] && $yayinli >= $hedef['ise_ozgu'] ? 'yeterli' : ($yayinli > 0 ? 'kismi' : 'eksik'),
            ];
        });

        $q = mb_strtolower(trim($this->naceArama));
        $filtreli = $satirlar->filter(fn ($s) => ($q === '' || str_contains(mb_strtolower($s['kod'].' '.$s['tanim']), $q))
            && ($this->naceDurum === '' || $s['durum'] === $this->naceDurum))->values();

        $sayfaBoyu = 10;
        $sonSayfa = max(1, (int) ceil($filtreli->count() / $sayfaBoyu));
        $sayfa = min(max(1, $this->naceSayfa), $sonSayfa);

        return [
            'toplam' => $satirlar->count(),
            'yeterli' => $satirlar->where('durum', 'yeterli')->count(),
            'kismi' => $satirlar->where('durum', 'kismi')->count(),
            'ortak' => $ortak,
            'naceli_yayinli' => $naceli->where('durum', 'onaylandi')->count(),
            'hedef' => $hedef,
            'satirlar' => $filtreli->slice(($sayfa - 1) * $sayfaBoyu, $sayfaBoyu)->values(),
            'bulunan' => $filtreli->count(),
            'sayfa' => $sayfa,
            'son_sayfa' => $sonSayfa,
        ];
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['naceArama', 'naceDurum'], true)) {
            $this->naceSayfa = 1;
        }

        $this->yenile();
    }

    private function yenile(): void
    {
        unset($this->sorular, $this->ozet, $this->naceKapsama);
    }

    /*
    |--------------------------------------------------------------------------
    | Soru formu (ekle / düzenle)
    |--------------------------------------------------------------------------
    */

    public function kaynakEkle(): void
    {
        $this->yeniKaynaklar[] = ['ad' => '', 'url' => '', 'madde' => '', 'tarih' => ''];
    }

    public function kaynakSil(int $i): void
    {
        unset($this->yeniKaynaklar[$i]);
        $this->yeniKaynaklar = array_values($this->yeniKaynaklar) ?: [['ad' => '', 'url' => '', 'madde' => '', 'tarih' => '']];
    }

    /** @return array<int, array{ad: string, url: ?string, madde: ?string, tarih: ?string}> */
    private function formKaynaklari(): array
    {
        $liste = collect($this->yeniKaynaklar)->filter(fn ($k) => filled(trim($k['ad'] ?? '')))
            ->map(fn ($k) => ['ad' => trim($k['ad']), 'url' => trim($k['url'] ?? '') ?: null, 'madde' => trim($k['madde'] ?? '') ?: null, 'tarih' => ($k['tarih'] ?? '') ?: null])
            ->values()->all();

        if (! $liste && filled($this->yeniKaynak)) {
            $liste = [['ad' => trim($this->yeniKaynak), 'url' => null, 'madde' => null, 'tarih' => null]];
        }

        return $liste;
    }

    public function soruEkle(): void
    {
        $secenekler = array_map(fn ($s) => trim((string) $s), $this->yeniSecenekler);
        $kaynaklar = $this->formKaynaklari();

        $hata = match (true) {
            blank($this->yeniSoru) || count(array_filter($secenekler, 'filled')) !== 4 => 'Soru metni ve 4 şık zorunlu',
            count(array_unique(array_map('mb_strtolower', $secenekler))) !== 4 => 'Seçenekler birbirinden farklı olmalı',
            $kaynaklar === [] => 'En az bir doğrulanabilir kaynak girin',
            default => null,
        };

        if ($hata) {
            Notification::make()->title($hata)->danger()->send();

            return;
        }

        $veri = [
            'soru_kodu' => filled($this->yeniKod) ? mb_substr(trim($this->yeniKod), 0, 60) : null,
            'sektor_anahtari' => $this->yeniSektor ?: null,
            'nace_onekleri' => SoruBankasiSorusu::naceOnekleriniHazirla($this->yeniNace),
            'konu' => $this->yeniKonu,
            'zorluk' => $this->yeniZorluk,
            'soru' => trim($this->yeniSoru),
            'secenekler' => array_values($secenekler),
            'dogru_index' => max(0, min(3, $this->yeniDogruIndex)),
            'aciklama' => filled($this->yeniAciklama) ? trim($this->yeniAciklama) : null,
            'kaynaklar' => $kaynaklar,
            'kaynak' => $kaynaklar[0]['ad'],
            'inceleme_notu' => filled($this->yeniIncelemeNotu) ? trim($this->yeniIncelemeNotu) : null,
        ];

        $mevcut = $this->duzenlenenId ? $this->kendiSorusu($this->duzenlenenId) : null;

        if ($mevcut && in_array($mevcut->durum, ['taslak', 'incelemede'], true)) {
            $mevcut->update($veri);
            $mesaj = 'Taslak güncellendi';
        } else {
            SoruBankasiSorusu::create([...$veri, 'user_id' => Filament::auth()->id(), 'durum' => 'taslak', 'uretim_kaynagi' => 'manuel']);
            $mesaj = 'Soru bankaya taslak olarak eklendi';
        }

        $this->formuTemizle();
        $this->yenile();

        Notification::make()->title($mesaj)->body('Yayımlamadan önce inceleyin; sınavlar yalnız yayımlanmış soruları kullanır.')->success()->send();
    }

    public function formuTemizle(): void
    {
        $this->reset('duzenlenenId', 'yeniKod', 'yeniSoru', 'yeniAciklama', 'yeniKaynak', 'yeniDogruIndex', 'yeniNace', 'yeniIncelemeNotu');
        $this->yeniSecenekler = ['', '', '', ''];
        $this->yeniKaynaklar = [['ad' => '', 'url' => '', 'madde' => '', 'tarih' => '']];
    }

    private function formaYukle(SoruBankasiSorusu $s): void
    {
        $this->duzenlenenId = $s->id;
        $this->yeniKod = $s->soru_kodu;
        $this->yeniSoru = $s->soru;
        $this->yeniSecenekler = array_pad(array_values($s->secenekler ?? []), 4, '');
        $this->yeniDogruIndex = (int) $s->dogru_index;
        $this->yeniAciklama = $s->aciklama;
        $this->yeniKaynak = null;
        $this->yeniKaynaklar = collect($s->kaynakListesi())->map(fn ($k) => ['ad' => $k['ad'], 'url' => (string) $k['url'], 'madde' => (string) $k['madde'], 'tarih' => (string) $k['tarih']])->all()
            ?: [['ad' => '', 'url' => '', 'madde' => '', 'tarih' => '']];
        $this->yeniSektor = $s->sektor_anahtari;
        $this->yeniNace = implode(', ', $s->naceKapsami());
        $this->yeniKonu = $s->konu;
        $this->yeniZorluk = $s->zorluk;
        $this->yeniIncelemeNotu = $s->inceleme_notu;
    }

    public function duzenle(int $id): void
    {
        $s = $this->kendiSorusu($id);

        if (! $s || ! in_array($s->durum, ['taslak', 'incelemede'], true)) {
            Notification::make()->title('Yalnız taslak / incelemedeki sorular düzenlenir')->body('Yayımlanmış soru için "Yeni sürüm" kullanın.')->warning()->send();

            return;
        }

        $this->formaYukle($s);
    }

    /** Yayımlanmış (veya kaldırılmış) sorunun bir sonraki sürümünü taslak olarak açar. */
    public function yeniSurum(int $id): void
    {
        $s = SoruBankasiSorusu::query()->erisilebilir(Filament::auth()->id())->find($id);

        if (! $s) {
            return;
        }

        $kod = $s->soru_kodu ?: 'SB-'.str_pad((string) $s->id, 4, '0', STR_PAD_LEFT);
        $surum = max((int) SoruBankasiSorusu::query()->where('soru_kodu', $kod)->max('surum'), (int) $s->surum) + 1;

        $kopya = $s->replicate(['onaylayan', 'onay_tarihi']);
        $kopya->fill([
            'user_id' => Filament::auth()->id(),
            'soru_kodu' => $kod,
            'surum' => $surum,
            'onceki_surum_id' => $s->id,
            'durum' => 'taslak',
            'uretim_kaynagi' => 'manuel',
            'kaynaklar' => $s->kaynakListesi(),
        ]);
        $kopya->save();

        if (! $s->soru_kodu && $this->sahipMi($s)) {
            $s->update(['soru_kodu' => $kod]);
        }

        $this->formaYukle($kopya);
        $this->yenile();

        Notification::make()->title($kopya->kodEtiketi().' taslağı açıldı')->body('Formda düzenleyip kaydedin; yayımlanınca önceki sürüm kullanımdan kalkar.')->success()->send();
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

    private function kendiSorusu(int $id): ?SoruBankasiSorusu
    {
        $s = SoruBankasiSorusu::find($id);

        return $s && $this->sahipMi($s) ? $s : null;
    }

    /** Yayımlama şartı: gerekçe + en az bir kaynak. */
    public static function yayimlanabilirMi(SoruBankasiSorusu $s): bool
    {
        return filled($s->aciklama) && $s->kaynakListesi() !== [];
    }

    private function yayimla(SoruBankasiSorusu $s): void
    {
        $s->update(['durum' => 'onaylandi', 'onaylayan' => Filament::auth()->user()?->name, 'onay_tarihi' => now()]);

        // Yeni sürüm yayımlanınca önceki sürüm kullanımdan kalkar
        if ($s->onceki_surum_id) {
            SoruBankasiSorusu::query()->whereKey($s->onceki_surum_id)->where('durum', 'onaylandi')->update(['durum' => 'arsiv']);
        }
    }

    public function incelemeyeGonder(int $id): void
    {
        if ($s = $this->kendiSorusu($id)) {
            $s->update(['durum' => 'incelemede']);
            $this->yenile();
        }
    }

    public function onayla(int $id): void
    {
        $s = $this->kendiSorusu($id);

        if (! $s) {
            return;
        }

        if (! static::yayimlanabilirMi($s)) {
            Notification::make()->title('Yayımlanamaz')->body('Doğru cevabın gerekçesi ve en az bir doğrulanabilir kaynak girilmeli. "Düzenle" ile tamamlayın.')->danger()->send();

            return;
        }

        $this->yayimla($s);
        $this->yenile();
    }

    public function arsivle(int $id): void
    {
        if ($s = $this->kendiSorusu($id)) {
            $s->update(['durum' => 'arsiv']);
            $this->yenile();
        }
    }

    public function taslagaAl(int $id): void
    {
        if ($s = $this->kendiSorusu($id)) {
            $s->update(['durum' => 'taslak', 'onaylayan' => null, 'onay_tarihi' => null]);
            $this->yenile();
        }
    }

    public function sil(int $id): void
    {
        $s = SoruBankasiSorusu::find($id);

        // Sistem havuzu soruları (user_id NULL) silinmez, yalnız kaldırılır.
        if ($s && $s->user_id === Filament::auth()->id()) {
            $s->delete();
            $this->yenile();
        }
    }

    public function tumTaslaklariOnayla(): void
    {
        $adaylar = SoruBankasiSorusu::query()
            ->erisilebilir(Filament::auth()->id())
            ->where('durum', $this->durumFiltre === 'incelemede' ? 'incelemede' : 'taslak')
            ->when($this->sektorFiltre !== null && $this->sektorFiltre !== '', fn ($q) => $q->where('sektor_anahtari', $this->sektorFiltre))
            ->when($this->konuFiltre, fn ($q) => $q->where('konu', $this->konuFiltre))
            ->get();

        $uygun = $adaylar->filter(fn ($s) => static::yayimlanabilirMi($s));
        $uygun->each(fn ($s) => $this->yayimla($s));

        $this->yenile();

        $atlanan = $adaylar->count() - $uygun->count();
        $bildirim = Notification::make()->title($uygun->count().' soru yayımlandı')
            ->body($atlanan ? "{$atlanan} soru gerekçe / kaynak eksik olduğu için atlandı." : null);
        ($atlanan ? $bildirim->warning() : $bildirim->success())->send();
    }

    /*
    |--------------------------------------------------------------------------
    | JSON şablonu ve toplu taslak yükleme
    |--------------------------------------------------------------------------
    */

    public function jsonSablonu()
    {
        $json = json_encode(SoruBankasiIceAktarici::sablon(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(fn () => print ($json), 'soru-bankasi-sablonu.json', ['Content-Type' => 'application/json']);
    }

    /** Toplu yüklemenin çekirdeği (dosya içeriği → taslaklar). */
    public function jsonIceAktar(string $icerik): array
    {
        $sonuc = SoruBankasiIceAktarici::cozumle($icerik);

        foreach ($sonuc['kayitlar'] as $k) {
            SoruBankasiSorusu::create([...$k, 'user_id' => Filament::auth()->id(), 'durum' => 'taslak', 'uretim_kaynagi' => 'json']);
        }

        $this->durumFiltre = 'taslak';
        $this->yenile();

        $bildirim = Notification::make()
            ->title(count($sonuc['kayitlar']).' soru taslak olarak eklendi')
            ->body($sonuc['hatalar'] ? implode("\n", array_slice($sonuc['hatalar'], 0, 10)).(count($sonuc['hatalar']) > 10 ? "\n…" : '') : null);
        ($sonuc['hatalar'] ? $bildirim->warning()->persistent() : $bildirim->success())->send();

        return $sonuc;
    }

    public function topluYukleAction(): Action
    {
        return Action::make('topluYukle')
            ->label('Toplu taslak yükle')
            ->icon('heroicon-o-arrow-up-tray')
            ->modalHeading('Toplu taslak yükle (JSON)')
            ->modalDescription('JSON şablonundaki biçimde soruları yükleyin. Geçerli sorular TASLAK olarak eklenir; hatalı kayıtlar satır numarasıyla bildirilir. Yayımlama yine incelemeden sonra yapılır.')
            ->modalSubmitActionLabel('Yükle')
            ->schema([
                FileUpload::make('dosya')->label('JSON dosyası')->required()
                    ->disk('local')->directory('soru-bankasi-yukleme')
                    ->acceptedFileTypes(['application/json', 'text/plain', 'text/json'])
                    ->maxSize(2048),
            ])
            ->action(function (array $data): void {
                $yol = is_array($data['dosya']) ? reset($data['dosya']) : $data['dosya'];
                $icerik = (string) Storage::disk('local')->get($yol);
                Storage::disk('local')->delete($yol);

                $this->jsonIceAktar($icerik);
            });
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

    public function aiUret(?string $sektor, string $konu, string $zorluk, int $adet, ?string $naceKod = null): void
    {
        $nace = $naceKod ? NaceKodu::bul($naceKod) : null;
        $sektorAdi = $nace
            ? 'NACE '.$nace->kod.' — '.$nace->tanim
            : ($sektor ? (config('isg.risk_ai.sektorler.'.$sektor.'.ad') ?? $sektor) : 'Genel');
        $konuAdi = $nace ? 'Bu faaliyete özgü iş sağlığı ve güvenliği riskleri' : config('isg.soru_bankasi.konular.'.$konu, $konu);
        $zorlukEtiketi = config('isg.soru_bankasi.zorluklar.'.$zorluk, 'Orta');

        $uretilen = GeminiSoruUretici::uret($sektorAdi, $zorlukEtiketi, $adet, $konuAdi);

        if (! $uretilen) {
            Notification::make()->title('Soru üretilemedi')->body('AI servisi yanıt vermedi veya devre dışı.')->warning()->send();

            return;
        }

        foreach ($uretilen as $s) {
            SoruBankasiSorusu::create([
                'user_id' => Filament::auth()->id(),
                'sektor_anahtari' => $nace ? null : ($sektor ?: null),
                'nace_onekleri' => $nace ? SoruBankasiSorusu::naceOnekleriniHazirla($nace->kod) : null,
                'soru_kodu' => $nace ? 'NACE-'.$nace->kod : null,
                'konu' => $konu,
                'zorluk' => $zorluk,
                'soru' => $s['soru'],
                'secenekler' => $s['secenekler'],
                'dogru_index' => $s['dogru_index'],
                'aciklama' => $s['aciklama'] ?? null,
                'kaynak' => $s['kaynak'] ?? null,
                'kaynaklar' => filled($s['kaynak'] ?? null) ? [['ad' => $s['kaynak'], 'url' => null, 'madde' => null, 'tarih' => now()->toDateString()]] : null,
                'durum' => 'taslak',
                'uretim_kaynagi' => 'ai',
                'inceleme_notu' => 'AI taslağı — kaynak ve doğru cevap uzman tarafından doğrulanmalı.',
            ]);
        }

        $this->sektorFiltre = $nace ? null : ($sektor ?: '');
        $this->konuFiltre = $nace ? null : $konu;
        $this->arama = $nace ? 'NACE-'.$nace->kod : null;
        $this->durumFiltre = 'taslak';
        $this->yenile();

        Notification::make()->title(count($uretilen).' soru üretildi — inceleyip yayımlayın')->success()->send();
    }

    /** NACE kapsama tablosundan: o NACE için eksik işe özgü soruları AI ile taslak üretir. */
    public function naceIcinUret(string $kod): void
    {
        $hedef = (int) config('isg.soru_bankasi.kapsama.ise_ozgu', 15);
        $mevcut = collect($this->naceKapsama['satirlar'])->firstWhere('kod', $kod);
        $eksik = max(3, min(20, $hedef - (int) ($mevcut['nace'] ?? 0) - (int) ($mevcut['bekleyen'] ?? 0)));

        $this->aiUret(null, 'genel_isg', 'orta', $eksik, $kod);
    }
}
