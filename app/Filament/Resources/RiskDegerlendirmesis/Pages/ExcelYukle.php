<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\Pages;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use App\Support\RiskExcelYukleyici;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Throwable;

/**
 * Risk Değerlendirmeleri → Excel Yükle (28.10.2026): dosya seçilince sistem sayfa ve sütunları
 * algılar; kullanıcı önizlemede "şu sütunu Tehlike, şu sütunu Olasılık olarak anladım" eşlemesini
 * görür, açılır listelerle düzeltir, yöntemi / sayfaları seçer ve onaylayınca kayıt açılır.
 * Hastane formu önizlemesiz, olduğu gibi alınır (bkz. RiskExcelYukleyici).
 */
class ExcelYukle extends Page
{
    use WithFileUploads;

    protected static string $resource = RiskDegerlendirmesiResource::class;

    protected string $view = 'filament.resources.risk-degerlendirmesis.excel-yukle';

    protected static ?string $title = 'Risk Analizi Excel Yükle';

    public ?int $firma_id = null;

    /** Doluysa yeni kayıt açılmaz: Excel bu değerlendirmenin sonuna eklenir (?degerlendirme=ID). */
    public ?int $degerlendirme_id = null;

    public function mount(): void
    {
        $id = (int) request()->query('degerlendirme');

        if ($id) {
            $rd = $this->mevcutDegerlendirme($id);
            $this->degerlendirme_id = $rd->id;
            $this->firma_id = $rd->firma_id;
        }
    }

