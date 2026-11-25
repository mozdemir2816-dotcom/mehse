<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Collection;

/**
 * İşyerinde yapılan gerçek iş kalemleri (`config('isg.kkd_matris.is_kalemleri')`) üzerinden
 * Yıllık Eğitim Planı 4. Bölümünü, Eğitim Katılım Formu ekip/iş kolu seçeneklerini ve firma
 * bazlı müfredatı tek merkezden besler.
 */
class IsKalemiKutuphanesi
{
    /** Şantiyelerde (İnşaat / Şantiye grubunda) otomatik en başa eklenecek genel tehlikeler başlığı. */
    public const SANTIYE_GENEL_ANAHTAR = 'insaat_genel_tehlikeler';

    /** Sektör anahtarı → öncelikli gösterilecek iş kalemi grubu. */
    private const SEKTOR_ANA_GRUP = [
        'insaat' => 'İnşaat / Şantiye',
        'maden' => 'İnşaat / Şantiye',
        'asansor' => 'İnşaat / Şantiye',
        'enerji_elektrik' => 'İnşaat / Şantiye',
        'cimento_beton' => 'İnşaat / Şantiye',
        'nace_metal' => 'İmalat / Fabrika',
        'nace_plastik' => 'İmalat / Fabrika',
        'nace_kimya' => 'İmalat / Fabrika',
        'nace_ahsap' => 'İmalat / Fabrika',
        'nace_basim' => 'İmalat / Fabrika',
        'tekstil' => 'İmalat / Fabrika',
        'nace_gida' => 'Gıda / Tarım / Ormancılık',
        'nace_tarim' => 'Gıda / Tarım / Ormancılık',
        'nakliye_tasima' => 'Genel / Hizmet / Lojistik',
        'nace_ofis' => 'Genel / Hizmet / Lojistik',
        'nace_ticaret' => 'Genel / Hizmet / Lojistik',
        'nace_temizlik' => 'Genel / Hizmet / Lojistik',
        'nace_konaklama' => 'Genel / Hizmet / Lojistik',
        'nace_egitim' => 'Genel / Hizmet / Lojistik',
        'nace_saglik' => 'Genel / Hizmet / Lojistik',
        'nace_atik' => 'Genel / Hizmet / Lojistik',
    ];

    /**
     * Grup adı => [anahtar => ['anahtar', 'ad', 'grup', 'egitim_konusu']]
     *
     * @return array<string, array<string, array{anahtar: string, ad: string, grup: string, egitim_konusu: string}>>
     */
    public static function gruplar(): array
    {
        $sonuc = [];

        foreach (config('isg.kkd_matris.is_kalemleri', []) as $grup => $kalemler) {
            foreach ($kalemler as $k) {
                $anahtar = trim((string) ($k['anahtar'] ?? ''));
                $konu = trim((string) ($k['egitim_konusu'] ?? ''));
                if ($anahtar === '' || $konu === '') {
                    continue;
                }

                $sonuc[$grup][$anahtar] = [
                    'anahtar' => $anahtar,
                    'ad' => (string) ($k['ad'] ?? $anahtar),
                    'grup' => (string) $grup,
                    'egitim_konusu' => $konu,
                ];
            }
        }

        return $sonuc;
    }

    /** @return Collection<string, array{anahtar: string, ad: string, grup: string, egitim_konusu: string}> */
    public static function tumu(): Collection
    {
        $duz = [];
        foreach (static::gruplar() as $kalemler) {
            foreach ($kalemler as $anahtar => $veri) {
                $duz[$anahtar] = $veri;
            }
        }

        return collect($duz);
    }

    /** Verilen sektör (veya firmanın etkin grubu) için öncelikli iş kalemi grubu adı. */
    public static function anaGrup(?string $sektorAnahtari, ?Firma $firma = null): string
    {
        $sektor = $sektorAnahtari ?: ($firma ? IseOzguSenkron::etkinGrup($firma) : null);

        return self::SEKTOR_ANA_GRUP[$sektor] ?? 'İnşaat / Şantiye';
    }

