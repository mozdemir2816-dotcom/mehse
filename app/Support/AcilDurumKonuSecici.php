<?php

namespace App\Support;

use App\Models\Firma;
use Illuminate\Support\Str;

/**
 * Acil Durum Eylem Planında yeni bir plan oluşturulurken, firmanın NACE koduna
 * ve tehlike sınıfına göre hangi acil durum konularının otomatik işaretleneceğini
 * belirler (`config/isg.php → acil_durum.konular[].kosul`). Firmaya uymayan konu
 * seçilmez; kullanıcı elle ekleyip çıkarabilir.
 */
class AcilDurumKonuSecici
{
    /** @return array<int, string> önceden seçilecek konu anahtarları */
    public static function firmaIcin(?Firma $firma): array
    {
        $nace = static::naceKok($firma?->nace_kodu);
        $tehlike = $firma?->tehlike_sinifi;

        return static::zorunluEkle(collect(config('isg.acil_durum.konular', []))
            ->filter(fn (array $konu) => static::uygunMu($konu['kosul'] ?? null, $nace, $tehlike))
            ->pluck('anahtar')
            ->all());
    }

    /** @return array<int, string> her planda bulunan, kaldırılamayan konular (yangın, iş kazası, elektrik). */
    public static function zorunlu(): array
    {
        return config('isg.acil_durum.zorunlu_konular', []);
    }

    /** Zorunlu konuları ekler; sıra config'teki konu sırasıdır. */
    public static function zorunluEkle(array $konular): array
    {
        $secili = array_unique([...$konular, ...static::zorunlu()]);

        return collect(config('isg.acil_durum.konular', []))->pluck('anahtar')
            ->filter(fn ($k) => in_array($k, $secili, true))->values()->all();
    }

    /**
     * "Risk Analizinden Otomatik Belirle" (kullanıcı 01.11.2026): firmanın risk değerlendirmesi
     * maddelerinde, iş ekipmanlarında ve kimyasal envanterinde geçen ifadelere göre eklenecek konular.
     * Kimyasal envanteri doluysa kimyasal dökülme ayrıca eklenir. Yalnız ekleme önerir; hiçbir konuyu kaldırmaz.
     *
     * @return array<int, string>
     */
    public static function riskAnalizindenOner(Firma $firma): array
    {
        $metin = static::firmaMetni($firma);
        $konular = $firma->kimyasalUrunler()->exists() ? ['kimyasal'] : [];

        foreach (config('isg.acil_durum.otomatik_konu_kurallari', []) as $kural) {
            if (static::geciyorMu($metin, $kural['ifadeler'])) {
                array_push($konular, ...$kural['konular']);
            }
        }

        // config sırasında, tekrarsız
        return collect(config('isg.acil_durum.konular', []))->pluck('anahtar')
            ->filter(fn ($k) => in_array($k, $konular, true))->values()->all();
    }

    /**
     * EK-3 KKD tablosunda basılacak faaliyet sütunları — firmanın verisinden. Firma hakkında hiç
     * veri yoksa null (tablo tüm sütunlarla basılır).
     *
     * @return array<int, string>|null
     */
    public static function kkdFaaliyetleri(Firma $firma): ?array
    {
        $metin = static::firmaMetni($firma);
        if (trim($metin) === '') {
            return null;
        }

        $kimyasalVar = $firma->kimyasalUrunler()->exists();

        return collect(config('isg.acil_durum.kkd_faaliyet_ifadeleri', []))
            ->filter(fn ($ifadeler, $faaliyet) => $ifadeler === null || static::geciyorMu($metin, $ifadeler)
                || ($faaliyet === 'KİMYASALLA ÇALIŞMA' && $kimyasalVar))
            ->keys()->values()->all();
    }

    /**
     * Risk tablosu satırları (anahtar => ad): yalnız konusu seçili olanlar (config risk_konusu).
     *
     * @return array<string, string>
     */
    public static function riskSatirlari(array $konular): array
    {
        $esleme = config('isg.acil_durum.risk_konusu', []);

        return collect(config('isg.acil_durum.risk_listesi', []))
            ->filter(fn ($ad, $anahtar) => in_array($esleme[$anahtar] ?? $anahtar, $konular, true))
            ->all();
    }

    /** Firmanın risk maddeleri + iş ekipmanları + kimyasal ürünleri, Türkçe küçük harf tek metin. */
    public static function firmaMetni(Firma $firma): string
    {
        $parcalar = \App\Models\RiskMaddesi::query()
            ->whereIn('risk_degerlendirmesi_id', $firma->riskDegerlendirmeleri()->select('id'))
            ->get(['bolum', 'faaliyet', 'tehlike', 'risk'])
            ->flatMap(fn ($m) => [$m->bolum, $m->faaliyet, $m->tehlike, $m->risk])
            ->merge($firma->isEkipmanlari()->pluck('ekipman_adi'))
            ->merge($firma->kimyasalUrunler()->pluck('urun_adi'))
            ->filter()->unique();

        return static::kucuk($parcalar->implode(' | '));
    }

    /** İfade bir sözcüğün BAŞINDA geçmeli ("pres" kompresörde eşleşmesin). */
    private static function geciyorMu(string $metin, array $ifadeler): bool
    {
        foreach ($ifadeler as $ifade) {
            if (preg_match('/(?<!\p{L})'.preg_quote(static::kucuk($ifade), '/').'/u', $metin)) {
                return true;
            }
        }

        return false;
    }

    private static function kucuk(string $metin): string
    {
        return mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], $metin), 'UTF-8');
    }

    /** Firma yokken kullanılacak genel varsayılan (koşulsuz konular). */
    public static function genelVarsayilan(): array
    {
        return collect(config('isg.acil_durum.konular', []))
            ->whereNull('kosul')
            ->pluck('anahtar')
            ->all();
    }

    private static function uygunMu(?array $kosul, ?string $naceKok, ?string $tehlike): bool
    {
        if ($kosul === null) {
            return true;
        }

        foreach ($kosul['nace'] ?? [] as $onEk) {
            if ($naceKok !== null && str_starts_with($naceKok, (string) $onEk)) {
                return true;
            }
        }

        return in_array($tehlike, $kosul['tehlike'] ?? [], true);
    }

    /** "10.71" / "1071" → "10" gibi ilk iki haneli NACE kökü. */
    private static function naceKok(?string $nace): ?string
    {
        if (blank($nace)) {
            return null;
        }

        $rakamlar = preg_replace('/\D/', '', $nace);

        return $rakamlar === '' ? null : Str::substr($rakamlar, 0, 2);
    }
}