    private function mevcutDegerlendirme(int $id): RiskDegerlendirmesi
    {
        return RiskDegerlendirmesi::whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))->findOrFail($id);
    }

    public function mevcutRd(): ?RiskDegerlendirmesi
    {
        return $this->degerlendirme_id ? $this->mevcutDegerlendirme($this->degerlendirme_id) : null;
    }

    /** @var array<int, TemporaryUploadedFile> */
    public array $dosyalar = [];

    /**
     * Çözümlenmiş dosyalar (RiskExcelYukleyici::analiz çıktısı + ekrandaki düzeltmeler).
     * Gerçek sunucu dosya yolları istemciye açılmaz ($dosyaYollari içinde kilitli tutulur).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $girdiler = [];

    /** @var array<string, string> kimlik => local diskteki yalnızca risk-yukleme/<dosya> göreli yolu */
    #[Locked]
    public array $dosyaYollari = [];

    /** @var array<int, string> */
    public array $hatalar = [];

    private function guvenliTamYol(?string $kimlik): ?string
    {
        if (! $kimlik || ! isset($this->dosyaYollari[$kimlik])) {
            return null;
        }

        $ad = basename($this->dosyaYollari[$kimlik]);
        $goreli = 'risk-yukleme/'.$ad;
        $kok = realpath(Storage::disk('local')->path('risk-yukleme'));
        $tam = realpath(Storage::disk('local')->path($goreli));

        if (! $kok || ! $tam || ! str_starts_with($tam, $kok.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $tam;
    }

    private function guvenliSil(?string $kimlik): void
    {
        if (! $kimlik || ! isset($this->dosyaYollari[$kimlik])) {
            return;
        }

        $ad = basename($this->dosyaYollari[$kimlik]);
        Storage::disk('local')->delete('risk-yukleme/'.$ad);
        unset($this->dosyaYollari[$kimlik]);
    }

    public function updatedDosyalar(): void
    {
        $this->validate(['dosyalar.*' => 'file|mimes:xlsx,xls,xlsm|max:20480'], [], ['dosyalar.*' => 'dosya']);

        foreach ($this->dosyalar as $dosya) {
            $ad = $dosya->getClientOriginalName();
            $uzanti = strtolower($dosya->getClientOriginalExtension()) ?: 'xlsx';
            $dosyaAdi = Str::uuid().'.'.$uzanti;
            $yol = Storage::disk('local')->putFileAs('risk-yukleme', $dosya, $dosyaAdi);

            try {
                $analiz = RiskExcelYukleyici::analiz(Storage::disk('local')->path($yol), $ad);
                $kimlik = (string) Str::uuid();
                $this->dosyaYollari[$kimlik] = 'risk-yukleme/'.basename($yol);
                unset($analiz['yol']);
                $analiz['kimlik'] = $kimlik;
                $this->girdiler[] = $analiz;
            } catch (Throwable $e) {
                Storage::disk('local')->delete($yol);
                $this->hatalar[] = "{$ad}: okunamadı ({$e->getMessage()})";
            }
        }

        $this->dosyalar = [];
    }

    public function girdiKaldir(int $i): void
    {
        $this->guvenliSil($this->girdiler[$i]['kimlik'] ?? null);

        unset($this->girdiler[$i]);
        $this->girdiler = array_values($this->girdiler);
    }

    public function temizle(): void
    {
        foreach (array_keys($this->dosyaYollari) as $kimlik) {
            $this->guvenliSil($kimlik);
        }

        $this->reset('girdiler', 'hatalar', 'dosyalar');
        $this->dosyaYollari = [];
    }

    public function kaydet(): void
    {
        $this->validate(['firma_id' => 'required|integer', 'girdiler' => 'required|array|min:1'], [], ['firma_id' => 'firma', 'girdiler' => 'yüklenecek dosya']);

        $mevcut = $this->mevcutRd();

        if ($mevcut?->kilitliMi()) {
            Notification::make()->title('Kilitli değerlendirmeye madde eklenemez')->danger()->send();

            return;
        }

        $firma = $mevcut ? $mevcut->firma : Firma::where('user_id', Filament::auth()->id())->findOrFail($this->firma_id);

        // Yüklemeden önce eşleme hatalarını topla: kayıt yarım kalmasın.
        $sorunlar = [];

        foreach ($this->girdiler as $g) {
            foreach ($g['sayfalar'] ?? [] as $s) {
                if (! ($s['kullan'] ?? false)) {
                    continue;
                }

                if (! in_array('tehlike', (array) ($s['sutunlar'] ?? []), true)) {
                    $sorunlar[] = "{$g['dosya']} / {$s['ad']}: bir sütunu \"Tehlike\" olarak seçin.";
                }

                foreach ($this->tekrarlayanAlanlar($s) as $alan) {
                    $sorunlar[] = "{$g['dosya']} / {$s['ad']}: \"".RiskDegerlendirmesiExcelOkuyucu::ALAN_ETIKETLERI[$alan].'" birden çok sütuna atanmış.';
                }
            }

            if (($g['tur'] ?? '') === 'standart' && ! collect($g['sayfalar'] ?? [])->contains(fn ($s) => $s['kullan'] ?? false)) {
                $sorunlar[] = "{$g['dosya']}: yüklenecek en az bir sayfa seçin.";
            }
        }

        if ($sorunlar) {
            $this->hatalar = $sorunlar;

            return;
        }

        $this->hatalar = [];
        $eklenen = $madde = 0;
        $notlar = [];

        foreach ($this->girdiler as $g) {
            $kimlik = $g['kimlik'] ?? null;
            $tamYol = $this->guvenliTamYol($kimlik);

            if (! $tamYol) {
                $notlar[] = ($g['dosya'] ?? 'Dosya').': geçici dosya bulunamadı veya geçersiz.';
                continue;
            }

            try {
                $girdi = $g + ['yol' => $tamYol];
                $sonuc = $mevcut ? RiskExcelYukleyici::mevcutaEkle($mevcut, $girdi) : RiskExcelYukleyici::yukle($firma, $girdi);
                $eklenen += $sonuc['eklenen'];
                $madde += $sonuc['madde'];
                $notlar = array_merge($notlar, $sonuc['notlar']);
            } catch (Throwable $e) {
                $notlar[] = "{$g['dosya']}: okunamadı ({$e->getMessage()})";
            } finally {
                $this->guvenliSil($kimlik);
            }
        }

        $this->reset('girdiler', 'dosyalar');
        $this->dosyaYollari = [];

        Notification::make()
            ->title($mevcut
                ? ($madde ? "{$madde} madde mevcut değerlendirmeye eklendi" : 'Eklenecek yeni madde bulunamadı')
                : ($eklenen ? "{$eklenen} değerlendirme, {$madde} madde yüklendi" : 'Hiçbir değerlendirme yüklenemedi'))
            ->body($notlar ? implode("\n", array_slice($notlar, 0, 8)) : null)
            ->{$eklenen ? 'success' : 'danger'}()
            ->persistent(! empty($notlar))
            ->send();

        if ($eklenen) {
            $this->redirect($mevcut
                ? RiskDegerlendirmesiResource::getUrl('edit', ['record' => $mevcut])
                : RiskDegerlendirmesiResource::getUrl('index'));
        }
    }

    /** @return array<int|string, string> */
    public function firmaSecenekleri(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->secenekler()->all();
    }

    /** @return array<string, string> */
    public function alanSecenekleri(): array
    {
        return RiskDegerlendirmesiExcelOkuyucu::ALAN_ETIKETLERI;
    }

    /**
     * Ekranda gösterilecek sütunlar (başlığı, eşlemesi ya da örnek verisi olanlar), sütun harfiyle.
     *
     * @param  array<string, mixed>  $sayfa
     * @return array<int, string> sütun indeksi => harf
     */
    public function gorunenSutunlar(array $sayfa): array
    {
        $idx = array_unique(array_merge(
            array_keys((array) ($sayfa['etiketler'] ?? [])),
            array_keys((array) ($sayfa['sutunlar'] ?? [])),
            ...array_map('array_keys', (array) ($sayfa['ornek'] ?? [])),
        ));
        sort($idx);

        return collect($idx)->mapWithKeys(fn ($c) => [(int) $c => Coordinate::stringFromColumnIndex((int) $c)])->all();
    }

    /**
     * Aynı alana birden çok sütun atanmış mı? (ikincisi birincisini ezerdi)
     *
     * @param  array<string, mixed>  $sayfa
     * @return list<string>
     */
    public function tekrarlayanAlanlar(array $sayfa): array
    {
        $sayim = array_count_values(array_filter((array) ($sayfa['sutunlar'] ?? []), fn ($a) => $a !== '' && $a !== null));

        return array_keys(array_filter($sayim, fn ($n) => $n > 1));
    }
}
