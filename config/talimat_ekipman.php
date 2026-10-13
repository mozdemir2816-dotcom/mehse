<?php

/*
 * Ekipman güvenli çalışma talimatları — kullanıcının kendi Word talimatlarından BİREBİR
 * aktarıldı; metinleri değiştirme. Ekipman kataloğunda (config/ekipman_katalogu.php
 * 'talimat') başlıkla bağlanır; firmaya ekipman eklenince firmaya otomatik eklenir.
 * Talimat Oluştur kütüphanesinde inşaat talimatlarının ardından listelenir
 * (App\Support\TalimatKutuphanesi).
 *
 * İSG-TLM-CNC-01: CNC_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx
 * İSG-TLM-TRN-01: Universal_Torna_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx
 * İSG-TLM-FRZ-01: Dikey_Freze_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx
 * İSG-TLM-KMP-01: Kompresor_ve_Hava_Tanki_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx
 * İSG-TLM-EKS-01: Ekstruzyon_Makinesi_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx
 * İSG-TLM-TRF-01: Trafo_Merkezi_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx
 */

return [
    [
        'baslik' => 'CNC MAKİNESİ GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'İSG-TLM-CNC-01',
        'aciklama' => 'Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Bu talimat, CNC (Torna / Freze / İşleme Merkezi) tezgahlarında talaşlı imalat operasyonları sırasında personelin can güvenliğini temin etmek, meslek hastalıkları ve iş kazalarını önlemek, tezgah ve ekipman güvenliğini sağlamak amacıyla 6331 sayılı İş Sağlığı ve Güvenliği Kanunu\'na uygun olarak hazırlanmıştır.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)',
                'aciklama' => '',
                'maddeler' => [
                    'Göz ve Yüz Koruma: EN 166 standardına uygun yan korumalı iş gözlüğü veya çapak/sıvı sıçramasına dayanıklı şeffaf vizör mutlaka kullanılmalıdır.',
                    'Ayak Koruma: EN ISO 20345 standardına uygun çelik/kompozit burunlu, tabanı delinmeye ve yağa dayanıklı iş ayakkabısı giyilmelidir.',
                    'İşitme Koruma: Gürültü maruziyet sınır değerlerinin aşıldığı durumlarda EN 352 uyumlu kulak tıkacı veya manşonlu kulak koruyucu takılmalıdır.',
                    'ÖNEMLİ UYARI - ELDİVEN YASAĞI: Dönen miller, aynalar, takımlar veya hareketli mekanizmalarla çalışırken kesinlikle ELDİVEN KULLANILMAZ. Eldivenin dönen parçaya kapılma riski uzuv kaybı yaratabilir.',
                    'Genel Kılık-Kıyafet: Bol kıyafetler, atkı, kravat, saat, bileklik, yüzük ve kolye yasaktır. Uzun saçlar mutlaka toplanmalı ve koruyucu bone/file içine alınmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ',
                'aciklama' => '',
                'maddeler' => [
                    'Günlük Kontrol: Çalışmaya başlamadan önce \'CNC Günlük Kontrol ve Bakım Formu\'ndaki maddeler eksiksiz denetlenmeli; arıza halinde makineye etiket (LOTO) asılarak amire bildirilmelidir.',
                    'Kabin ve Çevre Kontrolü: Kabin içi ve tezgah çevresindeki talaşlar, yabancı cisimler ve zemin üzerindeki kayganlaştırıcı sıvılar/yağlar temizlenmelidir.',
                    'Acil Durdurma Testi: Emergency Stop (Acil Durdurma) butonlarının serbest ve çalışır vaziyette olduğu doğrulanmalıdır.',
                    'Bağlama Güvenliği: İş parçası bağlama aynası, çeneler, mengene ve pabuçların sıkılık değerleri ve rijitliği parça tipine uygun torkta kontrol edilmelidir.',
                    'Takım Doğrulama: Kesici uçların aşınma, çatlak ve kırık kontrolleri yapılmalı; takım boy ve çap sıfırlama (offset) değerleri kontrol ünitesinde teyit edilmelidir.',
                    'Kuru Çalışma (Dry Run): Yeni, değiştirilmiş veya revize edilmiş CNC programları ilk kez çalıştırılırken mutlaka \'Single Block\' (Tek Blok) ve düşük ilerleme (%10-25 Feedrate Override) ile denenmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SIRASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Kabin Emniyet Kilidi: Çalışma sırasında tezgah kabin kapısı daima kapalı ve kilitli tutulmalıdır. Kapı emniyet sviçleri (interlock) kesinlikle köprülenemez veya devre dışı bırakılamaz.',
                    'Müdahale Yasağı: İş mili, ayna veya takım hareket halindeyken kabin içine uzanılmamalı, parçaya veya talaşa kesinlikle elle temas edilmemelidir.',
                    'Talaş Temizliği: Talaş temizliği yalnızca tezgah tamamen durdurulduktan sonra fırça, tel kanca veya endüstriyel talaş süpürgesi ile yapılmalıdır. Basınçlı hava ile talaş uçurmak KESİNLİKLE YASAKTIR.',
                    'Soğutma Sıvısı: Bor yağı ve soğutma sıvısının akış debisi ve yönü kesme noktasına odaklanmalı, sıçramalara karşı kabin contaları kontrol altında tutulmalıdır.',
                    'Anormal Durumlar: Aşırı titreşim, kıvılcım, duman, koku veya anormal ses hissedildiğinde derhal Acil Durdurma butonuna basılmalı ve işlem durdurulmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Program ve Eksenler: İş bitiminde parça çözülmeli, program resetlenmeli ve eksenler güvenli referans (home) noktasına çekilmelidir.',
                    'Temizlik ve Tahliye: Talaş konveyörü çalıştırılarak kabin içi biriken talaşlar talaş arabasına aktarılmalıdır.',
                    'Enerji Kesme: Mesai bitiminde kontrol paneli kapatılmalı ve ana güç şalteri emniyetli konuma getirilmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'ÜNİVERSAL TORNA TEZGAHI GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'İSG-TLM-TRN-01',
        'aciklama' => 'Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Bu talimat, üniversal torna tezgahlarında gerçekleştirilen silindirik tornalama, alın tornalama, diş açma, delik delme ve raybalama gibi talaşlı imalat operasyonlarında personelin can güvenliğini sağlamak, uzuv kapma ve fırlama kaynaklı iş kazalarını önlemek amacıyla 6331 sayılı İSG Kanunu ve Makine Emniyeti Yönetmeliği\'ne uygun olarak hazırlanmıştır.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)',
                'aciklama' => '',
                'maddeler' => [
                    'Göz ve Yüz Koruma: Fırlayan spiral talaşlara ve kesme sıvısına karşı EN 166 uyumlu yan korumalı iş gözlüğü veya şeffaf yüz siperi mutlaka takılmalıdır.',
                    'Ayak Koruma: EN ISO 20345 standartlarında çelik veya kompozit burunlu, tabanı delinmeye ve ağır iş parçası düşmelerine karşı dayanıklı iş ayakkabısı giyilmelidir.',
                    'İşitme Koruma: Atölye ortamında gürültü seviyesi 80-85 dB(A) eşiğini aştığında EN 352 uyumlu kulak tıkacı veya manşonlu kulaklık kullanılmalıdır.',
                    'HAYATİ UYARI - KESİNLİKLE ELDİVEN TAKILMAZ: Torna tezgahı başında çalışırken KESİNLİKLE ELDİVEN KULLANILMAZ. Dönen fener mili, ayna, iş parçası veya talaşlara eldivenin takılması uzuv kopması ve ölümlü kazalara neden olur.',
                    'Kıyafet ve Aksesuar: Kolları sarkan bol giysiler, atkı, kravat, saat, bileklik, yüzük ve zincir takmak kesinlikle yasaktır. Kıyafet kolları içe doğru sıkıca kıvrılmalı veya lastikli olmalıdır. Uzun saçlar mutlaka toplanıp bone/file içerisine hapsedilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ',
                'aciklama' => '',
                'maddeler' => [
                    'Günlük Ön Kontrol: Operatör çalışmaya başlamadan önce \'Üniversal Torna Günlük Kontrol Formu\'nu doldurmalı, arızalı tezgahta asla çalışmamalıdır.',
                    'Ayna Anahtarı Kuralı (HAYATİ): Ayna anahtarı yay mekanizmalı olmalıdır. Ayna sıkıldıktan sonra anahtar ASLA ayna üzerinde bırakılmamalı, derhal yuvasına konulmalıdır.',
                    'Ayna ve Talaş Siperliği: Tezgah aynasının üzerinde mikroswitchli emniyet muhafazası (siperlik) ve araba üzerinde şeffaf talaş koruyucu mutlaka kapalı tutulmalıdır.',
                    'Acil Durdurma Donanımları: Acil durdurma (E-Stop) butonu ve tezgah boyunca uzanan Acil Durdurma Ayak Pedalı basılarak test edilmeli, fren mekanizması doğrulanmalıdır.',
                    'Punta ve Yataklama Güvenliği: Uzun parçaların işlenmesinde gezer punta ve sabit/hareketli lünet kullanılmalı; fener mili arkasından sarkan uzun çubuklar için boru muhafazası takılmalıdır.',
                    'Kater ve Kalem Bağlama: Kalem kater yuvasına minimum serbest boyda ve rijit bağlanmalı, merkez yüksekliği gezer punta ucu ile tam eksene ayarlanmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SIRASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Elle Talaş Temizliği Yasağı: Dönen iş parçasından çıkan talaşlar ASLA elle çekilerek veya üflenerek alınmaz. Talaşlar makine durdurulduktan sonra talaş kancası veya fırça ile temizlenmelidir.',
                    'Dönerken Müdahale Yasağı: Tezgah çalışırken parça üzerinde kumpas veya mikrometre ile ölçüm yapılmaz, zımparalama veya çapak alma işlemi elle yapılmaz.',
                    'Zımpara Kullanımı: Zımparalama yapılması zorunlu hallerde zımpara kağıdı tahta bir takozun üzerine sarılmalı; iki elle tutularak asla parçaya elle sarılmamalıdır.',
                    'Devir ve İlerleme Ayarı: İş parçası çapına, malzeme cinsine ve kesici uca uygun kesme hızı (devir) seçilmeli; dişli değişimleri motor tamamen durduktan sonra yapılmalıdır.',
                    'Gövdeye Yaslanmama: Tezgaha veya fener mili kutusuna yaslanılmamalı, baş çalışma eksenine aşırı yaklaştırılmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Kapatma ve Frenleme: İş bitiminde kumanda kolu boşa alınmalı, ayna elle tutularak değil ayak freniyle durdurulmalıdır.',
                    'Temizlik ve Düzen: Kızaklar üzerindeki talaşlar fırça ile süpürülmeli, kızak yağlama kanalları temizlenmeli ve hafifçe yağlanmalıdır. Asla basınçlı hava kullanılmamalıdır.',
                    'Enerji Kesme: Mesai bitiminde ana pano şalteri kapatılmalı, zemin üzerindeki yağ ve talaşlar temizlenmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'DİKEY FREZE TEZGAHI GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'İSG-TLM-FRZ-01',
        'aciklama' => 'Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Bu talimat, üniversal dikey freze tezgahlarında yüzey işleme, kanal açma, cep boşaltma, delik delme ve açılı işleme operasyonlarında personelin güvenliğini sağlamak, dönen kesici takımlara ve fırlayan çapaklara bağlı iş kazalarını önlemek amacıyla 6331 sayılı İSG Kanunu ve Makine Emniyeti Yönetmeliği\'ne uygun olarak hazırlanmıştır.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)',
                'aciklama' => '',
                'maddeler' => [
                    'Göz ve Yüz Koruma: Fırlayan sıcak ve keskin çapaklara, soğutma sıvısına karşı EN 166 standartlarında yan korumalı iş gözlüğü veya şeffaf vizör mutlaka kullanılmalıdır.',
                    'Ayak Koruma: EN ISO 20345 standartlarına uygun çelik burunlu, tabanı delinmeye ve ağır parça düşmelerine dayanıklı iş ayakkabısı giyilmelidir.',
                    'İşitme Koruma: Talaş kaldırma esnasında gürültü sınır değerleri aşıldığında EN 352 uyumlu kulak tıkacı veya manşonlu kulaklık takılmalıdır.',
                    'HAYATİ UYARI - ELDİVEN KULLANIMI KESİNLİKLE YASAKTIR: Freze tezgahı başında çalışırken kesinlikle ELDİVEN KULLANILMAZ. Dönen freze çakısı, tarama kafası veya fener miline eldivenin takılması parmak/kol kopmasına neden olur.',
                    'Kılık-Kıyafet Güvenliği: Kolları bol giysiler, önlük bağcıkları, saat, yüzük, kolye yasaktır. Kollar dirsek üzerine kıvrılmalı, saçlar arkada toplanıp bone/file içine alınmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ',
                'aciklama' => '',
                'maddeler' => [
                    'Ön Kontrol: Çalışmaya başlamadan önce \'Dikey Freze Günlük Kontrol Formu\' eksiksiz doldurulmalı; tezgahta arıza varsa çalıştırılmamalıdır.',
                    'Acil Durdurma ve Siperlik: Acil durdurma (E-Stop) butonu ve fener mili çevresindeki mikroswitchli şeffaf çapak koruma siperliği kontrol edilmelidir.',
                    'İş Parçası Rijitliği: İş parçası mengene, pabuç ve T-kanallara uygun torkta bağlanmalı; gevşek veya rijit olmayan bağlamalarda asla talaş kaldırılmamalıdır.',
                    'Takım ve Pens Kontrolü: Freze çakısı, pens başlığı ve malafa cıvatası sıkılığı kontrol edilmeli; aşınmış veya kırık uçlu takımlar kullanılmamalıdır.',
                    'Sıkma Anahtarları: Mengene anahtarı, malafa anahtarı veya alyanlar sıkma işleminden hemen sonra tezgah üzerinden kaldırılmalı, tabla üzerinde bırakılmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SIRASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Elle Müdahale Yasağı: Mil ve kesici takım dönerken parçaya, talaşa veya tablaya kesinlikle elle temas edilmemelidir.',
                    'Talaş Temizliği Disiplini: Talaşlar ASLA elle veya basınçlı hava üflenerek temizlenmez. Tezgâh tamamen durdurulduktan sonra fırça veya talaş kancası kullanılmalıdır.',
                    'Dönerken Ölçüm Yapmama: İş mili dönerken kumpas, mikrometre veya komparatör ile ölçüm yapılmamalı; tezgah durdurulduktan sonra kontrol edilmelidir.',
                    'Devir ve İlerleme Uyumu: Kesici takım çapına, malzeme sertliğine ve talaş derinliğine uygun devir ve otomatik ilerleme seçilmelidir.',
                    'Soğutma Sıvısı Yönü: Bor yağı borusu kesme bölgesine yönlendirilmeli; sıçramalara karşı talaş tavası ve tabla siperlikleri devrede tutulmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Eksenleri Park Etme: Çalışma bittiğinde otomatik ilerleme kolları boşa alınmalı, tabla emniyetli konuma çekilmelidir.',
                    'Temizlik ve Yağlama: Tabla ve T-kanallardaki talaşlar süpürülmeli, kızaklar temizlenip kızak yağı ile yağlanmalıdır.',
                    'Enerji Kesme: Mesai bitiminde ana elektrik şalteri kapatılmalı, zemin kaygan sıvılardan arındırılmalıdır.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'KOMPRESÖR VE HAVA TANKI GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'İSG-TLM-KMP-01',
        'aciklama' => 'Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Bu talimat; işletmede bulunan vidalı / pistonlu hava kompresörleri ile basınçlı hava tanklarının güvenli bir şekilde işletilmesi, bakımının yapılması, patlama, yüksek basınç yaralanmaları ve gürültü risklerinin önlenmesi amacıyla İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği ve Basınçlı Ekipmanlar Yönetmeliği\'ne uygun olarak hazırlanmıştır.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)',
                'aciklama' => '',
                'maddeler' => [
                    'İşitme Koruma: Kompresör dairesinde ve test işlemlerinde yüksek gürültü seviyesine karşı EN 352 uyumlu kulak tıkacı veya manşonlu kulak koruyucu takılmalıdır.',
                    'Göz ve Yüz Koruma: Hava kaçağı denetimi, kondens tahliyesi veya filtre temizliğinde basınçlı partiküllere karşı EN 166 uyumlu iş gözlüğü takılmalıdır.',
                    'Ayak ve El Koruma: EN ISO 20345 çelik burunlu iş ayakkabısı ve mekanik/termal risklere (sıcak yüzeylere) uygun EN 388 / EN 407 koruyucu eldiven kullanılmalıdır.',
                    'HAYATİ UYARI - BASINÇLI HAVA İLE ŞAKA / ÜST-BAŞ TEMİZLİĞİ YASAĞI: Basınçlı hava KESİNLİKLE insan vücuduna tutulamaz, kıyafet veya saç temizliğinde kullanılamaz. Deri altına hava girmesi (hava embolisi), göz körlüğü ve kulak zarı patlaması ölümcül neticelere yol açabilir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ',
                'aciklama' => '',
                'maddeler' => [
                    'Periyodik Kontrol: Kompresör ve hava tankının akredite kuruluşça yapılmış yıllık periyodik kontrol / hidrostatik test etiketinin güncel olduğu teyit edilmelidir.',
                    'Kompresör Dairesi: Kompresör ünitesi çalışanların sürekli bulunduğu alanlardan tecrit edilmiş, havalandırması yeterli ve yangına dayanıklı özel bölmede bulunmalıdır.',
                    'Emniyet Ventili Kontrolü: Hava tankı ve kompresör üzerindeki emniyet ventilinin mühürlü, temiz ve mekanik çekme halkasının rahat hareket eder durumda olduğu kontrol edilmelidir.',
                    'Manometre Doğrulaması: Manometrelerin ibresi sıfır noktasında olmalı, maksimum işletme basıncını gösteren kırmızı emniyet çizgisi açıkça görünür olmalıdır.',
                    'Yağ Seviyesi: Vidalı/pistonlu kompresörün yağ seviye göstergesi kontrol edilmeli; seviye iki çizgi arasında olmalıdır.',
                    'Kayış-Kasnak Muhafazası: Açıkta dönen mekanik aksamlar (kayış-kasnak, kaplin, fan) tam korumalı kafes/muhafaza içinde olmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SIRASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Otomatik Durma/Kalkma (Yük-Boş): Kompresör ayarlanan set basıncına (örneğin 7.5-8 bar) ulaştığında boşa geçmeli, alt basınca düştüğünde yüke girmelidir. Basınç şalteri (presostat) fonksiyonu takip edilmelidir.',
                    'Sıcaklık ve Anormal Ses: Çalışma sırasında vida/kafa çıkış sıcaklığı kontrol edilmeli (kritik sınır aşılmamalı), anormal titreşim, sürtünme veya vuruntu durumunda derhal durdurulmalıdır.',
                    'Kaçak Kontrolü: Hava hatlarında, rakorlarda ve şartlandırıcı bağlantılarında hava kaçağı tespit edilirse basınç altındayken sıkma/onarma yapılmamalıdır.',
                    'Hortum Güvenliği: Yüksek basınçlı hava hortumlarının kırılma, çatlama ve ezilmelere karşı korunduğu, bağlantılarda kamlok/emniyet pimi (çırpınma teli) bulunduğu teyit edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI, SU TAHLİYESİ VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Kondens (Su) Tahliyesi (GÜNLÜK): Hava tankının altında ve filtrelerde biriken su ve yağ karışımı (kondens), otomatik veya manuel tahliye vanası açılarak her gün düzenli boşaltılmalıdır.',
                    'Basınç Düşürme (LOTO): Kompresör veya hava tankında bakım-onarım yapılmadan önce ana şalter kilitlenmeli (LOTO), tanktaki basınç tamamen tahliye edilmeli ve manometrenin sıfırlandığı görülmelidir.',
                    'Hava Emiş Filtresi: Emiş filtreleri periyodik olarak temizlenmeli; tozlu ortamların filtreyi tıkaması önlenmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'EKSTRÜZYON MAKİNESİ GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'İSG-TLM-EKS-01',
        'aciklama' => 'Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Bu talimat; ekstrüzyon hatlarında (profil, boru, levha, film vb.) hammadde besleme, kovan/vida eritme, kalıptan çıkarma, soğutma ve çekici (çekme-kesme) operasyonları esnasında yüksek sıcaklık, ezilme/kapılma, gaz maruziyeti ve elektriksel tehlikeleri önlemek amacıyla 6331 sayılı İSG Kanunu ve Makine Emniyeti Yönetmeliği\'ne uygun olarak hazırlanmıştır.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)',
                'aciklama' => '',
                'maddeler' => [
                    'Termal Koruma (Kalıp & Kovan Bölgesi): Kalıp değişimi, kafa temizliği veya nozul müdahalelerinde sıcak ergimiş polimere ve sıcak yüzeylere karşı EN 407 standardına uygun ısıya dayanıklı eldiven ve kolluk kullanılmalıdır.',
                    'Göz ve Yüz Koruma: Kalıp önü temizliği, gaz çıkışı ve basınçlı eriyik püskürme riskine karşı EN 166 standartlarında polikarbonat yüz siperi veya tam korumalı iş gözlüğü takılmalıdır.',
                    'Solunum Koruma: Eriyik polimer gazları, bozunma dumanları ve katkı maddelerine karşı EN 14387 standardına uygun ABEK filtreli yarım yüz gaz maskesi hazır bulundurulmalı ve kullanılmalıdır.',
                    'Ayak ve Vücut Koruma: EN ISO 20345 çelik/kompozit burunlu iş ayakkabısı giyilmeli; sentetik olmayan, aleve/ısıya dayanıklı pamuklu iş elbisesi tercih edilmelidir.',
                    'DÖNEN AKSAMDA DİKKAT: Çekici merdaneler, sarıcılar veya açıkta dönen helezon besleyiciler yakınında çalışırken bol giysiler, sarkan aksesuarlar yasaktır; saçlar toplanmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ',
                'aciklama' => '',
                'maddeler' => [
                    'Ön Kontrol ve Sıcaklık Doğrulama: Günlük kontrol formu doldurulmalı; kovan ısıtıcı bölgelerinin (rezistanslar) set sıcaklıklarına ulaştığı ve polimerin tam ergidiği doğrulanmalıdır (Soğuk vida kesinlikle döndürülmemelidir; mil kırma ve aşırı basınç riski).',
                    'Emniyet Donanımları: Acil durdurma (E-Stop) butonları, kovan koruma kapakları ve çekici merdane acil emniyet telleri/çubukları fiziksel olarak test edilmelidir.',
                    'Hammadde Haznesi (Bunker): Bunker besleme boğazındaki koruyucu ızgara kesinlikle sökülmemeli; hammadde akışını kontrol etmek için hazneye el veya yabancı cisim sokulmamalıdır.',
                    'Yerel Emiş Havalandırması: Kalıp kafası ve gaz alma (degazör) bölgelerindeki lokal egzoz/davlumbaz havalandırma sisteminin çalıştığı doğrulanmalıdır.',
                    'Soğutma Suyu Hatları: Kalıp ve soğutma teknesi su sirkülasyonunun açık olduğu, sızıntı veya tıkanıklık olmadığı kontrol edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SIRASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Kalıp Önünde Durmama (Püskürme Riski): İlk yol verme veya arıza sonrası başlatma esnasında kalıp çıkış ekseninin tam karşısında kesinlikle DURULMAMALIDIR (Basınç sıkışması sonucu sıcak eriyik patlaması tehlikesi).',
                    'Çekici ve Merdane Güvenliği: Profilin/ürünün çekici merdanelere verilmesi sırasında el merdane kıstırma noktasına sokulmamalı; yönlendirme emniyetli aparatlarla yapılmalıdır.',
                    'Kalıp Temizliği (Purge): Kafa temizliği sadece özel pirinç/bakır spatulalar ve ısıya dayanıklı KKD ile yapılmalıdır. Kesici, sert çelik aletlerle kalıba vurulmamalıdır.',
                    'Basınç ve Akım Takibi: Kovan içi eriyik basıncı (Melt Pressure) ve ana motor akımı takip edilmeli; basınç pik yaptığında besleme derhal durdurulmalıdır.',
                    'Kesici Ünite Güvenliği: Otomatik giyotin, testere veya döner bıçak koruma kapakları kapalı tutulmalı; kapak açıldığında makineyi durduran switchler köprülenmemelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Hammadde Boşaltma / Temizleme (Purge): Üretim bitiminde kovan içindeki yanıcı/bozunan hammadde temizleme granülü ile tahliye edilmeli, vida devri sıfırlanmalıdır.',
                    'Rezistans ve Isıtıcıların Kapatılması: Isıtıcı kademeleri kontrollü şekilde kapatılmalı, kovan soğuma periyoduna alınmalıdır.',
                    'LOTO Kuralı: Kalıp sökümü, vida çekme veya kovan içi bakımda ana şalter kilitlenmeli (LOTO), rezistans enerjisi ve hidrolik basınç tamamen kesilmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'TRAFO MERKEZİ VE YG TESİSLERİ GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'İSG-TLM-TRF-01',
        'aciklama' => 'Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Bu talimat, tesis bünyesindeki Yüksek Gerilim / Alçak Gerilim (YG/AG) Transformatör Merkezleri, hücreler ve ana dağıtım panolarında yürütülen kontrol, manevra ve periyodik gözetim faaliyetlerinde elektrik çarpması, ark patlaması (arc flash), yangın ve zehirlenme risklerini önlemek amacıyla Elektrik Kuvvetli Akım Tesisleri Yönetmeliği, Elektrik Tesislerinde Topraklamalar Yönetmeliği ve 6331 sayılı İSG Kanunu\'na uygun olarak hazırlanmıştır.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'KİŞİSEL KORUYUCU DONANIMLAR (KKD)',
                'aciklama' => '',
                'maddeler' => [
                    'Ark ve Termal Koruma: Manevra ve kontrollerde IEC 61482 standardına uygun ark koruyucu elbise, ark korumalı vizörlü kask veya başlık kullanılmalıdır.',
                    'Yalıtkan Ayak ve El Koruma: Hücre gerilim sınıfına uygun (sertifikalı ve periyodik testli) EN 60903 sınıfına uygun dielektrik yalıtkan eldiven ve EN 50321 yalıtkan çizme/bot kullanılmalıdır.',
                    'Yalıtkan Manevra Donanımı: Hücre açma/kapama ve topraklama işlemlerinde gerilim seviyesine uygun yalıtkan ıstanka (stank), neon lambalı gerilim kontrol dedektörü ve topraklama pensleri kullanılmalıdır.',
                    'Genel Koruma: Metal aksesuarlar (saat, yüzük, künye, metal tokalı kemer) kesinlikle çıkarılmalı; %100 pamuklu veya alev iletmez kıyafet giyilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ ALINACAK GÜVENLİK ÖNLEMLERİ',
                'aciklama' => '',
                'maddeler' => [
                    'Yetkisiz Giriş Yasağı: Trafo merkezine yalnızca \'Yüksek Gerilim İşletme Sorumlusu\' veya yetkilendirilmiş Elektrik Bakım Personeli girebilir. Kapı sürekli kilitli ve uyarı levhalı olmalıdır.',
                    'EKAT Belgesi ve İzin: Manevra ve çalışmalarda personelin geçerli EKAT (Elektrik Kuvvetli Akım Tesislerinde Çalışma) belgesine sahip olması ve Çalışma İzin Formu düzenlenmesi zorunludur.',
                    'Merkez Ortamı & Havalandırma: Trafo odasında yabancı malzeme, hurda veya kimyasal depolanamaz. Havalandırma panjurları açık ve tel kafesleri sağlam olmalıdır.',
                    'Emniyet Donanımları Kontrolü: Hücre önlerindeki yalıtkan paspasların (dielektrik halı), acil durum aydınlatmasının ve yangın söndürücülerin sağlamlığı doğrulanmalıdır.',
                    'Nem ve Su Tehdidi: Kablo kanallarında su birikintisi, rutubet veya tavandan damlama olup olmadığı kontrol edilmeli; ıslak zeminde kesinlikle manevra yapılmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SIRASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    '5 Altın Emniyet Kuralı: 1- Gerilimi kes, 2- Yeniden verilmesini kilitle (LOTO), 3- Gerilim yokluğunu dedektörle doğrula, 4- Topraklama ve kısa devre yap, 5- Çalışma alanını barikatla ve işaretle.',
                    'Manevra Sırasında Pozisyon: Hücre kesici manevralarında operatör kapak ekseninde değil, yan tarafta durmalı; ark parlaması riskine karşı vizör mutlaka kapalı olmalıdır.',
                    'Gösterge ve Yağ Takibi: Trafo yağ sıcaklığı, Buchholz rölesi sinyalleri, yağ seviye göstergesi ve silikajel (nem alıcı) renk değişimi takip edilmelidir.',
                    'Anormal Ses ve Koku: Aşırı vızıltı (harmonik ses), ark cızırtısı, yanık yağ veya ozon kokusu hissedildiğinde derhal amire bilgi verilmeli ve güvenli mesafeye çekilmelidir.',
                    'Tek Başına Çalışma Yasağı: Trafo merkezinde YG hücrelerinde bakım ve müdahaleler KESİNLİKLE tek başına yapılamaz; en az 2 yetkili personel bulunmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Manevra Tamamlama ve Kilit: İşlem bittiğinde topraklama pensleri ve kilitler sırayla sökülmeli, hücre kapakları tam kilitlenmelidir.',
                    'Topraklama Ekipmanlarının Düzeni: Yalıtkan stanka, gerilim kontrol dedektörü ve topraklama kabloları kuru ve korumalı askılarına yerleştirilmelidir.',
                    'Kapı Kilitleme ve Defter: Trafo binası anahtarla kilitlenmeli, yapılan gözlem veya manevralar \'Trafo Merkezi İşletme Defteri\'ne işlenmelidir.',
                ],
            ],
        ],
    ],
];
