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
            'kapanis_tarihi' => $durum === 'kapandi' ? static::tarih($m['kapanis_tarihi'] ?? null) : null,
            'kapanis_notu' => static::metin($m['kapanis_notu'] ?? null),
        ];
    }

    /** @return array<string, mixed> SahaBulgusu alanları */
    public static function gozlemMaddesinden(array $m): array
    {
        $oncelik = self::DERECE_ONCELIK[(int) ($m['risk_derecesi'] ?? 3)] ?? 'orta';
        [$olasilik, $siddet] = SahaBulgusu::onceliktenRisk($oncelik);

        return [
            'uygunsuzluk' => trim((string) ($m['tespit'] ?? '')),
            'aksiyon' => static::metin($m['oneriler_metni'] ?? ($m['oneri'] ?? null)),
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
            'kaynak' => in_array($m['kaynak'] ?? null, ['foto', 'metin', 'ai'], true) ? 'ai' : 'manuel',
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
     * Bulgu → DÖF madde dizisi (pdf.dof-raporu ve DÖF Oluştur listesi bu
     * anahtarları bekler). bulgu_id ile kaynağa geri bağlanır.
     *
     * @return array<string, mixed>
     */
    public static function dofMaddesi(SahaBulgusu $b): array
    {
        return array_filter([
            'bulgu_id' => $b->id,
            'tespit' => $b->uygunsuzluk,
            'oncelik' => $b->oncelikAnahtari(),
            'oneri' => $b->aksiyon,
            'sorumlu' => $b->sorumlu,
            'termin' => $b->termin?->toDateString(),
            'durum' => array_search($b->durum, self::DOF_DURUM, true) ?: 'acik',
            'foto_yolu' => ($b->fotograflar ?? [])[0] ?? null,
            'kok_neden' => $b->kok_neden,
            'duzeltici' => $b->duzeltici,
            'onleyici' => $b->onleyici,
            'kapanis_tarihi' => $b->kapanis_tarihi?->toDateString(),
            'kapanis_notu' => $b->kapanis_notu,
        ], fn ($v) => $v !== null && $v !== '');
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
