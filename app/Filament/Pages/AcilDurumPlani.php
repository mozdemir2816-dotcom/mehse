<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AileAnaSayfasi;
use App\Filament\Concerns\SinirliErisim;
use App\Filament\Support\ImzaSecenegi;
use App\Models\AcilDurumPlani as PlanModel;
use App\Models\AcilEkip;
use App\Models\Calisan;
use App\Models\Firma;
use App\Support\AcilDurumHazirlik;
use App\Support\AcilDurumKapakUretici;
use App\Support\AcilDurumKonuSecici;
use App\Support\AcilDurumPlaniUretici;
use App\Support\AcilDurumSektor;
use App\Support\AcilDurumWordUretici;
use App\Support\ExcelBellek;
use App\Support\GeminiAcilIletisimBulucu;
use App\Support\GeminiAcilSenaryoUretici;
use App\Support\KullaniciAyarlari;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnitEnum;

/**
 * Acil Durum Eylem Planı — isgpratik 19-21, 146-154.jpg. Firma seçilir, konu
 * sayfaları + destek ekipleri + kapak çerçevesi ayarlanır → PDF üretilir.
 * Ayrıca 7 acil durum afişi (A3/A4 talimat) indirilir.
 */
class AcilDurumPlani extends Page
{
    use AileAnaSayfasi;
    use SinirliErisim;
    use \App\Filament\Concerns\WhatsAppGonderir;

    protected string $view = 'filament.pages.acil-durum-plani';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'acil-durum-plani';

    protected static ?string $title = 'Acil Durum Eylem Planı';

    protected static ?string $navigationLabel = 'Acil Durum & Yangın Merkezi';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /**
     * 4 adımlı düzen (02.11.2026): 1 Firma & Senaryolar · 2 Saha Bilgileri · 3 Müdahale & İletişim ·
     * 4 Onay & Çıktı. Adres geçmişine yazılır — tarayıcının "geri" tuşu bir önceki adıma döner.
     */
    #[Url(as: 'adim', history: true, except: 1)]
    public int $adim = 1;

    public const ADIMLAR = [1 => 'Firma & Senaryolar', 2 => 'Saha Bilgileri', 3 => 'Müdahale & İletişim', 4 => 'Onay & Çıktı'];

    // Form durumu (seçili plana bağlanır)
    public ?string $dokumanNo = null;

    public ?string $raporTarihi = null;

    public string $kapakCercevesi = 'klasik';

    public ?string $revizyonNo = null;

    public ?string $toplanmaYeri = null;

    public ?string $disaridanEtkileyebilecekIsyerleri = null;

    /** @var array<int, string> seçili konu anahtarları */
    public array $konular = [];

    /** @var array<string, string> ekip anahtarı => virgülle ayrık isimler */
    public array $ekipMetni = [];

    /** isgsuite plan sihirbazı 2-3. adım: tedbirler, müdahale, iletişim, onay / tatbikat (bkz. AcilDurumHazirlik). */
    public array $uygulama = [];

    // Afiş seçimi
    public string $afisTipi = 'yangin';

    public string $afisEbat = 'a4';

    /**
     * Gerçek referans Word/PowerPoint şablonu seçimi — kullanıcı zamanla farklı
     * biçimli acil durum planı örnekleri ekleyecek (`config isg.acil_durum.
     * word_sablonlari`); "Word (Orijinal Şablon)" ve "Kapak Sayfası" aksiyonları
     * seçili şablona göre üretir.
     */
    public string $sablonId = 'fom';

    /** Risk puanları / çevresel riskler bu planda boştu, kullanıcının şablonundan getirildi. */
    public bool $puanlarSablondan = false;

    /** Yapay zekâ özel senaryo: başlık, düzenlenebilir önizleme (listeler satır satır metin), kütüphaneye kaydet. */
    public string $ozelBaslik = '';

    public ?array $ozelOnizleme = null;

    public bool $ozelKutuphaneye = true;

    public bool $ozelPanel = false;

    /** Adrese göre bulunan kurumlar (uzman onaylayana kadar yalnız öneri). */
    public array $iletisimOnerileri = [];

    public bool $iletisimAramali = false;

