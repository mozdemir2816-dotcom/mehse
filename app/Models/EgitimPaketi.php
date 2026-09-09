<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Uzaktan Eğitim Paketi — sıralı video dersleri + final sınav havuzu. İSG uzmanı
 * oluşturur, firmadaki çalışanlara atar (EgitimAtamasi). Çalışan portalda izler.
 */
class EgitimPaketi extends Model
{
    use HasFactory;

    protected $table = 'egitim_paketleri';

    protected $guarded = ['id'];

    protected $casts = [
        'gecme_puani' => 'integer',
        'video_zorunlu_yuzde' => 'integer',
        'sinav_soru_sayisi' => 'integer',
        'aktif' => 'boolean',
        'paylasildi' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (EgitimPaketi $p): void {
            $p->kod ??= 'UE-'.now()->format('Y').'-'.str_pad((string) (static::whereYear('created_at', now()->year)->count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dersler(): HasMany
    {
        return $this->hasMany(EgitimDersi::class)->orderBy('sira');
    }

    public function sorular(): HasMany
    {
        return $this->hasMany(EgitimPaketiSorusu::class);
    }

    public function atamalar(): HasMany
    {
        return $this->hasMany(EgitimAtamasi::class);
    }

    /** Kullanıcının kendi + paylaşılan paketleri. */
    public function scopeGorunur(Builder $q, int $userId): Builder
    {
        return $q->where(fn (Builder $b) => $b->where('user_id', $userId)->orWhere('paylasildi', true));
    }

    public function sektorEtiketi(): ?string
    {
        return $this->sektor ? config('isg.risk_ai.sektorler.'.$this->sektor.'.ad', $this->sektor) : null;
    }

    public function toplamSureDk(): int
    {
        return (int) ceil($this->dersler->sum('sure_sn') / 60);
    }

    /** Sınav için havuzdan rastgele soru seç (yetmezse hepsi). */
    public function sinavSorulari(): array
    {
        $havuz = $this->sorular()->inRandomOrder()->limit($this->sinav_soru_sayisi)->get();

        return $havuz->map(fn (EgitimPaketiSorusu $s) => [
            'id' => $s->id,
            'soru' => $s->soru,
            'secenekler' => $s->secenekler,
            'dogru_index' => $s->dogru_index,
        ])->all();
    }

    public function sinavaHazirMi(): bool
    {
        return $this->dersler()->exists() && $this->sorular()->count() >= min(5, $this->sinav_soru_sayisi);
    }

    public function paketOzeti(): string
    {
        return Str::of($this->ad)->limit(60).' · '.$this->dersler->count().' ders · '.$this->sorular()->count().' soru';
    }
}
