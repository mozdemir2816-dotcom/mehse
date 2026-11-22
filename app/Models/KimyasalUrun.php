<?php

namespace App\Models;

use App\Support\KullaniciAyarlari;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İşyeri kimyasal envanterindeki tek ürün — SDS dosyası + GHS sınıfları +
 * gözden geçirme tarihi.
 */
class KimyasalUrun extends Model
{
    use HasFactory;

    protected $table = 'kimyasal_urunler';

    protected $guarded = ['id'];

    protected $casts = [
        'ghs' => 'array',
        'sds_tarihi' => 'date',
        'sonraki_gozden_gecirme' => 'date',
        'aktif' => 'boolean',
        'ozet' => 'array',
        'ozet_yz_taslak' => 'boolean',
        'ozet_onaylandi_at' => 'datetime',
        'evrak_cikti_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (KimyasalUrun $u): void {
            // SDS tarihi girilmiş, sonraki gözden geçirme boşsa +1 yıl (yaygın uygulama).
            if ($u->sds_tarihi && ! $u->sonraki_gozden_gecirme) {
                $u->sonraki_gozden_gecirme = Carbon::parse($u->sds_tarihi)->addYear();
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function sdsVarMi(): bool
    {
        return filled($this->sds_dosya_yolu);
    }

    /** @return array<int, string> */
    public function ghsEtiketleri(): array
    {
        $tanim = config('isg.kimyasal.ghs', []);

        return collect($this->ghs ?? [])->map(fn ($k) => $tanim[$k] ?? $k)->all();
    }

    public function gozdenGecirmeDurumu(): string
    {
        if (! $this->sonraki_gozden_gecirme) {
            return 'belirsiz';
        }

        $gun = Carbon::today()->diffInDays($this->sonraki_gozden_gecirme, false);

        return match (true) {
            $gun < 0 => 'gecikmis',
            $gun <= KullaniciAyarlari::esik('kimyasal') => 'yaklasan',
            default => 'guncel',
        };
    }

    /** MSDS özeti (afiş + özet sayfası için yeterli içerik) var mı? */
    public function ozetVarMi(): bool
    {
        return filled($this->ozet['ilk_yardim'] ?? null) || filled($this->ozet['tehlikeler'] ?? null);
    }

    /** MSDS yüklendi ya da değişti ama özet eski MSDS'den (ya da MSDS'siz) üretilmiş: yenilenmeli. */
    public function ozetEskiMi(): bool
    {
        return $this->ozetVarMi() && $this->sdsVarMi() && ($this->ozet['kaynak_sds'] ?? null) !== $this->sds_dosya_yolu;
    }

    public function ozetOnayBekliyorMu(): bool
    {
        return $this->ozetVarMi() && $this->ozet_yz_taslak && ! $this->ozet_onaylandi_at;
    }

    /** Afişte basılacak GHS kodları: özetteki, yoksa üründe işaretli olanlar (ghs01–ghs09). */
    public function ghsKodlari(): array
    {
        $kodlar = filled($this->ozet['ghs'] ?? null) ? $this->ozet['ghs'] : ($this->ghs ?? []);

        return collect($kodlar)->map(fn ($k) => strtolower(trim((string) $k)))
            ->filter(fn ($k) => preg_match('/^ghs0[1-9]$/', $k))->unique()->values()->all();
    }
}
