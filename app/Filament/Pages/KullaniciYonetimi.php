<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\User;
use App\Support\CanliKonumTakibi;
use App\Support\SayfaKatalogu;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Sahip hesabın diğer kullanıcılara (yeni kayıt olanlar dahil) hangi sayfalara ve
 * hangi firmalara erişebileceğini tek tek belirlediği ekran. Sadece sahip görür —
 * SinirliErisim trait'i KASITLI olarak kullanılmıyor, canAccess() doğrudan sahipMi()
 * kontrol eder (genel yetki sistemine bağlı olmasın, ayrıcalık yükseltme riski olmasın).
 */
class KullaniciYonetimi extends Page
{
    protected string $view = 'filament.pages.kullanici-yonetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar ve Kütüphaneler';

    /** Menüde gizli — Sistem & Ayarlar Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'kullanici-yonetimi';

    protected static ?string $title = 'Kullanıcı Yetkileri';

    public ?int $acikKullaniciId = null;

    /** @var array<string,bool> */
    public array $sayfaSecim = [];

    /** @var array<int,bool> */
    public array $firmaSecim = [];

    /** Abonelik / deneme süresi — boş bitiş = süresiz (App\Http\Middleware\CheckTrialPeriod). */
    public ?string $abonelikPlani = null;

    public ?string $abonelikBitis = null;

    public string $sayfaArama = '';

    public string $firmaArama = '';

