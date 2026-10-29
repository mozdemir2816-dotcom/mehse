<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tanıtım sayfasındaki teklif formundan gelen talep (üye olmayan ziyaretçi). */
class TeklifTalebi extends Model
{
    protected $table = 'teklif_talepleri';

    protected $guarded = ['id'];

    public const DURUMLAR = [
        'yeni' => 'Yeni',
        'arandi' => 'Arandı',
        'teklif_verildi' => 'Teklif verildi',
        'kapandi' => 'Kapandı',
    ];

    public const TEHLIKE_SINIFLARI = ['Az Tehlikeli', 'Tehlikeli', 'Çok Tehlikeli', 'Bilmiyorum'];
}
