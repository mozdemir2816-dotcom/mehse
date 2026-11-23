<?php

namespace App\Support;

use App\Models\Firma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Geçici Görevlendirme & Dış Saha Görev Emri PDF Üreticisi —
 * 4 yasal şablon (Şantiye/Dış Saha, Tesisler Arası, Geçici İş İlişkisi 4857 m.7, Teknik Servis).
 */
class GeciciGorevlendirmeUretici
{
    /** @var array<string, array{ad: string, dayanak: string, aciklama: string, metin: string, taahhut: array<int, string>}> */
    public const SABLONLAR = [
        'santiye' => [
            'ad' => 'Şantiye & Dış Saha Görevlendirmesi',
            'dayanak' => '6331 Sayılı İSG Kanunu & Yapı İşlerinde İSG Yönetmeliği',
            'aciklama' => 'İnşaat, şantiye, montaj ve açık saha çalışmaları için İSG talimatlı görev emri.',
            'metin' => 'Aşağıda kimlik bilgileri yer alan çalışanımız, belirtilen tarihler arasında belirtilen şantiye / dış saha lokasyonunda görevlendirilmiştir. Görevlendirme süresince 6331 sayılı İş Sağlığı ve Güvenliği Kanunu, Yapı İşlerinde İSG Yönetmeliği ve saha İSG kurallarına eksiksiz uyulması zorunludur.',
            'taahhut' => [
                'Görevlendirildiğim şantiye/saha için gerekli tüm KKD\'leri (baret, reflektörlü yelek, çelik burunlu ayakkabı, emniyet kemeri vb.) zimmetli olarak teslim aldığımı ve eksiksiz kullanacağımı,',
                'Yüksekte çalışma, sıcak iş, kazı veya elektrik işlerinde yazılı İş İzni (PTW) alınmadan çalışmaya başlamayacağımı,',
                'Saha girişinde işbaşı oryantasyon ve tehlike bilgilendirmesine katılacağımı, güvensiz durumları derhal saha amirine ve İSG uzmanına bildireceğimi beyan ve taahhüt ederim.',
            ],
        ],
        'tesisler_arasi' => [
            'ad' => 'Şirket İçi / Tesisler & Şubeler Arası Görevlendirme',
            'dayanak' => '4857 Sayılı İş Kanunu & 6331 Sayılı İSG Kanunu m.16-17',
            'aciklama' => 'Aynı işverene bağlı farklı şube, fabrika veya depo lokasyonlarına geçici görevlendirme.',
            'metin' => 'Aşağıda bilgileri bulunan çalışanımız, işletme gereksinimleri doğrultusunda belirtilen tarih aralığında işyerimize bağlı diğer tesis / şube lokasyonunda geçici olarak görevlendirilmiştir. Görev süresince ulaşım, yemek ve harcırah koşulları aşağıda belirtilen esaslara göre yürütülür.',
            'taahhut' => [
                'Görevlendirildiğim tesis/şubenin acil durum planı, toplanma alanı ve dahili çalışma kurallarına uyacağımı,',
                'Yetkim ve eğitimim bulunmayan makine, tezgâh veya iş ekipmanlarına müdahale etmeyeceğimi,',
                'Görevlendirme süresince iş sağlığı ve güvenliği kurallarına aykırı davranışta bulunmayacağımı beyan ve taahhüt ederim.',
            ],
        ],
        'gecici_is_iliskisi' => [
            'ad' => 'Geçici İş İlişkisi Görevlendirmesi (4857 m.7 — İşçi Rızalı)',
            'dayanak' => '4857 Sayılı İş Kanunu m.7 & 6331 Sayılı İSG Kanunu m.16/3, m.17/5',
            'aciklama' => 'Holding bünyesi veya aynı şirketler topluluğu içinde işçinin yazılı rızasıyla geçici devir.',
            'metin' => '4857 sayılı İş Kanunu\'nun 7. maddesi uyarınca, çalışanın yazılı rızası alınmak suretiyle aşağıda belirtilen işyerinde ve tarih aralığında geçici iş ilişkisi kapsamında görevlendirme yapılmıştır. Görevlendirme süresince iş sözleşmesi mevcut işveren nezdinde devam etmekte olup, iş sağlığı ve güvenliği eğitimleri ile gözetimi her iki işverenin koordinasyonunda yürütülür.',
            'taahhut' => [
                '4857 sayılı İş Kanunu m.7 kapsamında belirtilen tarihler arasında geçici görevlendirmeye kendi özgür irademle yazılı rıza gösterdiğimi,',
                'Geçici görev yaptığım işyerinin İSG talimatlarına, acil durum kurallarına ve KKD zorunluluklarına tam olarak uyacağımı,',
                'Görev değişikliği veya yeni bir tehlike durumunda amirime ve İSG uzmanına bilgi vereceğimi beyan ve taahhüt ederim.',
            ],
        ],
        'teknik_servis' => [
            'ad' => 'Teknik Servis, Bakım & Saha Denetimi Görev Emri',
            'dayanak' => '6331 Sayılı İSG Kanunu & İş Ekipmanları Yönetmeliği',
            'aciklama' => 'Müşteri sahasında bakım-onarım, devreye alma, teknik servis ve denetim görevleri için.',
            'metin' => 'Aşağıda kimlik ve görev bilgileri yer alan personelimiz, belirtilen müşteri tesisi / çalışma sahasında teknik servis, bakım-onarım, montaj veya denetim faaliyetini yürütmek üzere görevlendirilmiştir. Çalışma öncesinde enerji kesme/kilitleme (LOTO) ve müşteri sahası giriş izin prosedürleri uygulanacaktır.',
            'taahhut' => [
                'Bakım, onarım ve servis işlemlerine başlamadan önce enerji izolasyonu ve kilitleme-etiketleme (LOTO) kurallarını uygulayacağımı,',
                'Müşteri sahasının İSG, ziyaretçi giriş ve iş izni kurallarına uyacağımı,',
                'İşe uygun kalibrasyonlu/sağlam el aletleri ve KKD kullanacağımı, işlem sonrası çalışma alanını güvenli halde teslim edeceğimi beyan ve taahhüt ederim.',
            ],
        ],
    ];

