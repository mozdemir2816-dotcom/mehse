<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AileAnaSayfasi;
use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\ImzaSecenegi;
use App\Models\AcilDurumPlani as PlanModel;
use App\Models\Firma;
use App\Support\AcilDurumHazirlik;
use App\Support\AcilDurumKapakUretici;
use App\Support\AcilDurumPlaniUretici;
use App\Support\AcilDurumSektor;
use App\Support\AcilDurumWordUretici;
use App\Support\ExcelBellek;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

/**
 * Acil Durum Eylem Planı — isgpratik 19-21, 146-154.jpg. Firma seçilir, konu
 * sayfaları + destek ekipleri + kapak çerçevesi ayarlanır → PDF üretilir.
 * Ayrıca 7 acil durum afişi (A3/A4 talimat) indirilir.
 */
class AcilDurumPlani extends Page
{
    use AileAnaSayfasi;
    use SinirliErisim;

    protected string $view = 'filament.pages.acil-durum-plani';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|UnitEnum|null $navigationGroup = 'Acil Durum & Yangın';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'acil-durum-plani';

    protected static ?string $title = 'Acil Durum Eylem Planı';

    protected static ?string $navigationLabel = 'Acil Durum Planı';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    // Form durumu (seçili plana bağlanır)
    public ?string $dokumanNo = null;

    public ?string $raporTarihi = null;

    public string $kapakCercevesi = 'klasik';

    public ?string $revizyonNo = null;

    public ?string $toplanmaYeri = null;

    public ?string $disaridanEtkileyebilecekIsyerleri = null;

    /** @var array<int, string> seçili konu anahtarları */
    public array $konular = [];

    /** @var array<string, string> ekip anahtarı => virgülle ayrık isimler */
    public array $ekipMetni = [];

    /** isgsuite plan sihirbazı 2-3. adım: tedbirler, müdahale, iletişim, onay / tatbikat (bkz. AcilDurumHazirlik). */
    public array $uygulama = [];

    // Afiş seçimi
    public string $afisTipi = 'yangin';

    public string $afisEbat = 'a4';

    /**
     * Gerçek referans Word/PowerPoint şablonu seçimi — kullanıcı zamanla farklı
     * biçimli acil durum planı örnekleri ekleyecek (`config isg.acil_durum.
     * word_sablonlari`); "Word (Orijinal Şablon)" ve "Kapak Sayfası" aksiyonları
     * seçili şablona göre üretir.
     */
    public string $sablonId = 'fom';

    /** Risk puanları / çevresel riskler bu planda boştu, kullanıcının şablonundan getirildi. */
    public bool $puanlarSablondan = false;

    /** Firmalar listesinden "Acil Durum Planı" satır aksiyonuyla ?firma= ile gelinir. */
    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

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

    public function plan(): ?PlanModel
    {
        return $this->firma ? PlanModel::firmaIcin($this->firma) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma);

        $plan = $this->plan();

        if (! $plan) {
            return;
        }

        $this->dokumanNo = $plan->dokuman_no;
        $this->raporTarihi = $plan->rapor_tarihi?->toDateString() ?? now()->toDateString();
        $this->kapakCercevesi = $plan->kapak_cercevesi ?: 'klasik';
        $this->revizyonNo = $plan->revizyon_no;
        $this->toplanmaYeri = $plan->toplanma_yeri;
        $this->disaridanEtkileyebilecekIsyerleri = $plan->disaridan_etkileyebilecek_isyerleri;
        $this->konular = $plan->konular ?? [];
        $this->uygulama = array_replace(AcilDurumHazirlik::varsayilan(), $plan->uygulama ?? []);

        // Sektör: kayıtlı seçim yoksa NACE önerisi (uzman değiştirebilir).
        if (! AcilDurumSektor::gecerliMi($this->uygulama['sektor'] ?? null)) {
            $this->uygulama['sektor'] = AcilDurumSektor::oner($this->firma);
        }

        // Boş kutular hazır örnek metinle gelir; uzman düzeltip Kaydet'e basar.
        foreach (config('isg.acil_durum.ornek_metinler', []) as $alan => $metin) {
            if (blank($this->uygulama[$alan] ?? null)) {
                $this->uygulama[$alan] = $metin;
            }
        }

        // Aynı bölgedeki işyerleri: bir kez doldurulan puanlar boş planlara gelir.
        $onceki = $this->uygulama;
        $this->uygulama = AcilDurumHazirlik::sablonlaTamamla($this->uygulama, Filament::auth()->user());
        $this->puanlarSablondan = $onceki !== $this->uygulama;

