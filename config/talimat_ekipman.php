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
];
