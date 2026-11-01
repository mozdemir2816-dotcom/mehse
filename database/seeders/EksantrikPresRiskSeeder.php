<?php

namespace Database\Seeders;

use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
use Illuminate\Database\Seeder;

/**
 * Risk kütüphanesi: "Ekipman – Eksantrik pres" klasörü (01.11.2026, kullanıcı isteği).
 * Anahtar config isg.risk_hizli_kurulum.ekipmanlar.eksantrik_pres ile birebir aynı olmalı.
 * Puanlar Fine-Kinney ölçeğinde (O/F/Ş: 0.2-10 / 0.5-10 / 1-100); 5x5 değerleri `matris`
 * sütununda (RiskKutuphanesi::maddeyeCevir 5x5 yönteminde bunları aynen kullanır).
 * Klasör yöntemi boş (ortak) — 5x5 ve Fine-Kinney sihirbazlarında görünür.
 * Tekrar çalıştırılabilir (anahtar / kod ile günceller). Canlı için: deploy/canli-eksantrik-pres-01-11-2026.sql
 */
class EksantrikPresRiskSeeder extends Seeder
{
    public const ANAHTAR = 'ekipman_eksantrik_pres';

    public function run(): void
    {
        $kategori = TehlikeKategorisi::updateOrCreate(
            ['anahtar' => self::ANAHTAR],
            ['ad' => 'Ekipman – Eksantrik pres', 'yontem' => null, 'sira' => 500],
        );

        foreach (self::maddeler() as $i => $m) {
            Tehlike::updateOrCreate(
                ['kod' => sprintf('EKP-%03d', $i + 1)],
                ['tehlike_kategorisi_id' => $kategori->id, 'bolum' => 'Eksantrik Pres'] + $m,
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function maddeler(): array
    {
        $m = fn (string $faaliyet, string $tehlike, string $risk, string $mevcut, string $oneri, string $mevzuat,
            array $fk, array $fkSon, array $x5, array $x5Son, string $sorumlu, string $termin) => [
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

        return [
            $m('Pres operasyonu',
                'Işık bariyeri (AOPD) emniyet mesafesinin yetersiz olması ve fonksiyon testinin yapılmaması',
                'Kök neden: Işık perdesi, koç durma süresi ölçülmeden koç bölgesine çok yakın monte edilmiş; vardiya başı test çubuğu ile fonksiyon kontrolü yapılmıyor. Operatör eli perdeyi geçip koç durmadan kalıp bölgesine ulaşabilir → parmak/el ezilmesi, uzuv kaybı.',
                'Işık bariyeri mevcut; montaj mesafesi ve durma süresi ölçüm kaydı yok, günlük test formu tutulmuyor.',
                'Koç durma süresi (T) ölçülmeli ve emniyet mesafesi EN ISO 13855’e göre hesaplanmalı: S = K × T + C (K = 2000 mm/s; S > 500 mm çıkarsa K = 1600 mm/s ile yeniden hesap; C = 8 × (d − 14) mm, d: bariyer çözünürlüğü). Bariyer hesaplanan mesafeye taşınmalı, kenarlardan ve üstten erişim sabit koruyucuyla kapatılmalı. Vardiya başı test çubuğuyla fonksiyon testi yapılıp forma işlenmeli; bariyer devre dışı bırakılamayacak (anahtarlı/şifreli) olmalı.',
                'Makine Emniyeti Yönetmeliği (2006/42/AT) Ek-I; İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; TS EN ISO 16092-1, TS EN ISO 16092-2; TS EN ISO 13855; TS EN 61496',
                [6, 6, 40], [0.2, 6, 40], [4, 5], [1, 5],
                'Bakım Şefi / Üretim Sorumlusu', 'Derhal — vardiya başı test sürekli'),

            $m('Pres operasyonu',
                'Çift el kumandasında 0,5 saniye eşzamanlılık şartının sağlanmaması ve koruyucu yakanın uygunsuz olması',
                'Kök neden: Butonlardan biri bantlanmış/sabitlenmiş ya da yakası olmayan butonlar dirsek veya tek elle basılabiliyor; eşzamanlılık (0,5 s) denetimi olmayan röle devresi. Operatör tek eliyle kalıp bölgesindeyken koç inebilir → el ezilmesi.',
                'Çift el kumandası mevcut; butonlar yakasız, eşzamanlılık ve bırakınca durma testi yapılmıyor.',
                'EN ISO 13851’e uygun (en az Tip IIIC) çift el kumanda ünitesi kullanılmalı: iki butona 0,5 s içinde basılmadıkça çevrim başlamamalı, butonlardan biri bırakılınca koç durmalı. Butonlar koruyucu yakalı ve birbirinden yeterli mesafede olmalı (tek elle/dirsekle basılamamalı); ünite EN ISO 13855’e göre hesaplanan mesafeye konmalı. Butonların köprülenmesi disiplin konusu yapılmalı, vardiya başı test edilmeli.',
                'Makine Emniyeti Yönetmeliği (2006/42/AT) Ek-I; İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği (kumanda sistemleri); TS EN ISO 13851; TS EN ISO 13855; TS EN ISO 16092-2',
                [3, 6, 40], [0.2, 6, 40], [3, 5], [1, 5],
                'Bakım Şefi / Operatör', 'Derhal'),

            $m('Pres operasyonu / bakım',
                'Volan, kayış-kasnak açık mekanizması ve kamalı (mekanik) kavramada çift vuruş (istem dışı tekrar) riski',
                'Kök neden: Volan ve kayış-kasnak koruyucusu sökülmüş; eski tip kamalı kavramada kama/yay aşınması nedeniyle koç tek çevrim yerine ikinci kez iniyor. Kayışa kapılma, volan çarpması ve beklenmedik ikinci vuruşla el ezilmesi.',
                'Volan koruyucusu kısmen var; kavrama ve kama düzeneği periyodik bakım planında yok.',
                'Volan, kayış-kasnak ve dişliler EN ISO 14120’ye uygun sabit (alet olmadan sökülemeyen) koruyucularla tamamen kapatılmalı. Kamalı kavramalı preslerde kama, yay ve kavrama düzeneği üretici talimatına göre bakımdan geçirilmeli; mümkünse pnömatik kavrama-fren grubuna ve tekrar (çift vuruş) önleyici emniyet devresine dönüştürülmeli. Tek vuruş modu dışında çalışma yalnız uygun koruyucularla yapılmalı.',
                'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği (hareketli parçalar, koruyucular); Makine Emniyeti Yönetmeliği (2006/42/AT) Ek-I; TS EN ISO 14120; TS EN ISO 16092-2',
                [3, 6, 40], [0.2, 6, 40], [3, 5], [1, 5],
                'Bakım Şefi', '1 ay'),

            $m('Kalıp değişimi / bakım-onarım',
                'Kalıp değişimi ve bakımda LOTO (kilitleme / etiketleme) uygulanmaması',
                'Kök neden: Kalıp değişimi ve arıza müdahalesinde yalnız kumanda butonundan durdurma yapılıyor; ana şalter kilitlenmiyor, basınçlı hava boşaltılmıyor, volan durması beklenmiyor. Başka bir çalışanın presi çalıştırması veya artık enerjiyle koçun inmesi → ezilme, ölüm.',
                'Yazılı LOTO talimatı, kişisel asma kilit ve etiket yok.',
                'Prese özel yazılı LOTO talimatı hazırlanmalı: durdur → ana şalteri kapat ve kişisel asma kilitle kilitle → etiket as → pnömatik/hidrolik artık enerjiyi boşalt → volanın tam durmasını bekle → çalıştırmayı deneyerek enerjisizliği doğrula. Her bakımcıya kişisel kilit ve etiket verilmeli, çoklu kilitleme aparatı kullanılmalı; talimat presin üzerine asılmalı ve eğitim kayıt altına alınmalı.',
                'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği (bakım işlemleri, enerji kaynaklarından ayırma); Güvenlik ve Sağlık İşaretleri Yönetmeliği; 6331 sayılı İş Sağlığı ve Güvenliği Kanunu',
                [3, 3, 40], [0.5, 3, 40], [3, 5], [1, 5],
                'Bakım Şefi / İSG Uzmanı', '1 hafta — sürekli uygulama'),

            $m('Kalıp değişimi / ayar',
                'Koç emniyet takozunun (mekanik blokaj çubuğu) kullanılmaması',
                'Kök neden: Kalıp ayarı ve değişiminde koç yalnız fren/hidrolik ile tutuluyor; emniyet takozu yok ya da takoz takılıyken presin çalışmasını engelleyen kilitleme bulunmuyor. Fren kaçırması veya yanlışlıkla çalıştırmada koç düşer → ezilme, ölüm.',
                'Emniyet takozu yok / kullanılmıyor.',
                'Koç ile tabla arasına yükü taşıyacak kapasitede emniyet takozu (blokaj çubuğu) temin edilmeli; takoz yuvasından alındığında pres ana kumandasını kesen elektrik kilitlemeli emniyet svici (EN ISO 14119) ile takoz takılıyken presin çalışması engellenmeli. Kalıp altında yapılan her işte takoz kullanımı LOTO talimatına eklenmeli.',
                'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; Makine Emniyeti Yönetmeliği (2006/42/AT) Ek-I; TS EN ISO 16092-1; TS EN ISO 14119',
                [3, 3, 40], [0.2, 3, 40], [3, 5], [1, 5],
                'Bakım Şefi', '1 ay'),

            $m('Kalıp bağlama',
                'Standart dışı kalıp bağlama pabuçları ve cıvatalar nedeniyle kalıbın kayması',
                'Kök neden: Kalıp, farklı boy ve kalitede cıvatalar, yamuk pabuçlar ve araya konan rastgele takozlarla bağlanıyor; tork değeri bilinmiyor. Çalışma sırasında kalıp kayar veya parçalanır → parça fırlaması, kalıp düşmesi, ezilme.',
                'Bağlama elemanları standart değil, tork kontrolü yapılmıyor.',
                'Tabla T-kanalına uygun standart kalıp bağlama pabuçları ve kalite sınıfı belirli cıvatalar kullanılmalı; hasarlı/uzamış cıvatalar ayıklanmalı. Kalıp bağlama sonrası tork anahtarıyla sıkma ve kontrol listesiyle onay yapılmalı. Kalıplar taşıma noktaları belirlenmiş şekilde uygun kaldırma ekipmanıyla taşınmalı.',
                'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; TS EN ISO 16092-1',
                [3, 3, 15], [1, 3, 15], [3, 4], [1, 4],
                'Kalıp Ayarcısı / Bakım Şefi', '1 ay'),

            $m('Pres operasyonu',
                'Pnömatik kavrama-fren grubunda basınç emniyet şalteri arızası ve gecikmeli duruş',
                'Kök neden: Kavrama-fren hava basıncı düştüğünde presi durduran basınç şalteri devre dışı/arızalı; emniyet valfi (çift gövdeli) izlenmiyor, fren balataları aşınmış. Koç geç duruyor veya istem dışı iniyor; ışık bariyeri mesafesi de geçersiz hâle geliyor → el ezilmesi.',
                'Basınç şalteri ve fren durma süresi periyodik olarak kontrol edilmiyor.',
                'Basınç emniyet şalteri çalışır hâle getirilmeli ve basınç düşüşünde presi durduracak şekilde bağlanmalı. Kavrama-fren kumandasında izlemeli çift gövdeli emniyet valfi kullanılmalı. Fren durma süresi periyodik ölçülmeli (durma süresi ölçer) ve ışık bariyeri mesafesi bu değere göre doğrulanmalı; balatalar üretici talimatına göre yenilenmeli. Pres, yetkili kişilerce periyodik kontrole tabi tutulmalı.',
                'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği (periyodik kontroller, kumanda sistemleri); TS EN ISO 16092-2; TS EN ISO 13849-1; TS EN ISO 13855',
                [3, 6, 40], [0.2, 6, 40], [3, 5], [1, 5],
                'Bakım Şefi', '1 ay — periyodik ölçüm'),

            $m('Elle parça besleme',
                'Ayak pedalı ile çalışmada ve elle parça beslemede koruyucusuz çalışma',
                'Kök neden: Pres ayak pedalıyla çalıştırılıyor; parça kalıba elle konup alınıyor ve kalıp bölgesinde koruyucu/ışık perdesi yok. Pedala istem dışı basma veya el kalıptayken çevrimin başlaması → parmak/el kopması.',
                'Pedal muhafazasız, kalıp bölgesi açık.',
                'Elle beslemede kalıp bölgesi ışık bariyeri veya kapalı (ayarlanabilir) koruyucuyla korunmalı; ayak pedalı yalnız bu koruma varken kullanılmalı ve istem dışı basmayı önleyen kapaklı (muhafazalı) tip olmalı. Mümkünse çift el kumandasına geçilmeli. El kalıp içine girmeden besleme için itici, maşa veya vakumlu tutucu gibi el aletleri kullanılmalı; operatörler bu yöntemle eğitilmeli.',
                'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; Makine Emniyeti Yönetmeliği (2006/42/AT) Ek-I; TS EN ISO 16092-1; TS EN ISO 16092-2; TS EN ISO 13855',
                [6, 6, 40], [0.2, 6, 40], [4, 5], [1, 5],
                'Üretim Sorumlusu / Operatör', 'Derhal'),
        ];
    }
}
