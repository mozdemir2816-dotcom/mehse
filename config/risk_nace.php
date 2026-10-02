<?php

/*
|--------------------------------------------------------------------------
| NACE Yol Haritası (isgsuite "NACE Yol Haritası")
|--------------------------------------------------------------------------
| NACE kodunun ilk iki hanesine (bölüm) göre risk değerlendirmesinde
| kontrol edilecek teknik risk başlıkları, özel senaryolar ve ilgili mevzuat.
| Bu liste BAŞLANGIÇ KAPSAMIDIR: otomatik risk üretmez; her başlık bölüm /
| faaliyet bazında saha gözlemiyle uzman tarafından doğrulanır.
| `bolumler`: NACE bölüm ön ekleri (iki hane).
*/

return [
    'gruplar' => [
        'insaat' => [
            'ad' => 'İnşaat', 'bolumler' => ['41', '42', '43'],
            'basliklar' => ['Yüksekte çalışma ve düşmeye karşı koruma', 'İskele ve geçici çalışma platformları', 'Kazı ve göçük', 'Kaldırma ve taşıma faaliyetleri', 'Geçici elektrik tesisatı', 'Şantiye içi araç–yaya trafiği', 'Malzeme düşmesi / savrulma', 'Kaynak, kesme ve sıcak işler'],
            'senaryolar' => ['Yüksekten düşme', 'Çökme ve göçük', 'Yer altı tesislerine (enerji / doğalgaz hattı) zarar'],
            'mevzuat' => ['Yapı İşlerinde İş Sağlığı ve Güvenliği Yönetmeliği', 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği', 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik', 'Elle Taşıma İşleri Yönetmeliği'],
        ],
        'madencilik' => [
            'ad' => 'Madencilik ve taş ocakçılığı', 'bolumler' => ['05', '06', '07', '08', '09'],
            'basliklar' => ['Patlayıcı madde kullanımı ve depolama', 'Şev / pasa stabilitesi', 'Toz ve kristal silika maruziyeti', 'Ağır iş makineleri ve nakliye yolları', 'Gürültü ve titreşim', 'Havalandırma ve gaz birikimi (yeraltı)'],
            'senaryolar' => ['Şev kayması / göçük', 'Patlatma kazası', 'Grizu / gaz patlaması'],
            'mevzuat' => ['Maden İşyerlerinde İş Sağlığı ve Güvenliği Yönetmeliği', 'Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik', 'Tozla Mücadele Yönetmeliği'],
        ],
        'gida' => [
            'ad' => 'Gıda ve içecek üretimi', 'bolumler' => ['10', '11'],
            'basliklar' => ['Kesme / doğrama makineleri', 'Sıcak yüzey, buhar ve kazan', 'Soğuk hava deposu ve amonyak', 'Kaygan zemin', 'Temizlik ve CIP kimyasalları', 'Elle taşıma ve tekrarlı hareket', 'Forklift / transpalet'],
            'senaryolar' => ['Amonyak kaçağı', 'Buhar kazanı patlaması', 'Soğuk depoda mahsur kalma'],
            'mevzuat' => ['İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği', 'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'Elle Taşıma İşleri Yönetmeliği'],
        ],
        'tekstil' => [
            'ad' => 'Tekstil, giyim ve deri', 'bolumler' => ['13', '14', '15'],
            'basliklar' => ['Dikiş / kesim makineleri', 'Pamuk tozu ve lif', 'Boya ve apre kimyasalları', 'Yangın yükü (kumaş, iplik)', 'Ergonomi ve tekrarlı hareket', 'Gürültü (dokuma)'],
            'senaryolar' => ['Depo yangını', 'Kimyasal sıçraması'],
            'mevzuat' => ['Binaların Yangından Korunması Hakkında Yönetmelik', 'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'Ekranlı Araçlarla Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik'],
        ],
        'ahsap' => [
            'ad' => 'Ağaç, mobilya ve kağıt', 'bolumler' => ['16', '17', '31'],
            'basliklar' => ['Testere, planya, freze makineleri', 'Ahşap tozu (kanserojen) ve ATEX', 'Boya, vernik, yapıştırıcı', 'Yangın yükü', 'Gürültü', 'Malzeme istifleme'],
            'senaryolar' => ['Toz patlaması', 'Boya kabininde yangın', 'Geri tepme (kickback) yaralanması'],
            'mevzuat' => ['Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik', 'Kanserojen veya Mutajen Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'Makina Emniyeti Yönetmeliği'],
        ],
        'basim' => [
            'ad' => 'Basım ve kayıt', 'bolumler' => ['18'],
            'basliklar' => ['Baskı makineleri (silindir sıkışması)', 'Solvent ve mürekkep buharı', 'Kağıt bobini taşıma', 'Gürültü'],
            'senaryolar' => ['Solvent yangını', 'Silindir arasında sıkışma'],
            'mevzuat' => ['Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği'],
        ],
        'kimya' => [
            'ad' => 'Kimya, petrol ve ilaç', 'bolumler' => ['19', '20', '21'],
            'basliklar' => ['Tehlikeli kimyasal depolama ve aktarma', 'Basınçlı kaplar ve reaktörler', 'Parlayıcı / patlayıcı ortam (ATEX)', 'Toksik gaz ve buhar', 'Statik elektrik', 'Atık yönetimi'],
            'senaryolar' => ['Kimyasal yayılım / sızıntı', 'Reaktör patlaması', 'Zehirli gaz bulutu'],
            'mevzuat' => ['Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik', 'Büyük Endüstriyel Kazaların Önlenmesi ve Etkilerinin Azaltılması Hakkında Yönetmelik', 'Basınçlı Ekipmanlar Yönetmeliği'],
        ],
        'plastik' => [
            'ad' => 'Kauçuk ve plastik', 'bolumler' => ['22'],
            'basliklar' => ['Enjeksiyon / ekstrüzyon makineleri', 'Sıcak kalıp ve eriyik', 'Buhar ve duman', 'Yangın yükü', 'Elle taşıma'],
            'senaryolar' => ['Kalıp arasında sıkışma', 'Hammadde deposu yangını'],
            'mevzuat' => ['Makina Emniyeti Yönetmeliği', 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği'],
        ],
        'mineral' => [
            'ad' => 'Cam, seramik, çimento, beton', 'bolumler' => ['23'],
            'basliklar' => ['Fırın ve yüksek sıcaklık', 'Silika tozu', 'Kırıcı / öğütücü makineler', 'Ağır yük taşıma', 'Gürültü'],
            'senaryolar' => ['Fırın patlaması', 'Silo içinde mahsur kalma'],
            'mevzuat' => ['Tozla Mücadele Yönetmeliği', 'Çalışanların Gürültü ile İlgili Risklerden Korunmalarına Dair Yönetmelik', 'İşyeri Bina ve Eklentilerinde Alınacak Sağlık ve Güvenlik Önlemlerine İlişkin Yönetmelik'],
        ],
        'metal' => [
            'ad' => 'Metal ve makine imalatı', 'bolumler' => ['24', '25', '26', '27', '28', '29', '30', '33'],
            'basliklar' => ['Pres, büküm ve kesme makineleri', 'CNC ve talaşlı imalat', 'Kaynak duman ve ışın', 'Tavan vinci ve kaldırma', 'Elektrik ve enerji izolasyonu (LOTO)', 'Taşlama ve savrulma', 'Gürültü ve titreşim'],
            'senaryolar' => ['Makineye kapılma / sıkışma', 'Asılı yük düşmesi', 'Kaynak kaynaklı yangın'],
            'mevzuat' => ['İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği', 'Makina Emniyeti Yönetmeliği', 'Çalışanların Gürültü ile İlgili Risklerden Korunmalarına Dair Yönetmelik', 'Çalışanların Titreşimle İlgili Risklerden Korunmalarına Dair Yönetmelik'],
        ],
        'enerji' => [
            'ad' => 'Elektrik, gaz ve enerji', 'bolumler' => ['35'],
            'basliklar' => ['Yüksek gerilim ve ark', 'Trafo ve şalt sahası', 'Doğalgaz ve basınçlı hat', 'Yüksekte çalışma (direk, kule)', 'Kapalı alan (kuyu, menhol)'],
            'senaryolar' => ['Elektrik çarpması / ark parlaması', 'Doğalgaz kaçağı ve patlama'],
            'mevzuat' => ['Elektrik Tesislerinde Topraklamalar Yönetmeliği', 'Elektrik Kuvvetli Akım Tesisleri Yönetmeliği', 'Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik'],
        ],
        'atik' => [
            'ad' => 'Su, kanalizasyon ve atık', 'bolumler' => ['36', '37', '38', '39'],
            'basliklar' => ['Kapalı alan (kuyu, tank, kanal)', 'Biyolojik etkenler', 'Toksik gaz (H₂S)', 'Atık presleri ve konveyör', 'Araç trafiği'],
            'senaryolar' => ['Kapalı alanda boğulma / zehirlenme', 'Biyolojik bulaş'],
            'mevzuat' => ['Biyolojik Etkenlere Maruziyet Risklerinin Önlenmesi Hakkında Yönetmelik', 'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik'],
        ],
        'ticaret' => [
            'ad' => 'Toptan ve perakende ticaret, oto servis', 'bolumler' => ['45', '46', '47'],
            'basliklar' => ['Raf ve istif düzeni', 'Forklift / transpalet', 'Elle taşıma', 'Müşteri / ziyaretçi güvenliği', 'Kaygan zemin', 'Oto serviste kaldırma lifti ve yağlar'],
            'senaryolar' => ['Raf devrilmesi', 'Mağaza yangını ve kalabalık tahliyesi'],
            'mevzuat' => ['Elle Taşıma İşleri Yönetmeliği', 'İşyeri Bina ve Eklentilerinde Alınacak Sağlık ve Güvenlik Önlemlerine İlişkin Yönetmelik', 'Binaların Yangından Korunması Hakkında Yönetmelik'],
        ],
        'tasima' => [
            'ad' => 'Taşımacılık ve depolama', 'bolumler' => ['49', '50', '51', '52', '53'],
            'basliklar' => ['Araç kullanımı ve trafik', 'Yükleme–boşaltma rampası', 'Forklift ve depo içi trafik', 'Tehlikeli madde taşıma', 'Yorgunluk ve vardiya', 'Elle taşıma'],
            'senaryolar' => ['Trafik kazası', 'Rampada araç kayması', 'Tehlikeli madde dökülmesi'],
            'mevzuat' => ['Elle Taşıma İşleri Yönetmeliği', 'Tehlikeli Malların Karayoluyla Taşınması Hakkında Yönetmelik', 'İşyeri Bina ve Eklentilerinde Alınacak Sağlık ve Güvenlik Önlemlerine İlişkin Yönetmelik'],
        ],
        'konaklama' => [
            'ad' => 'Konaklama ve yiyecek hizmetleri', 'bolumler' => ['55', '56'],
            'basliklar' => ['Mutfak: sıcak yüzey, yağ, bıçak', 'LPG / doğalgaz', 'Kaygan zemin', 'Temizlik kimyasalları', 'Misafir tahliyesi', 'Gece çalışması'],
            'senaryolar' => ['Mutfak yangını', 'Gaz kaçağı', 'Gıda zehirlenmesi'],
            'mevzuat' => ['Binaların Yangından Korunması Hakkında Yönetmelik', 'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik'],
        ],
        'ofis' => [
            'ad' => 'Ofis, bilişim ve profesyonel hizmetler', 'bolumler' => ['58', '59', '60', '61', '62', '63', '64', '65', '66', '68', '69', '70', '71', '72', '73', '74', '75', '77', '78', '79', '82', '84'],
            'basliklar' => ['Ekranlı araçlarla çalışma ve ergonomi', 'Elektrik ve kablo düzeni', 'Psikososyal riskler ve stres', 'Takılma / kayma', 'Acil çıkış ve yangın'],
            'senaryolar' => ['Ofis yangını', 'Deprem'],
            'mevzuat' => ['Ekranlı Araçlarla Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'İşyeri Bina ve Eklentilerinde Alınacak Sağlık ve Güvenlik Önlemlerine İlişkin Yönetmelik'],
        ],
        'temizlik' => [
            'ad' => 'Temizlik, güvenlik ve tesis hizmetleri', 'bolumler' => ['80', '81'],
            'basliklar' => ['Temizlik kimyasalları', 'Islak zemin', 'Yüksekte cam temizliği', 'Gece ve yalnız çalışma', 'Şiddet ve saldırı riski'],
            'senaryolar' => ['Kimyasal karışımdan gaz', 'Yüksekten düşme'],
            'mevzuat' => ['Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik'],
        ],
        'egitim' => [
            'ad' => 'Eğitim', 'bolumler' => ['85'],
            'basliklar' => ['Öğrenci / ziyaretçi tahliyesi', 'Laboratuvar kimyasalları', 'Spor alanları', 'Mutfak ve yemekhane', 'Psikososyal riskler'],
            'senaryolar' => ['Okul yangını ve tahliye', 'Deprem'],
            'mevzuat' => ['İşyerlerinde Acil Durumlar Hakkında Yönetmelik', 'Binaların Yangından Korunması Hakkında Yönetmelik'],
        ],
        'saglik' => [
            'ad' => 'İnsan sağlığı ve sosyal hizmetler', 'bolumler' => ['86', '87', '88'],
            'basliklar' => ['Biyolojik etkenler ve kesici-delici yaralanma', 'Hasta kaldırma ve ergonomi', 'İyonize radyasyon', 'Sterilizasyon ve dezenfektan kimyasalları', 'Medikal gazlar', 'Şiddet ve psikososyal riskler'],
            'senaryolar' => ['Kan yoluyla bulaş', 'Hasta tahliyesi (yatağa bağımlı)', 'Oksijen kaynaklı yangın'],
            'mevzuat' => ['Biyolojik Etkenlere Maruziyet Risklerinin Önlenmesi Hakkında Yönetmelik', 'Radyasyon Güvenliği Yönetmeliği'],
        ],
        'tarim' => [
            'ad' => 'Tarım, ormancılık ve balıkçılık', 'bolumler' => ['01', '02', '03'],
            'basliklar' => ['Traktör ve tarım makineleri', 'Pestisit ve gübre', 'Hayvanlarla çalışma', 'Açık hava, sıcak / soğuk stres', 'Ağaç kesimi'],
            'senaryolar' => ['Traktör devrilmesi', 'Pestisit zehirlenmesi'],
            'mevzuat' => ['Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği'],
        ],
    ],

    // Tüm işyerleri için ortak mevzuat.
    'ortak_mevzuat' => [
        '6331 sayılı İş Sağlığı ve Güvenliği Kanunu',
        'İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği',
        'İşyeri Bina ve Eklentilerinde Alınacak Sağlık ve Güvenlik Önlemlerine İlişkin Yönetmelik',
        'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği',
        'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik',
        'İşyerlerinde Acil Durumlar Hakkında Yönetmelik',
    ],

    // Tehlike ve Risk Analitiği — tehlike türleri. `anahtar`: tür seçilmemiş
    // maddelerde tehlike / risk / faaliyet metninde aranan kelime kökleri
    // (küçük harf). Sıra önemlidir: ilk eşleşen tür; hiçbiri değilse fiziksel.
    'tehlike_turleri' => [
        'psikososyal' => ['ad' => 'Psikososyal', 'renk' => '#db2777', 'anahtar' => ['psikososyal', 'stres', 'mobbing', 'taciz', 'saldırı', 'iş yükü', 'vardiya', 'gece çalış', 'monoton', 'yalnız çalış', 'tükenmiş']],
        'biyolojik' => ['ad' => 'Biyolojik', 'renk' => '#7c3aed', 'anahtar' => ['biyolojik', 'bakteri', 'virüs', 'mikrop', 'enfeksiyon', 'salgın', 'bulaş', ' kan ', 'küf', 'lejyonella', 'hijyen', 'tıbbi atık', 'hayvan', 'böcek', 'kemirgen']],
        'kimyasal' => ['ad' => 'Kimyasal', 'renk' => '#ea580c', 'anahtar' => ['kimyasal', 'solvent', 'tiner', 'boya', 'vernik', 'asit', ' baz ', 'kostik', 'gaz', 'buhar', 'duman', 'zehir', 'toksik', 'akaryakıt', 'benzin', 'mazot', 'lpg', 'amonyak', 'klor', 'sds', 'sızıntı', 'dökülme', 'yanıcı sıvı', 'yapıştırıcı', 'pestisit']],
        'ergonomik' => ['ad' => 'Ergonomik', 'renk' => '#2563eb', 'anahtar' => ['ergonom', 'elle taşı', 'elle kaldır', 'duruş', 'tekrarlı', 'ekranlı', 'ekran başı', 'kas-iskelet', 'kas iskelet', ' bel ', 'zorlayıcı', 'uzun süre ayakta', 'uzun süre otur', 'eğilme', 'uzanma']],
        'fiziksel' => ['ad' => 'Fiziksel', 'renk' => '#0d9488', 'anahtar' => []],
    ],
    // Risk değerlendirmesi maddelerinde önerilecek bölüm adları (isgsuite "Bölümler").
    'bolum_onerileri' => ['İdari Ofis', 'Üretim', 'Bakım', 'Depo', 'Sevkiyat', 'Laboratuvar', 'Kimyasal Depo', 'Elektrik Odası', 'Kazan Dairesi', 'Atölye', 'Çatı', 'Vinç Sahası', 'Yemekhane / Mutfak', 'Otopark', 'Şantiye Sahası'],
];
