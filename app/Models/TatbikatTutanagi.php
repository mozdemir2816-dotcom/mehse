<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tatbikat Tutanağı — isgpratik 61-65.jpg; isgsuite "Tatbikat Yönetimi" ile
 * planlama durumu eklendi (planlandı → yapıldı / takip gerekiyor).
 */
class TatbikatTutanagi extends Model
{
    use HasFactory;

    /** Yönetmelik gereği "yapılmış tatbikat" sayılan durumlar. */
    public const YAPILMIS = ['yapildi', 'takip'];

    protected $table = 'tatbikat_tutanaklari';

    protected $guarded = ['id'];

    protected $attributes = ['durum' => 'yapildi'];

    protected $casts = [
        'tatbikat_tarihi' => 'date',
        'belge_tarihi' => 'date',
        'haberli_tatbikat' => 'boolean',
        'yillik_plan_dahilinde' => 'boolean',
        'isyeri_hekimi_imzasi' => 'boolean',
        'katilimci_sayisi' => 'integer',
        'ekipler' => 'array',
        'degerlendirmeler' => 'array',
        'eksiklikler' => 'array',
        'dof_onerileri' => 'array',
        'katilimcilar' => 'array',
        'fotograflar' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function senaryoEtiketi(): string
    {
        return config('isg.tatbikat.senaryolar.'.$this->senaryo_anahtari.'.ad', 'Genel Tatbikat');
    }

    public function durumEtiketi(): string
    {
        return config('isg.tatbikat.durumlar.'.$this->durum, $this->durum ?? '—');
    }

    public function yapildiMi(): bool
    {
        return in_array($this->durum, self::YAPILMIS, true);
    }

    /** Planlanan tarih geçtiği hâlde hâlâ "planlandı" duran kayıt. */
    public function gecikmisMi(): bool
    {
        return $this->durum === 'planlandi' && $this->tatbikat_tarihi?->lt(today());
    }

    /** Elle girilen sayı yoksa katılımcı listesi sayılır. */
    public function katilimciSayisi(): int
    {
        return $this->katilimci_sayisi ?? count($this->katilimcilar ?? []);
    }

    /** Eksiklik veya DÖF önerisi olan yapılmış tatbikat takip ister. */
    public function takipGerektiriyorMu(): bool
    {
        return filled($this->eksiklikler) || filled($this->dof_onerileri);
    }
}
