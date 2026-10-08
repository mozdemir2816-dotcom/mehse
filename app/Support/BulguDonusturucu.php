<?php

namespace App\Support;

use App\Models\SahaBulgusu;
use Illuminate\Support\Carbon;

/**
 * Saha kontrolleri "tek bulgu, çok çıktı" (08.10.2026): eski madde yapıları ↔
 * ortak SahaBulgusu alanları. Kayıt üretmez, yalnız dizi çevirir:
 *
 *  - dofMaddesinden()     DofRaporu.maddeler / DofTabloOkuyucu maddesi
 *  - gozlemMaddesinden()  SahaAnalizi.bulgular (Fine-Kinney + yasal gerekçe)
 *  - tespitOneriMaddesinden()  TespitOneriDefteri.maddeler
 *  - dofMaddesi()         bulgu → DÖF PDF/ekranının beklediği madde dizisi
 *
 * Risk tek yöntemle: 5×5. Yalnız önceliği bilinen maddeye temsilî olasılık ×
 * şiddet verilir ve öncelik ayrıca saklanır (bilgi kaybolmaz).
 */
class BulguDonusturucu
{
    /** DÖF madde durumu → bulgu durumu. */
    private const DOF_DURUM = ['acik' => 'acik', 'devam_ediyor' => 'devam_ediyor', 'ertelendi' => 'ertelendi', 'tamamlandi' => 'kapandi'];

    /** Gözlem raporu risk derecesi (1 Çok Yüksek … 4 Düşük) → öncelik. */
    private const DERECE_ONCELIK = [1 => 'kritik', 2 => 'yuksek', 3 => 'orta', 4 => 'dusuk'];

    /** @return array<string, mixed> SahaBulgusu alanları */
    public static function dofMaddesinden(array $m): array
    {
        $oncelik = static::oncelik($m['oncelik'] ?? null);
        [$olasilik, $siddet] = SahaBulgusu::onceliktenRisk($oncelik);
        $durum = self::DOF_DURUM[$m['durum'] ?? 'acik'] ?? 'acik';

        return [
            'uygunsuzluk' => trim((string) ($m['tespit'] ?? '')),
            'aksiyon' => static::metin($m['oneri'] ?? null),
            'oncelik' => $oncelik,
            'olasilik' => $olasilik,
            'siddet' => $siddet,
            'sorumlu' => static::metin($m['sorumlu'] ?? null),
            'termin' => static::tarih($m['termin'] ?? null),
            'durum' => $durum,
            'fotograflar' => array_values(array_filter([$m['foto_yolu'] ?? null])),
            'kok_neden' => static::metin($m['kok_neden'] ?? null),
            'duzeltici' => static::metin($m['duzeltici'] ?? null),
            'onleyici' => static::metin($m['onleyici'] ?? null),
            // Ziyaret Modu "kapatma_*", diğerleri "kapanis_*" yazar
            'kapanis_tarihi' => $durum === 'kapandi' ? static::tarih($m['kapanis_tarihi'] ?? ($m['kapatma_tarihi'] ?? null)) : null,
            'kapanis_notu' => static::metin($m['kapanis_notu'] ?? ($m['kapatma_notu'] ?? null)),
        ];
    }

    /** DÖF madde durumu → bulgu durumu. */
    public static function dofDurumundan(?string $durum): string
    {
        return self::DOF_DURUM[$durum ?? 'acik'] ?? 'acik';
    }

    /** Bulgu durumu → DÖF madde durumu. */
    public static function dofDurumu(string $bulguDurumu): string
    {
        return array_search($bulguDurumu, self::DOF_DURUM, true) ?: 'acik';
    }

    /** @return array<string, mixed> SahaBulgusu alanları */
    public static function gozlemMaddesinden(array $m): array
    {
        $oncelik = self::DERECE_ONCELIK[(int) ($m['risk_derecesi'] ?? 3)] ?? 'orta';
        [$olasilik, $siddet] = SahaBulgusu::onceliktenRisk($oncelik);

        // Sayfa durumunda "oneriler_metni", kayıtlı raporda "oneriler" dizisi
        $oneriler = $m['oneriler_metni'] ?? (is_array($m['oneriler'] ?? null) ? implode("\n", $m['oneriler']) : ($m['oneri'] ?? null));

        return [
            'uygunsuzluk' => trim((string) ($m['tespit'] ?? '')),
            'aksiyon' => static::metin($oneriler),
            'yasal_gerekce' => static::metin($m['yasal_gerekce'] ?? null),
            'bolum' => static::metin($m['bina_bolge'] ?? null),
            'kategori' => static::metin($m['kategori'] ?? null),
            'oncelik' => $oncelik,
            'olasilik' => $olasilik,
            'siddet' => $siddet,
            'sorumlu' => static::metin($m['dof_sorumlu'] ?? null),
            'termin' => static::tarih($m['dof_termin'] ?? null),
            'durum' => 'acik',
            'fotograflar' => array_values(array_filter([$m['foto_yolu'] ?? null])),
            'kaynak' => in_array($m['kaynak'] ?? null, ['foto', 'aciklama', 'ai'], true) ? 'ai' : 'manuel',
        ];
    }

