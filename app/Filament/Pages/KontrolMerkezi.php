<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\SahaBulgusu;
use App\Support\FirmaEvrakPaketi;
use App\Support\FirmaEvrakZipUretici;
use App\Support\IsverenFirmaKarnesiUretici;
use App\Support\IsverenWhatsApp;
use App\Support\PortfoyKarne;
use App\Support\YasalTakvim;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * İSG Komuta Merkezi — isgpratik 135-136.jpg. Portföy genelinde yasal uyum
 * takibi. 3 sekme: Günlük Akış / Firma Asistanı / Çalışan Asistanı.
 */
class KontrolMerkezi extends Page
{
    use \App\Filament\Concerns\AileAnaSayfasi;

    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.kontrol-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'kontrol-merkezi';

    protected static ?string $title = 'Kontrol Merkezi';

    protected static ?string $navigationLabel = 'Kontrol Merkezi';

    public const SEKMELER = [
        'gunluk' => 'Günlük Akış',
        'firma' => 'Firma Asistanı',
        'calisan' => 'Çalışan Asistanı',
        'hizli_evrak' => 'Hızlı Evrak Matrisi',
        'kadro' => 'OSGB Kadro & Karne',
    ];

    public string $duyuruBaslik = '';

    public string $duyuruIcerik = '';

    public string $duyuruOncelik = 'normal';

