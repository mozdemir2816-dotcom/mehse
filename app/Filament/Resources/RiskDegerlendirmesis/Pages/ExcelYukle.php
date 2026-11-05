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
     *
     * @var array<int, array<string, mixed>>
     */
    public array $girdiler = [];

    /** @var array<int, string> */
    public array $hatalar = [];

    public function updatedDosyalar(): void
    {
        $this->validate(['dosyalar.*' => 'file|mimes:xlsx,xls,xlsm|max:20480'], [], ['dosyalar.*' => 'dosya']);

        foreach ($this->dosyalar as $dosya) {
            $ad = $dosya->getClientOriginalName();
            $uzanti = strtolower($dosya->getClientOriginalExtension()) ?: 'xlsx';
            $yol = Storage::disk('local')->putFileAs('risk-yukleme', $dosya, Str::uuid().'.'.$uzanti);

            try {
                $this->girdiler[] = RiskExcelYukleyici::analiz(Storage::disk('local')->path($yol), $ad);
            } catch (Throwable $e) {
                Storage::disk('local')->delete($yol);
                $this->hatalar[] = "{$ad}: okunamadı ({$e->getMessage()})";
            }
        }

        $this->dosyalar = [];
    }

    public function girdiKaldir(int $i): void
    {
        if (isset($this->girdiler[$i]['yol'])) {
            @unlink($this->girdiler[$i]['yol']);
        }

        unset($this->girdiler[$i]);
        $this->girdiler = array_values($this->girdiler);
    }

    public function temizle(): void
    {
        foreach ($this->girdiler as $g) {
            @unlink($g['yol'] ?? '');
        }

        $this->reset('girdiler', 'hatalar', 'dosyalar');
    }

    public function kaydet(): void
    {
        $this->validate(['firma_id' => 'required|integer', 'girdiler' => 'required|array|min:1'], [], ['firma_id' => 'firma', 'girdiler' => 'yüklenecek dosya']);

        $mevcut = $this->mevcutRd();
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
            try {
                $sonuc = $mevcut ? RiskExcelYukleyici::mevcutaEkle($mevcut, $g) : RiskExcelYukleyici::yukle($firma, $g);
                $eklenen += $sonuc['eklenen'];
                $madde += $sonuc['madde'];
                $notlar = array_merge($notlar, $sonuc['notlar']);
            } catch (Throwable $e) {
                $notlar[] = "{$g['dosya']}: okunamadı ({$e->getMessage()})";
            } finally {
                @unlink($g['yol'] ?? '');
            }
        }

        $this->reset('girdiler', 'dosyalar');

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
