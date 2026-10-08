<?php

namespace App\Filament\Concerns;

use App\Models\ArsivDosya;
use App\Models\Firma;
use App\Support\ArsivKurali;
use App\Filament\Pages\DofOlustur;
use App\Filament\Support\DosyaKabul;
use App\Support\ArsivYukleyici;
use App\Support\DofTabloOkuyucu;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

/**
 * Dışarıda hazırlanmış (Excel / Word / PDF) raporu, sayfanın madde/AI
 * akışına girmeden olduğu gibi firmanın Arşiv'ine kaydeder — DÖF Oluştur ve
 * Saha Gözlem Raporu sayfaları. Kayıt Arşiv'de de görünür ve kategorinin
 * takip kuralına (ör. saha gözlem 3 ayda bir) sayılır.
 * Görünüm: filament.components.hazir-raporlar.
 *
 * Kullanan sayfa $firmaId ve firma() computed'ını sağlar.
 */
trait HazirRaporYukleme
{
    /** @return array<int, string> */
    protected static function hazirRaporUzantilari(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'];
    }

    /** Arşiv kategori anahtarı (config/arsiv.php). */
    abstract protected function hazirRaporKategorisi(): string;

    protected function hazirRaporYukleAction(): Action
    {
        $ad = ArsivKurali::kategori($this->hazirRaporKategorisi())['ad'];

        return Action::make('hazirRaporYukle')
            ->label('Hazır Rapor Yükle (Excel/Word)')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn () => $this->firma !== null)
            ->modalHeading('Hazır Rapor Yükle')
            ->modalDescription('Kendi hazırladığınız Excel, Word veya PDF raporunu olduğu gibi bu firmanın arşivine ("'.$ad.'") kaydedin.')
            ->modalSubmitActionLabel('Kaydet')
            ->stickyModalFooter()
            ->schema([
                // MIME yerine uzantıyla sınırlanır — gerekçe DosyaKabul'de.
                DosyaKabul::uygula(FileUpload::make('dosyalar')->storeFileNamesIn('dosya_adlari')
                    ->label('Dosya')
                    ->multiple()
                    ->disk('public')->directory('arsiv/gecici'), static::hazirRaporUzantilari())
                    ->maxSize(20480)
                    ->maxFiles(10)
                    ->helperText('Birden fazla dosya seçerseniz tek ZIP olarak (yalnız fotoğraflarsa tek PDF) saklanır.')
                    ->required(),
                DatePicker::make('tarih')->label('Rapor tarihi')->default(now())->required(),
                Textarea::make('aciklama')->label('Not (opsiyonel)')->rows(1),
            ])
            ->action(fn (array $data) => $this->hazirRaporKaydet($data));
    }

    public function hazirRaporKaydet(array $data): void
    {
        $firma = $this->firma;

        if (! $firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        // DÖF tablosu formatındaki Word/Excel (ör. yapay zekaya hazırlatılan rapor)
        // olduğu gibi arşivlenmez; maddeler sisteme aktarılır ve çıktı sistemin
        // standart DÖF raporu olur (kullanıcı 08.10.2026: "yüklediğim gibi iniyor").
        if ($tablo = $this->hazirRaporDofTablosu($data)) {
            $this->dofTablosunuAktar($tablo);

            return;
        }

        $kategori = ArsivKurali::kategori($this->hazirRaporKategorisi());
        $baslik = $kategori['ad'].' — '.Carbon::parse($data['tarih'])->format('d.m.Y');
        $dosya = ArsivYukleyici::birlestir((array) ($data['dosyalar'] ?? []), $firma, $baslik, (array) ($data['dosya_adlari'] ?? []));

        if (! $dosya) {
            Notification::make()->title('Dosya yüklenemedi')->danger()->send();

            return;
        }

        ArsivDosya::create($dosya + [
            'firma_id' => $firma->id,
            'kategori' => $kategori['anahtar'],
            'baslik' => $baslik,
            'aciklama' => $data['aciklama'] ?? null,
            'yil' => (int) Carbon::parse($data['tarih'])->format('Y'),
            'baslangic_tarihi' => $data['tarih'],
            'aktif' => true,
            'asama' => ArsivDosya::DOSYADA,
            'kaynak' => 'yukleme',
        ]);

        unset($this->hazirRaporlar);
        Notification::make()->title('Rapor arşive kaydedildi')->body($dosya['dosya_adi'])->success()->send();
    }

    /**
     * Tek dosya yüklenmişse ve DÖF tablosu (Tespit / Öneri sütunlu Word-Excel)
     * içeriyorsa okunmuş sonucu döner; aksi halde null (normal arşivleme).
     *
     * @return array{bilgi: array<string, string>, maddeler: array<int, array<string, mixed>>}|null
     */
    protected function hazirRaporDofTablosu(array $data): ?array
    {
        $yollar = array_values((array) ($data['dosyalar'] ?? []));

        if (count($yollar) !== 1 || ! is_string($yollar[0])) {
            return null;
        }

        $yol = $yollar[0];
        $adi = (string) (((array) ($data['dosya_adlari'] ?? []))[$yol] ?? $yol);
        $uzanti = strtolower(pathinfo($adi, PATHINFO_EXTENSION));

        if (! in_array($uzanti, ['docx', 'xlsx', 'xls'], true) || ! Storage::disk('public')->exists($yol)) {
            return null;
        }

        try {
            $sonuc = DofTabloOkuyucu::oku(Storage::disk('public')->path($yol), $uzanti);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (! $sonuc['maddeler']) {
            return null;
        }

        Storage::disk('public')->delete($yol);

        return $sonuc;
    }

    /**
     * Varsayılan: DÖF Oluştur'a yönlendirip maddeleri orada açar. DÖF Oluştur
     * sayfası bunu ezer ve doğrudan kendi listesine ekler.
     *
     * @param  array{bilgi: array<string, string>, maddeler: array<int, array<string, mixed>>}  $tablo
     */
    protected function dofTablosunuAktar(array $tablo)
    {
        session()->put('dof_aktarim', ['firma_id' => $this->firma->id, 'tablo' => $tablo]);

        return $this->redirect(DofOlustur::getUrl());
    }

    /** Bu firmanın, sayfanın kategorisindeki arşiv dosyaları (yeniden eskiye). */
    #[Computed]
    public function hazirRaporlar(): Collection
    {
        return $this->firma
            ? ArsivDosya::query()
                ->where('firma_id', $this->firma->id)
                ->where('kategori', $this->hazirRaporKategorisi())
                ->whereNotNull('dosya_yolu')
                ->latest('baslangic_tarihi')->latest('id')
                ->get()
            : collect();
    }

    public function hazirRaporIndir(int $id)
    {
        $d = $this->hazirRaporlar->firstWhere('id', $id);

        if (! $d || ! Storage::disk('public')->exists($d->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($d->dosya_yolu, $d->dosya_adi);
    }

    public function hazirRaporSil(int $id): void
    {
        $d = $this->hazirRaporlar->firstWhere('id', $id);

        if (! $d) {
            return;
        }

        Storage::disk('public')->delete($d->dosya_yolu);
        $d->delete();
        unset($this->hazirRaporlar);
        Notification::make()->title('Rapor silindi')->success()->send();
    }
}
