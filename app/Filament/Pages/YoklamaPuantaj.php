<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\YoklamaKaydi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Günlük Yoklama, Puantaj & Acil Durum Saha Mevcut Listesi (N2).
 * Dış Paydaş & Tesis Giriş ailesinin sekmesi olarak açılır (sıfır sol menü kalabalığı).
 */
class YoklamaPuantaj extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.yoklama-puantaj';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Firmalar & Çalışanlar';

    /** Menüde gizli — Dış Paydaş & Tesis Giriş sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'yoklama-puantaj';

    protected static ?string $title = 'Günlük Yoklama & Puantaj';

    protected static ?string $navigationLabel = 'Günlük Yoklama & Puantaj';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    #[Url(as: 'tarih')]
    public string $tarih = '';

    public function mount(): void
    {
        $this->tarih = $this->tarih ?: now()->toDateString();
        $this->firmaId ??= \App\Support\KomutaFirma::baslangic($this->firmalar);
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->where('aktif', true)
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar)
            ? Firma::find($this->firmaId)
            : null;
    }

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return $this->firma->calisanlar()->where('aktif', true)->orderBy('ad_soyad')->get();
    }

    /** @return array<int, string> calisan_id => durum */
    #[Computed]
    public function gunlukDurumlar(): array
    {
        if (! $this->firma || ! \Illuminate\Support\Facades\Schema::hasTable('yoklama_kayitlari')) {
            return [];
        }

        return YoklamaKaydi::query()
            ->where('firma_id', $this->firma->id)
            ->whereDate('tarih', $this->tarih)
            ->pluck('durum', 'calisan_id')
            ->all();
    }

    /** @return array<string, int> */
    #[Computed]
    public function ozet(): array
    {
        $sayim = array_count_values($this->gunlukDurumlar);
        $toplam = $this->calisanlar->count();

        return [
            'toplam' => $toplam,
            'mevcut' => $sayim['mevcut'] ?? 0,
            'izinli' => $sayim['izinli'] ?? 0,
            'raporlu' => $sayim['raporlu'] ?? 0,
            'gorevli' => $sayim['gorevli'] ?? 0,
            'gelmedi' => $sayim['gelmedi'] ?? 0,
            'isaretsiz' => max(0, $toplam - count($this->gunlukDurumlar)),
        ];
    }

    private function guvenliTarih(): Carbon
    {
        try {
            return Carbon::parse($this->tarih ?: now()->toDateString());
        } catch (\Throwable) {
            $this->tarih = now()->toDateString();

            return now();
        }
    }

    /** Seçili ay için personel bazlı puantaj toplamları. */
    #[Computed]
    public function aylikPuantaj(): array
    {
        if (! $this->firma || ! \Illuminate\Support\Facades\Schema::hasTable('yoklama_kayitlari')) {
            return [];
        }

        $ayBas = $this->guvenliTarih()->startOfMonth();
        $ayBit = $ayBas->copy()->endOfMonth();

        $kayitlar = YoklamaKaydi::query()
            ->where('firma_id', $this->firma->id)
            ->whereBetween('tarih', [$ayBas->toDateString(), $ayBit->toDateString()])
            ->get(['calisan_id', 'durum'])
            ->groupBy('calisan_id');

        return $this->calisanlar->map(function (Calisan $c) use ($kayitlar): array {
            $g = $kayitlar->get($c->id, collect())->countBy('durum');

            return [
                'ad_soyad' => $c->ad_soyad,
                'gorev' => $c->gorev ?: '—',
                'mevcut' => (int) ($g['mevcut'] ?? 0),
                'izinli' => (int) ($g['izinli'] ?? 0),
                'raporlu' => (int) ($g['raporlu'] ?? 0),
                'gorevli' => (int) ($g['gorevli'] ?? 0),
                'gelmedi' => (int) ($g['gelmedi'] ?? 0),
            ];
        })->all();
    }

    public function durumIsaretle(int $calisanId, string $durum): void
    {
        if (! $this->firma || ! array_key_exists($durum, YoklamaKaydi::DURUMLAR)) {
            return;
        }

        abort_unless($this->firma->calisanlar()->whereKey($calisanId)->exists(), 403);

        $tarih = $this->guvenliTarih()->toDateString();
        YoklamaKaydi::updateOrCreate(
            ['firma_id' => $this->firma->id, 'calisan_id' => $calisanId, 'tarih' => $tarih],
            ['durum' => $durum, 'kaydeden' => Filament::auth()->user()?->name]
        );

        unset($this->gunlukDurumlar, $this->ozet, $this->aylikPuantaj);
    }

    public function tumunuMevcutYap(): void
    {
        if (! $this->firma) {
            return;
        }

        $tarih = $this->guvenliTarih()->toDateString();
        $kaydeden = Filament::auth()->user()?->name;
        foreach ($this->calisanlar as $c) {
            YoklamaKaydi::updateOrCreate(
                ['firma_id' => $this->firma->id, 'calisan_id' => $c->id, 'tarih' => $tarih],
                ['durum' => 'mevcut', 'kaydeden' => $kaydeden]
            );
        }

        unset($this->gunlukDurumlar, $this->ozet, $this->aylikPuantaj);
        Notification::make()->title($this->calisanlar->count().' personel "Mevcut (Sahada)" işaretlendi')->success()->send();
    }

    public function puantajCsvIndir(): ?StreamedResponse
    {
        if (! $this->firma) {
            return null;
        }

        $ay = $this->guvenliTarih()->format('Y-m');
        $satirlar = $this->aylikPuantaj;
        $guvenliHucre = fn (string $v): string => preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($satirlar, $guvenliHucre): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ad Soyad', 'Görev', 'Mevcut', 'İzinli', 'Raporlu', 'Görevli', 'Gelmedi'], ';');
            foreach ($satirlar as $s) {
                fputcsv($out, [$guvenliHucre((string) $s['ad_soyad']), $guvenliHucre((string) $s['gorev']), $s['mevcut'], $s['izinli'], $s['raporlu'], $s['gorevli'], $s['gelmedi']], ';');
            }
            fclose($out);
        }, 'puantaj-'.Str::slug($this->firma->unvan ?: 'firma').'-'.$ay.'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tumunuMevcut')
                ->label('Tümünü Mevcut (Sahada) İşaretle')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->firma !== null && $this->calisanlar->isNotEmpty())
                ->action(fn () => $this->tumunuMevcutYap()),
            Action::make('puantajCsv')
                ->label('Aylık Puantaj (Excel/CSV)')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma !== null && $this->calisanlar->isNotEmpty())
                ->action(fn () => $this->puantajCsvIndir()),
        ];
    }
}
