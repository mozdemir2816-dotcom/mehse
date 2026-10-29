<?php

namespace App\Support;

use App\Models\Calisan;
use Illuminate\Database\Eloquent\Builder;

/**
 * Çalışanlar listesindeki "İSG Durumu" rozetleri: eğitim kaydı, KKD zimmeti ve sağlık muayenesi.
 * Sayımlar liste sorgusuna toplu eklenir (modify), satır başına ek sorgu atılmaz.
 */
class CalisanIsgDurumu
{
    /** Liste sorgusuna gereken toplu sayımları ekler. */
    public static function sorgu(Builder $query): Builder
    {
        $bugun = now()->toDateString();

        return $query
            ->withCount([
                'egitimKayitlari as isg_egitim_say',
                'kkdZimmetleri as isg_kkd_say' => fn (Builder $q) => $q->where('durum', 'teslim_edildi'),
                'kkdZimmetleri as isg_kkd_gecikmis' => fn (Builder $q) => $q->where('durum', 'teslim_edildi')
                    ->where(fn (Builder $w) => $w->where('yenileme_tarihi', '<', $bugun)->orWhere('son_kullanma', '<', $bugun)),
            ])
            ->withMax('muayeneFormlari as isg_son_muayene', 'muayene_tarihi');
    }

    /** @return array<int, array{etiket: string, renk: string, ipucu: string}> */
    public static function rozetler(Calisan $c): array
    {
        $rozetler = [];

        $egitim = (int) ($c->isg_egitim_say ?? 0);
        $rozetler[] = $egitim > 0
            ? ['etiket' => 'Eğitim Tam', 'renk' => 'yesil', 'ipucu' => $egitim.' eğitim kaydı var']
            : ['etiket' => 'Eğitim Eksik', 'renk' => 'sari', 'ipucu' => 'Eğitim kaydı yok'];

        $gecikmis = (int) ($c->isg_kkd_gecikmis ?? 0);
        $rozetler[] = match (true) {
            $gecikmis > 0 => ['etiket' => 'KKD Gecikmiş', 'renk' => 'kirmizi', 'ipucu' => $gecikmis.' zimmet yenileme/SKT tarihini geçmiş'],
            (int) ($c->isg_kkd_say ?? 0) === 0 => ['etiket' => 'KKD Eksik', 'renk' => 'sari', 'ipucu' => 'Teslim edilmiş KKD zimmeti yok'],
            default => ['etiket' => 'KKD Tam', 'renk' => 'yesil', 'ipucu' => $c->isg_kkd_say.' KKD zimmeti güncel'],
        };

        $son = $c->isg_son_muayene ?? null;
        $periyotYil = (int) config('isg.saglik_periyodik_yili.'.($c->firma?->tehlike_sinifi ?? 'tehlikeli'), 3);
        $rozetler[] = match (true) {
            ! $son => ['etiket' => 'Muayene Yok', 'renk' => 'sari', 'ipucu' => 'Muayene formu kaydı yok'],
            \Illuminate\Support\Carbon::parse($son)->addYears($periyotYil)->isPast() => ['etiket' => 'Muayene Gecikmiş', 'renk' => 'kirmizi', 'ipucu' => 'Son muayene '.\Illuminate\Support\Carbon::parse($son)->format('d.m.Y').' — periyot '.$periyotYil.' yıl'],
            default => ['etiket' => 'Muayene Tam', 'renk' => 'yesil', 'ipucu' => 'Son muayene '.\Illuminate\Support\Carbon::parse($son)->format('d.m.Y')],
        };

        return $rozetler;
    }
}
