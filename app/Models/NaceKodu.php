<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaceKodu extends Model
{
    protected $guarded = ['id'];

    protected $table = 'nace_kodlari';

    /** Kullanıcı girdisini ("01.11.14", "011114", boşluklu vb.) resmi noktalı 6 haneli koda normalize eder. */
    public static function normalizeKod(string $girdi): ?string
    {
        $rakamlar = preg_replace('/\D/', '', $girdi);

        if (strlen($rakamlar) !== 6) {
            return null;
        }

        return substr($rakamlar, 0, 2).'.'.substr($rakamlar, 2, 2).'.'.substr($rakamlar, 4, 2);
    }

    public static function bul(string $girdi): ?self
    {
        $kod = self::normalizeKod($girdi);

        return $kod ? self::where('kod', $kod)->first() : null;
    }

    /** 4 haneli SGK işkolu koduna (ör. "4120" veya "41.20") bağlı 6 haneli alt kırılımları döner. */
    public static function nace4IleBul(string $nace4): \Illuminate\Database\Eloquent\Collection
    {
        $rakamlar = preg_replace('/\D/', '', $nace4) ?? '';
        if (strlen($rakamlar) < 4) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        $onEk = substr($rakamlar, 0, 2).'.'.substr($rakamlar, 2, 2).'.';

        return self::where('kod', 'like', $onEk.'%')->orderBy('kod')->get();
    }

    /** Faaliyet adına veya sektör tanımına göre NACE arama. */
    public static function faaliyetAra(string $terim): \Illuminate\Database\Eloquent\Collection
    {
        $temiz = trim($terim);
        if (mb_strlen($temiz) < 2) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return self::where('tanim', 'like', '%'.$temiz.'%')
            ->orWhere('sektor_adi', 'like', '%'.$temiz.'%')
            ->orWhere('kod', 'like', '%'.$temiz.'%')
            ->orderBy('kod')
            ->limit(25)
            ->get();
    }
}
