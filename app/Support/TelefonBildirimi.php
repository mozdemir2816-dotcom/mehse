<?php

namespace App\Support;

use App\Filament\Pages\BildirimMerkezi;
use App\Models\Bildirim;
use App\Models\PushAbonelik;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Günlük telefon bildirimi (13.10.2026): Bildirim Merkezi'ni tarar, telefona henüz
 * gönderilmemiş açık kritik/uyarı bildirimlerini TEK özet bildirimde gönderir.
 * Aynı uyarı ikinci kez gönderilmez (bildirimler.push_at); çözülüp yeniden
 * açılan uyarı BildirimTarayici'de yeni sayılır ve tekrar gönderilir.
 */
class TelefonBildirimi
{
    /** "bilgi" seviyesi telefona gönderilmez — yalnız panelde. */
    public const SEVIYELER = ['kritik', 'uyari'];

    /** @return array{abonelik: int, gonderildi: int, uyari: int} */
    public static function gunlukOzet(User $kullanici): array
    {
        $abonelikler = PushAbonelik::where('user_id', $kullanici->id)->get();
        if ($abonelikler->isEmpty()) {
            return ['abonelik' => 0, 'gonderildi' => 0, 'uyari' => 0];
        }

        BildirimTarayici::tara($kullanici);

        $yeniler = Bildirim::query()
            ->where('user_id', $kullanici->id)
            ->acik()
            ->whereNull('okundu_at')
            ->whereNull('push_at')
            ->whereIn('seviye', self::SEVIYELER)
            ->orderByRaw("CASE seviye WHEN 'kritik' THEN 0 ELSE 1 END")
            ->orderBy('tarih')
            ->get();

        if ($yeniler->isEmpty()) {
            return ['abonelik' => $abonelikler->count(), 'gonderildi' => 0, 'uyari' => 0];
        }

        $yuk = static::ozetYuku($yeniler);
        $gonderildi = $abonelikler->filter(fn (PushAbonelik $a) => WebPush::gonder($a, $yuk) === 'gonderildi')->count();

        // En az bir cihaza ulaştıysa işaretle; hiçbirine ulaşmadıysa ertesi gün yeniden denenir.
        if ($gonderildi > 0) {
            Bildirim::whereKey($yeniler->modelKeys())->update(['push_at' => now()]);
        }

        return ['abonelik' => $abonelikler->count(), 'gonderildi' => $gonderildi, 'uyari' => $yeniler->count()];
    }

    /** @return array{title: string, body: string, url: string, tag: string} */
    public static function ozetYuku(Collection $yeniler): array
    {
        $kritik = $yeniler->where('seviye', 'kritik')->count();
        $sayilar = array_filter([
            $kritik ? $kritik.' kritik' : null,
            ($u = $yeniler->count() - $kritik) ? $u.' uyarı' : null,
        ]);

        $satirlar = $yeniler->take(3)->map(fn (Bildirim $b) => '• '.$b->baslik)->all();
        if ($yeniler->count() > 3) {
            $satirlar[] = '… ve '.($yeniler->count() - 3).' bildirim daha';
        }

        return [
            'title' => 'MEHSE · '.implode(', ', $sayilar),
            'body' => implode("\n", $satirlar),
            'url' => BildirimMerkezi::getUrl(panel: 'admin'),
            'tag' => 'mehse-gunluk',
        ];
    }

    /** Ayarlar > "Deneme bildirimi gönder". */
    public static function deneme(User $kullanici): int
    {
        $yuk = [
            'title' => 'MEHSE bildirimleri açık',
            'body' => 'Vadesi yaklaşan ve geçen işler her sabah bu telefona özet olarak gelecek.',
            'url' => BildirimMerkezi::getUrl(panel: 'admin'),
            'tag' => 'mehse-deneme',
        ];

        return PushAbonelik::where('user_id', $kullanici->id)->get()
            ->filter(fn (PushAbonelik $a) => WebPush::gonder($a, $yuk, 3600) === 'gonderildi')
            ->count();
    }
}
