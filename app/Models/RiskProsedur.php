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
                ['tip' => 'paragraf', 'metin' => "Bu prosedürün temel amacı işyerindeki çalışma koşullarından kaynaklanan iş kazaları ve meslek hastalıklarını oluşturan nedenler ve bunları etkileyen faktörleri, bu faktörlere maruz kalınma sürelerini dikkate alarak mümkün olan en geçerli ve doğru bilgiyi toplayarak görünmeyen tehlikelerin ortaya çıkmasını engellemek için etkili bir güvenlik ağı kurmaktır."],
                ['tip' => 'baslik', 'metin' => 'KAPSAM'],
                ['tip' => 'paragraf', 'metin' => 'Kalite Yönetim Sistemi (KYS) kapsamında belirlenen prosesler için risklerin değerlendirilmesi faaliyetlerini kapsar.'],
                ['tip' => 'baslik', 'metin' => 'TANIMLAR'],
                ['tip' => 'paragraf', 'metin' => "OLAY: Kazaya neden olan veya olma potansiyeli olan durum.\nKAZA: Ölüme, hastalığa, hasara, zarara ya da diğer kayıplara yol açan istenmeyen olay.\nTEHLİKE: Çalışma ortam ve şartlarında var olan ya da dışarıdan gelebilecek, kapsamı belirlenmemiş, maruz kimselere, işyerine ve çevreye zarar ya da hasar verme potansiyeli.\nRİSK: Tehlikelerden kaynaklanan bir olayın, meydana gelme ihtimali ile zarar verme derecesinin bileşkesidir. Risk = Zararın ortaya çıkma olasılığı × Zararın ortaya çıktığı anda yapması muhtemel etki.\nKABUL EDİLEBİLİR RİSK: Kuruluşun yasal zorunluluklara ve kendi İSG politikasına göre tahammül edebileceği düzeye indirilmiş risk.\nRİSK DEĞERLENDİRME: Tehlike potansiyeli bulunan maddelerle ilgili her türlü bilimsel bilgi ve malumatın düzenlenmesi ve analiz edilmesine yönelik sistematik bir yaklaşımdır.\nMARUZ KALANLAR: Çeşitli faaliyetler sonucu açığa çıkan tehlikelerle karşılaşan ve bu tehlikelerden etkilenme potansiyeli bulunan operatör/operatörler, işçi/işçiler, ziyaretçi/ziyaretçiler vb.\nOLASILIK (O): Değerlendirmesi yapılan faaliyete ilişkin tehlikenin gerçekleşme ihtimali.\nŞİDDET (Ş): Değerlendirmesi yapılan faaliyete ilişkin tehlikenin gerçekleşmesi halinde zarar ve kayıp verme potansiyeli.\nRİSK PUANI (RP): Olasılık ile şiddet değerlerinin çarpımı ile bulunan puan."],
                ['tip' => 'baslik', 'metin' => 'RİSK DEĞERLENDİRMESİNİN VE YÖNETİMİNİN YARARLARI'],
                ['tip' => 'paragraf', 'metin' => "İşyerinde yazılı prosedür ve politikaların oluşmasını ya da olgunlaşmasını sağlar.\nÇalışanların ve işyeri yönetiminin iş sağlığı ve güvenliği konularında bilgi sahibi olmalarını ve karar verme kabiliyetini geliştirir.\nİşyerinde olası tehlikeler ve alınacak tedbirler belirlenir; risklerin büyüklüğü hesaplanır ve tolere edilebilir olup olmadığına karar verilir.\nYasal yükümlülükler ve İSG politikası çerçevesinde tahammül edilebilir düzeye indirilmiş risk ile çalışılmasını sağlar.\nGerekli düzeltici ve önleyici faaliyetlerin gerçekleştirilmesini sağlayacak verilerin kaydedilmesini, sonuçların izlenmesini ve ölçülmesini sağlar."],
                ['tip' => 'baslik', 'metin' => 'YETKİ VE SORUMLULUKLAR'],
                ['tip' => 'paragraf', 'metin' => "İşyerinde İş Sağlığı ve Güvenliği Risk Yönetimini etkileyen işlerle uğraşan personelin yetkileri, sorumlulukları ve otoriteleri ile karşılıklı ilişkileri tanımlanmalı ve dokümanlaştırılmalıdır. Bu kapsamda; riskin zararlı etkilerini azaltan veya önleyen eylemleri başlatanlar, risk seviyesi kabul edilir sınıra gelene kadar risk davranışı usulünü kontrol edenler, risk yönetimi ile ilgili problemleri anlayıp kaydedenler, belirlenen kanallar yoluyla çözümleri sağlayan/tavsiye eden/başlatanlar, çözümlerin uygulanmasını tasdik edenler ve uygun olduğunda dâhili/harici danışma ve iletişimde bulunanlar görevlidir."],
                ['tip' => 'baslik', 'metin' => 'UYGULAMA'],
                ['tip' => 'paragraf', 'metin' => "Bu prosedürde tanımlanan risk değerlendirme çalışmaları Matris Yöntemi kullanılarak yapılmaktadır. Tabloda \"Olasılık\" zararın ortaya çıkma olasılığını, \"Şiddet\" zararın ortaya çıktığı anda yapması muhtemel etkiyi, \"Risk Puanı\" ise \"Olasılık\" ve \"Şiddet\" sütunundaki rakamların çarpımı sonucu bulunan 1 ile 25 arasındaki puanı tanımlar. Risk puanına göre risk düzeyi \"KABUL EDİLEMEZ\" veya \"ÖNEMLİ\" olarak belirlenen faaliyetler Çevre ve İSG Yönetim Programı ve Aksiyon Planı'na alınıp, yapılacak iyileştirmelerle ve alınacak tedbirlerle bu faaliyetlerin risk puanı ve risk düzeyi düşürülür."],
                ['tip' => 'baslik', 'metin' => 'RİSK DEĞERLENDİRMESİNİN YENİLENMESİ GEREKEN DURUMLAR'],
                ['tip' => 'paragraf', 'metin' => "Yapılmış olan risk değerlendirmesi; tehlike sınıfına göre çok tehlikeli, tehlikeli ve az tehlikeli işyerlerinde sırasıyla en geç iki, dört ve altı yılda bir yenilenir. Ayrıca aşağıdaki durumlarda ortaya çıkabilecek yeni risklerin işyerinin tamamını veya bir bölümünü etkiliyor olması göz önünde bulundurularak risk değerlendirmesi tamamen veya kısmen yenilenir: işyerinin taşınması veya binalarda değişiklik yapılması; uygulanan teknoloji, madde ve ekipmanlarda değişiklik; üretim yönteminde değişiklik; iş kazası, meslek hastalığı veya ramak kala olay meydana gelmesi; çalışma ortamına ait sınır değerlere ilişkin mevzuat değişikliği; çalışma ortamı ölçümü ve sağlık gözetim sonuçlarına göre gerekli görülmesi; işyeri dışından kaynaklanan yeni bir tehlikenin ortaya çıkması."],
                ['tip' => 'baslik', 'metin' => 'RİSK DEĞERLENDİRMESİ ÇALIŞMALARINI YÜRÜTECEKLER'],
                ['tip' => 'paragraf', 'metin' => "Risk değerlendirmesi, işverenin oluşturduğu bir ekip tarafından gerçekleştirilir: işveren veya işveren vekili; işyerinde sağlık ve güvenlik hizmetini yürüten iş güvenliği uzmanları ile işyeri hekimleri; işyerindeki çalışan temsilcileri; işyerindeki destek elemanları; işyerindeki bütün birimleri temsil edecek şekilde belirlenen ve mevcut/muhtemel tehlike kaynakları ile riskler konusunda bilgi sahibi çalışanlar. İhtiyaç duyulduğunda ekibe destek olmak üzere işyeri dışındaki kişi ve kuruluşlardan danışmanlık hizmeti alınabilir."],
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
                ['tip' => 'paragraf', 'metin' => "Bu prosedürün uygulanmasından işyerinde çalışan tüm personel sorumludur. Başlangıçta Risk Analizi Ekibi tarafından hazırlanan risk analizi çalışmaları, sürecin devamında işveren yetkilileri tarafından sürekli olarak izlenecek ve iyileştirilecektir."],
                ['tip' => 'baslik', 'metin' => '4. TANIMLAR VE KISALTMALAR'],
                ['tip' => 'paragraf', 'metin' => "Kanun: 30/06/2012 tarihli ve 6331 sayılı İş Sağlığı ve Güvenliği Kanunu.\nYönetmelik: 29/12/2012 tarihli ve 28512 sayılı İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği.\nTehlike: İşyerinde var olan ya da dışarıdan gelebilecek, çalışanı veya işyerini etkileyebilecek zarar veya hasar verme potansiyeli.\nRisk: Tehlikeden kaynaklanacak kayıp, yaralanma ya da başka zararlı sonuç meydana gelme ihtimali.\nKabul edilebilir risk seviyesi: Yasal yükümlülüklere ve işyerinin önleme politikasına uygun, kayıp veya yaralanma oluşturmayacak risk seviyesi.\nÖnleme: İSG risklerini ortadan kaldırmak veya azaltmak için planlanan ve alınan tedbirlerin tümü.\nRamak kala olay: İşyerinde meydana gelen; çalışan, işyeri ya da iş ekipmanını zarara uğratma potansiyeli olduğu hâlde zarara uğratmayan olay.\nOlasılık: Tehlikenin, maddi ve manevi zarar verme ihtimali.\nŞiddet: Kazanın gerçekleşmesi durumunda oluşacak zararın büyüklüğü.\nFrekans: Tanımlanan faaliyetin gerçekleşme periyodu.\nRisk Değerlendirme Sonucu (RDS): Olasılık × Şiddet × Frekans değeri.\nRisk Derecelendirilmesi: Ortaya çıkan RDS sonucu tehlikelerin önem sırası."],
                ['tip' => 'baslik', 'metin' => '5. PROSEDÜR AKIŞI'],
                ['tip' => 'baslik', 'metin' => '5.1 Risk Değerlendirmesi'],
                ['tip' => 'paragraf', 'metin' => 'Tüm işyerleri için tasarım veya kuruluş aşamasından başlamak üzere tehlikeleri tanımlama, riskleri belirleme ve analiz etme, risk kontrol tedbirlerinin kararlaştırılması, dokümantasyon, yapılan çalışmaların güncellenmesi ve gerektiğinde yenileme aşamaları izlenerek gerçekleştirilir. Risk değerlendirmesinin gerçekleştirilmesinde "Kinney Metodu" uygulanır.'],
                ['tip' => 'baslik', 'metin' => '5.2 Risk Değerlendirmesi Ekibi'],
                ['tip' => 'paragraf', 'metin' => "Risk değerlendirmesi, işverenin oluşturduğu bir ekip tarafından gerçekleştirilir: işveren veya işveren vekili; işyerinde sağlık ve güvenlik hizmetini yürüten iş güvenliği uzmanları ile işyeri hekimleri; işyerindeki çalışan temsilcileri; işyerindeki destek elemanları; işyerindeki bütün birimleri temsil edecek şekilde belirlenen ve mevcut/muhtemel tehlike kaynakları ile riskler konusunda bilgi sahibi çalışanlar. İşveren, ihtiyaç duyulduğunda bu ekibe destek olmak üzere işyeri dışındaki kişi ve kuruluşlardan hizmet alabilir; koordinasyon işveren veya ekip içinden görevlendirilen bir kişi tarafından sağlanabilir."],
                ['tip' => 'baslik', 'metin' => '5.3 Tehlikelerin Belirlenmesi'],
                ['tip' => 'paragraf', 'metin' => "Tehlikeler tanımlanırken çalışma ortamı, çalışanlar ve işyerine ilişkin asgari olarak şu bilgiler toplanır: işyeri bina ve eklentileri; yürütülen faaliyetler ile iş ve işlemler; üretim süreç ve teknikleri; iş ekipmanları; kullanılan maddeler; artık ve atıklarla ilgili işlemler; organizasyon ve hiyerarşik yapı; çalışanların tecrübe ve düşünceleri; çalışma izin belgeleri; çalışanların eğitim/yaş/cinsiyet ve sağlık gözetimi kayıtları; özel politika gerektiren gruplar; teftiş sonuçları; meslek hastalığı ve iş kazası kayıtları; ramak kala olay kayıtları; malzeme güvenlik bilgi formları; ortam ve kişisel maruziyet ölçüm sonuçları; varsa önceki risk değerlendirmeleri; acil durum planları."],
                ['tip' => 'baslik', 'metin' => '5.4 Risk Değerlendirmesinin Yenilenmesi'],
                ['tip' => 'paragraf', 'metin' => "Yapılmış olan risk değerlendirmesi; tehlike sınıfına göre çok tehlikeli, tehlikeli ve az tehlikeli işyerlerinde sırasıyla en geç iki, dört ve altı yılda bir yenilenir. Ayrıca işyerinin taşınması/bina değişikliği, teknoloji/madde/ekipman değişikliği, üretim yöntemi değişikliği, iş kazası/meslek hastalığı/ramak kala olay, mevzuat değişikliği, ölçüm ve sağlık gözetimi sonuçları veya işyeri dışından kaynaklanan yeni bir tehlikenin ortaya çıkması hâllerinde tamamen veya kısmen yenilenir."],
                ['tip' => 'baslik', 'metin' => '5.5 Risklerin Belirlenmesi ve Analizi'],
                ['tip' => 'paragraf', 'metin' => "Tespit edilen tehlikelerin her biri ayrı ayrı dikkate alınarak, hangi sıklıkta oluşabileceği ile kimlerin/nelerin ne şekilde ve hangi şiddette zarar görebileceği belirlenir; mevcut kontrol tedbirlerinin etkisi de göz önünde bulundurulur. Analiz edilen riskler, kontrol tedbirlerine karar verilmek üzere etkilerinin büyüklüğüne ve önemine göre en yüksek risk seviyesinden başlanarak sıralanır ve yazılı hâle getirilir. Risk değerlendirmesinde \"Olasılık\", \"Frekans\" ve \"Şiddet\" parametreleri için puanlama cetveli doğrultusunda puanlama yapılır (Risk Değerlendirme Sonucu = O × F × Ş)."],
            ],
        ],
    ];
}
