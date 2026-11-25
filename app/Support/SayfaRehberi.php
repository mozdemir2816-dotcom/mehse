<?php

namespace App\Support;

use App\Filament\Pages;
use App\Filament\Resources\Calisans\CalisanResource;
use App\Filament\Resources\EgitimPaketis\EgitimPaketiResource;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use Filament\Resources\Resource;

/**
 * Her ana modülün sağ üstünde gösterilen "❓ Nasıl Kullanılır?" rehber kütüphanesi.
 * Temel 3 adımlı özetin yanında Eğitim & Sınav gibi çok ekranlı merkezlerde detaylı
 * iş akışı kartları, yasal süre tablosu ve doğrudan ekran yönlendirme bağlantıları sunar.
 */
class SayfaRehberi
{
    /**
     * @param  array<int, string>  $scopes
     * @return array<string, mixed>|null
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
     * Rehberdeki yönlendirme tanımlarını kullanıcının erişim yetkisine göre filtreler ve
     * aktif firma bağlamını (?firma=) koruyan canlı URL'lere dönüştürür.
     *
     * @param  array<string, mixed>  $rehber
     * @return array<int, array{etiket: string, aciklama: string, ikon: string, url: string, aktif: bool}>
     */
    public static function yonlendirmeler(array $rehber, array $scopes = []): array
    {
        $ham = $rehber['yonlendirmeler'] ?? [];
        if (! is_array($ham) || $ham === []) {
            return [];
        }

        $firmaId = request()->integer('firma') ?: null;
        $param = $firmaId ? ['firma' => $firmaId] : [];
        $sonuc = [];

        foreach ($ham as $item) {
            $sinif = $item['sinif'] ?? null;
            if (! is_string($sinif) || ! class_exists($sinif)) {
                continue;
            }
            if (method_exists($sinif, 'canAccess') && ! $sinif::canAccess()) {
                continue;
            }

            $url = is_subclass_of($sinif, Resource::class)
                ? $sinif::getUrl('index', $param)
                : $sinif::getUrl($param);

            $sonuc[] = [
                'etiket' => (string) ($item['etiket'] ?? class_basename($sinif)),
                'aciklama' => (string) ($item['aciklama'] ?? ''),
                'ikon' => (string) ($item['ikon'] ?? '→'),
                'url' => $url,
                'aktif' => in_array($sinif, $scopes, true),
            ];
        }

        return $sonuc;
    }

    /** Eğitim & Sınav sayfalarında ortak gösterilen RG 02.04.2026 + kullanıcı kuralı süre özet tablosu. */
    private static function egitimSureTablosu(): array
    {
        return [
            ['sinif' => 'Az Tehlikeli', 'ilk' => '8 saat', 'online' => '8 saat', 'yuz_yuze' => '0 saat', 'tekrar' => '8 saat (4s Online + 4s YY)', 'periyot' => '3 yılda 1'],
            ['sinif' => 'Tehlikeli', 'ilk' => '12 saat', 'online' => '8 saat (2+2+4)', 'yuz_yuze' => '4 saat (4. Bölüm)', 'tekrar' => '8 saat (4s Online + 4s YY)', 'periyot' => '2 yılda 1'],
            ['sinif' => 'Çok Tehlikeli', 'ilk' => '16 saat', 'online' => '8 saat (2+2+4)', 'yuz_yuze' => '8 saat (S4+T+4. Bölüm)', 'tekrar' => '8 saat (4s Online + 4s YY)', 'periyot' => 'Yılda 1'],
        ];
    }

