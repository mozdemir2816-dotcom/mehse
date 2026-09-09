<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\YillikPlan as YillikPlanModel;
use App\Support\YillikDegerlendirmeVerisi;
use App\Support\YillikPlanExcelIceAktarici;
use App\Support\YillikPlanUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Throwable;
use UnitEnum;

/**
 * Yıllık Planlar — isgpratik 86-90.jpg. 3 sekme: Çalışma Planı / Eğitim
 * Planı (ikisi de ay durum matrisli — Boş→Planlandı→Tamamlandı) /
 * Değerlendirme Raporu (satır bazlı serbest metin, ay matrisi yok).
 */
class YillikPlanlar extends Page
{
    protected string $view = 'filament.pages.yillik-planlar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'yillik-planlar';

    protected static ?string $title = 'Yıllık Planlar';

    protected static ?string $navigationLabel = 'Yıllık Planlar';

    public const AYLAR = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

    public const DURUM_SIRASI = ['bos', 'planlandi', 'tamamlandi'];

    public ?int $firmaId = null;

    public int $yil;

    public string $sekme = 'calisma';

    public ?string $yeniFaaliyet = null;

    public ?string $yeniSorumlu = null;

    public ?string $yeniAciklama = null;

    public ?string $yeniEgitimKonu = null;

    public ?string $yeniEgitimSure = null;

    public ?string $yeniEgitimEgitici = null;

    public ?string $yeniEgitimHedefKitle = null;

    public ?string $yeniDegerlendirmeCalisma = null;

    public function mount(): void
    {
        $this->yil = (int) now()->format('Y');

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
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
    public function plan(): ?YillikPlanModel
    {
        return $this->firma ? YillikPlanModel::firmaYilIcin($this->firma, $this->yil) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->plan);
    }

    public function updatedYil(): void
    {
        unset($this->plan);
    }

    /** Atanmış uzman (sözleşme başlangıcı) öncesi ay indeksi — bu aylar kilitli. */
    #[Computed]
    public function kilitAyIndeksi(): int
    {
        return $this->firma?->planKilitAyIndeksi($this->yil) ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Ay durum matrisi (Çalışma Planı + Eğitim Planı ortak) — $alan: 'faaliyetler'|'egitimler'
    |--------------------------------------------------------------------------
    */

    public function ayDurumDegistir(string $alan, int $index, int $ayIndex): void
    {
        $p = $this->plan();
        $satirlar = $p?->{$alan} ?? [];

        if (! $p || ! isset($satirlar[$index])) {
            return;
        }

        // Atanmış uzman öncesindeki aylar seçilemez.
        if ($ayIndex < $this->kilitAyIndeksi()) {
            Notification::make()
                ->title('Bu ay seçilemez')
                ->body('Firma sözleşme başlangıcından (atanmış uzman tarihi) önceki aylar için plan işaretlenemez.')
                ->warning()
                ->send();

            return;
        }

        $mevcut = $satirlar[$index]['aylar'][$ayIndex] ?? 'bos';
        $siraIndex = array_search($mevcut, self::DURUM_SIRASI, true);
        $yeni = self::DURUM_SIRASI[($siraIndex + 1) % count(self::DURUM_SIRASI)];

        $satirlar[$index]['aylar'][$ayIndex] = $yeni;
        $p->update([$alan => $satirlar]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Çalışma Planı
    |--------------------------------------------------------------------------
    */

    public function faaliyetEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniFaaliyet)) {
            return;
        }

        $faaliyetler = $p->faaliyetler ?? [];
        $faaliyetler[] = [
            'faaliyet' => $this->yeniFaaliyet,
            'sorumlu' => $this->yeniSorumlu,
            'aciklama' => $this->yeniAciklama,
            'aylar' => array_fill(0, 12, 'bos'),
        ];
        $p->update(['faaliyetler' => $faaliyetler]);

        $this->reset('yeniFaaliyet', 'yeniSorumlu', 'yeniAciklama');
    }

