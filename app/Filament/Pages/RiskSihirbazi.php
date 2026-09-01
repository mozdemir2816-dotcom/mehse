<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Models\Tehlike;
use App\Support\RiskKutuphanesi;
use App\Support\RiskSkorlama;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Risk Değerlendirme Sihirbazı — isgpratik 10-18.jpg.
 * 6 adım: Firma Bilgileri → Ekleme Yöntemi → Risk Ekleme → Tercihler →
 *         Risklerim → Önizleme & Kaydet.
 *
 * Faz 3a: "Manuel Seçim" yöntemi tam; AI / Şablon / Kayıtlı / Excel yöntemleri
 * ile PDF çıktısı sonraki fazda (ekran görüntüleri geldikçe).
 */
class RiskSihirbazi extends Page
{
    protected string $view = 'filament.pages.risk-sihirbazi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Risk Yönetimi';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'risk-degerlendirme';

    protected static ?string $title = 'Risk Değerlendirme';

    protected static ?string $navigationLabel = 'Risk Değerlendirme';

    public const ADIMLAR = [
        1 => 'Firma Bilgileri',
        2 => 'Ekleme Yöntemi',
        3 => 'Risk Ekleme',
        4 => 'Tercihler',
        5 => 'Risklerim',
        6 => 'Önizleme & PDF',
    ];

    /** Faz 3a'da yalnız "manuel" uygulanıyor. */
    public const YONTEMLER = [
        'ai' => ['ad' => 'Yapay Zeka Sohbeti ile Risk Üret', 'onerilen' => true, 'hazir' => false,
            'aciklama' => 'AI önce işyeriniz hakkında sorular sorar, sonra sektöre özel risk maddeleri önerir.'],
        'manuel' => ['ad' => 'Manuel Seçim', 'onerilen' => false, 'hazir' => true,
            'aciklama' => 'Risk Kütüphanesinden kategori bazlı seçim yapın.'],
        'sablon' => ['ad' => 'Şablonlar & Paylaşılanlar', 'onerilen' => false, 'hazir' => false,
            'aciklama' => 'Kendi şablonlarınız veya paylaşılan şablonlar.'],
        'kayitli' => ['ad' => 'Kayıtlı Risklerim', 'onerilen' => false, 'hazir' => false,
            'aciklama' => 'Daha önce eklediğiniz, klasörlenmiş risk maddeleri.'],
        'excel' => ['ad' => "Excel'den Yükle", 'onerilen' => false, 'hazir' => false,
            'aciklama' => 'Kendi Excel risk değerlendirmenizi yükleyin.'],
    ];

    public int $adim = 1;

    public ?int $firmaId = null;

    public ?string $raporTarihi = null;

    public ?string $gecerlilikTarihi = null;

    public string $yontem = 'matris_5x5';

    public ?string $yontemSecim = null;

    /** @var array<int, int> açık accordion kategori id'leri */
    public array $acikKategoriler = [];

    /** @var array<int, array<string, mixed>> seçilen risk maddeleri */
    public array $secilenler = [];

    // Adım 4 — Tercihler
    public bool $etkilenenDiger = false;

    public ?string $varsayilanTermin = null;

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();
        $this->gecerlilikTarihiHesapla();
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan (computed) veriler
    |--------------------------------------------------------------------------
    */

    /** @return array<int, string> kullanıcının aktif firmaları */
    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->get(['id', 'unvan', 'tehlike_sinifi'])
            ->mapWithKeys(fn (Firma $f) => [
                $f->id => $f->unvan.' ('.$f->tehlikeSinifiEtiketi().')',
            ])
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<int, \App\Models\TehlikeKategorisi> */
    #[Computed]
    public function kategoriler(): Collection
    {
        return RiskKutuphanesi::kategoriler();
    }

    #[Computed]
    public function fineKinney(): bool
    {
        return $this->yontem === 'fine_kinney';
    }

