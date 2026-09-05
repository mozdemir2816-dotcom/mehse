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
}
