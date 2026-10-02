<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Acil durum ekibi üyesi (destek elemanı). Belge bitişi boşsa belge
 * tarihinden ekip türünün geçerlilik süresiyle hesaplanır.
 */
class AcilEkipUyesi extends Model
{
    use SoftDeletes;

    public const BELGE_DURUMLARI = ['gecerli' => 'Geçerli', 'yaklasan' => '30 gün içinde', 'dolmus' => 'Süresi dolmuş', 'yok' => 'Belge yok'];

    protected $table = 'acil_ekip_uyeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'lider' => 'boolean',
        'belge_tarihi' => 'date',
        'belge_bitis' => 'date',
    ];

    public function ekip(): BelongsTo
    {
        return $this->belongsTo(AcilEkip::class, 'acil_ekip_id')->withTrashed();
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function belgeBitisTarihi(): ?Carbon
    {
        if ($this->belge_bitis) {
            return $this->belge_bitis;
        }

        $ay = $this->ekip?->turBilgisi()['belge_ay'] ?? 12;

        return $this->belge_tarihi?->copy()->addMonths($ay);
    }

    public function belgeDurumu(): string
    {
        $bitis = $this->belgeBitisTarihi();

        if (! $bitis) {
            return 'yok';
        }

        $kalan = (int) Carbon::today()->diffInDays($bitis, false);

        return match (true) {
            $kalan < 0 => 'dolmus',
            $kalan <= (int) config('isg.acil_durum.ekip_belge_yaklasan_gun', 30) => 'yaklasan',
            default => 'gecerli',
        };
    }

    public function uyelikEtiketi(): string
    {
        return $this->uyelik === 'yedek' ? 'Yedek' : 'Asıl';
    }
}
