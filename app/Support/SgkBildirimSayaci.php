<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * İş kazası SGK bildirim süresi (6331 SK m.14): kazadan sonraki 3 iş günü içinde.
 * Son gün = olay gününden 3 iş günü sonrasının sonu (hafta sonları sayılmaz; resmi tatiller hesaba katılmaz).
 */
class SgkBildirimSayaci
{
    public const IS_GUNU = 3;

    /**
     * @return array{son: Carbon, kalan_saat: int, durum: 'yapildi'|'gecti'|'acil'|'surede'}|null olay tarihi yoksa null
     */
    public static function hesapla(?string $olayTarihi, bool $yapildi = false, ?Carbon $simdi = null): ?array
    {
        if (blank($olayTarihi)) {
            return null;
        }

        $simdi ??= now();
        $son = Carbon::parse($olayTarihi)->addWeekdays(self::IS_GUNU)->endOfDay();
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
