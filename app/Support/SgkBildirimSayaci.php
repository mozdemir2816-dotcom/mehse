<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * İş kazası / meslek hastalığı SGK bildirim süresi (6331 SK m.14): 3 iş günü içinde.
 * Son gün = başlangıç tarihinden 3 iş günü sonrasının gün sonu (hafta sonları ve
 * Türkiye ulusal bayram / genel tatil günleri atlanır).
 */
class SgkBildirimSayaci
{
    public const IS_GUNU = 3;

    /** Her yıl sabit Türkiye resmî tatil günleri (AA-GG). */
    public const SABIT_RESMI_TATILLER = [
        '01-01', // Yılbaşı
        '04-23', // Ulusal Egemenlik ve Çocuk Bayramı
        '05-01', // Emek ve Dayanışma Günü
        '05-19', // Atatürk'ü Anma, Gençlik ve Spor Bayramı
        '07-15', // Demokrasi ve Millî Birlik Günü
        '08-30', // Zafer Bayramı
        '10-29', // Cumhuriyet Bayramı
    ];

    /** Verilen tarih hafta sonu veya Türkiye resmî tatili mi? */
    public static function tatilMi(Carbon $gun): bool
    {
        if ($gun->isWeekend()) {
            return true;
        }

        if (in_array($gun->format('m-d'), self::SABIT_RESMI_TATILLER, true)) {
            return true;
        }

        $ekTatiller = (array) config('isg.resmi_tatiller', []);

        return in_array($gun->toDateString(), $ekTatiller, true);
    }

    /** Hafta sonları ve resmî tatilleri atlayarak belirtilen sayıda iş günü ekler. */
    public static function isGunuEkle(Carbon $baslangic, int $isGunu = self::IS_GUNU): Carbon
    {
        $tarih = $baslangic->copy()->startOfDay();
        $kalan = max(0, $isGunu);

        while ($kalan > 0) {
            $tarih->addDay();
            if (! self::tatilMi($tarih)) {
                $kalan--;
            }
        }

        return $tarih;
    }

    /**
     * @return array{son: Carbon, kalan_saat: int, durum: 'yapildi'|'gecti'|'acil'|'surede'}|null olay tarihi yoksa null
     */
    public static function hesapla(?string $olayTarihi, bool $yapildi = false, ?Carbon $simdi = null): ?array
    {
        if (blank($olayTarihi)) {
            return null;
        }

        $simdi ??= now();
        $son = self::isGunuEkle(Carbon::parse($olayTarihi), self::IS_GUNU)->endOfDay();
        $kalan = (int) floor($simdi->diffInMinutes($son, false) / 60);

        $durum = match (true) {
            $yapildi => 'yapildi',
            $kalan < 0 => 'gecti',
            $kalan <= 24 => 'acil',
            default => 'surede',
        };

        return ['son' => $son, 'kalan_saat' => $kalan, 'durum' => $durum];
    }

    /** Ekranda gösterilecek tek satırlık metin ve renk (slate/rose/amber/emerald). */
    public static function metin(array $s): array
    {
        return match ($s['durum']) {
            'yapildi' => ['✓ SGK bildirimi yapıldı', '#047857', '#ecfdf5'],
            'gecti' => ['⚠ SGK bildirim süresi geçti ('.$s['son']->format('d.m.Y').') — gecikmeden bildirin (Md. 14)', '#be123c', '#fff1f2'],
            'acil' => ['⚠ SGK Bildirim Süresi: Kalan '.$s['kalan_saat'].' saat (Md. 14)', '#b45309', '#fffbeb'],
            default => ['SGK Bildirim Süresi: Kalan '.$s['kalan_saat'].' saat — son gün '.$s['son']->format('d.m.Y').' (Md. 14)', '#334155', '#f8fafc'],
        };
    }
}
