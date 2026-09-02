<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\SahaAnalizi;
use App\Support\GeminiSahaAnalizi;
use App\Support\SahaAnaliziUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * AI Saha Analizi — isgpratik AI SAHA ANALİZİ/1-6.jpg. Saha fotoğrafları
 * Gemini vision'a gönderilir, her fotoğraftaki uygunsuzluk için bulgu üretilir;
 * seçilen bulgulardan isgpratik'in gerçek "İSG Saha Gözetim Raporu" ile
 * birebir aynı PDF oluşturulur.
 */
class AiSahaAnalizi extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.ai-saha-analizi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'ai-saha-analizi';

    protected static ?string $title = 'AI Saha Analizi';

    protected static ?string $navigationLabel = 'AI Saha Analizi';

    public const MAX_FOTOGRAF = 10;

    public ?int $firmaId = null;

    public ?string $alanBolge = null;

    public ?string $gozetimTarihAraligi = null;

    public ?string $raporTarihi = null;

    public ?string $gozetimYapan = null;

    public ?string $gozetimYapanSertifikaNo = null;

    public ?string $sorumluKisi = 'İşveren / İşveren Vekili';

    public ?string $isverenVekiliAdi = null;

    public ?string $baglamNotu = null;

    /** @var array<int, UploadedFile> */
    public array $yeniFotograflar = [];

    /** @var array<int, string> zaten yüklenip analiz edilmiş fotoğraf yolları */
    public array $yuklenenFotograflar = [];

    /** @var array<int, array{foto_yolu: ?string, bina_bolge: ?string, kategori: ?string, tespit: string, oneriler_metni: string, yasal_gerekce: ?string, risk_derecesi: int, secili: bool}> */
    public array $bulgular = [];

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function riskDereceleri(): array
    {
        return config('isg.saha_analiz.risk_dereceleri');
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiSahaAnalizi::aktifMi();
    }

    /** @return Collection<int, SahaAnalizi> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->sahaAnalizleri()->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->gecmisKayitlar);

        $this->gozetimYapan = $this->firma?->igu?->ad_soyad;
        $this->gozetimYapanSertifikaNo = $this->firma?->igu?->sertifika_no;
        $this->isverenVekiliAdi = $this->firma?->isveren_vekili ?: $this->firma?->isveren_ad;
    }

    public function bulguSil(int $index): void
    {
        unset($this->bulgular[$index]);
        $this->bulgular = array_values($this->bulgular);
    }

    /**
     * Seçili bulguları DÖF Oluştur'un madde listesine aktarır (isgpratik'teki
     * "Seçilenleri Çoklu DÖF'e Aktar" — Bina/Bölge ve Yasal Gerekçe, DÖF'ün
     * şeması bunları ayrı tutmadığından tespit/öneri metnine katlanır).
     */
    public function secilenleriDofeAktar()
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        $secili = collect($this->bulgular)->filter(fn ($b) => $b['secili'] ?? false)->values();

        if ($secili->isEmpty()) {
            Notification::make()->title('En az bir bulgu seçin')->danger()->send();

            return null;
        }

        $maddeler = $secili->map(fn ($b) => [
            'tespit' => filled($b['bina_bolge'] ?? null) ? "[{$b['bina_bolge']}] {$b['tespit']}" : $b['tespit'],
            'oncelik' => match ((int) ($b['risk_derecesi'] ?? 3)) {
                1 => 'kritik',
                2 => 'yuksek',
                3 => 'orta',
                default => 'dusuk',
            },
            'oneri' => trim(($b['oneriler_metni'] ?? '').(filled($b['yasal_gerekce'] ?? null) ? "\n\nYasal dayanak: {$b['yasal_gerekce']}" : '')) ?: null,
            'sorumlu' => null,
            'termin' => null,
            'durum' => 'acik',
        ])->values()->all();

        session(['dof_aktarim' => ['firma_id' => $this->firma->id, 'maddeler' => $maddeler]]);

        return $this->redirect(DofOlustur::getUrl());
    }

    /*
    |--------------------------------------------------------------------------
    | Fotoğraf analizi
    |--------------------------------------------------------------------------
    */

    public function fotograflariAnalizEt(): void
    {
        if (! $this->yeniFotograflar) {
            Notification::make()->title('Önce en az bir fotoğraf ekleyin')->danger()->send();

            return;
        }

        if (count($this->yuklenenFotograflar) + count($this->yeniFotograflar) > static::MAX_FOTOGRAF) {
            Notification::make()->title('En fazla '.static::MAX_FOTOGRAF.' fotoğraf yükleyebilirsiniz')->danger()->send();

            return;
        }

        if (! GeminiSahaAnalizi::aktifMi()) {
            Notification::make()->title('Yapay zeka kullanılamıyor')->body('Gemini API anahtarı tanımlı değil.')->warning()->send();

            return;
        }

        $yeniYollar = [];

        foreach ($this->yeniFotograflar as $dosya) {
            $yeniYollar[] = $dosya->store('saha-analiz-foto', 'public');
        }

        $bulunanlar = GeminiSahaAnalizi::analizEt($yeniYollar, $this->baglamNotu);

        foreach ($bulunanlar as $b) {
            $this->bulgular[] = [
                'foto_yolu' => $b['foto_yolu'],
                'bina_bolge' => $b['bina_bolge'],
                'kategori' => $b['kategori'],
                'tespit' => $b['tespit'],
                'oneriler_metni' => implode("\n", $b['oneriler']),
                'yasal_gerekce' => $b['yasal_gerekce'],
                'risk_derecesi' => $b['risk_derecesi'],
                'secili' => true,
            ];
        }

        $this->yuklenenFotograflar = [...$this->yuklenenFotograflar, ...$yeniYollar];
        $this->yeniFotograflar = [];

        if (! $bulunanlar) {
            Notification::make()->title('Uygunsuzluk tespit edilmedi')->body('Yüklenen fotoğraflarda belirgin bir tehlike bulunamadı.')->info()->send();
        } else {
            Notification::make()->title(count($bulunanlar).' bulgu tespit edildi')->success()->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    /** @return array<int, array<string, mixed>> */
    private function secilenBulgular(): array
    {
        return collect($this->bulgular)
            ->filter(fn ($b) => $b['secili'] ?? false)
            ->map(fn ($b) => [
                'foto_yolu' => $b['foto_yolu'] ?? null,
                'bina_bolge' => $b['bina_bolge'] ?? null,
                'kategori' => $b['kategori'] ?? null,
                'tespit' => $b['tespit'] ?? '',
                'oneriler' => array_values(array_filter(preg_split('/\r\n|\r|\n/', (string) ($b['oneriler_metni'] ?? '')))),
                'yasal_gerekce' => $b['yasal_gerekce'] ?? null,
                'risk_derecesi' => (int) ($b['risk_derecesi'] ?? 3),
            ])
            ->values()
            ->all();
    }

    private function kaydet(): ?SahaAnalizi
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        $bulgular = $this->secilenBulgular();

        if (! $bulgular) {
            Notification::make()->title('En az bir bulgu seçin')->danger()->send();

            return null;
        }

        $s = new SahaAnalizi([
            'firma_id' => $this->firma->id,
            'alan_bolge' => $this->alanBolge,
            'gozetim_tarih_araligi' => $this->gozetimTarihAraligi,
            'rapor_tarihi' => $this->raporTarihi,
            'gozetim_yapan' => $this->gozetimYapan,
            'gozetim_yapan_sertifika_no' => $this->gozetimYapanSertifikaNo,
            'gozetim_yapan_kase' => $this->firma->igu?->kase_gorseli,
            'sorumlu_kisi' => $this->sorumluKisi,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'baglam_notu' => $this->baglamNotu,
            'bulgular' => $bulgular,
        ]);
        $s->save();

        unset($this->gecmisKayitlar);

        return $s;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Rapor Oluştur (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null && collect($this->bulgular)->contains('secili', true))
                ->action(function () {
                    $s = $this->kaydet();

                    if (! $s) {
                        return null;
                    }

                    Notification::make()->title('Saha gözetim raporu kaydedildi')->body($s->belge_no)->success()->send();

                    return SahaAnaliziUretici::pdf($s);
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $s = $this->firma?->sahaAnalizleri()->find($id);

        return $s ? SahaAnaliziUretici::pdf($s) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->sahaAnalizleri()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
