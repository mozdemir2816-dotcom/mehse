<?php

namespace App\Support;

/**
 * Ayarlar > Kontrol Merkezi kriterlerinin 4 ana kategoriye gruplanması ve hızlı seçim kümeleri
 * (30.10.2026). Kriter tanımı config('isg.kontrol_merkezi.kriterler') içinde kalır; burada yalnız
 * ekranda gösterim grubu ve "yalnızca yasal olanlar" kümesi tutulur.
 */
class KontrolKriterGruplari
{
    public const YASAL = 'Yasal Evraklar';

    public const SAHA = 'Saha ve Denetim';

    public const SAGLIK = 'Sağlık ve Olaylar';

    public const EGITIM = 'Eğitim ve Kurul';

    /** @var array<string, string> kriter anahtarı => grup */
    private const GRUP = [
        'risk_degerlendirmesi' => self::YASAL,
        'yillik_calisma_plani' => self::YASAL,
        'yillik_egitim_plani' => self::YASAL,
        'yillik_degerlendirme' => self::YASAL,
        'acil_durum_plani' => self::YASAL,
        'acil_durum_destek' => self::YASAL,
        'acil_durum_tatbikat' => self::YASAL,
        'igu_atamasi' => self::YASAL,
        'hekim_atamasi' => self::YASAL,
        'onayli_defter_nushalari' => self::YASAL,
        'periyodik_kontrol_raporu' => self::YASAL,
        'tespit_oneri' => self::SAHA,
        'saha_denetim_formu' => self::SAHA,
        'calisma_izin_formu' => self::SAHA,
        'diger_evrak' => self::SAHA,
        'ise_donus_muayenesi' => self::SAGLIK,
        'is_kazasi_bildirimi' => self::SAGLIK,
        'olay_ramak_kala_kaydi' => self::SAGLIK,
        'meslek_hastaligi_bildirimi' => self::SAGLIK,
        'saglik_raporu' => self::SAGLIK,
        'egitim_katilim_formu' => self::EGITIM,
        'kaza_sonrasi_isbasi_egitimi' => self::EGITIM,
        'isg_kurulu' => self::EGITIM,
        'calisan_temsilcisi' => self::EGITIM,
    ];

    /** @return array<int, string> */
    public static function gruplar(): array
    {
        return [self::YASAL, self::SAHA, self::SAGLIK, self::EGITIM];
    }

    public static function grup(string $anahtar): string
    {
        return self::GRUP[$anahtar] ?? self::SAHA;
    }

    /**
     * Kriterleri gruba göre sıralı verir (grup içinde config sırası korunur).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sirali(): array
    {
        $sira = array_flip(self::gruplar());

        return collect(config('isg.kontrol_merkezi.kriterler', []))
            ->sortBy(fn (array $k) => $sira[self::grup($k['anahtar'])] ?? 99)   // sortBy kararlı: grup içi sıra korunur
            ->values()
            ->all();
    }

    /** @return array<int, string> bir gruba ait kriter anahtarları */
    public static function grubunAnahtarlari(string $grup): array
    {
        return collect(config('isg.kontrol_merkezi.kriterler', []))
            ->filter(fn (array $k) => self::grup($k['anahtar']) === $grup)
            ->pluck('anahtar')->values()->all();
    }

    /** "Yalnızca yasal olanlar": her işyerinde zorunlu kayıtlar — "takip" bayrağı taşıyanlar ve "Diğer" hariç. */
    public static function yasalAnahtarlar(): array
    {
        return collect(config('isg.kontrol_merkezi.kriterler', []))
            ->reject(fn (array $k) => ($k['takip'] ?? false) || $k['anahtar'] === 'diger_evrak')
            ->pluck('anahtar')->values()->all();
    }

    /** @return array<int, string> */
    public static function tumAnahtarlar(): array
    {
        return array_column(config('isg.kontrol_merkezi.kriterler', []), 'anahtar');
    }
}