    /** @return array<string, int> düzey → madde sayısı */
    #[Computed]
    public function duzeyDagilimi(): array
    {
        $dagilim = [];

        foreach ($this->secilenler as $m) {
            $sonuc = $this->maddePuani($m);
            $duzey = $sonuc['puan'] ? $sonuc['duzey'] : 'Puansız';
            $dagilim[$duzey] = ($dagilim[$duzey] ?? 0) + 1;
        }

        return $dagilim;
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 1 — Firma & tarih
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma);
        $this->gecerlilikTarihiHesapla();
    }

    public function updatedRaporTarihi(): void
    {
        $this->gecerlilikTarihiHesapla();
    }

    public function updatedYontem(): void
    {
        unset($this->fineKinney);
    }

    private function gecerlilikTarihiHesapla(): void
    {
        if (! $this->raporTarihi) {
            return;
        }

        $yil = $this->firma?->riskGecerlilikYili() ?? 4;
        $this->gecerlilikTarihi = Carbon::parse($this->raporTarihi)->addYears($yil)->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 2 — Yöntem
    |--------------------------------------------------------------------------
    */

    public function yontemSec(string $anahtar): void
    {
        if (! (self::YONTEMLER[$anahtar]['hazir'] ?? false)) {
            Notification::make()
                ->title('Bu yöntem yakında')
                ->body('Şu an yalnız "Manuel Seçim" kullanılabilir.')
                ->warning()->send();

            return;
        }

        $this->yontemSecim = $anahtar;
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 3 — Risk ekleme (manuel / kütüphane)
    |--------------------------------------------------------------------------
    */

    public function kategoriToggle(int $id): void
    {
        $this->acikKategoriler = in_array($id, $this->acikKategoriler, true)
            ? array_values(array_diff($this->acikKategoriler, [$id]))
            : [...$this->acikKategoriler, $id];
    }

    public function tehlikeEkle(int $tehlikeId): void
    {
        if ($this->tehlikeSecili($tehlikeId)) {
            return;
        }

        $t = Tehlike::find($tehlikeId);

        if ($t) {
            $this->secilenler[] = RiskKutuphanesi::maddeyeCevir($t);
        }
    }

    public function kategoriTumunuEkle(int $kategoriId): void
    {
        foreach (Tehlike::where('tehlike_kategorisi_id', $kategoriId)->get() as $t) {
            if (! $this->tehlikeSecili($t->id)) {
                $this->secilenler[] = RiskKutuphanesi::maddeyeCevir($t);
            }
        }
    }

    public function tehlikeSecili(int $tehlikeId): bool
    {
        return collect($this->secilenler)->contains('tehlike_id', $tehlikeId);
    }

    public function maddeCikar(string $anahtar): void
    {
        $this->secilenler = array_values(array_filter(
            $this->secilenler,
            fn ($m) => $m['anahtar'] !== $anahtar,
        ));
    }

    public function bosMaddeEkle(): void
    {
        $this->secilenler[] = RiskKutuphanesi::bosMadde();

        if ($this->adim < 5) {
            $this->adim = 5;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Adım navigasyonu
    |--------------------------------------------------------------------------
    */

    public function ileri(): void
    {
        if (! $this->adimGecerli($this->adim)) {
            return;
        }

        $this->adim = min(6, $this->adim + 1);
    }

    public function geri(): void
    {
        $this->adim = max(1, $this->adim - 1);
    }

    public function adimaGit(int $hedef): void
    {
        if ($hedef <= $this->adim) {
            $this->adim = max(1, $hedef);

            return;
        }

        for ($a = $this->adim; $a < $hedef; $a++) {
            if (! $this->adimGecerli($a)) {
                return;
            }
        }

        $this->adim = $hedef;
    }

    public function adimGecerli(int $adim): bool
    {
        return match ($adim) {
            1 => filled($this->firmaId) && filled($this->raporTarihi),
            2 => filled($this->yontemSecim),
            3 => count($this->secilenler) > 0,
            4 => true,
            5 => $this->tumMaddelerPuanli(),
            default => true,
        };
    }

    public function tumMaddelerPuanli(): bool
    {
        if (count($this->secilenler) === 0) {
            return false;
        }

        foreach ($this->secilenler as $m) {
            if (blank($m['olasilik'] ?? null) || blank($m['siddet'] ?? null)) {
                return false;
            }

            if ($this->fineKinney && blank($m['frekans'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /** Blade için: bir maddenin canlı puan/düzeyi. */
    public function maddePuani(array $madde): array
    {
        return RiskSkorlama::hesapla(
            $this->yontem,
            $this->sayiVeyaNull($madde['olasilik'] ?? null),
            $this->sayiVeyaNull($madde['siddet'] ?? null),
            $this->sayiVeyaNull($madde['frekans'] ?? null),
        );
    }

    private function sayiVeyaNull($deger): ?float
    {
        return ($deger === null || $deger === '') ? null : (float) $deger;
    }

    /*
    |--------------------------------------------------------------------------
    | Adım 6 — Kaydet
    |--------------------------------------------------------------------------
    */

    public function kaydet()
    {
        if (! $this->adimGecerli(1) || ! $this->tumMaddelerPuanli()) {
            Notification::make()->title('Eksik bilgi var')->danger()->send();

            return null;
        }

        $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($this->firmaId);

        $rd = new RiskDegerlendirmesi([
            'firma_id' => $firma->id,
            'yontem' => $this->yontem,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => $this->gecerlilikTarihi,
            'durum' => 'taslak',
        ]);
        $rd->save();

        foreach (array_values($this->secilenler) as $i => $m) {
            $rd->maddeler()->create([
                'sira' => $i + 1,
                'bolum' => $m['bolum'] ?? null,
                'faaliyet' => $m['faaliyet'] ?? null,
                'tehlike' => ($m['tehlike'] ?? '') ?: '(tanımsız)',
                'risk' => $m['risk'] ?? null,
                'mevcut_onlem' => $m['mevcut_onlem'] ?? null,
                'etkilenen_calisan' => true,
                'etkilenen_diger' => $this->etkilenenDiger,
                'olasilik' => $this->sayiVeyaNull($m['olasilik'] ?? null),
                'frekans' => $this->fineKinney ? $this->sayiVeyaNull($m['frekans'] ?? null) : null,
                'siddet' => $this->sayiVeyaNull($m['siddet'] ?? null),
                'oneri' => $m['oneri'] ?? null,
                'sorumlu' => $m['sorumlu'] ?? null,
                'termin' => ($m['termin'] ?? '') ?: $this->varsayilanTermin,
                'durum' => 'acik',
            ]);
        }

        Notification::make()
            ->title('Risk değerlendirmesi kaydedildi')
            ->body($rd->belge_no.' · '.count($this->secilenler).' madde')
            ->success()->send();

        return redirect(RiskDegerlendirmesiResource::getUrl('edit', ['record' => $rd]));
    }
}
