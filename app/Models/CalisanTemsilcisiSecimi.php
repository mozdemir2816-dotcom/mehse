<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Çalışan Temsilcisi Seçim süreci — isgpratik ATAMA YAZISI/ÇALIŞAN TEMSİLCİSİ
 * referansı. Firma başına bir kayıt: seçim duyurusu, aday listesi, oy pusulası
 * ve atama tutanağı hep bu kayıttan üretilir (`CalisanTemsilcisiSecimiUretici`).
 */
class CalisanTemsilcisiSecimi extends Model
{
    use HasFactory;

    protected $table = 'calisan_temsilcisi_secimleri';

    protected $guarded = ['id'];

    protected $casts = [
        'ilan_tarihi' => 'date',
        'aday_basvuru_son_tarihi' => 'date',
        'secim_tarihi' => 'date',
        'gorevlendirme_tarihi' => 'date',
        'adaylar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CalisanTemsilcisiSecimi $s): void {
            $s->dokuman_no ??= 'ÇT-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 2, '0', STR_PAD_LEFT);
            $s->ilan_tarihi ??= now();

            if ($s->isyeri_calisan_sayisi === null) {
                $s->isyeri_calisan_sayisi = $s->firma?->calisanlar()->count() ?? 0;
            }

            if ($s->zorunlu_temsilci_sayisi === null) {
                $s->zorunlu_temsilci_sayisi = static::zorunluTemsilciSayisi($s->isyeri_calisan_sayisi);
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** @return array<int, array{ad_soyad: string, unvan: ?string}> */
    public function adaylarListesi(): array
    {
        return collect($this->adaylar ?? [])
            ->map(fn ($a) => ['ad_soyad' => $a['ad_soyad'] ?? '', 'unvan' => $a['unvan'] ?? null])
            ->all();
    }

    /** @return array{ad_soyad: string, unvan: ?string}|null */
    public function secilenAday(): ?array
    {
        if ($this->secilen_aday_index === null) {
            return null;
        }

        return $this->adaylarListesi()[$this->secilen_aday_index] ?? null;
    }

    /**
     * 6331 sayılı Kanun md.20/2 fıkrasındaki çalışan sayısı kademelerine göre
     * zorunlu çalışan temsilcisi sayısı — yalnızca ÖNERİ/varsayılan değerdir,
     * kullanıcı formda değiştirebilir.
     */
    public static function zorunluTemsilciSayisi(int $calisanSayisi): int
    {
        return match (true) {
            $calisanSayisi <= 50 => 1,
            $calisanSayisi <= 100 => 2,
            $calisanSayisi <= 500 => 3,
            $calisanSayisi <= 1000 => 4,
            $calisanSayisi <= 2000 => 5,
            default => 6,
        };
    }

    public static function firmaIcin(Firma $firma): self
    {
        $secim = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $secim->exists) {
            $secim->save();
        }

        return $secim;
    }
}
