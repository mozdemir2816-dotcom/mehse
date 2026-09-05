<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İSG Profesyoneli — İş Güvenliği Uzmanı / İşyeri Hekimi / DSP. Firmalara atanır
 * (Firma.igu_id/isyeri_hekimi_id/dsp_id); kaşe/imza görseli atandığı firmanın
 * belgelerinde (Atama Yazıları vb.) otomatik basılır.
 */
class IsgProfesyoneli extends Model
{
    use HasFactory;

    protected $table = 'isg_profesyonelleri';

    protected $guarded = ['id'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (IsgProfesyoneli $p): void {
            if (blank($p->user_id) && auth()->check()) {
                $p->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tipEtiketi(): string
    {
        return config('isg.isg_profesyonelleri.tipler.'.$this->tip, $this->tip);
    }

    /** Bu tipin atandığı Firma sütunu — toplu firma atama (IsgProfesyoneliResource). */
    public function firmaAlani(): ?string
    {
        return match ($this->tip) {
            'igu' => 'igu_id',
            'isyeri_hekimi' => 'isyeri_hekimi_id',
            'dsp' => 'dsp_id',
            default => null,
        };
    }
}
