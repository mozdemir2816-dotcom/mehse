<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Uzmanın kaydettiği işe özgü eğitim konusu (bkz. App\Support\IseOzguEgitimKutuphanesi). */
class IseOzguEgitimKonusu extends Model
{
    protected $table = 'ise_ozgu_egitim_konulari';

    protected $guarded = ['id'];

    protected $casts = [
        'is_kalemleri' => 'array',
        'gizli' => 'boolean',
    ];
}