    /** Firma bir şantiye / inşaat firması mı? */
    public static function santiyeFirmasiMi(?Firma $firma): bool
    {
        if (! $firma) {
            return false;
        }

        $etkin = IseOzguSenkron::etkinGrup($firma);

        return $etkin === 'insaat' || ($etkin === null && YillikPlanSablonu::insaatMi($firma));
    }

    /** Verilen iş kalemi anahtarları arasında 'İnşaat / Şantiye' grubuna ait en az bir iş kalemi var mı? */
    public static function santiyeKalemiVarMi(array $anahtarlar): bool
    {
        $tumu = static::tumu();

        return collect($anahtarlar)->contains(fn ($a) => ($tumu->get($a)['grup'] ?? null) === 'İnşaat / Şantiye');
    }

    /**
     * Şantiye iş kalemi seçiliyse "İnşaat Genel Tehlikeleri (Boşluklar, Kat Kenarları, Merdiven & Asansör Şaftları)"
     * başlığını otomatik olarak listenin en başına ekler.
     *
     * @param  array<int, string>  $anahtarlar
     * @return array<int, string>
     */
    public static function santiyeBasliginiEkle(array $anahtarlar): array
    {
        $temiz = array_values(array_unique(array_filter($anahtarlar, 'is_string')));

        if (! static::santiyeKalemiVarMi($temiz)) {
            return $temiz;
        }

        if (in_array(self::SANTIYE_GENEL_ANAHTAR, $temiz, true)) {
            return $temiz;
        }

        return [self::SANTIYE_GENEL_ANAHTAR, ...$temiz];
    }

    /**
     * Eğitim Katılım Formu "Çalışan Grubu / İş Kolu" butonları için seçenekler:
     *  - Firmada `is_kalemleri` seçiliyse YALNIZ firmanın seçili iş kalemleri döner ($tumunuGoster = false iken).
     *  - Seçili değilse aktif sektörün ana grubundaki tüm iş kalemleri döner.
     *
     * @return array<string, string> anahtar => ad
     */
    public static function katilimSecenekleri(?Firma $firma, ?string $sektorAnahtari, bool $tumunuGoster = false): array
    {
        $tumu = static::tumu();
        $firmaKalemleri = array_values(array_filter((array) ($firma?->is_kalemleri ?? [])));

        if (! $tumunuGoster && $firmaKalemleri !== []) {
            $secili = [];
            foreach ($firmaKalemleri as $a) {
                if ($tumu->has($a)) {
                    $secili[$a] = $tumu[$a]['ad'];
                }
            }
            if ($secili !== []) {
                return $secili;
            }
        }

        if ($tumunuGoster) {
            return $tumu->mapWithKeys(fn (array $k) => [$k['anahtar'] => $k['ad']])->all();
        }

        $anaGrup = static::anaGrup($sektorAnahtari, $firma);
        $grupKalemleri = static::gruplar()[$anaGrup] ?? [];

        return collect($grupKalemleri)->mapWithKeys(fn (array $k) => [$k['anahtar'] => $k['ad']])->all();
    }

