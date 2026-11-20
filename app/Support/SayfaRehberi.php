<?php

namespace App\Support;

use App\Filament\Pages;
use App\Filament\Resources\Calisans\CalisanResource;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;

/**
 * Her ana modülün sağ üstünde gösterilen "❓ Nasıl Kullanılır?" 3 adımlı görsel
 * rehber kütüphanesi — isgpratik sayfa içi yardım kutusu karşılığı.
 */
class SayfaRehberi
{
    /**
     * @param  array<int, string>  $scopes
     * @return array{baslik: string, ozet: string, adimlar: array<int, string>, ipucu: string}|null
     */
    public static function bul(array $scopes): ?array
    {
        $harita = static::harita();

        foreach ($scopes as $scope) {
            if (isset($harita[$scope])) {
                return $harita[$scope];
            }
        }

        return null;
    }

    /**
     * @return array<class-string, array{baslik: string, ozet: string, adimlar: array<int, string>, ipucu: string}>
     */
    public static function harita(): array
    {
        return [
            Pages\AnaSayfa::class => [
                'baslik' => 'Ana Sayfa & Portföy Özeti',
                'ozet' => 'Tüm firmalarınızın günlük İSG durumunu, yaklaşan süreleri ve açık saha aksiyonlarını tek bakışta izleyin.',
                'adimlar' => [
                    'Üst kartlardan geciken evrakları, yaklaşan muayene/eğitim sürelerini ve bugünkü ziyaretleri kontrol edin.',
                    'Sol menüden sık kullandığınız sayfaları ★ yıldızlayarak en üste sabitleyin.',
                    'Detaylı firma ve çalışan uyumu için İSG Komuta Merkezi sayfasına geçin.',
                ],
                'ipucu' => 'Ctrl+K (Mac: ⌘K) kısayoluyla herhangi bir firma, çalışan veya modüle anında ulaşabilirsiniz.',
            ],
            Pages\KontrolMerkezi::class => [
                'baslik' => 'İSG Komuta Merkezi',
                'ozet' => 'Portföy genelindeki tüm yasal zorunlulukları, çalışan eksiklerini ve firma uyum matrisini tek ekrandan yönetin.',
                'adimlar' => [
                    'Üstten bir firma seçerek Firma Asistanı, Çalışan Asistanı ve ⚡ Hızlı Evrak Matrisi sekmelerini inceleyin.',
                    'Eksik bir kritere tıklayıp "İlk Eksik Firmadan Başla →" bağlantısıyla firmalar arası seri evrak tamamlayın.',
                    'Sağ üstten "📦 Hızlı Evrak Paketi (ZIP)" veya "📊 İşveren Uyum Karnesi (PDF)" indirip işverene 30 günlük linkle paylaşın.',
                ],
                'ipucu' => 'Hızlı Evrak Matrisi sekmesinden işverene şifresiz, 30 gün geçerli salt-okunur uyum karnesi bağlantısı gönderebilirsiniz.',
            ],
            Pages\RiskSihirbazi::class => [
                'baslik' => 'Risk Değerlendirme Sihirbazı',
                'ozet' => '5×5 Matris veya Fine-Kinney metoduyla sektöre özel yasal risk değerlendirmesi ve Yapı İşleri SGP hazırlayın.',
                'adimlar' => [
                    'Firmayı, değerlendirme ekibini ve risk metodunu (5×5 / Fine-Kinney) belirleyin.',
                    'Sektör şablonundan, Kayıtlı Risklerimden veya Saha Bulgularından tehlike maddelerini tek tıkla aktarın.',
                    'Puanları ve önlemleri onaylayıp imzalı resmi PDF (veya Yapı İşleri SGP / Ek-3) çıktısını alın.',
                ],
                'ipucu' => '6331 m.10 gereği Risk Değerlendirmesi Çok Tehlikeli sınıfta 2, Tehlikeli sınıfta 4, Az Tehlikeli sınıfta 6 yılda bir yenilenir.',
            ],
            RiskDegerlendirmesiResource::class => [
                'baslik' => 'Kayıtlı Risk Değerlendirmeleri',
                'ozet' => 'Hazırlanmış tüm risk değerlendirmelerini görüntüleyin, güncelleyin, PDF indirin veya işverene WhatsApp ile gönderin.',
                'adimlar' => [
                    'Tablodan ilgili firmanın değerlendirmesini filtreleyip durumunu ve geçerlilik tarihini kontrol edin.',
                    'Düzenle ile maddeleri güncelleyin veya yeni revizyon oluşturun.',
                    'PDF indir veya işverene 30 günlük imzalı WhatsApp paylaşım bağlantısını kullanın.',
                ],
                'ipucu' => 'Yüksek skorlu risk maddelerini Onaylı Defter sayfasındaki "Riskten Çek" butonuyla doğrudan deftere aktarabilirsiniz.',
            ],
            Pages\RiskMerkezi::class => [
                'baslik' => 'Risk Panosu & QR Bildirim',
                'ozet' => 'Portföydeki yüksek/kabul edilemez riskleri izleyin ve işyerine asılacak sahadan risk bildirim QR afişi üretin.',
                'adimlar' => [
                    'Öncelikli açık riskleri ve yaklaşan terminleri tablodan takip edin.',
                    'Seçili firma için "📱 Risk Bildirim QR Posteri (PDF)" oluşturup işyeri ilan panosuna astırın.',
                    'Kapatılan riskleri güncelleyerek aksiyon takibini kayıt altına alın.',
                ],
                'ipucu' => 'QR posteri sayesinde çalışanlar tehlikeli durumları ve ramak kalaları cep telefonlarından anında bildirebilir.',
            ],
            Pages\ZiyaretProgrami::class => [
                'baslik' => 'Aylık Ziyaret Programı & Otomatik Dağıtım',
                'ozet' => 'Tüm firmalarınızın yıllık ve aylık saha ziyaretlerini İSG-KATİP sürelerine göre planlayın ve takip edin.',
                'adimlar' => [
                    'Genel Ajanda görünümünde takvimden gün seçip "+ Seçili Güne Firma Ziyareti Ekle" ile hızlı kayıt açın.',
                    '"Boş Ayları Otomatik Doldur" butonuna tıklayıp günlük maksimum saat, Cumartesi tercihi ve meşgul/tatil günlerinizi girerek otomatik dağıtım yapın.',
                    'Firma Bazlı Yıllık Plan sekmesinde durumları Boş → Planlandı → Tamamlandı şeklinde güncelleyin.',
                ],
                'ipucu' => 'Otomatik dağıtım daha önce elle doldurduğunuz aylara dokunmaz; yalnızca boş kalan ayları kapasitenize göre yerleştirir.',
            ],
            Pages\ZiyaretModu::class => [
                'baslik' => 'Bugün — Ziyaret Modu',
                'ozet' => 'Sahaya çıktığınızda o firmada yapmanız gereken kontrolleri, açık bulguları ve eksik evrakları tek ekrandan yönetin.',
                'adimlar' => [
                    'Ziyaret ettiğiniz firmayı seçerek bugünün kontrol listesini ve açık aksiyonlarını görün.',
                    'Sahada tespit ettiğiniz uygunsuzlukları hızlıca fotoğraflı bulgu veya ramak kala olarak kaydedin.',
                    'Ziyaret sonunda tek tıkla işverene özet rapor paylaşın.',
                ],
                'ipucu' => 'Telefonunuzun tarayıcısından "Ana Ekrana Ekle" diyerek mehse\'yi saha uygulaması (PWA) gibi kullanabilirsiniz.',
            ],
            Pages\HizliSahaBulgusu::class => [
                'baslik' => 'Hızlı Saha Bulgusu',
                'ozet' => 'Saha gezisinde uygunsuzlukları fotoğraf çekerek, sesle dikte ederek ve termin vererek saniyeler içinde kaydedin.',
                'adimlar' => [
                    'Firmayı, uygunsuzluk başlığını ve risk derecesini seçin.',
                    'Açıklama alanının yanındaki "🎤 Sesle Yaz" butonuyla klavyesiz dikte edin ve saha fotoğrafı ekleyin.',
                    'Kaydedilen bulguları tek tıkla DÖF raporuna, Ceza Tutanağına veya Risk Değerlendirmesine aktarın.',
                ],
                'ipucu' => 'Aynı bulgu fotoğrafını daha sonra Ceza & İhtar Tutanağında "Saha Bulgusundan Fotoğraf Seç" ile tekrar kullanabilirsiniz.',
            ],
            Pages\AiSahaAnalizi::class => [
                'baslik' => 'AI Saha Gözlem Raporu',
                'ozet' => 'Saha fotoğraflarını yapay zekâ ile analiz ederek 6331 sayılı mevzuata uygun tehlike, risk ve düzeltici öneri raporu üretin.',
                'adimlar' => [
                    'Firmayı seçin ve saha fotoğrafını yükleyin; isterseniz "🎤 Sesle Yaz" ile kısa gözlem notu ekleyin.',
                    'Yapay zekâ analizini başlatıp mevzuat dayanaklı uygunsuzluk ve aksiyon önerilerini inceleyin.',
                    'Onayladıktan sonra tek sayfalık PDF saha gözlem raporunu indirin veya WhatsApp ile paylaşın.',
                ],
                'ipucu' => 'Net ve geniş açılı saha fotoğrafları yapay zekânın KKD, makine koruyucu ve ergonomi tespit doğruluğunu artırır.',
            ],
            Pages\DofOlustur::class => [
                'baslik' => 'DÖF (Düzeltici & Önleyici Faaliyet)',
                'ozet' => 'Uygunsuzluklar için 5×5 veya Fine-Kinney skorlu, terminli ve fotoğraflı resmi DÖF raporları hazırlayın.',
                'adimlar' => [
                    'Firmayı seçip maddeleri manuel ekleyin, "🎤 Sesle Yaz" ile dikte edin veya AI Oto-Doldur / Onaylı Defterden çekin.',
                    'Termin kısayolları (1H, 2H, 1A, 3A, 6A) ve satır içi risk puanıyla önceliklendirin.',
                    'Resmi PDF çıktısını alın veya 30 günlük imzalı WhatsApp linkiyle işverene iletin.',
                ],
                'ipucu' => 'İş Kazası Raporu ve Onaylı Defter sayfalarındaki "DÖF\'e Aktar" düğmeleriyle mükerrer veri girişini önleyebilirsiniz.',
            ],
            Pages\TespitOneriDefteri::class => [
                'baslik' => 'İSG Tespit ve Öneri (Onaylı) Defteri',
                'ozet' => '6331 m.8 kapsamındaki yasal bildirimleri deftere işleyin, riskten çekin ve tek tıkla DÖF\'e dönüştürün.',
                'adimlar' => [
                    'Firmayı seçin; "✨ 5 Temel Yasal Madde Ekle" veya "Riskten Çek" ile hızlıca madde oluşturun.',
                    'Yeni madde yazarken "🎤 Sesle Yaz" butonunu kullanıp yasal süre ve önerileri düzenleyin.',
                    'Defter çıktısını PDF alın ve kritik maddeleri "DÖF\'e Aktar" ile aksiyon takibine geçirin.',
                ],
                'ipucu' => 'Onaylı deftere yazılan tespitler işverene tebliğ edilmiş sayılır (6331 m.8); imzalı nüshaları düzenli yüklemeyi unutmayın.',
            ],
            Pages\IsKazasiRaporu::class => [
                'baslik' => 'İş Kazası İnceleme & 12 Kırılımlı İstatistik',
                'ozet' => 'İş kazalarını kayıt altına alın, 5 Neden kök neden analizi ve DÖF üretin, 12 kırılımlı istatistik PDF\'i alın.',
                'adimlar' => [
                    'Kazaya ilişkin çalışan, tarih, saat, uzuv/olay türü ve özet bilgilerini doldurun.',
                    '"✨ 5 Neden & DÖF Oto-Üret" ile kök neden analizini hazırlayın ve tek tıkla DÖF\'e veya İSG Kurulu gündemine aktarın.',
                    '"📊 12 Kırılımlı Kaza İstatistikleri PDF" ile yönetim kurulu özetini indirin.',
                ],
                'ipucu' => '5510 m.13 gereği iş kazaları kazadan sonraki 3 iş günü içinde SGK\'ya bildirilmek zorundadır.',
            ],
            Pages\EgitimKatilim::class => [
                'baslik' => 'İSG Eğitim Katılım, Sınav & Sertifika',
                'ozet' => 'Temel İSG eğitim yoklaması, 10 soruluk ölçme sınavı ve katılım belgesini tek akışta üretin.',
                'adimlar' => [
                    'Firmayı, eğitim tarihini, süresini ve katılımcıları seçin (SGK PDF\'inden de çalışan ekleyebilirsiniz).',
                    'Eğitim Katılım Formu PDF\'ini ve katılımcıya özel "📝 10 Soruluk Sınav Kâğıdı & Cevap Anahtarı PDF" çıktısını alın.',
                    'Tamamlanan eğitimler çalışan özlük karnesine ve yenileme takibine otomatik işlenir.',
                ],
                'ipucu' => 'Eğitim yenileme süreleri Çok Tehlikeli 1 yıl (16 saat), Tehlikeli 2 yıl (12 saat), Az Tehlikeli 3 yıldır (8 saat).',
            ],
            Pages\IsbasiEgitim::class => [
                'baslik' => 'İşbaşı Oryantasyon Eğitimi',
                'ozet' => 'Yeni işe başlayan çalışanlar için en az 2 saatlik pratik işbaşı eğitim tutanağını canlı imzayla hazırlayın.',
                'adimlar' => [
                    'Firmayı ve çalışanı seçin veya SGK İşe Giriş PDF\'ini yükleyerek bilgileri otomatik doldurun.',
                    'Eğitim konularını işaretleyin ve çalışandan "📱 Ekrandan Canlı İmza" alın.',
                    'İmzalı tutanağı PDF olarak indirip özlük dosyasına ekleyin.',
                ],
                'ipucu' => 'İşbaşı eğitimi yeni işe girişte, iş/makine değişikliğinde ve iş kazası sonrası işe dönüşte zorunludur.',
            ],
            Pages\MuayeneFormu::class => [
                'baslik' => 'İşe Giriş / Periyodik Muayene Formu (EK-2)',
                'ozet' => 'İşyeri hekimi EK-2 sağlık muayene formunu hızlı şablonlar, SGK PDF okuyucu ve canlı imzayla düzenleyin.',
                'adimlar' => [
                    'Çalışanı seçin veya SGK İşe Giriş PDF\'ini yükleyerek kimlik/görev alanlarını otomatik doldurun.',
                    '"Aşıları Tam İşaretle" ve "Soygeçmiş Tümü Sağ" kısayollarıyla anamnez bölümünü hızla tamamlayın.',
                    'Hekim kanaatini ve canlı imzayı ekleyip resmi EK-2 PDF çıktısını alın.',
                ],
                'ipucu' => 'EK-2 muayene periyotları Çok Tehlikeli 1 yıl, Tehlikeli 3 yıl, Az Tehlikeli 5 yıldır (özel politika gerektirenlerde 6 ay).',
            ],
            Pages\KkdFormu::class => [
                'baslik' => 'KKD Zimmet ve Teslim Tutanağı',
                'ozet' => 'EN standartlı kişisel koruyucu donanımları sektörel paketlerle seçin, KVKK maskeli ve canlı imzalı tutanak üretin.',
                'adimlar' => [
                    'Firmayı ve çalışanı seçin (veya SGK İşe Giriş PDF\'inden otomatik aktarın).',
                    'İnşaat, Metal, Kimya veya Depo hazır KKD paketlerinden tek tıkla donanım listesini ekleyin.',
                    'KVKK T.C. maskeleme seçeneğini belirleyip çalışan canlı imzasını alarak PDF oluşturun.',
                ],
                'ipucu' => 'KKD zimmet tutanağı çalışana verilen ekipmanın CE/EN standardını ve kullanım eğitiminin verildiğini belgeler.',
            ],
            Pages\CezaTeblig::class => [
                'baslik' => 'Ceza, İhtar Tutanağı & 2026 İPC Hesaplayıcı',
                'ozet' => 'Çalışana İSG ihtar tutanağı hazırlayın veya işverene 2026 yılı resmi 6331 m.26 İPC risk tebliği oluşturun.',
                'adimlar' => [
                    'Tutanak türünü (Çalışana İhtar / İşverene İPC Tebliği) ve firmayı seçin.',
                    'İhtarlarda saha bulgusu fotoğraflarından kanıt ekleyin ve canlı imza alın; İPC\'de ilgili 6331 maddelerini işaretleyin.',
                    'Çalışan sayısı ve tehlike sınıfına göre hesaplanan resmi tutarla birlikte PDF çıktısını alın.',
                ],
                'ipucu' => '2026 İPC tutarları çalışan sayısı (1–9, 10–49, 50+) ve tehlike sınıfı katsayısına göre otomatik hesaplanır.',
            ],
            Pages\TalimatOlustur::class => [
                'baslik' => 'Güvenli Çalışma Talimatları & Tebellüğ',
                'ozet' => 'Makine, ekipman ve görev bazlı güvenli çalışma talimatları oluşturup tabletten canlı tebellüğ imzası alın.',
                'adimlar' => [
                    'Firmayı ve talimat şablonunu seçin veya makineye özel maddeleri düzenleyin.',
                    'Çalışan tebellüğ bölümünde "📱 Ekrandan Canlı İmza" ile imzayı kaydedin.',
                    'İmzalı tebellüğ bloğuyla birlikte A4 talimat PDF\'ini indirin.',
                ],
                'ipucu' => 'Talimatları Periyodik Kontrol sayfasındaki QR kodlu ekipman kartlarına bağlayarak operatörlerin sahada okumasını sağlayabilirsiniz.',
            ],
            Pages\PeriyodikKontrol::class => [
                'baslik' => 'İş Ekipmanları Periyodik Kontrol & QR Kart',
                'ozet' => 'İş ekipmanlarını Excel\'den toplu yükleyin, muayene vadelerini takip edin ve makine üstü QR operatör kartı basın.',
                'adimlar' => [
                    'Ekipmanları tek tek ekleyin veya "Excel Şablonu İndir & Toplu Ekipman Ekle" ile tüm envanteri saniyeler içinde aktarın.',
                    'Son muayene tarihlerini ve geçerlilik periyotlarını girerek otomatik uyarı takvimini başlatın.',
                    'Her ekipman için QR + PIN korumalı operatör günlük kontrol etiketini yazdırın.',
                ],
                'ipucu' => 'Kaldırma-iletme araçları, basınçlı kaplar ve elektrik tesisatı standartta aksi belirtilmedikçe yılda en az 1 kez kontrol edilir.',
            ],
            Pages\TatbikatTutanagi::class => [
                'baslik' => 'Acil Durum Tatbikat Tutanağı',
                'ozet' => 'Yıllık zorunlu yangın/tahliye tatbikat tutanağını tüm aktif çalışanları tek tıkla ekleyerek ve canlı imzayla hazırlayın.',
                'adimlar' => [
                    'Firmayı, tatbikat senaryosunu (Yangın, Deprem, Tahliye) ve süresini girin.',
                    '"+ Tüm Çalışanları Ekle" ile aktif personeli listeye aktarın ve yetkili canlı imzasını alın.',
                    'Fotoğraf ekleyerek resmi tatbikat tutanağı PDF\'ini indirin.',
                ],
                'ipucu' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik gereği tüm işyerlerinde yılda en az 1 kez tatbikat yapılması zorunludur.',
            ],
            Pages\Mevzuat::class => [
                'baslik' => 'İSG Mevzuat Kütüphanesi & AI Danışman',
                'ozet' => '6331 Sayılı Kanun sade özet rehberini inceleyin, mevzuat.gov.tr kaynaklarına ulaşın ve AI Mevzuat Danışmanına sorun.',
                'adimlar' => [
                    '"⚖️ Mevzuata Sor" sekmesinde hazır 6 hap sorudan birine tıklayın veya kendi saha sorunuzu yazın.',
                    '"📘 6331 Sade Özet Rehberi" ile kanunun kapsamını, yükümlülükleri ve 7 pratik adımı inceleyin.',
                    'Güncel kanun ve yönetmelik tam metinleri için resmi mevzuat.gov.tr bağlantılarını kullanın.',
                ],
                'ipucu' => 'Mevzuat danışmanı sorunuzu 6331 sayılı Kanun ve ilgili ikincil yönetmelik maddelerine atıfla yanıtlar.',
            ],
            Pages\Araclar::class => [
                'baslik' => 'İSG Hesaplayıcılar, Sözlük & Uzmanlık Sınavı',
                'ozet' => '26 haneli SGK sicil çözümleyici, 13 satırlık yükümlülük matrisi, İSG sözlüğü ve ÖSYM deneme sınavına tek yerden ulaşın.',
                'adimlar' => [
                    '26 haneli SGK sicil numarasını yapıştırarak NACE kodunu, tehlike sınıfını ve 13 yasal yükümlülüğü otomatik görün.',
                    'İSG Terimler Sözlüğünde 32 temel kavramı kategoriye göre arayın.',
                    '🎓 İSG Uzmanlık Deneme Sınavını başlatın veya 1 sayfalık Sayısal Hap Notlar PDF\'ini indirin.',
                ],
                'ipucu' => 'SGK sicil numarasının 2.–5. haneleri 4 haneli NACE faaliyet kodunu verir ve tehlike sınıfını doğrudan belirler.',
            ],
            Pages\Profilim::class => [
                'baslik' => 'Uzman Profili, Kaşe & İSG-KATİP Süre Hesabı',
                'ozet' => 'Kaşe/imza görsellerinizi tanımlayın ve portföyünüzdeki firmaların aylık İGU/Hekim/DSP dakikalarını otomatik hesaplayın.',
                'adimlar' => [
                    'Ad, unvan, sertifika numarası, kaşe ve imza görsellerinizi yükleyerek tüm PDF çıktılara otomatik basılmasını sağlayın.',
                    '⏱️ İSG-KATİP Hizmet Süresi tablosundan firma bazlı dakika ve kurul muafiyet durumunu inceleyin.',
                    'Toplam aylık çalışma kapasitenizi takip ederek doluluk oranınızı dengede tutun.',
                ],
                'ipucu' => 'Kaşe ve imza görselinizi PNG (şeffaf arka planlı) yüklerseniz tüm PDF imza kutularında net görünür.',
            ],
            Pages\AcilDurumPlani::class => [
                'baslik' => 'Acil Durum Eylem Planı (ADEP)',
                'ozet' => 'İşyerine özgü acil durum senaryolarını, ekipleri ve toplanma noktalarını kapsayan yasal ADEP hazırlayın.',
                'adimlar' => [
                    'Firmayı seçip işyeri bilgilerini, toplanma alanını ve acil telefon numaralarını kontrol edin.',
                    'Uygulanacak acil durum senaryolarını işaretleyip söndürme, kurtarma, koruma ve ilk yardım ekiplerini atayın.',
                    'Resmi ADEP PDF çıktısını alıp işverene WhatsApp üzerinden gönderin.',
                ],
                'ipucu' => 'Acil Durum Planı Çok Tehlikeli sınıfta 2, Tehlikeli sınıfta 4, Az Tehlikeli sınıfta 6 yılda bir yenilenir.',
            ],
            FirmaResource::class => [
                'baslik' => 'Firmalar (Portföy Yönetimi)',
                'ozet' => 'Hizmet verdiğiniz tüm işyerlerinin SGK, NACE, tehlike sınıfı, çalışan sayısı ve uzman atamalarını yönetin.',
                'adimlar' => [
                    'Yeni firma eklerken SGK sicil numarası veya NACE kodunu girin; tehlike sınıfı ve yasal süreler otomatik hesaplanır.',
                    'İGU, İşyeri Hekimi ve İşveren iletişim bilgilerini tanımlayın (WhatsApp paylaşımları bu numarayı kullanır).',
                    'Hizmeti sona eren firmaları pasife alarak açılır listelerden temizleyin.',
                ],
                'ipucu' => 'Firma telefonuna cep telefonu numarası girildiğinde tüm raporlardan tek tıkla işverene WhatsApp gönderebilirsiniz.',
            ],
            CalisanResource::class => [
                'baslik' => 'Çalışanlar (Personel Listesi)',
                'ozet' => 'Firma çalışanlarını tek tek veya SGK İşe Giriş Bildirgesi PDF\'i okuyarak saniyeler içinde sisteme aktarın.',
                'adimlar' => [
                    'Yeni çalışan ekleyin veya SGK İşe Giriş PDF okuyucu ile kimlik, görev ve giriş tarihini otomatik çekin.',
                    'T.C. kimlik no, doğum tarihi ve işe giriş tarihlerinin dolu olduğundan emin olun (genç çalışan ve muayene takibi için).',
                    'İşten ayrılan personelin çıkış tarihini girerek aktif kontenjan hesaplarının doğru kalmasını sağlayın.',
                ],
                'ipucu' => 'Aktif çalışan listesi eğitim, EK-2 muayene, KKD ve tatbikat formlarına otomatik olarak aktarılır.',
            ],
        ];
    }
}
