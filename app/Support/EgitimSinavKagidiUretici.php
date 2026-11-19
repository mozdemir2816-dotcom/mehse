<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (m.15)
 * kapsamında Eğitim Sonu Değerlendirme Sınav Kâğıdı (Ön Test / Son Test) ve Cevap Anahtarı PDF üretici.
 */
class EgitimSinavKagidiUretici
{
    /**
     * @return array<int, array{soru: string, siklar: array{A: string, B: string, C: string, D: string}, dogru: string}>
     */
    public static function sorular(): array
    {
        return [
            [
                'soru' => '6331 sayılı İş Sağlığı ve Güvenliği Kanunu\'na göre ciddi ve yakın bir tehlike ile karşı karşıya kalan çalışan ne yapmalıdır?',
                'siklar' => [
                    'A' => 'Tehlikeyi önemsemeden çalışmaya devam etmelidir',
                    'B' => 'Kurula veya işverene başvurarak durumun tespit edilmesini ve tedbir alınmasını talep etmeli, tedbir alınıncaya kadar çalışmaktan kaçınma hakkını kullanmalıdır',
                    'C' => 'Kimseye haber vermeden işyerini terk etmelidir',
                    'D' => 'Tehlikeli durumu kendi imkânlarıyla tamir etmeye çalışmalıdır',
                ],
                'dogru' => 'B',
            ],
            [
                'soru' => 'İşyerinde çalışana zarar verme potansiyeli olup ancak zarara veya yaralanmaya yol açmayan olaya ne ad verilir?',
                'siklar' => [
                    'A' => 'Meslek hastalığı',
                    'B' => 'İş kazası',
                    'C' => 'Ramak kala olay',
                    'D' => 'Periyodik kontrol',
                ],
                'dogru' => 'C',
            ],
            [
                'soru' => 'Kişisel Koruyucu Donanım (KKD) kullanımı ile ilgili aşağıdakilerden hangisi doğrudur?',
                'siklar' => [
                    'A' => 'Sadece denetim olduğu günlerde kullanılmalıdır',
                    'B' => 'Toplu korunma önlemlerinin riski tam önleyemediği durumlarda, işe uygun ve CE işaretli KKD talimatlara uygun olarak sürekli kullanılmalıdır',
                    'C' => 'Çalışan isterse KKD üzerinde delik açıp değişiklik yapabilir',
                    'D' => 'Yıpranan veya kırılan KKD yenisiyle değiştirilmeden kullanılmaya devam edilmelidir',
                ],
                'dogru' => 'B',
            ],
            [
                'soru' => 'Yangın anında yangın söndürme tüpü kullanılırken (P.A.S.S. kuralı) hortum nereye tutulmalıdır?',
                'siklar' => [
                    'A' => 'Alevlerin en üst noktasına',
                    'B' => 'Dumanın yoğun olduğu tavana',
                    'C' => 'Rüzgârı arkaya alarak alevin dibine (yanmakta olan maddenin tabanına)',
                    'D' => 'Rüzgâra karşı durarak alevin ortasına',
                ],
                'dogru' => 'C',
            ],
            [
                'soru' => 'Elle yük kaldırma ve taşıma işlerinde bel sağlığını korumak için aşağıdakilerden hangisi yapılmalıdır?',
                'siklar' => [
                    'A' => 'Dizler bükülmeden belden eğilerek yük kaldırılmalıdır',
                    'B' => 'Yük vücuttan mümkün olduğunca uzakta tutulmalıdır',
                    'C' => 'Dizler bükülerek çömelinmeli, sırt dik tutulmalı ve yük vücuda yakın tutularak bacak kuvvetiyle kaldırılmalıdır',
                    'D' => 'Yük kaldırılırken bel ani hareketle sağa-sola döndürülmelidir',
                ],
                'dogru' => 'C',
            ],
            [
                'soru' => 'İş ekipmanları ve makinelerle çalışırken aşağıdaki davranışlardan hangisi kesinlikle yasaktır?',
                'siklar' => [
                    'A' => 'Çalışmaya başlamadan önce acil durdurma butonunu kontrol etmek',
                    'B' => 'Makine koruyucu kapaklarını ve emniyet sensörlerini devre dışı bırakmak veya makine çalışırken bakım/temizlik yapmak',
                    'C' => 'Arızalı ekipmanı amire bildirmek',
                    'D' => 'Makine kullanım talimatına uymak',
                ],
                'dogru' => 'B',
            ],
            [
                'soru' => 'Elektrik kaynaklı iş kazalarını önlemek için aşağıdaki tedbirlerden hangisi doğrudur?',
                'siklar' => [
                    'A' => 'Islak zeminde ve ıslak elle elektrikli el aleti kullanmak',
                    'B' => 'Fişi prizden kablosundan çekerek çıkarmak',
                    'C' => 'Panoların önünde yalıtkan paspas bulundurmak, kaçak akım rölesi kullanmak ve hasarlı kabloları derhal kullanım dışı bırakmak',
                    'D' => 'Yetkisiz kişilerce elektrik panosuna müdahale etmek',
                ],
                'dogru' => 'C',
            ],
            [
                'soru' => 'İşyerinde acil durum (yangın, deprem, gaz kaçağı vb.) alarmı çaldığında çalışanların ilk yapması gereken nedir?',
                'siklar' => [
                    'A' => 'Asansörü kullanarak binadan inmeye çalışmak',
                    'B' => 'Panik yapmadan çalıştığı ekipmanı güvenli duruma getirip acil çıkış yönlendirmelerini takip ederek Acil Durum Toplanma Alanına gitmek',
                    'C' => 'Şahsi eşyalarını toplamak için soyunma odasına dönmek',
                    'D' => 'Olay yerine gidip kalabalık oluşturmak',
                ],
                'dogru' => 'B',
            ],
            [
                'soru' => 'Kimyasal maddelerle çalışılan alanlarda kimyasalın tehlikelerini, ilk yardım ve depolama kurallarını gösteren belgenin adı nedir?',
                'siklar' => [
                    'A' => 'Güvenlik Bilgi Formu (GBF / MSDS)',
                    'B' => 'Personel İzin Formu',
                    'C' => 'Vardiya Çizelgesi',
                    'D' => 'Yıllık Üretim Planı',
                ],
                'dogru' => 'A',
            ],
            [
                'soru' => 'İşyerinde bir iş kazası veya yaralanma meydana geldiğinde ne yapılmalıdır?',
                'siklar' => [
                    'A' => 'Küçük yaralanmalar gizlenmeli ve kimseye söylenmemelidir',
                    'B' => 'Olay yeri güvenliği sağlanmalı, yetkili ilkyardımcıya/112\'ye ve işveren/İSG birimine derhal bilgi verilmelidir',
                    'C' => 'Eğitimsiz kişilerce yaralıya rastgele müdahale edilmelidir',
                    'D' => 'Kaza ertesi hafta bildirilmelidir',
                ],
                'dogru' => 'B',
            ],
        ];
    }

    /**
     * @param  array<int, array{ad_soyad?: ?string, tc?: ?string, gorev?: ?string}>  $katilimcilar
     */
    public static function pdf(
        ?Firma $firma,
        string $egitimBasligi,
        ?string $tarih,
        array $katilimcilar = [],
        string $sinavTuru = 'Son Test (Değerlendirme Sınavı)',
    ): StreamedResponse {
        $liste = count($katilimcilar) > 0
            ? $katilimcilar
            : [['ad_soyad' => '', 'tc' => '', 'gorev' => '']];

        $pdf = Pdf::loadView('pdf.egitim-sinav-kagidi', [
            'firma' => $firma,
            'egitimBasligi' => $egitimBasligi,
            'tarih' => $tarih ?: now()->format('d.m.Y'),
            'sinavTuru' => $sinavTuru,
            'katilimcilar' => $liste,
            'sorular' => static::sorular(),
        ])->setPaper('a4');

        $ad = 'isg-egitim-sinav-kagidi-'.Str::slug($firma?->kisaEtiket() ?: 'bos').'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