    public function faaliyetSil(int $index): void
    {
        $p = $this->plan();
        $faaliyetler = $p?->faaliyetler ?? [];

        if (! $p || ! isset($faaliyetler[$index])) {
            return;
        }

        unset($faaliyetler[$index]);
        $p->update(['faaliyetler' => array_values($faaliyetler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Eğitim Planı
    |--------------------------------------------------------------------------
    */

    public function egitimEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniEgitimKonu)) {
            return;
        }

        $egitimler = $p->egitimler ?? [];
        $egitimler[] = [
            'konu' => $this->yeniEgitimKonu,
            'sure_saat' => $this->yeniEgitimSure ?: null,
            'egitici' => $this->yeniEgitimEgitici,
            'hedef' => null,
            'hedef_kitle' => $this->yeniEgitimHedefKitle,
            'aylar' => array_fill(0, 12, 'bos'),
        ];
        $p->update(['egitimler' => $egitimler]);

        $this->reset('yeniEgitimKonu', 'yeniEgitimSure', 'yeniEgitimEgitici', 'yeniEgitimHedefKitle');
    }

    public function egitimSil(int $index): void
    {
        $p = $this->plan();
        $egitimler = $p?->egitimler ?? [];

        if (! $p || ! isset($egitimler[$index])) {
            return;
        }

        unset($egitimler[$index]);
        $p->update(['egitimler' => array_values($egitimler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yıllık Değerlendirme Raporu (ay matrisi yok, satır bazlı serbest metin)
    |--------------------------------------------------------------------------
    */

    public function degerlendirmeGuncelle(int $index, string $alan, string $deger): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index]) || ! in_array($alan, ['tarih', 'yapan_kisi', 'tekrar_sayisi', 'yontem', 'sonuc'], true)) {
            return;
        }

        $degerlendirmeler[$index][$alan] = $deger;
        $p->update(['degerlendirmeler' => $degerlendirmeler]);
    }

    public function degerlendirmeEkle(): void
    {
        $p = $this->plan();

        if (! $p || blank($this->yeniDegerlendirmeCalisma)) {
            return;
        }

        $degerlendirmeler = $p->degerlendirmeler ?? [];
        $degerlendirmeler[] = [
            'calisma' => $this->yeniDegerlendirmeCalisma,
            'yapan_kisi' => null, 'yontem' => null, 'sonuc' => null,
            'tarih' => null, 'tekrar_sayisi' => null,
        ];
        $p->update(['degerlendirmeler' => $degerlendirmeler]);

        $this->reset('yeniDegerlendirmeCalisma');
    }

    public function degerlendirmeSil(int $index): void
    {
        $p = $this->plan();
        $degerlendirmeler = $p?->degerlendirmeler ?? [];

        if (! $p || ! isset($degerlendirmeler[$index])) {
            return;
        }

        unset($degerlendirmeler[$index]);
        $p->update(['degerlendirmeler' => array_values($degerlendirmeler)]);
    }

    /*
    |--------------------------------------------------------------------------
    | Ortak
    |--------------------------------------------------------------------------
    */

    public function varsayilanaSifirla(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $kilitAy = $this->kilitAyIndeksi();

        match ($this->sekme) {
            'egitim' => $p->update([
                'egitimler' => YillikPlanModel::maddeAylarIle(config('isg.yillik_plan.varsayilan_egitimler'), $kilitAy),
            ]),
            'degerlendirme' => $p->update([
                'degerlendirmeler' => collect(config('isg.yillik_plan.varsayilan_degerlendirmeler'))
                    ->map(fn ($d) => [...$d, 'tarih' => null, 'tekrar_sayisi' => null])
                    ->all(),
            ]),
            default => $p->update([
                'faaliyetler' => YillikPlanModel::maddeAylarIle(config('isg.yillik_plan.varsayilan_faaliyetler'), $kilitAy),
            ]),
        };

        Notification::make()->title('Bu sekme varsayılan içeriğe sıfırlandı (otomatik dolduruldu)')->success()->send();
    }

    /**
     * Planı Kaydet — çalışma/eğitim ay hücreleri ve satır ekleme/silme zaten anlık
     * kaydediliyor; bu buton değerlendirme sekmesindeki serbest metin alanlarının
     * (henüz odaktan çıkmamış olsa bile) sunucuya yazılmasını garantiler ve
     * kullanıcıya "kaydedildi" geri bildirimi verir.
     */
    public function planiKaydet(): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $p->update([
            'faaliyetler' => $p->faaliyetler ?? [],
            'egitimler' => $p->egitimler ?? [],
            'degerlendirmeler' => $p->degerlendirmeler ?? [],
        ]);

        unset($this->plan);

        Notification::make()
            ->title('Yıllık plan kaydedildi')
            ->body('Son kayıt: '.now()->format('d.m.Y H:i'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('planiKaydet')
                ->label('Planı Kaydet')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->visible(fn () => $this->plan() !== null)
                ->action(fn () => $this->planiKaydet()),

            Action::make('pdf')
                ->label('Çıktı İndir (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->plan() !== null)
                ->action(fn () => YillikPlanUretici::pdf($this->plan())),

            Action::make('excelYukleCalisma')
                ->label('Çalışma Planı Excel’den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'calisma')
                ->modalDescription('Kendi Yıllık Çalışma Planı Excel’inizi yükleyin. Satırlar isimle eşleştirilir: mevcut satır varsa ayları güncellenir, yoksa eklenir. Atanmış uzman öncesindeki aylar işaretlenmez.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([static::dosyaAlani()])
                ->action(fn (array $data) => $this->exceliIsle($data['dosya'], 'faaliyetler')),

            Action::make('excelYukleEgitim')
                ->label('Eğitim Planı Excel’den Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'egitim')
                ->modalDescription('Kendi Yıllık Eğitim Planı Excel’inizi yükleyin (12 ay × 4 hafta düzeni desteklenir, ay bazına indirilir). Satırlar isimle eşleştirilir.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([static::dosyaAlani()])
                ->action(fn (array $data) => $this->exceliIsle($data['dosya'], 'egitimler')),

            Action::make('degerlendirmeSistemdenDoldur')
                ->label('Sistemden Doldur')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => $this->plan() !== null && $this->sekme === 'degerlendirme')
                ->requiresConfirmation()
                ->modalHeading('Değerlendirmeyi sistem verisinden doldur')
                ->modalDescription('Risk değerlendirmesi, muayeneler, eğitimler, tatbikat, saha denetimi, kurul ve iş kazası satırlarının tarih + tekrar sayısı, '.$this->yil.' yılı için sisteme girilmiş kayıtlardan yazılır. Elle girdiğiniz bu iki alan üzerine yazılır; diğer alanlara dokunulmaz.')
                ->modalSubmitActionLabel('Doldur')
                ->action(function (): void {
                    $p = $this->plan();

                    if (! $p) {
                        return;
                    }

                    $sonuc = YillikDegerlendirmeVerisi::planiDoldur($this->firma, $this->yil, $p->degerlendirmeler ?? []);
                    $p->update(['degerlendirmeler' => $sonuc['satirlar']]);

                    Notification::make()
                        ->title($sonuc['doldurulan'] > 0
                            ? $sonuc['doldurulan'].' satır sistem verisinden dolduruldu'
                            : 'Bu yıl için eşleşen sistem kaydı bulunamadı')
                        ->{$sonuc['doldurulan'] > 0 ? 'success' : 'warning'}()
                        ->send();
                }),
        ];
    }

    private static function dosyaAlani(): FileUpload
    {
        return FileUpload::make('dosya')
            ->label('Excel dosyası (.xlsx / .xls)')
            ->disk('local')
            ->directory('excel-ice-aktarim')
            ->acceptedFileTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
            ])
            ->required();
    }

    /** @param  'faaliyetler'|'egitimler'  $tip */
    private function exceliIsle(string $dosya, string $tip): void
    {
        $p = $this->plan();

        if (! $p) {
            return;
        }

        $yol = Storage::disk('local')->path($dosya);

        try {
            $sonuc = YillikPlanExcelIceAktarici::iceAktar($yol, $p, $tip);
        } catch (Throwable $e) {
            Notification::make()->title('Dosya işlenemedi')->body($e->getMessage())->danger()->send();

            return;
        } finally {
            Storage::disk('local')->delete($dosya);
        }

        unset($this->plan);

        if ($sonuc['hatalar']) {
            Notification::make()->title('İçe aktarma tamamlanamadı')->body(implode(' ', $sonuc['hatalar']))->danger()->send();

            return;
        }

        Notification::make()
            ->title('Excel içe aktarıldı')
            ->body("{$sonuc['eklenen']} satır eklendi, {$sonuc['guncellenen']} satır güncellendi.")
            ->success()
            ->send();
    }
}
