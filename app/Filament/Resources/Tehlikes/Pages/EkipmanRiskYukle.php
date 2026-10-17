<?php

namespace App\Filament\Resources\Tehlikes\Pages;

use App\Filament\Resources\Tehlikes\TehlikeResource;
use App\Models\TehlikeKategorisi;
use App\Support\EkipmanKatalogu;
use App\Support\EkipmanRiskKutuphanesi;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Risk Kütüphanesi → Risk Analizi Yükle (17.10.2026; Fine-Kinney / 5x5 / HAZOP). Kullanıcının
 * risk analizi Excel'leri (EkipmanRiskKutuphanesi şablonu) önizlenir, kategori adı
 * ve bağlanacak ekipman tipi seçilip kütüphaneye yazılır. Bağlanan tip firmaya
 * eklendiğinde maddeler risk değerlendirmesine "Ekipman Eksikleri" ile önerilir.
 */
class EkipmanRiskYukle extends Page
{
    use WithFileUploads;

    protected static string $resource = TehlikeResource::class;

    protected string $view = 'filament.resources.tehlikes.ekipman-risk-yukle';

    protected static ?string $title = 'Risk Analizi Yükle';

    /** Dosyaların yöntemi (EkipmanRiskKutuphanesi::YONTEMLER). */
    public string $yontem = 'fine_kinney';

    /** @var array<int, TemporaryUploadedFile> */
    public array $dosyalar = [];

    /**
     * Önizleme satırları; maddeler önbellekte (anahtar).
     *
     * @var array<int, array{anahtar: string, dosya: string, ekipman: string, kategori_adi: string, ekipman_tipi: string, madde: int}>
     */
    public array $onizleme = [];

    /** @var array<int, string> */
    public array $hatalar = [];

    public function updatedDosyalar(): void
    {
        $this->validate([
            'yontem' => 'required|in:'.implode(',', array_keys(EkipmanRiskKutuphanesi::YONTEMLER)),
            'dosyalar.*' => 'file|mimes:xlsx,xls|max:20480',
        ]);

        foreach ($this->dosyalar as $dosya) {
            try {
                $gruplar = EkipmanRiskKutuphanesi::oku($dosya->getRealPath(), $dosya->getClientOriginalName(), $this->yontem);
            } catch (Throwable $e) {
                $this->hatalar[] = $dosya->getClientOriginalName().': '.$e->getMessage();

                continue;
            }

            if (! $gruplar) {
                $this->hatalar[] = $dosya->getClientOriginalName().': risk maddesi bulunamadı.';
            }

            foreach ($gruplar as $g) {
                $anahtar = 'ekipman-risk-yukle:'.Str::uuid();
                Cache::put($anahtar, $g['maddeler'], now()->addHours(2));

                $this->onizleme[] = [
                    'anahtar' => $anahtar,
                    'dosya' => $dosya->getClientOriginalName(),
                    'yontem' => EkipmanRiskKutuphanesi::YONTEMLER[$this->yontem],
                    'ekipman' => $g['ekipman'],
                    'kategori_adi' => $g['kategori_adi'],
                    'ekipman_tipi' => static::tahminiTip($g['ekipman']),
                    'madde' => count($g['maddeler']),
                ];
            }
        }

        $this->dosyalar = [];
    }

    public function satirKaldir(int $i): void
    {
        Cache::forget($this->onizleme[$i]['anahtar'] ?? '');
        unset($this->onizleme[$i]);
        $this->onizleme = array_values($this->onizleme);
    }

    public function temizle(): void
    {
        foreach ($this->onizleme as $s) {
            Cache::forget($s['anahtar']);
        }
        $this->reset('onizleme', 'hatalar', 'dosyalar');
    }

    public function kaydet(): void
    {
        $this->validate([
            'onizleme' => 'required|array|min:1',
            'onizleme.*.kategori_adi' => 'required|string|max:200',
        ], [], ['onizleme' => 'yüklenecek dosya', 'onizleme.*.kategori_adi' => 'kategori adı']);

        $ozet = [];
        $kalan = [];

        foreach ($this->onizleme as $s) {
            $maddeler = Cache::get($s['anahtar']);
            if (! $maddeler) {
                $kalan[] = $s;
                $this->hatalar[] = $s['dosya'].': önizleme süresi doldu, dosyayı yeniden seçin.';

                continue;
            }

            $sonuc = EkipmanRiskKutuphanesi::kaydet($s['kategori_adi'], $maddeler, array_filter([$s['ekipman_tipi'] ?? null]));
            Cache::forget($s['anahtar']);

            $ozet[] = $sonuc['kategori']->ad.': '
                .($sonuc['eklenen'] ? $sonuc['eklenen'].' yeni' : '')
                .($sonuc['eklenen'] && $sonuc['guncellenen'] ? ', ' : '')
                .($sonuc['guncellenen'] ? $sonuc['guncellenen'].' güncellendi' : '')
                .($sonuc['silinen'] ? ', '.$sonuc['silinen'].' eski madde kaldırıldı' : '')
                .($s['ekipman_tipi'] ? ' → '.$s['ekipman_tipi'] : ' (ekipmana bağlanmadı)');
        }

        $this->onizleme = $kalan;

        if ($ozet) {
            Notification::make()->title(count($ozet).' kategori kütüphaneye kaydedildi')
                ->body(implode("\n", $ozet))->success()->persistent()->send();
        }
    }

    /** @return array<string, array<string, string>> */
    public function tipSecenekleri(): array
    {
        return EkipmanKatalogu::tipSecenekleri();
    }

    /** Kütüphanede aynı adlı kategori var mı (güncelleme olacağını göstermek için). */
    public function mevcutMadde(string $kategoriAdi): ?int
    {
        $k = TehlikeKategorisi::query()->where('anahtar', Str::slug(trim($kategoriAdi), '_'))->withCount('tehlikeler')->first();

        return $k?->tehlikeler_count;
    }

    /** Ekipman adı tip adıyla birebir (büyük/küçük harf duyarsız) eşleşirse önerir; yoksa boş. */
    private static function tahminiTip(string $ekipman): string
    {
        $n = fn (string $s) => mb_strtolower(strtr($s, ['I' => 'ı', 'İ' => 'i']));
        $ekipmanN = $n($ekipman);

        foreach (EkipmanKatalogu::tipSecenekleri() as $tipler) {
            foreach ($tipler as $tip) {
                if ($n($tip) === $ekipmanN || $n(preg_replace('/\s*\(.*\)$/u', '', $tip)) === $ekipmanN) {
                    return $tip;
                }
            }
        }

        // Daha önce bu kategori bir tipe bağlandıysa onu öner.
        $k = TehlikeKategorisi::query()->where('anahtar', Str::slug(EkipmanRiskKutuphanesi::kategoriAdi($ekipman), '_'))->first();

        return $k?->ekipman_tipleri[0] ?? '';
    }
}
