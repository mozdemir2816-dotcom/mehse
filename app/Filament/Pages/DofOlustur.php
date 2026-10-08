<?php

namespace App\Filament\Pages;

use App\Models\DofRaporu;
use App\Models\OlayKaydi;
use App\Models\Firma;
use App\Filament\Support\ImzaSecenegi;
use App\Support\DofRaporuUretici;
use App\Support\DofTabloOkuyucu;
use App\Support\GeminiOneriDanismani;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * DÖF Oluştur (Çoklu DÖF) — isgpratik 158.jpg. Saha gözetimi sonucu birden
 * çok Düzeltici Önleyici Faaliyet maddesini tek raporda toplar; "AI ile Öneri
 * Al" GeminiOneriDanismani'yı (Tespit Öneri Defteri ile aynı) kullanır.
 */
class DofOlustur extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    use \App\Filament\Concerns\HazirRaporYukleme;
    use WithFileUploads;

    protected string $view = 'filament.pages.dof-olustur';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Saha Kontrolleri';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'dof';

    protected static ?string $title = 'DÖF Oluştur';

    protected static ?string $navigationLabel = 'DÖF Oluştur';

    public ?int $firmaId = null;

    public ?string $alanBolge = null;

    public ?string $gozetimTarihAraligi = null;

    public ?string $raporTarihi = null;

    public ?string $gozetimYapan = null;

    public ?string $gozetimYapanSertifikaNo = null;

    public ?string $sorumluKisi = 'İşveren / İşveren Vekili';

    public ?string $isverenVekiliAdi = null;

    /** @var array<int, array{tespit: string, oncelik: string, oneri: ?string, sorumlu: ?string, termin: ?string, durum: string}> */
    public array $maddeler = [];

    /** DÖF bir Olay Kaydı'ndan aktarıldıysa — kaydedilince olaya bağlanır. */
    public ?int $kaynakOlayKaydiId = null;

    public ?string $yeniTespit = null;

    public string $yeniOncelik = 'orta';

    public ?string $yeniOneri = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniTermin = null;

    /** Sahada tespit edilen uygunsuzluğun fotoğraf kanıtı (yeni madde eklerken). */
    public $yeniFoto = null;

    /** Listedeki maddeye sonradan fotoğraf eklemek için (madde indeksi => dosya). */
    public array $maddeFotolari = [];

    public function mount(): void
    {
        $this->raporTarihi = now()->toDateString();

        if ($aktarim = session()->pull('dof_aktarim')) {
            $this->firmaId = $aktarim['firma_id'];
            $this->updatedFirmaId();
            $this->maddeler = [...$this->maddeler, ...$aktarim['maddeler']];
            $this->kaynakOlayKaydiId = $aktarim['olay_kaydi_id'] ?? null;

            Notification::make()
                ->title(count($aktarim['maddeler']).' bulgu '.($aktarim['kaynak'] ?? 'AI Saha Analizi').'\'nden aktarıldı')
                ->success()
                ->send();

            return;
        }

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

    /**
     * Firma modelinin kendi görünürlük global scope'u (bkz. Firma::booted) zaten
     * sahip olunan + paylaşılan firmaları doğru kapsıyor — burada AYRICA
     * `user_id` ile filtrelemek (eski, tek kullanıcılı dönemden kalma) paylaşılan
     * firmaları dışlayıp uzmanın DÖF'ünü görememesine/indirememesine yol açıyordu.
     */
    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->orderBy('unvan')
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::find($this->firmaId)
            : null;
    }

    #[Computed]
    public function oncelikler(): array
    {
        return config('isg.dof.oncelikler');
    }

    #[Computed]
    public function durumlar(): array
    {
        return config('isg.dof.durumlar');
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiOneriDanismani::aktifMi();
    }

    /** @return Collection<int, DofRaporu> */
    #[Computed]
    public function gecmisKayitlar(): Collection
    {
        return $this->firma?->dofRaporlari()->latest()->get() ?? collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Portföy Geneli Takip — isgpratik DÖF ana ekranı (24.jpg)
    |--------------------------------------------------------------------------
    */

    public string $takipArama = '';

    public string $takipOncelikFiltre = '';

    /** @var array<string, string> "raporId-maddeIndex" => kapatma notu taslağı */
    public array $kapatmaNotlari = [];

    /** Kullanıcının tüm firmalarındaki tüm DÖF maddeleri, firma bilgisiyle birlikte düz liste. */
    #[Computed]
    public function tumMaddeler(): Collection
    {
        return DofRaporu::query()
            ->whereHas('firma')
            ->with('firma')
            ->get()
            ->flatMap(fn (DofRaporu $rapor) => collect($rapor->maddeler ?? [])->map(fn (array $m, int $i) => [
                ...$m,
                'anahtar' => $rapor->id.'-'.$i,
                'rapor_id' => $rapor->id,
                'madde_index' => $i,
                'firma' => $rapor->firma?->unvan ?? '—',
                'belge_no' => $rapor->belge_no,
            ]));
    }

    #[Computed]
    public function takipOzeti(): array
    {
        $tumu = $this->tumMaddeler;
        $acik = $tumu->where('durum', '!=', 'tamamlandi');
        $kapanmis = $tumu->where('durum', 'tamamlandi');

        return [
            'toplam_dof' => DofRaporu::query()->whereHas('firma')->count(),
            'acik_madde' => $acik->count(),
            'kapanmis_madde' => $kapanmis->count(),
            'kapatma_orani' => $tumu->isEmpty() ? 0 : round(($kapanmis->count() / $tumu->count()) * 100),
            'oncelik_dagilimi' => $acik->countBy('oncelik'),
        ];
    }

    /** @return Collection<int, array> arama/filtre uygulanmış açık maddeler */
    #[Computed]
    public function acikMaddelerFiltreli(): Collection
    {
        $terim = mb_strtolower(trim($this->takipArama));

        return $this->tumMaddeler
            ->where('durum', '!=', 'tamamlandi')
            ->when($this->takipOncelikFiltre !== '', fn ($q) => $q->where('oncelik', $this->takipOncelikFiltre))
            ->when($terim !== '', fn ($q) => $q->filter(
                fn (array $m) => str_contains(mb_strtolower($m['tespit'] ?? ''), $terim)
                    || str_contains(mb_strtolower($m['firma'] ?? ''), $terim)
                    || str_contains(mb_strtolower($m['belge_no'] ?? ''), $terim),
            ))
            ->sortByDesc(fn (array $m) => array_search($m['oncelik'] ?? 'dusuk', ['dusuk', 'orta', 'yuksek', 'kritik']))
            ->values();
    }

    /** Portföydeki (kaydedilmiş) bir DÖF maddesini "Tamamlandı" yapıp kapatma notu ekler. */
    public function maddeKapat(int $raporId, int $maddeIndex): void
    {
        $rapor = DofRaporu::query()
            ->whereHas('firma')
            ->find($raporId);

        if (! $rapor || ! isset($rapor->maddeler[$maddeIndex])) {
            return;
        }

        $maddeler = $rapor->maddeler;
        $maddeler[$maddeIndex]['durum'] = 'tamamlandi';
        $maddeler[$maddeIndex]['kapatma_notu'] = $this->kapatmaNotlari[$raporId.'-'.$maddeIndex] ?? null;
        $maddeler[$maddeIndex]['kapatma_tarihi'] = now()->toDateString();
        $rapor->update(['maddeler' => $maddeler]);

        unset($this->kapatmaNotlari[$raporId.'-'.$maddeIndex], $this->tumMaddeler, $this->takipOzeti, $this->acikMaddelerFiltreli);
        unset($this->gecmisKayitlar);

        Notification::make()->title('Madde kapatıldı')->success()->send();
    }

    /*
    |--------------------------------------------------------------------------
    | Form alanları
    |--------------------------------------------------------------------------
    */

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->gecmisKayitlar);

        foreach ($this->firmaKunyesi() as $alan => $deger) {
            $this->{$alan} = $deger;
        }
    }

    /**
     * Künyenin kişi bilgileri firma kaydından: gözetim yapan = firmanın İGU'su,
     * İGU atanmamışsa hesap sahibi (uzmanın kendisi); işveren vekili firmadan.
     * Word/Excel'den aktarımda bu alanlar dosyadakiyle değiştirilmez (kullanıcı
     * isteği 08.10.2026 — dışarıda hazırlanan raporlar aynı standartta çıksın).
     *
     * @return array<string, ?string>
     */
    private function firmaKunyesi(): array
    {
        $firma = $this->firma;

        if (! $firma) {
            return [];
        }

        $igu = $firma->igu;
        $sahip = $firma->user;

        return [
            'gozetimYapan' => $igu?->ad_soyad ?: $sahip?->name,
            'gozetimYapanSertifikaNo' => $igu ? $igu->sertifika_no : $sahip?->sertifika_no,
            'isverenVekiliAdi' => $firma->isveren_vekili ?: $firma->isveren_ad,
        ];
    }

    public function maddeEkle(): void
    {
        if (blank($this->yeniTespit)) {
            return;
        }

        $this->maddeler[] = [
            'tespit' => $this->yeniTespit,
            'oncelik' => $this->yeniOncelik,
            'oneri' => $this->yeniOneri,
            'sorumlu' => $this->yeniSorumlu,
            'termin' => $this->yeniTermin,
            'durum' => 'acik',
            'foto_yolu' => $this->yeniFoto?->store('dof-foto', 'public'),
        ];

        $this->reset('yeniTespit', 'yeniOneri', 'yeniSorumlu', 'yeniTermin', 'yeniFoto');
        $this->yeniOncelik = 'orta';
    }

    public function tablodanAktar(array $data): void
    {
        $yol = (string) ($data['dosya'] ?? '');
        $adi = (string) ($data['dosya_adi'] ?? $yol);

        try {
            $sonuc = DofTabloOkuyucu::oku(Storage::disk('local')->path($yol), pathinfo($adi, PATHINFO_EXTENSION));
        } catch (\Throwable $e) {
            report($e);
            $sonuc = ['bilgi' => [], 'maddeler' => []];
        } finally {
            Storage::disk('local')->delete($yol);
        }

        if (! $sonuc['maddeler']) {
            Notification::make()
                ->title('Dosyada DÖF tablosu bulunamadı')
                ->body('Tabloda "Tespit / Uygunsuzluk" ve "Öneri / Düzeltici Faaliyet" başlıklı sütunlar olmalı.')
                ->danger()->send();

            return;
        }

        // Dosyadan yalnız alan/bölge ve tarihler alınır; kişi bilgileri (gözetim
        // yapan, sertifika, işveren vekili, sorumlu kişi) firma kaydından gelir —
        // dosyadaki yalnız firmada karşılığı boşsa kullanılır.
        $firmadan = array_filter($this->firmaKunyesi());

        foreach ($sonuc['bilgi'] as $alan => $deger) {
            if (isset($firmadan[$alan]) || $alan === 'sorumluKisi') {
                continue;
            }

            if (blank($this->{$alan}) || $alan === 'raporTarihi') {
                $this->{$alan} = $deger;
            }
        }

        foreach ($firmadan as $alan => $deger) {
            $this->{$alan} = $deger;
        }

        $this->maddeler = [...$this->maddeler, ...$sonuc['maddeler']];

        Notification::make()
            ->title(count($sonuc['maddeler']).' madde aktarıldı')
            ->body($this->firma ? 'Fotoğrafları ekleyip "DÖF Raporu (Kaydet ve İndir)" ile çıktı alın.' : 'Kaydetmek için firmayı seçin.')
            ->success()->send();
    }

    public function updatedMaddeFotolari($dosya, $index): void
    {
        $index = (int) $index;

        if (! isset($this->maddeler[$index]) || ! $dosya) {
            return;
        }

        $this->validate(['maddeFotolari.'.$index => 'image|max:10240']);

        $this->maddeler[$index]['foto_yolu'] = $dosya->store('dof-foto', 'public');
        unset($this->maddeFotolari[$index]);
    }

    public function maddeFotoKaldir(int $index): void
    {
        if (isset($this->maddeler[$index])) {
            $this->maddeler[$index]['foto_yolu'] = null;
        }
    }

    public function maddeSil(int $index): void
    {
        unset($this->maddeler[$index]);
        $this->maddeler = array_values($this->maddeler);
        $this->maddeFotolari = [];
    }

    public function durumGuncelle(int $index, string $durum): void
    {
        if (isset($this->maddeler[$index])) {
            $this->maddeler[$index]['durum'] = $durum;
        }
    }

    public function aiOnerisiAl(): void
    {
        $oneri = GeminiOneriDanismani::oner((string) $this->yeniTespit);

        if ($oneri) {
            $this->yeniOneri = $oneri;
        } else {
            Notification::make()->title('Öneri alınamadı')->body('Yapay zeka şu an kullanılamıyor.')->warning()->send();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    private function kaydet(): ?DofRaporu
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (! $this->maddeler) {
            Notification::make()->title('En az bir DÖF maddesi ekleyin')->danger()->send();

            return null;
        }

        $d = new DofRaporu([
            'firma_id' => $this->firma->id,
            'alan_bolge' => $this->alanBolge,
            'gozetim_tarih_araligi' => $this->gozetimTarihAraligi,
            'rapor_tarihi' => $this->raporTarihi,
            'gozetim_yapan' => $this->gozetimYapan,
            'gozetim_yapan_sertifika_no' => $this->gozetimYapanSertifikaNo,
            'gozetim_yapan_kase' => $this->firma->igu?->kase_gorseli,
            'sorumlu_kisi' => $this->sorumluKisi,
            'isveren_vekili_adi' => $this->isverenVekiliAdi,
            'maddeler' => $this->maddeler,
        ]);
        $d->save();

        // Olay Kaydı'ndan gelen DÖF → olayın "DÖF" sütununa bağla (ilk DÖF korunur).
        if ($this->kaynakOlayKaydiId) {
            OlayKaydi::where('firma_id', $this->firma->id)
                ->whereKey($this->kaynakOlayKaydiId)
                ->whereNull('dof_raporu_id')
                ->update(['dof_raporu_id' => $d->id]);
        }

        unset($this->gecmisKayitlar);

        return $d;
    }

    protected function hazirRaporKategorisi(): string
    {
        return 'dof';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tablodanAktar')
                ->label("Word/Excel'den Aktar")
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->modalHeading("Word/Excel'deki DÖF tablosunu aktar")
                ->modalDescription('Bilgisayarda hazırladığınız DÖF tablosu (Tespit, Öncelik, Öneri, Sorumlu, Termin, Durum sütunları) madde madde listeye eklenir; üst bilgiler (Alan/Bölge, Gözetim Yapan...) doldurulur. Ardından fotoğrafları ekleyip sistemin DÖF raporu olarak çıktı alırsınız.')
                ->modalSubmitActionLabel('Aktar')
                ->stickyModalFooter()
                ->schema([
                    // MIME yerine uzantı — gerekçe DosyaKabul'de.
                    \App\Filament\Support\DosyaKabul::uygula(FileUpload::make('dosya')->storeFileNamesIn('dosya_adi')
                        ->label('Word (.docx) veya Excel (.xlsx/.xls) dosyası')
                        ->disk('local')->directory('dof-aktarim'), ['docx', 'xlsx', 'xls'])
                        ->maxSize(20480)
                        ->required(),
                ])
                ->action(fn (array $data) => $this->tablodanAktar($data)),
            $this->hazirRaporYukleAction(),
            Action::make('pdf')
                ->label('DÖF Raporu (Kaydet ve İndir)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $d = $this->kaydet();

                    if (! $d) {
                        return null;
                    }

                    Notification::make()->title('DÖF raporu kaydedildi')->body($d->belge_no)->success()->send();

                    return DofRaporuUretici::pdf($d, ImzaSecenegi::secili($data));
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $d = $this->firma?->dofRaporlari()->find($id);

        return $d ? DofRaporuUretici::pdf($d) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->dofRaporlari()->find($id)?->delete();
        unset($this->gecmisKayitlar);
    }
}
