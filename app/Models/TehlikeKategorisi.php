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

    public function tehlikeler(): HasMany
    {
        return $this->hasMany(Tehlike::class);
    }
}
