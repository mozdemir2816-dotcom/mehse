<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\OrtamOlcumu;
use App\Models\RiskMaddesi;
use App\Models\SahaBulgusu;
use App\Models\TabelaTasarimi;
use Illuminate\Support\Str;

/**
 * Tabela motorunu risk değerlendirmesi ve saha bulgularından besler (30.10.2026): firmanın son risk
 * değerlendirmesindeki açık maddeler ile açık saha bulguları taranır; tehlike metnindeki anahtar sözcükler
 * ilgili ikaz/yasak/emredici levha şablonuna (piktogramlar, başlık, açıklama, sinyal rengi) eşlenir.
 * Aynı levha için birden çok kaynak tek öneride toplanır; sinyal sözcüğü en yüksek ciddiyete göre seçilir.
 */
class TabelaOnerisi
{
    /**
     * Öncelik sırasıyla kurallar: ilk eşleşen kazanır. Anahtar sözcükler ASCII, küçük harf.
     * tip: uyari (sarı üçgen ailesi, ciddiyete göre renk) | zorunlu (mavi) | yasak (kırmızı).
     *
     * @var array<string, array{etiket: string, tip: string, kelimeler: array<int, string>, slots: array<int, string>, baslik: string, aciklama: string}>
     */
    public const KURALLAR = [
        'yuksekte' => ['etiket' => 'Yüksekte çalışma / düşme', 'tip' => 'uyari', 'kelimeler' => ['yuksekte', 'dusme', 'iskele', 'cati', 'korkuluk', 'merdiven'],
            'slots' => ['warn_falling', 'cmd_harness', 'cmd_helmet'], 'baslik' => 'YÜKSEKTE ÇALIŞMA — DÜŞME TEHLİKESİ',
            'aciklama' => 'Yüksekte çalışmada emniyet kemeri ve baret kullanımı zorunludur. Korkuluksuz kenara yaklaşmayınız.'],
        'elektrik' => ['etiket' => 'Elektrik', 'tip' => 'uyari', 'kelimeler' => ['elektrik', 'gerilim', 'pano', 'kacak akim', 'topraklama', 'kablo'],
            'slots' => ['warn_electric', 'no_touch'], 'baslik' => 'ELEKTRİK TEHLİKESİ',
            'aciklama' => 'Panolara ve elektrikli ekipmana yetkisiz müdahale kesinlikle yasaktır. Yalnızca yetkili personel çalışabilir.'],
        'kapali_alan' => ['etiket' => 'Kapalı / dar alan', 'tip' => 'yasak', 'kelimeler' => ['kapali alan', 'dar alan', 'tank', 'kuyu', 'silo'],
            'slots' => ['no_entry', 'warn_general'], 'baslik' => 'KAPALI ALANA GİRİLMEZ',
            'aciklama' => 'İş izni ve gaz ölçümü olmadan kapalı/dar alana girmek yasaktır.'],
        'kaldirma' => ['etiket' => 'Kaldırma / vinç', 'tip' => 'uyari', 'kelimeler' => ['kaldirma', 'vinc', 'asili yuk', 'sapan', 'yuk dusme'],
            'slots' => ['warn_overhead_load', 'cmd_helmet'], 'baslik' => 'ASILI YÜK TEHLİKESİ',
            'aciklama' => 'Askıdaki yükün altında durmak ve geçmek yasaktır. Baret kullanınız.'],
        'forklift' => ['etiket' => 'Forklift / araç trafiği', 'tip' => 'uyari', 'kelimeler' => ['forklift', 'istif', 'arac trafigi', 'is makinesi', 'yaya'],
            'slots' => ['warn_forklift', 'no_pedestrian'], 'baslik' => 'FORKLİFT TRAFİĞİ',
            'aciklama' => 'Araç yolunda yürümeyiniz. Yaya yollarını ve belirlenen geçitleri kullanınız.'],
        'yangin' => ['etiket' => 'Yangın / yanıcı madde', 'tip' => 'yasak', 'kelimeler' => ['yangin', 'yanici', 'parlayici', 'alev', 'sicak is', 'kivilcim'],
            'slots' => ['warn_flammable', 'no_flame', 'no_smoking'], 'baslik' => 'YANICI MADDE — AÇIK ALEV VE SİGARA YASAKTIR',
            'aciklama' => 'Bu alanda sigara içmek ve açık alev kullanmak yasaktır.'],
        // Kimyasal / biyolojik kurallarından ÖNCE: "iyonize radyasyon", "MR" gibi metinler kimyasal sayılmasın.
        'radyasyon' => ['etiket' => 'Radyasyon', 'tip' => 'uyari', 'kelimeler' => ['radyasyon', 'iyonize', 'radyoaktif', 'rontgen', 'nukleer', 'x isini', 'tomografi'],
            'slots' => ['warn_radioactive', 'no_entry'], 'baslik' => 'RADYASYON TEHLİKESİ',
            'aciklama' => 'Yetkisiz girişler yasaktır. Dozimetre kullanınız, koruyucu önlükle çalışınız ve maruziyet süresini sınırlayınız.'],
        'manyetik' => ['etiket' => 'Manyetik alan', 'tip' => 'uyari', 'kelimeler' => ['manyetik', 'mrg', 'mr cihazi', 'manyetik rezonans'],
            'slots' => ['warn_magnetic', 'no_entry'], 'baslik' => 'GÜÇLÜ MANYETİK ALAN',
            'aciklama' => 'Kalp pili ve metal implantı olanlar ile metal eşya taşıyanlar giremez.'],
        'lazer' => ['etiket' => 'Lazer', 'tip' => 'uyari', 'kelimeler' => ['lazer'],
            'slots' => ['warn_laser', 'cmd_glasses'], 'baslik' => 'LAZER IŞINI',
            'aciklama' => 'Işına doğrudan bakmayınız. Koruyucu gözlük kullanınız.'],
        'soguk' => ['etiket' => 'Düşük sıcaklık', 'tip' => 'uyari', 'kelimeler' => ['soguk oda', 'soguk depo', 'dondurucu', 'soguk hava', 'dusuk sicaklik', 'kriyo'],
            'slots' => ['warn_low_temp', 'cmd_gloves'], 'baslik' => 'DÜŞÜK SICAKLIK',
            'aciklama' => 'Soğuk ortamda uygun iş kıyafeti ve eldiven kullanınız, çalışma süresini sınırlayınız.'],
        'kimyasal' => ['etiket' => 'Kimyasal', 'tip' => 'uyari', 'kelimeler' => ['kimyasal', 'asit', 'toksik', 'zehir', 'solvent', 'cozucu', 'boya'],
            'slots' => ['warn_toxic', 'cmd_gloves', 'cmd_glasses'], 'baslik' => 'KİMYASAL MADDE TEHLİKESİ',
            'aciklama' => 'Kimyasal ile çalışırken eldiven ve koruyucu gözlük kullanınız. Güvenlik Bilgi Formunu okuyunuz.'],
        'patlayici' => ['etiket' => 'Patlayıcı / basınçlı', 'tip' => 'uyari', 'kelimeler' => ['patlay', 'basincli', 'kompresor', 'lpg', 'tup '],
            'slots' => ['warn_explosive'], 'baslik' => 'PATLAYICI ORTAM / BASINÇLI EKİPMAN',
            'aciklama' => 'Kıvılcım ve ısı kaynaklarını uzak tutunuz. Yetkisiz müdahale yasaktır.'],
        'gurultu' => ['etiket' => 'Gürültü', 'tip' => 'zorunlu', 'kelimeler' => ['gurultu', 'isitme'],
            'slots' => ['cmd_ear'], 'baslik' => 'KULAKLIK TAKMAK ZORUNLUDUR',
            'aciklama' => 'Bu alanda gürültü düzeyi yüksektir. Kulak koruyucu kullanınız.'],
        'toz' => ['etiket' => 'Toz / solunum', 'tip' => 'zorunlu', 'kelimeler' => ['toz', 'solunum', 'silika', 'duman'],
            'slots' => ['cmd_mask'], 'baslik' => 'MASKE TAKMAK ZORUNLUDUR',
            'aciklama' => 'Tozlu ortamda solunum koruyucu kullanınız.'],
        'biyolojik' => ['etiket' => 'Biyolojik', 'tip' => 'uyari', 'kelimeler' => ['biyolojik', 'enfeksiyon', 'tibbi atik'],
            'slots' => ['warn_biological', 'cmd_gloves'], 'baslik' => 'BİYOLOJİK TEHLİKE',
            'aciklama' => 'Eldiven kullanınız, temas sonrası ellerinizi yıkayınız.'],
        'kazi' => ['etiket' => 'Kazı / hafriyat', 'tip' => 'uyari', 'kelimeler' => ['kazi', 'hafriyat', 'cukur'],
            'slots' => ['warn_general', 'no_pedestrian', 'cmd_helmet'], 'baslik' => 'KAZI ÇALIŞMASI',
            'aciklama' => 'Kazı alanına yetkisiz giriş yasaktır. Kenarlara yaklaşmayınız, baret kullanınız.'],
        'makine' => ['etiket' => 'Makine koruyucuları', 'tip' => 'uyari', 'kelimeler' => ['makine', 'sikisma', 'donen', 'pres', 'kesici', 'testere', 'torna', 'freze'],
            'slots' => ['warn_general', 'no_touch', 'cmd_gloves'], 'baslik' => 'ÇALIŞAN MAKİNE — SIKIŞMA / KESİLME TEHLİKESİ',
            'aciklama' => 'Koruyucular yerinde değilken makineyi çalıştırmayınız. Hareketli parçalara dokunmayınız.'],
        'kkd' => ['etiket' => 'KKD kullanımı', 'tip' => 'zorunlu', 'kelimeler' => ['baret', 'kkd', 'koruyucu donanim', 'kisisel koruyucu', 'ayakkabi', 'eldiven', 'gozluk'],
            'slots' => ['cmd_helmet', 'cmd_boots', 'cmd_gloves'], 'baslik' => 'KİŞİSEL KORUYUCU DONANIM ZORUNLUDUR',
            'aciklama' => 'Bu alanda baret, iş ayakkabısı ve eldiven kullanımı zorunludur.'],
    ];

