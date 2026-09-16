<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Onaylı Defter Nüshası — Tespit ve Öneri Defteri'nin imzalı/onaylı, İSG-KATİP'e
 * yüklenen nüshasının taranmış hâli. `nusha_no` firma + defter türü başına
 * otomatik sıralanır (1, 2, 3...).
 */
class OnayliDefterNushasi extends Model
{
    use HasFactory;

    protected $table = 'onayli_defter_nushalari';

    protected $guarded = ['id'];

    protected $casts = [
        'onay_tarihi' => 'date',
        'boyut' => 'integer',
        'nusha_no' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (OnayliDefterNushasi $n): void {
            $n->nusha_no ??= static::sonrakiNo($n->firma_id, $n->defter_turu ?: 'tespit_oneri');
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function tespitOneriDefteri(): BelongsTo
    {
        return $this->belongsTo(TespitOneriDefteri::class);
    }

    /** Firma + defter türü için bir sonraki nüsha numarası. */
    public static function sonrakiNo(int $firmaId, string $defterTuru): int
    {
        return (int) static::where('firma_id', $firmaId)
            ->where('defter_turu', $defterTuru)
            ->max('nusha_no') + 1;
    }

    public function defterTuruEtiketi(): string
    {
        return config('isg.onayli_defter.turleri.'.$this->defter_turu, $this->defter_turu);
    }

    public function baslik(): string
    {
        return $this->defterTuruEtiketi().' — '.$this->nusha_no.'. Nüsha';
    }

    public function boyutEtiketi(): string
    {
        if ($this->boyut <= 0) {
            return '—';
        }

        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }

    /**
     * Firmanın en son onaylı nüsha yükleme tarihi + periyot (config
     * isg.onayli_defter.periyot_ay, varsayılan 3 ay) = bir sonraki yükleme
     * vadesi. $defterTuru verilirse yalnız o türe bakılır, verilmezse
     * firmanın tüm defter türleri arasındaki en güncel yükleme esas alınır.
     */
    public static function vadeTarihi(int $firmaId, ?string $defterTuru = null): ?Carbon
    {
        $sorgu = static::where('firma_id', $firmaId)->whereNotNull('onay_tarihi');

        if ($defterTuru) {
            $sorgu->where('defter_turu', $defterTuru);
        }

        $sonTarih = $sorgu->max('onay_tarihi');

        if (! $sonTarih) {
            return null;
        }

        $periyotAy = (int) config('isg.onayli_defter.periyot_ay', 3);

        return Carbon::parse($sonTarih)->addMonths($periyotAy);
    }

    /**
     * Yükleme periyodu durumu — IsEkipmani::vizeDurumu() ile aynı desen.
     *
     * @return 'bekliyor'|'gecerli'|'yaklasan'|'dolmus'
     */
    public static function durum(int $firmaId, ?string $defterTuru = null): string
    {
        $vadeTarihi = static::vadeTarihi($firmaId, $defterTuru);

        if (! $vadeTarihi) {
            return 'bekliyor';
        }

        $bugun = Carbon::today();

        if ($vadeTarihi->lt($bugun)) {
            return 'dolmus';
        }

        $esik = (int) config('isg.onayli_defter.yaklasan_gun', 15);

        return $vadeTarihi->lte($bugun->copy()->addDays($esik)) ? 'yaklasan' : 'gecerli';
    }

    public static function durumEtiketi(string $durum): string
    {
        return [
            'bekliyor' => 'Henüz Nüsha Yüklenmedi',
            'gecerli' => 'Yükleme Güncel',
            'yaklasan' => 'Yeni Nüsha Yakında Gerekiyor',
            'dolmus' => 'Süresi Doldu — Nüsha Yükleyin',
        ][$durum] ?? $durum;
    }
}
