<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Risk Analizi Prosedürü — Risk Değerlendirmesi PDF'inde Kapak'tan sonra,
 * Form'dan önce basılan yöntem prosedürü (AMAÇ/KAPSAM/TANIMLAR/UYGULAMA/
 * DÖKÜMANTASYON). Kullanıcı hiç yüklememişse "Matris" ve "Fine-Kinney"
 * yöntemleri için gerçek referans belgelerden (kullanıcının paylaştığı
 * RİSK ANALİZİ klasörü) alınan metinle başlar; "Prosedür Yükle" ile kendi
 * .docx'ini yükleyip bir yöntem için değiştirebilir veya yeni bir yöntem
 * tanımlayabilir.
 */
class RiskProsedur extends Model
{
    protected $table = 'risk_prosedurleri';

    protected $guarded = ['id'];

    protected $casts = [
        'icerik' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function aktifIcin(int $userId, string $yontem): ?self
    {
        static::varsayilanlariSeedEt($userId);

        return static::where('user_id', $userId)->where('yontem', $yontem)->first();
    }

    /** Kullanıcının hiç prosedürü yoksa Matris + Fine-Kinney için gerçek metinle başlatır. */
    public static function varsayilanlariSeedEt(int $userId): void
    {
        if (static::where('user_id', $userId)->exists()) {
            return;
        }

        foreach (static::VARSAYILANLAR as $yontem => $veri) {
            static::create([
                'user_id' => $userId,
                'yontem' => $yontem,
                'ad' => $veri['ad'],
                'icerik' => $veri['icerik'],
            ]);
        }
    }

    private const VARSAYILANLAR = [
        'matris_5x5' => [
            'ad' => 'Risk Analizi Prosedürü (Matris Yöntemi)',
            'icerik' => [
                ['tip' => 'baslik', 'metin' => 'AMAÇ'],
                ['tip' => 'paragraf', 'metin' => 'Bu prosedürün temel amacı işyerindeki çalışma koşullarından kaynaklanan iş kazaları ve meslek hastalıklarını oluşturan nedenler ve bunları etkileyen faktörleri, bu faktörlere maruz kalınma sürelerini dikkate alarak mümkün olan en geçerli ve doğru bilgiyi toplayarak görünmeyen tehlikelerin ortaya çıkmasını engellemek için etkili bir güvenlik ağı kurmaktır.'],
                ['tip' => 'baslik', 'metin' => 'KAPSAM'],
                ['tip' => 'paragraf', 'metin' => 'Kalite Yönetim Sistemi (KYS) kapsamında belirlenen prosesler için risklerin değerlendirilmesi faaliyetlerini kapsar.'],
                ['tip' => 'baslik', 'metin' => 'TANIMLAR'],
                ['tip' => 'paragraf', 'metin' => "OLAY: Kazaya neden olan veya olma potansiyeli olan durum.\nKAZA: Ölüme, hastalığa, hasara, zarara ya da diğer kayıplara yol açan istenmeyen olay.\nTEHLİKE: Çalışma ortam ve şartlarında var olan ya da dışarıdan gelebilecek, kapsamı belirlenmemiş, maruz kimselere, işyerine ve çevreye zarar ya da hasar verme potansiyeli.\nRİSK: Tehlikelerden kaynaklanan bir olayın, meydana gelme ihtimali ile zarar verme derecesinin bileşkesidir. Risk = Zararın ortaya çıkma olasılığı × Zararın ortaya çıktığı anda yapması muhtemel etki.\nKABUL EDİLEBİLİR RİSK: Kuruluşun yasal zorunluluklara ve kendi İSG politikasına göre tahammül edebileceği düzeye indirilmiş risk.\nRİSK DEĞERLENDİRME: Tehlike potansiyeli bulunan maddelerle ilgili her türlü bilimsel bilgi ve malumatın düzenlenmesi ve analiz edilmesine yönelik sistematik bir yaklaşımdır.\nMARUZ KALANLAR: Çeşitli faaliyetler sonucu açığa çıkan tehlikelerle karşılaşan ve bu tehlikelerden etkilenme potansiyeli bulunan operatör/operatörler, işçi/işçiler, ziyaretçi/ziyaretçiler vb.\nOLASILIK (O): Değerlendirmesi yapılan faaliyete ilişkin tehlikenin gerçekleşme ihtimali.\nŞİDDET (Ş): Değerlendirmesi yapılan faaliyete ilişkin tehlikenin gerçekleşmesi halinde zarar ve kayıp verme potansiyeli.\nRİSK PUANI (RP): Olasılık ile şiddet değerlerinin çarpımı ile bulunan puan."],
                ['tip' => 'baslik', 'metin' => 'RİSK DEĞERLENDİRMESİNİN VE YÖNETİMİNİN YARARLARI'],
                ['tip' => 'paragraf', 'metin' => "İşyerinde yazılı prosedür ve politikaların oluşmasını ya da olgunlaşmasını sağlar.\nÇalışanların ve işyeri yönetiminin iş sağlığı ve güvenliği konularında bilgi sahibi olmalarını ve karar verme kabiliyetini geliştirir.\nİşyerinde olası tehlikeler ve alınacak tedbirler belirlenir; risklerin büyüklüğü hesaplanır ve tolere edilebilir olup olmadığına karar verilir.\nYasal yükümlülükler ve İSG politikası çerçevesinde tahammül edilebilir düzeye indirilmiş risk ile çalışılmasını sağlar.\nGerekli düzeltici ve önleyici faaliyetlerin gerçekleştirilmesini sağlayacak verilerin kaydedilmesini, sonuçların izlenmesini ve ölçülmesini sağlar."],
                ['tip' => 'baslik', 'metin' => 'YETKİ VE SORUMLULUKLAR'],
                ['tip' => 'paragraf', 'metin' => 'İşyerinde İş Sağlığı ve Güvenliği Risk Yönetimini etkileyen işlerle uğraşan personelin yetkileri, sorumlulukları ve otoriteleri ile karşılıklı ilişkileri tanımlanmalı ve dokümanlaştırılmalıdır. Bu kapsamda; riskin zararlı etkilerini azaltan veya önleyen eylemleri başlatanlar, risk seviyesi kabul edilir sınıra gelene kadar risk davranışı usulünü kontrol edenler, risk yönetimi ile ilgili problemleri anlayıp kaydedenler, belirlenen kanallar yoluyla çözümleri sağlayan/tavsiye eden/başlatanlar, çözümlerin uygulanmasını tasdik edenler ve uygun olduğunda dâhili/harici danışma ve iletişimde bulunanlar görevlidir.'],
                ['tip' => 'baslik', 'metin' => 'UYGULAMA'],
                ['tip' => 'paragraf', 'metin' => "Bu prosedürde tanımlanan risk değerlendirme çalışmaları Matris Yöntemi kullanılarak yapılmaktadır. Tabloda \"Olasılık\" zararın ortaya çıkma olasılığını, \"Şiddet\" zararın ortaya çıktığı anda yapması muhtemel etkiyi, \"Risk Puanı\" ise \"Olasılık\" ve \"Şiddet\" sütunundaki rakamların çarpımı sonucu bulunan 1 ile 25 arasındaki puanı tanımlar. Risk puanına göre risk düzeyi \"KABUL EDİLEMEZ\" veya \"ÖNEMLİ\" olarak belirlenen faaliyetler Çevre ve İSG Yönetim Programı ve Aksiyon Planı'na alınıp, yapılacak iyileştirmelerle ve alınacak tedbirlerle bu faaliyetlerin risk puanı ve risk düzeyi düşürülür."],
                ['tip' => 'baslik', 'metin' => 'RİSK DEĞERLENDİRMESİNİN YENİLENMESİ GEREKEN DURUMLAR'],
                ['tip' => 'paragraf', 'metin' => 'Yapılmış olan risk değerlendirmesi; tehlike sınıfına göre çok tehlikeli, tehlikeli ve az tehlikeli işyerlerinde sırasıyla en geç iki, dört ve altı yılda bir yenilenir. Ayrıca aşağıdaki durumlarda ortaya çıkabilecek yeni risklerin işyerinin tamamını veya bir bölümünü etkiliyor olması göz önünde bulundurularak risk değerlendirmesi tamamen veya kısmen yenilenir: işyerinin taşınması veya binalarda değişiklik yapılması; uygulanan teknoloji, madde ve ekipmanlarda değişiklik; üretim yönteminde değişiklik; iş kazası, meslek hastalığı veya ramak kala olay meydana gelmesi; çalışma ortamına ait sınır değerlere ilişkin mevzuat değişikliği; çalışma ortamı ölçümü ve sağlık gözetim sonuçlarına göre gerekli görülmesi; işyeri dışından kaynaklanan yeni bir tehlikenin ortaya çıkması.'],
                ['tip' => 'baslik', 'metin' => 'RİSK DEĞERLENDİRMESİ ÇALIŞMALARINI YÜRÜTECEKLER'],
                ['tip' => 'paragraf', 'metin' => 'Risk değerlendirmesi, işverenin oluşturduğu bir ekip tarafından gerçekleştirilir: işveren veya işveren vekili; işyerinde sağlık ve güvenlik hizmetini yürüten iş güvenliği uzmanları ile işyeri hekimleri; işyerindeki çalışan temsilcileri; işyerindeki destek elemanları; işyerindeki bütün birimleri temsil edecek şekilde belirlenen ve mevcut/muhtemel tehlike kaynakları ile riskler konusunda bilgi sahibi çalışanlar. İhtiyaç duyulduğunda ekibe destek olmak üzere işyeri dışındaki kişi ve kuruluşlardan danışmanlık hizmeti alınabilir.'],
                ['tip' => 'baslik', 'metin' => 'DÖKÜMANTASYON'],
                ['tip' => 'paragraf', 'metin' => 'Risk değerlendirme çalışmalarının tamamlanmasının ve ilgili dokümanların hazırlanmasının ardından, gerçekleştiren kişiler tarafından her sayfası paraflanıp son sayfası imzalanır ve işyerinde saklanır.'],
            ],
        ],
        'fine_kinney' => [
            'ad' => 'Risk Analizi Prosedürü (Fine-Kinney Yöntemi)',
            'icerik' => [
                ['tip' => 'baslik', 'metin' => '1. AMAÇ'],
                ['tip' => 'paragraf', 'metin' => 'Bu prosedürün amacı; işyerinin faaliyetleri sırasında oluşabilecek potansiyel tehlikelerin ve bunlara ilişkin risklerin belirlenmesini, böylelikle beklenen veya olası risklerin kontrol altına alınmasına ilişkin yöntem ve esasların sistematik bir şekilde tanımlanmasını sağlayarak iş kazaları ve meslek hastalıklarının asgari seviyelere indirilmesini gerçekleştirebilmektir.'],
                ['tip' => 'baslik', 'metin' => '2. KAPSAM'],
                ['tip' => 'paragraf', 'metin' => 'İşyerinde yürütülen tüm faaliyetlerde mevcut / olası iş sağlığı ve güvenliği risklerinin belirlenmesini, değerlendirilmesini ve önlenmesi için bir iş sağlığı ve güvenliği planı oluşturulmasını kapsar.'],
                ['tip' => 'baslik', 'metin' => '3. SORUMLULUK VE YETKİ'],
                ['tip' => 'paragraf', 'metin' => 'Bu prosedürün uygulanmasından işyerinde çalışan tüm personel sorumludur. Başlangıçta Risk Analizi Ekibi tarafından hazırlanan risk analizi çalışmaları, sürecin devamında işveren yetkilileri tarafından sürekli olarak izlenecek ve iyileştirilecektir.'],
                ['tip' => 'baslik', 'metin' => '4. TANIMLAR VE KISALTMALAR'],
                ['tip' => 'paragraf', 'metin' => "Kanun: 30/06/2012 tarihli ve 6331 sayılı İş Sağlığı ve Güvenliği Kanunu.\nYönetmelik: 29/12/2012 tarihli ve 28512 sayılı İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği.\nTehlike: İşyerinde var olan ya da dışarıdan gelebilecek, çalışanı veya işyerini etkileyebilecek zarar veya hasar verme potansiyeli.\nRisk: Tehlikeden kaynaklanacak kayıp, yaralanma ya da başka zararlı sonuç meydana gelme ihtimali.\nKabul edilebilir risk seviyesi: Yasal yükümlülüklere ve işyerinin önleme politikasına uygun, kayıp veya yaralanma oluşturmayacak risk seviyesi.\nÖnleme: İSG risklerini ortadan kaldırmak veya azaltmak için planlanan ve alınan tedbirlerin tümü.\nRamak kala olay: İşyerinde meydana gelen; çalışan, işyeri ya da iş ekipmanını zarara uğratma potansiyeli olduğu hâlde zarara uğratmayan olay.\nOlasılık: Tehlikenin, maddi ve manevi zarar verme ihtimali.\nŞiddet: Kazanın gerçekleşmesi durumunda oluşacak zararın büyüklüğü.\nFrekans: Tanımlanan faaliyetin gerçekleşme periyodu.\nRisk Değerlendirme Sonucu (RDS): Olasılık × Şiddet × Frekans değeri.\nRisk Derecelendirilmesi: Ortaya çıkan RDS sonucu tehlikelerin önem sırası."],
                ['tip' => 'baslik', 'metin' => '5. PROSEDÜR AKIŞI'],
                ['tip' => 'baslik', 'metin' => '5.1 Risk Değerlendirmesi'],
                ['tip' => 'paragraf', 'metin' => 'Tüm işyerleri için tasarım veya kuruluş aşamasından başlamak üzere tehlikeleri tanımlama, riskleri belirleme ve analiz etme, risk kontrol tedbirlerinin kararlaştırılması, dokümantasyon, yapılan çalışmaların güncellenmesi ve gerektiğinde yenileme aşamaları izlenerek gerçekleştirilir. Risk değerlendirmesinin gerçekleştirilmesinde "Kinney Metodu" uygulanır.'],
                ['tip' => 'baslik', 'metin' => '5.2 Risk Değerlendirmesi Ekibi'],
                ['tip' => 'paragraf', 'metin' => 'Risk değerlendirmesi, işverenin oluşturduğu bir ekip tarafından gerçekleştirilir: işveren veya işveren vekili; işyerinde sağlık ve güvenlik hizmetini yürüten iş güvenliği uzmanları ile işyeri hekimleri; işyerindeki çalışan temsilcileri; işyerindeki destek elemanları; işyerindeki bütün birimleri temsil edecek şekilde belirlenen ve mevcut/muhtemel tehlike kaynakları ile riskler konusunda bilgi sahibi çalışanlar. İşveren, ihtiyaç duyulduğunda bu ekibe destek olmak üzere işyeri dışındaki kişi ve kuruluşlardan hizmet alabilir; koordinasyon işveren veya ekip içinden görevlendirilen bir kişi tarafından sağlanabilir.'],
                ['tip' => 'baslik', 'metin' => '5.3 Tehlikelerin Belirlenmesi'],
                ['tip' => 'paragraf', 'metin' => 'Tehlikeler tanımlanırken çalışma ortamı, çalışanlar ve işyerine ilişkin asgari olarak şu bilgiler toplanır: işyeri bina ve eklentileri; yürütülen faaliyetler ile iş ve işlemler; üretim süreç ve teknikleri; iş ekipmanları; kullanılan maddeler; artık ve atıklarla ilgili işlemler; organizasyon ve hiyerarşik yapı; çalışanların tecrübe ve düşünceleri; çalışma izin belgeleri; çalışanların eğitim/yaş/cinsiyet ve sağlık gözetimi kayıtları; özel politika gerektiren gruplar; teftiş sonuçları; meslek hastalığı ve iş kazası kayıtları; ramak kala olay kayıtları; malzeme güvenlik bilgi formları; ortam ve kişisel maruziyet ölçüm sonuçları; varsa önceki risk değerlendirmeleri; acil durum planları.'],
                ['tip' => 'baslik', 'metin' => '5.4 Risk Değerlendirmesinin Yenilenmesi'],
                ['tip' => 'paragraf', 'metin' => 'Yapılmış olan risk değerlendirmesi; tehlike sınıfına göre çok tehlikeli, tehlikeli ve az tehlikeli işyerlerinde sırasıyla en geç iki, dört ve altı yılda bir yenilenir. Ayrıca işyerinin taşınması/bina değişikliği, teknoloji/madde/ekipman değişikliği, üretim yöntemi değişikliği, iş kazası/meslek hastalığı/ramak kala olay, mevzuat değişikliği, ölçüm ve sağlık gözetimi sonuçları veya işyeri dışından kaynaklanan yeni bir tehlikenin ortaya çıkması hâllerinde tamamen veya kısmen yenilenir.'],
                ['tip' => 'baslik', 'metin' => '5.5 Risklerin Belirlenmesi ve Analizi'],
                ['tip' => 'paragraf', 'metin' => 'Tespit edilen tehlikelerin her biri ayrı ayrı dikkate alınarak, hangi sıklıkta oluşabileceği ile kimlerin/nelerin ne şekilde ve hangi şiddette zarar görebileceği belirlenir; mevcut kontrol tedbirlerinin etkisi de göz önünde bulundurulur. Analiz edilen riskler, kontrol tedbirlerine karar verilmek üzere etkilerinin büyüklüğüne ve önemine göre en yüksek risk seviyesinden başlanarak sıralanır ve yazılı hâle getirilir. Risk değerlendirmesinde "Olasılık", "Frekans" ve "Şiddet" parametreleri için puanlama cetveli doğrultusunda puanlama yapılır (Risk Değerlendirme Sonucu = O × F × Ş).'],
            ],
        ],
        'hazop' => [
            'ad' => 'Risk Analizi Prosedürü (HAZOP Yöntemi)',
            'icerik' => [
                ['tip' => 'baslik', 'metin' => '1. AMAÇ'],
                ['tip' => 'paragraf', 'metin' => 'Bu prosedürün amacı; proses/tesis içeren işyerlerinde tasarım, işletme veya bakım kaynaklı sapmaların (deviation) sistematik olarak incelenmesi yoluyla, sürecin normal işleyişinden her türlü olası sapmanın güvenlik, çevre ve işletme üzerindeki etkilerini önceden tespit ederek gerekli önleyici/koruyucu tedbirlerin belirlenmesini sağlamaktır.'],
                ['tip' => 'baslik', 'metin' => '2. KAPSAM'],
                ['tip' => 'paragraf', 'metin' => 'Bu prosedür; boru hatları, tanklar, reaktörler, ısı eşanjörleri, pompalar ve benzeri proses ekipmanı içeren tesislerin/prosesin tamamını veya incelenmek üzere ayrılan "düğüm" (node) olarak adlandırılan alt bölümlerini kapsar. Kimyasal, petrokimya, gıda, ilaç ve benzeri sürekli/kesikli proses tipi işletmelerde uygulanır.'],
                ['tip' => 'baslik', 'metin' => '3. TANIMLAR'],
                ['tip' => 'paragraf', 'metin' => "HAZOP (Hazard and Operability Study): Tehlike ve İşletilebilirlik Çalışması — bir prosesin tasarım amacından sapmalarını sistematik kılavuz kelimelerle inceleyen risk analizi yöntemi.\nDüğüm (Node): Proses akış şemasında incelemenin yapıldığı, başlangıç ve bitiş noktaları belirli olan boru/ekipman bölümü.\nProses Parametresi: Akış, basınç, sıcaklık, seviye, kompozisyon, reaksiyon, karıştırma gibi düğümde izlenen değişken.\nKılavuz Kelime (Guide Word): Sapmayı tanımlamak için parametreyle birlikte kullanılan standart terim — YOK/DEĞİL (No/Not), DAHA FAZLA (More), DAHA AZ (Less), TERSİNE (Reverse), BUNUNLA BİRLİKTE (As Well As), YERİNE (Part Of), BAŞKA (Other Than), ERKEN/GEÇ (Early/Late).\nSapma (Deviation): Kılavuz kelime ile parametrenin birleşiminden oluşan, tasarım amacından ayrılma durumu (örn. \"DAHA FAZLA Basınç\").\nNeden (Cause): Sapmaya yol açabilecek ekipman arızası, insan hatası veya dış etken.\nSonuç (Consequence): Sapmanın gerçekleşmesi hâlinde ortaya çıkabilecek güvenlik, sağlık, çevre veya işletme kaybı.\nKoruma (Safeguard): Sapmayı önleyen, algılayan veya sonucunu hafifleten mevcut tedbir (alarm, valf, prosedür, eğitim vb.)."],
                ['tip' => 'baslik', 'metin' => '4. YETKİ VE SORUMLULUKLAR'],
                ['tip' => 'paragraf', 'metin' => 'HAZOP çalışması, konusunda deneyimli bir HAZOP lideri (kolaylaştırıcı) eşliğinde çok disiplinli bir ekip tarafından yürütülür: işveren veya işveren vekili; işyerinde sağlık ve güvenlik hizmetini yürüten iş güvenliği uzmanları ile işyeri hekimleri; proses/tasarım mühendisi; işletme ve bakım personeli; işyerindeki çalışan temsilcileri; işyerindeki destek elemanları; mevcut/muhtemel tehlike kaynakları ile riskler konusunda bilgi sahibi çalışanlar. Kayıt tutucu (scribe) her düğüm için görüşülen sapma, neden, sonuç, koruma ve önerileri standart HAZOP kayıt tablosuna işler.'],
                ['tip' => 'baslik', 'metin' => '5. UYGULAMA'],
                ['tip' => 'baslik', 'metin' => '5.1 Hazırlık'],
                ['tip' => 'paragraf', 'metin' => 'Proses akış şeması (PFD) ve boru-enstrüman şeması (P&ID) temin edilir; inceleme kapsamındaki sistem, incelemeyi yönetilebilir kılacak şekilde düğümlere ayrılır. Her düğüm için tasarım amacı (intention) net biçimde tanımlanır.'],
                ['tip' => 'baslik', 'metin' => '5.2 Kılavuz Kelime Uygulaması'],
                ['tip' => 'paragraf', 'metin' => 'Her düğümde, ilgili proses parametreleri (akış, basınç, sıcaklık, seviye, kompozisyon vb.) sırasıyla ele alınır; her parametre için tüm kılavuz kelimeler ("YOK", "DAHA FAZLA", "DAHA AZ", "TERSİNE", "BUNUNLA BİRLİKTE", "YERİNE", "BAŞKA", "ERKEN/GEÇ") sistematik olarak uygulanarak anlamlı sapmalar türetilir (örn. "DAHA FAZLA Sıcaklık", "YOK Akış").'],
                ['tip' => 'baslik', 'metin' => '5.3 Neden-Sonuç-Koruma Analizi'],
                ['tip' => 'paragraf', 'metin' => 'Türetilen her sapma için olası nedenler, bu nedenlerin yol açabileceği sonuçlar (güvenlik/çevre/işletme) ve mevcut korumalar ekip tarafından tartışılıp kayıt altına alınır. Mevcut korumaların yeterliliği değerlendirilir; yetersiz görülen durumlar için risk düzeyi nitel olarak (Düşük/Orta/Yüksek/Çok Yüksek) sınıflandırılır ve ek öneri/aksiyon belirlenir.'],
                ['tip' => 'baslik', 'metin' => '5.4 Öneri ve Aksiyon Takibi'],
                ['tip' => 'paragraf', 'metin' => 'Her öneri için sorumlu kişi/birim ve hedef tamamlanma tarihi atanır; aksiyonlar kapatılıncaya kadar İSG yönetim sisteminde (DÖF/aksiyon takip listesi) izlenir.'],
                ['tip' => 'baslik', 'metin' => '6. RİSK DEĞERLENDİRMESİNİN YENİLENMESİ GEREKEN DURUMLAR'],
                ['tip' => 'paragraf', 'metin' => 'Yapılmış olan HAZOP çalışması; proseste, ekipmanda, kullanılan maddelerde veya işletme koşullarında (proses değişikliği, revamp, yeni ekipman devreye alma) herhangi bir değişiklik yapılması, proseste meydana gelen kaza/ramak kala olayı, mevzuat değişikliği veya işveren/uzman tarafından gerekli görülen diğer hâllerde tamamen veya kısmen yenilenir. Değişiklik yapılmayan proseslerde periyodik olarak (işletme politikasına göre genellikle 5 yılda bir) gözden geçirilmesi önerilir.'],
                ['tip' => 'baslik', 'metin' => '7. DÖKÜMANTASYON'],
                ['tip' => 'paragraf', 'metin' => 'HAZOP çalışma kayıtları (düğüm/parametre/kılavuz kelime/sapma/neden/sonuç/koruma/risk düzeyi/öneri/sorumlu/termin sütunlarını içeren tablo) her düğüm için ayrı ayrı, oturum tarihi ve katılımcı listesiyle birlikte belgelenir; çalışmanın tamamlanmasının ardından katılımcılar tarafından imzalanıp işyerinde saklanır.'],
            ],
        ],
        'fmea' => [
            'ad' => 'Risk Analizi Prosedürü (FMEA Yöntemi)',
            'icerik' => [
                ['tip' => 'baslik', 'metin' => '1. AMAÇ'],
                ['tip' => 'paragraf', 'metin' => 'Bu prosedürün amacı; bir ürün, ekipman veya sürecin olası hata türlerini (failure mode), bu hataların etkilerini ve köklerindeki nedenleri sistematik olarak belirleyip, hataların şiddetini, ortaya çıkma olasılığını ve fark edilebilirliğini değerlendirerek en kritik hatalara öncelik verilmesini ve önleyici tedbirlerin planlanmasını sağlamaktır.'],
                ['tip' => 'baslik', 'metin' => '2. KAPSAM'],
                ['tip' => 'paragraf', 'metin' => 'Bu prosedür; makine, ekipman, iş ekipmanı bakım süreçleri, üretim/proses adımları veya tasarım aşamasındaki sistemlerin, olası arıza/hata modlarının iş sağlığı ve güvenliği, ürün kalitesi ve işletme sürekliliği açısından değerlendirilmesi gereken tüm faaliyetlerini kapsar.'],
                ['tip' => 'baslik', 'metin' => '3. TANIMLAR'],
                ['tip' => 'paragraf', 'metin' => "FMEA (Failure Mode and Effects Analysis): Hata Türü ve Etkileri Analizi — bir sistemin olası hata modlarını, etkilerini ve nedenlerini sistematik olarak değerlendiren risk analizi yöntemi.\nHata Türü (Failure Mode): Bir bileşen, ekipman veya işlemin tasarım/işletme amacını yerine getirememe şekli (örn. \"kırılma\", \"aşınma\", \"sensör yanlış okuma\").\nHatanın Etkisi (Effect): Hata türünün çalışan, ekipman, çevre veya işletme üzerindeki sonucu.\nHatanın Nedeni (Cause): Hata türüne yol açan kök neden (malzeme yorulması, bakımsızlık, kullanım hatası vb.).\nŞiddet (Severity, S): Hatanın etkisinin ciddiyetinin 1 (önemsiz) ile 10 (can kaybı/ağır yaralanma) arasında puanlanması.\nOluşma Olasılığı (Occurrence, O): Hata nedeninin/türünün ne sıklıkla ortaya çıkabileceğinin 1 (çok nadir) ile 10 (çok sık/kaçınılmaz) arasında puanlanması.\nSaptanabilirlik (Detection, D): Hata gerçekleşmeden veya sonuç doğurmadan ÖNCE mevcut kontrollerle fark edilme ihtimalinin 1 (kesin fark edilir) ile 10 (fark edilmesi neredeyse imkânsız) arasında puanlanması — D ekseninde YÜKSEK puan DÜŞÜK saptanabilirlik anlamına gelir.\nRisk Öncelik Sayısı / ÖSD (RPN): Şiddet × Oluşma × Saptanabilirlik çarpımı ile bulunan, 1-1000 arasında değişen ve hataların önceliklendirilmesinde kullanılan bileşik puan (RPN = S × O × D)."],
                ['tip' => 'baslik', 'metin' => '4. YETKİ VE SORUMLULUKLAR'],
                ['tip' => 'paragraf', 'metin' => 'FMEA çalışması, ilgili ekipman/süreç hakkında bilgi sahibi çok disiplinli bir ekip tarafından yürütülür: işveren veya işveren vekili; işyerinde sağlık ve güvenlik hizmetini yürüten iş güvenliği uzmanları ile işyeri hekimleri; bakım ve üretim sorumluları; işyerindeki çalışan temsilcileri; işyerindeki destek elemanları; ekipmanı/süreci fiilen kullanan ve mevcut/muhtemel tehlike kaynakları ile riskler konusunda bilgi sahibi çalışanlar. Çalışmayı yöneten kişi (moderatör), her hata türü için S/O/D puanlarının ekip mutabakatıyla belirlenmesini sağlar.'],
                ['tip' => 'baslik', 'metin' => '5. UYGULAMA'],
                ['tip' => 'baslik', 'metin' => '5.1 Kapsamın ve Fonksiyonların Belirlenmesi'],
                ['tip' => 'paragraf', 'metin' => 'İncelenecek ekipman, bileşen veya proses adımı ile bunların beklenen işlevi (tasarım amacı) tanımlanır; inceleme, ekipman bazında (Ekipman FMEA) veya proses adımı bazında (Proses FMEA) yürütülebilir.'],
                ['tip' => 'baslik', 'metin' => '5.2 Hata Türlerinin Belirlenmesi'],
                ['tip' => 'paragraf', 'metin' => 'Her bileşen/adım için "bu, beklenen işlevini nasıl yerine getiremeyebilir?" sorusu sorularak olası tüm hata türleri (mekanik arıza, elektriksel arıza, insan hatası, yazılım hatası vb.) listelenir.'],
                ['tip' => 'baslik', 'metin' => '5.3 Etki, Neden ve Mevcut Kontrollerin Belirlenmesi'],
                ['tip' => 'paragraf', 'metin' => 'Her hata türü için olası etkiler, kök nedenler ve hâlihazırda uygulanan önleyici/tespit edici kontroller (bakım planı, sensör, alarm, prosedür, eğitim vb.) tespit edilir.'],
                ['tip' => 'baslik', 'metin' => '5.4 S-O-D Puanlaması ve RPN Hesabı'],
                ['tip' => 'paragraf', 'metin' => 'Her hata türü için Şiddet (S), Oluşma Olasılığı (O) ve Saptanabilirlik (D) puanları standart 1-10 ölçeğinden (bkz. Tanımlar) ekip mutabakatıyla belirlenir; RPN = S × O × D formülüyle hesaplanan puan, hataları önceliklendirmede kullanılır. Kurum içi eşik değerin (örn. RPN ≥ 100) üzerindeki hata türleri için önleyici faaliyet zorunlu kabul edilir.'],
                ['tip' => 'baslik', 'metin' => '5.5 Önleyici Faaliyet ve Yeniden Değerlendirme'],
                ['tip' => 'paragraf', 'metin' => 'Yüksek RPN\'li hata türleri için önleyici/iyileştirici faaliyetler (tasarım değişikliği, ek kontrol, bakım sıklığı artırımı, eğitim vb.) planlanır; faaliyet sonrası S, O ve/veya D puanları yeniden değerlendirilip yeni (rezidüel) RPN hesaplanarak iyileşme doğrulanır.'],
                ['tip' => 'baslik', 'metin' => '6. RİSK DEĞERLENDİRMESİNİN YENİLENMESİ GEREKEN DURUMLAR'],
                ['tip' => 'paragraf', 'metin' => 'Yapılmış olan FMEA çalışması; ekipman/süreçte tasarım veya işletme değişikliği yapılması, yeni ekipman/proses devreye alınması, ilgili ekipmanda arıza/kaza/ramak kala olayı meydana gelmesi, bakım stratejisinde değişiklik veya işveren/uzman tarafından gerekli görülen diğer hâllerde tamamen veya kısmen yenilenir. Değişiklik olmayan sistemlerde periyodik olarak (işletme politikasına göre genellikle 2-3 yılda bir) gözden geçirilmesi önerilir.'],
                ['tip' => 'baslik', 'metin' => '7. DÖKÜMANTASYON'],
                ['tip' => 'paragraf', 'metin' => 'FMEA çalışma kayıtları (bileşen/hata türü/etki/neden/mevcut kontrol/S/O/D/RPN/önleyici faaliyet/sorumlu/termin/rezidüel S-O-D-RPN sütunlarını içeren tablo) çalışma tarihi ve katılımcı listesiyle birlikte belgelenir; tamamlanmasının ardından katılımcılar tarafından imzalanıp işyerinde saklanır.'],
            ],
        ],
    ];
}
