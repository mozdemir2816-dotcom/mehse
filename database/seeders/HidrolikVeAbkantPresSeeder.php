<?php

namespace Database\Seeders;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Database\Seeder;

/**
 * Risk kütüphanesi: "Ekipman – Hidrolik pres (üretim)" (5 madde) ve "Ekipman – Abkant pres (sac büküm)"
 * (5 madde) — 01.11.2026, kullanıcı isteği. Anahtarlar config isg.risk_hizli_kurulum ile aynı olmalı
 * (hidrolik_pres kutusu ayrıca eski "11 hidrolik atölye presi" klasörünü de getirir).
 * Puan ölçeği ve `matris` (5x5) düzeni: EksantrikPresRiskSeeder ile aynı. Tekrar çalıştırılabilir.
 * Canlı için: deploy/canli-pres-kutuphanesi-01-11-2026.sql
 */
class HidrolikVeAbkantPresSeeder extends Seeder
{
    public const HIDROLIK = 'ekipman_hidrolik_pres';

    public const ABKANT = 'ekipman_abkant_pres';

    public function run(): void
    {
        $gruplar = [
            [self::HIDROLIK, 'Ekipman – Hidrolik pres (üretim)', 'Hidrolik Pres', 'HDP', self::hidrolik()],
            [self::ABKANT, 'Ekipman – Abkant pres (sac büküm)', 'Abkant Pres', 'ABK', self::abkant()],
        ];

        foreach ($gruplar as [$anahtar, $ad, $bolum, $onek, $maddeler]) {
            $kategori = TehlikeKategorisi::updateOrCreate(
                ['anahtar' => $anahtar],
                ['ad' => $ad, 'yontem' => null, 'sira' => 500],
            );

            foreach ($maddeler as $i => $m) {
                Tehlike::updateOrCreate(
                    ['kod' => sprintf('%s-%03d', $onek, $i + 1)],
                    ['tehlike_kategorisi_id' => $kategori->id, 'bolum' => $bolum] + $m,
                );
            }
        }
    }