    /** Eğitim & Sınav merkezinin ortak hızlı yönlendirme haritası. */
    private static function egitimYonlendirmeleri(): array
    {
        return [
            [
                'sinif' => Pages\IseOzguEgitimKonulari::class,
                'ikon' => '✨',
                'etiket' => 'İşe Özgü Eğitim Konuları (API / AI)',
                'aciklama' => 'Tüm 21 sektör için 4. Bölüm konularını Gemini API ile tek tıkla kütüphaneye doldurun.',
            ],
            [
                'sinif' => Pages\YillikPlan\YillikEgitimPlani::class,
                'ikon' => '📅',
                'etiket' => 'Yıllık Eğitim Planı',
                'aciklama' => 'Online/Yüz Yüze karma planlayın, NACE dışı Gerçek İş Grubu seçin ve aynı gruptaki firmalara eşitleyin.',
            ],
            [
                'sinif' => Pages\EgitimKatilim::class,
                'ikon' => '📝',
                'etiket' => 'Eğitim Katılım Formu (3 Ayrı Mod)',
                'aciklama' => 'Tamamı Yüz Yüze, Uzaktan/Online veya Yüz Yüze Tamamlama (Karma) tutanağı, sınav ve sertifika üretin.',
            ],
            [
                'sinif' => Pages\UzaktanEgitimAtama::class,
                'ikon' => '💻',
                'etiket' => 'Online Eğitim Atama (LMS)',
                'aciklama' => 'Çalışanlara 8 saat (İlk Defa) veya 4 saat (Yenileme) online eğitim + sınav bağlantısı gönderin.',
            ],
            [
                'sinif' => Pages\UzaktanEgitimTakip::class,
                'ikon' => '📊',
                'etiket' => 'Online Eğitim Takip',
                'aciklama' => 'Bitirenleri ve sınav puanlarını izleyin; tamamlananlar Yıllık Eğitim Planında otomatik G işlenir.',
            ],
            [
                'sinif' => Pages\EgitimYenilemeTakibi::class,
                'ikon' => '🔄',
                'etiket' => 'Eğitim Takibi (Yenileme)',
                'aciklama' => 'Vadesi yaklaşan veya süresi geçen çalışanları görün, tek tıkla yenileme planlayın.',
            ],
            [
                'sinif' => Pages\IsbasiEgitim::class,
                'ikon' => '🦺',
                'etiket' => 'İşbaşı Eğitimi (En Az 2 Saat)',
                'aciklama' => 'İşe yeni başlayan veya görevi değişen çalışan için canlı imzalı oryantasyon tutanağı oluşturun.',
            ],
            [
                'sinif' => Pages\SertifikaOlustur::class,
                'ikon' => '🎓',
                'etiket' => 'Sertifika Oluştur (7 Çerçeve / Arkalı-Önlü)',
                'aciklama' => 'Katılımcılara özel tek sayfa, arkalı-önlü veya Yıldız Grup sertifikası (PDF/ZIP) basın.',
            ],
            [
                'sinif' => Pages\EgitimSorulari::class,
                'ikon' => '🤖',
                'etiket' => 'AI ile Soru / Sınav Hazırla',
                'aciklama' => 'Sektöre ve firmaya özel çoktan seçmeli ön/son test soruları üretip Soru Bankasına ekleyin.',
            ],
            [
                'sinif' => Pages\ToolboxKonusmalari::class,
                'ikon' => '🗣️',
                'etiket' => 'Toolbox Konuşmaları',
                'aciklama' => 'Sahada 5 dakikalık iş öncesi kısa güvenlik bilgilendirmesi ve yoklama tutanağı alın.',
            ],
        ];
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
                'baslik' => 'Eğitim & Sınav Merkezi — Detaylı Kullanım ve Yönlendirme Rehberi',
                'ozet' => 'Temel İSG eğitimlerini (Tamamı Yüz Yüze, Uzaktan/Online veya Karma Tamamlama), ekip bazlı formları (Kalıp / Sıva), 10 soruluk sınav kâğıtlarını ve sertifikaları Yıllık Eğitim Planı ile birebir senkron biçimde yönetin.',
                'adimlar' => [
                    '1. İLK ADIM — Yıllık Eğitim Planı (Ana Kaynak): Önce Yıllık Eğitim Planı ekranından "✨ AI ile Tüm Firmaları Planla" (veya tek firma için "✨ AI ile Bu Firmanın 4. Bölümünü Planla") ile her firmanın 4. bölümünü ve Karma saatlerini oluşturun. Eğitim Katılım, Boş Form ve Sertifikalar doğrudan bu plandan beslenir.',
                    '2. Firma, Şekil ve Ekip Seçimi: Firmayı seçin; "Eğitim Şekli (Katılım Formu Tipi)" kutusundan Tamamı Yüz Yüze, Uzaktan/Online veya Yüz Yüze Tamamlama (Karma) formunu seçin. Şantiyede farklı ekiplere eğitim veriyorsanız "Çalışan Grubu / İş Kolu" etiketini işaretleyin.',
                    '3. Konular, Otomatik Süre ve Katılımcılar: 4. bölüm firmanın Yıllık Eğitim Planından birebir gelir; isterseniz "+ Konu Ekle" / "✕" ile düzenleyin — dakikalar toplam eğitim süresine göre kayıpsız otomatik bölünür ve Boş Form (PDF) ekrandaki güncel haliyle çıkar.',
                    '4. Çıktı & Otomatik İşleme: Üstteki "Çıktı Al ▾" menüsünden PDF (TR veya TR/EN), Excel, Word, 10 Soruluk Sınav Kâğıdı veya Sertifika indirin. Kaydedilen eğitim Yıllık Eğitim Planına ve yenileme takibine otomatik işlenir.',
                ],
                'bolumler' => [
                    [
                        'baslik' => '3 Ayrı Eğitim Katılım Formu Nasıl Kullanılır?',
                        'etiket' => 'Form Tipleri',
                        'maddeler' => [
                            'Tamamı Yüz Yüze: Tüm 4 bölüm dahil, tam süre (Az Tehlikeli 8s · Tehlikeli 12s · Çok Tehlikeli 16s; Tekrar 8s). 12 saat ve üstünde otomatik 2 gün + çift imza sütunu açılır.',
                            'Uzaktan / Online Verilen Eğitim Formu (LMS): Yalnız online verilen konular ve saatler (İlk Defa 8s / Tekrar 4s) listelenir; Tehlikeli ve Çok Tehlikeli sınıfta 4. bölüm hariç tutulur ve PDF "UZAKTAN (ONLİNE) EĞİTİM KATILIM FORMU" başlığıyla çıkar.',
                            'Yüz Yüze Tamamlama Formu (Karma): Online sonrası sahada yüz yüze verilecek kalan konuları kapsar (Tehlikeli İlk & Tekrar 4s = yalnız 4. bölüm 180 dk; Çok Tehlikeli İlk 8s = S4 + T4/T5/T10/T12 + 4. bölüm). PDF "YÜZ YÜZE TAMAMLAMA EĞİTİM KATILIM FORMU (KARMA)" başlığıyla basılır.',
                        ],
                    ],
                    [
                        'baslik' => 'NACE Uyuşmuyorsa & Tüm Sektörlere AI ile Konu Ekleme',
                        'etiket' => 'Sektör & AI',
                        'maddeler' => [
                            'Firmanın SGK NACE kodu yaptığı işle tutmuyorsa "İşyerine Özgü Risk Sektörü" açılır listesinden gerçek işi seçin — bu seçim firmada hatırlanır ve Yıllık Eğitim Planı ile ortak çalışır.',
                            '4. bölüm kutusundaki "✨ AI ile İşe Özgü Konu Üret" butonuyla o firma veya ekip için saniyeler içinde yeni konular üretebilir, isterseniz kütüphaneye kaydedip aynı sektördeki tüm firmalarda kullanabilirsiniz.',
                            'Konu ekledikçe, sildikçe, tik kaldırdıkça veya "Ders Saati"ni değiştirdikçe dakika dağılımı toplam süreye göre kayıpsız yeniden hesaplanır (elle dakika yazarsanız sizin değeriniz korunur).',
                        ],
                    ],
                    [
                        'baslik' => 'Şantiyede Kalıp Ekibi ile Sıva Ekibine Ayrı Eğitim Verme',
                        'etiket' => 'Ekip Ayrımı',
                        'maddeler' => [
                            'Önce "Çalışan Grubu / İş Kolu" altında "Kalıp, İskele ve Demir İşleri" etiketine tıklayın → 4. bölümde yalnız kalıp/iskele/demir konuları kalır (kazı/sıva karışmaz).',
                            '3. Katılımcı Listesinde "Görev" filtresinden "Kalıpçı" seçip "Yalnız bu listedekileri seç" deyin ve kaydedip PDF alın.',
                            'Üstten "Yeni kayıt"a basıp bu kez "Sıva, Boya ve Yüzey Kaplama" + "Sıvacı" seçerek ikinci ekibin ayrı tutanağını oluşturun. Her iki ekip Yıllık Eğitim Planında 👷 rozetiyle ve Excel\'de [EKİP: ...] etiketiyle ayrı görünür.',
                        ],
                    ],
                    [
                        'baslik' => 'Önerilen Uçtan Uca İş Akışı (Hangi Ekrandan Başlamalı?)',
                        'etiket' => 'İş Akışı',
                        'maddeler' => [
                            'Adım 1 — Yıllık Eğitim Planı (Ana Kaynak): "✨ AI ile Tüm Firmaları Planla" ile tüm aktif firmalarınızın 4. bölümünü sektör/Gerçek İş Grubuna göre tek seferde kurun (aynı iş grubundaki firmalar aynı 4. bölümü alır, G ayları korunur).',
                            'Adım 2 — Online Eğitim Atama & Takip: Çalışanlara LMS 8s/4s online eğitimi atayın; bitirenler Yıllık Eğitim Planında otomatik G (Gerçekleşti) işlenir.',
                            'Adım 3 — Eğitim Katılım Formu (3 Mod) & Boş Form: Plandan birebir gelen konular ve hatasız sürelerle Tamamı Yüz Yüze, Online veya Karma tamamlama tutanağını, 10 soruluk sınav kâğıdını ve sertifikalarını alın.',
                            'Adım 4 — Konu Kütüphanesi (İsteğe Bağlı): Ortak şablonları yönetmek veya bir konuyu tüm firmaların planlarından tek seferde temizlemek için İşe Özgü Eğitim Konuları ekranını kullanın.',
                        ],
                    ],
                ],
                'sure_tablosu' => static::egitimSureTablosu(),
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'RG 02.04.2026 (33212) Md.12/3 gereği Tehlikeli ve Çok Tehlikeli sınıflarda 4. Bölüm (İşe ve İşyerine Özgü Riskler) mutlaka yüz yüze verilmelidir. Çıktı Al ▾ menüsü önce kaydedip sonra belge üretir; kopya kayıt açmaz.',
            ],
            Pages\IsbasiEgitim::class => [
                'baslik' => 'İşbaşı Oryantasyon Eğitimi',
                'ozet' => 'Yeni işe başlayan, bölümü/makinesi değişen veya iş kazası sonrası dönen çalışanlar için en az 2 saatlik uygulamalı işbaşı eğitim tutanağını canlı imzayla hazırlayın.',
                'adimlar' => [
                    'Firmayı ve çalışanı seçin veya SGK İşe Giriş Bildirgesi PDF\'ini yükleyerek kimlik/görev alanlarını otomatik doldurun.',
                    'Çalışanın yapacağı işe ve kullanacağı ekipmana özgü oryantasyon başlıklarını işaretleyin.',
                    'Çalışandan "📱 Ekrandan Canlı İmza" alın ve imzalı tutanağı PDF indirip özlük dosyasına ekleyin.',
                ],
                'yonlendirmeler' => [
                    ['sinif' => Pages\EgitimKatilim::class, 'ikon' => '📝', 'etiket' => 'Temel İSG Eğitim Katılım', 'aciklama' => 'İşbaşı sonrası temel 8/12/16 saatlik İSG eğitim tutanağına geçin.'],
                    ['sinif' => Pages\EgitimYenilemeTakibi::class, 'ikon' => '🔄', 'etiket' => 'Eğitim Yenileme Takibi', 'aciklama' => 'Eğitimi eksik veya vadesi gelen tüm çalışanları görün.'],
                    ['sinif' => Pages\ToolboxKonusmalari::class, 'ikon' => '🗣️', 'etiket' => 'Toolbox Konuşmaları', 'aciklama' => 'Saha başı 5 dakikalık kısa güvenlik hatırlatması düzenleyin.'],
                ],
                'ipucu' => 'İşbaşı eğitimi (en az 2 saat) fiilen işe başlamadan önce verilir ve temel İSG eğitim süresinden sayılmaz (RG 02.04.2026 Md.7).',
            ],
            Pages\EgitimYenilemeTakibi::class => [
                'baslik' => 'Eğitim Takibi & Yenileme Matrisi',
                'ozet' => 'Tüm firmalarınızdaki çalışanların temel İSG, işbaşı ve özel eğitim geçerlilik tarihlerini renk kodlu takip edin; süresi yaklaşanları tek tıkla eğitime alın.',
                'adimlar' => [
                    'Üstten firmayı veya tüm portföyü süzerek "Eksik", "Süresi Geçti" ve "Yaklaşıyor" çalışanları görün.',
                    'Yüz yüze eğitim verilecekler için Eğitim Katılım sayfasına, online yenileme için Online Eğitim Atama veya Yıl Başı Toplu Eğitim ekranına geçin.',
                    'Planlı kayıtlarda eğitim tamamlandığında "Katılım / Sonuç" alanından geçenleri işaretleyerek takibi güncelleyin.',
                ],
                'sure_tablosu' => static::egitimSureTablosu(),
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Tekrar eğitimleri tüm tehlike sınıflarında en az 8 saattir (4s Online + 4s Yüz Yüze) ve Çok Tehlikeli\'de yılda 1, Tehlikeli\'de 2 yılda 1, Az Tehlikeli\'de 3 yılda 1 yenilenir.',
            ],
            Pages\SertifikaOlustur::class => [
                'baslik' => 'Eğitim Sertifikası Oluşturma (7 Çerçeve & Arkalı-Önlü)',
                'ozet' => 'Temel İSG, Yüksekte Çalışma, Kapalı Alan veya Özel Eğitimler için 7 kurumsal çerçeve, Tek Sayfa / Arkalı-Önlü düzen ve Yıldız Grup formatında toplu sertifika (PDF/ZIP) basın.',
                'adimlar' => [
                    'Firmayı, sertifika tipini (İSG, Yüksekte Çalışma vb.), eğitim türünü (İlk Defa / Tekrar) ve tarihleri seçin.',
                    'Çerçeve stilini (7 seçenek) ve düzeni (Tek Sayfa veya Arkalı-Önlü: 1. sayfa geniş sertifika, 2. sayfa detaylı müfredat) belirleyin.',
                    'Katılımcıları seçip "PDF İndir" veya kişi başı ayrı dosya için "ZIP İndir" butonuna tıklayın.',
                ],
                'yonlendirmeler' => [
                    ['sinif' => Pages\EgitimKatilim::class, 'ikon' => '📝', 'etiket' => 'Eğitim Katılım Formu', 'aciklama' => 'Katılım tutanağından doğrudan sertifika ve Yıldız Grup çıktısı alın.'],
                    ['sinif' => Pages\UzaktanEgitimTakip::class, 'ikon' => '📊', 'etiket' => 'Online Eğitim Takip', 'aciklama' => 'Online sınavı geçen çalışanların QR doğrulamalı belgelerini görün.'],
                    ['sinif' => Pages\Profilim::class, 'ikon' => '🖋️', 'etiket' => 'Profilim (Kaşe & İmza)', 'aciklama' => 'Sertifikaya basılacak İGU / Hekim kaşesini güncelleyin.'],
                ],
                'ipucu' => 'Eğitim Katılım Formu\'nda oluşturduğunuz bir kaydın "Çıktı Al ▾ → Katılımcı Sertifikaları" veya "Yıldız Grup" seçeneğini kullanırsanız konular ve katılımcılar yeniden girmeden otomatik aktarılır.',
            ],
            EgitimPaketiResource::class => [
                'baslik' => 'Online Eğitim Paketleri (LMS)',
                'ozet' => 'Uzaktan verilecek temel İSG (8 saat İlk Defa / 4 saat Yenileme) ve özel eğitim paketlerini, ders slaytlarını, videolarını ve sınav kurallarını yönetin.',
                'adimlar' => [
                    'Mevcut paketlerden birini düzenleyin veya "+ Yeni Eğitim Paketi" ile kendi müfredatınızı oluşturun.',
                    'Paket içindeki modüllere video/slayt, minimum izleme süresi ve geçme puanı (varsayılan ≥60/70) tanımlayın.',
                    'Hazır paketi Online Eğitim Atama ekranından firma çalışanlarına gönderin.',
                ],
                'sure_tablosu' => static::egitimSureTablosu(),
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'LMS paketleri 8 saat (İlk Defa) ve 4 saat (Yenileme) olarak Yıllık Eğitim Planı "Karma — MEHSE LMS" moduyla birebir eşleşir.',
            ],
            Pages\UzaktanEgitimFirmaAyarlari::class => [
                'baslik' => 'Online Eğitim Firma Ayarları',
                'ozet' => 'Her firma için uzaktan eğitim izinlerini, geçme puanını, sertifika şablonunu ve çalışan giriş/SMS tercihlerini yapılandırın.',
                'adimlar' => [
                    'Listeden firmayı seçerek online eğitim kullanımını ve varsayılan paket tercihlerini belirleyin.',
                    'Çalışanların kimlik doğrulama, sınav tekrar hakkı ve otomatik hatırlatma seçeneklerini kaydedin.',
                    'Ayarlar tamamlandıktan sonra Online Eğitim Atama ekranından çalışanlara link gönderin.',
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Online eğitim aktif olan Tehlikeli/Çok Tehlikeli firmalarda Eğitim Katılım Formu açıldığında otomatik "Karma (Yüz Yüze Tamamlama)" önerilir.',
            ],
            Pages\UzaktanEgitimAtama::class => [
                'baslik' => 'Online Eğitim Atama (Çalışana Link / WhatsApp)',
                'ozet' => 'Seçili firmanın çalışanlarına tek tıkla İlk Defa (8s) veya Yenileme (4s) uzaktan İSG eğitimi atayın ve kişisel erişim bağlantısı gönderin.',
                'adimlar' => [
                    'Firmayı, eğitim paketini ve eğitim türünü (İlk Defa / Yenileme) seçin.',
                    'Eğitim atanacak aktif çalışanları işaretleyin (veya tümünü seçin) ve son tamamlama tarihini girin.',
                    'Atamayı oluşturup çalışanlara WhatsApp / SMS / e-posta bağlantısını iletin; ilerlemeyi Online Eğitim Takip ekranından izleyin.',
                ],
                'sure_tablosu' => static::egitimSureTablosu(),
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Tehlikeli ve Çok Tehlikeli sınıflarda online atama 1.–3. bölümleri kapsar; çalışanlar online kısmı bitirdikten sonra Eğitim Katılım Formu (Karma) ile kalan yüz yüze oturumu tamamlayın.',
            ],
            Pages\UzaktanEgitimTakip::class => [
                'baslik' => 'Online Eğitim Takip & Otomatik Plan İşleme',
                'ozet' => 'Çalışanların uzaktan eğitim izleme yüzdelerini, ön/son test puanlarını ve tamamlama belgelerini canlı takip edin.',
                'adimlar' => [
                    'Firmaya göre filtreleyip "Bekliyor", "Devam Ediyor" ve "Tamamlandı" durumlarını inceleyin.',
                    'Eksik kalan çalışanlara hatırlatma gönderin; tamamlayanların sınav ve katılım belgelerini indirin.',
                    'Tamamlanan online eğitimler firmanın Yıllık Eğitim Planında ilgili online konuların ayına otomatik "G (Gerçekleşti)" olarak işlenir.',
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Online kısmı tamamlayan Tehlikeli/Çok Tehlikeli çalışanlar için şimdi "Eğitim Katılım Formu → Yüz Yüze Tamamlama (Karma)" ekranından kalan 4s/8s oturumun imza tutanağını alabilirsiniz.',
            ],
            Pages\UzaktanEgitimYilBasi::class => [
                'baslik' => 'Yıl Başı Toplu Online Eğitim Planlama',
                'ozet' => 'Yeni yıl başında veya toplu yenileme dönemlerinde tüm aktif firmalarınızdaki vadesi gelen çalışanlara tek seferde online yenileme eğitimi atayın.',
                'adimlar' => [
                    'Hedef yılı ve yenileme kapsamındaki firmaları/çalışanları listeleyin.',
                    'Uygulanacak yenileme paketini (4 saat Online) ve son tarihi seçin.',
                    'Toplu atamayı başlatın ve sonuçları Online Eğitim Takip ekranından izleyin.',
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Toplu atama zaten aktif/geçerli eğitimi bulunan çalışanlara mükerrer atama açmaz.',
            ],
            Pages\UzaktanEgitimSorulari::class => [
                'baslik' => 'Online Eğitim Sınav Soruları',
                'ozet' => 'Uzaktan eğitim (LMS) ön test ve son test değerlendirmelerinde çalışanların karşısına çıkacak çoktan seçmeli soruları yönetin.',
                'adimlar' => [
                    'İlgili eğitim paketini veya konu kategorisini seçerek mevcut soruları inceleyin.',
                    'Yeni soru ekleyin, doğru şıkkı işaretleyin veya pasif soruları düzenleyin.',
                    'Sektöre özel yeni sorular üretmek için "AI ile Soru / Sınav" sayfasını kullanın.',
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Sorular çalışanlara rastgele sırayla sunulur; geçme puanını sağlayanlar sertifika almaya hak kazanır.',
            ],
            Pages\SoruBankasi::class => [
                'baslik' => 'İSG Soru Bankası',
                'ozet' => 'Yüz yüze sınav kâğıtlarında ve online eğitim sınavlarında kullanılacak tüm çoktan seçmeli soru havuzunuzu tek yerde yönetin.',
                'adimlar' => [
                    'Kategori veya sektör anahtar kelimesiyle mevcut soruları arayın ve düzenleyin.',
                    'Elle yeni soru ekleyin veya "AI ile Soru / Sınav" ekranından toplu soru üretip bankaya aktarın.',
                    'Eğitim Katılım Formu\'ndaki "10 Soruluk Sınav Kâğıdı & Cevap Anahtarı PDF" çıktısı bu havuzdan otomatik beslenir.',
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Soru bankanız ne kadar zenginse her firma ve ekip için farklılaşan, tekrar etmeyen değerlendirme sınavları üretebilirsiniz.',
            ],
            Pages\EgitimSorulari::class => [
                'baslik' => 'AI ile Eğitim Sorusu & Sınav Kâğıdı Üretici',
                'ozet' => 'Seçili firma, sektör veya özel eğitim konusu için yapay zekâ ile çoktan seçmeli ön/son test soruları ve cevap anahtarı oluşturun.',
                'adimlar' => [
                    'Firmayı, eğitim konusunu (örn. Yüksekte Çalışma, Kalıp İşleri, Metal İmalat) ve soru sayısını belirleyin.',
                    'Yapay zekâ ile soruları üretip şıkları ve doğru cevapları gözden geçirin.',
                    'Beğendiğiniz soruları Soru Bankasına kaydedin veya doğrudan PDF sınav kâğıdı olarak yazdırın.',
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Sektöre özgü 4. bölüm eğitimleri için bu ekrandan ekibe özel 10 soruluk sınav üretip Eğitim Katılım tutanağıyla birlikte arşivleyebilirsiniz.',
            ],
            Pages\ToolboxKonusmalari::class => [
                'baslik' => 'Toolbox (5 Dakikalık Saha Konuşmaları)',
                'ozet' => 'Vardiya veya kritik iş öncesi sahada yapılan 5 dakikalık kısa İSG bilgilendirmelerini konu metni ve katılımcı imzasıyla belgeleyin.',
                'adimlar' => [
                    'Firmayı ve saha konusunu (iskele, sıcak iş, forklift, KKD vb.) seçin veya özel konu yazın.',
                    'Konuşmaya katılan çalışanları işaretleyin (şube/görev bazlı seçebilirsiniz).',
                    'Tek sayfalık Toolbox tutanağı PDF\'ini indirip sahada imzalatarak arşivleyin.',
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => 'Toolbox konuşmaları şantiyelerde ve üretim sahalarında iş kazalarını önlemenin ve denetimlerde aktif İSG kültürünü kanıtlamanın en pratik yoludur.',
            ],
            Pages\IseOzguEgitimKonulari::class => [
                'baslik' => 'İşe Özgü Eğitim Konuları Kütüphanesi (4. Bölüm & API / AI)',
                'ozet' => 'Yıllık Eğitim Planı ve Eğitim Katılım Formu\'nun 4. Bölümünü ("İşe ve İşyerine Özgü Riskler") besleyen merkezi kütüphane. Gemini API ile tüm 21 sektörü tek tıkla doldurabilirsiniz.',
                'adimlar' => [
                    'Sağ üstteki "✨ API / AI ile Sektör Konuları Üret" butonuna tıklayın.',
                    'Tek sektör, birden çok sektör veya "Tüm sektörler için toplu üret (21 sektör)" seçeneğini belirleyip sektör başına konu adedini (örn. 5) seçin.',
                    'Üretilen konular otomatik NACE ön ekleriyle kütüphaneye kaydedilir; o NACE\'deki tüm firmaların Yıllık Eğitim Planı ve Eğitim Katılım formlarına doğrudan entegre olur.',
                    'İsterseniz "Yeni Konu" ile belirli bir ekibe (ör. Kalıp Ekibi, Sıva Ekibi) özel konu ekleyebilir veya sistem konularını düzenleyip gizleyebilirsiniz.',
                ],
                'bolumler' => [
                    [
                        'baslik' => 'Kütüphane Diğer Ekranlarla Nasıl Senkron Çalışır?',
                        'etiket' => 'Otomatik Entegrasyon',
                        'maddeler' => [
                            'Yıllık Eğitim Planı: Yeni plan açılırken veya "İşe Özgü Konuları Seç" modalında firmanın NACE koduna/iş kalemine uyan kütüphane konuları ★ yıldızlı önerilir.',
                            'Eğitim Katılım Formu: Kütüphaneye eklediğiniz kendi/AI sektör konuları o NACE\'deki firmalarda otomatik gelir; "İş kolu" etiketi verdiğiniz konular ise yalnız o ekip seçildiğinde eklenir.',
                            'Otomatik Süre Dağıtımı: Kütüphaneden kaç konu seçerseniz seçin, hem Yıllık Eğitim Planı saatleri hem Eğitim Katılım dakikaları toplam yasal süreye göre otomatik bölünür.',
                        ],
                    ],
                ],
                'yonlendirmeler' => static::egitimYonlendirmeleri(),
                'ipucu' => '21 sektörü tek seferde üretmek ~1–2 dakika sürebilir; daha hızlı kontrol etmek için 4–5\'erli sektör grupları hâlinde de üretebilirsiniz.',
            ],
            Pages\YillikPlan\YillikEgitimPlani::class => [
                'baslik' => 'Yıllık Eğitim Planı — Online / Yüz Yüze Karma & 4. Bölüm Yönetimi',
                'ozet' => 'RG 02.04.2026 (33212) uyumlu yıllık eğitim planını Online + Yüz Yüze (Karma) olarak planlayın, NACE uyuşmayan firmalarda Gerçek İş Grubu atayın ve aynı işi yapan firmaları tek tıkla eşitleyin.',
                'adimlar' => [
                    'Firmayı ve yılı seçin; üst yeşil şeritten "Karma — MEHSE LMS (8s/4s Online + Kalanı Yüz Yüze)" modunu seçip "Online / Yüz Yüze Otomatik Planla" butonuna basın.',
                    'NACE kodu gerçek işiyle uyuşmayan firmada "4. Bölüm Gerçek İş Grubu" seçicisinden doğru sektörü seçin ve "4. Bölümü Gruba Göre Doldur" deyin.',
                    '4. bölümü düzenledikten sonra "Aynı İş Grubundaki Firmalara Eşitle" butonuna basarak aynı işi yapan tüm firmaların 4. bölümünü tek tıkla güncelleyin (tamamlanmış G ayları korunur).',
                    'Alttaki "✨ AI ile 4. Bölüm Üret" veya "+ Ekle" ile konu ekledikçe/sildikçe satır saatleri toplam eğitim süresine göre otomatik güncellenir; hazır olduğunda Excel çıktısı alın.',
                ],
                'bolumler' => [
                    [
                        'baslik' => 'RG 02.04.2026 Mevzuat Kilidi & Otomatik G İşleme',
                        'etiket' => 'Otomasyon',
                        'maddeler' => [
                            'Tehlikeli ve Çok Tehlikeli sınıflarda 4. Bölüm (İşe Özgü) ve 5. Bölüm (Diğer Uygulamalı Eğitimler) kilitli olarak "Yüz Yüze 🔒" kalır; yanlışlıkla Online yapılamaz.',
                            'Çalışanlar LMS üzerinden online eğitimi bitirdiğinde ilgili online konular o ay otomatik "G (Gerçekleşti)" işlenir; yüz yüze katılım formu kaydedildiğinde de yüz yüze konular "G" olur.',
                            'Eğitim Katılım Formunda ekip bazlı (Kalıp / Sıva) eğitim kaydedildiğinde o ekibin konuları plana 👷 ekip rozetiyle ve Excel C sütununa [EKİP: ...] etiketiyle işlenir.',
                        ],
                    ],
                ],
                'sure_tablosu' => static::egitimSureTablosu(),
                'yonlendirmeler' => [
                    ['sinif' => Pages\IseOzguEgitimKonulari::class, 'ikon' => '✨', 'etiket' => 'İşe Özgü Konular (API / AI)', 'aciklama' => 'Tüm 21 sektör için 4. Bölüm kütüphanesini API ile doldurun.'],
                    ['sinif' => Pages\EgitimKatilim::class, 'ikon' => '📝', 'etiket' => 'Eğitim Katılım Formu (3 Mod)', 'aciklama' => 'Plandaki 4. bölümle birebir eşleşen yüz yüze / karma / online tutanak üretin.'],
                    ['sinif' => Pages\UzaktanEgitimAtama::class, 'ikon' => '💻', 'etiket' => 'Online Eğitim Ata (LMS)', 'aciklama' => 'Plandaki online saatleri çalışanlara hemen atayın.'],
                    ['sinif' => Pages\YillikPlan\YillikCalismaPlani::class, 'ikon' => '📋', 'etiket' => 'Yıllık Çalışma Planı', 'aciklama' => 'Aynı firmanın Yıllık Çalışma Planına geçin.'],
                    ['sinif' => Pages\YillikPlan\YillikDegerlendirmeRaporu::class, 'ikon' => '📊', 'etiket' => 'Yıllık Değerlendirme Raporu', 'aciklama' => 'Yıl sonu değerlendirme raporunu sistem kayıtlarından otomatik doldurun.'],
                ],
                'ipucu' => 'Tehlikeli sınıfta 4. Bölüm 4 saat (8s Online + 4s Yüz Yüze = 12s; Tekrar 4s Online + 4s Yüz Yüze = 8s) olarak planlanır ve Eğitim Katılım Formu ile birebir eşleşir.',
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
            Pages\GeciciGorevlendirme::class => [
                'baslik' => 'Geçici Görevlendirme & Dış Saha Görev Emri',
                'ozet' => 'Şantiye, şubeler arası, 4857 m.7 geçici iş ilişkisi veya teknik servis için İSG taahhütnameli resmi görev emri PDF\'i üretin.',
                'adimlar' => [
                    'Üstten 4 yasal şablondan uygun olanı seçin ve firma, görev yeri, tarih aralığı ile görev konusunu girin.',
                    'Aktif çalışan listesinden bir veya birden fazla kişi işaretleyin (isterseniz KVKK T.C. maskelemeyi açın).',
                    'Tek çalışanda canlı imza alıp "📄 Geçici Görevlendirme PDF İndir" ile her kişi için ayrı A4 belge üretin.',
                ],
                'ipucu' => '4857 sayılı İş Kanunu m.7 kapsamındaki geçici iş ilişkisi görevlendirmesinde çalışanın yazılı rızası belgeye otomatik eklenir.',
            ],
            Pages\CariHesap::class => [
                'baslik' => 'OSGB Cari Hesap & Tahsilat Takibi',
                'ozet' => 'Müşteri firmalarınızın aylık İSG hizmet bedellerini, borç/tahsilat hareketlerini ve güncel bakiyelerini takip edin.',
                'adimlar' => [
                    'Alt tablodan bir firmanın "Yönet" düğmesine tıklayıp aylık sözleşme bedelini ve KDV oranını kaydedin.',
                    'Her ay başında sağ üstteki "⚡ Bu Ay Tüm Firmalara Tahakkuk Oluştur" ile tüm aylık hizmet bedellerini tek tıkla borç işleyin.',
                    'Ödeme geldiğinde tahsilat girin ve "📄 PDF Hesap Ekstresi İndir" ile müşteriye resmi ekstre gönderin.',
                ],
                'ipucu' => 'Toplu tahakkuk aynı dönem için ikinci kez borç oluşturmaz; mükerrer kayıt riski olmadan her ay güvenle kullanabilirsiniz.',
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