    private const SEVIYE_SIRA = ['dusuk' => 0, 'orta' => 1, 'yuksek' => 2];

    /**
     * @return array<int, array{anahtar: string, etiket: string, tip: string, seviye: string, sayi: int, kaynaklar: array<int, string>, tasarim: array<string, mixed>}>
     */
    public static function firmaIcin(Firma $firma, int $limit = 20): array
    {
        $oneriler = [];

        foreach (self::kaynakKayitlari($firma) as $kayit) {
            $anahtar = self::kuralBul($kayit['metin']);

            if ($anahtar === null) {
                continue;
            }

            $o = $oneriler[$anahtar] ?? ['anahtar' => $anahtar, 'seviye' => 'dusuk', 'sayi' => 0, 'kaynaklar' => []];
            $o['sayi']++;
            $o['seviye'] = self::SEVIYE_SIRA[$kayit['seviye']] > self::SEVIYE_SIRA[$o['seviye']] ? $kayit['seviye'] : $o['seviye'];

            if (count($o['kaynaklar']) < 3) {
                $o['kaynaklar'][] = $kayit['kaynak'];
            }

            $oneriler[$anahtar] = $o;
        }

        $liste = array_map(fn (array $o) => [
            'anahtar' => $o['anahtar'],
            'etiket' => self::KURALLAR[$o['anahtar']]['etiket'],
            'tip' => self::KURALLAR[$o['anahtar']]['tip'],
            'seviye' => $o['seviye'],
            'sayi' => $o['sayi'],
            'kaynaklar' => $o['kaynaklar'],
            'tasarim' => self::tasarim($o['anahtar'], $o['seviye']),
        ], array_values($oneriler));

        usort($liste, fn (array $a, array $b) => [self::SEVIYE_SIRA[$b['seviye']], $b['sayi']] <=> [self::SEVIYE_SIRA[$a['seviye']], $a['sayi']]);

        return array_slice($liste, 0, $limit);
    }

