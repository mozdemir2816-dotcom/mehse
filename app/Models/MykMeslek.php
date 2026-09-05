<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MykMeslek extends Model
{
    protected $guarded = ['id'];

    protected $table = 'myk_meslekleri';

    /** Meslek adında veya yeterlilik kodunda (kısmi, büyük/küçük harf duyarsız) arar. */
    public static function ara(string $terim): Collection
    {
        $terim = trim($terim);

        if ($terim === '') {
            return new Collection;
        }

        return self::query()
            ->where(function (Builder $q) use ($terim) {
                $q->where('yeterlilik_adi', 'like', "%{$terim}%")
                    ->orWhere('yeterlilik_kodu', 'like', "%{$terim}%");
            })
            ->orderBy('yeterlilik_adi')
            ->orderBy('yeterlilik_kodu')
            ->get();
    }
}
