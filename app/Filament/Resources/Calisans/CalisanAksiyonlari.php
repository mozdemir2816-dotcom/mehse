<?php

namespace App\Filament\Resources\Calisans;

use App\Models\Firma;
use App\Support\CalisanDosyasiUretici;
use App\Support\CalisanExcelIceAktarici;
use App\Support\CalisanMukerrerBirlestirici;
use App\Support\CalisanListesiUretici;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Personel listesi başlık aksiyonları — Excel/PDF rapor, örnek şablon, toplu yükleme.
 * Firma → Çalışanlar sekmesinde firma sabittir ($sabitFirma); bağımsız Çalışanlar
 * sayfasında modalda firma seçilir (varsayılan: tablodaki firma filtresi).
 */
class CalisanAksiyonlari
{
    /**
     * @param  Closure(): ?Firma  $sabitFirma
     * @param  Closure(): mixed  $varsayilanFirmaId
     * @return array<int, Action>
     */
    public static function hepsi(?Closure $sabitFirma = null, ?Closure $varsayilanFirmaId = null): array
    {
        return [
            ActionGroup::make([
            static::rapor('excelRapor', 'Excel Rapor', 'heroicon-o-table-cells', $sabitFirma, $varsayilanFirmaId,
                fn (Firma $f, array $d) => CalisanListesiUretici::excel($f, $d['gorunum'], $d['sube'] ?? null)),
            static::rapor('pdfRapor', 'PDF Rapor', 'heroicon-o-document-text', $sabitFirma, $varsayilanFirmaId,
                fn (Firma $f, array $d) => CalisanListesiUretici::pdf($f, $d['gorunum'], $d['sube'] ?? null)),
            static::rapor('personelDosyasi', 'Toplu Personel Dosyası', 'heroicon-o-folder-arrow-down', $sabitFirma, $varsayilanFirmaId,
                fn (Firma $f, array $d) => CalisanDosyasiUretici::excel($f, $d['gorunum'], $d['sube'] ?? null))
                ->color('primary')
                ->modalDescription('Tüm çalışanlar tek Excel dosyasında: Özet · Personel · Eğitimler (eğitim kayıtları, katılım formları, uzaktan eğitim) · '
                    .'Sağlık (muayene formları, sağlık gözetimi tetkikleri, kan grubu). Sağlık bilgileri KVKK kapsamında özel nitelikli veridir; yalnız yetkili kişilerle paylaşın.'),

            Action::make('excelSablon')
                ->label('Excel Şablonu İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => CalisanExcelIceAktarici::sablonIndir()),

            Action::make('excelYukle')
                ->label('Doldurulan Excel\'i Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalDescription('İlk satır başlık kabul edilir, sütun sırası önemli değil; yalnızca "Ad Soyad" zorunlu. '
                    .'Boş hücreler mevcut bilgileri silmez; dolu alanlar işlenir. T.C. No (yoksa ad soyad) eşleşen kayıt güncellenir. '
                    .'İşten çıkış tarihi girilen kayıtlar otomatik pasife alınır. Listede olmayanlar yalnız aşağıdaki seçeneği açıp onaylarsanız pasife alınır. Excel Rapor çıktısı da düzenlenip geri yüklenebilir.')
                ->modalSubmitActionLabel('Yükle')
                ->schema([
                    ...static::firmaAlani($sabitFirma, $varsayilanFirmaId),
                    FileUpload::make('dosya')
                        ->label('Excel / CSV dosyası')
                        ->disk('local')
                        ->directory('excel-ice-aktarim')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->live()
                        ->helperText('Beklenen sütunlar (Örnek Excel\'i indirin): '.implode(', ', CalisanExcelIceAktarici::SABLON_BASLIKLARI)),
                    Toggle::make('pasife_al')
                        ->label('Listede olmayan aktif personeli pasife al')
                        ->helperText('Silinmez, yalnız pasife alınır (sonradan aktif yapılabilir). Önizleme aşağıda görünür.')
                        ->live(),
                    Placeholder::make('onizleme')
                        ->label('Önizleme')
                        ->visible(fn (Get $get) => filled($get('dosya')))
                        ->content(fn (Get $get) => static::onizlemeMetni($get, $sabitFirma)),
                    Toggle::make('pasife_onay')
                        ->label('Yukarıda listelenen personelin pasife alınmasını onaylıyorum')
                        // Önizleme hazırlanamasa bile seçenek açıkken onay kutusu hep görünür (sessizce atlanmasın).
                        ->visible(fn (Get $get) => (bool) $get('pasife_al'))
                        ->accepted(fn (Get $get) => (bool) $get('pasife_al')),
                ])
                ->action(function (array $data) use ($sabitFirma): void {
                    $yol = Storage::disk('local')->path($data['dosya']);
                    $firma = static::firmaBul($sabitFirma, $data);

                    try {
                        $sonuc = CalisanExcelIceAktarici::iceAktar($yol, $firma->id, (bool) ($data['pasife_al'] ?? false) && (bool) ($data['pasife_onay'] ?? false));
                    } catch (Throwable $e) {
                        Notification::make()->title('Dosya okunamadı')->body($e->getMessage())->danger()->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['dosya']);
                    }

                    $pasifeIstendi = (bool) ($data['pasife_al'] ?? false);
                    $bildirim = Notification::make()->title($sonuc['basarili'].' çalışan eklendi/güncellendi'
                        .($pasifeIstendi ? ', '.$sonuc['pasife'].' kişi pasife alındı' : ''));

                    $notlar = $sonuc['hatalar'];

                    if ($pasifeIstendi && $sonuc['pasife'] === 0) {
                        array_unshift($notlar, 'Pasife alınacak (listede olmayan aktif) personel bulunamadı veya yükleme işlenemedi.');
                    }

                    if ($notlar) {
                        $bildirim->body(implode("\n", array_slice($notlar, 0, 10)));
                    }

                    $sonuc['basarili'] > 0 ? $bildirim->success()->send() : $bildirim->danger()->send();
                }),

            Action::make('mukerrerBirlestir')
                ->label('Mükerrer Personeli Birleştir')
                ->icon('heroicon-o-arrows-pointing-in')
                ->color('gray')
                ->modalWidth('5xl')
                ->modalDescription('Aynı firmada aynı adlı kayıtlar tek kişide birleştirilir: en eski kayıt kalır, diğerlerine bağlı eğitim / muayene / KKD / '
                    .'ekip kayıtları ona taşınır, alanlar en yeni dolu değerle güncellenir, mükerrerler silinir. Farklı T.C.\'li aynı adlılar birleştirilmez. '
                    .'Önce aşağıdaki önizlemeyi inceleyin.')
                ->modalSubmitActionLabel('Birleştir')
                ->schema([
                    ...array_map(fn ($alan) => $alan->live(), static::firmaAlani($sabitFirma, $varsayilanFirmaId)),
                    Placeholder::make('mukerrerOnizleme')
                        ->label('Önizleme')
                        ->content(fn (Get $get) => static::mukerrerOnizlemeMetni($get, $sabitFirma)),
                    Toggle::make('onay')
                        ->label('Önizlemeyi inceledim; mükerrer kayıtların birleştirilip silinmesini onaylıyorum')
                        ->accepted(),
                ])
                ->action(function (array $data) use ($sabitFirma): void {
                    $sonuc = CalisanMukerrerBirlestirici::birlestir(static::firmaBul($sabitFirma, $data));

                    if ($sonuc['grup'] === 0) {
                        Notification::make()->title('Birleştirilecek mükerrer personel bulunamadı')->warning()->send();

                        return;
                    }

                    Notification::make()
                        ->title($sonuc['grup'].' kişide '.$sonuc['silinen'].' mükerrer kayıt birleştirildi')
                        ->body($sonuc['tasinan'].' bağlı kayıt tutulan kişiye taşındı'.($sonuc['cakisan'] ? '; '.$sonuc['cakisan'].' çakışan kayıt tek kayda indirildi.' : '.'))
                        ->success()->send();
                }),
            ])
                ->label('Dışa Aktar / İçe Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->button(),
        ];
    }

    private static function rapor(string $ad, string $etiket, string $ikon, ?Closure $sabitFirma, ?Closure $varsayilanFirmaId, Closure $uret): Action
    {
        return Action::make($ad)
            ->label($etiket)
            ->icon($ikon)
            ->color('gray')
            ->modalWidth('md')
            ->modalSubmitActionLabel('İndir')
            ->schema([
                ...static::firmaAlani($sabitFirma, $varsayilanFirmaId),
                Select::make('gorunum')->label('Personel görünümü')
                    ->options(CalisanListesiUretici::GORUNUMLER)->default('aktif')->required(),
                TextInput::make('sube')->label('Şube (isteğe bağlı)')
                    ->helperText('Boş bırakılırsa tüm şubeler'),
            ])
            ->action(fn (array $data) => $uret(static::firmaBul($sabitFirma, $data), $data));
    }

    /** @return array<int, Select> */
    private static function firmaAlani(?Closure $sabitFirma, ?Closure $varsayilanFirmaId): array
    {
        if ($sabitFirma) {
            return [];
        }

        return [
            Select::make('firma_id')->label('Firma')
                ->options(fn () => Firma::query()
                    ->where('user_id', Filament::auth()->id())
                    ->orderBy('unvan')->secenekler())
                ->default(fn () => $varsayilanFirmaId ? $varsayilanFirmaId() : null)
                ->searchable()->required(),
        ];
    }

    /** Form durumundaki yüklenmiş dosyanın geçici yolu (kaydedilmeden önce). */
    private static function yuklenenYol(Get $get): ?string
    {
        $deger = $get('dosya');
        $ilk = is_array($deger) ? (reset($deger) ?: null) : $deger;

        if ($ilk instanceof TemporaryUploadedFile) {
            return $ilk->getRealPath() ?: null;
        }

        return is_string($ilk) && Storage::disk('local')->exists($ilk) ? Storage::disk('local')->path($ilk) : null;
    }

    private static function onizleme(Get $get, ?Closure $sabitFirma): ?array
    {
        $yol = static::yuklenenYol($get);
        $firmaId = $sabitFirma ? $sabitFirma()?->id : (int) $get('firma_id');

        if (! $yol || ! $firmaId || ! Firma::query()->where('user_id', Filament::auth()->id())->whereKey($firmaId)->exists()) {
            return null;
        }

        // Aynı istekte form birkaç kez değerlendirilir; dosyayı her seferinde yeniden okuma.
        static $onbellek = [];
        $anahtar = $yol.'|'.$firmaId.'|'.@filemtime($yol).'|'.@filesize($yol);

        if (! array_key_exists($anahtar, $onbellek)) {
            try {
                $onbellek[$anahtar] = CalisanExcelIceAktarici::onizle($yol, (int) $firmaId);
            } catch (Throwable) {
                $onbellek[$anahtar] = null;
            }
        }

        return $onbellek[$anahtar];
    }

    private static function pasifeAlinacakVar(Get $get, ?Closure $sabitFirma): bool
    {
        return (bool) $get('pasife_al') && ! empty(static::onizleme($get, $sabitFirma)['pasife'] ?? []);
    }

    private static function onizlemeMetni(Get $get, ?Closure $sabitFirma): HtmlString|string
    {
        $o = static::onizleme($get, $sabitFirma);

        if (! $o) {
            return 'Önizleme hazırlanamadı (dosya okunamadı veya firma seçilmedi). Yükleme yine de yapılabilir; pasife alma için aşağıdaki onayı vermeniz gerekir.';
        }

        if ($o['hata']) {
            return $o['hata'];
        }

        $satir = '<div><strong>'.$o['yeni'].'</strong> yeni çalışan eklenecek · <strong>'.$o['guncellenen'].'</strong> çalışan güncellenecek</div>';

        if ($get('pasife_al')) {
            $adlar = $o['pasife'];
            $satir .= $adlar
                ? '<div style="margin-top:.4rem;color:#b45309"><strong>'.count($adlar).' / '.$o['aktif'].'</strong> aktif çalışan Excel\'de olmadığı için pasife alınacak: '
                    .e(implode(', ', array_slice($adlar, 0, 40))).(count($adlar) > 40 ? ' … (+'.(count($adlar) - 40).')' : '').'</div>'
                : '<div style="margin-top:.4rem">Pasife alınacak personel yok.</div>';
        }

        return new HtmlString($satir);
    }

    private static function mukerrerOnizlemeMetni(Get $get, ?Closure $sabitFirma): HtmlString|string
    {
        $firma = $sabitFirma
            ? $sabitFirma()
            : (filled($get('firma_id')) ? Firma::query()->where('user_id', Filament::auth()->id())->find($get('firma_id')) : null);

        return static::mukerrerOnizlemeHtml($firma);
    }

    /** Mükerrer birleştirme önizlemesi (modal içeriği). */
    public static function mukerrerOnizlemeHtml(?Firma $firma): HtmlString|string
    {
        if (! $firma) {
            return 'Önce firma seçin.';
        }

        $o = CalisanMukerrerBirlestirici::onizle($firma);

        if ($o['grup'] === 0 && ! $o['atlanan']) {
            return 'Bu firmada mükerrer (aynı adlı) personel yok.';
        }

        $etiket = ['tc' => 'T.C.', 'gorev' => 'Görev', 'departman' => 'Departman', 'sube' => 'Şube', 'cinsiyet' => 'Cinsiyet', 'ozel_durum' => 'Özel durum', 'ise_giris' => 'İşe giriş', 'isten_cikis' => 'İşten çıkış', 'dogum_tarihi' => 'Doğum', 'kan_grubu' => 'Kan grubu', 'telefon' => 'Telefon', 'eposta' => 'E-posta', 'agir_tehlikeli_iste' => 'Ağır/tehlikeli iş', 'notlar' => 'Notlar', 'aktif' => 'Aktif'];

        $html = '<div style="font-size:.85rem"><strong>'.$o['grup'].'</strong> kişide <strong>'.$o['silinecek'].'</strong> mükerrer kayıt silinecek · '
            .'<strong>'.$o['tasinacak'].'</strong> bağlı kayıt taşınacak'.($o['cakisan'] ? ' · <strong>'.$o['cakisan'].'</strong> çakışan kayıt tek kayda inecek' : '').'</div>';

        if ($o['satirlar']) {
            $html .= '<div style="max-height:18rem;overflow:auto;margin-top:.5rem"><table style="width:100%;border-collapse:collapse;font-size:.78rem">'
                .'<tr style="text-align:left"><th style="padding:.25rem .4rem">Kişi</th><th style="padding:.25rem .4rem">Kalacak kayıt ← silinecek</th><th style="padding:.25rem .4rem">Değişecek alanlar</th><th style="padding:.25rem .4rem">Taşınan</th></tr>';

            foreach (array_slice($o['satirlar'], 0, 80) as $s) {
                $degisen = collect($s['degisen'])->map(fn ($d, $alan) => e(($etiket[$alan] ?? $alan).': '.($d[0] !== '' ? $d[0] : '—').' → '.$d[1]))->implode('<br>');
                $html .= '<tr style="border-top:1px solid rgb(107 114 128 / .25);vertical-align:top">'
                    .'<td style="padding:.25rem .4rem"><strong>'.e($s['ad']).'</strong></td>'
                    .'<td style="padding:.25rem .4rem">#'.$s['tutulan'].' ← '.e(implode(', ', array_map(fn ($i) => '#'.$i, $s['silinecek']))).'</td>'
                    .'<td style="padding:.25rem .4rem">'.($degisen ?: '—').'</td>'
                    .'<td style="padding:.25rem .4rem">'.$s['tasinacak'].($s['cakisan'] ? ' (+'.$s['cakisan'].' çakışan)' : '').'</td></tr>';
            }

            $html .= '</table></div>'.(count($o['satirlar']) > 80 ? '<div style="font-size:.75rem">… ilk 80 kişi gösterildi; hepsi birleştirilir.</div>' : '');
        }

        if ($o['atlanan']) {
            $html .= '<div style="margin-top:.5rem;font-size:.78rem;color:#b45309"><strong>Birleştirilmeyecek ('.count($o['atlanan']).'):</strong> '
                .e(collect($o['atlanan'])->map(fn ($a) => $a['ad'].' — '.$a['neden'])->implode('; ')).'</div>';
        }

        return new HtmlString($html);
    }

    private static function firmaBul(?Closure $sabitFirma, array $data): Firma
    {
        return $sabitFirma
            ? $sabitFirma()
            : Firma::query()->where('user_id', Filament::auth()->id())->findOrFail($data['firma_id']);
    }
}