    /** Firmalar listesinden "Acil Durum Planı" satır aksiyonuyla ?firma= ile gelinir. */
    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }

        if (! $this->firma || ! isset(self::ADIMLAR[$this->adim])) {
            $this->adim = 1;
        }
    }

    public function adimaGit(int $adim): void
    {
        if (isset(self::ADIMLAR[$adim]) && ($adim === 1 || $this->firma)) {
            $this->adim = $adim;
        }
    }

    /** Adımın altındaki "Kaydet ve İleri". */
    public function kaydetVeIlerle(): void
    {
        $this->kaydet();
        $this->adimaGit(min(4, $this->adim + 1));
    }

    /**
     * Firma seçilince (02.11.2026) adrese göre resmî kurum telefonları arka planda bir kez aranır: görünüm
     * yüklendikten sonra ayrı istekle (x-init) çağrılır, sayfa beklemez. Yalnız EK-2'de kurum yokken,
     * adres girilmişken ve bu plan için daha önce denenmemişse çalışır.
     */
    public function iletisimOtomatikBul(): void
    {
        $plan = $this->plan();

        if (! $plan || ! $this->iletisimOtomatikGerekli()) {
            return;
        }

        // Aynı anda ikinci istek olmasın; yapay zekâ yanıt vermezse ertesi gün yeniden denenir, bulursa bir daha aranmaz.
        $anahtar = 'adep-iletisim-otomatik-'.$plan->id;
        \Illuminate\Support\Facades\Cache::put($anahtar, true, now()->addDay());
        $this->iletisimBul();

        if (filled($this->uygulama['hastane_ad'] ?? null) || $this->iletisimOnerileri || count($this->uygulama['iletisim'] ?? []) > 1) {
            \Illuminate\Support\Facades\Cache::forever($anahtar, true);
        }
    }

    public function iletisimOtomatikGerekli(): bool
    {
        $plan = $this->plan();

        return $plan !== null
            && GeminiAcilIletisimBulucu::aktifMi()
            && mb_strlen(trim((string) $this->firmaAdresi())) >= 8
            && blank($this->uygulama['hastane_ad'] ?? null)
            && collect($this->uygulama['iletisim'] ?? [])->reject(fn ($i) => trim((string) ($i['telefon'] ?? '')) === '112')->isEmpty()
            && ! \Illuminate\Support\Facades\Cache::has('adep-iletisim-otomatik-'.$plan->id);
    }

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

    /**
     * Plan ekipleri için yasal asgari (Acil Durumlar Yön. md.11 / İlkyardım Yön.) ile atanan kişi sayısı.
     * Atanan: Acil Durum Ekipleri'ndeki üyeler, yoksa buradaki virgülle yazılan isimler.
     *
     * @return array<string, array{gereken: int, atanan: int, eksik: int}>
     */
    public function ekipSayaclari(): array
    {
        $firma = $this->firma;

        if (! $firma) {
            return [];
        }

        $calisan = $firma->calisanlar()->where('aktif', true)->count() ?: (int) $firma->calisan_sayisi;
        $sonuc = [];

        foreach (config('isg.acil_durum.ekip_turleri') as $tur => $bilgi) {
            if (! $bilgi['plan'] || ! $bilgi['oran']) {
                continue;
            }

            $ekip = AcilEkip::where('firma_id', $firma->id)->where('tur', $tur)->first();
            $gereken = AcilEkip::yasalMinimum($tur, $firma->tehlike_sinifi, $calisan);
            $atanan = $ekip
                ? $ekip->uyeler()->count()
                : count(array_filter(array_map('trim', explode(',', (string) ($this->ekipMetni[$bilgi['plan']] ?? '')))));

            $sonuc[$bilgi['plan']] = ['gereken' => $gereken, 'atanan' => $atanan, 'eksik' => max($gereken - $atanan, 0)];
        }

        return $sonuc;
    }

    public function plan(): ?PlanModel
    {
        return $this->firma ? PlanModel::firmaIcin($this->firma) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanSecenekleri);
        $this->ozelOnizleme = null;
        $this->ozelPanel = false;
        $this->iletisimOnerileri = [];

        $plan = $this->plan();

        if (! $plan) {
            return;
        }

        $this->dokumanNo = $plan->dokuman_no;
        $this->raporTarihi = $plan->rapor_tarihi?->toDateString() ?? now()->toDateString();
        $this->kapakCercevesi = $plan->kapak_cercevesi ?: 'klasik';
        $this->revizyonNo = $plan->revizyon_no;
        $this->toplanmaYeri = $plan->toplanma_yeri;
        $this->disaridanEtkileyebilecekIsyerleri = $plan->disaridan_etkileyebilecek_isyerleri;
        $this->konular = $plan->konular ?? [];
        $this->uygulama = array_replace(AcilDurumHazirlik::varsayilan(), $plan->uygulama ?? []);

        // Yeni plan (02.11.2026): NACE'ye göre seçilen senaryolara firmanın risk analizi / iş ekipmanı /
        // kimyasal envanterinden gelenler de "Risk Analizinden Otomatik Belirle"ye basılmadan eklenir.
        if ($plan->wasRecentlyCreated && $this->firma) {
            $oneri = AcilDurumKonuSecici::riskAnalizindenOner($this->firma);
            $this->konular = AcilDurumKonuSecici::zorunluEkle([...$this->konular, ...$oneri]);
            $plan->forceFill(['konular' => $this->konular])->save();

            Notification::make()->title(count($this->konular).' acil durum senaryosu otomatik seçildi')
                ->body('Firmanın NACE kodu ('.($this->firma->nace_kodu ?: '—').'), tehlike sınıfı'.($oneri ? ' ve risk analizi' : '').' esas alındı: '
                    .collect(config('isg.acil_durum.konular'))->whereIn('anahtar', $this->konular)->pluck('ad')->implode(', ').'. Gerekirse değiştirip Kaydet\'e basın.')
                ->success()->send();
        }

        // Sektör: kayıtlı seçim yoksa NACE önerisi (uzman değiştirebilir).
        if (! AcilDurumSektor::gecerliMi($this->uygulama['sektor'] ?? null)) {
            $this->uygulama['sektor'] = AcilDurumSektor::oner($this->firma);
        }

        // Boş kutular hazır örnek metinle gelir; uzman düzeltip Kaydet'e basar.
        foreach (config('isg.acil_durum.ornek_metinler', []) as $alan => $metin) {
            if (blank($this->uygulama[$alan] ?? null)) {
                $this->uygulama[$alan] = $metin;
            }
        }

        // Aynı bölgedeki işyerleri: bir kez doldurulan puanlar boş planlara gelir.
        $onceki = $this->uygulama;
        $this->uygulama = AcilDurumHazirlik::sablonlaTamamla($this->uygulama, Filament::auth()->user());
        $this->puanlarSablondan = $onceki !== $this->uygulama;

        unset($this->hazirlik);
        $this->ekipMetni = collect(config('isg.acil_durum.ekipler'))
            ->mapWithKeys(fn ($ad, $k) => [$k => implode(', ', $plan->ekipListesi()[$k] ?? [])])
            ->all();
    }

    public function konuToggle(string $anahtar): void
    {
        if (in_array($anahtar, AcilDurumKonuSecici::zorunlu(), true)) {
            Notification::make()->title('Bu senaryo her planda bulunur')->body('Yangın, iş kazası / ilkyardım ve elektrik çarpması kaldırılamaz.')->warning()->send();

            return;
        }

        $this->konular = in_array($anahtar, $this->konular, true)
            ? array_values(array_diff($this->konular, [$anahtar]))
            : [...$this->konular, $anahtar];
    }

    public function tumKonular(bool $sec): void
    {
        $this->konular = $sec
            ? collect(config('isg.acil_durum.konular'))->pluck('anahtar')->all()
            : AcilDurumKonuSecici::zorunlu();
    }

    /**
     * "Risk Analizinden Otomatik Belirle" (01.11.2026): firmanın risk değerlendirmesi maddeleri, iş ekipmanları
     * ve kimyasal envanterinden senaryo ekler (kaldırmaz) ve EK-3 KKD faaliyetlerini belirler. Kaydet'e basılınca kalıcı olur.
     */
    public function riskAnalizindenBelirle(): void
    {
        if (! $this->firma) {
            return;
        }

        $oneri = AcilDurumKonuSecici::riskAnalizindenOner($this->firma);
        $yeni = array_values(array_diff($oneri, $this->konular));
        $this->konular = AcilDurumKonuSecici::zorunluEkle([...$this->konular, ...$yeni]);

        $kkd = AcilDurumKonuSecici::kkdFaaliyetleri($this->firma);
        if ($kkd !== null) {
            $this->uygulama['kkd_faaliyetler'] = $kkd;
        }

        if ($oneri === [] && $kkd === null) {
            Notification::make()->title('Firmada risk analizi verisi bulunamadı')
                ->body('Risk değerlendirmesi, iş ekipmanı veya kimyasal envanteri girildikten sonra tekrar deneyin. Senaryoları elle seçebilirsiniz.')->warning()->send();

            return;
        }

        $adlar = collect(config('isg.acil_durum.konular'))->whereIn('anahtar', $yeni)->pluck('ad')->implode(', ');
        Notification::make()->title($yeni ? count($yeni).' senaryo eklendi' : 'Yeni senaryo gerekmedi')
            ->body(($yeni ? $adlar.'. ' : 'Risk analizindeki senaryolar zaten seçili. ').'EK-3 KKD faaliyetleri güncellendi. Kalıcı olması için Kaydet düğmesine basın.')
            ->success()->send();
    }

    /** EK-3 KKD faaliyet sütunu aç / kapat (boşsa otomatik listeden başlar). */
    public function kkdFaaliyetToggle(string $faaliyet): void
    {
        $mevcut = $this->kkdFaaliyetleri();
        $this->uygulama['kkd_faaliyetler'] = in_array($faaliyet, $mevcut, true)
            ? array_values(array_diff($mevcut, [$faaliyet]))
            : array_values(array_intersect(config('isg.acil_durum.kkd_matrisi.faaliyetler'), [...$mevcut, $faaliyet]));
    }

    /** @return array<int, string> EK-3'te basılacak faaliyetler: seçili, yoksa otomatik, o da yoksa hepsi. */
    public function kkdFaaliyetleri(): array
    {
        return ($this->uygulama['kkd_faaliyetler'] ?? []) ?: ($this->firma ? AcilDurumKonuSecici::kkdFaaliyetleri($this->firma) : null)
            ?: config('isg.acil_durum.kkd_matrisi.faaliyetler');
    }

    /* ---------- Yapay zekâ ile özel senaryo (02.11.2026) ---------- */

    public function ozelSenaryoUret(): void
    {
        $sonuc = GeminiAcilSenaryoUretici::uret($this->ozelBaslik, AcilDurumSektor::liste()[$this->uygulama['sektor'] ?? ''] ?? null);

        if ($sonuc['hata']) {
            Notification::make()->title($sonuc['hata'])->warning()->send();

            return;
        }

        $s = $sonuc['senaryo'];
        $this->ozelOnizleme = [
            'ad' => $s['ad'], 'aciklama' => (string) $s['aciklama'],
            ...collect(array_keys(GeminiAcilSenaryoUretici::LISTELER))->mapWithKeys(fn ($k) => [$k => implode("\n", $s[$k])])->all(),
        ];
    }

    /** Önizlemedeki (düzeltilmiş) senaryo plana kalıcı eklenir; istenirse kullanıcının kütüphanesine de. */
    public function ozelSenaryoOnayla(): void
    {
        $plan = $this->plan();
        $s = $this->ozelOnizleme ? GeminiAcilSenaryoUretici::normalize($this->ozelOnizleme) : null;

        if (! $plan || ! $s || ! $s['mudahale']) {
            Notification::make()->title('Senaryonun başlığı ve en az bir müdahale adımı olmalı')->warning()->send();

            return;
        }

        $this->ozelSenaryolariKaydet([...($this->uygulama['ozel_senaryolar'] ?? []), $s]);

        if ($this->ozelKutuphaneye && ($user = Filament::auth()->user())) {
            $kutuphane = collect($this->ozelKutuphanesi())->reject(fn ($k) => mb_strtolower($k['ad']) === mb_strtolower($s['ad']));
            KullaniciAyarlari::kaydet($user, ['acil_durum_ozel_senaryolari' => $kutuphane->push($s)->take(-30)->values()->all()]);
        }

        $this->ozelOnizleme = null;
        $this->ozelBaslik = '';
        $this->ozelPanel = false;
        Notification::make()->title('"'.$s['ad'].'" plana eklendi')
            ->body('EK-1’de seçili senaryolardan sonra numaralanır.'.($this->ozelKutuphaneye ? ' Diğer firmalarınızda da kütüphaneden tek tıkla eklenebilir.' : ''))->success()->send();
    }

    public function ozelSenaryoSil(int $i): void
    {
        $liste = $this->uygulama['ozel_senaryolar'] ?? [];
        unset($liste[$i]);
        $this->ozelSenaryolariKaydet(array_values($liste));
    }

    /** Kütüphanedeki senaryoyu bu plana yeni anahtarla ekler. */
    public function kutuphanedenEkle(int $i): void
    {
        $s = $this->ozelKutuphanesi()[$i] ?? null;
        $mevcut = collect($this->uygulama['ozel_senaryolar'] ?? [])->pluck('ad')->map(fn ($a) => mb_strtolower($a));

        if (! $s || $mevcut->contains(mb_strtolower($s['ad']))) {
            return;
        }

        $this->ozelSenaryolariKaydet([...($this->uygulama['ozel_senaryolar'] ?? []), [...$s, 'anahtar' => null]]);
        Notification::make()->title('"'.$s['ad'].'" plana eklendi')->success()->send();
    }

    /** @return array<int, array> kullanıcının kaydettiği özel senaryolar (users.ayarlar) */
    public function ozelKutuphanesi(): array
    {
        $kayitli = (array) (((array) (Filament::auth()->user()?->ayarlar ?? []))['acil_durum_ozel_senaryolari'] ?? []);

        return collect($kayitli)->filter(fn ($s) => is_array($s))->map(fn ($s) => GeminiAcilSenaryoUretici::normalize($s))->filter()->values()->all();
    }

    private function ozelSenaryolariKaydet(array $liste): void
    {
        $plan = $this->plan();
        $temiz = AcilDurumHazirlik::temizle(['ozel_senaryolar' => $liste])['ozel_senaryolar'];
        $this->uygulama['ozel_senaryolar'] = $temiz;
        $plan?->forceFill(['uygulama' => [...($plan->uygulama ?? []), 'ozel_senaryolar' => $temiz]])->save();
    }

    /* ---------- Saha bilgileri ve iletişim tamamlama sihirbazı (02.11.2026) ---------- */

    /**
     * "Adrese Göre Kurumları Bul (Yapay Zekâ)" (02.11.2026): firmanın açık adresi + ilçe / il ile en yakın
     * hastane, itfaiye, emniyet / jandarma, kaymakamlık, elektrik ve doğalgaz arıza kurumları bulunur;
     * telefonu bulunanlar acil iletişim listesine (EK-2) OTOMATİK eklenip kaydedilir, telefonu bulunamayanlar
     * elle tamamlamak için listede kalır.
     */
    public function iletisimBul(): void
    {
        $sonuc = GeminiAcilIletisimBulucu::bul($this->firmaAdresi());

        if ($sonuc['hata']) {
            Notification::make()->title($sonuc['hata'])->warning()->send();

            return;
        }

        $this->iletisimAramali = $sonuc['aramali'];
        $satirlar = collect($sonuc['satirlar'])->map(fn ($r) => [...$r, 'sec' => filled($r['telefon'])]);
        $telefonlu = $satirlar->where('sec', true);

        if ($telefonlu->isEmpty()) {
            $this->iletisimOnerileri = $satirlar->all();
            Notification::make()->title('Kurumlar bulundu ama telefon numarası bulunamadı')->body('Numaraları elle girip ekleyin.')->warning()->send();

            return;
        }

        $this->iletisimOnerileri = $telefonlu->all();
        $this->iletisimOnerileriniEkle();
        $this->iletisimOnerileri = $satirlar->where('sec', false)->values()->all();

        Notification::make()->title($telefonlu->count().' kurum acil iletişim listesine eklendi')
            ->body($telefonlu->pluck('birim')->implode(', ').'. Numaraları yapay zekâ buldu — çıktı almadan önce kontrol edin.'
                .($this->iletisimOnerileri ? ' Telefonu bulunamayan '.count($this->iletisimOnerileri).' kurum aşağıda; elle tamamlayın.' : ''))
            ->success()->persistent()->send();
    }

    /** Açık adres; ilçe / il adreste geçmiyorsa sona eklenir. */
    private function firmaAdresi(): ?string
    {
        $f = $this->firma;
        if (! $f) {
            return null;
        }
        $adres = trim((string) $f->adres);
        foreach ([$f->ilce, $f->il] as $parca) {
            if (filled($parca) && ! str_contains(mb_strtolower($adres), mb_strtolower(trim($parca)))) {
                $adres = trim($adres.' '.trim($parca), ' ,');
            }
        }

        return $adres ?: null;
    }

    /** Onaylanan kurumlar: hastane plan alanlarına, diğerleri acil iletişim listesine (EK-2) yazılır ve kaydedilir. */
    public function iletisimOnerileriniEkle(): void
    {
        $secilen = collect($this->iletisimOnerileri)->filter(fn ($r) => ($r['sec'] ?? false) && filled($r['ad'] ?? null));

        if ($secilen->isEmpty()) {
            Notification::make()->title('Eklenecek kurum seçilmedi')->warning()->send();

            return;
        }

        $liste = collect($this->uygulama['iletisim'] ?? []);
        foreach ($secilen as $r) {
            $ad = trim($r['ad']);
            $tel = trim((string) $r['telefon']);

            if ($r['kurum'] === 'hastane') {
                $this->uygulama['hastane_ad'] = $ad;
                $this->uygulama['hastane_tel'] = $tel;

                continue;
            }

            $birim = GeminiAcilIletisimBulucu::KURUMLAR[$r['kurum']] ?? '';
            $liste = $liste->reject(fn ($i) => mb_strtolower(trim((string) ($i['ad'] ?? ''))) === mb_strtolower($ad))
                ->push(['ad' => $ad, 'telefon' => $tel, 'aciklama' => trim($birim.(filled($r['not'] ?? null) ? ' — '.$r['not'] : ''))]);
        }

        $this->uygulama['iletisim'] = $liste->values()->all();
        $this->iletisimOnerileri = [];
        $this->kaydet();
    }

    /** Sihirbazda boş kalan sorular (alan => [soru, örnek]); ekrandaki değere bakar. */
    public function eksikSorular(): array
    {
        return collect(AcilDurumHazirlik::SIHIRBAZ_SORULARI)
            ->filter(fn ($soru, $alan) => blank($alan === 'toplanma_yeri' ? $this->toplanmaYeri : ($this->uygulama[$alan] ?? null)))
            ->all();
    }

    /** @return Collection<int, Calisan> firmanın aktif çalışanları (koordinatör seçimi) */
    #[Computed]
    public function calisanSecenekleri(): Collection
    {
        return $this->firma ? $this->firma->calisanlar()->where('aktif', true)->whereNull('isten_cikis')
            ->orderBy('ad_soyad')->get(['id', 'ad_soyad', 'gorev', 'telefon']) : collect();
    }

    /** Çalışanlar listesinden koordinatör / vekil seçimi: adı ve (varsa) telefonu yazar. */
    public function koordinatorSec(string $rol, int|string|null $calisanId): void
    {
        $c = $this->calisanSecenekleri->firstWhere('id', (int) $calisanId);
        if (! $c || ! in_array($rol, ['koordinator', 'koordinator_vekil'], true)) {
            return;
        }

        $this->uygulama[$rol === 'koordinator' ? 'koordinator_ad' : 'koordinator_vekil'] = $c->ad_soyad.($c->gorev ? ' ('.$c->gorev.')' : '');
        if (filled($c->telefon)) {
            $this->uygulama[$rol === 'koordinator' ? 'koordinator_tel' : 'koordinator_vekil_tel'] = $c->telefon;
        }
    }

    public function kaydet(): void
    {
        $plan = $this->plan();

        if (! $plan) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $plan->forceFill([
            'dokuman_no' => $this->dokumanNo,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => null, // saving hook yeniden hesaplar
            'kapak_cercevesi' => $this->kapakCercevesi,
            'revizyon_no' => $this->revizyonNo,
            'toplanma_yeri' => $this->toplanmaYeri,
            'disaridan_etkileyebilecek_isyerleri' => $this->disaridanEtkileyebilecekIsyerleri,
            'konular' => $this->konular ?: null,
            'uygulama' => AcilDurumHazirlik::temizle($this->uygulama),
            'ekipler' => collect($this->ekipMetni)
                ->map(fn ($metin) => array_values(array_filter(array_map('trim', explode(',', (string) $metin)))))
                ->all(),
        ])->save();

        unset($this->hazirlik, $this->portfoy);
        $this->puanlarSablondan = false;

        Notification::make()->title('Acil durum planı kaydedildi')->success()->send();

        // İlk doldurulan plan, diğer firmalar için şablon olur.
        $user = Filament::auth()->user();
        $u = $plan->uygulama ?? [];
        if ($user && AcilDurumHazirlik::bosMu(AcilDurumHazirlik::kullaniciSablonu($user))
            && ! AcilDurumHazirlik::bosMu(array_intersect_key($u, array_flip(AcilDurumHazirlik::SABLON_ALANLARI)))) {
            AcilDurumHazirlik::sablonaKaydet($user, $u);
            Notification::make()->title('Risk puanları varsayılan yapıldı')->body('Puanı girilmemiş diğer firmalarınızda bu puanlar otomatik gelecek.')->info()->send();
        }
    }

    /** Ekrandaki risk puanları ve çevresel riskleri tüm firmalar için varsayılan yapar. */
    public function puanlariVarsayilanYap(): void
    {
        AcilDurumHazirlik::sablonaKaydet(Filament::auth()->user(), $this->uygulama);

        Notification::make()->title('Varsayılan risk puanları güncellendi')->body('Puanı girilmemiş firmalarda bu değerler gelecek; puanı kayıtlı planlar değişmez.')->success()->send();
    }

    public function ornekMetin(string $alan): void
    {
        $metin = config('isg.acil_durum.ornek_metinler.'.$alan);

        if (is_string($metin)) {
            $this->uygulama[$alan] = $metin;
        }
    }

    public function iletisimEkle(): void
    {
        $this->uygulama['iletisim'][] = ['ad' => '', 'telefon' => '', 'aciklama' => ''];
    }

    public function iletisimSil(int $i): void
    {
        unset($this->uygulama['iletisim'][$i]);
        $this->uygulama['iletisim'] = array_values($this->uygulama['iletisim']);
    }

    /** Seçili planın hazırlık kontrolü (kayıtlı veriden). */
    #[Computed]
    public function hazirlik(): ?array
    {
        $plan = $this->plan();

        return $plan ? AcilDurumHazirlik::kontrol($plan->fresh('firma')) : null;
    }

    /** Tüm işyerlerinin acil durum hazırlığı (isgsuite plan portföyü). */
    #[Computed]
    public function portfoy(): Collection
    {
        return AcilDurumHazirlik::portfoy((int) Filament::auth()->id());
    }

    public function portfoyExcel()
    {
        ExcelBellek::artir();
        $kitap = new Spreadsheet;
        $s = $kitap->getActiveSheet();
        $s->setTitle('Acil Durum Hazırlığı');
        $s->fromArray(['İşyeri', 'Tehlike sınıfı', 'Plan tarihi', 'Gözden geçirme', 'Hazırlık %', 'Durum', 'Eksik başlıklar'], null, 'A1');
        $s->getStyle('A1:G1')->getFont()->setBold(true);
        foreach ($this->portfoy->values() as $n => $r) {
            $k = $r['kontrol'];
            $s->fromArray([
                $r['firma']->unvan, $r['firma']->tehlikeSinifiEtiketi(),
                $r['plan']?->rapor_tarihi?->format('d.m.Y'), $r['plan']?->gecerlilik_tarihi?->format('d.m.Y'),
                $k ? $k['yuzde'] : null, $k ? AcilDurumHazirlik::DURUM_ETIKET[$k['durum']] : 'Plan yok',
                $k ? collect($k['kontroller'])->where('tamam', false)->pluck('baslik')->implode('; ') : null,
            ], null, 'A'.($n + 2));
        }
        foreach (range('A', 'G') as $c) {
            $s->getColumnDimension($c)->setAutoSize(true);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'adp').'.xlsx';
        (new Xlsx($kitap))->save($tmp);

        return response()->streamDownload(function () use ($tmp) {
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, 'acil-durum-hazirligi.xlsx');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Plan Çıktısı')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->schema([
                    Radio::make('bicim')
                        ->label('Dosya biçimi')
                        ->options(['pdf' => 'PDF', 'word' => 'Word (.docx — düzenlenebilir)'])
                        ->default('pdf')
                        ->inline()
                        ->required(),
                    ImzaSecenegi::alan(),
                ])
                ->action(function (array $data) {
                    $this->kaydet();

                    // Word: PDF ile aynı içerik ve düzen (Kapsamlı ADEP), seçili şablondan bağımsız.
                    return ($data['bicim'] ?? 'pdf') === 'word'
                        ? AcilDurumWordUretici::docx($this->plan(), 'fom', ImzaSecenegi::secili($data))
                        : AcilDurumPlaniUretici::pdf($this->plan(), ImzaSecenegi::secili($data));
                }),

            Action::make('whatsapp')
                ->label('📲 WhatsApp ile İşverene Gönder')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->visible(fn () => $this->firma !== null)
                ->tooltip('İşverenin cep telefonuna 30 gün geçerli plan (PDF) bağlantısıyla hazır mesaj.')
                ->action(function (): void {
                    $this->kaydet();
                    $plan = $this->plan();
                    $this->whatsappAc(\App\Support\IsverenWhatsApp::url($this->firma, \App\Support\IsverenWhatsApp::adepMesaji($plan)), 'Acil Durum Eylem Planı');
                }),

            Action::make('word')
                ->label('Word')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->tooltip('Seçili Word şablonuyla düzenlenebilir plan (.docx). Kapsamlı ADEP, PDF ile aynı içeriği FOM belgesinin biçimiyle verir.')
                ->schema([ImzaSecenegi::alan()])
                ->action(function (array $data) {
                    $this->kaydet();

                    return AcilDurumWordUretici::docx($this->plan(), $this->sablonId, ImzaSecenegi::secili($data));
                }),

            Action::make('kapakSayfasi')
                ->label('Kapak Sayfası')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => $this->firma !== null && filled(config('isg.acil_durum.word_sablonlari.'.$this->sablonId.'.kapak_dosya')))
                ->tooltip('Word şablonuyla eşleşen kapak sayfası (PowerPoint) — ayrı yazdırılıp plan çıktısının önüne konur.')
                ->schema([ImzaSecenegi::alan()])
                ->action(fn () => AcilDurumKapakUretici::pptx($this->plan(), $this->sablonId)),

            Action::make('krokiPlani')
                ->label('Kroki Planı Hazırla')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->url(fn () => AcilDurumKrokisi::getUrl(['firma' => $this->firma->id])),

            Action::make('tahliyePlani')
                ->label('Tahliye Planı Görseli')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('İşyerinin kat/bölüm krokisi üzerine kaçış yolları, toplanma yeri ve acil durum ekipmanlarının işaretlendiği tahliye planı — İşyerlerinde Acil Durumlar Hakkında Yönetmelik gereği zorunludur.')
                ->fillForm(fn (): array => ['tahliye_plani_gorseli' => $this->plan()?->tahliye_plani_gorseli])
                ->schema([
                    FileUpload::make('tahliye_plani_gorseli')
                        ->label('Tahliye planı / kroki görseli')
                        ->image()->imageEditor()
                        ->disk('public')->directory('acil-durum-tahliye')->maxSize(4096),
                ])
                ->action(function (array $data): void {
                    $this->plan()?->forceFill($data)->save();
                    Notification::make()->title('Tahliye planı görseli kaydedildi')->success()->send();
                }),
        ];
    }

    public function afisIndir()
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        return AcilDurumPlaniUretici::afis($this->firma, $this->afisTipi, $this->afisEbat);
    }

    /** Firmaya otomatik tanımlı (planın seçili konularından türeyen) afiş anahtarları. */
    #[Computed]
    public function afisTipleri(): array
    {
        return $this->plan() ? AcilDurumPlaniUretici::afisTipleri($this->plan()) : [];
    }

    public function afislerZipIndir()
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (empty($this->afisTipleri)) {
            Notification::make()->title('Bu firma için otomatik tanımlı afiş yok')
                ->body('Önce Acil Durum Planı konularını kaydedin.')->warning()->send();

            return null;
        }

        return AcilDurumPlaniUretici::afisZip($this->firma, $this->afisTipleri, $this->afisEbat);
    }
}