    /** @return array<string, mixed> SahaBulgusu alanları */
    public static function tespitOneriMaddesinden(array $m): array
    {
        $oncelik = static::oncelik($m['oncelik'] ?? null);
        [$olasilik, $siddet] = SahaBulgusu::onceliktenRisk($oncelik);

        return [
            'uygunsuzluk' => trim((string) ($m['tespit'] ?? '')),
            'aksiyon' => static::metin($m['oneri'] ?? null),
            'yasal_gerekce' => static::metin($m['dayanak'] ?? null),
            'oncelik' => $oncelik,
            'olasilik' => $olasilik,
            'siddet' => $siddet,
            'durum' => 'acik',
            'fotograflar' => array_values(array_filter([$m['foto_yolu'] ?? null])),
        ];
    }

    /**
     * Saha Denetimi "uygun değil" maddesi (SahaDenetimi.cevaplar) → bulgu.
     * Kritik madde "kritik", diğerleri "orta"; aksiyonu uzman bulguda yazar.
     *
     * @return array<string, mixed> SahaBulgusu alanları
     */
    public static function denetimMaddesinden(array $m): array
    {
        $oncelik = ($m['kritik'] ?? false) ? 'kritik' : 'orta';
        [$olasilik, $siddet] = SahaBulgusu::onceliktenRisk($oncelik);
        $aciklama = trim((string) ($m['aciklama'] ?? ''));

        return [
            'uygunsuzluk' => trim((string) ($m['ifade'] ?? '')).($aciklama !== '' ? ' — '.$aciklama : ''),
            'kategori' => static::metin($m['kategori_ad'] ?? null),
            'oncelik' => $oncelik,
            'olasilik' => $olasilik,
            'siddet' => $siddet,
            'durum' => 'acik',
            'fotograflar' => array_values(array_filter([$m['foto_yolu'] ?? null])),
        ];
    }

    /**
     * Bulgu → DÖF madde dizisi (pdf.dof-raporu ve DÖF Oluştur listesi bu
     * anahtarları bekler). bulgu_id ile kaynağa geri bağlanır.
     *
     * @return array<string, mixed>
     */
    public static function dofMaddesi(SahaBulgusu $b, bool $bolumOnEki = false): array
    {
        // DÖF ekranı ve PDF'i çekirdek anahtarların hep var olmasını bekler (boşsa null);
        // isteğe bağlı olanlar yalnız doluysa eklenir.
        return [
            'bulgu_id' => $b->id,
            'tespit' => trim(($bolumOnEki && $b->bolum ? "[{$b->bolum}] " : '').$b->uygunsuzluk),
            'oncelik' => $b->oncelikAnahtari(),
            'oneri' => $b->aksiyon,
            'sorumlu' => $b->sorumlu,
            'termin' => $b->termin?->toDateString(),
            'durum' => array_search($b->durum, self::DOF_DURUM, true) ?: 'acik',
            'foto_yolu' => ($b->fotograflar ?? [])[0] ?? null,
        ] + array_filter([
            'kok_neden' => $b->kok_neden,
            'duzeltici' => $b->duzeltici,
            'onleyici' => $b->onleyici,
            'kapanis_tarihi' => $b->kapanis_tarihi?->toDateString(),
            'kapanis_notu' => $b->kapanis_notu,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Bulgu → Saha Gözlem Raporu madde dizisi (AiSahaAnalizi bosBulgu alanları).
     * Bulgu zaten değerlendirilmiş sayıldığı için "onaylandi" gelir; Fine-Kinney
     * alanları boş, risk derecesi öncelikten.
     *
     * @return array<string, mixed>
     */
    public static function gozlemMaddesi(SahaBulgusu $b): array
    {
        return [
            'bulgu_id' => $b->id,
            'foto_yolu' => ($b->fotograflar ?? [])[0] ?? null,
            'bina_bolge' => $b->bolum ?: $b->gozlem_konumu,
            'kategori' => $b->kategori,
            'tespit' => $b->uygunsuzluk,
            'oneriler_metni' => (string) $b->aksiyon,
            'yasal_gerekce' => $b->yasal_gerekce,
            'olasilik' => null,
            'frekans' => null,
            'siddet' => null,
            'risk_derecesi' => array_flip(self::DERECE_ONCELIK)[$b->oncelikAnahtari()] ?? 3,
            'durum' => 'onaylandi',
            'kaynak' => 'bulgu',
            'dof_acilacak' => false,
            'dof_sorumlu_tipi' => 'kendim',
            'dof_sorumlu' => null,
            'dof_termin' => $b->termin?->toDateString(),
            'dof_hedef_skor' => null,
        ];
    }

    /** Bulgu → Tespit ve Öneri Defteri madde dizisi. @return array<string, mixed> */
    public static function tespitOneriMaddesi(SahaBulgusu $b): array
    {
        return [
            'bulgu_id' => $b->id,
            'tespit' => $b->uygunsuzluk,
            'oneri' => (string) $b->aksiyon,
            'dayanak' => $b->yasal_gerekce,
            'oncelik' => $b->oncelikAnahtari(),
            'foto_yolu' => ($b->fotograflar ?? [])[0] ?? null,
        ];
    }

    private static function oncelik(mixed $deger): string
    {
        $deger = (string) $deger;

        return array_key_exists($deger, SahaBulgusu::ONCELIKLER)
            ? $deger
            : DofTabloOkuyucu::oncelik($deger);   // "Yüksek", "Çok yüksek" gibi metin
    }

    private static function metin(mixed $deger): ?string
    {
        $deger = trim((string) $deger);

        return $deger === '' ? null : $deger;
    }

    private static function tarih(mixed $deger): ?string
    {
        if (blank($deger)) {
            return null;
        }

        try {
            return Carbon::parse($deger)->toDateString();
        } catch (\Throwable) {
            return DofTabloOkuyucu::tarih((string) $deger);
        }
    }
}
