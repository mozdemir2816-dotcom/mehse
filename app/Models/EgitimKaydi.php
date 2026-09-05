<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Eğitim Kaydı — isgpratik 139-140.jpg. Çalışan × eğitim türü (kullanıcının
 * EgitimTuru listesi) tamamlanma tarihi.
 */
class EgitimKaydi extends Model
{
    protected $table = 'egitim_kayitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
    ];

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    /**
     * Eğitim türü tanımı. Çağıran taraf zaten elinde tutuyorsa (matris
     * döngüsü gibi) $tur ile geçirip her kayıt için ayrı DB sorgusunu önler.
     */
    public function turTanimi(?EgitimTuru $tur = null): ?EgitimTuru
    {
        if ($tur) {
            return $tur;
        }

        $userId = $this->calisan?->firma?->user_id;

        return $userId ? EgitimTuru::where('user_id', $userId)->where('anahtar', $this->tur)->first() : null;
    }

    public function turEtiketi(?EgitimTuru $tur = null): string
    {
        return $this->turTanimi($tur)?->ad ?? $this->tur;
    }

    /**
     * Geçerlilik tarihi: sabit ay tanımlıysa tarih + ay; temel İSG eğitimi
     * (gecerlilik_ay=null) tehlike sınıfına göre yıl bazlı (egitim_yenileme_yili).
     */
    public function gecerlilikTarihi(?EgitimTuru $tur = null): ?Carbon
    {
        $ay = $this->turTanimi($tur)?->gecerlilik_ay;

        if ($ay !== null) {
            return $this->tarih?->copy()->addMonths($ay);
        }

        $tehlikeSinifi = $this->calisan?->firma?->tehlike_sinifi;
        $yil = config('isg.egitim_yenileme_yili.'.$tehlikeSinifi);

        return $yil ? $this->tarih?->copy()->addYears($yil) : null;
    }

    /** @return 'gecerli'|'yakinda'|'dolmus' */
    public function durum(?EgitimTuru $tur = null): string
    {
        $gecerlilik = $this->gecerlilikTarihi($tur);

        if (! $gecerlilik) {
            return 'gecerli';
        }

        if ($gecerlilik->isPast()) {
            return 'dolmus';
        }

        return $gecerlilik->diffInDays(now(), absolute: true) <= 60 ? 'yakinda' : 'gecerli';
    }
}
