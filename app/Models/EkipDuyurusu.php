<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OSGB Ekip İçi Duyuru / Talimat — okundu ve onaylandı takibi (F6).
 */
class EkipDuyurusu extends Model
{
    protected $table = 'ekip_duyurulari';

    protected $guarded = ['id'];

    protected $casts = [
        'okuyanlar' => 'array',
    ];

    public const ONCELIKLER = [
        'normal' => 'Normal',
        'onemli' => 'Önemli',
        'acil' => 'Acil',
    ];

    public function gonderen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function okuduMu(int $userId): bool
    {
        return isset(($this->okuyanlar ?? [])[(string) $userId]);
    }

    public function okunduIsaretle(User $user): void
    {
        $liste = (array) ($this->okuyanlar ?? []);
        $anahtar = (string) $user->id;
        if (! isset($liste[$anahtar])) {
            $liste[$anahtar] = [
                'ad' => (string) $user->name,
                'okundu_at' => now()->toIso8601String(),
            ];
            $this->update(['okuyanlar' => $liste]);
        }
    }

    public function okuyanSayisi(): int
    {
        return count((array) ($this->okuyanlar ?? []));
    }

    /**
     * Multi-tenant kiracı izolasyonu: kullanıcı yalnız kendi duyurularını ve
     * ortak firma paylaştığı ekip arkadaşlarının duyurularını görür.
     *
     * @return list<int>
     */
    public static function ekipKullaniciIdleri(User $user): array
    {
        $idler = [(int) $user->id];

        $kendiFirmaIdleri = \Illuminate\Support\Facades\DB::table('firmalar')
            ->where('user_id', $user->id)
            ->pluck('id');
        $paylasilanFirmaIdleri = \Illuminate\Support\Facades\DB::table('kullanici_firmalar')
            ->where('user_id', $user->id)
            ->pluck('firma_id');

        $ortakFirmaIdleri = $kendiFirmaIdleri->merge($paylasilanFirmaIdleri)->unique()->values();

        if ($ortakFirmaIdleri->isNotEmpty()) {
            $sahipler = \Illuminate\Support\Facades\DB::table('firmalar')
                ->whereIn('id', $ortakFirmaIdleri)
                ->pluck('user_id');
            $paylasanlar = \Illuminate\Support\Facades\DB::table('kullanici_firmalar')
                ->whereIn('firma_id', $ortakFirmaIdleri)
                ->pluck('user_id');

            $idler = collect($idler)->merge($sahipler)->merge($paylasanlar)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return $idler;
    }
}