    /** Metindeki anahtar sözcüklere göre ilk eşleşen kuralın anahtarı. */
    public static function kuralBul(string $metin): ?string
    {
        $m = ' '.self::sade($metin).' ';

        foreach (self::KURALLAR as $anahtar => $kural) {
            foreach ($kural['kelimeler'] as $k) {
                // "tup " gibi sonu boşluklu kelimeler sözcük sınırını da ister; diğerleri alt dize eşleşmesidir
                if (str_contains($m, $k)) {
                    return $anahtar;
                }
            }
        }

        return null;
    }

    /**
     * Kural + ciddiyetten tam editör tasarım durumu.
     *
     * @return array<string, mixed>
     */
    public static function tasarim(string $anahtar, string $seviye): array
    {
        $kural = self::KURALLAR[$anahtar];

        [$tr, $en, $bg, $yazi, $ucgen] = match ($kural['tip']) {
            'zorunlu' => ['ZORUNLU', 'MANDATORY', '#005596', '#ffffff', false],
            'yasak' => ['YASAK', 'PROHIBITED', '#D02C2F', '#ffffff', false],
            default => match ($seviye) {
                'yuksek' => ['TEHLİKE', 'DANGER', '#D02C2F', '#ffffff', true],
                'orta' => ['UYARI', 'WARNING', '#F96302', '#000000', true],
                default => ['DİKKAT', 'CAUTION', '#FFC82E', '#000000', true],
            },
        };

        $slotlar = array_slice($kural['slots'], 0, 4);

        return [
            'signalTr' => $tr,
            'signalEn' => $en,
            'headerBg' => $bg,
            'headerTextCol' => $yazi,
            'hasTriangle' => $ucgen,
            'picCount' => count($slotlar),
            'slots' => array_pad($slotlar, 4, null),
            'picPosition' => 'top',
            'picWrapper' => 'none',
            'mainTitle' => $kural['baslik'],
            'descText' => $kural['aciklama'],
            'specNote' => 'Risk değerlendirmesi / saha bulgusundan önerildi',
        ] + TabelaTasarimi::varsayilanTasarim();
    }