    public static function tcMaskele(?string $tc, bool $maskele): string
    {
        $temiz = trim((string) $tc);
        if ($temiz === '') {
            return '—';
        }
        if (! $maskele || mb_strlen($temiz) < 5) {
            return $temiz;
        }

        return mb_substr($temiz, 0, 3).str_repeat('*', max(1, mb_strlen($temiz) - 5)).mb_substr($temiz, -2);
    }

    /**
     * @param  array{
     *   sablon: string,
     *   gorev_yeri: string,
     *   gorev_konusu: string,
     *   baslangic_tarihi: string,
     *   bitis_tarihi?: ?string,
     *   harcirah_notu?: ?string,
     *   arac_plaka?: ?string,
     *   belge_no?: ?string,
     *   tc_maskele?: bool,
     *   canli_imza?: ?string,
     *   calisanlar: array<int, array{ad_soyad: string, tc?: ?string, gorev?: ?string}>,
     * }  $veri
     */
    public static function pdf(Firma $firma, array $veri): StreamedResponse
    {
        $firma->loadMissing(['igu', 'isyeriHekimi']);
        $sablonAnahtar = array_key_exists($veri['sablon'] ?? '', self::SABLONLAR) ? $veri['sablon'] : 'santiye';
        $sablon = self::SABLONLAR[$sablonAnahtar];

        $baslangic = filled($veri['baslangic_tarihi'] ?? null)
            ? Carbon::parse($veri['baslangic_tarihi'])->format('d.m.Y')
            : now()->format('d.m.Y');
        $bitis = filled($veri['bitis_tarihi'] ?? null)
            ? Carbon::parse($veri['bitis_tarihi'])->format('d.m.Y')
            : 'İşin bitimine kadar';

        $pdf = Pdf::loadView('pdf.gecici-gorevlendirme', [
            'firma' => $firma,
            'sablon' => $sablon,
            'sablonAnahtar' => $sablonAnahtar,
            'belgeNo' => ($veri['belge_no'] ?? null) ?: ('GG-'.now()->format('Y-md-Hi')),
            'tarih' => now()->format('d.m.Y'),
            'gorevYeri' => $veri['gorev_yeri'],
            'gorevKonusu' => $veri['gorev_konusu'],
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'harcirahNotu' => $veri['harcirah_notu'] ?? null,
            'aracPlaka' => $veri['arac_plaka'] ?? null,
            'tcMaskele' => (bool) ($veri['tc_maskele'] ?? false),
            'canliImza' => $veri['canli_imza'] ?? null,
            'calisanlar' => array_values($veri['calisanlar'] ?? []),
        ])->setPaper('a4');

        $ad = 'gecici-gorevlendirme-'.Str::slug($firma->unvan).'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $ad);
    }
}