    #[Url(as: 'sekme')]
    public string $sekme = 'firma';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** Komuta Merkezi ortak firma seçimi (App\Support\KomutaFirma): null = Tüm Portföy. */
    public function mount(): void
    {
        $this->firmaId = \App\Support\KomutaFirma::baslangic($this->firmalar);
    }

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
        $this->acikKriter = null;
        unset(
            $this->ozet,
            $this->kriterler,
            $this->kriterGruplari,
            $this->calisanEksikleri,
            $this->secilenFirma,
            $this->calisanKarne,
            $this->ekipKontenjanTablosu,
            $this->hizliEvrakMatrisi,
            $this->acikKriterEksikFirmalar,
        );
    }

    /** Portföy kriter listesinde "eksik firmaları göster" için açık olan kriter anahtarı. */
    public ?string $acikKriter = null;

    public function sekmeSec(string $sekme): void
    {
        if (array_key_exists($sekme, self::SEKMELER)) {
            $this->sekme = $sekme;
        }
    }

    /** Bir kriter satırına tıklayınca eksik firma listesini aç/kapat. */
    public function kriterDetayAc(string $anahtar): void
    {
        $this->acikKriter = $this->acikKriter === $anahtar ? null : $anahtar;
    }

    /**
     * Açık kriteri henüz karşılamayan firmalar (id => unvan). Kullanıcı "işyeri
     * hekimi 1 firmada eksik" görünce hangi firma olduğunu tıkla-gör.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function acikKriterEksikFirmalar(): array
    {
        if ($this->acikKriter === null) {
            return [];
        }

        return PortfoyKarne::eksikFirmalar(Filament::auth()->id(), $this->acikKriter, $this->firmaId)
            ->mapWithKeys(fn (Firma $f) => [$f->id => $f->secimEtiketi()])
            ->all();
    }

    /** @return array<int, string> */
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
    public function ozet(): array
    {
        return PortfoyKarne::ozet(Filament::auth()->id(), $this->firmaId);
    }

    /** Çalışan kaydı eksik firmalar (kayıtlı < bildirilen, TC / işe giriş boş) — çalışan listesine bağlantıyla. */
    #[Computed]
    public function calisanEksikleri(): array
    {
        $firmalar = Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->where('aktif', true)
            ->when($this->firmaId, fn ($q) => $q->whereKey($this->firmaId))
            ->orderBy('unvan')
            ->get();

        return array_map(fn (array $s) => [...$s, 'url' => \App\Filament\Resources\Calisans\CalisanResource::getUrl('index', ['tableFilters' => ['firma_id' => ['value' => $s['id']]]])],
            PortfoyKarne::calisanKaydiEksikFirmalar($firmalar));
    }

    #[Computed]
    public function kriterler(): array
    {
        return PortfoyKarne::kriterler(Filament::auth()->id(), $this->firmaId);
    }

    /** Kriterler 3 mantıksal grupta (Temel Yasal Belgeler / Saha & Kurul / Sağlık & Takip). */
    #[Computed]
    public function kriterGruplari(): array
    {
        return PortfoyKarne::grupla($this->kriterler);
    }

    #[Computed]
    public function secilenFirma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function calisanKarne(): ?array
    {
        return $this->secilenFirma
            ? PortfoyKarne::calisanKarne($this->secilenFirma)
            : null;
    }

    #[Computed]
    public function ekipKontenjanTablosu(): array
    {
        return PortfoyKarne::ekipKontenjanTablosu(Filament::auth()->id(), $this->firmaId);
    }

    #[Computed]
    public function hizliEvrakMatrisi(): ?array
    {
        return $this->secilenFirma
            ? PortfoyKarne::hizliEvrakMatrisi($this->secilenFirma)
            : null;
    }

    /** Seçili firma için 30 günlük salt-okunur İşveren İSG Uyum Karnesi imzalı bağlantısı ve WhatsApp linki. */
    #[Computed]
    public function isverenKarnePaylasim(): ?array
    {
        $firma = $this->secilenFirma;
        if (! $firma) {
            return null;
        }

        return [
            'link' => IsverenWhatsApp::pdfBaglantisi('firma-karnesi', $firma->id),
            'whatsapp' => IsverenWhatsApp::url($firma, IsverenWhatsApp::firmaKarnesiMesaji($firma), true),
        ];
    }

    public function isverenKarnesiPdf()
    {
        $firma = $this->secilenFirma;
        if (! $firma) {
            Notification::make()->title('Önce bir firma seçin')->warning()->send();

            return null;
        }

        return IsverenFirmaKarnesiUretici::pdf($firma);
    }

    protected function getHeaderActions(): array
    {
        $bosFormlar = FirmaEvrakPaketi::bosFormlar();

        return [
            \App\Filament\Support\SanalMufettisAksiyonu::make(
                fn () => $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null
            ),

            \App\Filament\Support\MufettisYetkilendirAksiyonu::make(
                fn () => $this->firmaId ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId) : null
            ),

            Action::make('isverenUyumKarnesi')
                ->label('İşveren Uyum Karnesi (PDF)')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->modalHeading('İşveren Salt-Okunur İSG Uyum Karnesi')
                ->modalDescription('Seçili firmanın tüm yasal evrak durumunu, İSG-KATİP hizmet sürelerini ve ekip kontenjanlarını tek sayfalık yönetici karnesi (PDF) olarak indirin.')
                ->modalSubmitActionLabel('Karneyi PDF İndir')
                ->schema([
                    Select::make('firma_id')
                        ->label('Firma')
                        ->options(fn () => $this->firmalar)
                        ->default(fn () => $this->firmaId ?: array_key_first($this->firmalar))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($data['firma_id']);

                    return IsverenFirmaKarnesiUretici::pdf($firma);
                }),

            Action::make('hizliEvrakPaketi')
                ->label('Hızlı Evrak Paketi (ZIP)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('primary')
                ->modalWidth('2xl')
                ->modalHeading('📦 Hızlı Evrak Paketi — Tek Tıkla Toplu ZIP İndir')
                ->modalDescription('Firmayı seçin; kayıtlı İSG evraklarını ve firma ünvanı basılı boş formları işaretleyip tek bir ZIP dosyası olarak indirin.')
                ->modalSubmitActionLabel('ZIP Olarak İndir')
                ->schema([
                    Select::make('firma_id')
                        ->label('Firma')
                        ->options(fn () => $this->firmalar)
                        ->default(fn () => $this->firmaId ?: array_key_first($this->firmalar))
                        ->live()
                        ->searchable()
                        ->required(),
                    Section::make('Kayıtlı Evraklar')
                        ->collapsible()
                        ->schema([
                            CheckboxList::make('kayitli')
                                ->hiddenLabel()
                                ->options(function (Get $get): array {
                                    $fId = (int) ($get('firma_id') ?: $this->firmaId);
                                    $f = $fId ? Firma::where('user_id', Filament::auth()->id())->find($fId) : null;

                                    return $f ? FirmaEvrakZipUretici::secenekler($f) : [];
                                })
                                ->bulkToggleable()
                                ->columns(1),
                        ]),
                    ...collect(FirmaEvrakPaketi::GRUPLAR)->map(fn (string $baslik, string $grup) => Section::make($baslik)
                        ->collapsible()
                        ->schema([
                            CheckboxList::make($grup)
                                ->hiddenLabel()
                                ->options($bosFormlar[$grup])
                                ->bulkToggleable()
                                ->columns(2),
                        ]))->values()->all(),
                ])
                ->action(function (array $data) {
                    $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($data['firma_id']);
                    unset($data['firma_id']);

                    if (! collect($data)->flatten()->filter()->count()) {
                        Notification::make()->title('En az bir evrak seçin')->warning()->send();

                        return null;
                    }

                    @set_time_limit(app()->runningInConsole() ? 0 : 300);

                    return FirmaEvrakPaketi::zip($firma, $data);
                }),
        ];
    }

    /** Günlük Akış penceresi (gün). */
    public const TAKVIM_GUN = 90;

    /**
     * Günlük Akış (13.11.2026): yalnız risk değerlendirmesi değil, tüm süreli yasal yükümlülükler —
     * süresi geçenler ve önümüzdeki 90 gün içinde dolanlar (App\Support\YasalTakvim).
     */
    #[Computed]
    public function yaklasanIsler(): array
    {
        return YasalTakvim::kalemler(Filament::auth()->id(), self::TAKVIM_GUN)
            ->map(fn (array $k) => [...$k, 'tarih' => $k['tarih']?->format('d.m.Y')])
            ->all();
    }

    /** Benim Günüm: tüm aktif firmalardaki açık/gecikmiş saha bulguları (tek tıkla +7 gün ertele / kapat). */
    #[Computed]
    public function acikSahaBulgulari(): array
    {
        return SahaBulgusu::query()
            ->whereIn('durum', ['acik', 'devam_ediyor', 'ertelendi'])
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id())->where('aktif', true))
            ->with('firma:id,unvan')
            ->orderByRaw('termin IS NULL, termin ASC')
            ->limit(15)
            ->get()
            ->map(function (SahaBulgusu $b): array {
                $gecikti = $b->termin && $b->termin->isPast() && ! $b->termin->isToday();

                return [
                    'id' => $b->id,
                    'bulgu_no' => $b->bulgu_no,
                    'firma' => $b->firma?->unvan ?? '—',
                    'firma_id' => $b->firma_id,
                    'uygunsuzluk' => (string) $b->uygunsuzluk,
                    'hiyerarsi' => $b->kontrolHiyerarsisiEtiketi(),
                    'hiyerarsi_renk' => \App\Models\SahaBulgusu::KONTROL_HIYERARSISI_RENK[$b->kontrol_hiyerarsisi ?? ''] ?? null,
                    'durum' => $b->durum,
                    'termin' => $b->termin?->format('d.m.Y'),
                    'gecikti' => $gecikti,
                    'url' => HizliSahaBulgusu::getUrl(['firma' => $b->firma_id]),
                ];
            })
            ->all();
    }

    public function bulguErtele(int $id, int $gun = 7): void
    {
        $bulgu = SahaBulgusu::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($id);

        if (! $bulgu) {
            return;
        }

        $baz = $bulgu->termin && $bulgu->termin->isFuture() ? $bulgu->termin : today();
        $yeniTermin = $baz->copy()->addDays(max(1, $gun));

        $bulgu->update([
            'termin' => $yeniTermin,
            'durum' => 'ertelendi',
        ]);

        unset($this->acikSahaBulgulari);
        Notification::make()
            ->title('Bulgu '.$gun.' gün ertelendi')
            ->body(($bulgu->bulgu_no ?: 'Bulgu').' — Yeni termin: '.$yeniTermin->format('d.m.Y'))
            ->success()
            ->send();
    }

    public function bulguTamamla(int $id): void
    {
        $bulgu = SahaBulgusu::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($id);

        if (! $bulgu) {
            return;
        }

        $bulgu->update([
            'durum' => 'kapandi',
            'kapanis_tarihi' => today(),
        ]);

        unset($this->acikSahaBulgulari);
        Notification::make()
            ->title('Saha bulgusu kapatıldı')
            ->body(($bulgu->bulgu_no ?: 'Bulgu').' tamamlandı olarak işaretlendi (bağlı DÖF kaydı güncellendi).')
            ->success()
            ->send();
    }

    /** OSGB Kadro Panosu: FTE kapasite + doluluk bantları (F4). */
    #[Computed]
    public function kadroPanosu(): array
    {
        return \App\Support\OsgbKadroAnalizi::kadroPanosu((int) Filament::auth()->id());
    }

    /** Belge sınıfı uyuşmazlığı tespit edilen firmalar (F4). */
    #[Computed]
    public function sinifUyusmazliklari(): array
    {
        return \App\Support\OsgbKadroAnalizi::sinifUyusmazliklari((int) Filament::auth()->id());
    }

    /** En uzun süredir saha ziyareti yapılmamış aktif firmalar (F4). */
    #[Computed]
    public function ihmalEdilenFirmalar(): array
    {
        return \App\Support\OsgbKadroAnalizi::ihmalEdilenFirmalar((int) Filament::auth()->id());
    }

    /** Aylık Uzman Değerlendirme Karnesi (0–100 + erken uyarı — F6). */
    #[Computed]
    public function uzmanKarneleri(): array
    {
        return \App\Support\OsgbKadroAnalizi::uzmanKarneleri((int) Filament::auth()->id());
    }

    /** Ekip içi duyuru ve talimatlar (okundu/onaylandı takipli — F6, kiracı izole). */
    #[Computed]
    public function ekipDuyurulari()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('ekip_duyurulari')) {
            return collect();
        }

        $user = Filament::auth()->user();
        if (! $user instanceof \App\Models\User) {
            return collect();
        }

        return \App\Models\EkipDuyurusu::query()
            ->whereIn('user_id', \App\Models\EkipDuyurusu::ekipKullaniciIdleri($user))
            ->with('gonderen:id,name')
            ->latest('id')
            ->limit(20)
            ->get();
    }

    public function ekipDuyurusuGonder(): void
    {
        $baslik = trim($this->duyuruBaslik);
        $icerik = trim($this->duyuruIcerik);
        if ($baslik === '' || $icerik === '') {
            Notification::make()->title('Başlık ve duyuru metni zorunludur')->warning()->send();

            return;
        }

        $user = Filament::auth()->user();
        \App\Models\EkipDuyurusu::create([
            'user_id' => $user->id,
            'baslik' => $baslik,
            'icerik' => $icerik,
            'oncelik' => in_array($this->duyuruOncelik, ['normal', 'onemli', 'acil'], true) ? $this->duyuruOncelik : 'normal',
            'okuyanlar' => [
                (string) $user->id => ['ad' => (string) $user->name, 'okundu_at' => now()->toIso8601String()],
            ],
        ]);

        \App\Support\DenetimKaydedici::kaydet('OSGB Ekip', 'olusturdu', 'Ekip duyurusu gönderildi: '.$baslik);

        $this->duyuruBaslik = '';
        $this->duyuruIcerik = '';
        $this->duyuruOncelik = 'normal';
        unset($this->ekipDuyurulari);

        Notification::make()->title('Ekip duyurusu yayınlandı')->body('Ekip üyelerinin okundu onayları aşağıda canlı izlenir.')->success()->send();
    }

    public function ekipDuyurusuOkundu(int $id): void
    {
        $user = Filament::auth()->user();
        if (! $user instanceof \App\Models\User) {
            return;
        }

        $duyuru = \App\Models\EkipDuyurusu::query()
            ->whereIn('user_id', \App\Models\EkipDuyurusu::ekipKullaniciIdleri($user))
            ->find($id);

        if ($duyuru) {
            $duyuru->okunduIsaretle($user);
            unset($this->ekipDuyurulari);
            Notification::make()->title('Okundu & onaylandı olarak işlendi')->success()->send();
        }
    }
}
