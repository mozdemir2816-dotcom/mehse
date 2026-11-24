<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\DosyaKabul;
use App\Filament\Support\ImzaSecenegi;
use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Models\TespitOneriDefteri as TespitOneriDefteriModel;
use App\Support\BilgilendirmeYazisiOkuyucu;
use App\Support\BulguDonusturucu;
use App\Support\BulguHavuzu;
use App\Support\DofTabloOkuyucu;
use App\Support\GeminiOneriDanismani;
use App\Support\TespitOneriDefteriUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Tespit ve Öneri Defteri — isgpratik 65-66.jpg. Hazır katalogdan tek tıkla
 * madde ekleme + serbest (opsiyonel AI destekli) tespit/öneri yazma.
 */
class TespitOneriDefteri extends Page
{
    use SinirliErisim;
    use WithFileUploads;
    use \App\Filament\Concerns\WhatsAppGonderir;

    protected string $view = 'filament.pages.tespit-oneri-defteri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'tespit-oneri-defteri';

    protected static ?string $title = 'Tespit ve Öneri Defteri';

    protected static ?string $navigationLabel = 'Tespit Öneri Defteri';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public string $konuFiltre = '';

    public string $arama = '';

    public ?string $genelNot = null;

    // Serbest tespit/öneri yazma
    public ?string $serbestTespit = null;

    public ?string $serbestOneri = null;

    public bool $serbestHayatiTehlike = false;

    /** Serbest tespitin fotoğraf kanıtı (opsiyonel). */
    public $yeniFoto = null;

