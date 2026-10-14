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
 * TLT-VALS-01: Acik_Vals_Tezgahi_Guvenli_Calisma_Talimati_ve_Kontrol_Formu.docx
 * TLT-MER-01: Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx
 * TLT-BZM-02: Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx
 * TLT-SEZ-03: Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx
 * TLT-EKS-04: Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx
 * TLT-PUR-05: Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx
 * TLT-KIR-06: Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx
 * TLT-TAS-07: Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx
 * TLT-SPK-01: Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx
 * TLT-KRD-02: Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx
 * TLT-SMV-03: Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx
 * TLT-PLZ-04: Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx
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
    [
        'baslik' => 'AÇIK VALS TEZGÂHI İSG GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-VALS-01',
        'aciklama' => 'Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Bu talimat; açık vals tezgâhında kauçuk/plastik hamuru yoğurma, karıştırma ve ezme işlemleri esnasında meydana gelebilecek silindirlere uzuv kapılması, sıkışma, ezilme, fırlama ve yanık risklerini ortadan kaldırmak üzere uyulması zorunlu kuralları belirler.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI – KESİNLİKLE ELDİVEN KULLANILMAZ!',
                'aciklama' => '',
                'maddeler' => [
                    'Döner merdaneli açık vals tezgâhında çalışırken ELDİVEN KULLANILMASI KESİNLİKLE YASAKTIR. (Eldiven kumaşının merdaneler arasına kapılması halinde eli ve kolu hızla içeri çekerek ölümcül kazalara yol açar.)',
                    'Bol kıyafet, sarkıntılı atkı/önlük ipi, saat, yüzük ve kolye kesinlikle yasaktır. Uzun saçlar bone/baret içine hapsedilmelidir.',
                    'Zorunlu KKD: S3 Çelik Burunlu Kaymaz Tabanlı İş Ayakkabısı, Dar Manşetli İş Elbisesi, EN 166 Koruyucu Gözlük ve Kulak Koruyucu.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ GÜVENLİK KONTROLLERİ',
                'aciklama' => '',
                'maddeler' => [
                    'Acil durdurma göğüs/diz emniyet barı, çekme halatı ve acil stop butonları tezgâh boşta iken BİZZAT TEST EDİLMELİDİR.',
                    'Merdanelerin acil frenleme mesafesi (en fazla çeyrek tur içinde anında durma) kontrol edilmelidir.',
                    'Kayış-kasnak, zincir ve dişli muhafaza kapaklarının tam kapalı, sabit ve kilitli olduğu doğrulanmalıdır.',
                    'Tezgâh tabanındaki kaymaz ızgara/ped kontrol edilmeli; zeminde kaymaya neden olacak yağ/sıvı olmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK GÜVENLİK KURALLARI',
                'aciklama' => '',
                'maddeler' => [
                    'Tezgâha malzeme beslenirken eller asla silindirlerin tepe noktasından (ısırıcı \'nip\' bölgesi) aşağıya sokulmamalıdır.',
                    'Hamur kesme, sıyırma veya çevirme işlemlerinde eller asla merdaneye temas ettirilmemeli; uygun ahşap/pirinç spatula kullanılmalıdır.',
                    'Silindire sarılan hamur veya yapışan malzeme tezgâh dönerken elle çekilerek kurtarılmaya çalışılmamalıdır.',
                    'Tezgâh çalışırken operatör kesinlikle yerinden ayrılmamalı; dikkat dağıtıcı unsurlarla (cep telefonu vb.) ilgilenilmemelidir.',
                ],
            ],
            [
                'baslik' => 'ACİL DURUM MÜDAHALE ADIMLARI',
                'aciklama' => '',
                'maddeler' => [
                    'Uzuv kapılma/sıkışma durumunda operatör derhal göğsüyle emniyet barına abanmalı veya acil stop ipini çekmelidir.',
                    'Diğer personel derhal acil durdurma butonuna basmalı ve varsa \'Ters Dönüş (Reverse) / Merdane Hızlı Açma\' mekanizmasını tetiklemelidir.',
                    'Sıkışan uzuv asla çekilmemeli, merdaneler mekanik olarak açılarak kazazede kurtarılmalı ve derhal 112 Acil Yardım aranmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'İş bitiminde makine kapatılmalı ve ana panodan şalter indirilerek EKED (Etiketle-Kilitle-Emniyete Al-Dene) kuralı uygulanmalıdır.',
                    'Merdaneler tamamen durmadan ve enerji kesilmeden hiçbir surette temizlik veya bakım yapılmamalıdır.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'SEYYAR EL MERDİVENİ GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-MER-01',
        'aciklama' => 'Portatif Dayamalı / A-Tipi Alüminyum ve Ahşap Merdivenler — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'İşletme genelinde bakım, temizlik ve malzeme erişiminde seyyar el merdivenlerinin kullanımında yüksekten düşme ve kayma risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG KURALI: \'3 NOKTA TEMAS\' & 4\'E 1 YASLAMA KURALI!',
                'aciklama' => '',
                'maddeler' => [
                    'Merdivene tırmanırken ve inerken yüz daima basamaklara dönük olmalı ve her zaman en az 3 nokta (2 el 1 ayak veya 2 ayak 1 el) temas etmelidir.',
                    'Dayamalı merdivenler 4:1 (75 derece) eğim açısıyla kurulmalı, tepe noktasından veya tabanından sabitlenmelidir.',
                    'Merdiven basamaklarında taşınamayacak ağır yüklerle çalışılamaz; son 3 basamağa kesinlikle çıkılmaz!',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Merdivenin basamaklarında, takozlarında (pabuçlarında) çatlak, ezilme, yağ veya eğilme olmadığı kontrol edilmelidir.',
                    'A-tipi mafsallı merdivenlerde gergi makası/emniyet zinciri kilitlenmeden merdivene çıkılmamalıdır.',
                    'Kaygan zeminlerde, çamurda, ıslak veya eğimli yüzeylerde merdiven pabuçları sabitlenmeden kullanılmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Merdiven üzerinde gövde ağırlık merkezi yan kirişlerin dışına taşırılmamalıdır (uzanma hareketi yasaktır).',
                    'Aynı merdiven üzerinde asla birden fazla kişi aynı anda çalışmamalıdır.',
                    'Kapı arkalarına veya araç trafiği olan yollara kurulurken bariyer çekilmeli veya ikinci bir gözlemci refakat etmelidir.',
                    'Elektrikli panoların veya açık hatların yakınında metal/alüminyum iletken merdivenler kesinlikle kullanılamaz (fiberglas tercih edilir).',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Merdivenler zemin üzerinde gelişi güzel bırakılmamalı, asılarak veya özel rafında kilitli muhafaza edilmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'SÜTUNLU ZIMPARA MAKİNESİ (BANT ZIMPARA) GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-BZM-02',
        'aciklama' => 'Ayaklı / Sütunlu Bant Zımpara ve Çapak Alma Tezgâhı — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Parça çapak alma ve yüzey zımparalama işlemlerinde toz soluma, kıvılcım fırlaması, bant kopması ve uzuv kapılma risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: DÖNER BANTTA ELDİVEN YASAKTIR!',
                'aciklama' => '',
                'maddeler' => [
                    'Hızlı dönen zımpara bandı ve tahrik kasnağında kumaş/örme eldiven kapılma riski yaratır; parça boyutuna göre mengene/tutucu aparat kullanılmalıdır.',
                    'Zorunlu KKD: EN 166 Gözlük veya Yüz Siperi, FFP2 Toz Maskesi, Dar paçalı iş elbisesi, Çelik burunlu ayakkabı.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Zımpara bandında yırtık, ek yerinde açılma veya aşırı aşınma olmadığı kontrol edilmelidir.',
                    'Bant gergi mekanizması ve ayar kolu kontrol edilmeli, bant boşta elle çevrilerek merkezleme kontrol edilmelidir.',
                    'Acil durdurma (E-Stop) pedalı veya butonu test edilmeli, toz emiş ünitesi çalışır durumda olmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'İşlenecek küçük parçalar doğrudan elle değil, özel tutucu kıskaç veya mengene ile zımparaya verilmelidir.',
                    'Parça zımpara bandına aşırı baskı ile bastırılmamalıdır (bant kopmasına yol açar).',
                    'Kıvılcım ve tozların yönlendirilmesi için siperlik daima kapalı konumda tutulmalıdır.',
                    'Makine çalışırken bant gerginlik ayarı veya temizlik yapılmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'İş bitiminde makine durdurulmalı, talaş tepsisindeki ve filtredeki metal/toz artıkları vakumla temizlenmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'PLASTİK ÇEKME / EZME MERDANESİ GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-SEZ-03',
        'aciklama' => 'Silindir Çekici / Ezici Kalibrasyon Ünitesi — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Ekstrüzyon hattından çıkan plastik profil/levhaların çift silindir arasından çekilmesi ve ezilmesinde ezilme, sıkışma ve kapılma risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: MERDANE GİRİŞİNE ELLE MÜDAHALE YASAKTIR!',
                'aciklama' => '',
                'maddeler' => [
                    'Merdanelerin çekme (ısırıcı) bölgesine profil yönlendirilirken kesinlikle elle itilmez; kılavuz çubuk/pense kullanılır.',
                    'Döner merdane başında çalışırken bol kıyafet, takı, eldiven kesinlikle yasaktır!',
                    'Zorunlu KKD: S3 İş Ayakkabısı, Dar kollu cepsiz iş kıyafeti, Koruyucu Gözlük.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Merdane önündeki tel/bar tipi acil emniyet switchinin çalışıp çalışmadığı denenmelidir.',
                    'Elektrik panosu kapağının kapalı olduğu ve motor redüktör yağ seviyesi kontrol edilmelidir.',
                    'Merdane baskı ayar yayları/pnömatik pistonların dengeli bastığı kontrol edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Plastik şerit veya fitil merdanelere beslenirken güvenli mesafede durulmalı, merdane açıklığı elle kontrol edilmemelidir.',
                    'Merdane yüzeyine sarılan malzeme makine dönerken elle veya maket bıçağıyla kazınmamalıdır.',
                    'Hız kontrolü (hız potansiyometresi) ekstrüder çıkış hızına senkronize ayarlanmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Merdaneler durdurulduktan ve ana şalter kilitlendikten (EKED) sonra pirinç kazıyıcı ile temizlenmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'PLASTİK EKSTRÜZYON HATTI (EKSTRUDER) GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-EKS-04',
        'aciklama' => 'Kovan-Vida Isıtıcılı Plastik Profil / Granül Ekstrüzyon Makinesi — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Termoplastik hammadde ergitme ve şekillendirme hattında yüksek sıcaklık (yanık), elektrik, mekanik kapılma ve toksik gaz soluma risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: 200°C+ YÜKSEK SICAKLIK VE YANIK RİSKİ!',
                'aciklama' => '',
                'maddeler' => [
                    'Rezistans kovanına ve kafaya (die) çıplak elle dokunulmaz; müdahalede EN 407 Isıya Dayanıklı Eldiven zorunludur.',
                    'Bunker (huni) besleme boğazına yabancı metal parçası düşürülmemelidir; boğaza asla el sokulmaz!',
                    'Kafa sökümünde kovan içi basıncın tahliye edildiğinden emin olunmalıdır (patlama/fışkırma riski).',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Isıtıcı rezistansların sıcaklık kontrol cihazları (PID) set edilen değerlere ulaştığı teyit edilmelidir (soğuk çalıştırma vida kırma riski yaratır).',
                    'Soğutma teknesindeki su seviyesi ve devridaim pompası kontrol edilmelidir.',
                    'Bunker emniyet ızgarasının yerinde ve sabit olduğu kontrol edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Hammadde çuvalları kaldırılırken ergonomi kurallarına uyulmalı, bunker dolumunda toz maskesi takılmalıdır.',
                    'Kalıp kafasından çıkan ilk ergimiş plastik parçalar maşa veya pirinç spatula ile alınmalıdır.',
                    'Hammadde tıkandığında boğaza metal boru veya el sokulmaz, standart tahta tokmak kullanılır.',
                    'Havalandırma davlumbazı plastik dumanını tahliye etmek üzere sürekli açık tutulmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Isıtıcılar kapatılmalı, hat temizliği için kafa temizlenmeli ve ana şalter indirilmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'PÜRMÜZ VE PİKNİK TÜPÜ (LPG TAVLAMA SETİ) GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-PUR-05',
        'aciklama' => 'Kalıp Isıtma / Tavlama ve Yüzey Yakma LPG Şaloma Ekipmanı — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Kalıp ısıtma ve çapak yakma amacıyla kullanılan portatif LPG tüpü ve şaloma setinde gaz kaçağı, yangın, patlama ve yanık risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: GAZ KAÇAĞI KONTROLÜ ASLA ATEŞLE YAPILMAZ!',
                'aciklama' => '',
                'maddeler' => [
                    'Kaçak kontrolü sadece KÖPÜK / SABUNLU SU veya gaz detektörü ile yapılır.',
                    'Tüp kesinlikle devrilmeyecek şekilde sabitlenmeli veya dik durmalıdır; yatık kullanılmaz!',
                    'Çalışma alanında 1 metre mesafede yangın söndürme tüpü (KKT) hazır bulundurulmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'LPG tüpü vanası, contası ve şaloma hortumunun çatlak, yıpranma veya gevşeklik taşımadığı kontrol edilmelidir.',
                    'Hortum kelepçelerinin sıkı olduğu teyit edilmeli, tel sarılarak yapılan bağlantılar KESİNLİKLE YASAKTIR.',
                    'Tüpün yakınında yanıcı kimyasal (tiner, solvent, plastik tozu) bulunmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Ateşleme sadece çakmaktaşlı şaloma çakmağı veya pürmüz butonuyla yapılmalı; alev asla insanlara veya tüpe yöneltilmemelidir.',
                    'Pürmüz alevli vaziyette yere veya tezgâha asla bırakılmamalıdır.',
                    'İş sırasında ortam havalandırması sağlanmalı; kapalı dar hacimlerde LPG birikmesi engellenmelidir.',
                    'Tüp aşırı sıcak kaynakların veya güneşin altına konulmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Önce tüpün ana vanası kapatılarak hortumdaki gazın yanıp bitmesi beklenmeli, alev söndükten sonra pürmüz vanası kapatılmalıdır.',
                    'Soğuma sağlanana kadar pürmüz ucu yanıcı zeminle temas ettirilmemelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'PLASTİK KIRMA MAKİNESİ (GRANÜL KRICI) GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-KIR-06',
        'aciklama' => 'Bunkerli Plastik Takoz / Çapak ve Fire Kırma Değirmeni — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Plastik geri dönüşümünde döner bıçaklara uzuv kaptırma, fırlayan plastik parça çarpması, yüksek gürültü ve toz maruziyetini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: BUNKER İÇİNE ASLA EL VE KOL SOKULMAZ!',
                'aciklama' => '',
                'maddeler' => [
                    'Bunker kapağı emniyet switchi asla köprülenemez (by-pass edilemez). Kapak açıldığında rotor anında durmalıdır!',
                    'Malzeme sıkışmasında makine tamamen durup ana şalter kilitlenmeden (EKED) müdahale edilemez!',
                    'Zorunlu KKD: EN 166 Gözlük/Siperlik, EN 352 Kulak Koruyucu, Dar manşetli elbise, S3 Ayakkabı.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Bunker emniyet limit switchi (interlock) kapağın açılmasıyla motoru kesiyor mu kontrol edilmelidir.',
                    'Acil durdurma mantar butonu test edilmeli, volan ve kayış-kasnak muhafazalarının kilitli olduğu doğrulanmalıdır.',
                    'Kırma haznesinde unutulmuş metal el aleti (anahtar, çekiç vb.) olmadığından emin olunmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Plastik takozlar atılırken bunker içine eğilinmemeli; geri fırlayan parçalara karşı yüz siperi takılmalıdır.',
                    'Malzeme bunkere itilirken eller içeri sokulmaz, sadece standart tahta tokmak kullanılır.',
                    'Metal, taş veya sert cisimlerin kırıcıya girmesi kesinlikle engellenmelidir (bıçak patlaması riski).',
                    'Makine çalışırken alt toplama çekmecesi açılmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Bıçak bileme veya temizlik işlemlerinde rotor mekanik pimle kilitlenmeli ve EKED kilidi asılmalıdır.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'ÇİFT TAŞLAMA MOTORU (ZIMPARA TAŞ MOTORU) GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-TAS-07',
        'aciklama' => 'Sehpalı / Masaüstü Çift Taraflı Çapak Alma ve Bileme Taş Motoru — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Takım bileme ve çapak alma işlemlerinde taş patlaması, parça sıkışması, kıvılcım fırlaması ve görme kaybı risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: DAYAMA BOŞLUĞU MAKSİMUM 3 MM OLMALIDIR!',
                'aciklama' => '',
                'maddeler' => [
                    'Parça dayama sehpası ile taş arasındaki açıklık 3 mm\'den fazla olamaz (parça sıkışıp taşı patlatır).',
                    'Taşın yan yüzeyiyle kesinlikle taşlama yapılmaz (taş kırılma riski).',
                    'Çatlak taş kesinlikle takılmaz (\'Çınlama Testi\' yapılmalıdır)!',
                    'Çapak alma sırasında kumaş eldiven kullanılamaz; parça maşayla tutulmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Taş muhafazalarının 180 dereceden fazla açılmadığı ve sağlam çelik saçtan olduğu kontrol edilmelidir.',
                    'Şeffaf çapak koruma siperliklerinin ayarlı ve temiz olduğu kontrol edilmelidir.',
                    'Dayama mesafesi kontrol edilmeli (Dayama tablası max 3 mm, üst siperlik max 6 mm).',
                    'Taş boşta çalıştırılarak salgı veya yalpalama yapıp yapmadığı 1 dakika gözlenmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'İş parçası taşa yavaşça ve kademeli yaklaştırılmalı, taşa sert darbe vurulmamalıdır.',
                    'Çok küçük parçalar elle tutularak taşlanmaz; özel mengene veya kıskaç kullanılmalıdır.',
                    'Taş çalışırken taşın tam ekseninde (karşısında) değil, hafif yanında durulmalıdır.',
                    'Taş dönmeye devam ederken tezgâh başından ayrılınmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Taş motoru kapatıldıktan sonra taşın kendi kendine durması beklenmeli, parça bastırılarak durdurulmamalıdır.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'SPİRAL HAVA KANALI MAKİNESİ (DFC MACHINERY) GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-SPK-01',
        'aciklama' => 'Rulo Sac Beslemeli Kenetlemeli Yuvarlak Spiral Boru İmalat Tezgâhı — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Galvaniz rulo sacdan spiral kenetli yuvarlak havalandırma borusu bükme ve kenetleme hattında döner silindir kapması, kesik ve gürültü risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: SAC BESLEME RULOLARINA VE DÖNER ŞAFTA MÜDAHALE YASAKTIR!',
                'aciklama' => '',
                'maddeler' => [
                    'Rulo sac kılavuz makaralara girerken kesinlikle çıplak elle itilmez veya tutulmaz; kesilmeye dirençli eldiven (EN 388 Level 4/5) kullanılmalıdır.',
                    'Makine otomatik büküm ve dairesel uçan testere/bıçakla kesim yaparken kesim kafası siperliği asla açılmamalıdır.',
                    'Acil durumlarda dokunmatik panel altındaki E-Stop mantar butonuna basılmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Dokunmatik operatör paneli (HMI) ve \'EMERGENCY STOP\' butonunun fonksiyonelliği test edilmelidir.',
                    'Rulo açıcı sehpadaki sac kılavuz makaralarının ve baskı merdanelerinin boşluk ayarları kontrol edilmelidir.',
                    'Form verme ve kenetleme makaralarının (kenet kafası) yağlama seviyesi teyit edilmelidir.',
                    'Uçan kesici testere/bıçak koruma siperliğinin kapalı ve kilitli olduğu kontrol edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Sac rulo yüklenirken vinç veya istifleyici kullanılmalı, sac açılma fırlamasına karşı çember kesilirken dikkat edilmelidir.',
                    'Kanal üretimi sırasında otomatik kenet form makaralarının arasına el veya herhangi bir alet sokulmamalıdır.',
                    'Çıkan spiral borular boy sehpasından alınırken keskin sac kenarlarına karşı koruyucu eldiven takılmalıdır.',
                    'Hammadde sıkışması halinde makine dokunmatik ekrandan durdurulmadan ve hidrolik/pnömatik basınç kesilmeden müdahale edilmemelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Vardiya bitiminde makine ana şalterden kapatılmalı, kordon ve makara yüzeyleri çapaklardan fırça yardımıyla temizlenmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'MOTORLU PEDALLI KORDON TEZGÂHI GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-KRD-02',
        'aciklama' => 'Ayak Pedallı Sac Kanal Boğaz Açma / Kordon Çekme Makinesi (Kısmet / DFC) — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Yuvarlak ve dirsek havalandırma kanallarının boğazlarına kordon çekme, kenet payı açma ve ezme işlemlerinde merdane kapması ve açık dişli risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: AÇIK DİŞLİ VE KAYIŞ-KASNAK KORUYUCUSUZ ÇALIŞTIRILAMAZ!',
                'aciklama' => '',
                'maddeler' => [
                    'DİKKAT: Fotoğraftaki makinede açıkta bulunan çift kademeli dişli çarklar ve tahrik kasnağı KORUYUCU SAC MUHAFAZASIZ ASLA ÇALIŞTIRILAMAZ! (Fotoğraftaki muhafazasız durum acil giderilmelidir.)',
                    'Kordon makaraları arasına parça yönlendirirken KESİNLİKLE KUMAŞ ELDİVEN KULLANILMAZ (kapılma anında eli içeri çeker).',
                    'Ayak pedalı yaylı koruma kılıflı (pedal muhafazalı) olmalı, istemsiz basılmaya karşı korunmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Açıkta bulunan tahrik dişlilerinin ve V-kayış kasnağının muhafaza kapağının takılı ve kilitli olduğu kontrol edilmelidir.',
                    'Ayak kumanda pedalının takılma yapmadığı, ayağı çekince motoru anında kestiği kontrol edilmelidir.',
                    'Üst mil ayar kolunun ve kordon makarası baskı vidasının düzgün sıktığı test edilmelidir.',
                    'Tezgâh gövdesinin zemine cıvatalı ve sarsıntısız olduğu teyit edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Kordon açılacak silindirik sac parça iki elle yan yüzeylerinden tutulmalı, parmaklar makara ağzına 5 cm\'den fazla yaklaştırılmamalıdır.',
                    'Pedala basarken dikkatin tamamen iş parçası ve makara hizasında olması sağlanmalıdır.',
                    'Makara arasına sıkışan parça makine çalışırken ve pedal basılıyken elle çekilmeye çalışılmamalıdır.',
                    'Tezgâh çalışırken mil mesafesi veya kordon derinlik ayarı kesinlikle yapılmamalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'İş bitiminde açma/kapama şalteri (0) konumuna getirilmeli, tezgâh tabanındaki sac talaşları fırçayla süpürülmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'MOTORLU SAC SİLİNDİR BÜKÜM MAKİNESİ (VALS) GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-SMV-03',
        'aciklama' => '3 Toplu Mekanik / Motorlu Sac Bükme ve Kanal Yuvarlama Valsi — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Kanal imalatında sac levhaların dairesel kıvrılması ve boru haline getirilmesinde merdane arasına el/kol kapılması, açık dişli ve malzeme çarpma risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: AÇIK DİŞLİ ÇARKLARA VE MERDANE AĞZINA YAKLAŞMA YASAKTIR!',
                'aciklama' => '',
                'maddeler' => [
                    'FOTOĞRAFTAKİ TEHLİKE: Silindirin sağ tarafındaki açıkta çalışan tahrik dişli çarkları kapaksız KESİNLİKLE ÇALIŞTIRILAMAZ!',
                    'Merdanelere sac sürülürken eller merdane temas çizgisine (ısırıcı \'nip\' noktasına) asla 10 cm\'den fazla yaklaştırılmamalıdır.',
                    'Tezgâh çevresinde acil emniyet teli/halatı veya acil stop pedalı mutlaka çalışır durumda olmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Sağ kısımdaki açık ayna ve tahrik dişlilerinin muhafaza kapağının yerinde olduğu kontrol edilmelidir.',
                    'Acil durdurma teli / butonu tezgâh boşta çalıştırılarak test edilmelidir.',
                    'Üst top açma kilit mekanizmasının (boru çıkartma mil kapağı) kilitli ve emniyette olduğu teyit edilmelidir.',
                    'Merdane paralellik ayar vidalarının her iki tarafta eşit ayarlandığı kontrol edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Bükülecek sac levha iki elle dış uçlarından tutularak sürülmeli; sac merdaneye girdiğinde sacın arkasından itilmemelidir.',
                    'Kıvrılan sacın serbest ucu yukarı kalkarken operatörün göğsüne veya yüzüne çarpmaması için güvenli mesafede durulmalıdır.',
                    'Bükülen yuvarlak boru çıkarılırken üst mil yatağı açılmadan önce motor tamamen durdurulmalıdır.',
                    'Merdaneler dönerken silindir üzerindeki sac talaşı veya pas elle silinmemeli, fırça kullanılmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'İş bitiminde üst mil kilitlenmeli, ana elektrik şalteri indirilerek kilitlenmelidir.',
                ],
            ],
        ],
    ],
    [
        'baslik' => 'CNC PLAZMA SAC KESİM TEZGÂHI & ÇAPAK MASASI GÜVENLİ ÇALIŞMA TALİMATI',
        'kategori' => 'is_makineleri',
        'dokuman_no' => 'TLT-PLZ-04',
        'aciklama' => 'Kanal Parçaları Kesim Masası, Izgaralı Toplama Tablası ve Kaynak Gazı Tüpü — Kullanıcı talimatı, Rev. 00',
        'kkdler' => [],
        'maddeler' => [],
        'bolumler' => [
            [
                'baslik' => 'AMAÇ VE KAPSAM',
                'aciklama' => 'Havalandırma dirsek, redüksiyon ve kanal saclarının plazma kesiminde ve çapak temizliğinde kesik, ultraviyole ışın, duman soluma ve devrilme risklerini önlemek.',
                'maddeler' => [],
            ],
            [
                'baslik' => 'HAYATİ İSG UYARISI: SAC KESİKLERİ VE GAZ TÜPÜ DEVRİLME RİSKİ!',
                'aciklama' => '',
                'maddeler' => [
                    'Kesilen galvaniz parçalar son derece keskin jilet kenarlara sahiptir; saclar toplanırken mutlaka EN 388 Seviye 5 Kesilmez Eldiven kullanılmalıdır.',
                    'Masadaki parçalar gelişigüzel yığılmamalı, istif yüksekliği ve devrilme riski kontrol edilmelidir.',
                    'Arkada duran basınçlı gaz tüpü KESİNLİKLE ZİNCİRLE SABİTLENMELİ, koruma başlığı takılı olmalıdır!',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ÖNCESİ KONTROLLER',
                'aciklama' => '',
                'maddeler' => [
                    'Kesim ızgaralarının aşırı erimemiş olduğu ve parçaların ızgara arasına düşmeyeceği kontrol edilmelidir.',
                    'Plazma kesim masası altındaki duman emiş fanının ve filtre sisteminin çalıştığı kontrol edilmelidir.',
                    'Basınçlı gaz tüpünün duvara veya arabaya zincirle sabitlendiği teyit edilmelidir.',
                    'Çapak temizleme çekici ve keskilerinin mantarlaşmamış (baş kısmında çatlak/saçaklanma olmayan) olduğu kontrol edilmelidir.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA ESNASINDA UYULACAK KURALLAR',
                'aciklama' => '',
                'maddeler' => [
                    'Plazma kesim torcu aktifken doğrudan ışığa çıplak gözle bakılmamalı (DIN 5 kaynak gözlüğü şarttır).',
                    'Izgara üzerinde biriken kesilmiş dirsek parçaları eldivenle dikkatlice toplanmalı ve taşıma kasalarına düzgün istiflenmelidir.',
                    'Çapak alma çekiciyle vururken fırlayan sac parçacıklarına karşı koruyucu gözlük mutlaka takılmalıdır.',
                    'Masa üzerinde gelişi güzel çekiç, boru ve levyeler bırakılmamalı, düzenli tutulmalıdır.',
                ],
            ],
            [
                'baslik' => 'ÇALIŞMA SONRASI VE BAKIM',
                'aciklama' => '',
                'maddeler' => [
                    'Izgara altı cüruf çekmecesi dolduğunda temizlenmeli, gaz tüpü vanası mesai bitiminde kapatılmalıdır.',
                ],
            ],
        ],
    ],
];