    /**
     * Seçilen iş kalemlerinden Yıllık Eğitim Planı 4. Bölüm (`ise_ozgu`) satırlarını üretir.
     * Mevcut plandaki tamamlanmış (G) ayları konu/anahtar eşleşmesiyle korur.
     *
     * @param  array<int, string>  $anahtarlar
     * @param  array<int, array<string, mixed>>  $mevcutEgitimler
     * @return array<int, array<string, mixed>>
     */
    public static function planSatirlari(array $anahtarlar, int $kilitAy = 0, array $mevcutEgitimler = []): array
    {
        $anahtarlar = static::santiyeBasliginiEkle($anahtarlar);
        $tumu = static::tumu();

        // Mevcut 4. bölüm tamamlandı (G) aylarını konu adı veya kütüphane anahtarıyla koru
        $mevcutG = [];
        foreach (collect($mevcutEgitimler)->where('kategori', 'ise_ozgu') as $m) {
            $norm = EgitimKonulari::normalize((string) ($m['konu'] ?? ''));
            $kAnahtar = (string) ($m['kutuphane_anahtari'] ?? '');
            foreach (($m['aylar'] ?? []) as $ay => $durum) {
                if ($durum === 'tamamlandi') {
                    $mevcutG[$norm][$ay] = true;
                    if ($kAnahtar !== '') {
                        $mevcutG[$kAnahtar][$ay] = true;
                    }
                }
            }
        }

        $satirlar = [];
        foreach (array_values(array_unique($anahtarlar)) as $a) {
            $k = $tumu->get($a);
            if (! $k) {
                continue;
            }

            $kutuphaneAnahtari = 'kalem_'.$a;
            $satir = YillikPlan::maddeAylarIle([[
                'konu' => $k['ad'],
                'kategori' => 'ise_ozgu',
                'hedef' => $k['egitim_konusu'],
                'hedef_kitle' => $k['ad'],
                'egitici' => IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                'hafta' => 2,
                'varsayilan_aylar' => range(0, 11),
                'kutuphane_anahtari' => $kutuphaneAnahtari,
            ]], $kilitAy)[0];

            $norm = EgitimKonulari::normalize($k['ad']);
            foreach (array_keys(($mevcutG[$kutuphaneAnahtari] ?? []) + ($mevcutG[$norm] ?? [])) as $ay) {
                $satir['aylar'][$ay] = 'tamamlandi';
            }

            $satirlar[] = $satir;
        }

        return $satirlar;
    }

