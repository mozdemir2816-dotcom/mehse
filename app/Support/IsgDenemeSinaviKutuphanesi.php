<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * İSG Uzmanlık Deneme Sınavı (ÖSYM Modu) ve Saha / Sınav Hap Notları Kütüphanesi
 * — isgpratik "/deneme-sinavi" & "Hap Notlar (PDF)" karşılığı.
 */
class IsgDenemeSinaviKutuphanesi
{
    /**
     * ÖSYM formatında 5 şıklı (A–E), çözümlü ve mevzuat dayanaklı İSG uzmanlık soruları.
     *
     * @return array<int, array{konu: string, soru: string, siklar: array<int, string>, dogru: int, cozum: string}>
     */
    public static function sorular(): array
    {
        return [
            [
                'konu' => '6331 Sayılı İSG Kanunu',
                'soru' => '6331 sayılı İş Sağlığı ve Güvenliği Kanunu\'na göre, Çok Tehlikeli sınıfta yer alan ve 350 çalışanı bulunan bir işyerinde en az kaç çalışan temsilcisi görevlendirilmelidir?',
                'siklar' => ['A) 1', 'B) 2', 'C) 3', 'D) 4', 'E) 5'],
                'dogru' => 2,
                'cozum' => '6331 s.K. m.20 uyarınca çalışan temsilcisi sayıları: 2–50 çalışan: 1, 51–100: 2, 101–500: 3, 501–1000: 4, 1001–2000: 5, 2001+: 6 temsilcidir. Tehlike sınıfı temsilci sayısını değiştirmez; 350 çalışan için 3 temsilci gerekir.',
            ],
            [
                'konu' => 'İGU & İşyeri Hekimi Süreleri',
                'soru' => 'İş Güvenliği Uzmanlarının Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik\'e göre Tehlikeli sınıfta yer alan bir işyerinde çalışan başına aylık asgari İGU görevlendirme süresi kaç dakikadır?',
                'siklar' => ['A) 5 dakika', 'B) 10 dakika', 'C) 15 dakika', 'D) 20 dakika', 'E) 40 dakika'],
                'dogru' => 3,
                'cozum' => 'İGU Yönetmeliği m.12 uyarınca çalışan başına aylık asgari süreler: Az Tehlikeli sınıfta 10 dk, Tehlikeli sınıfta 20 dk, Çok Tehlikeli sınıfta 40 dakikadır.',
            ],
            [
                'konu' => 'Risk Değerlendirmesi & Acil Durum',
                'soru' => 'Tehlikeli sınıfta yer alan bir işyerinde Risk Değerlendirmesi ve Acil Durum Planı normal şartlarda en geç kaç yılda bir yenilenir?',
                'siklar' => ['A) 1 yılda bir', 'B) 2 yılda bir', 'C) 3 yılda bir', 'D) 4 yılda bir', 'E) 6 yılda bir'],
                'dogru' => 3,
                'cozum' => 'Risk Değerlendirmesi Yönetmeliği m.12 ve Acil Durumlar Yönetmeliği m.14 uyarınca yenileme süreleri Çok Tehlikeli sınıfta 2 yıl, Tehlikeli sınıfta 4 yıl, Az Tehlikeli sınıfta 6 yıldır.',
            ],
            [
                'konu' => 'Çalışanların İSG Eğitimleri',
                'soru' => 'Çok Tehlikeli sınıfta yer alan bir işyerinde çalışanlara verilecek Temel İSG Eğitiminin asgari süresi ve tekrar periyodu aşağıdakilerden hangisidir?',
                'siklar' => [
                    'A) En az 8 saat — 3 yılda bir',
                    'B) En az 12 saat — 2 yılda bir',
                    'C) En az 16 saat — Yılda bir',
                    'D) En az 16 saat — 2 yılda bir',
                    'E) En az 24 saat — Yılda bir',
                ],
                'dogru' => 2,
                'cozum' => 'Çalışanların İSG Eğitimleri Yönetmeliği m.6 uyarınca: Az Tehlikeli (8 saat / 3 yıl), Tehlikeli (12 saat / 2 yıl), Çok Tehlikeli (16 saat / yılda 1) olarak uygulanır.',
            ],
            [
                'konu' => 'Gürültü Yönetmeliği',
                'soru' => 'Çalışanların Gürültü ile İlgili Risklerden Korunmalarına Dair Yönetmelik\'e göre "En Düşük Maruziyet Eylem Değeri", "En Yüksek Maruziyet Eylem Değeri" ve "Maruziyet Sınır Değeri" (Lex,8h) sırasıyla kaç dB(A)\'dır?',
                'siklar' => [
                    'A) 75 – 80 – 85 dB(A)',
                    'B) 80 – 85 – 87 dB(A)',
                    'C) 80 – 85 – 90 dB(A)',
                    'D) 82 – 85 – 87 dB(A)',
                    'E) 85 – 87 – 90 dB(A)',
                ],
                'dogru' => 1,
                'cozum' => 'Gürültü Yönetmeliği m.5 uyarınca: En düşük maruziyet eylem değeri = 80 dB(A) (KKD hazır bulundurulur), En yüksek maruziyet eylem değeri = 85 dB(A) (KKD takılması zorunlu), Sınır değer = 87 dB(A).',
            ],
            [
                'konu' => 'İş Ekipmanları & Periyodik Kontrol',
                'soru' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği\'ne göre basınçlı kaplarda temel ilke olarak hidrostatik test basıncı, aksi standartlarda belirtilmedikçe işletme basıncının kaç katı ile yapılır?',
                'siklar' => ['A) 1,1 katı', 'B) 1,25 katı', 'C) 1,5 katı', 'D) 2 katı', 'E) 2,5 katı'],
                'dogru' => 2,
                'cozum' => 'İş Ekipmanları Yönetmeliği Ek-III m.2.1.1 uyarınca hidrostatik testler, standartlarda aksi belirtilmediği sürece işletme basıncının 1,5 katı ile ve bir yılı aşmayan sürelerle yapılır.',
            ],
            [
                'konu' => 'Yapı İşlerinde İSG',
                'soru' => 'Yapı İşlerinde İş Sağlığı ve Güvenliği Yönetmeliği\'ne göre yüksekten düşmeyi önlemek amacıyla yapılacak geçici kenar koruma (korkuluk) sistemlerinde üst korkuluk, ara korkuluk açıklığı ve topuk levhası ölçüleri nasıl olmalıdır?',
                'siklar' => [
                    'A) Üst: en az 90 cm, Açıklık: en fazla 50 cm, Topuk: en az 10 cm',
                    'B) Üst: en az 100 cm, Açıklık: en fazla 47 cm, Topuk: en az 15 cm',
                    'C) Üst: en az 110 cm, Açıklık: en fazla 60 cm, Topuk: en az 10 cm',
                    'D) Üst: en az 100 cm, Açıklık: en fazla 55 cm, Topuk: en az 12 cm',
                    'E) Üst: en az 120 cm, Açıklık: en fazla 45 cm, Topuk: en az 15 cm',
                ],
                'dogru' => 1,
                'cozum' => 'Yapı İşlerinde İSG Yönetmeliği Ek-4 uyarınca: Çalışma platformundan en az 1 metre (100 cm) yükseklikte üst korkuluk, en az 15 cm yüksekliğinde topuk levhası ve herhangi bir açıklığı 47 cm\'den fazla olmayacak şekilde ara korkuluk bulunur.',
            ],
            [
                'konu' => 'Acil Durumlar & İlk Yardım',
                'soru' => 'Çok Tehlikeli sınıfta yer alan ve 90 çalışanı bulunan bir işyerinde görevlendirilmesi gereken asgari "Sertifikalı İlk Yardımcı" sayısı ile "Söndürme, Kurtarma, Koruma" destek elemanı toplam sayısı kaçtır?',
                'siklar' => [
                    'A) 9 İlk Yardımcı — Her ekipten 3\'er kişi (Toplam 9 destek elemanı)',
                    'B) 6 İlk Yardımcı — Her ekipten 2\'şer kişi (Toplam 6 destek elemanı)',
                    'C) 9 İlk Yardımcı — Her ekipten 2\'şer kişi (Toplam 6 destek elemanı)',
                    'D) 5 İlk Yardımcı — Her ekipten 3\'er kişi (Toplam 9 destek elemanı)',
                    'E) 10 İlk Yardımcı — Her ekipten 1\'er kişi (Toplam 3 destek elemanı)',
                ],
                'dogru' => 0,
                'cozum' => 'Çok Tehlikeli sınıfta her 10 çalışana 1 ilk yardımcı (90/10 = 9 ilk yardımcı) ve her 30 çalışana kadar söndürme, kurtarma ve koruma ekiplerinin her biri için 1\'er destek elemanı (90/30 = 3\'er kişi × 3 ekip = toplam 9 destek elemanı) görevlendirilir.',
            ],
            [
                'konu' => 'İş Kazası Bildirimi',
                'soru' => 'İşveren, meydana gelen bir iş kazasını Sosyal Güvenlik Kurumuna (SGK) en geç hangi süre içinde bildirmekle yükümlüdür?',
                'siklar' => [
                    'A) Kazanın olduğu gün mesai bitimine kadar',
                    'B) Kazadan sonraki 2 iş günü içinde',
                    'C) Kazadan sonraki 3 iş günü içinde',
                    'D) Kazadan sonraki 5 iş günü içinde',
                    'E) Kazadan sonraki 10 gün içinde',
                ],
                'dogru' => 2,
                'cozum' => '5510 s.K. m.13 ve 6331 s.K. m.14 uyarınca işveren iş kazasını kazadan sonraki 3 İŞ GÜNÜ içinde SGK\'ya bildirmek zorundadır (Sağlık hizmeti sunucuları ise en geç 10 gün içinde bildirir).',
            ],
            [
                'konu' => 'Sağlık Gözetimi (EK-2)',
                'soru' => 'Tehlikeli sınıftaki bir işyerinde çalışanların periyodik sağlık muayeneleri (EK-2) en geç kaç yılda bir tekrarlanır?',
                'siklar' => ['A) 6 ayda bir', 'B) Yılda bir', 'C) 2 yılda bir', 'D) 3 yılda bir', 'E) 5 yılda bir'],
                'dogru' => 3,
                'cozum' => 'İşyeri Hekimi Yönetmeliği uyarınca periyodik sağlık muayeneleri: Az Tehlikeli sınıfta en geç 5 yılda bir, Tehlikeli sınıfta en geç 3 yılda bir, Çok Tehlikeli sınıfta en geç yılda 1 defa (Özel politika gerektirenlerde 6 ayda bir) yapılır.',
            ],
        ];
    }