        unset($this->hazirlik);
        $this->ekipMetni = collect(config('isg.acil_durum.ekipler'))
            ->mapWithKeys(fn ($ad, $k) => [$k => implode(', ', $plan->ekipListesi()[$k] ?? [])])
            ->all();
    }

    public function konuToggle(string $anahtar): void
    {
        $this->konular = in_array($anahtar, $this->konular, true)
            ? array_values(array_diff($this->konular, [$anahtar]))
            : [...$this->konular, $anahtar];
    }

    public function tumKonular(bool $sec): void
    {
        $this->konular = $sec
            ? collect(config('isg.acil_durum.konular'))->pluck('anahtar')->all()
            : [];
    }

    public function kaydet(): void
    {
        $plan = $this->plan();

        if (! $plan) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $plan->forceFill([
            'dokuman_no' => $this->dokumanNo,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => null, // saving hook yeniden hesaplar
            'kapak_cercevesi' => $this->kapakCercevesi,
            'revizyon_no' => $this->revizyonNo,
            'toplanma_yeri' => $this->toplanmaYeri,
            'disaridan_etkileyebilecek_isyerleri' => $this->disaridanEtkileyebilecekIsyerleri,
            'konular' => $this->konular ?: null,
            'uygulama' => AcilDurumHazirlik::temizle($this->uygulama),
            'ekipler' => collect($this->ekipMetni)
                ->map(fn ($metin) => array_values(array_filter(array_map('trim', explode(',', (string) $metin)))))
                ->all(),
        ])->save();

        unset($this->hazirlik, $this->portfoy);
        $this->puanlarSablondan = false;

        Notification::make()->title('Acil durum planı kaydedildi')->success()->send();

        // İlk doldurulan plan, diğer firmalar için şablon olur.
        $user = Filament::auth()->user();
        $u = $plan->uygulama ?? [];
        if ($user && AcilDurumHazirlik::bosMu(AcilDurumHazirlik::kullaniciSablonu($user))
            && ! AcilDurumHazirlik::bosMu(array_intersect_key($u, array_flip(AcilDurumHazirlik::SABLON_ALANLARI)))) {
            AcilDurumHazirlik::sablonaKaydet($user, $u);
            Notification::make()->title('Risk puanları varsayılan yapıldı')->body('Puanı girilmemiş diğer firmalarınızda bu puanlar otomatik gelecek.')->info()->send();
        }
    }

    /** Ekrandaki risk puanları ve çevresel riskleri tüm firmalar için varsayılan yapar. */
    public function puanlariVarsayilanYap(): void
    {
        AcilDurumHazirlik::sablonaKaydet(Filament::auth()->user(), $this->uygulama);

        Notification::make()->title('Varsayılan risk puanları güncellendi')->body('Puanı girilmemiş firmalarda bu değerler gelecek; puanı kayıtlı planlar değişmez.')->success()->send();
    }

    public function ornekMetin(string $alan): void
    {
        $metin = config('isg.acil_durum.ornek_metinler.'.$alan);

        if (is_string($metin)) {
            $this->uygulama[$alan] = $metin;
        }
    }

    public function iletisimEkle(): void
    {
        $this->uygulama['iletisim'][] = ['ad' => '', 'telefon' => '', 'aciklama' => ''];
    }

    public function iletisimSil(int $i): void
    {
        unset($this->uygulama['iletisim'][$i]);
        $this->uygulama['iletisim'] = array_values($this->uygulama['iletisim']);
    }

    /** Seçili planın hazırlık kontrolü (kayıtlı veriden). */
    #[Computed]
    public function hazirlik(): ?array
    {
        $plan = $this->plan();

        return $plan ? AcilDurumHazirlik::kontrol($plan->fresh('firma')) : null;
    }

    /** Tüm işyerlerinin acil durum hazırlığı (isgsuite plan portföyü). */
    #[Computed]
    public function portfoy(): Collection
    {
        return AcilDurumHazirlik::portfoy((int) Filament::auth()->id());
    }

    public function portfoyExcel()
    {
        ExcelBellek::artir();
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Acil Durum Hazırlığı');
        $s->fromArray(['İşyeri', 'Tehlike sınıfı', 'Plan tarihi', 'Gözden geçirme', 'Hazırlık %', 'Durum', 'Eksik başlıklar'], null, 'A1');
        $s->getStyle('A1:G1')->getFont()->setBold(true);
        foreach ($this->portfoy->values() as $n => $r) {
            $k = $r['kontrol'];
            $s->fromArray([
                $r['firma']->unvan, $r['firma']->tehlikeSinifiEtiketi(),
                $r['plan']?->rapor_tarihi?->format('d.m.Y'), $r['plan']?->gecerlilik_tarihi?->format('d.m.Y'),
                $k ? $k['yuzde'] : null, $k ? AcilDurumHazirlik::DURUM_ETIKET[$k['durum']] : 'Plan yok',
                $k ? collect($k['kontroller'])->where('tamam', false)->pluck('baslik')->implode('; ') : null,
            ], null, 'A'.($n + 2));
        }
        foreach (range('A', 'G') as $c) {
            $s->getColumnDimension($c)->setAutoSize(true);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'adp').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'acil-durum-hazirligi.xlsx');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Plan Çıktısı')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([
                    Radio::make('bicim')
                        ->label('Dosya biçimi')
                        ->options(['pdf' => 'PDF', 'word' => 'Word (.docx — düzenlenebilir)'])
                        ->default('pdf')
                        ->inline()
                        ->required(),
                    ImzaSecenegi::alan(),
                ])
                ->action(function (array $data) {
                    $this->kaydet();

                    // Word: PDF ile aynı içerik ve düzen (Kapsamlı ADEP), seçili şablondan bağımsız.
                    return ($data['bicim'] ?? 'pdf') === 'word'
                        ? AcilDurumWordUretici::docx($this->plan(), 'fom', ImzaSecenegi::secili($data))
                        : AcilDurumPlaniUretici::pdf($this->plan(), ImzaSecenegi::secili($data));
                }),

            Action::make('word')
                ->label('Word')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->tooltip('Seçili Word şablonuyla düzenlenebilir plan (.docx). Kapsamlı ADEP, PDF ile aynı içeriği FOM belgesinin biçimiyle verir.')
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $this->kaydet();

                    return AcilDurumWordUretici::docx($this->plan(), $this->sablonId, ImzaSecenegi::secili($data));
                }),

            Action::make('kapakSayfasi')
                ->label('Kapak Sayfası')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => $this->firma !== null && filled(config('isg.acil_durum.word_sablonlari.'.$this->sablonId.'.kapak_dosya')))
                ->tooltip('Word şablonuyla eşleşen kapak sayfası (PowerPoint) — ayrı yazdırılıp plan çıktısının önüne konur.')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => AcilDurumKapakUretici::pptx($this->plan(), $this->sablonId)),

            Action::make('krokiPlani')
                ->label('Kroki Planı Hazırla')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->url(fn () => AcilDurumKrokisi::getUrl(['firma' => $this->firma->id])),

            Action::make('tahliyePlani')
                ->label('Tahliye Planı Görseli')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('İşyerinin kat/bölüm krokisi üzerine kaçış yolları, toplanma yeri ve acil durum ekipmanlarının işaretlendiği tahliye planı — İşyerlerinde Acil Durumlar Hakkında Yönetmelik gereği zorunludur.')
                ->fillForm(fn (): array => ['tahliye_plani_gorseli' => $this->plan()?->tahliye_plani_gorseli])
                ->schema([
                    FileUpload::make('tahliye_plani_gorseli')
                        ->label('Tahliye planı / kroki görseli')
                        ->image()->imageEditor()
                        ->disk('public')->directory('acil-durum-tahliye')->maxSize(4096),
                ])
                ->action(function (array $data): void {
                    $this->plan()?->forceFill($data)->save();
                    Notification::make()->title('Tahliye planı görseli kaydedildi')->success()->send();
                }),
        ];
    }

    public function afisIndir()
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        return AcilDurumPlaniUretici::afis($this->firma, $this->afisTipi, $this->afisEbat);
    }

    /** Firmaya otomatik tanımlı (planın seçili konularından türeyen) afiş anahtarları. */
    #[Computed]
    public function afisTipleri(): array
    {
        return $this->plan() ? AcilDurumPlaniUretici::afisTipleri($this->plan()) : [];
    }

    public function afislerZipIndir()
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (empty($this->afisTipleri)) {
            Notification::make()->title('Bu firma için otomatik tanımlı afiş yok')
                ->body('Önce Acil Durum Planı konularını kaydedin.')->warning()->send();

            return null;
        }

        return AcilDurumPlaniUretici::afisZip($this->firma, $this->afisTipleri, $this->afisEbat);
    }
}
