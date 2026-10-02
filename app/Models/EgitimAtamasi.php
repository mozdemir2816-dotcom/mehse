<?php

namespace App\Models;

use App\Support\EgitimTakibi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bir çalışana atanmış uzaktan eğitim. İlerleme (izlenen dersler) + sınav
 * sonuçları buna bağlı; `durum` bunlardan türetilir.
 */
class EgitimAtamasi extends Model
{
    use HasFactory;

    protected $table = 'egitim_atamalari';

    protected $guarded = ['id'];

    protected $casts = [
        'atandi_at' => 'datetime',
        'son_tarih' => 'date',
        'tamamlandi_at' => 'datetime',
        'on_test_at' => 'datetime',
        'on_test_puani' => 'integer',
        'yeniden_baslatma' => 'integer',
        'yeniden_baslatildi_at' => 'datetime',
    ];

    public function paket(): BelongsTo
    {
        return $this->belongsTo(EgitimPaketi::class, 'egitim_paketi_id');
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function atayan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atayan_user_id');
    }

    protected static function booted(): void
    {
        static::deleting(fn (EgitimAtamasi $a) => $a->girisler()->delete());
    }

    /** Bu eğitimin açıldığı tarihler (giriş günlüğü). */
    public function girisler(): HasMany
    {
        return $this->hasMany(EgitimGirisi::class)->orderBy('giris_at');
    }

    public function ilerlemeler(): HasMany
    {
        return $this->hasMany(EgitimDersIlerlemesi::class);
    }

    public function sinavSonuclari(): HasMany
    {
        return $this->hasMany(EgitimSinavSonucu::class)->orderByDesc('deneme_no');
    }

    /** İzlenmiş ders sayısı / toplam ders. */
    public function izlenenDersSayisi(): int
    {
        return $this->ilerlemeler()->where('izlendi', true)->count();
    }

    public function toplamDersSayisi(): int
    {
        return $this->paket->dersler()->count();
    }

    public function ilerlemeYuzdesi(): int
    {
        $toplam = $this->toplamDersSayisi();

        return $toplam > 0 ? (int) round($this->izlenenDersSayisi() / $toplam * 100) : 0;
    }

    public function tumDerslerIzlendiMi(): bool
    {
        $toplam = $this->toplamDersSayisi();

        return $toplam > 0 && $this->izlenenDersSayisi() >= $toplam;
    }

    public function sonSinav(): ?EgitimSinavSonucu
    {
        return $this->sinavSonuclari()->first();
    }

    public function basariliMi(): bool
    {
        return $this->sinavSonuclari()->where('gecti', true)->exists();
    }

    public function sonrakiDeneme(): int
    {
        return (int) $this->sinavSonuclari()->max('deneme_no') + 1;
    }

    /** Yönetmelik alt sınırı (60) altına inilemeyen geçme puanı. */
    public function gecmePuani(): int
    {
        return max((int) $this->paket?->gecme_puani, (int) config('isg.uzaktan_egitim.gecme_puani_alt_sinir', 60));
    }

    /** Eğitim son kez (yeniden) başlatıldıktan sonra girilen sınavlar. */
    public function donemSinavlari(): HasMany
    {
        return $this->sinavSonuclari()
            ->when($this->yeniden_baslatildi_at, fn ($q) => $q->where('tamamlandi_at', '>', $this->yeniden_baslatildi_at));
    }

    /** Md.16/3: ilk sınav + en fazla iki tekrar. */
    public function kalanSinavHakki(): int
    {
        return max(0, (int) config('isg.uzaktan_egitim.sinav_hakki', 3) - $this->donemSinavlari()->count());
    }

    /**
     * Üç sınavda da başarısız olan temel eğitime yeniden katılır: ders
     * ilerlemesi silinir, sınav hakkı yenilenir. Eski sınavlar kayıtta kalır.
     */
    public function yenidenBaslat(): void
    {
        $this->ilerlemeler()->delete();
        $this->forceFill([
            'yeniden_baslatma' => $this->yeniden_baslatma + 1,
            'yeniden_baslatildi_at' => now(),
            'durum' => 'atandi',
        ])->save();
    }

