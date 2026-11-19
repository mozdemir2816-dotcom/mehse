<?php

namespace App\Support;

/**
 * 26 Haneli SGK İşyeri Sicil Numarası Görsel Çözümleyici ve Tehlike Sınıfına Göre
 * 13 Satırlık Yasal İSG Yükümlülükler Matrisi (isgpratik "Tehlike Sınıfını Bul" aracı).
 *
 * SGK 26 haneli işyeri sicil numarası yapısı:
 * - 1. hane (1): Mahiyet kodu (1: Kamu/Resmi, 2: Özel, 3: Geçici/Resmi, 4: Geçici/Özel)
 * - 2-5. hane (4): İşkolu kodu (NACE Rev.2 ilk 4 hanesi)
 * - 6-7. hane (2): Yeni ünite kodu
 * - 8-9. hane (2): Eski ünite kodu
 * - 10-16. hane (7): İşyeri sıra numarası
 * - 17-19. hane (3): İl kodu (ör. 034 = İstanbul, 006 = Ankara)
 * - 20-21. hane (2): İlçe kodu
 * - 22-23. hane (2): Kontrol numarası
 * - 24-26. hane (3): Alt işveren / aracı kodu (000 = Asıl işveren)
 */
class SgkSicilCozumleyici
{
    public const ORNEK_SICIL_NO = '2 4120 01 01 1234567 034 01 85 000';

    public const MAHIYET_KODLARI = [
        '1' => '1 — Kamu / Devamlı İşyeri',
        '2' => '2 — Özel Sektör / Devamlı İşyeri',
        '3' => '3 — Kamu / Geçici veya Mevsimlik İşyeri',
        '4' => '4 — Özel Sektör / Geçici veya Mevsimlik İşyeri',
    ];

    /**
     * 26 haneli SGK işyeri sicil numarasını 9 bileşenine ayırır.
     *
     * @return array{
     *     gecerli: bool,
     *     hata: ?string,
     *     temiz: string,
     *     nace4: ?string,
     *     parcalar: array<int, array{etiket: string, deger: string, aciklama: string, vurgu?: bool}>
     * }
     */
    public static function cozumle(string $girdi): array
    {
        $rakamlar = preg_replace('/\D/', '', $girdi) ?? '';

        if ($rakamlar === '') {
            return ['gecerli' => false, 'hata' => null, 'temiz' => '', 'nace4' => null, 'parcalar' => []];
        }

        if ( strlen($rakamlar) !== 26) {
            return [
                'gecerli' => false,
                'hata' => 'SGK işyeri sicil numarası 26 rakamdan oluşmalıdır (girilen: '.strlen($rakamlar).' hane).',
                'temiz' => $rakamlar,
                'nace4' => null,
                'parcalar' => [],
            ];
        }

        $mahiyet = substr($rakamlar, 0, 1);
        $iskolu = substr($rakamlar, 1, 4);
        $yeniUnite = substr($rakamlar, 5, 2);
        $eskiUnite = substr($rakamlar, 7, 2);
        $isyeriSira = substr($rakamlar, 9, 7);
        $ilKodu = substr($rakamlar, 16, 3);
        $ilceKodu = substr($rakamlar, 19, 2);
        $kontrolNo = substr($rakamlar, 21, 2);
        $araciKodu = substr($rakamlar, 23, 3);

        $naceFormatli = substr($iskolu, 0, 2).'.'.substr($iskolu, 2, 2);

        return [
            'gecerli' => true,
            'hata' => null,
            'temiz' => $rakamlar,
            'nace4' => $iskolu,
            'parcalar' => [
                [
                    'etiket' => '1. Mahiyet',
                    'deger' => $mahiyet,
                    'aciklama' => self::MAHIYET_KODLARI[$mahiyet] ?? 'Özel / Resmi İşyeri',
                ],
                [
                    'etiket' => '2. İşkolu (NACE 4 Hane)',
                    'deger' => $naceFormatli,
                    'aciklama' => 'NACE Rev.2 ilk 4 hane (son 2 haneyi aşağıdan seçin)',
                    'vurgu' => true,
                ],
                [
                    'etiket' => '3. Yeni Ünite',
                    'deger' => $yeniUnite,
                    'aciklama' => 'SGK yeni ünite kodu',
                ],
                [
                    'etiket' => '4. Eski Ünite',
                    'deger' => $eskiUnite,
                    'aciklama' => 'SGK eski ünite kodu',
                ],
                [
                    'etiket' => '5. İşyeri Sıra No',
                    'deger' => $isyeriSira,
                    'aciklama' => 'SGK dosya tescil sıra no',
                ],
                [
                    'etiket' => '6. İl Kodu',
                    'deger' => $ilKodu,
                    'aciklama' => 'Plaka kodu ('.ltrim($ilKodu, '0').')',
                ],
                [
                    'etiket' => '7. İlçe Kodu',
                    'deger' => $ilceKodu,
                    'aciklama' => 'SGK merkez/ilçe kodu',
                ],
                [
                    'etiket' => '8. Kontrol No',
                    'deger' => $kontrolNo,
                    'aciklama' => 'Doğrulama hanesi',
                ],
                [
                    'etiket' => '9. Alt İşveren / Aracı',
                    'deger' => $araciKodu,
                    'aciklama' => $araciKodu === '000' ? 'Asıl İşveren (000)' : 'Alt İşveren / Taşeron ('.$araciKodu.')',
                ],
            ],
        ];
    }

