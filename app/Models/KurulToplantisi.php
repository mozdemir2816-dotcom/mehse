<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İSG Kurulu toplantısı — isgpratik yardım/kurul-toplantisi rehberi.
 */
class KurulToplantisi extends Model
{
    use HasFactory;

    protected $table = 'kurul_toplantilari';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
        'sonraki_toplanti' => 'date',
        'katilimcilar' => 'array',
        'gundem' => 'array',
        'kararlar' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function durumEtiketi(): string
    {
        return config("isg.kurul_toplantisi.durumlar.{$this->durum}", (string) $this->durum);
    }

    public function turEtiketi(): string
    {
        return config("isg.kurul_toplantisi.turler.{$this->tur}", (string) $this->tur);
    }

    /** "23:49 - 02:49" / "14:00" / null */
    public function saatAraligi(): ?string
    {
        return collect([$this->saat, $this->bitis_saati])->filter()->implode(' - ') ?: null;
    }

    public function katilanSayisi(): int
    {
        return collect($this->katilimcilar ?? [])->where('katildi', true)->count();
    }

    /**
     * Firma + yıl bazında sıradaki toplantı numarası — "2026/1", "2026/2" …
     * Yeni toplantı oluşturulurken atanır; kullanıcı sonradan düzeltebilir.
     */
    public static function sonrakiNo(Firma $firma, ?string $tarih = null): string
    {
        $yil = ($tarih ? Carbon::parse($tarih) : now())->year;

        $sayi = $firma->kurulToplantilari()->whereYear('tarih', $yil)->count() + 1;

        return $yil.'/'.$sayi;
    }
}
