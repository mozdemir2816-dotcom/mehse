<?php

/*
 | Acil Durum Planı EK-1 senaryoları (konu anahtarı => açıklama, müdahale
 | adımları, olay sonrası). Kaynak: kullanıcının FOM MAKİNA ADEP belgesi
 | (salgın … makine arızası); FOM'da içeriği boş kalan yangın, deprem, sabotaj,
 | iş kazası, elektrik, kimyasal, zehirlenme, patlama, gıda zehirlenmesi ve
 | asansör aynı biçimde yazıldı. Plan PDF'i ve Word çıktısı bunu kullanır.
*/

return [
    'yangin' => [
        'aciklama' => 'İşyerinde çıkabilecek yangınlarda can güvenliğini öncelikli tutarak alarm, ilk müdahale, tahliye ve itfaiyeye bilgi verme prosedürüdür. Başlangıç yangınına yalnızca eğitimli ve donanımlı kişiler, kaçış yolu güvenliyken müdahale eder.',
        'mudahale' => [
            'Yangını ilk gören kişi çevresini sesle uyarır; varsa güvenle erişilebilen yangın ihbar butonuna basar.',
            'Güvenli noktadan 112 aranır; açık adres, yangının yeri, yanan malzemenin cinsi (elektrik, yağ, kimyasal, katı madde) ve içeride kalan kişi bilgisi verilir.',
            'Söndürme ekibi, eğitimi ve donanımı kapsamında, kaçış yolunu arkasında tutarak uygun söndürücü ile başlangıç yangınına müdahale eder. Elektrik yangınına su ile müdahale edilmez.',
            'Teknik müdahale ekibi veya yetkili kişi, güvenli noktadan elektrik ve gaz akışını keser; yangın bölgesindeki pano ve vanalara yaklaşılmaz.',
            'Yangın büyüyor, yoğun duman oluşuyor veya söndürücü yetersiz kalıyorsa müdahale bırakılır, kapılar kapatılarak (kilitlenmeden) tahliye edilir.',
            'Tahliyede asansör kullanılmaz, koşulmaz, dumanlı ortamda eğilerek ilerlenir; eşya almak için geri dönülmez.',
            'Toplanma alanında koruma ekibi sayım yapar; eksik kişi ve son görüldüğü yer itfaiyeye bildirilir.',
            'İtfaiye geldiğinde koordinatör olay hakkında bilgi verir; ekipler itfaiyenin yönlendirmesine göre hareket eder.',
        ],
        'olay_sonrasi' => [
            'İtfaiye ve yetkili kişilerin güvenli beyanı olmadan binaya girilmez; elektrik ve gaz tesisatı kontrol edilmeden devreye alınmaz.',
            'Kullanılan söndürücüler dolum / yenileme için ayrılır, eksikler tamamlanır.',
            'Yangının nedeni araştırılır; olay tutanağı hazırlanır, yaralanma varsa iş kazası bildirimi yapılır.',
            'DÖF açılır; risk değerlendirmesi ve acil durum planı gözden geçirilir.',
            'Hasar kayıt altına alınır, sigorta bildirimi yapılır.',
        ],
        // Kaynak: Beyda Yapı ADEP; halon, eski söndürücü tipleri ve sınıf hataları düzeltildi.
        'ek' => [
            ['baslik' => 'YANGINA KARŞI ÖNCEDEN ALINACAK TEDBİRLER', 'maddeler' => [
                'Kimyasallar: Etiketli ve kapalı kaplarda, kullanım yerinde yalnız gerektiği kadar bulundurulur; ateşleme kaynaklarından uzak tutulur. 50 litreyi aşan çözücüler döküntü tepsili metal dolaplarda veya bu amaçla düzenlenmiş odalarda saklanır.',
                'Kimyasal depoları (varsa): Aydınlatma ve elektrik tesisatı etanj olur, depo elektriği dışarıdan kontrol edilir; raf, dolap ve personel için topraklama yapılır, sürekli temiz hava girişi sağlanır. Kimyasalla çalışanlar statik elektrik biriktirmeyen (pamuklu / antistatik) iş elbisesi giyer.',
                'Yanıcı gazlar (varsa): Borular sağlam ve ısınmayacak şekilde yerleştirilir; kullanılmayan vanalar kapatılır; tüpler devrilmeye karşı sabitlenerek dışarıda, güneşten korunarak bekletilir. Gazla çalışılırken alan terk edilmez, uygun gaz dedektörü ve sürekli havalandırma bulunur.',
                'Elektrik panoları, UPS, trafo: Önleri boş tutulur; yağlı bez ve üstüpü ağzı kapaklı metal kaplarda toplanır; paratoner ve topraklama periyodik kontrol edilir; uygun tipte söndürücü (CO2 / ABC tozlu) ve otomatik yangın algılama bulunur.',
                'Depolar: Depo içinde sigara içilmez; çabuk parlayıcı malzemeler sık kontrol edilir; malzemeye uygun söndürücüler dolu ve hazır tutulur; elektrik tesisatı, kablo, fiş ve prizler kontrol listesiyle düzenli kontrol edilir, hasarlılar değiştirilir.',
                'Açık alanlar: Yabani ot, kâğıt, bez gibi yanıcı atıklar temizlenir; kesilen otlar yakılmaz, saha dışına çıkarılır.',
                'Ana elektrik panosunda yangından korunma amaçlı 300 mA kaçak akım koruma rölesi bulunur (insan koruması için priz devrelerindeki 30 mA röleye ek olarak).',
                'Elektrik tesisatı: Projesi dışında aşırı yük bağlanmaz; panolardaki sigortalar muhafazalı ve otomatik olur; gerekli cihazlar topraklanır; çıplak veya hasarlı kablo, priz, fiş derhal yenilenir; tesisat yetkili kişilerce periyodik kontrol edilir.',
                'Uygun yerlere “Sigara içmek yasaktır” ve “Ateşle yaklaşmayınız” levhaları asılır.',
            ]],
            ['baslik' => 'YANGIN İÇİN SINIRLANDIRICI TEDBİRLER', 'maddeler' => [
                'Gerekli bölümlerde duman / ısı dedektörleri ile erken uyarı için ışıklı ve sesli alarm sistemi bulunur.',
                'Söndürücüler kolay ulaşılır yerlerde, işaretli ve periyodik kontrolleri (6 ayda bir) yapılmış olarak bulundurulur; kullanımdan sonra derhal yeniden doldurulur.',
                'Acil durum tahliye planı ve yangın müdahale şeması her kat giriş ve çıkışında göz hizasında asılır.',
                'Gaz kesme vanaları ve elektrik kesme noktaları belirlenir, işaretlenir ve krokide gösterilir.',
                'Ekip üyeleri ve iletişim bilgileri görünür yerde asılır; yılda en az bir kez yangın tahliye ve kurtarma tatbikatı yapılır.',
                'Bina yangın yönetmeliğinin gerektirdiği yerlerde sprinkler (otomatik sulu söndürme) sistemi kurulur.',
            ]],
            ['baslik' => 'YANGIN SINIFLARI VE UYGUN SÖNDÜRÜCÜ', 'maddeler' => [
                'A sınıfı (katı: kâğıt, odun, kumaş, plastik): Soğutarak söndürülür — su, ABC tozlu, köpüklü söndürücü.',
                'B sınıfı (sıvı: boya, tiner, yağ, akaryakıt): Boğarak söndürülür — köpüklü, ABC / BC tozlu, CO2 söndürücü. Su kullanılmaz; yanan sıvıyı yayar.',
                'C sınıfı (gaz: doğalgaz, LPG, propan, asetilen): Önce gaz akışı kesilir. Gaz kesilemiyorsa alev söndürülmez (birikecek gaz patlamaya yol açar); alan boşaltılır, çevre soğutulur — ABC / BC tozlu söndürücü.',
                'D sınıfı (yanıcı metal: magnezyum, alüminyum tozu, sodyum): Yalnız D sınıfı metal yangını tozu kullanılır; su ve köpük kullanılmaz.',
                'F sınıfı (yemeklik yağ, mutfak): F sınıfı (ıslak kimyasal) söndürücü veya yangın battaniyesi kullanılır; su kullanılmaz.',
                'Elektrik yangınları: Önce enerji kesilir; enerji kesilemiyorsa yalnız CO2 veya ABC tozlu söndürücü kullanılır, su kullanılmaz.',
            ]],
            ['baslik' => 'YANGINDA KİŞİSEL DAVRANIŞ', 'maddeler' => [
                'Yangını ilk gören, sakin ve yüksek sesle “YANGIN VAR” diye çevresini uyarır; söndürme ekibinde değilse olay yerinden uzaklaşır ve tahliye talimatına uyar.',
                'Yangın kapalı alandaysa yayılmasını önlemek için kapı ve pencereler (kilitlenmeden) kapatılır.',
                'Duman veya yanık kokusu başka odadan geliyorsa kapı açılmadan önce elin tersiyle sıcaklığı kontrol edilir; sıcaksa açılmaz.',
                'Dumanlı ortamda eğilerek veya sürünerek ilerlenir; ağız ve burun ıslak bezle kapatılır.',
                'Çıkış olanaksızsa dolap vb. yerlere saklanılmaz; kapı aralıkları ıslak bezle kapatılır, pencereden dışarıdakilerle iletişim kurulur.',
                'Kıyafet alev alırsa koşulmaz: DUR — YAT — YUVARLAN; alevler battaniye türü örtüyle boğulur.',
                'Yanık bölge serin, akan suyla 10–20 dakika soğutulur; buz, diş macunu, yağ vb. sürülmez, yapışan giysi çekilmez; 112’ye bilgi verilir.',
            ]],
        ],
    ],
    'deprem' => [
        'aciklama' => 'Deprem sırasında ve sonrasında çalışanların yaralanmasını önlemeye, güvenli tahliyeye ve ikincil tehlikelerin (yangın, gaz kaçağı, çökme) kontrolüne yönelik prosedürdür.',
        'mudahale' => [
            'Sarsıntı sırasında koşulmaz, merdiven ve asansöre yönelinmez; "Çök – Kapan – Tutun" hareketi yapılır.',
            'Cam, raf, dolap, asılı cisim ve makinelerden uzak durulur; sağlam bir masa altına veya iç duvar dibine geçilir.',
            'Sarsıntı sırasında yalnız elinizin altındaki makine acil stop ile durdurulur; ocak, kaynak ve ısıl işlem kaynakları sarsıntı bitince kapatılır.',
            'Sarsıntı bittikten sonra elektrik ve gaz, güvenli noktadan yetkili kişi tarafından kesilir.',
            'Tahliye, hasarlı bölümlerden ve düşme tehlikesi olan cephelerden uzak güvenli çıkışlar kullanılarak sakin biçimde yapılır.',
            'Toplanma alanında sayım yapılır; eksik ve enkaz altında kalan kişilerin son görüldüğü yer 112 / AFAD’a bildirilir.',
            'Enkaz altında kalanlara gönüllü kazı ile müdahale edilmez; profesyonel ekiplerin yönlendirmesi beklenir. Artçı sarsıntılara karşı binaya girilmez.',
        ],
        'olay_sonrasi' => [
            'Bina, yetkili kişilerce hasar tespiti yapılıp güvenli bulunmadan yeniden kullanılmaz.',
            'Elektrik, gaz, su ve basınçlı hava tesisatları kontrol edilmeden devreye alınmaz.',
            'Raf, dolap ve makine sabitlemeleri gözden geçirilir; eksikler giderilir.',
            'Yaralanmalar için iş kazası bildirimi yapılır; çalışanlara psiko-sosyal destek planlanır.',
            'Toplanma alanı ve tahliye yolları değerlendirilir, plan güncellenir.',
        ],
        // Kaynak: Beyda Yapı ADEP; iç çelişkiler AFAD "Çök – Kapan – Tutun" esasına göre giderildi.
        'ek' => [
            ['baslik' => 'DEPREMDEN ÖNCE HAZIRLIK', 'maddeler' => [
                'Depremi önleyemeyiz; ancak yaralanmayı önlemeye, hasarı en aza indirmeye ve deprem sonrası en az 72 saat dışarıdan yardım almadan idare etmeye hazırlıklı olunur.',
                'Raf, dolap, makine ve devrilebilecek ekipmanlar sabitlenir; ağır eşyalar alt raflara konur; kaçış yollarına devrilebilecek malzeme konmaz.',
                'Her bölümde sarsıntıda sığınılacak güvenli noktalar (sağlam masa altı, iç duvar dibi, kolon yanı) önceden belirlenir; cam, raf ve asılı cisimlerin yakını güvenli nokta sayılmaz.',
                'İşyerinde su, ilkyardım malzemesi, el feneri, düdük ve pil içeren afet ve acil durum çantası bulundurulur.',
                'Çalışanlara “Çök – Kapan – Tutun” eğitimi verilir; deprem tatbikatı yapılır.',
            ]],
            ['baslik' => 'DEPREM SIRASINDA BULUNULAN YERE GÖRE DAVRANIŞ', 'maddeler' => [
                'Sarsıntı başladığında nerede olunursa olunsun hemen korunma pozisyonu alınır ve sarsıntı durana kadar orada kalınır; sarsıntı sırasında dışarı çıkılmaya çalışılmaz, koşulmaz, merdiven ve asansör kullanılmaz.',
                'ÇÖK: Hedefi küçültmek için yere çökülür. KAPAN: Kollarla baş ve boyun korunur, varsa sağlam bir masanın altına girilir. TUTUN: Sarsıntı bitene kadar tutunarak beklenir.',
                'Kapalı alanda: Pencere, cam, raf, dolap ve makinelerden uzaklaşılır; yemekhanede masa altına girilir. Üst katların pencere veya balkonlarından atlanmaz.',
                'Açık alanda: Bina, duvar, direk, elektrik telleri ve iskelelerden uzak açık bir yerde Çök – Kapan – Tutun yapılır; sarsıntı bitince toplanma alanına gidilir.',
                'Forklift, kamyon, iş makinesi veya araçta: Araç, yolu kapatmadan ve köprü, alt / üst geçit, bina ve direklerden uzak güvenli bir yere çekilip durdurulur, el freni çekilir; sarsıntı bitene kadar araçta kalınır.',
                'Yüksekte çalışırken: Emniyet kemeriyle bağlı kalınır ve sağlam bir yere tutunulur; sarsıntı bitince güvenli şekilde aşağı inilir.',
                'Kalabalık ortamda: Ezilmeyecek bir noktaya sığınılır, kapılara ve merdivenlere hücum edilmez.',
                'Sarsıntı sırasında yalnız elinizin altındaki acil stop butonuna basılabilir; gaz, elektrik ve diğer tesisata müdahale sarsıntı bittikten sonra yapılır.',
            ]],
            ['baslik' => 'DEPREMDEN SONRA', 'maddeler' => [
                'Sarsıntı bitince kaçış yolları kullanılarak, panik yapmadan ve artçı sarsıntılara hazırlıklı olarak toplanma alanına gidilir.',
                'Gaz kaçağı ve yanıcı madde sızıntısı kontrol edilir; karanlıkta yalnız el feneri kullanılır, kibrit, çakmak ve açık alev kullanılmaz, elektrik anahtarlarına dokunulmaz.',
                'Yangın varsa yangın senaryosuna göre hareket edilir; elektrik tesisatı yetkili elektrikçi tarafından kontrol edilir.',
                'Yaralılar 112’ye bildirilir; ağır yaralının taşınması 112 yönlendirmesiyle yapılır. Mahsur kalanlar için 112 / AFAD’dan yardım istenir.',
                'Dökülmüş ve saçılmış maddeler (özellikle kimyasallar) güvenli şekilde temizlenir.',
                'Hasar tespiti yapılmadan ve acil durum koordinatörünün talimatı olmadan çalışmaya başlanmaz.',
            ]],
        ],
    ],
    'sabotaj' => [
        'aciklama' => 'İşyerine, tesislere, ürünlere veya çalışanlara zarar vermek amacıyla kasıtlı olarak yapılan eylemlerde (kundaklama, ekipmana zarar verme, zehirleme, yetkisiz giriş) uygulanan emniyet prosedürüdür.',
        'mudahale' => [
            'Şüpheli davranış, yetkisiz kişi veya kasıtlı hasar fark eden kişi derhal koordinatöre ve koruma ekibine haber verir.',
            'Saldırgana veya silahlı kişiye müdahale edilmez; güvenli bir yere geçilerek 112 aranır, kişi ve araç eşkâli (plaka, kıyafet, yön) bildirilir.',
            'Sabotajın sonucu olan acil duruma (yangın, patlama, kimyasal sızıntı, yaralanma) ilgili senaryoya göre müdahale edilir.',
            'Tehlike işyerinin tamamını etkiliyorsa tahliye başlatılır, toplanma alanında sayım yapılır.',
            'Olay yeri, delillerin korunması için güvenlik şeridi ile çevrilir; kolluk gelene kadar alana girilmez ve hiçbir şeye dokunulmaz.',
            'Zehirleme şüphesinde ilgili yiyecek, içecek veya ürün kullanılmaz, ayrılarak muhafaza edilir; 112 ve 114 UZEM aranır.',
        ],
        'olay_sonrasi' => [
            'Olay kolluk kuvvetlerine bildirilir, tutanak tutulur; kamera kayıtları ve tanık bilgileri korunur.',
            'Giriş kontrolü, ziyaretçi kaydı ve kritik alanların erişim yetkileri gözden geçirilir.',
            'Hasar ve kayıplar kaydedilir; sigorta bildirimi yapılır.',
            'Çalışanlar şüpheli durumları bildirme konusunda yeniden bilgilendirilir.',
        ],
        // Kaynak: Beyda Yapı ADEP Acil durum 5 (önleyici tedbir içermiyordu; kullanıcı onaylı metin). Oyalama / takip / yakalama talimatları alınmadı.
        'ek' => [
            ['baslik' => 'SABOTAJA KARŞI ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'Giriş kontrolü yapılır; ziyaretçiler kaydedilir ve işyerinde refakatle dolaşır.',
                'Kritik alanlar (elektrik panosu, kazan dairesi, kimyasal deposu, mutfak ve yemekhane) kilitli tutulur; yalnız yetkili kişiler girer.',
                'Kamera sistemi ve çevre aydınlatması bulunur; şantiyelerde saha çevresi çevrilir.',
                'Anahtar ve giriş kartları kayıt altında tutulur; işten ayrılan kişinin erişim yetkileri derhal kapatılır.',
                'Çalışanlara şüpheli kişi, paket ve davranışları bildirme konusunda eğitim verilir.',
            ]],
            ['baslik' => 'SALDIRGANA KARŞI TUTUM', 'maddeler' => [
                'Çalışanlar saldırganı yakalamaya, oyalamaya veya takip etmeye çalışmaz; güvenli bir yerden eşkâl, gidiş yönü ve araç plakası not edilerek 112’ye bildirilir.',
                'Varsa güvenlik görevlisi, can güvenliğini tehlikeye atmadan yetkisi kapsamında hareket eder.',
                'Olayda kasıt tespit edilirse acil durum koordinatörü başkanlığında olayın sebepleri araştırılır ve yasal işlem için yetkili kurumlara başvurulur.',
            ]],
        ],
    ],
    'is_kazasi' => [
        'aciklama' => 'İşyerinde meydana gelen iş kazası, ani rahatsızlanma ve sağlık olaylarında ilk müdahale, sağlık ekiplerine ulaşma ve yasal bildirim prosedürüdür.',
        'mudahale' => [
            'Olay yerine giden ilk kişi önce kendi güvenliğini sağlar; makine, enerji veya düşen yük gibi tehlike devam ediyorsa ortam güvenli hale getirilir.',
            'İlkyardım ekibine haber verilir; ağır yaralanma, bilinç kaybı, solunum güçlüğü veya ciddi kanamada vakit kaybetmeden 112 aranır.',
            'İlkyardımcı, eğitimine ve 112 yönlendirmesine uygun olarak bilinç, solunum ve kanama kontrolü yapar; ilaç ve tıbbi uygulama yapılmaz.',
            'Yaralı, hayati tehlike yoksa yerinden oynatılmaz; omurga yaralanması şüphesinde baş-boyun-gövde ekseni korunur.',
            'Ambulansın işyerine kolay ulaşması için haberleşme / koruma ekibi giriş yolunu açık tutar ve ekibi karşılar.',
            'Kaza yeri, inceleme yapılana kadar değiştirilmez; yalnızca tehlikeyi önlemek ve kurtarma için gerekli müdahale yapılır.',
        ],
        'olay_sonrasi' => [
            'İşveren iş kazasını yetkili kolluğa derhal, SGK’ye kazadan sonraki üç iş günü içinde bildirir.',
            'Kaza tutanağı hazırlanır; tanık ifadeleri, fotoğraflar ve kayıtlar toplanır.',
            'Kök neden analizi yapılır, DÖF açılır; risk değerlendirmesi güncellenir.',
            'Kazazede işe dönmeden önce işyeri hekimi değerlendirmesi yapılır; ilgili çalışanlara bilgilendirme / eğitim verilir.',
        ],
        // Kaynak: Beyda Yapı ADEP (Acil durum 9 + kaza akış şeması); İlkyardım Yönetmeliği oranları eklendi.
        'ek' => [
            ['baslik' => 'ÖLÜMLÜ VEYA AĞIR YARALANMALI KAZADA', 'maddeler' => [
                'Önce ölüm ve yaralı olup olmadığı belirlenir; yaralıya ilkyardım ve 112 bildirimi her şeyden önce gelir.',
                'Olay yeri güvenlik şeridiyle çevrilerek korunur; görevliler dışında kimse alana alınmaz.',
                'Hayat kurtarma ve tehlikeyi önleme dışında kaza yerindeki hiçbir şeye dokunulmaz, makine, malzeme ve ekipmanın yeri değiştirilmez.',
                '112 aranarak sağlık ekibi ve kolluk çağrılır; kolluk ve Cumhuriyet savcılığının talimatı olmadan olay yeri temizlenmez, çalışmaya başlanmaz.',
                'İşveren / işveren vekili, iş güvenliği uzmanı ve işyeri hekimi derhal bilgilendirilir; ilkyardım ekibi kazazedenin kimlik ve durum bilgisini sağlık ekibine iletir.',
            ]],
            ['baslik' => 'İLKYARDIM ORGANİZASYONU', 'maddeler' => [
                'İlkyardım Yönetmeliğine göre her 20 çalışana (az tehlikeli), 15 çalışana (tehlikeli) veya 10 çalışana (çok tehlikeli) en az bir ilkyardımcı bulundurulur; vardiyalı çalışmada her vardiyada ilkyardımcı bulunur.',
                'İlkyardımcılar Sağlık Bakanlığınca onaylı ilkyardım eğitim merkezinden belge alır; belgeler 3 yılda bir güncelleme eğitimiyle yenilenir.',
                'İçeriği düzenli kontrol edilen ilkyardım dolabı / çantası kolay ulaşılır yerde bulundurulur.',
                'Acil durum irtibat numaraları ve ilkyardımcıların adları görünür yerlere asılır.',
                'Uygun kişisel koruyucu donanım sağlanır ve doğru kullanımı denetlenir.',
                'Araçlar otoparka, acil durumda hızla çıkabilecek şekilde (ön tarafı çıkışa dönük) park edilir; ambulans giriş yolu açık tutulur.',
            ]],
            ['baslik' => 'İŞ KAZALARINA KARŞI ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'Risk değerlendirmesi yapılır ve değişikliklerde güncellenir.',
                'Çalışanlara işe özgü ve iş sağlığı ve güvenliği eğitimleri verilir.',
                'Çalışanların sağlık gözetimi belirlenen aralıklarla yapılır.',
                'Ramak kala olaylar bildirilir, kayıt altına alınır ve değerlendirilir.',
                'Çalışanların iş sağlığı ve güvenliği konusundaki görüş ve önerileri alınır.',
                'Zemin, yol ve merdivenler mevzuat ve standartlara uygun tutulur.',
                'İşyeri içi güncel trafik planı bulundurulur; araç ve yaya yolları ayrılır.',
            ]],
        ],
    ],
    'elektrik' => [
        'aciklama' => 'Elektrik akımına kapılma, ark ve elektrik kaynaklı yaralanmalarda kazazedeyi akımdan güvenli şekilde ayırma ve ilk müdahale prosedürüdür.',
        'mudahale' => [
            'Kazazedeye çıplak elle dokunulmaz; önce elektrik sigorta veya ana şalterden güvenli şekilde kesilir.',
            'Akım kesilemiyorsa kuru ve yalıtkan bir zemin üzerinde durularak tahta, plastik gibi yalıtkan bir cisimle kazazede akımdan ayrılır.',
            'Yüksek gerilim hatlarına yaklaşılmaz; elektrik arıza hattına (186) ve 112’ye haber verilir.',
            '112 aranır; olayın elektrik çarpması olduğu, gerilim düzeyi ve kazazedenin durumu bildirilir.',
            'İlkyardımcı bilinç ve solunumu değerlendirir; gerekiyorsa eğitimine ve 112 yönlendirmesine uygun temel yaşam desteği uygular.',
            'Akım kesildikten sonra yanıklar serin, akan suyla soğutulur ve temiz bir örtü ile kapatılır; yanığa herhangi bir madde sürülmez.',
        ],
        'olay_sonrasi' => [
            'Görünür yarası olmasa da kazazede mutlaka sağlık kuruluşunda değerlendirilir.',
            'Arızalı tesisat / ekipman kilitlenip etiketlenir; yetkili elektrikçi kontrolü olmadan kullanılmaz.',
            'İş kazası bildirimi yapılır, DÖF açılır.',
            'Kaçak akım rölesi, topraklama ve tesisat periyodik kontrolleri gözden geçirilir.',
        ],
        // Kaynak: Beyda Yapı ADEP Acil durum 7; kazazedeye temas eden çelişkili maddeler ve yanlış güvence çıkarıldı.
        'ek' => [
            ['baslik' => 'ELEKTRİK ÇARPMASINA KARŞI ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'Elektrikle ilgili her türlü iş yalnız yetkili elektrikçiler tarafından yapılır.',
                'Elektrikli ekipmanlar kullanılmadan önce gözle kontrol edilir; hasarlı ekipman hiçbir şekilde kullanılmaz.',
                'Çalışma alanlarındaki dağınık kablo ve tertibatlar yalıtkan kutu veya kapaklar içine alınır; kablolar ezilmeye ve ıslanmaya karşı korunur.',
                'Priz devrelerinde 30 mA kaçak akım koruma rölesi bulunur ve test butonuyla düzenli denenir.',
                'Elektrik tesisatı, topraklama ve yıldırımdan korunma (paratoner) tesisatı yılda bir yetkili kişilerce kontrol edilir, raporları saklanır.',
                'Pano kapakları kapalı tutulur, önleri boş bırakılır; bakım ve onarımda enerji kilitlenip etiketlenir (LOTO).',
                'İkaz ve uyarı levhalarındaki talimatlara aykırı hareket edilmez.',
                'Varsa yüksek gerilim / enerji nakil hatlarına güvenli yaklaşma mesafesi korunur; gerekli durumlarda elektrik dağıtım şirketine (186) bilgi verilerek hattın yalıtılması, toprak altına alınması veya uzaklaştırılması sağlanır.',
            ]],
            ['baslik' => 'ORTAMIN GÜVENLİ HALE GETİRİLMESİ', 'maddeler' => [
                'Olay yerine ilk gelen kişinin önceliği, başka kişilerin akıma kapılmasını önlemektir; diğer kişiler olay yerinden uzak tutulur.',
                'Elektrik şalterden veya sigortadan kesilir. Akım kesildiğinden emin olunmadan kazazedeye dokunulmaz, iletken zemine veya malzemeye basılmaz.',
                'Akım kesilemiyorsa tahta kutu, plastik veya kuru paspas gibi kuru ve yalıtkan bir zeminde durularak süpürge sapı, tahta sandalye gibi kuru yalıtkan bir cisimle kazazede ile elektrik kaynağı ayrılır.',
                'Islak, nemli veya metal cisimlerle elektrik kaynağı uzaklaştırılmaya çalışılmaz.',
                'Yüksek gerilimde akımın kesildiği yetkililerce bildirilmeden en az 18 m’den fazla yaklaşılmaz; elektrik dağıtım şirketi (186), itfaiye ve 112’den yardım istenir.',
                'Kazazede kendi aracınızla taşınmaz; 112 çağrılır.',
            ]],
        ],
    ],
    'kimyasal' => [
        'aciklama' => 'İşyerinde kullanılan veya depolanan kimyasalların dökülmesi, sızması veya buharlaşması durumunda yayılımı sınırlama, maruziyeti önleme ve tahliye prosedürüdür. Müdahale, ilgili Güvenlik Bilgi Formu (SDS) bilgilerine göre yapılır.',
        'mudahale' => [
            'Dökülmeyi gören kişi çevresini uyarır, alanı terk eder ve koordinatöre haber verir.',
            'Kimyasalın adı ve miktarı belirlenir; ilgili SDS’den tehlikeleri ve müdahale yöntemi kontrol edilir.',
            'Yanıcı kimyasalda tüm ateşleme kaynakları uzaklaştırılır; alanda elektrik anahtarı, telefon ve kıvılcım çıkarabilecek araç kullanılmaz.',
            'Küçük dökülmelere yalnızca eğitimli kişiler uygun KKD ile, emici malzeme / dökülme kiti kullanarak müdahale eder; kanalizasyona akması önlenir.',
            'Büyük sızıntı, zehirli gaz veya bilinmeyen madde durumunda alan tahliye edilir, rüzgârın geldiği yöne doğru (rüzgâr üstüne) uzaklaşılır ve 112 aranır; toplanma alanı rüzgâr altında kalıyorsa yedek alan kullanılır.',
            'Maruz kalan kişi temiz havaya çıkarılır; göz veya cilt teması varsa bol su ile en az 15 dakika yıkanır; 112 ve 114 UZEM’e bilgi verilir.',
        ],
        'olay_sonrasi' => [
            'Toplanan kimyasal ve kirlenmiş malzemeler tehlikeli atık olarak etiketlenip lisanslı firmaya verilir.',
            'Çevreye yayılım varsa Çevre, Şehircilik ve İklim Değişikliği İl Müdürlüğü bilgilendirilir.',
            'Depolama koşulları, kaplar ve dökülme kitleri kontrol edilir, eksikler tamamlanır.',
            'Maruziyet yaşayan çalışanlar işyeri hekimince değerlendirilir; olay kaydı ve DÖF açılır.',
        ],
        // Kaynak: Beyda Yapı ADEP Acil durum 4 + Kimyasal Maddelerle Çalışmalarda SG Önlemleri Yönetmeliği temel istekleri.
        'ek' => [
            ['baslik' => 'KİMYASAL YAYILIMA KARŞI ÖNLEYİCİ VE SINIRLANDIRICI TEDBİRLER', 'maddeler' => [
                'Güncel kimyasal envanteri tutulur; Türkçe Güvenlik Bilgi Formları (SDS) kullanım yerinde erişilebilir bulundurulur.',
                'Kimyasallar orijinal ve etiketli kaplarda saklanır; başka kaba aktarılan kimyasal da etiketlenir.',
                'Kimyasallar özellik ve tehlikelerine göre, uygun kimyasal depolama dolaplarında saklanır; birbiriyle tepkimeye girebilecek (uyumsuz) kimyasallar ayrı tutulur.',
                'Depolama alanlarında sızıntıya yol açacak durumlar engellenir; döküntü tepsisi / havuzu ve uygun havalandırma bulunur.',
                'Yetkisiz kişilerin kimyasal depolama alanlarına girmesi engellenir.',
                'Vana, bağlantı ve hatlar sızıntıya karşı rutin olarak kontrol edilir.',
                'Kullanım ve depolama alanlarında dökülme kiti, gerekli yerlerde göz duşu / acil duş bulundurulur.',
                'Çalışanlara uygun kişisel koruyucu donanım sağlanır, doğru kullanımı sağlanır; acil durum ekiplerine kimyasal yayılıma müdahale eğitimi verilir / aldırılır.',
                'Kimyasal yayılımda işyerini hızla boşaltmak için tahliye planları hazırlanır ve görünür yerlere asılır.',
            ]],
        ],
    ],
    'zehirlenme' => [
        'aciklama' => 'Gaz, buhar, kimyasal madde, yiyecek veya içecek yoluyla meydana gelen zehirlenmelerde kişiyi etkenden uzaklaştırma ve ilk müdahale prosedürüdür.',
        'mudahale' => [
            'Zehirlenme belirtisi gösteren kişi, kurtarıcı kendini tehlikeye atmadan etkenden uzaklaştırılır; kapalı alanda gaz şüphesinde korunmasız girilmez.',
            '112 ve 114 Ulusal Zehir Danışma Merkezi aranır; zehirlenmeye yol açan madde, miktar ve süre bildirilir.',
            'Gaz zehirlenmesinde ortam havalandırılır, kişi temiz havaya çıkarılır; elektrik anahtarı kullanılmaz.',
            'Kişi kusturulmaz, ağızdan bir şey verilmez; bilinci kapalıysa yan yatırılarak solunumu izlenir.',
            'Zehirlenmeye neden olan maddenin ambalajı veya SDS’si sağlık ekibine gösterilmek üzere saklanır.',
            'Aynı kaynaktan etkilenmiş olabilecek diğer kişiler belirlenir ve kontrol edilir.',
        ],
        'olay_sonrasi' => [
            'Zehirlenmenin kaynağı belirlenir ve ortadan kaldırılır.',
            'Ortam ölçümleri ve havalandırma sistemi gözden geçirilir.',
            'İş kazası / meslek hastalığı şüphesinde gerekli bildirimler yapılır.',
            'Çalışanlar kimyasal güvenliği ve hijyen konusunda yeniden bilgilendirilir, DÖF açılır.',
        ],
        'ek' => [
            ['baslik' => 'KARBONMONOKSİT VE KAPALI ALANLARDA ZEHİRLENME', 'maddeler' => [
                'Kapalı ortamda jeneratör, kompresör, motorlu araç, mangal veya soba çalıştırılmaz; ısıtıcılar bacalı ve kontrollü olur. Gerekli yerlerde karbonmonoksit dedektörü bulunur.',
                'Baş ağrısı, baş dönmesi, bulantı veya uyku hali birden fazla kişide aynı anda görülürse gaz zehirlenmesinden şüphelenilir ve ortam derhal boşaltılır.',
                'Tank, silo, kuyu, rögar gibi kapalı alanlara iş izniyle girilir; girişten önce ve çalışma boyunca gaz ölçümü yapılır, havalandırma sağlanır, dışarıda gözcü ve kurtarma ekipmanı bulunur.',
                'Kapalı alanda bayılan kişiyi kurtarmak için solunum cihazı olmadan içeri girilmez; bu tür kazalarda ölümlerin önemli bir kısmı kurtarmaya giren kişilerde olur. 112 aranır, kurtarma eğitimli ve donanımlı kişilerce yapılır.',
                'Göz veya cilt yoluyla kimyasal temasında kimyasal yayılım senaryosundaki yıkama adımı uygulanır.',
            ]],
        ],
    ],
    'patlama' => [
        'aciklama' => 'Gaz kaçağı, toz birikimi, yanıcı buhar veya basınçlı ekipman kaynaklı patlama ve patlama tehlikesi durumlarında tahliye ve ikincil tehlikelerin önlenmesi prosedürüdür.',
        'mudahale' => [
            'Gaz kokusu veya patlama tehlikesi fark edildiğinde elektrik anahtarlarına, fişlere ve kıvılcım çıkarabilecek cihazlara dokunulmaz.',
            'Güvenli olarak erişilebiliyorsa gaz vanası kapatılır; kapı ve pencereler açılarak havalandırma sağlanır.',
            'Alan derhal tahliye edilir; çalışanlar binadan uzak, güvenli toplanma alanına gider.',
            '112 ve doğalgaz acil (187) güvenli noktadan aranır; patlama türü, yeri ve yaralı bilgisi verilir.',
            'Patlama olduysa ikinci patlama ve çökme riskine karşı alana yaklaşılmaz; yaralılara yalnız güvenli alanda müdahale edilir.',
            'Toplanma alanında sayım yapılır; eksik kişilerin son bilinen konumları itfaiyeye bildirilir.',
        ],
        'olay_sonrasi' => [
            'Yetkili ekipler alanı güvenli ilan etmeden binaya girilmez; tesisatlar teknik kontrolden geçmeden devreye alınmaz.',
            'Olay yeri inceleme için korunur; kolluk ve SGK bildirimleri yapılır.',
            'Patlamanın nedeni araştırılır; gaz, toz ve yanıcı madde kontrolleri gözden geçirilir.',
            'DÖF açılır, risk değerlendirmesi ve patlamadan korunma tedbirleri güncellenir.',
        ],
        // Kaynak: Beyda Yapı ADEP Acil durum 8 + Patlayıcı Ortamlar Yönetmeliği; tekrar eden maddeler çıkarıldı.
        'ek' => [
            ['baslik' => 'PATLAMAYA KARŞI ÖNLEYİCİ VE SINIRLANDIRICI TEDBİRLER', 'maddeler' => [
                'Patlayıcı ortam oluşabilecek işyerlerinde patlamadan korunma dokümanı hazırlanır; bu alanlar patlayıcı ortamın oluşma sıklığı ve süresine göre bölgelere ayrılır, buralarda uygun kategoride (Ex işaretli) elektrikli ve elektriksiz ekipman kullanılır.',
                'Kazan, kompresör ve basınçlı kaplar yılda bir periyodik kontrolden geçirilir; kazan dairesine yetkisiz kişilerin girmesi önlenir.',
                'Gaz tüpleri ve gaz hatları periyodik olarak kontrol edilir; kullanılmayan vanalar kapalı tutulur.',
                'Gaz dedektörleri doğru yere takılır: doğalgaz (havadan hafif) için tavana yakın, LPG (havadan ağır) için zemine yakın. Yakıtla çalışan cihazların bulunduğu yerlerde karbonmonoksit dedektörü bulunur.',
                'Dedektörler, gaz akışını kesen acil durum vanası ve ışıklı / sesli alarm sistemiyle bağlantılı çalışır.',
                'Yanıcı toz (metal, ahşap, un vb.) birikimi düzenli olarak temizlenir; tozlar basınçlı havayla üflenmez, uygun vakumlu sistemle alınır.',
                'Uygun havalandırma sağlanır; kimyasal atıklar düzenli ve ısı kaynaklarından uzak depolanır.',
                'Acil çıkışların ve söndürme ekipmanlarının gösterildiği tahliye planları işyerinde görünür yerlere asılır.',
            ]],
        ],
    ],
    'gida_zehirlenmesi' => [
        'aciklama' => 'İşyeri yemekhanesinde veya dışarıdan temin edilen yemeklerden kaynaklanan toplu gıda zehirlenmelerinde ilk müdahale ve kaynağın kontrolü prosedürüdür.',
        'mudahale' => [
            'Bulantı, kusma, ishal, karın ağrısı gibi belirtiler birden fazla kişide görülürse durum derhal işyeri hekimine ve koordinatöre bildirilir.',
            'Ağır belirti gösterenler için 112 aranır; 114 Ulusal Zehir Danışma Merkezi’nden bilgi alınır.',
            'Şüpheli yiyecek ve içeceklerin dağıtımı durdurulur; numuneler atılmadan saklanır.',
            'Etkilenen kişilerin listesi ve ne yedikleri kayıt altına alınır.',
            'İlaç verilmez, kusturulmaz; sağlık ekibinin yönlendirmesine göre hareket edilir.',
        ],
        'olay_sonrasi' => [
            'İl Sağlık Müdürlüğü ve Tarım ve Orman İl / İlçe Müdürlüğü (Alo 174 Gıda Hattı) bilgilendirilir.',
            'Yemek hizmeti veren firma ile birlikte kaynak araştırılır; saklanan numuneler incelemeye verilir.',
            'Mutfak hijyeni, soğuk zincir ve personel sağlık kontrolleri gözden geçirilir.',
            'Olay kaydı ve DÖF açılır.',
        ],
        // Kaynak: Beyda Yapı ADEP Acil durum 10; numune saklama süresi, yemek firması belgesi ve içme suyu eklendi.
        'ek' => [
            ['baslik' => 'GIDA ZEHİRLENMESİNE KARŞI ÖNLEYİCİ VE SINIRLANDIRICI TEDBİRLER', 'maddeler' => [
                'Gıda ürünlerinin son tüketim tarihleri kontrol edilir.',
                'Yemek dışarıdan temin ediliyorsa yemek firmasının işletme kayıt / onay belgesi bulunur; yemekler hijyenik koşullarda ve uygun sıcaklıkta (sıcak yemek sıcak, soğuk yemek soğuk) taşınır.',
                'Her öğünden numune alınır ve 72 saat buzdolabında saklanır.',
                'Yemek servisi yapılan tabak, çatal, tepsi vb. temiz tutulur.',
                'Yemek servisi yapan personel eldiven, bone ve iş kıyafeti kullanır; hijyen eğitimi alır ve sağlık kontrollerinden geçer. Tüm personele genel hijyen eğitimi verilir.',
                'İçme suyu güvenli kaynaktan sağlanır: kapalı ambalajlı su veya düzenli temizlenen ve kontrol edilen su deposu.',
                'Acil durum irtibat numaraları görünür yerlere asılır.',
            ]],
        ],
    ],
    'asansor' => [
        'aciklama' => 'Asansör arızası veya elektrik kesintisi nedeniyle asansörde mahsur kalınması durumunda güvenli kurtarma prosedürüdür. Kurtarma yalnızca asansör firması veya itfaiye tarafından yapılır.',
        'mudahale' => [
            'Mahsur kalan kişi alarm butonuna basar veya telefonla yardım ister; kabin kapısını zorlamaz.',
            'Haber alan kişi, kabindekilerle konuşarak sakin kalmalarını sağlar ve sağlık durumlarını öğrenir.',
            'Asansör bakım firması aranır; ulaşılamıyorsa veya kabinde rahatsızlanan kişi varsa 112 aranır.',
            'Yetkisiz kişiler kapıları açmaya veya kabini elle hareket ettirmeye çalışmaz.',
            'Yangın durumunda asansör kullanılmaz; asansörde mahsur kalan varsa itfaiyeye derhal bildirilir.',
        ],
        'olay_sonrasi' => [
            'Asansör, bakım firması kontrol etmeden yeniden kullanıma açılmaz.',
            'Arızanın nedeni ve kurtarma süresi kayıt altına alınır.',
            'Periyodik kontrol ve bakım kayıtları gözden geçirilir.',
        ],
        // Kullanıcı onaylı metin (Beyda planında asansör bölümü yok); Asansör İşletme, Bakım ve Periyodik Kontrol Yönetmeliği esas.
        'ek' => [
            ['baslik' => 'ASANSÖR GÜVENLİĞİ İÇİN ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'Asansörün bakımı yetkili servis tarafından ayda bir yapılır; yılda bir yetkili muayene kuruluşunca periyodik kontrolü yaptırılır. Kontrol etiketi kabinde görünür durur; kırmızı etiketli (güvensiz) asansör kullanılmaz.',
                'Kabindeki alarm butonu, iletişim cihazı ve acil aydınlatma çalışır durumda tutulur; bakım firmasının telefonu kabinde ve asansör girişinde asılır.',
                'Her katta “Yangın ve depremde asansör kullanılmaz” levhası bulunur.',
                'Makine dairesi ve kumanda panosu kilitli tutulur; yalnız yetkili kişiler girer.',
                'Yük asansörlerine insan binmez; asansörlerde taşıma kapasitesi aşılmaz.',
            ]],
        ],
    ],
    'salgin' => [
        'aciklama' => 'Salgın şüphesinde işyeri hekimi ve sağlık otoritesinin güncel yönlendirmeleri uygulanır. Hijyen, uygun havalandırma, ortak alan temizliği ve belirti gösteren çalışanın değerlendirilmesiyle yayılım önlenir.',
        'mudahale' => [
            'Şüpheli veya teyitli vaka tespit edildiğinde derhal işyeri hekimine ve acil durum koordinatörüne bildirilir.',
            'Belirti gösteren kişi, tıbbi durumu uygunsa diğer kişilerden ayrılır ve sağlık kuruluşuna yönlendirilir; ağır durumda 112 aranır. Korunma tedbirleri etken ve sağlık otoritesinin önerisine göre belirlenir.',
            'Gerekli temas bilgileri gizlilik korunarak işyeri hekimi / sağlık otoritesinin değerlendirmesine sunulur.',
            'İlgili bölge boşaltılır, havalandırılır ve uygun dezenfektan ile temizlenir.',
            'Bildirim yükümlülüğü ve kanalı hastalığa göre işyeri hekimi / sağlık kuruluşunca değerlendirilir.',
            'Etken ve maruziyete uygun hijyen malzemesi ve gerekli KKD sağlanır; maske ve eldiven kullanımı etkene ve sağlık otoritesinin önerisine göre belirlenir, gerekmedikçe genel zorunluluk getirilmez.',
            'Yemekhane, soyunma odası ve tuvalet gibi ortak alanlar sık aralıkla temizlenir.',
        ],
        'olay_sonrasi' => [
            'Olay ve alınan önlemler kaydedilir; zorunlu sağlık bildirimleri ilgili sağlık sistemi üzerinden yetkili kişilerce yapılır. İSG-KATİP salgın bildirimi kanalı olarak kullanılmaz.',
            'Çalışanlara hijyen ve korunma eğitimi yenilenir.',
            'Bina havalandırma sistemleri ve filtre değişimi gözden geçirilir.',
            'Risk değerlendirmesi biyolojik etken başlığı altında güncellenir.',
            'Yeni vakalar için aktif gözetim süresi belirlenir.',
        ],
        // Kullanıcı onaylı metin (Beyda planında salgın bölümü yok).
        'ek' => [
            ['baslik' => 'SALGINA KARŞI ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'El yıkama noktaları, sabun ve kâğıt havlu bulundurulur; ortak bardak ve havlu kullanılmaz.',
                'Belirtisi olan çalışan işe gelmeden amirine ve işyeri hekimine haber verir; hasta çalışan işe gelmeye zorlanmaz.',
                'Yemekhane, soyunma odası, servis araçları ve varsa yatakhane / konteyner gibi kalabalık ortak alanlar havalandırılır ve düzenli temizlenir.',
                'Aşılama ihtiyacı, yapılan iş ve maruziyete göre işyeri hekimince değerlendirilir.',
                'Kritik görevler için yedek personel belirlenir; salgın döneminde işin sürekliliği planlanır.',
                'Sağlık otoritesinin duyuruları takip edilir, çalışanlara güncel bilgi verilir.',
            ]],
        ],
    ],
    'sel' => [
        'aciklama' => 'Şiddetli yağış, dere taşması veya altyapı arızası kaynaklı sel ve su baskını durumunda can ve mal kaybını önlemeye yönelik tahliye, enerji kesme ve koruma prosedürüdür.',
        'mudahale' => [
            'AFAD veya Meteoroloji uyarısı alındığında acil durum koordinatörü ekibi toplar ve hazırlık başlatılır.',
            'Su basması beklenen düşük kotlar güvenli ve yüksek bir alana tahliye edilir. Güzergâh su altında veya akıntılıysa kullanılmaz; mahsur kalanlar konumlarını 112’ye bildirir.',
            'Enerji yalnızca kuru ve güvenli bir noktadan yetkili kişi tarafından kesilir; su içindeki pano / vanaya yaklaşılmaz.',
            'Kimyasal ve kritik kayıtlar önceden güvenli kotta tutulur; olay sırasında eşya taşımak için tahliye geciktirilmez.',
            'Tahliye yolları su seviyesine göre yeniden belirlenir; asansör kesinlikle kullanılmaz.',
            'Asıl alan güvensizse Ek-7’de belirtilen yedek alana gidilir; alanlar kullanılmadan önce güvenliği değerlendirilir.',
            '112 ve AFAD aranır; mahsur kalan kişi varsa konum bildirilir.',
        ],
        'olay_sonrasi' => [
            'Bina elektriği yetkili kişiye kontrol ettirilmeden tekrar enerjilendirilmez.',
            'Su çekildikten sonra mekanik tahliye ve dezenfeksiyon yapılır.',
            'Tehlikeli atık veya kimyasal sızıntısı varsa Çevre İl Müdürlüğü bilgilendirilir.',
            'Hasar ve tedbirler kaydedilir; iş kazası varsa SGK / kolluk bildirimi, çevresel etki varsa ilgili kurum bildirimi yapılır.',
            'Acil durum planı sel senaryosu için güncellenir ve tatbikat planlanır.',
        ],
        // Kaynak: Beyda Yapı ADEP Acil durum 3 (önleyici tedbir içermiyordu; kullanıcı onaylı metin). Sel sırasında set çekme / malzeme kurtarma talimatları sınırlandırıldı.
        'ek' => [
            ['baslik' => 'SELE KARŞI ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'Yağmur suyu kanalları, ızgaralar ve oluklar düzenli olarak temizlenir.',
                'Bodrum gibi düşük kotlarda kimyasal, elektrik panosu, önemli kayıt ve değerli ekipman bulundurulmaz.',
                'Su baskını riski olan yerlerde kum torbası, su bariyeri ve dalgıç pompa hazır tutulur.',
                'Kazı ve temel çukurlarında su birikmesine karşı drenaj yapılır.',
                'Meteoroloji uyarıları (MeteoUyarı) ve AFAD duyuruları takip edilir.',
            ]],
            ['baslik' => 'SU BASKININDA GÜVENLİ DAVRANIŞ', 'maddeler' => [
                'UNUTMAYIN: Hızla akan 15–20 cm derinlikteki su bir insanı düşürüp sürükleyebilir; yaklaşık 60 cm akan su bir aracı sürükler. Akan suya yürüyerek veya araçla girilmez.',
                'Akıntısız, küçük su baskınında (ör. bodruma yağmur suyu dolması) elektrik kuru ve güvenli noktadan kesildikten sonra eğitimli personel kum torbası veya pompa ile önlem alabilir.',
                'Sel ve akıntılı suda set çekmeye, kanal açmaya veya malzeme kurtarmaya çalışılmaz; can güvenliği önce gelir, gerekirse 112 ile itfaiyeden yardım istenir.',
            ]],
        ],
    ],
    'yildirim' => [
        'aciklama' => 'Şiddetli rüzgâr, fırtına, hortum veya yıldırım düşmesi gibi atmosferik olaylar sırasında açık ve kapalı alanda bulunan çalışanların güvenliğini sağlamaya yönelik prosedürdür.',
        'mudahale' => [
            'Turuncu veya kırmızı meteoroloji uyarısında ya da fırtına yaklaştığında dış mekân çalışmaları durdurulur; uyarı döneminde gevşek malzemeler fırtına başlamadan güvenceye alınır.',
            'Çalışanlar sağlam kapalı yapıya alınır; camlardan ve elektrik ileten yüzeylerden uzak tutulur. Güvensiz hafif yapılarda beklenmez.',
            'Varsa çatı, merdiven, dış bakım ve yüksekte çalışmalar önceden durdurulur; ekipman güvenli duruma alınır.',
            'Açık alanda ağaç, direk ve su kenarından uzaklaşılıp güvenli kapalı yapıya veya kapalı metal tavanlı araca geçilir. Yıldırım sırasında açık alan güvenli kabul edilmez.',
            'Cihaz ve tesisat kontrolleri fırtına başlamadan yapılır; yıldırım sırasında fiş, pano veya paratonere müdahale edilmez.',
            'Gevşek malzemeler uyarı döneminde güvenceye alınır; fırtına sırasında dışarı çıkılıp bağlama yapılmaz.',
            'Varsa jeneratör yalnızca yetkili kişi tarafından, üretici talimatına ve güvenli elektrik bağlantısına uygun kullanılır; kapalı hacimde çalıştırılmaz.',
        ],
        'olay_sonrasi' => [
            'Yapı, paratoner ve elektrik tesisatı yetkili kişiye kontrol ettirilir.',
            'Çatı, levha, anten ve dış kaplama gevşeklikleri için bina kontrolü yapılır.',
            'Yangın ve sızıntı riski açısından bina taraması yapılır.',
            'Hasar tutanağa bağlanır; gerekirse sigorta bildirimi yapılır.',
            'Bir sonraki uyarı senaryosu için tatbikat tarihi belirlenir.',
        ],
        // Kullanıcı onaylı metin (Beyda planında yıldırım / fırtına bölümü yok).
        'ek' => [
            ['baslik' => 'FIRTINA VE YILDIRIMA KARŞI TEDBİRLER', 'maddeler' => [
                'Meteoroloji uyarıları (MeteoUyarı: sarı, turuncu, kırmızı) takip edilir; uyarıyı alıp çalışanlara duyurmaktan sorumlu kişi belirlenir.',
                '30/30 kuralı: Şimşek ile gök gürültüsü arasındaki süre 30 saniyeden kısaysa hemen sağlam kapalı yapıya geçilir; son gök gürültüsünden sonra 30 dakika dolmadan dışarı çıkılmaz.',
                'Varsa vinç ve kaldırma ekipmanları üreticinin belirttiği rüzgâr hızı sınırında durdurulur ve güvenli konuma alınır; rüzgâr hızı 45 km/saate ulaştığında yüksekte çalışma yapılmaz.',
                'Paratoner (yıldırımdan korunma tesisatı) yılda bir yetkili kişilerce kontrol ettirilir.',
                'Çatı kaplaması, levha, tabela, iskele, konteyner ve geçici yapılar rüzgâra karşı sabitlenir; devrilme tehlikesi olan ağaçlar budanır.',
            ]],
        ],
    ],
    'bomba' => [
        'aciklama' => 'Telefon, mektup veya sözlü olarak bomba ihbarı alındığında ya da şüpheli paket tespit edildiğinde uygulanan tahliye ve emniyet prosedürüdür.',
        'mudahale' => [
            'İhbarı alan kişi sakin kalır, karşı tarafı dinler ve hat kesilmeden konuşmayı mümkün olduğunca uzatır.',
            'Konuşma süresince arayanın ses tonu, aksanı ve arka plan sesleri not edilir.',
            'Güvenli noktadan gecikmeden 112 aranır; mümkünse diğer kişi çağrıyı yaparken ihbar bilgileri kaydedilir.',
            'Şüpheli pakete kesinlikle dokunulmaz, koklanmaz, taşınmaz, açılmaz.',
            'Telsiz ve cep telefonu paket yakınında kullanılmaz; sinyal patlayıcıyı tetikleyebilir.',
            'Tüm personel sessizce ve panik yapılmadan tahliye edilir.',
            'Tahliye yolu şüpheli paket yakınından geçmez; yedek çıkış kullanılır.',
        ],
        'olay_sonrasi' => [
            'Bomba imha veya yetkili emniyet ekibinin güvenli beyanı olmadan binaya girilmez.',
            'Olay tutanağı hazırlanır, tanık ifadeleri toplanır.',
            'Çalışanlara psiko-sosyal destek hizmeti duyurulur.',
            'Güvenlik prosedürleri ve giriş kontrolleri gözden geçirilir.',
        ],
        // Kullanıcı onaylı metin (Beyda planında bomba ihbarı bölümü yok).
        'ek' => [
            ['baslik' => 'BOMBA İHBARI VE ŞÜPHELİ PAKET', 'maddeler' => [
                'Santral / ana telefonun yanında ihbar formu bulunur. İhbarı alan kişi şunları sorar: Bomba ne zaman patlayacak? Nerede? Neye benziyor? Neden konuldu? Siz kimsiniz? Cevaplar kelimesi kelimesine yazılır; arayan numara ve saat not edilir.',
                'Şüpheli paket belirtileri: gönderen belirsiz veya adres eksik, aşırı pul veya bant, yağ lekesi, dışarı çıkmış tel ya da folyo, koku, tik tak sesi, dengesiz ağırlık. Böyle bir pakete dokunulmaz, alan boşaltılır, 112 aranır.',
                'Tahliye kararını acil durum koordinatörü verir; gerekirse emniyetin yönlendirmesine uyulur. Toplanma alanı binadan, cam cephelerden, park halindeki araçlardan ve çöp kutularından uzak seçilir.',
                'Emniyet talep ederse çalışanlar kendi bölümlerinde yabancı bir nesne olup olmadığını gözle kontrol eder; bulunan nesneye dokunulmaz, yeri bildirilir.',
            ]],
        ],
    ],
    'yuksekten_dusme' => [
        'aciklama' => 'Yüksek mekânda, iskelede, çatıda veya merdivende çalışan kişinin düşmesi durumunda hızlı ve doğru ilk müdahale ile ileri yaralanmaları önleme prosedürüdür.',
        'mudahale' => [
            'Olay yerine giden ilk kişi öncelikle kendi güvenliğini sağlar ve çevreyi emniyete alır.',
            'Düşen kişiye gereksiz dokunulmaz; aksi kanıtlanmadıkça omurga hasarı kabul edilir.',
            '112 acil servis aranır; lokasyon ve tahmini düşüş yüksekliği bildirilir.',
            'İlkyardımcı bilinç ve normal solunumu değerlendirir; temel yaşam desteği gereksiniminde eğitimine ve 112 yönlendirmesine uygun hareket eder.',
            'Kanama varsa direkt baskı ile durdurulur, kırık şüphesi olan uzuv sabitlenir.',
            'Çalışan asla tek başına ayağa kaldırılmaya çalışılmaz.',
            'Çalışma alanı izole edilir ve koruma altına alınır.',
        ],
        'olay_sonrasi' => [
            'İşveren, 4/a kapsamındaki iş kazasını yetkili kolluğa derhal, SGK’ye kazadan sonraki üç iş günü içinde bildirir. Bildirim SGK’nın iş kazası bildirim kanalıyla yapılır; İSG-KATİP kullanılmaz.',
            'Kök neden analizi yapılır (emniyet kemeri, korkuluk eksikliği, ağ sistemi vb.).',
            'Düşme önleyici ekipman kontrolü ve eğitim yenilenir.',
            'DÖF açılır, ilgili risk değerlendirmesi güncellenir.',
        ],
        // Her işyerine uyan yüksekte çalışma ekleri (kaynak: Beyda Yapı ADEP); şantiyeye özgü
        // bloklar (iskele, korkuluk, çatı) config/acil_durum_sektorler.php > insaat.
        'ek' => [
            ['baslik' => 'ASKIDA KALAN ÇALIŞANA ERİŞİM, TAHLİYE VE İLKYARDIM', 'maddeler' => [
                'Yüksekten düşme sonucu askıda kalan çalışana hızla müdahale edilir. Koşumda hareketsiz asılı kalmak kısa süre için bile ciddi fizyolojik zarar (askı travması) verebilir; hemen ele alınmazsa ölüme neden olabilir.',
                'Askıda kalan personele 10 dakika içinde müdahale edilir.',
                '30 dakikayı aşan çalışmalarda iş oturağı (footloop) kullanılır; personel ayağını basabileceği bir platforma geçerek kan dolaşımını düzenler.',
                'Askıda kalan personelin çevredeki engellere çarpmış olabileceği düşünülerek boyun ateli takılır.',
                'Personel, eğitimli kişilerce uygun kurtarma teknikleri ve ekipmanlarıyla indirilir. İndirilen kişiye ilkyardımcı güncel ilkyardım eğitimine ve 112 yönlendirmesine göre müdahale eder; omurga yaralanması şüphesinde gereksiz hareket ettirilmez.',
                'Kendine gelmeyen kazazede beklenmeden 112 ile hastaneye ulaştırılır.',
            ]],
            ['baslik' => 'YÜKSEKTE KURTARMA', 'maddeler' => [
                'Kurtarma çeşitleri planlanır: kişinin kendini kurtarması, ekip arkadaşlarının kurtarması, ekip çağrılarak yapılan kurtarma.',
                'Kurtarma için gerekli ekipmanlar işyerinde hazır bulunur; kurtarmadan kimin sorumlu olacağı, ne yapacağı ve haberleşme yöntemi önceden belirlenir.',
                'Kurtarma personeli teknik yeterliliğe sahip, soğukkanlı, ekipmanı kullanabilen ve operasyonu yönetebilen kişilerden seçilir; eğitimleri eksiksiz olur.',
                'Kurtarma kaldırma cihazıyla kurtarmada: kurtarıcı, askıdaki personelin baş hizasına ankraj noktası oluşturur, cihazı bu noktaya bağlar, personeli yukarı çekerek lanyarddan kurtarır ve cihazı indirme konumuna alarak üreticinin belirlediği kontrollü hızla aşağı tahliye eder.',
            ]],
            ['baslik' => 'PARAŞÜT TİPİ EMNİYET KEMERİ KULLANIMI', 'maddeler' => [
                'Düşmeyi engelleyici donanımlar her kullanımdan önce kontrol edilir; mümkün olduğunca vücudun üst bölümüne ve yalnız taşıma gücü uygun sabitleme noktalarına bağlanır.',
                'Kazara açılmayı önlemek için yalnız kilitli kancalar kullanılır; tırmanmada donanım kemerin ön kancasına takılır.',
                'Kayma ve düşme tehlikesinde enerji emici sistemler kullanılır.',
                'Halat ve bağlantı elemanları keskin kenarlardan geçirilmez, düğümlenmez, ek yapılarak uzatılmaz.',
                'Baret mutlaka kullanılır. Hasar görmüş veya düşme sonucu direnci azalmış donanım tekrar kullanılmaz.',
            ]],
            ['baslik' => 'MERDİVEN KURULUMU VE KULLANIMI', 'maddeler' => [
                'Merdiven her gün kullanım öncesi kontrol edilir ve sabitlenir; zemin sağlam ve düz, üst ucu sağlam bir yere dayalı, basamaklar temiz, çevresi düzenli olur.',
                'Merdivende çalışma süresi kısa (en fazla 30 dakika), taşınan malzeme hafif (en fazla 10 kg) olur.',
                'Merdiven açısı 75° olur (4 m yüksekliğe 1 m ayak mesafesi). Tırmanırken her zaman tutunulur, aşırı uzanılmaz.',
                'En üstteki 2 basamakta çalışılmaz; bu kısım tutunmak içindir.',
            ]],
        ],
    ],
    'heyelan' => [
        'aciklama' => 'Bu senaryo, işyeri veya yakın çevresinde şev, eğimli arazi ya da toprak kayması tehlikesi bulunması halinde uygulanır. Uygulanabilirlik Ek-6’da yerinde değerlendirilir.',
        'mudahale' => [
            'Yer çatlağı, ağaç eğilmesi, anormal ses veya su sızıntısı gibi ön belirtilerde alan boşaltılır.',
            'Çalışanlar önceden belirlenmiş, kayma alanı dışındaki güvenli güzergâhla uzaklaşır; hareketli zemine ve heyelan akışına girilmez.',
            '112 Acil Çağrı Merkezi (AFAD/İtfaiye) aranır; konum ve tahmini etki alanı bildirilir.',
            'Toprak altında kalan kişi varsa bilinen son konum bildirilir; gönüllü kazıya başlanmaz.',
            'İş makineleri varsa güvenli konumda durdurulur; tesisata yalnızca yetkili kişi güvenli noktadan müdahale eder.',
        ],
        'olay_sonrasi' => [
            'Bölge jeolog veya yetkili kişi raporu olmadan yeniden kullanıma açılmaz.',
            'Hasar kaydedilir; iş kazası oluşmuşsa kolluk ve SGK bildirimleri yapılır.',
            'Şev açısı, drenaj ve istinat duvarı tasarımı yeniden değerlendirilir.',
            'Erken uyarı sistemi (eğim ölçer, çatlak sensörü) gözden geçirilir.',
            'Çalışanlara heyelan senaryolu tatbikat planlanır.',
        ],
        // Kullanıcı onaylı metin; şantiyeye özgü kazı / göçük bloğu config/acil_durum_sektorler.php > insaat.
        'ek' => [
            ['baslik' => 'ŞEV VE ZEMİN İÇİN ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'Eğimli arazi varsa şevler ve istinat duvarları özellikle yağıştan sonra kontrol edilir; çatlak ve oturmalar izlenir.',
                'Drenaj kanalları açık tutulur; şev üstüne yük, malzeme veya hafriyat konmaz.',
            ]],
        ],
    ],
    'radyasyon' => [
        'aciklama' => 'Bu senaryo, işyerinde kaynak bulunması veya çevresel radyolojik olayın işyerini etkilemesi halinde uygulanır. Çevresel olaylarda AFAD, NDK ve 112 yönlendirmesi esas alınır.',
        'mudahale' => [
            'Kaynağa yaklaşılmaz ve dokunulmaz; güvenli uzaklığa geçilir. Dış kaynaklı olayda tahliye / kapalı alanda kalma kararı yetkili makam yönlendirmesine göre uygulanır.',
            'Alanın giriş ve çıkışları kapatılır, radyasyon uyarı işaretleri yerleştirilir.',
            '112’ye bilgi verilir; işyerinde kaynak varsa yetkili radyasyondan korunma sorumlusu ve NDK ilgili usule göre bilgilendirilir.',
            'Maruz kalma ile radyoaktif bulaşma birbirinden ayrılır; kişilerin kontrolü yetkili ekiplerce yapılır.',
            'Bulaşma şüphesinde yayılım önlenir; arındırma, sağlık ve uzman ekip yönlendirmesine göre yapılır. Her maruziyette gelişigüzel yıkama uygulanmaz.',
            'Acil yaralanma tedavisi geciktirilmez; 112’ye olası radyoaktif bulaşma bilgisi verilir.',
        ],
        'olay_sonrasi' => [
            'Radyasyon ölçümü yapılana kadar bölge kontrollü alan olarak ilan edilir.',
            'Kaynak kaybı / radyolojik olay, lisans ve ilgili NDK düzenlemelerinde öngörülen usul ve süreyle yetkili sorumlu tarafından bildirilir; bildirim için beklenmez.',
            'Olay, ölçüm ve sağlık kayıtları ilgili mevzuatın öngördüğü süre ve gizlilik şartlarıyla saklanır.',
            'Personel sağlık taraması ve psiko-sosyal destek planlanır.',
        ],
    ],
    'trafik' => [
        'aciklama' => 'İşyerinde fiilen bulunan taşıma ekipmanı, teslimat aracı veya servis aracının karıştığı kazalarda uygulanır. Yaya yolları ve araç hareketleri ayrılır; park ve teslimat alanları kaçışı engellemez.',
        'mudahale' => [
            'Kaza alanı işaret konileri veya uyarı şeritleri ile çevrelenir.',
            'Araç güvenliyse durdurulur; yakıt / gaz kaçağı olan ortamda akü kutbu sökülmez ve kıvılcım oluşturacak müdahale yapılmaz.',
            'Yaralılar için 112 aranır; araç altında kalan kişi varsa yetkili gelmeden araç kaldırılmaz.',
            'Sızıntıda alan boşaltılır, ateşleyici kaynaklardan uzak durulur; güvenli küçük dökülmeye yalnız eğitimli kişi uygun emiciyle müdahale eder.',
            'Diğer araçlar bekletilir; görgü tanıkları belirlenir.',
            'Güvenlik kamera kayıtları (CCTV) güvence altına alınır.',
        ],
        'olay_sonrasi' => [
            'İşveren iş kazasını yetkili kolluğa derhal ve SGK’ye üç iş günü içinde bildirir; kayıtlar korunur.',
            'Sürücünün operatör belgesi, periyodik muayene ve eğitim durumu kontrol edilir.',
            'Sürüş güzergâhı, hız limiti ve yaya yolları yeniden işaretlenir.',
            'DÖF açılır, sürücülere yeniden eğitim verilir.',
        ],
        // Kaynak: Beyda Yapı ADEP Acil durum 11 + 3.8; maddi hasarlı kaza KTK Md.81’e göre düzeltildi, 155 -> 112, 5510 Md.13 eklendi.
        'ek' => [
            ['baslik' => 'İŞYERİ ARAÇLARIYLA KARAYOLUNDA TRAFİK KAZASI', 'maddeler' => [
                'Trafik için ek tehlike oluşturmadan hemen durulur; reflektör ve ışıklı işaret konur, yansıtıcı yelek giyilir.',
                'Kaza yalnız maddi hasarlıysa araçlar trafiği engellemeyecek şekilde yol kenarına çekilir; taraflar anlaşırsa kaza tespit tutanağı doldurulur. Anlaşma sağlanamazsa veya kamu malına zarar varsa 112 aranarak kolluk çağrılır. Her durumda yönetime haber verilir.',
                'Ölümlü veya yaralanmalı kazada ve vurup kaçmada 112 aranır; araç yerinden oynatılmaz, olay yerinden ayrılınmaz. Yaralı, yangın gibi ek bir tehlike yoksa hareket ettirilmez.',
                'Görev sırasında veya işverenin sağladığı servis aracıyla toplu taşıma sırasında olan kaza iş kazası sayılır; işveren SGK’ye kazadan sonraki üç iş günü içinde bildirir.',
            ]],
            ['baslik' => 'TRAFİK KAZALARINA KARŞI ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                'Sürücülerin sürücü belgesi geçerli ve araca uygundur; personel servisi sürücülerinde mesleki yeterlilik (SRC) ve psikoteknik belgesi bulunur.',
                'Araçların muayenesi, sigortası ve bakımı zamanında yapılır; sürücüler ve araçlar kurallara uygunluk bakımından düzenli denetlenir.',
                'Araçlarda ilkyardım çantası, yangın söndürücü ve reflektör bulunur.',
                'Emniyet kemeri takılır; sürüş sırasında elde telefon kullanılmaz; yorgun araç kullanılmaz, dinlenme sürelerine uyulur.',
            ]],
        ],
    ],
    'bogulma' => [
        'aciklama' => 'İşyerinde su deposu, havuz veya benzeri suya düşme tehlikesi varsa uygulanır. Böyle bir alan yoksa uygulanmadığı kaydedilir. Kapalı hacimde kurtarma ayrı teknik kurtarma gerektirir.',
        'mudahale' => [
            'Kurtarıcı suya veya kapalı tanka girmez. Güvenli kıyıdan erişilebiliyorsa can simidi / halat uzatılır; 112’den teknik kurtarma istenir.',
            '112 aranır; lokasyon ve su tipi (tatlı/tuzlu/kimyasal) bildirilir.',
            'Sudan çıkarılan kişi güvenli alanda değerlendirilir; olası travmaya dikkat edilir.',
            'İlkyardımcı, boğulmaya özgü güncel temel yaşam desteği eğitimi ve 112 yönlendirmesine göre müdahale eder.',
            'Ağız içinde yalnızca görülen ve kolayca alınabilen yabancı cisim çıkarılır; körlemesine parmak sokulmaz.',
            'Kişi soğuksa ısıtılır, ıslak kıyafeti çıkarılır.',
        ],
        'olay_sonrasi' => [
            'Solunum geri gelse de kişi mutlaka hastaneye sevk edilir (geç boğulma riski).',
            'Kapalı tankta uygun izin, atmosfer ölçümü, izolasyon ve teknik kurtarma düzeni sağlanmadan yeniden giriş yapılmaz.',
            'Korkuluk, can simidi ve halat ekipmanları kontrol edilir.',
            'Olay tutanağı ve iş kazası bildirimi yapılır.',
        ],
    ],
    'basincli_kap' => [
        'aciklama' => 'İşyerinde kazan, kompresör, hidrofor veya basınçlı tüp varsa uygulanır. Ekipmanların bakımı, periyodik kontrolü ve emniyet donanımları takip edilir; kontrolsüz basınç yükselmesine izin verilmez.',
        'mudahale' => [
            'Patlama duyulduğunda alan derhal boşaltılır, ekipmana yaklaşılmaz.',
            'Enerji yalnızca güvenli noktadan yetkili kişi tarafından kesilir; patlama bölgesindeki pano ve vanalara yaklaşılmaz.',
            '112 Acil Çağrı Merkezi aranır; olayın tipi ve konumu bildirilir.',
            'Yaralılara yalnız güvenli alanda, ilkyardım eğitimi ve 112 yönlendirmesi kapsamında müdahale edilir.',
            'İkinci patlama ve yangın riski 112’ye bildirilir; profesyonel ekiplere ekipman ve içerik bilgisi verilir.',
            'Komşu basınçlı kapların vanaları açılmaz, manuel boşaltma yapılmaz. Basınç düşürme ve izolasyon, güvenli yöntemle yetkili teknik ekipçe değerlendirilir.',
            'Personel ikinci patlama riskine karşı emniyet mesafesine alınır.',
        ],
        'olay_sonrasi' => [
            'Kurtarma ve tehlikeyi önleme dışında olay yeri korunur; kolluk / SGK ve gerekiyorsa ilgili kurum bildirimleri yapılır.',
            'Periyodik muayene belgeleri ve sertifikalar gözden geçirilir.',
            'DÖF açılır; arıza nedenine göre yetkili teknik kişi onarım, parça değişimi ve testleri belirler.',
            'Aynı tip ekipmanlar için erken muayene planlanır.',
        ],
    ],
    'makine_arizasi' => [
        'aciklama' => 'İşyerinde fiilen bulunan pres, torna, CNC, freze, kesme, taşlama, konveyör ve benzeri makinelerde sıkışma / ezilmede uygulanır. Makine listesi Ek-5’e kaydedilir. Koruyucular ve kilitlemeler iptal edilmez; temizlik ve bakımda enerji izolasyonu yapılır.',
        'mudahale' => [
            'Makine acil stop butonuna basılır, enerji kesilir ve LOTO (Lockout-Tagout) uygulanır.',
            'Hareketli parça zorlanmaz; depolanmış enerji ve ilave hareket ihtimali değerlendirilir.',
            '112 aranır; sıkışan uzuv ve makine tipi bildirilir.',
            'Makine gelişigüzel ters döndürülmez. Sıkışan kişinin kurtarılması, üretici yöntemi ve enerji izolasyonu doğrulanarak yetkili teknik ve profesyonel kurtarma ekiplerince yürütülür.',
            'Kopan uzuv temiz, sızdırmaz poşete alınır; bu poşet buzlu su bulunan ikinci poşete konur. Uzuv buz / suyla doğrudan temas ettirilmeden kişiyle birlikte sağlık ekibine teslim edilir.',
            'Kanama, eğitimli ilkyardımcı tarafından güncel eğitimi ve 112 yönlendirmesine göre kontrol edilir; yaşamı tehdit eden kanama geciktirilmez.',
        ],
        'olay_sonrasi' => [
            'LOTO prosedürü gözden geçirilir ve kayıt altına alınır.',
            'Koruyucu çitler, fotosel, iki el kontrol sistemleri kontrol edilir.',
            'İş kazası bildirimi yapılır ve DÖF açılır.',
            'Operatöre psiko-sosyal destek sağlanır; ekibe LOTO eğitimi tekrarlanır.',
        ],
    ],
];