    /**
     * Tehlike sınıfına göre 13 satırlık yasal İSG yükümlülükleri karşılaştırma matrisi.
     *
     * @return array<int, array{baslik: string, dayanak: string, az_tehlikeli: string, tehlikeli: string, cok_tehlikeli: string}>
     */
    public static function yukumlulukMatrisi(): array
    {
        return [
            [
                'baslik' => '1. İş Güvenliği Uzmanı (İGU) Süresi',
                'dayanak' => 'İGU Yönetmeliği m.12',
                'az_tehlikeli' => 'Çalışan başına ayda en az 10 dk (1000 çalışana 1 tam gün)',
                'tehlikeli' => 'Çalışan başına ayda en az 20 dk (500 çalışana 1 tam gün)',
                'cok_tehlikeli' => 'Çalışan başına ayda en az 40 dk (250 çalışana 1 tam gün)',
            ],
            [
                'baslik' => '2. İşyeri Hekimi Görevlendirme Süresi',
                'dayanak' => 'İşyeri Hekimi ve DSP Yön. m.12',
                'az_tehlikeli' => 'Çalışan başına ayda en az 5 dk (2000 çalışana 1 tam gün)',
                'tehlikeli' => 'Çalışan başına ayda en az 10 dk (1000 çalışana 1 tam gün)',
                'cok_tehlikeli' => 'Çalışan başına ayda en az 15 dk (750 çalışana 1 tam gün)',
            ],
            [
                'baslik' => '3. Diğer Sağlık Personeli (DSP) Süresi',
                'dayanak' => 'İşyeri Hekimi ve DSP Yön. m.19',
                'az_tehlikeli' => 'Zorunlu değil (İsteğe bağlı)',
                'tehlikeli' => 'Zorunlu değil (İsteğe bağlı)',
                'cok_tehlikeli' => '10–49 çalışan: 10 dk/ay · 50–249: 15 dk/ay · 250+: 20 dk/ay (Tam zamanlı hekim varsa şart değil)',
            ],
            [
                'baslik' => '4. Görevlendirilebilecek İGU Sınıfı',
                'dayanak' => '6331 s.K. m.8',
                'az_tehlikeli' => 'A, B veya C Sınıfı İSG Uzmanı',
                'tehlikeli' => 'A veya B Sınıfı İSG Uzmanı',
                'cok_tehlikeli' => 'Yalnız A Sınıfı (veya sektörel/geçici yetkili B Sınıfı)',
            ],
            [
                'baslik' => '5. Risk Değerlendirmesi Yenileme Periyodu',
                'dayanak' => 'Risk Değerlendirmesi Yön. m.12',
                'az_tehlikeli' => 'En geç 6 yılda bir',
                'tehlikeli' => 'En geç 4 yılda bir',
                'cok_tehlikeli' => 'En geç 2 yılda bir',
            ],
            [
                'baslik' => '6. Acil Durum Planı Yenileme Periyodu',
                'dayanak' => 'Acil Durumlar Yön. m.14',
                'az_tehlikeli' => 'En geç 6 yılda bir (Tatbikat: yılda en az 1)',
                'tehlikeli' => 'En geç 4 yılda bir (Tatbikat: yılda en az 1)',
                'cok_tehlikeli' => 'En geç 2 yılda bir (Tatbikat: yılda en az 1)',
            ],
            [
                'baslik' => '7. Periyodik Sağlık Muayenesi (EK-2)',
                'dayanak' => '6331 s.K. m.15',
                'az_tehlikeli' => 'En geç 5 yılda bir',
                'tehlikeli' => 'En geç 3 yılda bir',
                'cok_tehlikeli' => 'En geç yılda 1 defa (Özel politika: 6 ayda 1)',
            ],
            [
                'baslik' => '8. Temel İSG Eğitimi Süresi & Tekrarı',
                'dayanak' => 'Çalışanların İSG Eğitimleri Yön. m.6',
                'az_tehlikeli' => 'En az 8 saat — 3 yılda bir tekrar',
                'tehlikeli' => 'En az 12 saat — 2 yılda bir tekrar',
                'cok_tehlikeli' => 'En az 16 saat — Yılda bir tekrar',
            ],
            [
                'baslik' => '9. Sertifikalı İlk Yardımcı Kontenjanı',
                'dayanak' => 'İlk Yardım Yönetmeliği m.19',
                'az_tehlikeli' => 'Her 20 çalışana 1 ilk yardımcı',
                'tehlikeli' => 'Her 15 çalışana 1 ilk yardımcı',
                'cok_tehlikeli' => 'Her 10 çalışana 1 ilk yardımcı',
            ],
            [
                'baslik' => '10. Acil Durum Destek Elemanı (Söndürme/Kurtarma/Koruma)',
                'dayanak' => 'Acil Durumlar Yön. m.11',
                'az_tehlikeli' => 'Her 50 çalışana kadar her ekipten 1\'er kişi',
                'tehlikeli' => 'Her 40 çalışana kadar her ekipten 1\'er kişi',
                'cok_tehlikeli' => 'Her 30 çalışana kadar her ekipten 1\'er kişi',
            ],
            [
                'baslik' => '11. İSG Kurulu Toplantı Periyodu (50+ Çalışan)',
                'dayanak' => 'İSG Kurulları Yön. m.9',
                'az_tehlikeli' => 'Ayda 1 (Kurul kararıyla 3 ayda 1 yapılabilir)',
                'tehlikeli' => 'Ayda 1 (Kurul kararıyla 2 ayda 1 yapılabilir)',
                'cok_tehlikeli' => 'En az ayda 1 defa',
            ],
            [
                'baslik' => '12. İşverenin İSG Hizmetini Kendisinin Üstlenmesi',
                'dayanak' => '6331 s.K. m.6 / İşveren Üstlenme Yön.',
                'az_tehlikeli' => '50\'den az çalışanı varsa eğitim alarak üstlenebilir (EK-2 muayene hariç)',
                'tehlikeli' => 'Üstlenemez (İGU ve İşyeri Hekimi zorunlu)',
                'cok_tehlikeli' => 'Üstlenemez (İGU ve İşyeri Hekimi zorunlu)',
            ],
            [
                'baslik' => '13. Mesleki Eğitim / MYK Belgesi Zorunluluğu',
                'dayanak' => '6331 s.K. m.17/3 & 5544 s.K.',
                'az_tehlikeli' => 'Genel mesleki eğitim (MYK kapsamındaki meslekler hariç zorunlu değil)',
                'tehlikeli' => 'Mesleki Eğitim Belgesi veya MYK Mesleki Yeterlilik Belgesi zorunlu',
                'cok_tehlikeli' => 'Mesleki Eğitim Belgesi veya MYK Mesleki Yeterlilik Belgesi zorunlu',
            ],
        ];
    }
}