    /**
     * Kaynak kayıtları: son risk değerlendirmesinin açık maddeleri + açık saha bulguları.
     *
     * @return array<int, array{metin: string, seviye: string, kaynak: string}>
     */
    private static function kaynakKayitlari(Firma $firma): array
    {
        $kayitlar = [];
        $yuksekDuzeyler = config('isg.acik_risk_duzeyleri', ['Yüksek Risk', 'Çok Yüksek Risk', 'Önemli Risk', 'Esaslı Risk', 'Tolerans Gösterilemez Risk']);

        // Firmanın TÜM değerlendirmelerindeki açık maddeler (hastane gibi birim başına ayrı değerlendirmesi olan
        // firmalarda yalnız sonuncusuna bakmak çoğu tehlikeyi kaçırıyordu).
        $maddeler = RiskMaddesi::query()
            ->whereIn('risk_degerlendirmesi_id', $firma->riskDegerlendirmeleri()->select('id'))
            ->where('durum', '!=', 'kapali')
            ->get();

        foreach ($maddeler as $m) {
            /** @var RiskMaddesi $m */
            $duzey = (string) $m->duzey;

            $kayitlar[] = [
                'metin' => implode(' ', array_filter([$m->tehlike, $m->risk, $m->faaliyet, $m->bolum])),
                'seviye' => in_array($duzey, $yuksekDuzeyler, true) ? 'yuksek' : (str_contains(mb_strtolower($duzey), 'orta') ? 'orta' : 'dusuk'),
                'kaynak' => 'Risk: '.Str::limit((string) $m->tehlike, 60).($duzey !== '' ? ' ('.$duzey.')' : ''),
            ];
        }

        foreach (SahaBulgusu::query()->where('firma_id', $firma->id)->whereIn('durum', ['acik', 'devam_ediyor'])->get() as $b) {
            /** @var SahaBulgusu $b */
            $kayitlar[] = [
                'metin' => implode(' ', array_filter([$b->tehlike, $b->uygunsuzluk, $b->kategori, $b->bolum])),
                'seviye' => match ($b->oncelikAnahtari()) {
                    'kritik', 'yuksek' => 'yuksek',
                    'orta' => 'orta',
                    default => 'dusuk',
                },
                'kaynak' => 'Saha bulgusu '.($b->bulgu_no ?: '#'.$b->id).': '.Str::limit((string) $b->uygunsuzluk, 50),
            ];
        }

        // Ortam ölçümlerinde sınır değere yakın / aşım çıkan parametreler (gürültü → kulaklık, toz → maske ...).
        foreach (OrtamOlcumu::query()->where('firma_id', $firma->id)->get() as $o) {
            foreach ($o->olcumler ?? [] as $m) {
                $sonuc = (string) ($m['sonuc'] ?? '');

                if (! in_array($sonuc, ['asim', 'sinir'], true)) {
                    continue;
                }

                $kayitlar[] = [
                    'metin' => (string) ($m['parametre'] ?? ''),
                    'seviye' => $sonuc === 'asim' ? 'yuksek' : 'orta',
                    'kaynak' => 'Ölçüm: '.Str::limit((string) ($m['parametre'] ?? ''), 50).' ('.config('isg.ortam_olcum.sonuclar.'.$sonuc).')',
                ];
            }
        }

        return $kayitlar;
    }

    private static function sade(string $metin): string
    {
        return Str::of(Str::ascii(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $metin))))->toString();
    }
}