    public function mount(): void
    {
        // ?firma= yoksa Saha modülü ortak firma bağlamı (App\Support\KomutaFirma) açılır.
        if ($firmaId = request()->integer('firma') ?: \App\Support\KomutaFirma::baslangic($this->firmalar)) {
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
            ->secenekler()
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
    public function defter(): ?TespitOneriDefteriModel
    {
        return $this->firma ? TespitOneriDefteriModel::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function katalog(): array
    {
        $katalog = config('isg.tespit_oneri.katalog', []);

        if (blank($this->konuFiltre) && blank($this->arama)) {
            return $katalog;
        }

        $sonuc = [];

        foreach ($katalog as $kategori => $maddeler) {
            if ($this->konuFiltre && $this->konuFiltre !== $kategori) {
                continue;
            }

            $filtrelenen = $this->arama
                // Tespit metni + öneri + yasal dayanak (mevzuat maddesine göre de bulunur: "6331 m.10", "Yangın Yönetmeliği").
                ? array_values(array_filter($maddeler, fn ($m) => str_contains(
                    mb_strtolower($m['tespit'].' '.($m['oneri'] ?? '').' '.($m['dayanak'] ?? '')),
                    mb_strtolower($this->arama),
                )))
                : $maddeler;

            if ($filtrelenen) {
                $sonuc[$kategori] = $filtrelenen;
            }
        }

        return $sonuc;
    }

    #[Computed]
    public function konular(): array
    {
        return array_keys(config('isg.tespit_oneri.katalog', []));
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiOneriDanismani::aktifMi();
    }

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
        unset($this->firma, $this->defter);
        $this->genelNot = $this->defter()?->genel_not;
    }

    /*
    |--------------------------------------------------------------------------
    | Madde ekleme
    |--------------------------------------------------------------------------
    */

    public function katalogdanEkle(string $tespit): void
    {
        $d = $this->defter();

        if (! $d) {
            return;
        }

        $eklenecek = collect(config('isg.tespit_oneri.katalog', []))
            ->flatten(1)
            ->firstWhere('tespit', $tespit);

        if (! $eklenecek) {
            return;
        }

        $maddeler = $d->maddeler ?? [];
        $maddeler[] = [
            'tespit' => $eklenecek['tespit'],
            'oneri' => $eklenecek['oneri'],
            'dayanak' => $eklenecek['dayanak'] ?? null,
            'oncelik' => $eklenecek['oncelik'] ?? 'orta',
            'foto_yolu' => null,
        ];
        $d->update(['maddeler' => $maddeler]);

        Notification::make()->title('Madde eklendi')->success()->send();
    }

    public function aiOnerisiAl(): void
    {
        if (blank($this->serbestTespit)) {
            return;
        }

        $oneri = GeminiOneriDanismani::oner($this->serbestTespit);

        if ($oneri) {
            $this->serbestOneri = $oneri;
        } else {
            Notification::make()->title('AI önerisi alınamadı')->warning()->send();
        }
    }

    public function serbestEkle(): void
    {
        $d = $this->defter();

        if (! $d || blank($this->serbestTespit) || blank($this->serbestOneri)) {
            Notification::make()->title('Tespit ve öneri metni gerekli')->danger()->send();

            return;
        }

        $maddeler = $d->maddeler ?? [];
        $maddeler[] = [
            'tespit' => $this->serbestTespit,
            'oneri' => $this->serbestOneri,
            'dayanak' => $this->serbestHayatiTehlike ? '6331 Sayılı İSG Kanunu m.8/2 (Hayati Tehlike)' : null,
            'oncelik' => $this->serbestHayatiTehlike ? 'kritik' : 'orta',
            'hayati_tehlike' => $this->serbestHayatiTehlike,
            'foto_yolu' => \App\Support\GuvenliFoto::yukle($this->yeniFoto, 'tespit-oneri-foto'),
        ];
        $d->update(['maddeler' => $maddeler]);

        $this->reset('serbestTespit', 'serbestOneri', 'yeniFoto', 'serbestHayatiTehlike');
        Notification::make()->title('Madde eklendi')->success()->send();
    }

    public function hayatiTehlikeToggle(int $index): void
    {
        $d = $this->defter();
        $maddeler = $d?->maddeler ?? [];

        if (! $d || ! isset($maddeler[$index])) {
            return;
        }

        if (! empty($maddeler[$index]['kilitli'])) {
            Notification::make()->title('Onaylı nüshaya işlenmiş madde değiştirilemez')->warning()->send();

            return;
        }

        $aktif = ! empty($maddeler[$index]['hayati_tehlike']);
        $maddeler[$index]['hayati_tehlike'] = ! $aktif;
        if (! $aktif) {
            $maddeler[$index]['oncelik'] = 'kritik';
            $maddeler[$index]['dayanak'] = $maddeler[$index]['dayanak'] ?: '6331 Sayılı İSG Kanunu m.8/2 (Hayati Tehlike)';
        }
        $d->update(['maddeler' => $maddeler]);
        unset($this->defter);
    }

    public function maddeSil(int $index): void
    {
        $d = $this->defter();

        if (! $d) {
            return;
        }

        $maddeler = $d->maddeler ?? [];

        if (! empty($maddeler[$index]['kilitli'])) {
            Notification::make()
                ->title('Kilitli madde silinemez')
                ->body('Onaylı defter nüshasına işlenen maddeler resmi kayıt bütünlüğü gereği silinemez.')
                ->danger()
                ->send();

            return;
        }

        unset($maddeler[$index]);
        $d->update(['maddeler' => array_values($maddeler)]);
        unset($this->defter);
    }

    /** Henüz kilitlenmemiş defter maddelerini Seri/Sayfa ve Onay Makamı bilgisiyle kilitler. */
    public function nushayaKilitle(?string $seriSayfaNo = null, ?string $onayMakami = null): int
    {
        $d = $this->defter();

        if (! $d || empty($d->maddeler)) {
            return 0;
        }

        $maddeler = $d->maddeler;
        $kilitlenen = 0;
        $bilgi = collect([
            filled($seriSayfaNo) ? 'Seri/Sayfa: '.trim((string) $seriSayfaNo) : null,
            filled($onayMakami) ? 'Onay: '.trim((string) $onayMakami) : null,
        ])->filter()->implode(' · ');

        foreach ($maddeler as $i => $m) {
            if (! empty($m['kilitli'])) {
                continue;
            }
            $maddeler[$i]['kilitli'] = true;
            $maddeler[$i]['kilit_tarihi'] = now()->format('d.m.Y');
            if ($bilgi !== '') {
                $maddeler[$i]['nusha_bilgisi'] = $bilgi;
            }
            $kilitlenen++;
        }

        if ($kilitlenen > 0) {
            $d->update(['maddeler' => $maddeler]);
            unset($this->defter);
        }

        return $kilitlenen;
    }

    public function genelNotuKaydet(): void
    {
        $this->defter()?->update(['genel_not' => $this->genelNot]);
        Notification::make()->title('Not kaydedildi')->success()->send();
    }

    /** Kullanıcının Word/Excel'de hazırladığı tespit-öneri tablosunu deftere ekler (DÖF ile aynı okuyucu). */
    public function wordAktar(array $data): void
    {
        $d = $this->defter();
        $yol = (string) ($data['dosya'] ?? '');
        $adi = (string) ($data['dosya_adi'] ?? $yol);

        $yazi = null;

        try {
            $tam = Storage::disk('local')->path($yol);
            $okunan = DofTabloOkuyucu::oku($tam, pathinfo($adi, PATHINFO_EXTENSION))['maddeler'];
            $yazi = ! $okunan && strtolower(pathinfo($adi, PATHINFO_EXTENSION)) === 'docx' ? BilgilendirmeYazisiOkuyucu::oku($tam) : null;
        } catch (\Throwable $e) {
            report($e);
            $okunan = [];
        } finally {
            Storage::disk('local')->delete($yol);
        }

        // Tablo yoksa: tek bir bilgilendirme yazısı (firmaya önceden bildirilen tedbirler).
        if ($d && ! $okunan && $yazi) {
            $d->bilgilendirmeEkle($yazi);
            Notification::make()->title('Bilgilendirme yazısı deftere eklendi')->body('Ön bilgilendirme olarak: konu + firmanın alması gereken önlemler.')->success()->send();

            return;
        }

        if (! $d || ! $okunan) {
            Notification::make()
                ->title('Dosyada tespit-öneri tablosu bulunamadı')
                ->body('Tabloda "Tespit" ve "Öneri" başlıklı sütunlar ya da başlıklar altında maddeler olan bir bilgilendirme yazısı olmalı.')
                ->danger()->send();

            return;
        }

        $maddeler = collect($okunan)->map(fn (array $m) => [
            'tespit' => $m['tespit'],
            'oneri' => $m['oneri'] ?? '',
            'dayanak' => $m['dayanak'] ?? null,
            'oncelik' => $m['oncelik'] ?? 'orta',
            'foto_yolu' => $m['foto_yolu'] ?? null,
        ]);

        $d->update(['maddeler' => [...($d->maddeler ?? []), ...$maddeler->all()]]);
        Notification::make()->title($maddeler->count().' madde deftere eklendi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('wordAktar')
                ->label("Word/Excel'den Ekle")
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->visible(fn () => $this->defter() !== null)
                ->modalHeading('Hazırladığınız Word/Excel defterini ekle')
                ->modalDescription('Bilgisayarda hazırladığınız tablodaki her satır deftere madde olarak eklenir. Tablo ise her satır bir madde olur (sütunlar: Tespit, Öneri; isteğe bağlı Öncelik, Dayanak/Mevzuat, Fotoğraf). Tablosuz bilgilendirme yazısı ise tek madde olarak "Ön Bilgilendirme" şeklinde eklenir: konu, firmanın alması gereken önlemler ve dayanak. Mevcut maddeler silinmez.')
                ->modalSubmitActionLabel('Ekle')
                ->schema([
                    // MIME yerine uzantı — gerekçe DosyaKabul'de.
                    DosyaKabul::uygula(FileUpload::make('dosya')->storeFileNamesIn('dosya_adi')
                        ->label('Word (.docx) veya Excel (.xlsx/.xls) dosyası')
                        ->disk('local')->directory('tespit-oneri-aktarim'), ['docx', 'xlsx', 'xls'])
                        ->maxSize(20480)
                        ->required(),
                ])
                ->action(fn (array $data) => $this->wordAktar($data)),
            Action::make('bugununBulgulari')
                ->label('Bugünkü Saha Bulgularından Çek')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->visible(fn () => $this->defter() !== null)
                ->action(fn () => $this->bugununBulgulariniCek()),

            Action::make('bulgulardanEkle')
                ->label('Saha Bulgularından Ekle')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->visible(fn () => $this->defter() !== null)
                ->modalDescription('Saha Bulguları\'nda kayıtlı açık bulgular deftere tespit / öneri olarak eklenir.')
                ->schema([
                    CheckboxList::make('idler')
                        ->label('Açık bulgular')
                        ->options(fn () => BulguHavuzu::secenekler($this->firma->id, $this->defter()->maddeler ?? []))
                        ->required()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data): void {
                    $d = $this->defter();
                    $eklenen = SahaBulgusu::query()->where('firma_id', $this->firma->id)->whereIn('id', $data['idler'])->orderBy('id')->get();
                    $d->update(['maddeler' => [...($d->maddeler ?? []), ...$eklenen->map(fn ($b) => BulguDonusturucu::tespitOneriMaddesi($b))->all()]]);
                    Notification::make()->title($eklenen->count().' bulgu eklendi')->success()->send();
                }),

            Action::make('risktenEkle')
                ->label('Risk Değerlendirmesinden Çek')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn () => $this->defter() !== null && $this->firma?->riskDegerlendirmeleri()->exists())
                ->modalHeading('Risk Değerlendirmesinden Onaylı Deftere Madde Aktar')
                ->modalDescription('Firmanın son risk değerlendirmesindeki risk maddelerini seçip doğrudan Tespit ve Öneri Defterine aktarabilirsiniz.')
                ->modalSubmitActionLabel('Seçilenleri Deftere Ekle')
                ->schema([
                    CheckboxList::make('madde_idleri')
                        ->label('Risk Maddeleri (Yüksek puanlılar üstte)')
                        ->options(function (): array {
                            $rd = $this->firma?->riskDegerlendirmeleri()->latest('id')->first();
                            if (! $rd) {
                                return [];
                            }

                            return $rd->maddeler()
                                ->orderByDesc('puan')
                                ->limit(80)
                                ->get()
                                ->mapWithKeys(fn ($m) => [
                                    $m->id => trim(($m->duzey ? '['.$m->duzey.'] ' : '').$m->tehlike.($m->risk ? ' → '.$m->risk : '')),
                                ])
                                ->all();
                        })
                        ->required()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data): void {
                    $d = $this->defter();
                    $rd = $this->firma?->riskDegerlendirmeleri()->latest('id')->first();
                    if (! $d || ! $rd) {
                        return;
                    }

                    $maddeler = $rd->maddeler()->whereIn('id', $data['madde_idleri'] ?? [])->get();
                    $yeni = $maddeler->map(fn ($m) => [
                        'tespit' => trim($m->tehlike.($m->risk ? ' (Risk: '.$m->risk.')' : '')),
                        'oneri' => $m->oneri ?: ($m->mevcut_onlem ?: \App\Models\TespitOneriDefteri::VARSAYILAN_ONERI),
                        'dayanak' => $m->mevzuat ?: '6331 Sayılı İSG Kanunu Md. 10',
                        'oncelik' => in_array($m->duzey, ['Çok Yüksek', 'Yüksek', 'Tolerans Gösterilemez', 'Önemli'], true) ? 'yuksek' : 'orta',
                        'foto_yolu' => null,
                    ])->all();

                    $d->update(['maddeler' => [...($d->maddeler ?? []), ...$yeni]]);
                    unset($this->defter);
                    Notification::make()->title(count($yeni).' risk maddesi onaylı deftere eklendi')->success()->send();
                }),

            Action::make('nushaKilitle')
                ->label('Onaylı Nüshaya İşle / Kilitle')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn () => filled($this->defter()?->maddeler))
                ->modalHeading('Maddeleri Onaylı Defter Nüshasına İşle ve Kilitle')
                ->modalDescription('Onaylı deftere aktarılan maddeler kilitlenir; kilitli maddeler sonradan kazara silinemez ve künye bilgisi PDF çıktısına yansır.')
                ->modalSubmitActionLabel('Kilitle')
                ->schema([
                    TextInput::make('seri_sayfa_no')
                        ->label('Defter Seri / Sayfa No (opsiyonel)')
                        ->placeholder('Örn: Seri A · Sayfa 12–14'),
                    TextInput::make('onay_makami')
                        ->label('Onay Makamı / Tasdik No (opsiyonel)')
                        ->placeholder('Örn: 14. Noter / Çalışma ve İş Kurumu İl Müd.'),
                ])
                ->action(function (array $data): void {
                    $adet = $this->nushayaKilitle($data['seri_sayfa_no'] ?? null, $data['onay_makami'] ?? null);
                    if ($adet === 0) {
                        Notification::make()->title('Kilitlenecek yeni madde yok')->warning()->send();

                        return;
                    }
                    Notification::make()->title($adet.' madde onaylı nüshaya kilitlendi')->success()->send();
                }),

            Action::make('pdf')
                ->label('PDF Çıktı Al')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->defter() !== null)
                ->action(fn () => TespitOneriDefteriUretici::pdf($this->defter())),

            Action::make('whatsapp')
                ->label('📲 WhatsApp ile Gönder')
                ->color('success')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn () => $this->defter() !== null)
                ->action(function (): void {
                    $d = $this->defter();
                    $this->whatsappAc($d ? \App\Support\IsverenWhatsApp::url($d->firma, \App\Support\IsverenWhatsApp::defterMesaji($d)) : null, 'Defter sayfası');
                }),
        ];
    }

    /**
     * Bugün açılan saha bulguları (Saha Denetimi'nde "Uygun Değil" yapılanlar, DÖF maddeleri, hızlı
     * bulgular) deftere resmî dille eklenir; deftere zaten bağlı bulgular atlanır (bulgu_id).
     */
    public function bugununBulgulariniCek(): void
    {
        $d = $this->defter();
        if (! $d) {
            return;
        }

        $eklenen = TespitOneriDefteriModel::bugununBulgulariniEkle($this->firma);
        unset($this->defter);

        if ($eklenen === 0) {
            Notification::make()->title('Bugün deftere eklenecek yeni saha bulgusu yok')
                ->body('Bugün açılan bulgular zaten defterde ya da henüz kayıt yok (Saha Denetimi\'ni "Tamamla" ile kaydedin).')->warning()->send();

            return;
        }

        Notification::make()->title($eklenen.' bugünkü bulgu deftere eklendi')->success()->send();
    }

    /** Katalogdaki yüksek öncelikli temel yasal maddelerden defterde olmayan ilk 5 tanesini tek tıkla ekler. */
    public function temelTespitleriEkle(): void
    {
        $d = $this->defter();
        if (! $d) {
            return;
        }

        $mevcutTespitler = collect($d->maddeler ?? [])->pluck('tespit')->all();

        $adaylar = collect(config('isg.tespit_oneri.katalog', []))
            ->flatten(1)
            ->reject(fn (array $m) => in_array($m['tespit'], $mevcutTespitler, true))
            ->sortByDesc(fn (array $m) => ($m['oncelik'] ?? 'orta') === 'yuksek' ? 1 : 0)
            ->take(5)
            ->map(fn (array $m) => [
                'tespit' => $m['tespit'],
                'oneri' => $m['oneri'] ?? '',
                'dayanak' => $m['dayanak'] ?? '6331 Sayılı İSG Kanunu',
                'oncelik' => $m['oncelik'] ?? 'yuksek',
                'foto_yolu' => null,
            ])
            ->values()
            ->all();

        if ($adaylar === []) {
            Notification::make()->title('Temel katalog maddeleri zaten defterde mevcut')->warning()->send();

            return;
        }

        $d->update(['maddeler' => [...($d->maddeler ?? []), ...$adaylar]]);
        unset($this->defter);

        Notification::make()->title(count($adaylar).' temel yasal tespit ve öneri maddesi eklendi')->success()->send();
    }

    /** Onaylı defterdeki maddeleri DÖF Oluştur modülüne aktarır. */
    public function dofModuluneAktar()
    {
        $d = $this->defter();
        if (! $d || ! $this->firma || empty($d->maddeler)) {
            Notification::make()->title('Aktarılacak defter maddesi bulunamadı')->warning()->send();

            return null;
        }

        $maddeler = collect($d->maddeler)->map(fn (array $m) => [
            'tespit' => $m['tespit'],
            'oncelik' => ($m['oncelik'] ?? 'orta') === 'yuksek' ? 'yuksek' : 'orta',
            'oneri' => $m['oneri'] ?? \App\Models\TespitOneriDefteri::VARSAYILAN_ONERI,
            'sorumlu' => $this->firma->isveren_vekili ?: $this->firma->isveren_ad,
            'termin' => now()->addDays(15)->toDateString(),
            'durum' => 'acik',
            'foto_yolu' => $m['foto_yolu'] ?? null,
        ])->values()->all();

        session([
            'dof_aktarim' => [
                'firma_id' => $this->firma->id,
                'kaynak' => 'Tespit ve Öneri Defteri',
                'maddeler' => $maddeler,
            ],
        ]);

        return redirect(DofOlustur::getUrl(['firma' => $this->firma->id]));
    }
}
