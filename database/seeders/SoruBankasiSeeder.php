<?php

namespace Database\Seeders;

use App\Models\SoruBankasiSorusu;
use Illuminate\Database\Seeder;

/**
 * Soru Bankası başlangıç havuzu — sistem/ortak sorular (user_id NULL), hepsi
 * onaylı. Uzman kendi sorularını panelden ekler. Idempotent: soru metnine göre
 * firstOrCreate.
 */
class SoruBankasiSeeder extends Seeder
{
    public function run(): void
    {
        foreach (static::sorular() as $s) {
            SoruBankasiSorusu::firstOrCreate(
                ['soru' => $s['soru'], 'user_id' => null],
                [
                    'sektor_anahtari' => $s['sektor'] ?? null,
                    'konu' => $s['konu'],
                    'zorluk' => $s['zorluk'] ?? 'orta',
                    'secenekler' => $s['secenekler'],
                    'dogru_index' => $s['dogru_index'],
                    'aciklama' => $s['aciklama'] ?? null,
                    'kaynak' => $s['kaynak'] ?? null,
                    'durum' => 'onaylandi',
                    'uretim_kaynagi' => 'seed',
                    'onaylayan' => 'Sistem',
                    'onay_tarihi' => now(),
                ],
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private static function sorular(): array
    {
        return [
            // --- Genel İSG / Mevzuat ---
            [
                'konu' => 'genel_isg', 'zorluk' => 'kolay',
                'soru' => 'İş sağlığı ve güvenliği ile ilgili temel kanun hangisidir?',
                'secenekler' => ['4857 sayılı İş Kanunu', '6331 sayılı İş Sağlığı ve Güvenliği Kanunu', '5510 sayılı Kanun', '2872 sayılı Çevre Kanunu'],
                'dogru_index' => 1,
                'aciklama' => 'İSG ile ilgili temel çerçeve kanun 6331 sayılı Kanundur.',
                'kaynak' => '6331 sayılı İSG Kanunu',
            ],
            [
                'konu' => 'genel_isg', 'zorluk' => 'orta',
                'soru' => 'İş güvenliği önlemlerinde uygulanması gereken öncelik sırası nedir?',
                'secenekler' => [
                    'Önce KKD, sonra mühendislik önlemleri',
                    'Önce tehlikeyi kaynağında yok etme, sonra toplu koruma, en son KKD',
                    'Önce eğitim, sonra KKD, en son mühendislik',
                    'Sıralama önemli değildir',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Kontrol hiyerarşisi: eliminasyon > ikame > mühendislik/toplu koruma > idari önlemler > KKD.',
                'kaynak' => '6331 sK m.5 (risklerden korunma ilkeleri)',
            ],
            [
                'konu' => 'calisan_haklari', 'zorluk' => 'orta',
                'soru' => 'Ciddi ve yakın tehlike ile karşılaşan çalışan ne yapabilir?',
                'secenekler' => [
                    'Yalnızca amirine haber verip çalışmaya devam eder',
                    'Gerekli tedbir alınana kadar çalışmaktan kaçınabilir ve çalışma yerini terk edebilir',
                    'Hiçbir şey yapamaz, işvereni beklemek zorundadır',
                    'Doğrudan istifa etmek zorundadır',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Çalışan, kurula/işverene başvurup gerekli tedbir alınmazsa çalışmaktan kaçınabilir; bu süre ücretinden sayılır.',
                'kaynak' => '6331 sK m.13',
            ],
            [
                'konu' => 'risk_degerlendirmesi', 'zorluk' => 'kolay',
                'soru' => 'Risk değerlendirmesi kim(ler) tarafından yapılır/yürütülür?',
                'secenekler' => [
                    'Sadece işveren tek başına',
                    'İşveren; İGU, işyeri hekimi, çalışan temsilcileri ve destek elemanlarından oluşan ekiple',
                    'Sadece çalışanlar',
                    'Sadece Bakanlık müfettişleri',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Risk değerlendirmesi işverenin sorumluluğunda, belirtilen ekip tarafından yürütülür.',
                'kaynak' => 'İSG Risk Değerlendirmesi Yönetmeliği m.6',
            ],

            // --- Acil durum / yangın / ilk yardım ---
            [
                'konu' => 'acil_durum', 'zorluk' => 'kolay',
                'soru' => 'İşyerinde acil durum tahliyesinde temel kural nedir?',
                'secenekler' => [
                    'Asansör kullanarak hızla inmek',
                    'Eşyaları toplamak için geri dönmek',
                    'Panik yapmadan, en yakın acil çıkıştan toplanma alanına gitmek',
                    'Yerinde beklemek',
                ],
                'dogru_index' => 2,
                'aciklama' => 'Tahliyede asansör kullanılmaz, geri dönülmez; işaretli acil çıkıştan toplanma alanına gidilir.',
                'kaynak' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik',
            ],
            [
                'konu' => 'yangin', 'zorluk' => 'orta',
                'soru' => 'Elektrik panosu kaynaklı bir yangında hangi söndürücü kullanılmalıdır?',
                'secenekler' => ['Su', 'Köpük', 'Kuru kimyevi toz veya CO2', 'Kum ile boğma'],
                'dogru_index' => 2,
                'aciklama' => 'Elektrik (E sınıfı) yangınlarında iletken olmayan kuru kimyevi toz veya CO2 kullanılır; su kullanılmaz.',
                'kaynak' => 'Binaların Yangından Korunması Hakkında Yönetmelik',
            ],
            [
                'konu' => 'ilk_yardim', 'zorluk' => 'kolay',
                'soru' => 'İş kazası sonrası olay yerinde ilk yapılması gereken nedir?',
                'secenekler' => [
                    'Yaralıyı hemen hareket ettirmek',
                    'Olay yeri güvenliğini sağlamak ve 112’yi aramak',
                    'Fotoğraf çekmek',
                    'Kimseye haber vermeden beklemek',
                ],
                'dogru_index' => 1,
                'aciklama' => 'İlk yardımın temel adımı: kendini ve yaralıyı güvenceye al (olay yeri güvenliği), sonra 112.',
                'kaynak' => 'İlk Yardım Yönetmeliği',
            ],

            // --- KKD / ergonomi / elle taşıma ---
            [
                'konu' => 'kkd', 'zorluk' => 'kolay',
                'soru' => 'Kişisel koruyucu donanım (KKD) ile ilgili aşağıdakilerden hangisi doğrudur?',
                'secenekler' => [
                    'KKD ilk önlem olarak seçilmelidir',
                    'KKD, toplu koruma önlemleri yetersiz kaldığında son çare olarak kullanılır ve ücretsiz sağlanır',
                    'KKD bedeli çalışandan tahsil edilir',
                    'KKD kullanımı çalışanın isteğine bağlıdır',
                ],
                'dogru_index' => 1,
                'aciklama' => 'KKD son koruma katmanıdır; işveren ücretsiz sağlar ve kullanımı zorunludur.',
                'kaynak' => 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Yönetmeliği',
            ],
            [
                'konu' => 'elle_tasima', 'zorluk' => 'orta',
                'soru' => 'Yükü elle kaldırırken doğru teknik hangisidir?',
                'secenekler' => [
                    'Belden eğilerek, bacaklar düz',
                    'Dizleri bükerek, sırtı dik tutarak, yükü gövdeye yakın kaldırmak',
                    'Yükü kollarla uzakta tutarak',
                    'Ani ve hızlı bir hareketle',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Doğru teknik: dizden çök, sırtı dik tut, yükü gövdeye yakın tut, bacak kaslarıyla kaldır.',
                'kaynak' => 'Elle Taşıma İşleri Yönetmeliği',
            ],
            [
                'konu' => 'ergonomi', 'zorluk' => 'kolay',
                'soru' => 'Ekranlı araçlarla sürekli çalışanlar için önerilen mola düzeni nedir?',
                'secenekler' => [
                    'Günde bir kez 1 saat mola',
                    'Düzenli aralıklarla kısa molalar veya iş değişikliği',
                    'Mola gerekmez',
                    'Sadece öğle molası yeterli',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Ekranlı araç çalışmasında düzenli kısa aralar / görev çeşitlendirmesi önerilir.',
                'kaynak' => 'Ekranlı Araçlarla Çalışmalarda SG Yönetmeliği',
            ],

            // --- Elektrik / iş ekipmanları ---
            [
                'konu' => 'elektrik', 'zorluk' => 'orta',
                'soru' => 'Elektrikle çalışmada "beş altın kural"dan biri değildir?',
                'secenekler' => [
                    'Gerilimi kesmek',
                    'Tekrar gerilim verilmesini önlemek (kilitleme-etiketleme)',
                    'Gerilim yokluğunu kontrol etmek',
                    'Çalışma bittikten sonra topraklamayı hemen sökmek',
                ],
                'dogru_index' => 3,
                'aciklama' => 'Beş kural: kes, tekrar verilmesini önle, gerilim yokluğunu doğrula, kısa devre et ve toprakla, komşu gerilimli kısımları yalıt. Topraklama iş bitene kadar kalır.',
                'kaynak' => 'Elektrik Kuvvetli Akım Tesisleri Yönetmeliği / TS EN 50110',
            ],
            [
                'konu' => 'is_ekipmanlari', 'zorluk' => 'orta',
                'soru' => 'Kaldırma ekipmanlarının periyodik kontrol süresi (aksi belirtilmedikçe) ne sıklıkta yapılır?',
                'secenekler' => ['Ayda bir', 'Yılda bir', 'İki yılda bir', 'Beş yılda bir'],
                'dogru_index' => 1,
                'aciklama' => 'İş Ekipmanları Yönetmeliği Ek-III’e göre kaldırma ve iletme ekipmanları kural olarak yılda bir kontrol edilir.',
                'kaynak' => 'İş Ekipmanlarının Kullanımında SG Şartları Yönetmeliği Ek-III',
            ],

            // --- İnşaat sektörü ---
            [
                'sektor' => 'insaat', 'konu' => 'yuksekte_calisma', 'zorluk' => 'orta',
                'soru' => 'Yapı işlerinde yüksekte çalışmada kollektif koruma önlemi olarak öncelik hangisidir?',
                'secenekler' => [
                    'Paraşüt tipi emniyet kemeri',
                    'Korkuluk sistemleri, güvenlik ağları gibi toplu koruyucular',
                    'Sadece baret',
                    'Uyarı levhası',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Önce toplu koruma (korkuluk, ağ, platform); bunlar mümkün değilse bireysel düşme durdurma sistemi.',
                'kaynak' => 'Yapı İşlerinde İSG Yönetmeliği Ek-4',
            ],
            [
                'sektor' => 'insaat', 'konu' => 'kazi', 'zorluk' => 'orta',
                'soru' => 'Kazı çalışmalarında göçük riskine karşı alınacak önlem hangisidir?',
                'secenekler' => [
                    'Kazı kenarına malzeme yığmak',
                    'Şevlendirme, iksa (destekleme) veya kazı sandığı kullanmak',
                    'Kazıyı hızlı bitirmek',
                    'Sadece baret takmak',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Zemin türüne göre şev açısı verilir veya iksa/kazı sandığı ile yan duvarlar desteklenir; kenara yük konmaz.',
                'kaynak' => 'Yapı İşlerinde İSG Yönetmeliği Ek-4',
            ],
            [
                'sektor' => 'insaat', 'konu' => 'kkd', 'zorluk' => 'kolay',
                'soru' => 'Şantiyede baret kullanımı ne zaman zorunludur?',
                'secenekler' => [
                    'Sadece vinç çalışırken',
                    'Şantiye alanına girişten çıkışa kadar sürekli',
                    'Sadece yağmurlu havada',
                    'İsteğe bağlı',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Şantiyede düşen cisim riski süreklidir; baret alan boyunca zorunludur.',
                'kaynak' => 'Yapı İşlerinde İSG Yönetmeliği',
            ],

            // --- Fabrika / üretim ---
            [
                'sektor' => 'fabrika', 'konu' => 'is_ekipmanlari', 'zorluk' => 'orta',
                'soru' => 'Dönen/hareketli parçası olan tezgahlarda temel güvenlik önlemi nedir?',
                'secenekler' => [
                    'Makine koruyucularının (muhafaza) takılı ve sağlam olması',
                    'Operatörün eldiven takması yeterlidir',
                    'Makineyi yavaş çalıştırmak',
                    'Uyarı levhası asmak yeterlidir',
                ],
                'dogru_index' => 0,
                'aciklama' => 'Ezilme/kapılma riskine karşı sabit veya kilitlemeli hareketli koruyucular esastır; koruyucu sökülüyse makine çalıştırılmaz.',
                'kaynak' => 'İş Ekipmanları Yönetmeliği / Makine Emniyeti Yönetmeliği',
            ],
            [
                'sektor' => 'fabrika', 'konu' => 'gurultu_titresim', 'zorluk' => 'orta',
                'soru' => 'Günlük gürültü maruziyeti hangi seviyeyi aştığında işveren kulak koruyucu kullandırmak zorundadır?',
                'secenekler' => ['80 dB(A)', '85 dB(A)', '90 dB(A)', '95 dB(A)'],
                'dogru_index' => 1,
                'aciklama' => 'En yüksek maruziyet eylem değeri 85 dB(A)’dır; bu değerde KKD kullanımı ve işaretleme zorunludur (80 dB(A) alt eylem değeri).',
                'kaynak' => 'Çalışanların Gürültü ile İlgili Risklerden Korunmalarına Dair Yönetmelik',
            ],

            // --- Kimyasal ---
            [
                'konu' => 'kimyasal', 'zorluk' => 'kolay',
                'soru' => 'Tehlikeli kimyasalların güvenli kullanımı için başvurulacak temel belge hangisidir?',
                'secenekler' => ['İrsaliye', 'Güvenlik Bilgi Formu (GBF/SDS)', 'Fatura', 'Garanti belgesi'],
                'dogru_index' => 1,
                'aciklama' => '16 bölümlük Güvenlik Bilgi Formu (SDS) kimyasalın tehlikeleri, korunma ve acil durum bilgilerini içerir.',
                'kaynak' => 'Zararlı Maddeler ve Karışımlara İlişkin GBF Hakkında Yönetmelik',
            ],
            [
                'konu' => 'kimyasal', 'zorluk' => 'orta',
                'soru' => 'Kimyasalların depolanmasında aşağıdakilerden hangisi yanlıştır?',
                'secenekler' => [
                    'Birbiriyle reaksiyona giren kimyasalları ayrı depolamak',
                    'Asit ve bazları aynı rafta yan yana tutmak',
                    'Havalandırmalı, etiketli ve dökülme havuzlu alan kullanmak',
                    'SDS’lere göre uyumsuzluk tablosuna uymak',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Asit ve bazlar uyumsuzdur; teması şiddetli reaksiyon/ısı açığa çıkarır, ayrı bölümlerde saklanır.',
                'kaynak' => 'Kimyasal Maddelerle Çalışmalarda SG Önlemleri Yönetmeliği',
            ],

            // --- Sağlık sektörü ---
            [
                'sektor' => 'saglik', 'konu' => 'biyolojik', 'zorluk' => 'orta',
                'soru' => 'Sağlık çalışanında iğne batması (perkütan yaralanma) sonrası ilk adım nedir?',
                'secenekler' => [
                    'Yarayı sıkıp kanatmak ve bol su-sabunla yıkamak, sonra bildirim yapmak',
                    'Yarayı ağıza almak',
                    'Hiçbir şey yapmadan çalışmaya devam etmek',
                    'Yarayı alkol dışında bir şeyle kapatmamak',
                ],
                'dogru_index' => 0,
                'aciklama' => 'Yara su-sabunla yıkanır (ovalanmaz), kaynak hasta serolojisi değerlendirilir, olay kayıt altına alınıp gerekli profilaksi başlatılır.',
                'kaynak' => 'Sağlık Kurum ve Kuruluşlarında Kesici Delici Alet Yaralanmalarının Önlenmesi Genelgesi',
            ],

            // --- Depo / lojistik ---
            [
                'sektor' => 'depo', 'konu' => 'trafik_saha', 'zorluk' => 'kolay',
                'soru' => 'Forklift operasyon alanında yaya güvenliği için en etkili önlem hangisidir?',
                'secenekler' => [
                    'Yaya ve araç yollarını fiziksel olarak ayırmak, geçişleri işaretlemek',
                    'Yayaların dikkatli olmasını beklemek',
                    'Forklifti hızlı kullanmak',
                    'Sadece korna çalmak',
                ],
                'dogru_index' => 0,
                'aciklama' => 'İç saha trafiğinde yaya-araç ayrımı (bariyer/çizgi), kör nokta aynaları ve hız sınırı temel önlemlerdir.',
                'kaynak' => 'İşyeri Bina ve Eklentilerinde Alınacak SG Önlemlerine İlişkin Yönetmelik',
            ],
            [
                'sektor' => 'depo', 'konu' => 'ellecleme_istif', 'zorluk' => 'kolay',
                'soru' => 'Raf sistemlerinde devrilme riskini azaltmak için ne yapılır?',
                'secenekler' => [
                    'Ağır yükleri üst raflara koymak',
                    'Rafları zemine sabitlemek ve yük kapasitesi etiketine uymak',
                    'Rafları duvardan uzak tutmak yeterlidir',
                    'Raf ayaklarına çarpma korumasını kaldırmak',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Raflar zemine ankajlanır, ağır yük altta tutulur, kapasite aşılmaz, ayaklara çarpma bariyeri konur.',
                'kaynak' => 'TS EN 15635 (raf sistemleri kullanımı)',
            ],

            // --- İş kazası / meslek hastalığı ---
            [
                'konu' => 'is_kazasi', 'zorluk' => 'orta',
                'soru' => 'İş kazası, kazadan sonra SGK’ya en geç ne zaman bildirilmelidir?',
                'secenekler' => ['Aynı gün', 'Kazadan sonraki 3 iş günü içinde', '1 hafta içinde', '1 ay içinde'],
                'dogru_index' => 1,
                'aciklama' => 'İşveren iş kazasını kazadan sonraki 3 iş günü içinde SGK’ya bildirmek zorundadır.',
                'kaynak' => '6331 sK m.14 / 5510 sK m.13',
            ],
            [
                'konu' => 'is_kazasi', 'zorluk' => 'kolay',
                'soru' => '"Ramak kala" (kazaya ramak kalan olay) ne anlama gelir?',
                'secenekler' => [
                    'Yaralanma veya hasarla sonuçlanan olay',
                    'Yaralanma/hasara yol açmayan ancak açabilecek olay',
                    'Sadece maddi hasarlı kaza',
                    'Meslek hastalığı',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Ramak kala; zarara yol açmayan ama koşullar biraz farklı olsaydı kazaya dönüşebilecek olaydır ve kayıt altına alınmalıdır.',
                'kaynak' => '6331 sK m.14',
            ],
            [
                'konu' => 'meslek_hastaligi', 'zorluk' => 'orta',
                'soru' => 'Meslek hastalığı şüphesi olan çalışan için doğru yaklaşım nedir?',
                'secenekler' => [
                    'Görmezden gelmek',
                    'İşyeri hekimine yönlendirmek, gerekli tetkik/sevk ve SGK bildirim sürecini işletmek',
                    'Çalışanı hemen işten çıkarmak',
                    'Sadece dinlenme izni vermek',
                ],
                'dogru_index' => 1,
                'aciklama' => 'Şüphe halinde işyeri hekimi değerlendirir, yetkili sağlık kuruluşuna sevk edilir ve tanı halinde SGK’ya bildirilir.',
                'kaynak' => '6331 sK m.14 / SGK mevzuatı',
            ],
        ];
    }
}
