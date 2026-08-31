<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tehlike extends Model
{
    use HasFactory;

    protected $table = 'tehlikeler';

    protected $guarded = ['id'];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(TehlikeKategorisi::class, 'tehlike_kategorisi_id');
    }
}
