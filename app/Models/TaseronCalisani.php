<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Taşeron çalışanı — ana personel listesine (calisanlar) eklenmez. */
class TaseronCalisani extends Model
{
    protected $table = 'taseron_calisanlari';

    protected $guarded = ['id'];

    protected $attributes = ['aktif' => true];

    protected $casts = [
        'ise_giris' => 'date',
        'isg_egitim_tarihi' => 'date',
        'saglik_raporu_tarihi' => 'date',
        'aktif' => 'boolean',
    ];

    public function taseron(): BelongsTo
    {
        return $this->belongsTo(Taseron::class);
    }

    /** Eğitimin geçerli olduğu son gün — tehlike sınıfına göre yenileme yılı. */
    public function egitimBitis(): ?Carbon
    {
        $yil = config('isg.egitim_yenileme_yili.'.$this->taseron?->etkinTehlikeSinifi(), 1);

        return $this->isg_egitim_tarihi?->copy()->addYears($yil);
    }

    /** Sağlık raporunun geçerli olduğu son gün — periyodik muayene yılı. */
    public function saglikBitis(): ?Carbon
    {
        $yil = config('isg.saglik_periyodik_yili.'.$this->taseron?->etkinTehlikeSinifi(), 1);

        return $this->saglik_raporu_tarihi?->copy()->addYears($yil);
    }

    public function egitimGecerliMi(): bool
    {
        return (bool) $this->egitimBitis()?->isFuture();
    }

    public function saglikGecerliMi(): bool
    {
        return (bool) $this->saglikBitis()?->isFuture();
    }

    /** "12345678901" → "123******01"; zaten maskeliyse dokunmaz. */
    public static function maskele(?string $tc): ?string
    {
        $tc = $tc !== null ? trim($tc) : null;

        if (blank($tc)) {
            return null;
        }

        return preg_match('/^\d{11}$/', $tc) ? substr($tc, 0, 3).'******'.substr($tc, -2) : $tc;
    }
}
