<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TehlikeKategorisi extends Model
{
    use HasFactory;

    protected $table = 'tehlike_kategorileri';

    protected $guarded = ['id'];

    protected $casts = [
        'ekipman_tipleri' => 'array',
    ];

    public function tehlikeler(): HasMany
    {
        return $this->hasMany(Tehlike::class);
    }
}
