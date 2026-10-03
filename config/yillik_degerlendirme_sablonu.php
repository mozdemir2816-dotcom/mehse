<?php

/*
|--------------------------------------------------------------------------
| İSG Yıllık Değerlendirme Raporu şablonu
|--------------------------------------------------------------------------
| Kullanıcının gerçek şablonundan (Desktop\isgsuit\yıllık değerlendirme\n| ISG_Yillik_Degerlendirme_Raporu.xlsx → resources/belge/yillik-degerlendirme.xlsx)
| birebir çıkarıldı — metinleri DEĞİŞTİRMEYİN. 36 çalışma satırı (No, Yapılan
| çalışma, Yapan kişi / Ünvan, Kullanılan yöntem / Kanıt, Sonuç şablonu) +
| 7. sayfa genel sonuç başlıkları. `anahtar`: App\Support\YillikDegerlendirmeVerisi
| bu satırı hangi sistem kayıtlarından dolduracağını bilir.
*/

return array (
  'dokuman_no' => 'İSG-YDR-01',
  'revizyon' => '00',
  'satirlar' => 
  array (
    0 => 
    array (
      'anahtar' => 'risk',
      'no' => 1,
      'calisma' => 'Risk değerlendirmesi',
      'yapan_kisi' => 'İGU / İYH / Risk değerlendirme ekibi',
      'yontem' => 'Doküman inceleme ve saha gözlemi',
      'sonuc_sablonu' => 'Rapor tarihi: … / … / … ; revizyon: … . İncelenen risk: … ; eklenen/güncellenen madde: … ; açık tedbir: … .',
    ),
    1 => 
    array (
      'anahtar' => 'ortam',
      'no' => 2,
      'calisma' => 'Çalışma ortamı ölçümleri',
      'yapan_kisi' => 'Yetkili laboratuvar / İGU',
      'yontem' => 'Maruziyet ve ortam ölçüm raporları',
      'sonuc_sablonu' => '… noktada/kişide ölçüm yapıldı. Parametreler: … . Sınır değeri aşan sonuç: … ; alınan önlem: … .',
    ),
    2 => 
    array (
      'anahtar' => 'ise_giris',
      'no' => 3,
      'calisma' => 'İşe giriş muayeneleri',
      'yapan_kisi' => 'İşyeri hekimi',
      'yontem' => 'İşe giriş muayene kayıtları',
      'sonuc_sablonu' => '… kişinin işe giriş muayenesi yapıldı. İşe uygunluk ve çalışma kısıtları değerlendirildi. Eksik işlem: … .',
    ),
    3 => 
    array (
      'anahtar' => 'periyodik',
      'no' => 4,
      'calisma' => 'Periyodik muayeneler',
      'yapan_kisi' => 'İşyeri hekimi',
      'yontem' => 'Periyodik muayene kayıtları',
      'sonuc_sablonu' => '… kişinin periyodik muayenesi yapıldı. Muayenesi bekleyen kişi: … ; takip işlemi: … .',
    ),
    4 => 
    array (
      'anahtar' => 'radyolojik',
      'no' => 5,
      'calisma' => 'Radyolojik incelemeler',
      'yapan_kisi' => 'İşyeri hekimi / Sağlık kuruluşu',
      'yontem' => 'Hekim istemi ve tetkik kayıtları',
      'sonuc_sablonu' => 'Hekim değerlendirmesine göre … kişiye inceleme yapıldı. İleri değerlendirme/sevk: … kişi.',
    ),
    5 => 
    array (
      'anahtar' => 'biyolojik',
      'no' => 6,
      'calisma' => 'Biyolojik analizler',
      'yapan_kisi' => 'İşyeri hekimi / Sağlık kuruluşu',
      'yontem' => 'Hekim istemi ve laboratuvar kayıtları',
      'sonuc_sablonu' => '… kişiye gerekli biyolojik analizler yapıldı. Takip/sevk edilen: … kişi; takip durumu: … .',
    ),
    6 => 
    array (
      'anahtar' => 'toksikolojik',
      'no' => 7,
      'calisma' => 'Toksikolojik analizler',
      'yapan_kisi' => 'İşyeri hekimi / Sağlık kuruluşu',
      'yontem' => 'Maruziyete göre biyolojik izlem',
      'sonuc_sablonu' => 'Gereklilik: … . … kişiye analiz yapıldı. Maruziyetin azaltılmasına yönelik işlem: … .',
    ),
    7 => 
    array (
      'anahtar' => 'fizyolojik',
      'no' => 8,
      'calisma' => 'Fizyolojik testler',
      'yapan_kisi' => 'İşyeri hekimi / Sağlık kuruluşu',
      'yontem' => 'Gerekli SFT, odyometri, EKG vb.',
      'sonuc_sablonu' => '… kişiye işe ve maruziyete uygun test yapıldı. İleri değerlendirmeye alınan: … kişi.',
    ),
    8 => 
    array (
      'anahtar' => 'psikolojik',
      'no' => 9,
      'calisma' => 'Psikolojik değerlendirmeler',
      'yapan_kisi' => 'İşyeri hekimi / İlgili uzman',
      'yontem' => 'Hekim değerlendirmesi ve sevk',
      'sonuc_sablonu' => 'Gereklilik: … . Değerlendirilen: … kişi; takip ve işe uyum yönünden alınan önlem: … .',
    ),
    9 => 
    array (
      'anahtar' => 'temel_egitim',
      'no' => 10,
      'calisma' => 'Temel İSG eğitimleri',
      'yapan_kisi' => 'İGU / İYH / Yetkili eğiticiler',
      'yontem' => 'Katılım, içerik ve değerlendirme kayıtları',
      'sonuc_sablonu' => '… çalışana eğitim verildi. Kişi başı süre: … saat; eğitim oturumu: … ; eksik eğitimi bulunan: … kişi.',
    ),
    10 => 
    array (
      'anahtar' => 'isbasi_egitim',
      'no' => 11,
      'calisma' => 'İşe başlama ve iş değişikliği eğitimi',
      'yapan_kisi' => 'İşveren / Görevlendirilen eğitici',
      'yontem' => 'İşe ve işyerine özgü uygulamalı eğitim',
      'sonuc_sablonu' => '… kişiye işe başlama/iş değişikliği eğitimi verildi. Tarihler: … ; kayıt/uygulama eksikliği: … .',
    ),
    11 => 
    array (
      'anahtar' => 'ise_ozgu_egitim',
      'no' => 12,
      'calisma' => 'İşe özgü eğitim ve bilgilendirme',
      'yapan_kisi' => 'İGU / İYH / İlgili eğiticiler',
      'yontem' => 'Uygulama, talimat ve toolbox kayıtları',
      'sonuc_sablonu' => 'Konular: … . … oturumda … katılım sağlandı. Uygulama değerlendirmesi ve ihtiyaç: … .',
    ),
    12 => 
    array (
      'anahtar' => 'mesleki_belge',
      'no' => 13,
      'calisma' => 'Mesleki eğitim ve operatör belgeleri',
      'yapan_kisi' => 'İşveren / İGU',
      'yontem' => 'Belge ve görev uygunluğu incelemesi',
      'sonuc_sablonu' => '… çalışanın belgesi kontrol edildi. Eksik/uygunsuz belge: … ; tamamlanan: … ; işlem: … .',
    ),
    13 => 
    array (
      'anahtar' => 'acil_plan',
      'no' => 14,
      'calisma' => 'Acil durum planı ve görevlendirmeler',
      'yapan_kisi' => 'İşveren / İGU / İYH',
      'yontem' => 'Plan, ekip listeleri ve saha kontrolü',
      'sonuc_sablonu' => 'Plan tarihi/revizyonu: … . Ekip görevlendirmeleri ve iletişim bilgileri gözden geçirildi. Eksik: … .',
    ),
    14 => 
    array (
      'anahtar' => 'yangin_egitim',
      'no' => 15,
      'calisma' => 'Yangın, tahliye ve kurtarma eğitimi',
      'yapan_kisi' => 'İlgili eğiticiler / Acil durum ekipleri',
      'yontem' => 'Teorik ve uygulamalı eğitim',
      'sonuc_sablonu' => '… kişiye … saat eğitim verildi. Söndürme, kurtarma ve koruma görevleri için eksik eğitim: … .',
    ),
    15 => 
    array (
      'anahtar' => 'ilkyardim',
      'no' => 16,
      'calisma' => 'İlkyardımcı eğitimleri',
      'yapan_kisi' => 'Yetkili eğitim merkezi / İşveren',
      'yontem' => 'Sertifika ve geçerlilik kontrolü',
      'sonuc_sablonu' => 'Geçerli belgeli ilkyardımcı: … kişi. Yeni eğitim: … ; güncelleme eğitimi: … ; ihtiyaç: … kişi.',
    ),
    16 => 
    array (
      'anahtar' => 'tatbikat',
      'no' => 17,
      'calisma' => 'Acil durum tatbikatları',
      'yapan_kisi' => 'İşveren / İGU / Acil durum ekipleri',
      'yontem' => 'Senaryo, gözlem ve tatbikat tutanağı',
      'sonuc_sablonu' => '… tarihinde … senaryolu tatbikat yapıldı. Katılımcı: … ; tespit: … ; iyileştirme: … .',
    ),
    17 => 
    array (
      'anahtar' => 'yangin_ekipman',
      'no' => 18,
      'calisma' => 'Yangın ve acil durum ekipmanları',
      'yapan_kisi' => 'İşveren / Teknik birim / Yetkili kişi',
      'yontem' => 'Kontrol, bakım ve test kayıtları',
      'sonuc_sablonu' => '… ekipman/sistem kontrol edildi. Eksik veya arızalı: … ; giderilen: … ; açık işlem: … .',
    ),
    18 => 
    array (
      'anahtar' => 'periyodik_kontrol',
      'no' => 19,
      'calisma' => 'İş ekipmanları periyodik kontrolleri',
      'yapan_kisi' => 'Yetkili kontrol kişileri / İşveren',
      'yontem' => 'Periyodik kontrol raporu incelemesi',
      'sonuc_sablonu' => '… ekipmanın kontrolü yapıldı. Uygunsuz rapor: … ; giderilen eksik: … ; kontrol bekleyen: … .',
    ),
    19 => 
    array (
      'anahtar' => 'elektrik',
      'no' => 20,
      'calisma' => 'Elektrik, topraklama ve yıldırımdan korunma',
      'yapan_kisi' => 'Yetkili kontrol kişileri / Teknik birim',
      'yontem' => 'Tesisat kontrol ve ölçüm raporları',
      'sonuc_sablonu' => 'Kontrol tarihi: … ; kapsam: … . Tespit: … ; giderilen: … ; açık uygunsuzluk: … .',
    ),
    20 => 
    array (
      'anahtar' => 'makine',
      'no' => 21,
      'calisma' => 'Makine koruyucuları ve bakım',
      'yapan_kisi' => 'Teknik birim / İGU',
      'yontem' => 'Bakım kayıtları ve işlev kontrolleri',
      'sonuc_sablonu' => '… makine/ekipman incelendi. Koruyucu, acil durdurma ve güvenlik sistemlerinde eksik: … ; işlem: … .',
    ),
    21 => 
    array (
      'anahtar' => 'saha',
      'no' => 22,
      'calisma' => 'Saha gözetimi ve denetimler',
      'yapan_kisi' => 'İGU / İYH / İşveren temsilcisi',
      'yontem' => 'Saha gözlem raporu ve kontrol listesi',
      'sonuc_sablonu' => '… saha denetimi yapıldı. Tespit edilen uygunsuzluk: … ; kapatılan: … ; açık kalan: … .',
    ),
    22 => 
    array (
      'anahtar' => 'dof',
      'no' => 23,
      'calisma' => 'Düzeltici ve önleyici faaliyetler',
      'yapan_kisi' => 'İşveren / İlgili birimler / İGU',
      'yontem' => 'DÖF kayıtları ve kapanış doğrulaması',
      'sonuc_sablonu' => '… DÖF açıldı; … DÖF kapatıldı. Devreden açık DÖF: … ; gecikenler ve gerekçeleri: … .',
    ),
    23 => 
    array (
      'anahtar' => 'defter',
      'no' => 24,
      'calisma' => 'Onaylı defter ve yazılı bildirimler',
      'yapan_kisi' => 'İGU / İYH / İşveren',
      'yontem' => 'Tespit-öneri ve bildirim kayıtları',
      'sonuc_sablonu' => '… yazılı bildirim/defter kaydı düzenlendi. Tamamlanan öneri: … ; açık öneri: … ; takip: … .',
    ),
    24 => 
    array (
      'anahtar' => 'is_kazasi',
      'no' => 25,
      'calisma' => 'İş kazalarının incelenmesi',
      'yapan_kisi' => 'İşveren / İGU / İYH',
      'yontem' => 'Kaza kayıtları ve kök neden analizi',
      'sonuc_sablonu' => 'İş kazası: … ; yaralanan kişi: … ; kayıp iş günü: … . Kök nedenler/önlemler: … ; bildirim takibi: … .',
    ),
    25 => 
    array (
      'anahtar' => 'ramak_kala',
      'no' => 26,
      'calisma' => 'Ramak kala ve tehlike bildirimleri',
      'yapan_kisi' => 'Çalışanlar / İGU / İşveren',
      'yontem' => 'Bildirim ve olay inceleme kayıtları',
      'sonuc_sablonu' => '… ramak kala/tehlike bildirimi alındı. İncelenen: … ; kapatılan önlem: … ; açık konu: … .',
    ),
    26 => 
    array (
      'anahtar' => 'meslek_hastaligi',
      'no' => 27,
      'calisma' => 'Meslek hastalığı ve işe bağlı sağlık izlemi',
      'yapan_kisi' => 'İşyeri hekimi / İşveren',
      'yontem' => 'Toplulaştırılmış sağlık ve sevk kayıtları',
      'sonuc_sablonu' => 'Şüphe nedeniyle sevk: … kişi; tanısı bildirilen: … kişi. İşyerinde alınan koruyucu önlemler: … .',
    ),
    27 => 
    array (
      'anahtar' => 'kkd',
      'no' => 28,
      'calisma' => 'KKD seçimi, teslimi ve kullanımı',
      'yapan_kisi' => 'İşveren / İGU / İlgili birimler',
      'yontem' => 'Risk bazlı seçim, zimmet ve gözlem',
      'sonuc_sablonu' => '… kişiye KKD teslimi/yenilemesi yapıldı. Uygunsuzluk: … ; kullanım eğitimi ve düzeltme: … .',
    ),
    28 => 
    array (
      'anahtar' => 'kimyasal',
      'no' => 29,
      'calisma' => 'Kimyasallar ve güvenlik bilgi formları',
      'yapan_kisi' => 'İşveren / İGU / İYH',
      'yontem' => 'Envanter, GBF ve depolama kontrolü',
      'sonuc_sablonu' => '… kimyasal incelendi. Eksik GBF/etiket: … ; depolama ve maruziyet önlemi: … .',
    ),
    29 => 
    array (
      'anahtar' => 'hijyen',
      'no' => 30,
      'calisma' => 'Hijyen ve ortak kullanım alanları',
      'yapan_kisi' => 'İşyeri hekimi / İşveren',
      'yontem' => 'Hijyen gözlemi ve kontrol kayıtları',
      'sonuc_sablonu' => '… hijyen denetimi yapıldı. Yemekhane, içme suyu, tuvalet ve ortak alanlarda tespit/işlem: … .',
    ),
    30 => 
    array (
      'anahtar' => 'ergonomi',
      'no' => 31,
      'calisma' => 'Ergonomi ve özel politika gerektiren gruplar',
      'yapan_kisi' => 'İGU / İYH / İşveren',
      'yontem' => 'İşe uyum ve çalışma koşulu incelemesi',
      'sonuc_sablonu' => '… iş/çalışma alanı değerlendirildi. Uyarlama yapılan: … ; ergonomik veya koruyucu önlem: … .',
    ),
    31 => 
    array (
      'anahtar' => 'kurul',
      'no' => 32,
      'calisma' => 'İSG kurulu ve çalışan katılımı',
      'yapan_kisi' => 'İşveren / Kurul / Çalışan temsilcisi',
      'yontem' => 'Toplantı, karar ve öneri kayıtları',
      'sonuc_sablonu' => 'Kurulun uygulanabilirliği: … . … toplantı yapıldı; … karar alındı. Açık karar/çalışan önerisi: … .',
    ),
    32 => 
    array (
      'anahtar' => 'alt_isveren',
      'no' => 33,
      'calisma' => 'Alt işveren ve ziyaretçi koordinasyonu',
      'yapan_kisi' => 'İşveren / İGU / İlgili birimler',
      'yontem' => 'Koordinasyon ve bilgilendirme kayıtları',
      'sonuc_sablonu' => '… koordinasyon/bilgilendirme çalışması yapıldı. Ortak riskler ve alınan tedbirler: … .',
    ),
    33 => 
    array (
      'anahtar' => 'gorevlendirme',
      'no' => 34,
      'calisma' => 'İSG görevlendirmeleri ve doküman takibi',
      'yapan_kisi' => 'İşveren / İGU / İYH',
      'yontem' => 'Görevlendirme ve doküman incelemesi',
      'sonuc_sablonu' => 'Uzman, hekim, temsilci ve destek elemanı kayıtları incelendi. Eksik/güncellenen belge: … .',
    ),
    34 => 
    array (
      'anahtar' => 'plan_disi',
      'no' => 35,
      'calisma' => 'Plan dışı yapılan çalışmalar',
      'yapan_kisi' => 'İşveren / İGU / İYH / İlgili birimler',
      'yontem' => 'Ek çalışma ve kanıt kayıtları',
      'sonuc_sablonu' => 'Plan dışında … çalışma yapıldı. Gerekçe: … ; tarih: … ; sonuç: … ; gelecek yıla etkisi: … .',
    ),
    35 => 
    array (
      'anahtar' => 'gerceklesmeyen',
      'no' => 36,
      'calisma' => 'Gerçekleştirilemeyen çalışmalar',
      'yapan_kisi' => 'İşveren / İGU / İYH / İlgili birimler',
      'yontem' => 'Yıllık plan ve gerçekleşme incelemesi',
      'sonuc_sablonu' => 'Gerçekleştirilemeyen/kısmi çalışma: … . Gerekçe: … ; risk etkisi: … ; yeni termin ve sorumlu: … .',
    ),
  ),
  'genel_sonuc' => 
  array (
    0 => 
    array (
      'baslik' => 'Genel değerlendirme',
      'sablon' => 'Yıl içinde yürütülen İSG çalışmaları ve yıllık çalışma planı değerlendirilmiştir. Güçlü yönler: … . Öncelikli eksiklikler: … .',
    ),
    1 => 
    array (
      'baslik' => 'Eğitim ve sağlık gözetimi',
      'sablon' => 'Eğitime katılan farklı çalışan: … kişi; toplam katılım: … . İşe giriş muayenesi: … kişi; periyodik muayene: … kişi. Tamamlanacak işlemler: … .',
    ),
    2 => 
    array (
      'baslik' => 'Kazalar ve önleyici çalışmalar',
      'sablon' => 'İş kazası: … ; ramak kala: … ; kayıp iş günü: … . Öne çıkan kök nedenler: … . Tekrarını önlemek için kararlar: … .',
    ),
    3 => 
    array (
      'baslik' => 'Açık uygunsuzluklar',
      'sablon' => 'Açık kalan başlıca konu: … . Geçici tedbir: … . Kalıcı faaliyet: … . Sorumlu: … . Tamamlanma tarihi: … .',
    ),
    4 => 
    array (
      'baslik' => 'Gerçekleştirilemeyen faaliyetler',
      'sablon' => 'Faaliyet: … . Gerçekleşmeme/kısmi gerçekleşme nedeni: … . Alınacak önlem: … . Sorumlu ve yeni termin: … .',
    ),
    5 => 
    array (
      'baslik' => 'Gelecek yıl önerileri',
      'sablon' => 'Yıllık çalışma ve eğitim planına aktarılacak faaliyet: … . Öncelik/gerekçe: … . Kaynak ihtiyacı: … . Sorumlu: … . Hedef tarih: … .',
    ),
  ),
  'ekler' => 
  array (
    'baslik' => 'Ekler / dayanak kayıtlar',
    'sablon' => 'Risk değerlendirmesi, eğitim katılım kayıtları, toplu sağlık verileri, ölçüm ve kontrol raporları, tatbikat tutanakları, saha raporları ve DÖF kayıtları. Ek no / kayıt yeri: … .',
  ),
  'doldurma_notu' => 'Doldurma: Sonuç metinleri taslaktır; yalnızca kayıtlarla doğrulanan çalışmaları yazınız. Yapılmayan iş için “yapılmadı” ve gerekçesini; kapsam dışı iş için “uygulanamaz” yazınız.',
  'alt_not' => 'Rapor, ilgili döneme ait kayıtlar esas alınarak doldurulur. Sağlık verileri kişi adı ve tanı ayrıntısı içermeden toplu olarak yazılır. Genç/çocuk sayıları toplam çalışan sayısının alt grubudur.',
  'karar_satiri' => 'Değerlendirme sonucu alınan kararlar: …',
);