    /** Hazır rol paketleri — tek tıkla yetki seçimi (SayfaKatalogu anahtarları). */
    public const ROL_SABLONLARI = [
        'igu' => [
            'ad' => 'İGU Paketi',
            'sayfalar' => [
                'ana-sayfa', 'kontrol-merkezi', 'firma-denetim-operasyon', 'risk-merkezi', 'profilim',
                'hizli-saha-bulgusu', 'ziyaret-modu', 'saha-ekrani', 'saha-denetimi', 'kontrol-karti', 'ai-saha-analizi',
                'dof-olustur', 'tespit-oneri-defteri', 'onayli-defter-nushalari', 'on-bilgilendirmeler',
                'ziyaret-programi', 'is-izin-formu', 'talimat-olustur', 'atama-yazilari', 'calisan-temsilcisi-secimi',
                'ceza-teblig', 'gecici-gorevlendirme', 'kkd-takip', 'kkd-formu', 'kkd-secim-matrisi',
                'periyodik-kontrol', 'ortam-olcumleri', 'ekipman-afisleri', 'toplu-makine-paketi', 'matbu-evrak-paketi',
                'dokuman-yonetimi', 'risk-sihirbazi', 'risk-degerlendirmesi-resource', 'jsa-degerlendirmesi',
                'kimyasal-yonetimi', 'kimyasal-risk-degerlendirmesi', 'kimyasal-sicili', 'pkd-sicili',
                'tehlike-risk-analitigi', 'tehlike-resource', 'risk-sablonu-resource', 'risk-prosedur-resource',
                'egitim-katilim', 'isbasi-egitim', 'egitim-yenileme-takibi', 'sertifika-olustur',
                'egitim-paketi-resource', 'uzaktan-egitim-firma-ayarlari', 'uzaktan-egitim-atama', 'uzaktan-egitim-takip',
                'uzaktan-egitim-yil-basi', 'uzaktan-egitim-sorulari', 'soru-bankasi', 'egitim-sorulari',
                'toolbox-konusmalari', 'ise-ozgu-egitim-konulari', 'yillik-planlar',
                'olay-kayitlari', 'is-kazasi-raporu', 'kaza-istatistikleri', 'olay-komuta360',
                'acil-durum-plani', 'acil-durum-krokisi', 'acil-durum-ekipleri', 'yangin-guvenligi', 'tatbikat-tutanagi', 'tabela-tasarimi',
                'kurul-toplantisi', 'firma-resource', 'calisan-resource', 'isg-profesyoneli-resource',
                'taseron-yonetimi', 'ziyaretci-yonetimi', 'yoklama-puantaj', 'isg-katip-sozlesmeleri', 'isg-katip-robot',
                'araclar', 'mevzuat', 'hazir-istemler', 'bildirim-merkezi',
            ],
        ],
        'hekim' => [
            'ad' => 'İşyeri Hekimi Paketi',
            'sayfalar' => [
                'ana-sayfa', 'kontrol-merkezi', 'profilim', 'saglik-gozetimi', 'muayene-formu', 'ise-donus-belgesi',
                'meslek-hastaligi-bildirimi', 'e-reetem', 'olay-kayitlari', 'is-kazasi-raporu', 'kaza-istatistikleri',
                'olay-komuta360', 'egitim-katilim', 'isbasi-egitim', 'egitim-yenileme-takibi', 'sertifika-olustur',
                'kurul-toplantisi', 'tespit-oneri-defteri', 'onayli-defter-nushalari', 'ziyaret-programi',
                'ziyaret-modu', 'saha-ekrani', 'dokuman-yonetimi', 'firma-resource', 'calisan-resource',
                'isg-katip-sozlesmeleri', 'araclar', 'mevzuat', 'bildirim-merkezi',
            ],
        ],
        'dsp' => [
            'ad' => 'DSP Paketi',
            'sayfalar' => [
                'ana-sayfa', 'profilim', 'saglik-gozetimi', 'muayene-formu', 'ise-donus-belgesi', 'olay-kayitlari',
                'ziyaret-programi', 'ziyaret-modu', 'saha-ekrani', 'dokuman-yonetimi',
                'firma-resource', 'calisan-resource', 'bildirim-merkezi',
            ],
        ],
        'saha_destek' => [
            'ad' => 'Saha Destek Paketi',
            'sayfalar' => [
                'ana-sayfa', 'profilim', 'saha-ekrani', 'ziyaret-modu', 'ziyaret-programi',
                'hizli-saha-bulgusu', 'saha-denetimi', 'kontrol-karti', 'dof-olustur',
                'kkd-takip', 'kkd-formu', 'ekipman-afisleri', 'periyodik-kontrol',
                'dokuman-yonetimi', 'firma-resource', 'calisan-resource',
                'yoklama-puantaj', 'ziyaretci-yonetimi', 'bildirim-merkezi',
            ],
        ],
    ];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->sahipMi();
    }

    #[Computed]
    public function kullanicilar()
    {
        return User::where('rol', '!=', 'sahip')->orderBy('name')->get();
    }

    /** @return array<int, array{id: int, name: string, unvan: string, enlem: ?float, boylam: ?float, guncelleme: ?string, harita_url: ?string}> */
    #[Computed]
    public function aktifKonumlar(): array
    {
        return CanliKonumTakibi::aktifKonumlar();
    }

    #[Computed]
    public function sayfaKatalogu(): array
    {
        return SayfaKatalogu::tumSayfalar();
    }

    /** @return array<string, string> */
    #[Computed]
    public function filtrelenmisSayfalar(): array
    {
        $tum = $this->sayfaKatalogu();
        $q = trim(\App\Support\TurkceMetin::kucuk($this->sayfaArama));
        if ($q === '') {
            return $tum;
        }

        return array_filter(
            $tum,
            fn (string $ad, string $anahtar) => str_contains(\App\Support\TurkceMetin::kucuk($ad.' '.$anahtar), $q),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    #[Computed]
    public function firmalar()
    {
        return Firma::withoutGlobalScopes()->orderBy('unvan')->get();
    }

    #[Computed]
    public function filtrelenmisFirmalar()
    {
        $q = trim(\App\Support\TurkceMetin::kucuk($this->firmaArama));
        if ($q === '') {
            return $this->firmalar;
        }

        return $this->firmalar
            ->filter(fn (Firma $f) => str_contains(\App\Support\TurkceMetin::kucuk($f->unvan.' '.$f->kisa_ad), $q))
            ->values();
    }

    public function ac(int $userId): void
    {
        if ($this->acikKullaniciId === $userId) {
            $this->acikKullaniciId = null;

            return;
        }

        $user = User::findOrFail($userId);

        $this->acikKullaniciId = $userId;
        $this->sayfaArama = '';
        $this->firmaArama = '';
        $this->sayfaSecim = array_fill_keys(array_keys($this->sayfaKatalogu()), false);
        foreach ($user->sayfaYetkileri()->pluck('sayfa_anahtari') as $anahtar) {
            $this->sayfaSecim[$anahtar] = true;
        }

        $this->abonelikPlani = $user->abonelik_plani;
        $this->abonelikBitis = $user->abonelik_bitis?->toDateString();

        $this->firmaSecim = [];
        $sahipOlunan = DB::table('firmalar')->where('user_id', $userId)->pluck('id');
        $paylasilan = DB::table('kullanici_firmalar')->where('user_id', $userId)->pluck('firma_id');
        foreach ($sahipOlunan->merge($paylasilan)->unique() as $firmaId) {
            $this->firmaSecim[$firmaId] = true;
        }
    }

    public function tumSayfalariSec(): void
    {
        $this->sayfaSecim = array_fill_keys(array_keys($this->sayfaKatalogu()), true);
    }

    public function tumSayfalariKaldir(): void
    {
        $this->sayfaSecim = array_fill_keys(array_keys($this->sayfaKatalogu()), false);
    }

    public function rolSablonuUygula(string $rol): void
    {
        $sablon = self::ROL_SABLONLARI[$rol] ?? null;
        if (! $sablon) {
            return;
        }

        $katalog = array_keys($this->sayfaKatalogu());
        $this->sayfaSecim = array_fill_keys($katalog, false);
        foreach ($sablon['sayfalar'] as $anahtar) {
            if (array_key_exists($anahtar, $this->sayfaSecim)) {
                $this->sayfaSecim[$anahtar] = true;
            }
        }
    }

    public function tumFirmalariSec(): void
    {
        $this->firmaSecim = array_fill_keys($this->firmalar()->pluck('id')->all(), true);
    }

    public function tumFirmalariKaldir(): void
    {
        $this->firmaSecim = [];
    }

    public function kaydet(): void
    {
        if (! $this->acikKullaniciId) {
            return;
        }

        $user = User::findOrFail($this->acikKullaniciId);
        abort_if($user->sahipMi(), 403);

        $this->validate([
            'abonelikPlani' => ['required', 'in:'.implode(',', array_keys(User::ABONELIK_PLANLARI))],
            'abonelikBitis' => ['nullable', 'date'],
        ], attributes: ['abonelikPlani' => 'abonelik planı', 'abonelikBitis' => 'bitiş tarihi']);

        $user->update([
            'abonelik_plani' => $this->abonelikPlani,
            'abonelik_bitis' => $this->abonelikBitis ?: null,
        ]);

        $secilenSayfalar = array_keys(array_filter($this->sayfaSecim));
        $user->sayfaYetkileri()->delete();
        $user->sayfaYetkileri()->createMany(
            collect($secilenSayfalar)->map(fn (string $anahtar) => ['sayfa_anahtari' => $anahtar])->all()
        );

        // Sahip olduğu (firmalar.user_id) firmalara ek paylaşım satırı açmaya gerek yok,
        // sadece kendi SAHİBİ OLMADIĞI ama seçilen firmalar için paylaşım kaydı tutulur.
        $kendiFirmaIdleri = DB::table('firmalar')->where('user_id', $user->id)->pluck('id')->all();
        $secilenFirmaIdleri = array_keys(array_filter($this->firmaSecim));
        $paylasimIdleri = array_values(array_diff($secilenFirmaIdleri, $kendiFirmaIdleri));

        DB::table('kullanici_firmalar')->where('user_id', $user->id)->delete();
        if ($paylasimIdleri !== []) {
            DB::table('kullanici_firmalar')->insert(array_map(fn ($firmaId) => [
                'user_id' => $user->id,
                'firma_id' => $firmaId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $paylasimIdleri));
        }

        unset($this->kullanicilar);

        \App\Support\DenetimKaydedici::kaydet(
            'Kullanıcı Yetkileri',
            'guncelledi',
            $user->name.' — plan: '.$this->abonelikPlani.($this->abonelikBitis ? ' ('.$this->abonelikBitis.')' : ' (süresiz)').', '.count($secilenSayfalar).' sayfa, '.count($paylasimIdleri).' paylaşılan firma',
        );

        Notification::make()
            ->title($user->name.' için yetkiler kaydedildi')
            ->success()
            ->send();
    }

    /** Bitişi mevcut bitişten (gelecekteyse) ya da bugünden uzatır; "Kaydet" ile yazılır. */
    public function abonelikUzat(int $gun): void
    {
        $bitis = $this->abonelikBitis ? Carbon::parse($this->abonelikBitis) : null;
        $taban = $bitis && $bitis->isFuture() ? $bitis : today();

        $this->abonelikBitis = $taban->addDays($gun)->toDateString();
    }

    public function aktifDegistir(int $userId): void
    {
        $user = User::findOrFail($userId);
        abort_if($user->sahipMi(), 403);
        $yeniDurum = ! $user->aktif;
        $user->update(['aktif' => $yeniDurum]);
        unset($this->kullanicilar);

        \App\Support\DenetimKaydedici::kaydet(
            'Kullanıcı Yetkileri',
            'guncelledi',
            $user->name.' — hesap '.($yeniDurum ? 'aktif edildi' : 'pasife alındı'),
        );
    }
}