    /**
     * Saha ve ÖSYM sınavları için 12 kritik sayısal Hap Not tablosu.
     *
     * @return array<int, array{baslik: string, kategori: string, maddeler: array<int, string>}>
     */
    public static function hapNotlar(): array
    {
        return [
            [
                'baslik' => '1. İGU, İşyeri Hekimi ve DSP Aylık Süreleri',
                'kategori' => 'Görevlendirme',
                'maddeler' => [
                    'İGU Süresi: Az Tehlikeli 10 dk · Tehlikeli 20 dk · Çok Tehlikeli 40 dk / çalışan',
                    'Tam Gün İGU Kontenjanı: Az Tehlikeli 1000 · Tehlikeli 500 · Çok Tehlikeli 250 çalışan',
                    'İşyeri Hekimi Süresi: Az Tehlikeli 5 dk · Tehlikeli 10 dk · Çok Tehlikeli 15 dk / çalışan',
                    'Tam Gün Hekim Kontenjanı: Az Tehlikeli 2000 · Tehlikeli 1000 · Çok Tehlikeli 750 çalışan',
                    'DSP (Yalnız Çok Tehlikeli): 10–49 çalışan: 10 dk · 50–249: 15 dk · 250+: 20 dk / çalışan',
                ],
            ],
            [
                'baslik' => '2. Yasal Evrak & Muayene Yenileme Periyotları (Az / Tehlikeli / Çok)',
                'kategori' => 'Periyotlar',
                'maddeler' => [
                    'Risk Değerlendirmesi Yenileme: 6 Yıl / 4 Yıl / 2 Yıl',
                    'Acil Durum Planı Yenileme: 6 Yıl / 4 Yıl / 2 Yıl (Tatbikat: Tüm sınıflarda yılda en az 1)',
                    'Periyodik Sağlık Muayenesi (EK-2): 5 Yıl / 3 Yıl / 1 Yıl (Çocuk, genç, gebe: 6 ayda 1)',
                    'Temel İSG Eğitimi: 8 Saat (3 Yılda 1) / 12 Saat (2 Yılda 1) / 16 Saat (Yılda 1)',
                    'İSG Kurulu Toplantısı (50+ Çalışan): 3 Ayda 1 / 2 Ayda 1 / Ayda 1',
                ],
            ],
            [
                'baslik' => '3. İlk Yardımcı, Destek Elemanı ve Çalışan Temsilcisi Sayıları',
                'kategori' => 'Kontenjanlar',
                'maddeler' => [
                    'Sertifikalı İlk Yardımcı: Az Tehlikeli 20\'de 1 · Tehlikeli 15\'te 1 · Çok Tehlikeli 10\'da 1',
                    'Söndürme / Kurtarma / Koruma: Az Tehlikeli 50\'de 1\'er · Tehlikeli 40\'ta 1\'er · Çok Tehlikeli 30\'da 1\'er',
                    'Çalışan Temsilcisi: 2–50: 1 · 51–100: 2 · 101–500: 3 · 501–1000: 4 · 1001–2000: 5 · 2001+: 6',
                ],
            ],
            [
                'baslik' => '4. Gürültü ve Titreşim Maruziyet Sınır Değerleri',
                'kategori' => 'Fiziksel Risk',
                'maddeler' => [
                    'Gürültü (Lex,8h): En Düşük Eylem = 80 dB(A) · En Yüksek Eylem = 85 dB(A) · Sınır Değer = 87 dB(A)',
                    'El-Kol Titreşimi (8 saat): Maruziyet Eylem Değeri = 2,5 m/s² · Sınır Değer = 5,0 m/s²',
                    'Bütün Vücut Titreşimi (8 saat): Maruziyet Eylem Değeri = 0,5 m/s² · Sınır Değer = 1,15 m/s²',
                ],
            ],
            [
                'baslik' => '5. İş Ekipmanları Periyodik Kontrol Azami Süreleri & Test Katsayıları',
                'kategori' => 'Ekipman',
                'maddeler' => [
                    'Basınçlı Kaplar (Kompresör, Kazan, Hidrofor): Yılda 1 (Hidrostatik test = 1,5 × İşletme Basıncı)',
                    'Kaldırma & İletme (Forklift, Vinç, Caraskal, Transpalet): Yılda 1 (Statik test = 1,25×, Dinamik = 1,1×)',
                    'Elektrik Tesisatı, Topraklama ve Paratoner: Yılda 1 (Akümülatör & Trafo: Yılda 1)',
                    'Yangın Tesisatı, Hortumlar ve Motopomplar: Yılda 1 (Yangın Tüpleri görsel/tartım: 6 ayda/yılda 1)',
                    'İskeleler (Yapı İşleri): 6 Ayda 1',
                ],
            ],
            [
                'baslik' => '6. Yapı İşleri, Yangın ve Çalışma Süreleri Kritik Rakamlar',
                'kategori' => 'Saha Ölçüleri',
                'maddeler' => [
                    'Yapı İşleri Bildirimi (Ek-3): İşin süresi 30 iş gününden fazla + devamlı 20\'den fazla çalışan veya 500 yevmiye',
                    'Korkuluk Ölçüleri: Üst korkuluk ≥ 100 cm · Topuk levhası ≥ 15 cm · Ara açıklık ≤ 47 cm',
                    'Seyyar Yangın Tüpü: Az Tehlikeli her 500 m² · Tehlikeli/Çok Tehlikeli her 250 m² için 1 adet 6 kg KKT (Yürüme ≤ 25 m, Asma yüksekliği = 90 cm)',
                    'İş Kazası Bildirimi: İşveren 3 iş günü · Hastane/Sağlık sunucusu 10 gün içinde SGK\'ya',
                    'Gece Çalışması: En fazla 7,5 saat (Turizm, özel güvenlik ve sağlık hariç; gebe/emziren ve 18 yaş altı çalıştırılamaz)',
                ],
            ],
        ];
    }

    public static function hapNotlarPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.isg-hap-notlar', [
            'notlar' => static::hapNotlar(),
            'sorular' => static::sorular(),
            'tarih' => now()->format('d.m.Y'),
        ])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), 'isg-uzmanlik-ve-saha-hap-notlar.pdf');
    }
}