    private static function madde(string $faaliyet, string $tehlike, string $risk, string $mevcut, string $oneri, string $mevzuat,
        array $fk, array $fkSon, array $x5, array $x5Son, string $sorumlu, string $termin): array
    {
        return [
            'faaliyet' => $faaliyet,
            'tehlike' => $tehlike,
            'risk' => $risk,
            'mevcut_onlem' => $mevcut,
            'oneri' => $oneri,
            'mevzuat' => $mevzuat,
            'olasilik' => $fk[0], 'frekans' => $fk[1], 'siddet' => $fk[2],
            'son_olasilik' => $fkSon[0], 'son_frekans' => $fkSon[1], 'son_siddet' => $fkSon[2],
            'matris' => ['olasilik' => $x5[0], 'siddet' => $x5[1], 'son_olasilik' => $x5Son[0], 'son_siddet' => $x5Son[1]],
            'sorumlu' => $sorumlu,
            'termin' => $termin,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function hidrolik(): array
    {
        $ortak = 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; Makine Emniyeti Yönetmeliği (2006/42/AT) Ek-I; TS EN ISO 16092-1; TS EN ISO 16092-3';

        return [
            self::madde('Pres operasyonu / bakım',
                'Yüksek basınçlı hidrolik hortum patlaması ve sıcak yağ enjeksiyonu',
                'Kök neden: Hortumlar ömrünü doldurmuş, sürtünme noktalarında aşınmış ve koruma kılıfı yok; kaçak kontrolü elle yapılıyor. Patlayan hortumdan çıkan yüksek basınçlı sıcak yağ cilde enjekte olabilir, göze gelebilir veya hortum kırbaç gibi savrulabilir → enjeksiyon yaralanması, yanık, göz yaralanması.',
                'Hortumlar gözle kontrol ediliyor; değişim periyodu ve koruma kılıfı yok.',
                'Operatör tarafına bakan hortumlara patlama koruma kılıfı (hortum çorabı) takılmalı, sürtünme noktaları spiral koruyucuyla korunmalı, savrulma riskine karşı hortum emniyet kelepçesi/zinciri kullanılmalı. Hortumlar üretim tarihine ve üretici ömrüne göre değiştirilmeli, değişim kaydı tutulmalı. Kaçak arama asla elle yapılmamalı (karton/kâğıt ile yapılmalı); bakım öncesi sistem basıncı boşaltılmalı. Yüz siperi ve uygun eldiven kullanılmalı.',
                $ortak.'; TS EN ISO 4413 (hidrolik sistemler)',
                [3, 6, 15], [0.5, 6, 15], [3, 4], [1, 4],
                'Bakım Şefi', '1 ay — periyodik değişim'),

            self::madde('Pres operasyonu',
                'Valf iç kaçağı nedeniyle üst koçun yerçekimiyle kontrolsüz kayması (inmesi)',
                'Kök neden: Yön/tutma valflerinde iç kaçak var; koç durdurulduğunda yerinde kalmıyor, yavaşça veya aniden aşağı kayıyor. Operatör veya ayarcı kalıp bölgesine elini koyduğunda koç kendiliğinden iner → ezilme, uzuv kaybı.',
                'Koç tutma valfi periyodik test edilmiyor; koç kayması gözlemleniyor ancak kayıt yok.',
                'Koç, yerçekimiyle inmeye karşı izlemeli (konumu denetlenen) tutma/emniyet valfleriyle donatılmalı; iç kaçak yapan valfler değiştirilmeli. Koç kayma testi (durdurulmuş koçun belirli sürede kayma miktarı) periyodik yapılıp kaydedilmeli. Kalıp bölgesine girilen tüm işlerde koç mekanik emniyet takozuyla desteklenmeli. Pres, yetkili kişilerce periyodik kontrolden geçirilmeli.',
                $ortak.'; TS EN ISO 13849-1',
                [3, 6, 40], [0.2, 6, 40], [3, 5], [1, 5],
                'Bakım Şefi', '1 ay'),

            self::madde('Kalıp değişimi / ayar',
                'Kalıp değişimi sırasında elektriksel kilitli (interlock) emniyet takozu bulunmaması',
                'Kök neden: Kalıp değişiminde koç yalnız hidrolik basınçla tutuluyor; mekanik emniyet takozu yok veya takoz takılıyken presin çalışmasını engelleyen kilitleme bulunmuyor. Hidrolik arıza ya da yanlışlıkla çalıştırmada koç düşer → ezilme, ölüm.',
                'Emniyet takozu yok / kilitlemesiz takoz kullanılıyor.',
                'Koç ağırlığını ve pres kuvvetini taşıyacak kapasitede emniyet takozu temin edilmeli; takoz yuvasından alındığında pres kumandasını kesen elektrik kilitlemeli emniyet svici (EN ISO 14119) bağlanmalı. Kalıp değişimi talimatına takoz kullanımı ve LOTO adımları eklenmeli, kalıp ayarcıları eğitilmeli.',
                $ortak.'; TS EN ISO 14119',
                [3, 3, 40], [0.2, 3, 40], [3, 5], [1, 5],
                'Bakım Şefi / Kalıp Ayarcısı', '1 ay'),

            self::madde('Pres operasyonu',
                'Arka ve yan bölgelerde sabit veya kilitli fiziki muhafaza bulunmaması',
                'Kök neden: Koruma yalnız ön tarafta (ışık bariyeri / çift el) var; arka ve yanlardan kalıp bölgesine erişim açık. Arka taraftan parça alan veya müdahale eden çalışan koç hareketinde kalıp bölgesine uzanabilir → ezilme.',
                'Ön tarafta ışık bariyeri var; arka ve yanlar açık.',
                'Arka ve yan erişim noktaları EN ISO 14120’ye uygun sabit (alet olmadan sökülemeyen) koruyucularla kapatılmalı; sık erişim gereken yerlerde kapı kullanılacaksa EN ISO 14119’a uygun kilitleme (interlock) ile kapı açıkken pres çalışmamalı. Koruyucu mesafeleri EN ISO 13857 (uzanma mesafeleri) ve EN ISO 13855’e göre kontrol edilmeli.',
                $ortak.'; TS EN ISO 14120; TS EN ISO 14119; TS EN ISO 13857',
                [3, 6, 40], [0.2, 6, 40], [3, 5], [1, 5],
                'Bakım Şefi / Üretim Sorumlusu', '1 ay'),

            self::madde('Pres operasyonu / bakım',
                'Hidrolik yağ tankı sıcaklık / seviye uyarı sistemlerinin arızası ve yangın riski',
                'Kök neden: Yağ sıcaklık ve seviye sensörleri devre dışı veya arızalı; soğutucu bakımsız. Aşırı ısınan yağ buharlaşır, kaçak yağ sıcak yüzeylere veya elektrik bileşenlerine temas eder → yangın, yanık, pompa hasarı ve ani basınç kaybıyla koç hareketi.',
                'Sıcaklık göstergesi var ancak alarm/otomatik durdurma çalışmıyor; tank çevresinde yağ birikintisi var.',
                'Yağ sıcaklığı ve seviye sensörleri alarm ve gerektiğinde otomatik durdurma verecek şekilde çalışır hâle getirilmeli; soğutucu ve filtreler bakım planına alınmalı. Kaçaklar giderilip tank çevresi temiz tutulmalı, yanıcı malzeme uzaklaştırılmalı. Pres yakınında uygun sınıfta yangın söndürücü bulundurulmalı; mümkünse yanmaya dayanıklı hidrolik yağ değerlendirilmeli.',
                $ortak.'; TS EN ISO 4413; Binaların Yangından Korunması Hakkında Yönetmelik',
                [1, 6, 40], [0.2, 6, 40], [2, 5], [1, 5],
                'Bakım Şefi', '1 ay'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function abkant(): array
    {
        $ortak = 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; Makine Emniyeti Yönetmeliği (2006/42/AT) Ek-I; TS EN 12622 (hidrolik abkant presler)';

        return [
            self::madde('Sac büküm operasyonu',
                'Lazer parmak koruma sisteminin (DSP / Akas / Lazersafe vb.) muting veya hizalama hatası ya da kapatılması',
                'Kök neden: Küçük ve çok kenarlı parçalarda lazer koruma "işi yavaşlatıyor" diye kapatılıyor veya bypass ediliyor; kalıp değişiminden sonra verici-alıcı hizası ayarlanmıyor, muting noktası yanlış. Operatör parmakları üst bıçak ile alt kalıp arasında kalır → parmak/el ezilmesi, kopması.',
                'Lazer koruma mevcut; hizalama ve günlük test yapılmıyor, devre dışı bırakma anahtarı serbest erişimli.',
                'Lazer koruma sistemi kapatılamayacak şekilde (anahtar yetkili kişide) korunmalı; her kalıp değişiminden sonra hizalama ve vardiya başı test parçasıyla fonksiyon testi yapılıp forma işlenmeli. Muting (korumanın susturulması) yalnız EN 12622’nin izin verdiği küçük kalıp açıklığına inildikten sonra devreye girmeli ve bu aşamada kapanma hızı standardın sınırını aşmamalı. Koruma kullanılamayan özel bükümlerde düşük hız ve çift el kumandası gibi standartta tanımlı alternatif yöntemler uygulanmalı.',
                $ortak.'; TS EN ISO 13855; TS EN 61496',
                [6, 6, 40], [0.2, 6, 40], [4, 5], [1, 5],
                'Üretim Sorumlusu / Bakım Şefi', 'Derhal — vardiya başı test sürekli'),

            self::madde('Sac büküm operasyonu',
                'Büküm sırasında sacın hızla yukarı fırlaması (whip-up) ve operatöre çarpması',
                'Kök neden: Büyük ve ağır saclar büküm sırasında operatör tarafına doğru hızla kalkıyor; sac elle destekleniyor ve operatör savrulma alanında duruyor. Sac kenarı yüze, göğse veya kollara çarpar; elle tutulan sac eli kalıba doğru çekebilir → kesik, çarpma, ezilme.',
                'Büyük saclar iki çalışan tarafından elle tutularak büküluyor; destek kolu yok.',
                'Büyük saclar için sac takip eden büküm destek kolları (ön destek / bükme takipçisi) kullanılmalı. Operatörlerin sacın savrulma yayının dışında durması talimatla belirlenmeli ve işaretlenmeli. Ağır saclar kaldırma yardımcı ekipmanıyla beslenmeli; kesilmeye dirençli eldiven ve uygun iş kıyafeti kullanılmalı.',
                $ortak,
                [3, 6, 15], [0.5, 6, 15], [3, 4], [1, 4],
                'Üretim Sorumlusu / Operatör', '1 ay'),

            self::madde('Bıçak / kalıp değişimi',
                'Bıçak ve kalıp değişiminde ayak pedalı kilidinin olmaması ve LOTO eksikliği',
                'Kök neden: Bıçak ve alt kalıp değiştirilirken makine yalnız beklemeye alınıyor; ayak pedalı yerde ve kilitsiz, ana şalter kilitlenmiyor. Pedala istem dışı basılması veya başka bir çalışanın makineyi çalıştırması → el ve kol ezilmesi.',
                'Yazılı LOTO talimatı ve pedal kilidi yok.',
                'Abkanta özel yazılı LOTO talimatı hazırlanmalı: koçu alt konuma indir veya emniyetle destekle → ana şalteri kapatıp kişisel asma kilitle kilitle → etiket as → hidrolik basıncı boşalt → çalıştırmayı deneyerek enerjisizliği doğrula. Ayak pedalı kapaklı (muhafazalı) olmalı ve kalıp değişiminde devre dışı bırakılmalı (anahtarlı mod seçici). Talimat makineye asılmalı ve eğitim kaydı tutulmalı.',
                $ortak.'; Güvenlik ve Sağlık İşaretleri Yönetmeliği',
                [3, 3, 40], [0.5, 3, 40], [3, 5], [1, 5],
                'Bakım Şefi / İSG Uzmanı', '1 hafta — sürekli uygulama'),

            self::madde('Sac büküm operasyonu / ayar',
                'Arka dayama (backgauge) eksenlerinin hızlı hareketinde sıkışma riski',
                'Kök neden: Arka dayama eksenleri programla hızlı konumlanıyor; makinenin arkasına geçen çalışan veya parçayı arkadan alan operatör dayama ile gövde arasında sıkışabiliyor. Arka bölge açık ve uyarı yok → el, kol veya gövde sıkışması.',
                'Makinenin arkası açık; arka bölgeye giriş kısıtlanmamış.',
                'Makinenin arka bölgesi EN ISO 14120’ye uygun sabit koruyucularla veya kilitli kapı / ışık perdesi ile korunmalı; arka bölgeye girişte dayama hareketi durmalı. Ayar ve parça alma işleri ön taraftan yapılacak şekilde düzenlenmeli; arka bölge zemin işaretlemesi ve uyarı levhasıyla belirtilmeli.',
                $ortak.'; TS EN ISO 14120; TS EN ISO 14119',
                [3, 6, 7], [1, 6, 7], [3, 3], [1, 3],
                'Bakım Şefi', '1 ay'),

            self::madde('Kalıp seçimi / sac büküm',
                'Yanlış V kanal seçimi nedeniyle aşırı tonajda kalıp kırılması ve parça fırlaması',
                'Kök neden: Sac kalınlığına göre çok dar V kanal seçiliyor veya tonaj tablosuna bakılmıyor; kalıp ve bıçak kapasitesinin üstünde yükleniyor. Kalıp çatlar veya kırılır, parça ya da kalıp parçası fırlar → çarpma, göz ve yüz yaralanması; makine hasarı.',
                'Tonaj tablosu makinede yok; V kanal seçimi operatörün tecrübesine bırakılmış.',
                'Her makineye kalıp üreticisinin tonaj (kuvvet) tablosu asılmalı; V kanal açıklığı sac kalınlığı ve malzemesine göre tablodan seçilmeli, kalıp ve bıçak üzerinde yazan azami yük aşılmamalı. Makine kuvvet sınırlaması doğru ayarlanmalı; çatlak veya hasarlı kalıplar kullanımdan kaldırılmalı. Operatörlere kalıp seçimi eğitimi verilmeli, koruyucu gözlük kullanılmalı.',
                $ortak,
                [3, 3, 15], [0.5, 3, 15], [3, 4], [1, 4],
                'Kalıp Ayarcısı / Operatör', '1 ay'),
        ];
    }
}
