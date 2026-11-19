<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * İSG Terimler Sözlüğü — isgpratik "/isg-sozluk" karşılığı. Sahada, risk analizlerinde,
 * ortam ölçümlerinde ve mevzuatta geçen temel İSG kavramlarının pratik açıklamaları.
 */
class IsgSozlukKutuphanesi
{
    public const KATEGORILER = [
        'mevzuat' => 'Genel İSG & Mevzuat',
        'risk' => 'Risk Analizi & Metodoloji',
        'kimyasal' => 'Kimyasal, Patlama & Yangın',
        'olcum' => 'Ortam Ölçümü & Ergonomi',
        'saglik' => 'Meslek Hastalığı & Sağlık',
        'saha' => 'Saha, Ekipman & KKD',
    ];

    /**
     * @return array<int, array{terim: string, kategori: string, aciklama: string}>
     */
    public static function tumu(): array
    {
        return [
            ['terim' => 'Ramak Kala Olay (Near-Miss)', 'kategori' => 'mevzuat', 'aciklama' => 'İşyerinde meydana gelen; çalışan, işyeri ya da iş ekipmanını zarara uğratma potansiyeli olduğu halde zarara uğratmayan olaydır (6331 s.K. m.14 kapsamında kayıt altına alınması zorunludur).'],
            ['terim' => 'Tehlike (Hazard)', 'kategori' => 'risk', 'aciklama' => 'İşyerinde var olan ya da dışarıdan gelebilecek, çalışanı veya işyerini etkileyebilecek zarar veya hasar verme potansiyelidir.'],
            ['terim' => 'Risk', 'kategori' => 'risk', 'aciklama' => 'Tehlikeden kaynaklanacak kayıp, yaralanma ya da başka zararlı sonuç meydana gelme ihtimali ile şiddetinin bileşkesidir.'],
            ['terim' => 'Kabul Edilebilir Risk', 'kategori' => 'risk', 'aciklama' => 'Yasal yükümlülüklere ve işyerinin önleme politikasına uygun, kayıp veya yaralanma oluşturmayacak düzeye indirilmiş risk seviyesidir.'],
            ['terim' => 'Fine-Kinney Metodu', 'kategori' => 'risk', 'aciklama' => 'Risk = Olasılık (0.2–10) × Frekans (0.5–10) × Şiddet (1–100) formülüyle hesaplanan, özellikle sanayi ve inşaatta yaygın kullanılan kantitatif risk değerlendirme yöntemidir.'],
            ['terim' => '5×5 L Tipi Matris', 'kategori' => 'risk', 'aciklama' => 'Olasılık (1–5) ve Şiddet (1–5) çarpımıyla 1 ila 25 arasında risk skoru üreten neden-sonuç ilişkili temel risk değerlendirme yöntemidir.'],
            ['terim' => 'JSA (İş Güvenliği Analizi / Job Safety Analysis)', 'kategori' => 'risk', 'aciklama' => 'Belirli bir işin veya görevin adım adım alt basamaklara ayrılarak her adımdaki tehlike ve kontrol önlemlerinin belirlendiği işe özgü risk analizi yöntemidir.'],
            ['terim' => 'HAZOP (Tehlike ve İşletilebilirlik Analizi)', 'kategori' => 'risk', 'aciklama' => 'Kimya ve proses endüstrisinde kılavuz kelimeler (Fazla, Az, Yok, Ters vb.) kullanılarak basınç, sıcaklık ve akış sapmalarının incelendiği sistematik metodolojidir.'],
            ['terim' => 'FMEA (Hata Türü ve Etkileri Analizi)', 'kategori' => 'risk', 'aciklama' => 'RPN = Şiddet (S) × Oluşma Olasılığı (O) × Saptanabilirlik (D) çarpımıyla sistem veya ekipman arıza modlarını önceliklendiren mühendislik analizidir.'],
            ['terim' => '5 Neden (5 Whys) & Ishikawa (Balık Kılçığı)', 'kategori' => 'risk', 'aciklama' => 'İş kazası veya uygunsuzluğun görünür semptomlarından başlayıp art arda "Neden?" sorusuyla ve 6M (İnsan, Makine, Metot, Malzeme, Ölçüm, Çevre) modeliyle kök nedene ulaşma tekniğidir.'],
            ['terim' => 'DÖF (Düzeltici ve Önleyici Faaliyet)', 'kategori' => 'mevzuat', 'aciklama' => 'Saha denetimi, iş kazası veya uygunsuzluk sonrasında kök nedeni ortadan kaldırmak üzere sorumlu ve termin tarihi atanarak takip edilen aksiyon kaydıdır.'],
            ['terim' => 'Çalışmaktan Kaçınma Hakkı', 'kategori' => 'mevzuat', 'aciklama' => '6331 s.K. m.13 uyarınca ciddi ve yakın tehlike ile karşı karşıya kalan çalışanların, gerekli tedbirler alınıncaya kadar çalışmaktan kaçınma hakkıdır.'],
            ['terim' => 'İşin Durdurulması', 'kategori' => 'mevzuat', 'aciklama' => '6331 s.K. m.25 uyarınca çalışanlar için hayati tehlike oluşturan durumlarda veya çok tehlikeli sınıfta risk değerlendirmesi yapılmadığında işin bir bölümünün veya tamamının müfettiş/heyet kararıyla durdurulmasıdır.'],
            ['terim' => 'Onaylı Defter (Tespit ve Öneri Defteri)', 'kategori' => 'mevzuat', 'aciklama' => 'İGU ve İşyeri Hekiminin işyerine ilişkin tespit ve önerilerini yazdığı; noter, İŞKUR veya Çalışma ve İş Kurumu İl Müdürlüğünce onaylanan ve yazılanların işverene tebliğ edilmiş sayıldığı resmi defterdir.'],
            ['terim' => 'İSG-KATİP', 'kategori' => 'mevzuat', 'aciklama' => 'Çalışma ve Sosyal Güvenlik Bakanlığı tarafından İGU, İşyeri Hekimi, DSP ve OSGB sözleşme/görevlendirme işlemlerinin elektronik ortamda takip edildiği sistemdir.'],
            ['terim' => 'Destek Elemanı', 'kategori' => 'mevzuat', 'aciklama' => 'Asli görevinin yanında İSG ile ilgili önleme, koruma, tahliye, yangınla mücadele ve ilk yardım konularında özel olarak görevlendirilmiş uygun donanım ve eğitime sahip çalışandır.'],
            ['terim' => 'Çalışan Temsilcisi', 'kategori' => 'mevzuat', 'aciklama' => '6331 s.K. m.20 uyarınca İSG çalışmalarını izleme, tedbir alınmasını isteme ve çalışanları temsil etme yetkisine sahip, seçimle (veya aday çıkmazsa atamayla) belirlenen 1 ila 6 çalışandır.'],
            ['terim' => 'LOTO (Lockout / Tagout — Kilitleme ve Etiketleme)', 'kategori' => 'saha', 'aciklama' => 'Bakım, onarım veya temizlik sırasında makinenin elektrik, pnömatik, hidrolik vb. tüm enerji kaynaklarının kesilerek kişisel asma kilitle kilitlenmesi ve etiketlenmesi prosedürüdür.'],
            ['terim' => 'PTW (Permit to Work — İş İzin Sistemi)', 'kategori' => 'saha', 'aciklama' => 'Yüksekte çalışma, sıcak işlem (kaynak/kesme), kapalı alan, kazı ve elektrik işleri gibi yüksek riskli rutin dışı işler öncesinde kontrol listesi ve onay imzasıyla verilen yazılı çalışma iznidir.'],
            ['terim' => 'KKD (Kişisel Koruyucu Donanım) & CE / EN Standardı', 'kategori' => 'saha', 'aciklama' => 'Toplu koruma tedbirleriyle önlenemeyen risklere karşı çalışanı koruyan, CE işaretli ve ilgili TS EN standardına (örn. EN 397 Baret, EN 388 Eldiven, EN ISO 20345 İş Ayakkabısı) uygun donanımdır.'],
            ['terim' => 'MYK (Mesleki Yeterlilik Belgesi)', 'kategori' => 'saha', 'aciklama' => '5544 sayılı Kanun ve 6331 s.K. m.17 uyarınca tehlikeli ve çok tehlikeli sınıftaki tebliğ kapsamı mesleklerde çalışanların sahip olması zorunlu ulusal yeterlilik belgesidir.'],
            ['terim' => 'Periyodik Kontrol (İş Ekipmanı Muayenesi)', 'kategori' => 'saha', 'aciklama' => 'Basınçlı kaplar, kaldırma-iletme ekipmanları (vinç, forklift, caraskal) ve elektrik/topraklama tesisatının yönetmelikte belirtilen azami sürelerde EKİPNET kayıtlı yetkili uzmanlarca muayene ve test edilmesidir.'],
            ['terim' => 'GBF / MSDS (Güvenlik Bilgi Formu)', 'kategori' => 'kimyasal', 'aciklama' => 'Kimyasal maddelerin tehlike özelliklerini (GHS piktogramları, H/P ifadeleri), ilk yardım, yangın, sızıntı, depolama ve KKD önlemlerini içeren 16 bölümlük standart teknik belgedir.'],
            ['terim' => 'CAS Numarası', 'kategori' => 'kimyasal', 'aciklama' => 'Her kimyasal bileşiğe Chemical Abstracts Service tarafından verilen, kimyasalı ticari adından bağımsız olarak dünyada tekil biçimde tanımlayan sayısal koddur.'],
            ['terim' => 'ATEX & PKD (Patlamadan Korunma Dokümanı)', 'kategori' => 'kimyasal', 'aciklama' => 'Yanıcı gaz, buhar, sis veya tozların hava ile patlayıcı ortam oluşturabileceği işyerlerinde Bölge (Zone 0/1/2 veya 20/21/22) sınıflandırmasını ve Ex-proof ekipman tedbirlerini içeren zorunlu dokümandır.'],
            ['terim' => 'LEL / UEL (Alt ve Üst Patlama Sınırı)', 'kategori' => 'kimyasal', 'aciklama' => 'Yanıcı bir gaz veya buharın havadaki hacimsel yüzdesinin patlama/parlama oluşturabileceği en düşük (LEL) ve en yüksek (UEL) konsantrasyon aralığıdır.'],
            ['terim' => 'Yangın Sınıfları (A, B, C, D, F)', 'kategori' => 'kimyasal', 'aciklama' => 'A: Katı madde (odun, kağıt, tekstil), B: Yanıcı sıvı (benzin, boya, tiner), C: Yanıcı gaz (LPG, doğalgaz), D: Yanıcı metal (magnezyum, alüminyum), F: Pişirme yağları yangınlarıdır.'],
            ['terim' => 'Lex,8h (Günlük Gürültü Maruziyet Düzeyi)', 'kategori' => 'olcum', 'aciklama' => 'Sekiz saatlik bir iş günü için zaman ağırlıklı ortalama gürültü maruziyet düzeyidir (En düşük maruziyet eylem değeri: 80 dB(A), En yüksek eylem: 85 dB(A), Sınır değer: 87 dB(A)).'],
            ['terim' => 'TWA & STEL (Kimyasal Maruziyet Sınır Değerleri)', 'kategori' => 'olcum', 'aciklama' => 'TWA: Günde 8 saat ve haftada 40 saatlik çalışma süresi için zaman ağırlıklı ortalama sınır değer; STEL: Başka süre belirtilmedikçe 15 dakikalık kısa süreli maruziyet sınır değeridir.'],
            ['terim' => 'Termal Konfor (PMV / PPD / WBGT)', 'kategori' => 'olcum', 'aciklama' => 'Çalışma ortamındaki sıcaklık, nem, hava akım hızı ve radyant ısının çalışanların ısıl rahatlığı ve sıcak/soğuk stresi üzerindeki etkisinin ölçümüdür.'],
            ['terim' => 'Kaza Sıklık Hızı & Kaza Ağırlık Hızı', 'kategori' => 'olcum', 'aciklama' => 'Sıklık Hızı = (Kaza Sayısı × 1.000.000) / Toplam Çalışma Saati; Ağırlık Hızı = (Kayıp Gün Sayısı × 1.000) / Toplam Çalışma Saati formülleriyle hesaplanan uluslararası İSG performans göstergeleridir.'],
            ['terim' => 'EK-2 (İşe Giriş / Periyodik Muayene Formu)', 'kategori' => 'saglik', 'aciklama' => 'İşyeri Hekimi tarafından çalışanın tıbbi anamnezi, fizik muayenesi, laboratuvar/tetkik sonuçları (odyometri, SFT, akciğer grafisi vb.) değerlendirilerek düzenlenen resmi sağlık gözetim belgesidir.'],
            ['terim' => 'Odyometri & SFT (Solunum Fonksiyon Testi)', 'kategori' => 'saglik', 'aciklama' => 'Odyometri: Gürültülü işlerde işitme kaybını izlemek için yapılan işitme testi; SFT: Tozlu ve kimyasal ortamlarda akciğer kapasitesini ölçen solunum testidir.'],
            ['terim' => 'Pnömokonyoz', 'kategori' => 'saglik', 'aciklama' => 'Akciğerlerde inorganik tozların (silis, kömür, asbest vb.) birikmesi ve buna karşı doku reaksiyonu sonucu oluşan mesleki akciğer hastalığıdır.'],
        ];
    }

    /**
     * @return array<int, array{terim: string, kategori: string, aciklama: string}>
     */
    public static function filtrele(string $arama = '', string $kategori = ''): array
    {
        $terim = Str::lower(trim($arama));

        return collect(static::tumu())
            ->when($kategori !== '', fn ($q) => $q->where('kategori', $kategori))
            ->when($terim !== '', fn ($q) => $q->filter(fn (array $m) => Str::contains(Str::lower($m['terim'].' '.$m['aciklama']), $terim)))
            ->values()
            ->all();
    }
}