    /**
     * Eğitim konusu için boş bırakılan "Eğitimin Hedefi" (Excel C sütunu) metnini
     * iş kalemi kütüphanesi, standart şablon ve hazır İSG hedef sözlüğünden otomatik üretir.
     * Mevcut hedef doluysa olduğu gibi korunur.
     */
    public static function hedefOlustur(string $konu, ?string $mevcutHedef = null): string
    {
        $mevcut = trim((string) $mevcutHedef);
        if ($mevcut !== '') {
            return $mevcut;
        }

        $temizKonu = trim($konu);
        if ($temizKonu === '') {
            return '';
        }

        $norm = EgitimKonulari::normalize($temizKonu);

        // 1) İş kalemi kütüphanesinde doğrudan eşleşme
        foreach (static::tumu() as $k) {
            if (EgitimKonulari::normalize($k['ad']) === $norm && trim($k['egitim_konusu']) !== '') {
                return trim($k['egitim_konusu']);
            }
        }

        // 2) Standart yıllık plan şablonunda eşleşme
        foreach (config('yillik_plan_sablonu.egitimler', []) as $e) {
            if (EgitimKonulari::normalize((string) ($e['konu'] ?? '')) === $norm && trim((string) ($e['hedef'] ?? '')) !== '') {
                return trim((string) $e['hedef']);
            }
        }

        // 3) Yaygın sektör / şantiye / asansör / atölye konuları için hazır İSG hedefleri
        $sozluk = [
            'iskele ve kalip guvenligi' => 'Cephe, mobil ve kalıp altı iskelelerin standartlara uygun kurulum-söküm ve kontrol kurallarını, kalıp montaj-demontajında düşme ve devrilme önlemlerini öğretmek.',
            'yuksekte calisma onlemleri' => 'Yüksekte çalışmada toplu koruma (korkuluk, güvenlik ağı), yaşam hattı, tam vücut emniyet kemeri ve güvenli erişim kurallarıyla düşme kazalarını önlemek.',
            'yuksekte calisma ve dusmeyi onleme' => 'Yüksekte çalışmada toplu koruma (korkuluk, güvenlik ağı), yaşam hattı, tam vücut emniyet kemeri ve güvenli erişim kurallarıyla düşme kazalarını önlemek.',
            'yuksekte guvenli calisma ve dusmeyi durdurma donanimlari' => 'Yüksekte çalışmalarda ankraj, yaşam hattı ve tam vücut emniyet kemerinin doğru seçim, kontrol ve kullanım kurallarını sahada uygulamak.',
            'kazi islerinde guvenlik' => 'Kazı ve hafriyatta şevlendirme, iksa/shoring, yeraltı tesisat tespiti, su tahliyesi ve kazı kenarı düşme-göçük önlemlerini uygulamak.',
            'kazi ve zemin isleri' => 'Kazı, hafriyat ve zemin iyileştirme işlerinde şevlendirme, iksa, göçük önleme, yeraltı hat tespiti ve ağır makine çevre güvenliğini sağlamak.',
            'agir is makinesi kullanimi' => 'Ekskavatör, beko loder, yükleyici, silindir ve kamyonlarla çalışmada kör nokta, işaretçi-manevra, saha hızı ve yaya-araç ayrımı kurallarını uygulamak.',
            'dusen nesne tehlikesi' => 'Kat kenarlarında malzeme istif yasağı, eteklik/ağ/yakalama platformu, alet bağlama ve baret kullanımıyla yüksekten malzeme düşmesi kazalarını önlemek.',
            'iskele guvenligi ve platform kurallari' => 'Sabit, mobil ve asma iskeleler ile çalışma platformlarının kurulum, yük sınırı, korkuluk, tekerlek kilidi ve günlük kontrol kurallarını uygulamak.',
            'iskelede guvenli calisma' => 'Cephe ve mobil iskelelerde güvenli çıkma-inme, korkuluk ve platform tamlığı, yükleme sınırları ve yüksekten düşmeye karşı korunma kurallarını öğretmek.',
            'manlift iskele guvenli kullanimi' => 'Makaslı/eklemli personel yükseltici platform (manlift) ve iskelelerin zemin kontrolü, aşırı yük koruması, emniyet kemeri bağlantısı ve güvenli kullanım kurallarını uygulamak.',
            'el aletleri ve elektrik guvenligi' => 'Elektrikli/şarjlı el aletlerinin koruyucuları, topraklama, kaçak akım rölesi (30 mA), seyyar kablo düzeni ve güvenli kullanım kurallarıyla çarpılma ve yaralanmaları önlemek.',
            'kimyasal ve toz maruziyeti riskleri' => 'Boya, tiner, solvent, yapıştırıcı ve tozlu çalışmalarda GBF/SDS okuma, lokal/genel havalandırma, solunum maskesi ve göz-cilt koruma kurallarını uygulamak.',
            'insaattaki riskler' => 'Şantiyedeki temel tehlikeleri (boşluklar, kat kenarları, merdiven/asansör şaftları, elektrik, düşen malzeme ve saha trafiği) tanıyarak toplu ve kişisel koruma önlemlerini uygulamak.',
            'kalip islerine ozgu riskler ve guvenli calisma' => 'Kalıp montaj-demontajında teleskopik dikme, yatar daire testere koruyucuları, çivi/kesici el aletleri ve geçici kenar koruma kurallarını uygulamak.',
            'demir donati islerine ozgu riskler ve guvenli calisma' => 'Demir kesme-bükme makineleri, spiral taşlama, filiz demiri başlık koruması, ergonomik taşıma ve göz-el KKD kurallarıyla kesilme ve batma kazalarını önlemek.',
            'elektrik ve saha tertip duzeni' => 'Şantiye geçici elektrik panoları, kaçak akım rölesi, kablo askılama, aydınlatma ve düzenli malzeme istifleme ile çarpılma, takılma ve yangın risklerini önlemek.',
            'kaldirma tasima ve vinc ile iletisim' => 'Kule/mobil vinç ve kaldırma ekipmanlarında sapan-kanca kontrolü, yük dengesi, askıda yük altına girmeme ve standart işaretçi iletişimi kurallarını uygulamak.',
            'yuk kaldirma tasima ve askilama guvenligi' => 'Kaldırma-iletme ekipmanları, sapan ve mapaların kontrolü, güvenli yük bağlama ve askıda yük çevresinde emniyet kurallarını uygulamak.',
            'kaynak islerinden guvenli calisma' => 'Kaynak, kesme ve taşlama işlerinde sıcak iş izni, yangın önlemleri, tüp güvenliği, kaynak dumanı havalandırması ve UV/IR maske-eldiven kullanımını sağlamak.',
            'servis araclari guvenli surusu ve seyir guvenligi' => 'Servis ve saha araçlarının güvenli sürüş, hız sınırları, emniyet kemeri, malzeme sabitleme, araç bakım ve trafik güvenliği kurallarını uygulamak.',
            'kapali kisitli alan tehlikeleri ve ergonomi' => 'Kapalı/kısıtlı alanlarda giriş izni, havalandırma, gaz ölçümü, gözetmen bulundurma ile ergonomik duruş ve güvenli elle taşıma tekniklerini uygulamak.',
            'enerji izolasyonu ve elektriksel tehlikeler loto' => 'Bakım-onarım ve servis sırasında elektrik/mekanik enerjinin kesilmesi, kilitleme-etiketleme (EKED/LOTO), enerjisizlik kontrolü ve yalıtkan KKD kurallarını uygulamak.',
            'yuruyen merdiven ve bantlara ozgu riskler' => 'Yürüyen merdiven ve yürüyen bant bakım-onarımında alan bariyerleme, kuyu/makine dairesi güvenliği, sıkışma-kapılma önlemleri ve enerji kesme kurallarını uygulamak.',
            'kuyu ici ve kabin ustu guvenli calisma prensipleri' => 'Asansör kuyusu ve kabin üstünde revizyon kumandası, güvenlik boşluğu, düşme-sıkışma koruması, durdurma anahtarı ve haberleşme kurallarını uygulamak.',
        ];

        if (isset($sozluk[$norm])) {
            return $sozluk[$norm];
        }

        // Kısmi eşleşme (ör. "İnşaat Genel Tehlikeleri: Boşlukların tehlikeli olduğu..." veya "Boya/tiner buharına...")
        if (str_contains($norm, 'insaat genel tehlikeleri') || str_contains($norm, 'bosluklarin tehlikeli')) {
            return 'Şantiyede merdiven ve asansör boşlukları, tesisat/döşeme açıklıkları ve açık kat kenarlarındaki düşme tehlikesini kavratmak; korkuluk, kapak, güvenlik ağı ve koruyucuları sökmeme kurallarını uygulamak.';
        }
        if (str_contains($norm, 'boya') && str_contains($norm, 'tiner')) {
            return 'Boya, tiner ve solvent buharı maruziyetine karşı havalandırma ve uygun filtreli maske kullanımını, sıva/boya ekipmanları ile yüksekte çalışmada düşme önlemlerini uygulamak.';
        }
        if (str_contains($norm, 'taslama') || str_contains($norm, 'matkab') || str_contains($norm, 'matakab')) {
            return 'Taşlama makinesi, el matkabı ve elektrikli el aletlerinde koruyucu siperlik, uygun disk/uç seçimi, elektrik-kablo kontrolü, çapak sıçramasına karşı gözlük/siperlik ve güvenli çalışma kurallarını uygulamak.';
        }
        if (str_contains($norm, 'lazer kesim') || str_contains($norm, 'spiral kanal') || str_contains($norm, 'flash')) {
            return 'Flanş şekillendirme, lazer kesim ve spiral kanal makinelerinde koruyucu kapak/sensör, sac kenarı kesilme önlemleri, göz-el KKD kullanımı ve acil durdurma kurallarını uygulamak.';
        }

        // Genel otomatik hedef (her yeni özel konu için düzgün Türkçe cümle)
        $baslik = rtrim($temizKonu, '.');

        return $baslik.' kapsamındaki tehlike ve riskleri tanımak, alınacak toplu/kişisel korunma önlemleri ile güvenli çalışma kurallarını sahada uygulamak.';
    }
}