    /** Ön test (seviye tespiti) gerekiyor mu — Md.16/1. */
    public function onTestBekliyorMu(): bool
    {
        return $this->on_test_at === null && ! $this->basariliMi() && $this->paket?->sorular()->exists();
    }

    /** Fiilen izlenen toplam süre (saniye). */
    public function izlenenSure(): int
    {
        return (int) $this->ilerlemeler()->sum('izlenen_sn');
    }

    /** Md.12/3: tehlikeli / çok tehlikeli işyerinde 4. konu başlığı yüz yüze verilir. */
    public function dorduncuKonuYuzYuzeMi(): bool
    {
        return in_array($this->calisan?->firma?->tehlike_sinifi, config('isg.uzaktan_egitim.yuz_yuze_dorduncu_konu', []), true);
    }

    public function turEtiketi(): string
    {
        return config('isg.uzaktan_egitim.egitim_turleri.'.$this->egitim_turu)
            ?? config('isg.uzaktan_egitim.eski_egitim_turleri.'.$this->egitim_turu, (string) $this->egitim_turu);
    }

    /** İlerleme/sınav durumuna göre `durum` alanını günceller. */
    public function durumuTazele(): void
    {
        $yeni = match (true) {
            $this->basariliMi() => 'tamamlandi',
            $this->donemSinavlari()->exists() => 'basarisiz',
            $this->izlenenDersSayisi() > 0 => 'devam',
            default => 'atandi',
        };

        if ($yeni === 'tamamlandi' && ! $this->tamamlandi_at) {
            $this->tamamlandi_at = now();
        }

        $yeniTamamlandi = $yeni === 'tamamlandi' && $this->durum !== 'tamamlandi';

        if ($this->durum !== $yeni || $this->isDirty('tamamlandi_at')) {
            $this->durum = $yeni;
            $this->save();
        }

        if ($yeniTamamlandi) {
            $this->egitimKaydinaIsle();
        }
    }

    /**
     * Temel İSG eğitimi (ilk defa / yenileme) tamamlanınca çalışanın Eğitim
     * Kayıtları'na tamamlanma tarihiyle işlenir — personel dosyası, eğitim
     * matrisi ve Yenileme Takibi bunu görür. Daha yeni bir kayıt varsa
     * dokunulmaz. İşbaşı eğitimi bu listede ayrı tür olmadığından işlenmez.
     * Tehlikeli / çok tehlikeli işyerinde 4. konu yüz yüze verilmeden temel
     * eğitim tamamlanmış sayılmaz (Md.12/3) — yüz yüze katılım formu girilince
     * Yenileme Takibi onu esas alır.
     */
    public function egitimKaydinaIsle(): ?EgitimKaydi
    {
        if (! $this->tamamlandi_at || ! $this->egitimKaydinaIslenirMi()) {
            return null;
        }

        $kayit = EgitimKaydi::firstOrNew(['calisan_id' => $this->calisan_id, 'tur' => EgitimTakibi::TEMEL_TUR]);

        if ($kayit->exists && $kayit->tarih && $kayit->tarih->gte($this->tamamlandi_at->copy()->startOfDay())) {
            return $kayit;
        }

        $kayit->fill([
            'tarih' => $this->tamamlandi_at->toDateString(),
            'notlar' => 'Uzaktan eğitim: '.($this->paket?->ad ?? '').' (otomatik)',
        ])->save();

        return $kayit;
    }

    public function egitimKaydinaIslenirMi(): bool
    {
        return in_array($this->egitim_turu, ['ilk_defa', 'yenileme'], true) && ! $this->dorduncuKonuYuzYuzeMi();
    }

    public function durumEtiketi(): string
    {
        return config('isg.uzaktan_egitim.durumlar.'.$this->durum, $this->durum);
    }

    public function gecikti(): bool
    {
        return $this->son_tarih && $this->son_tarih->isPast() && ! $this->basariliMi();
    }
}
