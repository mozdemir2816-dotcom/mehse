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
            $s->dokuman_no ??= \App\Support\BelgeNumarasi::sonraki(static::class, 'dokuman_no', 'ÇT-'.now()->format('Y').'-', 2);
            $s->ilan_tarihi ??= now();

            if ($s->isyeri_calisan_sayisi === null) {
                $s->isyeri_calisan_sayisi = $s->firma?->calisanlar()->where('aktif', true)->count()
                    ?: (int) ($s->firma?->calisan_sayisi ?? 0);
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

    /** @return array<int, array{ad_soyad: string, unvan: ?string, secildi: bool, bas_temsilci: bool}> */
    public function adaylarListesi(): array
    {
        $ham = $this->adaylar ?? [];
        $herhangiSecili = collect($ham)->contains(fn ($a) => ! empty($a['secildi']));

        return collect($ham)
            ->values()
            ->map(function ($a, int $i) use ($herhangiSecili) {
                $secildi = $herhangiSecili
                    ? ! empty($a['secildi'])
                    : ($this->secilen_aday_index !== null && $this->secilen_aday_index === $i);
                $bas = ! empty($a['bas_temsilci'])
                    || (! $herhangiSecili && $secildi);

                return [
                    'ad_soyad' => $a['ad_soyad'] ?? '',
                    'unvan' => $a['unvan'] ?? null,
                    'secildi' => $secildi,
                    'bas_temsilci' => $secildi && $bas,
                ];
            })
            ->all();
    }

    /** 6331 m.20/1–2: seçilen tüm çalışan temsilcileri (Baş Temsilci en üstte). @return array<int, array{ad_soyad: string, unvan: ?string, secildi: bool, bas_temsilci: bool}> */
    public function secilenAdaylar(): array
    {
        return collect($this->adaylarListesi())
            ->filter(fn (array $a) => $a['secildi'] && filled($a['ad_soyad']))
            ->sortByDesc(fn (array $a) => $a['bas_temsilci'] ? 1 : 0)
            ->values()
            ->all();
    }

    /** @return array{ad_soyad: string, unvan: ?string, secildi: bool, bas_temsilci: bool}|null */
    public function basTemsilci(): ?array
    {
        $secilenler = $this->secilenAdaylar();

        return collect($secilenler)->firstWhere('bas_temsilci', true) ?? ($secilenler[0] ?? null);
    }

    /** @return array{ad_soyad: string, unvan: ?string, secildi: bool, bas_temsilci: bool}|null */
    public function secilenAday(): ?array
    {
        if ($bas = $this->basTemsilci()) {
            return $bas;
        }

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
